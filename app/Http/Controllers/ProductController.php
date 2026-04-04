<?php

namespace App\Http\Controllers;

use App\Models\ProdukSaya;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = ProdukSaya::with('account');

        // Filter: platform
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        // Filter: status (ALL = tampilkan semua, kosong = semua)
        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('product_status', $request->status);
        }

        // Filter: search by title or sku
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('seller_sku', 'like', "%{$search}%")
                    ->orWhere('product_id', 'like', "%{$search}%");
            });
        }

        // Filter: account
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        $products = $query->latest()->paginate(25)->withQueryString();

        // Stats
        $stats = [
            'total'       => ProdukSaya::count(),
            'tiktok'      => ProdukSaya::where('platform', 'TIKTOK')->count(),
            'tokopedia'   => ProdukSaya::where('platform', 'TOKOPEDIA')->count(),
            'active'      => ProdukSaya::where('product_status', 'ACTIVATE')->count(),
            'deactivated' => ProdukSaya::whereIn('product_status', ['SELLER_DEACTIVATED', 'PLATFORM_DEACTIVATED'])->count(),
            'draft'       => ProdukSaya::where('product_status', 'DRAFT')->count(),
        ];

        return view('products.index', compact('products', 'stats'));
    }
}
