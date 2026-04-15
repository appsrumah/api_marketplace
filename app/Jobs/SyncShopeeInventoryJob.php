<?php

namespace App\Jobs;

use App\Models\AccountShopShopee;
use App\Models\ProdukSaya;
use App\Models\StockSyncLog;
use App\Services\PosStockService;
use App\Services\ShopeeApiService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * SyncShopeeInventoryJob — Batch update stok 1 akun Shopee
 *
 * Mengikuti pola SyncAccountInventoryJob (TikTok):
 *   1. Guard: id_outlet harus ada
 *   2. Auto-refresh token jika expired
 *   3. Ambil produk ACTIVATE dari produk_saya (platform=SHOPEE)
 *   4. Ambil stok bulk dari POS
 *   5. Push stok ke Shopee API via updateStock()
 *
 * Shopee updateStock format (v2 — seller_stock):
 *   - item_id    : integer
 *   - stock_list : [{ model_id: int, seller_stock: [{ stock: int }] }]
 *
 * Karena sku_id di produk_saya sekarang = model_id (untuk varian) atau item_id (single-SKU),
 * cukup bandingkan sku_id == product_id untuk menentukan apakah ini varian atau tidak.
 */
class SyncShopeeInventoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public array $backoff = [120];
    public int $timeout = 600;

    public function __construct(
        public readonly int $accountId,
    ) {}

    public function handle(ShopeeApiService $shopeeService, PosStockService $posStock): void
    {
        $account  = AccountShopShopee::findOrFail($this->accountId);
        $cacheKey = "shopee_stock_sync_progress_{$this->accountId}";

        Log::info("▶ SyncShopeeInventoryJob mulai [{$account->seller_name}]", [
            'account_id' => $this->accountId,
        ]);

        // ── 1. Guard: id_outlet harus ada ────────────────────────────────
        if (!$account->id_outlet) {
            Log::warning("SyncShopeeInventoryJob: id_outlet kosong [{$account->seller_name}], skip.");
            Cache::put($cacheKey, [
                'status'       => 'skipped',
                'account_name' => $account->seller_name,
                'reason'       => 'id_outlet belum di-set',
                'updated_at'   => now()->toIso8601String(),
            ], 86400);
            $this->delete();
            return;
        }

        // ── 2. Catat status "mulai" ke Cache ─────────────────────────────
        Cache::put($cacheKey, [
            'status'       => 'starting',
            'account_name' => $account->seller_name,
            'total'        => 0,
            'current'      => 0,
            'success'      => 0,
            'failed'       => 0,
            'started_at'   => now()->toIso8601String(),
            'updated_at'   => now()->toIso8601String(),
        ], 3600);

        // ── 3. Auto-refresh token jika expired ───────────────────────────
        if ($account->isTokenExpired()) {
            $this->doRefreshToken($account, $shopeeService);
        }

        // ── 4. Ambil semua produk SHOPEE aktif dari produk_saya ──────────
        $products = ProdukSaya::where('account_id', $this->accountId)
            ->where('platform', 'SHOPEE')
            ->where('product_status', 'ACTIVATE')
            ->whereNotNull('seller_sku')
            ->where('seller_sku', '!=', '')
            ->select('product_id', 'sku_id', 'seller_sku', 'title', 'quantity')
            ->distinct()
            ->get();

        if ($products->isEmpty()) {
            Log::info("SyncShopeeInventoryJob: tidak ada produk aktif [{$account->seller_name}], selesai.");
            Cache::put($cacheKey, [
                'status'       => 'completed',
                'account_name' => $account->seller_name,
                'total'        => 0,
                'current'      => 0,
                'success'      => 0,
                'failed'       => 0,
                'reason'       => 'Tidak ada produk SHOPEE ACTIVATE dengan seller_sku',
                'started_at'   => Cache::get($cacheKey)['started_at'] ?? now()->toIso8601String(),
                'finished_at'  => now()->toIso8601String(),
                'updated_at'   => now()->toIso8601String(),
            ], 86400);
            return;
        }

        // ── 5. Ambil SEMUA stok dari POS dalam SATU query ────────────────
        $skus     = $products->pluck('seller_sku')->unique()->values()->all();
        $stockMap = $posStock->getStockBulk($skus, $account->id_outlet);

        $total = $products->count();
        Log::info("SyncShopeeInventoryJob: stok diambil bulk dari POS", [
            'akun'      => $account->seller_name,
            'sku_count' => count($skus),
        ]);

        Cache::put($cacheKey, array_merge(Cache::get($cacheKey, []), [
            'status'     => 'running',
            'total'      => $total,
            'updated_at' => now()->toIso8601String(),
        ]), 3600);

        // ── 6. Push stok ke Shopee API ───────────────────────────────────
        // Group products by item_id to batch updateStock calls
        $grouped = $products->groupBy('product_id');
        $success = 0;
        $failed  = 0;
        $i       = 0;
        $startedAt = Cache::get($cacheKey)['started_at'] ?? now()->toIso8601String();
        $shopId    = (int) $account->shop_id;

        foreach ($grouped as $itemId => $variants) {
            // Build stock_list for this item
            $stockList = [];

            foreach ($variants as $product) {
                $i++;
                $qty = $stockMap[$product->seller_sku] ?? 0;

                // sku_id sekarang = model_id (varian) atau item_id (single-SKU)
                // Jika sku_id == product_id → produk tanpa varian → model_id = 0
                // Jika sku_id != product_id → sku_id IS the model_id langsung
                $isVariant = (string) $product->sku_id !== (string) $product->product_id;
                $modelId   = $isVariant ? (int) $product->sku_id : 0;

                $stockList[] = [
                    'model_id'    => $modelId,
                    'seller_stock' => [
                        ['stock' => max(0, $qty)],
                    ],
                ];
            }

            // Dedupe stock_list by model_id to avoid Shopee "Repeat model_id" error
            $deduped = [];
            $duplicates = [];
            foreach ($stockList as $entry) {
                $mid = (string) ($entry['model_id'] ?? '0');
                if (isset($deduped[$mid])) {
                    $duplicates[] = $mid;
                }
                // overwrite if duplicate — last occurrence wins
                $deduped[$mid] = $entry;
            }
            if (!empty($duplicates)) {
                Log::warning('SyncShopeeInventoryJob: duplicate model_id detected, deduping', [
                    'account_id' => $this->accountId,
                    'item_id'    => $itemId,
                    'duplicates' => array_values(array_unique($duplicates)),
                ]);
            }
            $stockList = array_values($deduped);

            try {
                $apiResult = $shopeeService->updateStock(
                    accessToken: $account->access_token,
                    shopId: $shopId,
                    itemId: (int) $itemId,
                    stockList: $stockList,
                );
                $success += count($variants);

                // ✅ Update quantity + log ke stock_sync_logs
                foreach ($variants as $product) {
                    $pushedQty = $stockMap[$product->seller_sku] ?? 0;
                    $oldQty    = (int) $product->quantity;

                    try {
                        ProdukSaya::where('account_id', $this->accountId)
                            ->where('product_id', $itemId)
                            ->where('sku_id', $product->sku_id)
                            ->update(['quantity' => max(0, $pushedQty)]);

                        StockSyncLog::create([
                            'account_id'   => $this->accountId,
                            'platform'     => 'SHOPEE',
                            'account_name' => $account->seller_name,
                            'product_id'   => $product->product_id,
                            'sku_id'       => $product->sku_id,
                            'seller_sku'   => $product->seller_sku,
                            'title'        => $product->title,
                            'old_quantity' => $oldQty,
                            'pos_stock'    => $pushedQty,
                            'pushed_stock' => $pushedQty,
                            'status'       => 'success',
                            'api_response' => $apiResult,
                            'synced_at'    => now(),
                        ]);
                    } catch (\Throwable $logErr) {
                        Log::warning('SyncShopeeInventoryJob: gagal tulis log/update quantity', [
                            'seller_sku' => $product->seller_sku,
                            'error'      => $logErr->getMessage(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                $failed += count($variants);
                Log::warning("SyncShopeeInventoryJob: gagal push item [{$itemId}]", [
                    'error' => $e->getMessage(),
                ]);

                // ✅ Log gagal ke stock_sync_logs per varian
                foreach ($variants as $product) {
                    $pushedQty = $stockMap[$product->seller_sku] ?? 0;
                    try {
                        StockSyncLog::create([
                            'account_id'   => $this->accountId,
                            'platform'     => 'SHOPEE',
                            'account_name' => $account->seller_name,
                            'product_id'   => $product->product_id,
                            'sku_id'       => $product->sku_id,
                            'seller_sku'   => $product->seller_sku,
                            'title'        => $product->title,
                            'old_quantity' => (int) $product->quantity,
                            'pos_stock'    => $pushedQty,
                            'pushed_stock' => 0,
                            'status'       => 'failed',
                            'error_message' => $e->getMessage(),
                            'synced_at'    => now(),
                        ]);
                    } catch (\Throwable $logErr) {
                        Log::warning('SyncShopeeInventoryJob: gagal tulis failure log', [
                            'seller_sku' => $product->seller_sku,
                            'error'      => $logErr->getMessage(),
                        ]);
                    }
                }

                if (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), 'rate')) {
                    sleep(1);
                }
            }

            // Update Cache setiap 10 item groups
            if ($i % 20 === 0 || $i === $total) {
                Cache::put($cacheKey, [
                    'status'       => 'running',
                    'account_name' => $account->seller_name,
                    'total'        => $total,
                    'current'      => $i,
                    'success'      => $success,
                    'failed'       => $failed,
                    'started_at'   => $startedAt,
                    'updated_at'   => now()->toIso8601String(),
                ], 3600);
            }

            usleep(200_000); // 200ms jeda antar item — hindari rate limit Shopee
        }

        $account->update(['last_update_stock' => now()]);

        Cache::put($cacheKey, [
            'status'       => 'completed',
            'account_name' => $account->seller_name,
            'total'        => $total,
            'current'      => $total,
            'success'      => $success,
            'failed'       => $failed,
            'started_at'   => $startedAt,
            'finished_at'  => now()->toIso8601String(),
            'updated_at'   => now()->toIso8601String(),
        ], 86400);

        Log::info("✅ SyncShopeeInventoryJob selesai [{$account->seller_name}]", [
            'total'   => $total,
            'success' => $success,
            'failed'  => $failed,
        ]);
    }

    private function doRefreshToken(AccountShopShopee $account, ShopeeApiService $shopeeService): void
    {
        Log::info("SyncShopeeInventoryJob: token expired, refresh [{$account->seller_name}]");

        $shopId    = (int) $account->shop_id;
        $tokenData = $shopeeService->refreshAccessToken($account->refresh_token, $shopId);

        if (empty($tokenData['access_token'])) {
            throw new \RuntimeException("Refresh token gagal untuk [{$account->seller_name}]");
        }

        $expireIn        = (int) ($tokenData['expire_in'] ?? 14400);
        $refreshExpireIn = (int) ($tokenData['refresh_token_expire_in'] ?? 2592000);

        $account->update([
            'access_token'            => $tokenData['access_token'],
            'access_token_expire_in'  => now()->addSeconds($expireIn),
            'refresh_token'           => $tokenData['refresh_token'] ?? $account->refresh_token,
            'refresh_token_expire_in' => now()->addSeconds($refreshExpireIn),
            'token_obtained_at'       => now(),
            'status'                  => 'active',
        ]);
        $account->refresh();
    }

    public function failed(\Throwable $e): void
    {
        Log::error("❌ SyncShopeeInventoryJob GAGAL TOTAL [account_id: {$this->accountId}]", [
            'error' => $e->getMessage(),
        ]);

        $cacheKey = "shopee_stock_sync_progress_{$this->accountId}";
        $prev     = Cache::get($cacheKey, []);
        Cache::put($cacheKey, array_merge($prev, [
            'status'     => 'failed',
            'error'      => $e->getMessage(),
            'failed_at'  => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]), 86400);
    }
}
