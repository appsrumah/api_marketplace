<?php

namespace App\Http\Controllers;

use App\Models\AccountShopShopee;
use App\Models\AccountShopTiktok;
use App\Models\Order;
use App\Models\ShopeeOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * UnifiedOrderController — Halaman gabungan pesanan dari semua platform
 *
 * Menampilkan TikTok + Shopee orders dalam satu tabel dengan filter platform.
 * Detail order tetap redirect ke controller masing-masing (OrderController / ShopeeOrderController).
 */
class UnifiedOrderController extends Controller
{
    /* ===================================================================
     *  INDEX — Daftar gabungan order dari semua platform
     * =================================================================== */
    public function index(Request $request)
    {
        $platform = $request->input('platform', 'ALL');
        $perPage  = 25;
        $page     = (int) $request->input('page', 1);

        // Account IDs per platform
        $tiktokAccountIds = AccountShopTiktok::forUser()->pluck('id');
        $shopeeAccountIds = AccountShopShopee::forUser()->pluck('id');

        // ── Build queries based on platform filter ───────────────────
        $tiktokOrders = collect();
        $shopeeOrders = collect();
        $totalCount   = 0;

        if (in_array($platform, ['ALL', 'TIKTOK'])) {
            $tQuery = Order::with(['account', 'items'])->whereIn('account_id', $tiktokAccountIds);
            $this->applyFilters($tQuery, $request, 'tiktok');
            if ($platform === 'TIKTOK') {
                // Single platform → use normal pagination
                $orders = $tQuery->latest('tiktok_create_time')->paginate($perPage)->withQueryString();
                return $this->renderView($request, $orders, $platform, $tiktokAccountIds, $shopeeAccountIds);
            }
            $tiktokOrders = $tQuery->latest('tiktok_create_time')->get();
        }

        if (in_array($platform, ['ALL', 'SHOPEE'])) {
            $sQuery = ShopeeOrder::with(['account', 'items'])->whereIn('account_id', $shopeeAccountIds);
            $this->applyFilters($sQuery, $request, 'shopee');
            if ($platform === 'SHOPEE') {
                $orders = $sQuery->latest('create_time')->paginate($perPage)->withQueryString();
                return $this->renderView($request, $orders, $platform, $tiktokAccountIds, $shopeeAccountIds);
            }
            $shopeeOrders = $sQuery->latest('create_time')->get();
        }

        // ── ALL: Merge + sort + manual paginate ──────────────────────
        // Transform to common format for sorting
        $merged = collect();

        foreach ($tiktokOrders as $o) {
            $merged->push((object) [
                'platform'     => 'TIKTOK',
                'original'     => $o,
                'sort_time'    => $o->tiktok_create_time ?? 0,
            ]);
        }
        foreach ($shopeeOrders as $o) {
            $merged->push((object) [
                'platform'     => 'SHOPEE',
                'original'     => $o,
                'sort_time'    => $o->create_time ?? 0,
            ]);
        }

        $sorted     = $merged->sortByDesc('sort_time')->values();
        $totalCount = $sorted->count();
        $sliced     = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        // Unwrap back to original models
        $items = $sliced->map(fn($wrap) => $wrap->original);

        $orders = new LengthAwarePaginator(
            $items,
            $totalCount,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return $this->renderView($request, $orders, $platform, $tiktokAccountIds, $shopeeAccountIds);
    }

    /**
     * Apply common filters to a query (search, status, account, date).
     */
    private function applyFilters($query, Request $request, string $type): void
    {
        // Filter: status
        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('order_status', $request->status);
        }

        // Filter: account
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Filter: search
        if ($request->filled('search')) {
            $search = $request->search;
            if ($type === 'tiktok') {
                $query->where(function ($q) use ($search) {
                    $q->where('order_id', 'like', "%{$search}%")
                        ->orWhere('buyer_name', 'like', "%{$search}%")
                        ->orWhere('tracking_number', 'like', "%{$search}%");
                });
            } else {
                $query->where(function ($q) use ($search) {
                    $q->where('order_sn', 'like', "%{$search}%")
                        ->orWhere('buyer_name', 'like', "%{$search}%")
                        ->orWhere('tracking_number', 'like', "%{$search}%");
                });
            }
        }

        // Filter: date range
        if ($request->filled('date_from')) {
            $ts = strtotime($request->date_from);
            $col = $type === 'tiktok' ? 'tiktok_create_time' : 'create_time';
            $query->where($col, '>=', $ts);
        }
        if ($request->filled('date_to')) {
            $ts = strtotime($request->date_to . ' 23:59:59');
            $col = $type === 'tiktok' ? 'tiktok_create_time' : 'create_time';
            $query->where($col, '<=', $ts);
        }
    }

