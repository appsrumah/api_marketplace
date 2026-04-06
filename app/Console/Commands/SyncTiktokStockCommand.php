<?php

namespace App\Console\Commands;

use App\Jobs\UpdateTiktokInventoryJob;
use App\Models\AccountShopTiktok;
use App\Models\ProdukSaya;
use Illuminate\Console\Command;

class SyncTiktokStockCommand extends Command
{
    /**
     * Jalankan: php artisan tiktok:sync-stock
     * Jalankan untuk 1 akun: php artisan tiktok:sync-stock --account=5
     */
    protected $signature = 'tiktok:sync-stock
                            {--account= : ID account_shop_tiktok tertentu (opsional)}
                            {--dry-run  : Tampilkan daftar produk tanpa dispatch job}';

    protected $description = 'Dispatch job update stok TikTok dari DB POS untuk semua atau 1 akun';

    public function handle(): int
    {
        $accountId = $this->option('account');
        $dryRun    = $this->option('dry-run');

        // ── Ambil akun ─────────────────────────────────────────────────
        $query = AccountShopTiktok::query()->where('status', 'active');

        if ($accountId) {
            $query->where('id', $accountId);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->warn('Tidak ada akun TikTok aktif yang ditemukan.');
            return self::FAILURE;
        }

        $totalQueued  = 0;
        $totalSkipped = 0;

        foreach ($accounts as $account) {
            // Lewati akun yang tidak punya id_outlet (belum di-set)
            if (! $account->id_outlet) {
                $this->warn("  ⚠ Akun [{$account->id}] {$account->seller_name}: id_outlet belum di-set, dilewati.");
                $totalSkipped++;
                continue;
            }

            $this->info("▶ Akun [{$account->id}] {$account->seller_name} (outlet: {$account->id_outlet})");

            // Ambil semua produk TIKTOK + TOKOPEDIA ACTIVATE
            // seller_sku tidak wajib — jika kosong, job akan di-skip (stok tidak bisa diambil dari POS)
            $products = ProdukSaya::where('account_id', $account->id)
                ->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])
                ->where('product_status', 'ACTIVATE')
                ->get(['product_id', 'sku_id', 'seller_sku', 'platform', 'title']);

            if ($products->isEmpty()) {
                $this->warn("  ⚠ Tidak ada produk TIKTOK/TOKOPEDIA ACTIVATE untuk akun ini.");
                continue;
            }

            $this->line("  → {$products->count()} produk akan di-update stoknya");

            foreach ($products as $product) {
                if ($dryRun) {
                    $skuInfo = $product->seller_sku ?: '(kosong-skip)';
                    $this->line("  [DRY-RUN] [{$product->platform}] SKU={$skuInfo} | sku_id={$product->sku_id} | {$product->title}");
                    continue;
                }

                UpdateTiktokInventoryJob::dispatch(
                    accountId: $account->id,
                    productId: $product->product_id,
                    skuId: $product->sku_id,
                    sellerSku: $product->seller_sku ?? '',
                    idOutlet: $account->id_outlet,
                )->onQueue('tiktok-inventory');

                $totalQueued++;
            }
        }

        if ($dryRun) {
            $this->info('✅ Dry-run selesai. Tidak ada job yang di-dispatch.');
        } else {
            $this->info("✅ Selesai! {$totalQueued} job di-dispatch, {$totalSkipped} akun dilewati.");
        }

        return self::SUCCESS;
    }
}
