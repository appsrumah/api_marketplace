<?php

namespace App\Http\Controllers;

use App\Models\AccountShopShopee;
use App\Models\AccountShopTiktok;
use Illuminate\Http\Request;
use App\Models\ProdukSaya;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user       = \Illuminate\Support\Facades\Auth::user();
        $isSuperAdmin = $user && $user->isSuperAdmin();

        // ── TikTok accounts ───────────────────────────────────────────
        // Super Admin melihat SEMUA akun tanpa filter kepemilikan/status
        if ($isSuperAdmin) {
            $tiktokAccounts = AccountShopTiktok::withCount('produk')->latest()->get()
                ->each(fn($a) => $a->platform = 'TIKTOK');
        } else {
            $tiktokAccounts = AccountShopTiktok::forUser()->withCount('produk')->latest()->get()
                ->each(fn($a) => $a->platform = 'TIKTOK');
        }

        // ── Shopee accounts ───────────────────────────────────────────
        // Super Admin: semua akun termasuk non-active; User biasa: hanya active + milik sendiri
        if ($isSuperAdmin) {
            $shopeeAccounts = AccountShopShopee::withCount('produk')->latest()->get()
                ->each(fn($a) => $a->platform = 'SHOPEE');
        } else {
            $shopeeAccounts = AccountShopShopee::forUser()->where('status', 'active')
                ->withCount('produk')->latest()->get()
                ->each(fn($a) => $a->platform = 'SHOPEE');
        }

        // Gabungan untuk view
        $accounts = $tiktokAccounts->merge($shopeeAccounts);

        $tiktokAccountIds = $tiktokAccounts->pluck('id');
        $shopeeAccountIds = $shopeeAccounts->pluck('id');

        $stats = [
            'total_accounts'  => $accounts->count(),
            'active_accounts' => $tiktokAccounts->where('status', 'active')->count() + $shopeeAccounts->count(),
            'total_products'  => ProdukSaya::where(function ($q) use ($tiktokAccountIds, $shopeeAccountIds) {
                $q->where(fn($s) => $s->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])->whereIn('account_id', $tiktokAccountIds))
                    ->orWhere(fn($s) => $s->where('platform', 'SHOPEE')->whereIn('account_id', $shopeeAccountIds));
            })->count(),
            'total_tiktok'    => ProdukSaya::whereIn('account_id', $tiktokAccountIds)->where('platform', 'TIKTOK')->count(),
            'total_tokopedia' => ProdukSaya::whereIn('account_id', $tiktokAccountIds)->where('platform', 'TOKOPEDIA')->count(),
            'total_shopee'    => ProdukSaya::whereIn('account_id', $shopeeAccountIds)->where('platform', 'SHOPEE')->count(),
        ];

        // ── Stock sync summary ────────────────────────────────────────
        $lastSyncTiktok = ($isSuperAdmin ? AccountShopTiktok::query() : AccountShopTiktok::forUser())
            ->whereNotNull('last_update_stock')->max('last_update_stock');
        $lastSyncShopee = ($isSuperAdmin ? AccountShopShopee::query() : AccountShopShopee::forUser())
            ->whereNotNull('last_update_stock')->max('last_update_stock');
        $lastSync       = collect([$lastSyncTiktok, $lastSyncShopee])->filter()->max();

        $syncStats = [
            'siap_sync'    => ProdukSaya::where(function ($q) use ($tiktokAccountIds, $shopeeAccountIds) {
                $q->where(fn($s) => $s->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])->whereIn('account_id', $tiktokAccountIds))
                    ->orWhere(fn($s) => $s->where('platform', 'SHOPEE')->whereIn('account_id', $shopeeAccountIds));
            })->where('product_status', 'ACTIVATE')->whereNotNull('seller_sku')->where('seller_sku', '!=', '')->count(),
            'jobs_pending' => 0,
            'last_sync'    => $lastSync,
        ];

        try {
            $syncStats['jobs_pending'] = DB::table('jobs')
                ->whereIn('queue', ['tiktok-inventory', 'shopee-inventory'])->count();
        } catch (\Throwable $e) { /* jobs table might not exist yet */
        }

        return view('dashboard', compact('accounts', 'stats', 'syncStats'));
    }
}
