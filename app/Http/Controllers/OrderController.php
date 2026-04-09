<?php

namespace App\Http\Controllers;

use App\Models\AccountShopTiktok;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\PosOrderService;
use App\Services\TiktokApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(
        private TiktokApiService $tiktokService,
        private PosOrderService  $posService
    ) {}

    /* ===================================================================
     *  INDEX — Daftar order dengan filter
     * =================================================================== */
    public function index(Request $request)
    {
        // User-scoped: hanya order dari akun milik user ini
        $accountIds = AccountShopTiktok::forUser()->pluck('id');

        $query = Order::with(['account', 'items'])->whereIn('account_id', $accountIds);

        // Filter: status
        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('order_status', $request->status);
        }

        // Filter: account
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Filter: search (order_id, buyer_name)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('buyer_name', 'like', "%{$search}%")
                    ->orWhere('tracking_number', 'like', "%{$search}%");
            });
        }

        // Filter: date range (tiktok_create_time)
        if ($request->filled('date_from')) {
            $query->where('tiktok_create_time', '>=', strtotime($request->date_from));
        }
        if ($request->filled('date_to')) {
            $query->where('tiktok_create_time', '<=', strtotime($request->date_to . ' 23:59:59'));
        }

        $orders = $query->latest('tiktok_create_time')->paginate(25)->withQueryString();

        // Stats (user-scoped)
        $statsBase = Order::whereIn('account_id', $accountIds);
        $stats = [
            'total'             => (clone $statsBase)->count(),
            'awaiting_shipment' => (clone $statsBase)->where('order_status', 'AWAITING_SHIPMENT')->count(),
            'in_transit'        => (clone $statsBase)->where('order_status', 'IN_TRANSIT')->count(),
            'completed'         => (clone $statsBase)->where('order_status', 'COMPLETED')->count(),
            'cancelled'         => (clone $statsBase)->where('order_status', 'CANCELLED')->count(),
            'unsynced_pos'      => (clone $statsBase)
                ->where('is_synced_to_pos', false)
                ->whereNotIn('order_status', ['UNPAID', 'CANCELLED'])
                ->count(),
        ];

        $accounts = AccountShopTiktok::forUser()
            ->where('status', 'active')
            ->orderBy('shop_name')
            ->get(['id', 'shop_name', 'seller_name']);

        return view('orders.index', compact('orders', 'stats', 'accounts'));
    }

    /* ===================================================================
     *  SHOW — Detail 1 order
     * =================================================================== */
    public function show(Order $order)
    {
        $order->load(['account', 'items', 'channel', 'warehouse']);

        return view('orders.show', compact('order'));
    }

    /* ===================================================================
     *  SYNC ORDERS — Tarik order dari TikTok API untuk 1 akun
     * =================================================================== */
    public function syncOrders(Request $request, AccountShopTiktok $account)
    {
        try {
            // Ensure fresh access token
            $accessToken = $this->ensureFreshToken($account);

            // Build filters
            $filters = [];

            // Default: last 7 days
            $from = $request->filled('date_from')
                ? strtotime($request->date_from)
                : now()->subDays(7)->timestamp;
            $to = $request->filled('date_to')
                ? strtotime($request->date_to . ' 23:59:59')
                : time();

            $filters['create_time_ge'] = $from;
            $filters['create_time_lt'] = $to;

            if ($request->filled('order_status')) {
                $filters['order_status'] = $request->order_status;
            }

            // Paginate through orders
            $pageToken  = null;
            $totalSaved = 0;
            $totalPages = 0;

            do {
                $totalPages++;
                $result = $this->tiktokService->searchOrders(
                    $accessToken,
                    $account->shop_cipher,
                    50,
                    $pageToken,
                    $filters
                );

                $orderList = $result['orders'] ?? [];
                $pageToken = $result['next_page_token'] ?? null;

                if (empty($orderList)) {
                    break;
                }

                // Get order IDs for detail fetch
                $orderIds = array_column($orderList, 'id');

                // Fetch full details
                $detailResult = $this->tiktokService->getOrderDetail(
                    $accessToken,
                    $account->shop_cipher,
                    $orderIds
                );

                $detailedOrders = $detailResult['orders'] ?? [];

                foreach ($detailedOrders as $apiOrder) {
                    $totalSaved += $this->saveOrder($account, $apiOrder);
                }

                // Rate limiting
                if ($pageToken) {
                    usleep(300000); // 300ms
                }
            } while ($pageToken && $totalPages < 20);

            ActivityLog::record('orders.sync', "Sinkronisasi {$totalSaved} order dari {$account->shop_name}");

            return redirect()->route('orders.index')
                ->with('success', "Berhasil sinkronisasi {$totalSaved} order dari {$account->shop_name} ({$totalPages} halaman).");
        } catch (\Throwable $e) {
            Log::error('Order sync failed', [
                'account_id' => $account->id,
                'error'      => $e->getMessage(),
            ]);

            return redirect()->route('orders.index')
                ->with('error', 'Gagal sinkronisasi order: ' . $e->getMessage());
        }
    }

    /* ===================================================================
     *  TEST — Fetch 1 order detail (for testing API)
     * =================================================================== */
    public function testFetchOne(AccountShopTiktok $account)
    {
        try {
            $accessToken = $this->ensureFreshToken($account);

            // Search for 1 order
            $result = $this->tiktokService->searchOrders(
                $accessToken,
                $account->shop_cipher,
                1,
                null,
                []
            );

            $orderList = $result['orders'] ?? [];

            if (empty($orderList)) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Tidak ada order ditemukan di akun ini.',
                    'data'    => null,
                ]);
            }

            $orderId = $orderList[0]['id'];

            // Fetch detail
            $detailResult = $this->tiktokService->getOrderDetail(
                $accessToken,
                $account->shop_cipher,
                [$orderId]
            );

            return response()->json([
                'status'       => 'success',
                'message'      => 'Berhasil mengambil 1 order dari TikTok API.',
                'search_result' => $orderList[0],
                'detail'       => $detailResult['orders'][0] ?? null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ===================================================================
     *  PUSH TO POS — Kirim 1 order ke database POS
     * =================================================================== */
    public function pushToPos(Order $order)
    {
        $accountIds = AccountShopTiktok::forUser()->pluck('id');
        abort_if(!$accountIds->contains($order->account_id), 403);

        $order->load(['items', 'account']);
        $result = $this->posService->pushOrderToPos($order);

        ActivityLog::record(
            'pos.push_order',
            ($result['success'] ? '✓ ' : '— ') . "Push order {$order->order_id} ke POS: {$result['message']}"
        );

        if (request()->wantsJson()) {
            return response()->json($result);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /* ===================================================================
     *  PUSH ALL TO POS — Batch push order belum sync ke POS (maks 50)
     * =================================================================== */
    public function pushAllToPos(Request $request)
    {
        $accountIds = AccountShopTiktok::forUser()->pluck('id');

        $orders = Order::with(['items', 'account'])
            ->whereIn('account_id', $accountIds)
            ->where('is_synced_to_pos', false)
            ->whereNotIn('order_status', ['UNPAID', 'CANCELLED'])
            ->latest('tiktok_create_time')
            ->limit(50)
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('info', 'Tidak ada order yang perlu di-push ke POS saat ini.');
        }

        $results = $this->posService->pushBatchToPos($orders);

        ActivityLog::record(
            'pos.push_batch',
            "Batch push POS — Berhasil: {$results['success']}, Dilewati: {$results['skipped']}, Gagal: {$results['failed']}"
        );

        $msg  = "✓ Berhasil: {$results['success']} order";
        if ($results['skipped'] > 0) $msg .= " | ⟳ Dilewati: {$results['skipped']}";
        if ($results['failed']  > 0) $msg .= " | ✗ Gagal: {$results['failed']}";

        $type = $results['failed'] > 0 ? 'error' : ($results['success'] > 0 ? 'success' : 'info');

        return back()->with($type, $msg);
    }

    /* ===================================================================
     *  PRIVATE: Save single order + items to database
     * =================================================================== */
    private function saveOrder(AccountShopTiktok $account, array $apiOrder): int
    {
        $orderId = $apiOrder['id'] ?? null;
        if (!$orderId) {
            return 0;
        }

        $payment   = $apiOrder['payment'] ?? [];
        $shipping  = $apiOrder['shipping'] ?? [];
        $buyer     = $apiOrder['buyer'] ?? [];
        // TikTok API v202507: recipient_address is at order root level
        $recipient = $apiOrder['recipient_address'] ?? $shipping['recipient_address'] ?? [];

        $order = Order::updateOrCreate(
            [
                'order_id'   => $orderId,
                'account_id' => $account->id,
            ],
            [
                'channel_id'              => $account->channel_id,
                'warehouse_id'            => $account->warehouse_id,
                'platform'                => 'TIKTOK',
                'order_status'            => $apiOrder['status'] ?? null,
                // v202507: buyer_uid at root; v202309: buyer.user_id
                'buyer_user_id'           => $apiOrder['buyer_uid'] ?? $buyer['user_id'] ?? $buyer['buyer_uid'] ?? null,
                // v202507: recipient_address.name; v202309: buyer.first_name
                'buyer_name'              => ($recipient['name'] ?? null)
                    ?: ($buyer['buyer_name'] ?? $buyer['first_name'] ?? null),
                'buyer_phone'             => ($recipient['phone_number'] ?? null)
                    ?: ($buyer['phone_number'] ?? null),
                'buyer_message'           => $apiOrder['buyer_message'] ?? null,
                'shipping_type'           => $shipping['shipping_type'] ?? null,
                'shipping_provider'       => $shipping['shipping_provider_name'] ?? null,
                'tracking_number'         => $shipping['tracking_number'] ?? null,
                'shipping_address'        => !empty($recipient) ? $recipient : ($shipping['recipient_address'] ?? null),
                'total_amount'            => $payment['total_amount'] ?? 0,
                'subtotal_amount'         => $payment['sub_total'] ?? 0,
                'shipping_fee'            => $payment['shipping_fee'] ?? 0,
                'seller_discount'         => $payment['seller_discount'] ?? 0,
                'platform_discount'       => $payment['platform_discount'] ?? 0,
                'currency'                => $payment['currency'] ?? 'IDR',
                'payment_method'          => $payment['payment_method_name'] ?? null,
                'payment_status'          => $apiOrder['payment_status'] ?? null,
                'is_cod'                  => ($apiOrder['is_cod'] ?? false),
                'is_buyer_request_cancel' => ($apiOrder['is_buyer_request_cancel'] ?? false),
                'is_on_hold_order'        => ($apiOrder['is_on_hold_order'] ?? false),
                'is_replacement_order'    => ($apiOrder['is_replacement_order'] ?? false),
                'paid_at'                 => isset($apiOrder['paid_time']) ? \Carbon\Carbon::createFromTimestamp($apiOrder['paid_time']) : null,
                'shipped_at'              => isset($apiOrder['rts_time']) ? \Carbon\Carbon::createFromTimestamp($apiOrder['rts_time']) : null,
                'delivered_at'            => isset($apiOrder['delivery_time']) ? \Carbon\Carbon::createFromTimestamp($apiOrder['delivery_time']) : null,
                'completed_at'            => isset($apiOrder['complete_time']) ? \Carbon\Carbon::createFromTimestamp($apiOrder['complete_time']) : null,
                'cancelled_at'            => isset($apiOrder['cancel_time']) ? \Carbon\Carbon::createFromTimestamp($apiOrder['cancel_time']) : null,
                'cancel_reason'           => $apiOrder['cancel_reason'] ?? null,
                'tiktok_create_time'      => $apiOrder['create_time'] ?? null,
                'tiktok_update_time'      => $apiOrder['update_time'] ?? null,
                'raw_data'                => $apiOrder,
            ]
        );

        // Save order items
        // CATATAN: TikTok API mengembalikan 1 line_item PER UNIT (bukan per SKU).
        // Contoh: order 1 produk qty 20 → 20 baris line_items dengan sku_id sama, masing-masing qty=1.
        // Solusi: group by sku_id dulu, jumlahkan qty-nya, baru simpan.
        $lineItems = $apiOrder['line_items'] ?? $apiOrder['item_list'] ?? [];

        $groupedItems = [];
        foreach ($lineItems as $item) {
            $skuId = $item['sku_id'] ?? null;

            // Jika tidak ada sku_id, fallback ke id (line_item_id) — simpan as-is
            if ($skuId === null) {
                $skuId = $item['id'] ?? uniqid();
                $groupedItems[$skuId] = $item;
                $groupedItems[$skuId]['quantity'] = (int) ($item['quantity'] ?? 1);
                continue;
            }

            if (!isset($groupedItems[$skuId])) {
                $groupedItems[$skuId]             = $item;
                $groupedItems[$skuId]['quantity'] = (int) ($item['quantity'] ?? 1);
            } else {
                // Akumulasi qty untuk SKU yang sama (TikTok split per unit)
                $groupedItems[$skuId]['quantity'] += (int) ($item['quantity'] ?? 1);
            }
        }

        foreach ($groupedItems as $skuId => $item) {
            OrderItem::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'sku_id'   => $skuId,
                ],
                [
                    'product_id'        => $item['product_id'] ?? null,
                    'product_name'      => $item['product_name'] ?? null,
                    'sku_name'          => $item['sku_name'] ?? null,
                    'seller_sku'        => $item['seller_sku'] ?? null,
                    'quantity'          => $item['quantity'],
                    'original_price'    => $item['original_price'] ?? 0,
                    'sale_price'        => $item['sale_price'] ?? 0,
                    'platform_discount' => $item['platform_discount'] ?? 0,
                    'seller_discount'   => $item['seller_discount'] ?? 0,
                    'item_tax'          => $item['item_tax'] ?? 0,
                    'currency'          => $item['currency'] ?? 'IDR',
                    'product_image'     => $item['product_image']['url'] ?? $item['sku_image'] ?? null,
                ]
            );
        }

        return 1;
    }

    /* ===================================================================
     *  PRIVATE: Ensure fresh access token (refresh if expired)
     * =================================================================== */
    private function ensureFreshToken(AccountShopTiktok $account): string
    {
        if ($account->access_token_expire_in && now()->gte($account->access_token_expire_in)) {
            Log::info("Refreshing expired token for account {$account->id}");

            $tokenData = $this->tiktokService->refreshAccessToken($account->refresh_token);

            $account->update([
                'access_token'            => $tokenData['access_token'],
                'access_token_expire_in'  => now()->addSeconds($tokenData['access_token_expire_in'] ?? 0),
                'refresh_token'           => $tokenData['refresh_token'] ?? $account->refresh_token,
                'refresh_token_expire_in' => now()->addSeconds($tokenData['refresh_token_expire_in'] ?? 0),
                'token_obtained_at'       => now(),
            ]);

            $account->refresh();
        }

        return $account->access_token;
    }
}
