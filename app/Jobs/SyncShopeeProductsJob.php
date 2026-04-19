<?php

namespace App\Jobs;

use App\Models\AccountShopShopee;
use App\Services\ShopeeProductSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SyncShopeeProductsJob — Sinkronisasi daftar produk 1 akun Shopee ke produk_saya.
 *
 * Dijalankan di queue agar tidak memblokir HTTP request (340+ produk bisa >60 detik).
 */
class SyncShopeeProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Beri waktu cukup: 340 produk × ~200ms per model-list call + overhead */
    public int $timeout = 600;
    public int $tries   = 2;
    public array $backoff = [60];

    public function __construct(
        public readonly int $accountId,
    ) {}

    public function handle(ShopeeProductSyncService $syncService): void
    {
        $account = AccountShopShopee::findOrFail($this->accountId);

        Log::info("▶ SyncShopeeProductsJob mulai [{$account->seller_name}]", [
            'account_id' => $this->accountId,
        ]);

        $result = $syncService->syncForAccount($account);

        if ($result['error']) {
            Log::warning("SyncShopeeProductsJob selesai dengan error [{$account->seller_name}]", [
                'saved' => $result['saved'],
                'error' => $result['error'],
            ]);
        } else {
            Log::info("✅ SyncShopeeProductsJob selesai [{$account->seller_name}]", [
                'saved' => $result['saved'],
                'pages' => $result['pages'],
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SyncShopeeProductsJob GAGAL [account_id={$this->accountId}]: " . $e->getMessage());
    }
}