    /**
     * Build stats and render the unified view.
     */
    private function renderView(Request $request, $orders, string $platform, Collection $tiktokAccountIds, Collection $shopeeAccountIds)
    {
        // ── Stats ────────────────────────────────────────────────────
        $tStats = Order::whereIn('account_id', $tiktokAccountIds);
        $sStats = ShopeeOrder::whereIn('account_id', $shopeeAccountIds);

        $stats = [
            'total'        => (clone $tStats)->count() + (clone $sStats)->count(),
            'tiktok_total' => (clone $tStats)->count(),
            'shopee_total' => (clone $sStats)->count(),
            'awaiting'     => (clone $tStats)->where('order_status', 'AWAITING_SHIPMENT')->count()
                + (clone $sStats)->where('order_status', 'READY_TO_SHIP')->count(),
            'in_transit'   => (clone $tStats)->where('order_status', 'IN_TRANSIT')->count()
                + (clone $sStats)->where('order_status', 'SHIPPED')->count(),
            'completed'    => (clone $tStats)->where('order_status', 'COMPLETED')->count()
                + (clone $sStats)->where('order_status', 'COMPLETED')->count(),
            'cancelled'    => (clone $tStats)->where('order_status', 'CANCELLED')->count()
                + (clone $sStats)->where('order_status', 'CANCELLED')->count(),
            'unsynced_pos' => (clone $tStats)->where('is_synced_to_pos', false)->whereNotIn('order_status', ['UNPAID', 'CANCELLED'])->count()
                + (clone $sStats)->where('is_synced_to_pos', false)->whereNotIn('order_status', ['UNPAID', 'CANCELLED', 'IN_CANCEL'])->count(),
        ];

        // ── Accounts for dropdown ────────────────────────────────────
        $tiktokAccounts = AccountShopTiktok::forUser()->where('status', 'active')
            ->orderBy('shop_name')->get(['id', 'shop_name', 'seller_name'])
            ->map(fn($a) => (object)['id' => $a->id, 'name' => $a->shop_name ?: $a->seller_name, 'platform' => 'TikTok']);

        $shopeeAccounts = AccountShopShopee::forUser()
            ->where('status', 'active')->orderBy('seller_name')->get(['id', 'seller_name'])
            ->map(fn($a) => (object)['id' => $a->id, 'name' => $a->seller_name, 'platform' => 'Shopee']);

        $accounts = $tiktokAccounts->merge($shopeeAccounts);

        // ── Sync accounts (for sync button dropdown) ─────────────────
        $syncTiktok = AccountShopTiktok::forUser()->where('status', 'active')
            ->get(['id', 'shop_name', 'seller_name'])
            ->map(fn($a) => (object)['id' => $a->id, 'name' => $a->shop_name ?: $a->seller_name, 'platform' => 'tiktok', 'route' => route('orders.sync', $a->id)]);

        $syncShopee = AccountShopShopee::forUser()
            ->where('status', 'active')->get(['id', 'seller_name'])
            ->map(fn($a) => (object)['id' => $a->id, 'name' => $a->seller_name, 'platform' => 'shopee', 'route' => route('shopee.orders.sync', $a->id)]);

        $syncAccounts = $syncTiktok->merge($syncShopee);

        return view('unified.orders.index', compact('orders', 'stats', 'accounts', 'syncAccounts', 'platform'));
    }

    /* ===================================================================
     *  CRON — Sync SEMUA order (TikTok + Shopee) dari 1 URL
     *  GET /orders/cron-sync-all?secret=xxx
     *
     *  Menggabungkan OrderController::cronSyncAll dan
     *  ShopeeOrderController::cronSyncAll dalam satu endpoint.
     *  Cukup 1 cron job untuk semua platform pesanan.
     * =================================================================== */
    public function cronSyncAllOrders(Request $request): JsonResponse
    {
        if ($request->query('secret') !== config('app.order_sync_secret')) {
            return response()->json(['status' => 'Unauthorized'], 401);
        }

        // Delegate ke masing-masing controller (service injection via app())
        // Setiap platform dibungkus try-catch agar kegagalan 1 platform
        // tidak merusak platform lain (dan tidak return 500).
        $tiktokData = ['status' => 'skipped', 'reason' => 'not started'];
        $shopeeData = ['status' => 'skipped', 'reason' => 'not started'];

        try {
            $tiktokResponse = app(OrderController::class)->cronSyncAll($request);
            $tiktokData = json_decode($tiktokResponse->getContent(), true) ?? [];
        } catch (\Throwable $e) {
            $tiktokData = ['status' => 'ERROR', 'error' => $e->getMessage()];
            \Illuminate\Support\Facades\Log::error('UnifiedCron TikTok failed', ['error' => $e->getMessage()]);
        }

        try {
            $shopeeResponse = app(ShopeeOrderController::class)->cronSyncAll($request);
            $shopeeData = json_decode($shopeeResponse->getContent(), true) ?? [];
        } catch (\Throwable $e) {
            $shopeeData = ['status' => 'ERROR', 'error' => $e->getMessage()];
            \Illuminate\Support\Facades\Log::error('UnifiedCron Shopee failed', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'status' => 'OK',
            'tiktok' => $tiktokData,
            'shopee' => $shopeeData,
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
