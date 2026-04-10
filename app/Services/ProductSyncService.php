<?php

namespace App\Services;

use App\Models\AccountShopTiktok;
use App\Models\ProdukSaya;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * ProductSyncService — Sinkronisasi daftar produk dari TikTok API ke tabel produk_saya.
 *
 * Service ini bisa dipanggil dari:
 *   - TiktokAuthController  (manual sync produk + OAuth callback)
 *   - StockController       (cron otomatis — sync produk sebelum dispatch inventory jobs)
 *
 * Alur syncForAccount():
 *   1. Refresh access token jika expired
 *   2. Ambil shop_cipher dari TikTok jika belum ada
 *   3. fetchAllProducts (paginated) dari TikTok API
 *   4. Upsert ke tabel produk_saya (by account_id + sku_id + platform)
 *   5. Update last_sync_at di account
 */
class ProductSyncService
{
    public function __construct(
        private TiktokApiService $tiktokService
    ) {}

    /* ===================================================================
     *  SYNC FOR ACCOUNT — Fetch all products + upsert produk_saya
     * =================================================================== */

    /**
     * @return array{saved: int, pages: int, skipped: int, error: string|null}
     */
    public function syncForAccount(AccountShopTiktok $account): array
    {
        try {
            // 1. Refresh token jika expired
            $this->ensureFreshToken($account);

            // 2. Ambil shop_cipher dari TikTok jika masih kosong
            if (!$account->shop_cipher) {
                $shops = $this->tiktokService->getAuthShop($account->access_token);
                if (!empty($shops)) {
                    $account->update(['shop_cipher' => $shops[0]['cipher'] ?? null]);
                    $account->refresh();
                }
            }

            if (!$account->shop_cipher) {
                return [
                    'saved'   => 0,
                    'pages'   => 0,
                    'skipped' => 0,
                    'error'   => 'shop_cipher tidak ditemukan untuk akun: ' . ($account->seller_name ?? $account->id),
                ];
            }

            // 3. Fetch semua produk (paginated)
            $result = $this->tiktokService->fetchAllProducts(
                $account->access_token,
                $account->shop_cipher
            );

            // 4. Upsert ke produk_saya
            $saved = $this->saveProducts($account->id, $result['products'] ?? []);

            // 5. Tandai waktu sync terakhir
            $account->update(['last_sync_at' => now()]);

            Log::info("ProductSyncService: sync selesai [{$account->shop_name}] — {$saved} produk, {$result['total_pages']} halaman, {$result['total_skipped']} dilewati");

            return [
                'saved'   => $saved,
                'pages'   => $result['total_pages'] ?? 0,
                'skipped' => $result['total_skipped'] ?? 0,
                'error'   => null,
            ];
        } catch (\Throwable $e) {
            Log::error("ProductSyncService::syncForAccount gagal [account_id={$account->id}]: " . $e->getMessage());

            return [
                'saved'   => 0,
                'pages'   => 0,
                'skipped' => 0,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /* ===================================================================
     *  SAVE PRODUCTS — Upsert array produk ke tabel produk_saya
     * =================================================================== */

    /**
     * Upsert produk ke tabel produk_saya.
     * Key unik: (account_id, sku_id, platform).
     */
    public function saveProducts(int $accountId, array $products): int
    {
        if (empty($products)) {
            return 0;
        }

        $saved  = 0;
        $chunks = array_chunk($products, 100);

        foreach ($chunks as $chunk) {
            $rows = array_map(function ($p) use ($accountId) {
                return [
                    'account_id'     => $accountId,
                    'product_id'     => $p['product_id'],
                    'sku_id'         => $p['sku_id'],
                    'platform'       => $p['platform'],
                    'title'          => $p['title'],
                    'product_status' => $p['product_status'],
                    'quantity'       => $p['quantity'],
                    'price'          => $p['price'],
                    'seller_sku'     => $p['seller_sku'],
                    'status_info'    => $p['status_info'],
                    'current_status' => $p['current_status'],
                    'updated_at'     => now(),
                    'created_at'     => now(),
                ];
            }, $chunk);

            // Upsert: insert or update by unique key (account_id, sku_id, platform)
            ProdukSaya::upsert(
                $rows,
                ['account_id', 'sku_id', 'platform'],
                ['title', 'product_status', 'quantity', 'price', 'seller_sku', 'status_info', 'current_status', 'updated_at']
            );

            $saved += count($chunk);
        }

        return $saved;
    }

    /* ===================================================================
     *  ENSURE FRESH TOKEN — Refresh jika expired, update DB in-place
     * =================================================================== */

    /**
     * Pastikan access token masih valid.
     * Jika expired → refresh & simpan ke DB, lalu panggil $account->refresh().
     */
    public function ensureFreshToken(AccountShopTiktok $account): void
    {
        if (!$account->isTokenExpired()) {
            return;
        }

        Log::info("ProductSyncService: refresh token untuk akun [{$account->id}] {$account->seller_name}");

        $now       = now();
        $tokenData = $this->tiktokService->refreshAccessToken($account->refresh_token);

        $account->update([
            'access_token'            => $tokenData['access_token'],
            'access_token_expire_in'  => Carbon::createFromTimestamp($tokenData['access_token_expire_in'] ?? 0),
            'refresh_token'           => $tokenData['refresh_token'] ?? $account->refresh_token,
            'refresh_token_expire_in' => isset($tokenData['refresh_token_expire_in'])
                ? Carbon::createFromTimestamp($tokenData['refresh_token_expire_in'])
                : $account->refresh_token_expire_in,
            'token_obtained_at'       => $now,
        ]);

        $account->refresh();
    }
}
