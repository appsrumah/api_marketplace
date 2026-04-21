<?php

namespace App\Http\Controllers;

use App\Models\AccountShopShopee;
use App\Models\AccountShopTiktok;
use Illuminate\Http\Request;
use App\Models\ProdukSaya;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user       = \Illuminate\Support\Facades\Auth::user();
        $isSuperAdmin = $user && $user->isSuperAdmin();

        // ── TikTok accounts ───────────────────────────────────────────
        // Super Admin melihat SEMUA akun tanpa filter kepemilikan/status
        // Pertahankan nilai platform dari DB (TIKTOK atau TOKOPEDIA), default TIKTOK jika kosong
        if ($isSuperAdmin) {
            $tiktokAccounts = AccountShopTiktok::withCount('produk')->latest()->get()
                ->each(fn($a) => $a->platform = ($a->getRawOriginal('platform') ?: 'TIKTOK'));
        } else {
            $tiktokAccounts = AccountShopTiktok::forUser()->withCount('produk')->latest()->get()
                ->each(fn($a) => $a->platform = ($a->getRawOriginal('platform') ?: 'TIKTOK'));
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

        // Gabungan SEMUA akun (active + non-active) untuk list di view
        $accounts = $tiktokAccounts->merge($shopeeAccounts);

        // Alias untuk debug compat
        $tiktokForList = $tiktokAccounts;
        $shopeeForList = $shopeeAccounts;

        $tiktokAccountIds = $tiktokAccounts->pluck('id');
        $shopeeAccountIds = $shopeeAccounts->pluck('id');

        // Hitungan langsung dari database (memperhitungkan scope forUser() dan isSuperAdmin)
        // total_accounts = SEMUA akun (active + non-active) agar sesuai dengan list di view
        $tiktokCountQuery = $isSuperAdmin ? AccountShopTiktok::query() : AccountShopTiktok::forUser();
        $shopeeCountQuery = $isSuperAdmin ? AccountShopShopee::query() : AccountShopShopee::forUser();

        $totalAccountsFromDb = $tiktokCountQuery->count() + $shopeeCountQuery->count();

        $tiktokActiveCount = $isSuperAdmin
            ? AccountShopTiktok::where('status', 'active')->count()
            : AccountShopTiktok::forUser()->where('status', 'active')->count();

        $shopeeActiveCount = $isSuperAdmin
            ? AccountShopShopee::where('status', 'active')->count()
            : AccountShopShopee::forUser()->where('status', 'active')->count();

        $activeAccountsFromDb = $tiktokActiveCount + $shopeeActiveCount;

        $stats = [
            // gunakan nilai yang dihitung langsung dari tabel DB
            'total_accounts'  => $totalAccountsFromDb,
            'active_accounts' => $activeAccountsFromDb,
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

        // Debug info to help track discrepancy between stats and list
        $debug = [
            'tiktok_active_db' => $tiktokActiveCount,
            'shopee_active_db' => $shopeeActiveCount,
            'active_accounts_db' => $activeAccountsFromDb,
            'accounts_list_count' => $accounts->count(),
            'tiktok_forlist_count' => isset($tiktokForList) ? $tiktokForList->count() : null,
            'shopee_forlist_count' => isset($shopeeForList) ? $shopeeForList->count() : null,
        ];

        Log::info('dashboard debug counts', $debug);

        return view('dashboard', compact('accounts', 'stats', 'syncStats', 'debug'));
    }
}
