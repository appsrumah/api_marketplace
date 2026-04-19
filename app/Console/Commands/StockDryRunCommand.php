<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AccountShopTiktok;
use App\Models\AccountShopShopee;
use App\Models\ProdukSaya;
use App\Services\PosStockService;

class StockDryRunCommand extends Command
{
    protected $signature = 'stock:dry-run
        {--accountId= : Account ID to run for}
        {--platform= : Platform filter (tiktok|shopee)}
        {--limit= : Limit number of rows per account}
    ';

    protected $description = 'Dry-run stock sync: list SKUs that would be pushed (no API calls, no DB writes)';

    public function handle(): int
    {
        $accountId = $this->option('accountId');
        $platform  = strtolower($this->option('platform') ?? '');
        $limit     = (int) ($this->option('limit') ?: 0) ?: null;

        /** @var PosStockService $posStock */
        $posStock = $this->laravel->make(PosStockService::class);

        $accounts = collect();

        if ($accountId) {
            $a = AccountShopTiktok::find($accountId) ?? AccountShopShopee::find($accountId);
            if (! $a) {
                $this->error("Account not found: {$accountId}");
                return 1;
            }
            $accounts->push($a);
        } else {
            if ($platform === 'shopee') {
                $accounts = AccountShopShopee::where('status', 'active')->get();
            } elseif ($platform === 'tiktok') {
                $accounts = AccountShopTiktok::whereNotNull('id_outlet')->get();
            } else {
                $accounts = AccountShopTiktok::whereNotNull('id_outlet')->get()->merge(AccountShopShopee::where('status', 'active')->get());
            }
        }

        foreach ($accounts as $account) {
            $this->info("\n== Account: " . ($account->seller_name ?? $account->shop_name ?? 'unknown') . " (ID: {$account->id}) ==");

            // Determine which produk_saya rows to consider, following job logic
            if ($account instanceof AccountShopShopee) {
                $query = ProdukSaya::where('account_id', $account->id)
                    ->where('platform', 'SHOPEE')
                    ->where('product_status', 'ACTIVATE')
                    ->whereNotNull('seller_sku')
                    ->where('seller_sku', '!=', '');
            } else {
                $query = ProdukSaya::where('account_id', $account->id)
                    ->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])
                    ->where('product_status', 'ACTIVATE')
                    ->whereNotNull('seller_sku')
                    ->where('seller_sku', '!=', '');
            }

            $total = $query->count();
            if ($total === 0) {
                $this->line('  Tidak ada produk ACTIVATE dengan seller_sku untuk akun ini.');
                continue;
            }

            $products = $query->select('product_id', 'sku_id', 'seller_sku', 'title', 'quantity', 'last_pushed_stock')->distinct()->get();

            $skus = $products->pluck('seller_sku')->unique()->values()->all();
            $stockMap = $posStock->getStockBulk($skus, $account->id_outlet ?? null);

            $rows = [];
            foreach ($products as $p) {
                $posQty = $stockMap[$p->seller_sku] ?? 0;
                $lastPushed = isset($p->last_pushed_stock) ? (int) $p->last_pushed_stock : (int) $p->quantity;
                if ($posQty !== $lastPushed) {
                    $rows[] = [
                        'product_id' => $p->product_id,
                        'sku_id'     => $p->sku_id,
                        'seller_sku' => $p->seller_sku,
                        'title'      => $p->title,
                        'pos_stock'  => $posQty,
                        'mkt_stock'  => (int) $p->quantity,
                        'last_pushed' => $lastPushed,
                    ];
                }
                if ($limit && count($rows) >= $limit) break;
            }

            if (empty($rows)) {
                $this->line('  Semua stok sama — tidak ada SKU yang perlu dipush.');
                continue;
            }

            $this->table([
                'product_id',
                'sku_id',
                'seller_sku',
                'pos_stock',
                'mkt_stock',
                'last_pushed',
                'title'
            ], array_map(function ($r) {
                return [$r['product_id'], $r['sku_id'], $r['seller_sku'], $r['pos_stock'], $r['mkt_stock'], $r['last_pushed'], $r['title']];
            }, $rows));

            $this->info('  Summary: ' . count($rows) . ' SKU would be pushed (of ' . $total . ' total).');
        }

        $this->info('\nDry-run selesai. Tidak ada perubahan pada DB/API.');
        return 0;
    }
}
