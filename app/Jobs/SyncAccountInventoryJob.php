<?php

namespace App\Jobs;

use App\Models\AccountShopTiktok;
use App\Models\ProdukSaya;
use App\Models\StockSyncLog;
use App\Services\PosStockService;
use App\Services\TiktokApiService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * SyncAccountInventoryJob — Batch update stok 1 akun ke TikTok
 *
 * STRATEGI BARU (vs UpdateTiktokInventoryJob lama):
 *   Lama : 1 job per SKU  → N jobs → N DB queries → N HTTP requests  (LAMBAT)
 *   Baru : 1 job per akun → 1 job  → 1 DB query   → N HTTP requests  (CEPAT)
 *
 * Keunggulan:
 *  - getStockBulk() ambil semua stok dalam 1 query ke DB POS
 *  - Queue tidak penuh ribuan jobs — cukup 1 job per akun
 *  - Tidak ada penumpukan antrian saat akun punya banyak SKU
 */
class SyncAccountInventoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Jumlah retry jika gagal total (termasuk pertama) */
    public int $tries = 2;

    /** Jeda sebelum retry — 2 menit */
    public array $backoff = [120];

    /** Timeout per job — 10 menit (cukup untuk 5000 SKU @ 100ms/SKU) */
    public int $timeout = 600;

    public function __construct(
        public readonly int $accountId,
    ) {}

    public function handle(TiktokApiService $tiktokService, PosStockService $posStock): void
    {
        $account  = AccountShopTiktok::findOrFail($this->accountId);
        $cacheKey = "stock_sync_progress_{$this->accountId}";

        Log::info("▶ SyncAccountInventoryJob mulai [{$account->seller_name}]", [
            'account_id' => $this->accountId,
        ]);

        // ── 1. Guard: id_outlet harus ada ────────────────────────────────
        if (!$account->id_outlet) {
            Log::warning("SyncAccountInventoryJob: id_outlet kosong [{$account->seller_name}], skip.");
            Cache::put($cacheKey, [
                'status'       => 'skipped',
                'account_name' => $account->seller_name,
                'reason'       => 'id_outlet belum di-set',
                'updated_at'   => now()->toIso8601String(),
            ], 86400);
            $this->delete();
            return;
        }

        // ── 1b. Acquire lock per account+outlet (prevent concurrent syncs)
        // Using MySQL GET_LOCK so Redis is not required.
        $lockName = "stock_sync_{$this->accountId}_{$account->id_outlet}";
        $gotLock = false;
        try {
            $res = \Illuminate\Support\Facades\DB::select('SELECT GET_LOCK(?, 0) as got', [$lockName]);
            $gotLock = isset($res[0]) && ((int) ($res[0]->got ?? 0) === 1);
            if (! $gotLock) {
                Log::info("SyncAccountInventoryJob: lock busy, skip run [{$account->seller_name}]");
                Cache::put($cacheKey, array_merge(Cache::get($cacheKey, []), [
                    'status' => 'skipped',
                    'reason' => 'another sync running',
                    'updated_at' => now()->toIso8601String(),
                ]), 600);
                return;
            }
        } catch (\Throwable $e) {
            // If DB locking fails, log and continue without lock
            Log::warning('SyncAccountInventoryJob: GET_LOCK failed, continuing without lock', ['error' => $e->getMessage()]);
            $gotLock = false;
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
            $this->doRefreshToken($account, $tiktokService);
        }

        // ── 4. Ambil semua produk aktif dari produk_saya (lokal DB) ──────
        // Tidak perlu tarik ulang dari TikTok API — data sudah ada di produk_saya.
        // Produk sync cukup dijalankan 1x/hari secara terpisah.
        $products = ProdukSaya::where('account_id', $this->accountId)
            ->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])
            ->where('product_status', 'ACTIVATE')
            ->whereNotNull('seller_sku')
            ->where('seller_sku', '!=', '')
            ->select('product_id', 'sku_id', 'seller_sku', 'title', 'quantity', 'platform')
            ->distinct()
            ->get();

        if ($products->isEmpty()) {
            Log::info("SyncAccountInventoryJob: tidak ada produk aktif [{$account->seller_name}], selesai.");
            Cache::put($cacheKey, [
                'status'       => 'completed',
                'account_name' => $account->seller_name,
                'total'        => 0,
                'current'      => 0,
                'success'      => 0,
                'failed'       => 0,
                'reason'       => 'Tidak ada produk ACTIVATE dengan seller_sku',
                'started_at'   => Cache::get($cacheKey)['started_at'] ?? now()->toIso8601String(),
                'finished_at'  => now()->toIso8601String(),
                'updated_at'   => now()->toIso8601String(),
            ], 86400);
            return;
        }

        // ── 5. Ambil SEMUA stok dari POS dalam SATU query ────────────────
        $skus     = $products->pluck('seller_sku')->unique()->values()->all();
        $stockMap = $posStock->getStockBulk($skus, $account->id_outlet);

        Log::info("SyncAccountInventoryJob: stok diambil bulk dari POS", [
            'akun'      => $account->seller_name,
            'sku_count' => count($skus),
        ]);

        // ── 5b. Filter hanya SKU yang stoknya BERUBAH (diff check)
        // Bandingkan pos_stock vs last_pushed_stock (fallback ke quantity bila null).
        $totalFetched = $products->count();
        $products = $products->filter(function ($product) use ($stockMap) {
            $posQty = $stockMap[$product->seller_sku] ?? 0;
            $lastPushed = isset($product->last_pushed_stock) ? (int) $product->last_pushed_stock : (int) $product->quantity;
            return $posQty !== $lastPushed;
        });

        $skipped = $totalFetched - $products->count();
        $total   = $products->count();

        Log::info("SyncAccountInventoryJob: diff check selesai", [
            'akun'     => $account->seller_name,
            'fetched'  => $totalFetched,
            'changed'  => $total,
            'skipped'  => $skipped,
        ]);

        if ($total === 0) {
            Log::info("SyncAccountInventoryJob: semua stok sama, tidak ada yang perlu di-push [{$account->seller_name}]");
            $account->update(['last_update_stock' => now()]);
            Cache::put($cacheKey, [
                'status'       => 'completed',
                'account_name' => $account->seller_name,
                'total'        => 0,
                'skipped'      => $skipped,
                'current'      => 0,
                'success'      => 0,
                'failed'       => 0,
                'reason'       => 'Semua stok tidak berubah',
                'started_at'   => Cache::get($cacheKey)['started_at'] ?? now()->toIso8601String(),
                'finished_at'  => now()->toIso8601String(),
                'updated_at'   => now()->toIso8601String(),
            ], 86400);
            return;
        }

        // Update Cache: jumlah produk yang akan di-push (hanya yang berubah)
        Cache::put($cacheKey, array_merge(Cache::get($cacheKey, []), [
            'status'     => 'running',
            'total'      => $total,
            'skipped'    => $skipped,
            'updated_at' => now()->toIso8601String(),
        ]), 3600);

        // ── 6. Push stok ke TikTok API in concurrent batches ─────────────
        $success = 0;
        $failed  = 0;
        $i       = 0;
        $startedAt = Cache::get($cacheKey)['started_at'] ?? now()->toIso8601String();

        // Build updates map keyed by unique key
        $updates = [];
        $productMap = [];
        foreach ($products as $product) {
            $qty = $stockMap[$product->seller_sku] ?? 0;
            $key = "{$product->product_id}|{$product->sku_id}|{$product->seller_sku}";
            $updates[$key] = [
                'accessToken' => $account->access_token,
                'shopCipher'  => $account->shop_cipher,
                'productId'   => $product->product_id,
                'skuId'       => $product->sku_id,
                'quantity'    => (int) $qty,
            ];
            $productMap[$key] = $product;
        }

        $chunkSize = 50; // concurrent requests per batch
        $chunks = array_chunk($updates, $chunkSize, true);

        foreach ($chunks as $chunk) {
            $results = $tiktokService->batchUpdateInventory($chunk, $chunkSize);

            foreach ($results as $key => $res) {
                $product = $productMap[$key] ?? null;
                if (! $product) {
                    continue;
                }
                $i++;
                $qty = $stockMap[$product->seller_sku] ?? 0;

                if (!empty($res['success'])) {
                    $success++;
                    $oldQty = (int) $product->quantity;
                    try {
                        ProdukSaya::where('account_id', $this->accountId)
                            ->where('product_id', $product->product_id)
                            ->where('sku_id', $product->sku_id)
                            ->update([
                                'quantity' => max(0, $qty),
                                'last_pushed_stock' => max(0, $qty),
                                'last_pushed_at' => now(),
                            ]);

                        StockSyncLog::create([
                            'account_id'   => $this->accountId,
                            'platform'     => $product->platform ?? 'TIKTOK',
                            'account_name' => $account->seller_name,
                            'product_id'   => $product->product_id,
                            'sku_id'       => $product->sku_id,
                            'seller_sku'   => $product->seller_sku,
                            'title'        => $product->title,
                            'old_quantity' => $oldQty,
                            'pos_stock'    => $qty,
                            'pushed_stock' => $qty,
                            'status'       => 'success',
                            'api_response' => $res['data'] ?? null,
                            'synced_at'    => now(),
                        ]);
                    } catch (\Throwable $logErr) {
                        Log::warning('SyncAccountInventoryJob: gagal tulis log/update quantity', [
                            'seller_sku' => $product->seller_sku,
                            'error'      => $logErr->getMessage(),
                        ]);
                    }
                } else {
                    $failed++;
                    $errMsg = $res['error'] ?? json_encode($res['data'] ?? []);
                    Log::warning("SyncAccountInventoryJob: gagal push SKU [{$product->seller_sku}]", [
                        'sku_id' => $product->sku_id,
                        'error'  => $errMsg,
                    ]);

                    try {
                        StockSyncLog::create([
                            'account_id'    => $this->accountId,
                            'platform'      => $product->platform ?? 'TIKTOK',
                            'account_name'  => $account->seller_name,
                            'product_id'    => $product->product_id,
                            'sku_id'        => $product->sku_id,
                            'seller_sku'    => $product->seller_sku,
                            'title'         => $product->title,
                            'old_quantity'  => (int) $product->quantity,
                            'pos_stock'     => $qty,
                            'pushed_stock'  => 0,
                            'status'        => 'failed',
                            'error_message' => $errMsg,
                            'synced_at'     => now(),
                        ]);
                    } catch (\Throwable $logErr) {
                        Log::warning('SyncAccountInventoryJob: gagal tulis failure log', [
                            'seller_sku' => $product->seller_sku,
                            'error'      => $logErr->getMessage(),
                        ]);
                    }
                }
            }

            // Update Cache per chunk
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

            usleep(100_000); // short pause between batches
        }

        $account->update(['last_update_stock' => now()]);

        // Tulis hasil akhir ke Cache (disimpan 24 jam agar bisa dilihat setelah selesai)
        Cache::put($cacheKey, [
            'status'       => 'completed',
            'account_name' => $account->seller_name,
            'total'        => $total,
            'skipped'      => $skipped ?? 0,
            'current'      => $total,
            'success'      => $success,
            'failed'       => $failed,
            'started_at'   => $startedAt,
            'finished_at'  => now()->toIso8601String(),
            'updated_at'   => now()->toIso8601String(),
        ], 86400);

        Log::info("✅ SyncAccountInventoryJob selesai [{$account->seller_name}]", [
            'total'   => $total,
            'skipped' => $skipped ?? 0,
            'success' => $success,
            'failed'  => $failed,
        ]);

        // Release DB lock jika kita berhasil mendapatkannya
        try {
            if (isset($gotLock) && $gotLock) {
                \Illuminate\Support\Facades\DB::select('SELECT RELEASE_LOCK(?)', [$lockName]);
            }
        } catch (\Throwable $e) {
            Log::warning('SyncAccountInventoryJob: gagal release DB lock', ['error' => $e->getMessage()]);
        }
    }

    /* ── Private helper: refresh token ───────────────────────────── */
    private function doRefreshToken(AccountShopTiktok $account, TiktokApiService $tiktokService): void
    {
        Log::info("SyncAccountInventoryJob: token expired, refresh [{$account->seller_name}]");

        $tokenData = $tiktokService->refreshAccessToken($account->refresh_token);

        if (empty($tokenData['access_token'])) {
            throw new \RuntimeException("Refresh token gagal untuk [{$account->seller_name}]");
        }

        $account->update([
            'access_token'            => $tokenData['access_token'],
            // ✅ Unix Timestamp — bukan addSeconds()
            'access_token_expire_in'  => Carbon::createFromTimestamp($tokenData['access_token_expire_in'] ?? 0),
            'refresh_token'           => $tokenData['refresh_token'] ?? $account->refresh_token,
            'refresh_token_expire_in' => isset($tokenData['refresh_token_expire_in'])
                ? Carbon::createFromTimestamp($tokenData['refresh_token_expire_in'])
                : $account->refresh_token_expire_in,
            'token_obtained_at'       => now(),
        ]);
        $account->refresh();
    }

    /* ── Dipanggil setelah semua retry habis ─────────────────────── */
    public function failed(\Throwable $e): void
    {
        Log::error("❌ SyncAccountInventoryJob GAGAL TOTAL [account_id: {$this->accountId}]", [
            'error' => $e->getMessage(),
        ]);

        // Tulis status gagal ke Cache agar dashboard menampilkan error
        $cacheKey = "stock_sync_progress_{$this->accountId}";
        $prev     = Cache::get($cacheKey, []);
        Cache::put($cacheKey, array_merge($prev, [
            'status'    => 'failed',
            'error'     => $e->getMessage(),
            'failed_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]), 86400);
    }
}
