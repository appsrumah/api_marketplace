<?php

namespace App\Http\Controllers;

use App\Models\AccountShopTiktok;
use App\Models\ProdukSaya;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $accounts = AccountShopTiktok::forUser()->withCount('produk')->latest()->get();
        $accountIds = $accounts->pluck('id');

        $stats = [
            'total_accounts'   => $accounts->count(),
            'active_accounts'  => $accounts->where('status', 'active')->count(),
            'total_products'   => ProdukSaya::whereIn('account_id', $accountIds)->count(),
            'total_tiktok'     => ProdukSaya::whereIn('account_id', $accountIds)->where('platform', 'TIKTOK')->count(),
            'total_tokopedia'  => ProdukSaya::whereIn('account_id', $accountIds)->where('platform', 'TOKOPEDIA')->count(),
        ];

        // Stock sync summary
        $syncStats = [
            'siap_sync'    => ProdukSaya::whereIn('account_id', $accountIds)
                ->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])
                ->where('product_status', 'ACTIVATE')
                ->whereNotNull('seller_sku')->where('seller_sku', '!=', '')->count(),
            'jobs_pending' => 0,
            'last_sync'    => AccountShopTiktok::forUser()->whereNotNull('last_update_stock')
                ->orderByDesc('last_update_stock')->value('last_update_stock'),
        ];

        try {
            $syncStats['jobs_pending'] = DB::table('jobs')->where('queue', 'tiktok-inventory')->count();
        } catch (\Throwable $e) { /* jobs table might not exist yet */
        }

        return view('dashboard', compact('accounts', 'stats', 'syncStats'));
    }
}
