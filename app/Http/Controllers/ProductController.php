<?php

namespace App\Http\Controllers;

use App\Models\AccountShopShopee;
use App\Models\AccountShopTiktok;
use App\Models\ActivityLog;
use App\Models\ProdukSaya;
use App\Models\ProductDetail;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Gather account IDs from ALL platforms the user has access to
        $tiktokAccountIds = AccountShopTiktok::forUser()->pluck('id');
        $shopeeAccountIds = AccountShopShopee::when(
            !auth()->user()->isSuperAdmin(),
            fn($q) => $q->where('user_id', auth()->id())
        )->pluck('id');

        // Build the base query depending on platform filter
        $platform = $request->input('platform');

        if ($platform === 'SHOPEE') {
            $accountIds = $shopeeAccountIds;
        } elseif ($platform === 'TIKTOK' || $platform === 'TOKOPEDIA') {
            $accountIds = $tiktokAccountIds;
        } else {
            // "ALL" — we can't just merge IDs since they come from different tables
            // Instead, filter by platform + respective account IDs
            $accountIds = null; // marker for "all"
        }

        $query = ProdukSaya::with(['channel', 'detail']);

        if ($accountIds !== null) {
            $query->whereIn('account_id', $accountIds);
            if ($platform) {
                $query->where('platform', $platform);
            }
        } else {
            // ALL platforms: combine both sets
            $query->where(function ($q) use ($tiktokAccountIds, $shopeeAccountIds) {
                $q->where(function ($sub) use ($tiktokAccountIds) {
                    $sub->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])
                        ->whereIn('account_id', $tiktokAccountIds);
                })->orWhere(function ($sub) use ($shopeeAccountIds) {
                    $sub->where('platform', 'SHOPEE')
                        ->whereIn('account_id', $shopeeAccountIds);
                });
            });
        }

        // Filter: status
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

        // Stats (all platforms combined)
        $allAccountIds = $tiktokAccountIds->merge($shopeeAccountIds)->unique();
        // But we need platform-aware stats. Use the combined query approach.
        $statsQuery = ProdukSaya::where(function ($q) use ($tiktokAccountIds, $shopeeAccountIds) {
            $q->where(function ($sub) use ($tiktokAccountIds) {
                $sub->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])->whereIn('account_id', $tiktokAccountIds);
            })->orWhere(function ($sub) use ($shopeeAccountIds) {
                $sub->where('platform', 'SHOPEE')->whereIn('account_id', $shopeeAccountIds);
            });
        });

        $stats = [
            'total'       => (clone $statsQuery)->count(),
            'tiktok'      => (clone $statsQuery)->where('platform', 'TIKTOK')->count(),
            'tokopedia'   => (clone $statsQuery)->where('platform', 'TOKOPEDIA')->count(),
            'shopee'      => (clone $statsQuery)->where('platform', 'SHOPEE')->count(),
            'active'      => (clone $statsQuery)->where('product_status', 'ACTIVATE')->count(),
            'deactivated' => (clone $statsQuery)->whereIn('product_status', ['SELLER_DEACTIVATED', 'PLATFORM_DEACTIVATED'])->count(),
            'draft'       => (clone $statsQuery)->where('product_status', 'DRAFT')->count(),
        ];

        // Account list for filter (both platforms)
        $tiktokAccounts = AccountShopTiktok::forUser()->orderBy('shop_name')->get(['id', 'shop_name', 'seller_name'])
            ->map(fn($a) => (object)['id' => $a->id, 'name' => $a->shop_name ?: $a->seller_name, 'platform' => 'TIKTOK']);

        $shopeeAccounts = AccountShopShopee::when(!auth()->user()->isSuperAdmin(), fn($q) => $q->where('user_id', auth()->id()))
            ->orderBy('seller_name')->get(['id', 'seller_name'])
            ->map(fn($a) => (object)['id' => $a->id, 'name' => $a->seller_name, 'platform' => 'SHOPEE']);

        $accounts = $tiktokAccounts->merge($shopeeAccounts);

        return view('products.index', compact('products', 'stats', 'accounts'));
    }

    public function destroy(ProdukSaya $product)
    {
        // Check ownership across both platforms
        $tiktokIds = AccountShopTiktok::forUser()->pluck('id');
        $shopeeIds = AccountShopShopee::when(!auth()->user()->isSuperAdmin(), fn($q) => $q->where('user_id', auth()->id()))->pluck('id');
        $allIds = $tiktokIds->merge($shopeeIds);

        if (!$allIds->contains($product->account_id)) {
            abort(403, 'Anda tidak memiliki akses ke produk ini.');
        }

        $title = $product->title;

        ProductDetail::where('product_id', $product->product_id)
            ->where('account_id', $product->account_id)
            ->delete();

        $product->delete();

        ActivityLog::record('products.delete', "Menghapus produk: {$title}");

        return back()->with('success', "Produk \"{$title}\" berhasil dihapus.");
    }
}
