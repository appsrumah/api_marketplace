<?php

namespace App\Http\Controllers;

use App\Models\AccountShopTiktok;
use App\Models\ProdukSaya;

class DashboardController extends Controller
{
    public function index()
    {
        $accounts = AccountShopTiktok::withCount('produk')->latest()->get();

        $stats = [
            'total_accounts'   => $accounts->count(),
            'active_accounts'  => $accounts->where('status', 'active')->count(),
            'total_products'   => ProdukSaya::count(),
            'total_tiktok'     => ProdukSaya::where('platform', 'TIKTOK')->count(),
            'total_tokopedia'  => ProdukSaya::where('platform', 'TOKOPEDIA')->count(),
        ];

        return view('dashboard', compact('accounts', 'stats'));
    }
}
