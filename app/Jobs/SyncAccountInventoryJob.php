<?php

namespace App\Jobs;

use App\Models\AccountShopTiktok;
use App\Models\ProdukSaya;
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
            ->select('product_id', 'sku_id', 'seller_sku')
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

        $total = $products->count();
        Log::info("SyncAccountInventoryJob: stok diambil bulk dari POS", [
            'akun'      => $account->seller_name,
            'sku_count' => count($skus),
        ]);

        // Update Cache: total produk diketahui
        Cache::put($cacheKey, array_merge(Cache::get($cacheKey, []), [
            'status'     => 'running',
            'total'      => $total,
            'updated_at' => now()->toIso8601String(),
        ]), 3600);

        // ── 6. Push stok ke TikTok API satu per satu ─────────────────────
        $success = 0;
        $failed  = 0;
        $i       = 0;
        $startedAt = Cache::get($cacheKey)['started_at'] ?? now()->toIso8601String();

        foreach ($products as $product) {
            $i++;
            $qty = $stockMap[$product->seller_sku] ?? 0;

            try {
                $tiktokService->updateInventory(
                    accessToken: $account->access_token,
                    shopCipher: $account->shop_cipher,
                    productId: $product->product_id,
                    skuId: $product->sku_id,
                    quantity: $qty,
                );
                $success++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning("SyncAccountInventoryJob: gagal push SKU [{$product->seller_sku}]", [
                    'sku_id' => $product->sku_id,
                    'error'  => $e->getMessage(),
                ]);

                // Jika rate limit (429) → tunggu 1 detik lalu lanjut
                if (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), 'rate')) {
                    sleep(1);
                }
            }

            // Update Cache setiap 20 SKU agar dashboard bisa menampilkan progress
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

            usleep(100_000); // 100ms jeda antar SKU — hindari rate limit TikTok
        }

        $account->update(['last_update_stock' => now()]);

        // Tulis hasil akhir ke Cache (disimpan 24 jam agar bisa dilihat setelah selesai)
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

        Log::info("✅ SyncAccountInventoryJob selesai [{$account->seller_name}]", [
            'total'   => $total,
            'success' => $success,
            'failed'  => $failed,
        ]);
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
