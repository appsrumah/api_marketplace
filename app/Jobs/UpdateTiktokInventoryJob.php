<?php

namespace App\Jobs;

use App\Models\AccountShopTiktok;
use App\Services\PosStockService;
use App\Services\TiktokApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateTiktokInventoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan jika gagal (termasuk percobaan pertama).
     * Artinya: 1 kali coba, 2 kali retry = total 3 percobaan.
     */
    public int $tries = 3;

    /**
     * Jeda antar retry (detik). Percobaan ke-1 tunggu 60 detik, ke-2 tunggu 120 detik.
     */
    public array $backoff = [60, 120];

    /**
     * Timeout per job (detik).
     */
    public int $timeout = 60;

    public function __construct(
        public readonly int    $accountId,
        public readonly string $productId,
        public readonly string $skuId,
        public readonly string $sellerSku,
        public readonly int    $idOutlet,
    ) {}

    public function handle(TiktokApiService $tiktokService, PosStockService $posStock): void
    {
        // ── 1. Ambil stok realtime dari DB POS (PO - SO) ─────────────────
        // seller_sku dipakai untuk lookup POS (nomor_product = seller_sku)
        // Jika seller_sku kosong → tidak bisa lookup POS → qty = 0, job di-skip
        if (empty($this->sellerSku)) {
            Log::warning('UpdateTiktokInventoryJob: seller_sku kosong, job di-skip (isi seller_sku di TikTok Seller Center)', [
                'sku_id'     => $this->skuId,
                'product_id' => $this->productId,
                'account_id' => $this->accountId,
            ]);
            $this->delete(); // hapus dari queue, tidak perlu retry
            return;
        }

        $quantity = $posStock->getStock($this->sellerSku, $this->idOutlet);

        Log::info('UpdateTiktokInventoryJob: stok dari POS', [
            'seller_sku' => $this->sellerSku,
            'id_outlet'  => $this->idOutlet,
            'qty'        => $quantity,
        ]);

        // ── 2. Ambil akun & auto-refresh token jika expired ──────────────
        $account = AccountShopTiktok::findOrFail($this->accountId);

        if ($account->isTokenExpired()) {
            Log::info('UpdateTiktokInventoryJob: token expired, refresh', [
                'account_id' => $this->accountId,
            ]);

            $newToken = $tiktokService->refreshAccessToken($account->refresh_token);
            $account->update([
                'access_token'           => $newToken['access_token'],
                'access_token_expire_in' => now()->addSeconds($newToken['access_token_expire_in'] ?? 0),
                'refresh_token'          => $newToken['refresh_token'] ?? $account->refresh_token,
                'refresh_token_expire_in' => isset($newToken['refresh_token_expire_in'])
                    ? now()->addSeconds($newToken['refresh_token_expire_in'])
                    : $account->refresh_token_expire_in,
                'token_obtained_at'      => now(),
            ]);
            $account->refresh();
        }

        // ── 3. Push stok ke TikTok API ───────────────────────────────────
        $tiktokService->updateInventory(
            accessToken: $account->access_token,
            shopCipher: $account->shop_cipher,
            productId: $this->productId,
            skuId: $this->skuId,
            quantity: $quantity,
        );

        // ── 4. Catat waktu update terakhir di tabel account ──────────────
        $account->update(['last_update_stock' => now()]);

        Log::info('✅ UpdateTiktokInventoryJob: berhasil', [
            'seller_sku' => $this->sellerSku,
            'sku_id'     => $this->skuId,
            'qty'        => $quantity,
            'seller'     => $account->seller_name,
        ]);
    }

    /**
     * Dipanggil setelah semua percobaan habis dan masih gagal.
     */
    public function failed(\Throwable $e): void
    {
        Log::error('❌ UpdateTiktokInventoryJob: GAGAL setelah semua retry', [
            'account_id' => $this->accountId,
            'seller_sku' => $this->sellerSku,
            'product_id' => $this->productId,
            'sku_id'     => $this->skuId,
            'error'      => $e->getMessage(),
        ]);
    }
}
