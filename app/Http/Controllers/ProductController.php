<?php

namespace App\Http\Controllers;

use App\Models\AccountShopTiktok;
use App\Models\ActivityLog;
use App\Models\ProdukSaya;
use App\Models\ProductDetail;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // User-scoped: hanya produk dari akun milik user ini
        $accountIds = AccountShopTiktok::forUser()->pluck('id');

        $query = ProdukSaya::with(['account', 'detail'])->whereIn('account_id', $accountIds);

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

        // Stats (user-scoped)
        $baseStats = ProdukSaya::whereIn('account_id', $accountIds);
        $stats = [
            'total'       => (clone $baseStats)->count(),
            'tiktok'      => (clone $baseStats)->where('platform', 'TIKTOK')->count(),
            'tokopedia'   => (clone $baseStats)->where('platform', 'TOKOPEDIA')->count(),
            'active'      => (clone $baseStats)->where('product_status', 'ACTIVATE')->count(),
            'deactivated' => (clone $baseStats)->whereIn('product_status', ['SELLER_DEACTIVATED', 'PLATFORM_DEACTIVATED'])->count(),
            'draft'       => (clone $baseStats)->where('product_status', 'DRAFT')->count(),
        ];

        // Daftar akun untuk filter dropdown
        $accounts = AccountShopTiktok::forUser()->orderBy('shop_name')->get(['id', 'shop_name', 'seller_name']);

        return view('products.index', compact('products', 'stats', 'accounts'));
    }

    /* ===================================================================
     *  DESTROY — Hapus produk dari database lokal
     * =================================================================== */
    public function destroy(ProdukSaya $product)
    {
        // Pastikan produk milik user
        $accountIds = AccountShopTiktok::forUser()->pluck('id');
        if (!$accountIds->contains($product->account_id)) {
            abort(403, 'Anda tidak memiliki akses ke produk ini.');
        }

        $title = $product->title;

        // Hapus detail juga jika ada
        ProductDetail::where('product_id', $product->product_id)
            ->where('account_id', $product->account_id)
            ->delete();

        $product->delete();

        ActivityLog::record('products.delete', "Menghapus produk: {$title}");

        return back()->with('success', "Produk \"{$title}\" berhasil dihapus.");
    }
}
