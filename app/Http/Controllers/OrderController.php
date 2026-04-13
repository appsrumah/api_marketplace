<?php

namespace App\Http\Controllers;

use App\Models\AccountShopTiktok;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\PosOrderService;
use App\Services\TiktokApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

    /**
     * Map external API status/display_status values to internal Order::STATUS_* constants.
     */
    private function mapOrderStatus(?string $status): ?string
    {
        if (empty($status)) {
            return null;
        }

        $s = strtoupper(trim($status));

        return match ($s) {
            'UNPAID', 'PENDING_PAYMENT' => Order::STATUS_UNPAID,
            'ON_HOLD', 'HOLD' => Order::STATUS_ON_HOLD,
            'AWAITING_SHIPMENT', 'AWAITING_DELIVERY', 'READY_TO_SHIP' => Order::STATUS_AWAITING_SHIPMENT,
            'PARTIALLY_SHIPPING' => Order::STATUS_PARTIALLY_SHIPPING,
            'AWAITING_COLLECTION' => Order::STATUS_AWAITING_COLLECTION,
            'IN_TRANSIT', 'SHIPPING' => Order::STATUS_IN_TRANSIT,
            'DELIVERED' => Order::STATUS_DELIVERED,
            'COMPLETED' => Order::STATUS_COMPLETED,
            'CANCELLED', 'CANCELED' => Order::STATUS_CANCELLED,
            default => $s,
        };
    }

    /**
     * Save single API order payload to local DB (create or update).
     *
     * Returns local Order id (or 0 on failure).
     */
    private function saveOrder(AccountShopTiktok $account, array $apiOrder): int
    {
        $orderId = $apiOrder['id'] ?? $apiOrder['order_id'] ?? null;
        if (!$orderId) return 0;

        $payment   = $apiOrder['payment'] ?? [];
        $shipping  = $apiOrder['shipping'] ?? [];
        $buyer     = $apiOrder['buyer'] ?? [];
        $recipient = $apiOrder['recipient_address'] ?? ($shipping['recipient_address'] ?? []);
        $lineItems = $apiOrder['line_items'] ?? $apiOrder['items'] ?? [];

        // values normalized / fallbacks
        $rootStatus = $apiOrder['status'] ?? null;
        if (empty($rootStatus) && !empty($lineItems) && isset($lineItems[0]['display_status'])) {
            $rootStatus = $lineItems[0]['display_status'];
        }

        $orderStatus = $this->mapOrderStatus($rootStatus);

        // data that should always be updated on sync
        $updateData = [
            'order_status'       => $orderStatus,
            'tracking_number'    => $apiOrder['tracking_number'] ?? $shipping['tracking_number'] ?? ($lineItems[0]['tracking_number'] ?? null),
            'shipping_provider'  => $apiOrder['shipping_provider'] ?? $shipping['shipping_provider_name'] ?? ($lineItems[0]['shipping_provider_name'] ?? null),
            'payment_status'     => $apiOrder['payment_status'] ?? null,
            'tiktok_update_time' => $apiOrder['update_time'] ?? null,
            'raw_data'           => $apiOrder,
        ];

        // data set only on create
        $createOnly = [
            'channel_id'           => $account->channel_id ?? null,
            'warehouse_id'         => $account->warehouse_id ?? null,
            'platform'             => 'TIKTOK',
            'buyer_user_id'        => $apiOrder['buyer_uid'] ?? $buyer['user_id'] ?? null,
            'buyer_name'           => $recipient['name'] ?? $buyer['buyer_name'] ?? null,
            'buyer_phone'          => $recipient['phone_number'] ?? $buyer['phone_number'] ?? null,
            'buyer_email'          => $apiOrder['buyer_email'] ?? null,
            'shipping_address'     => !empty($recipient) ? $recipient : ($shipping['recipient_address'] ?? null),
            'total_amount'         => isset($payment['total_amount']) ? (float) $payment['total_amount'] : (float) ($payment['original_total_product_price'] ?? 0),
            'subtotal_amount'      => isset($payment['sub_total']) ? (float) $payment['sub_total'] : (float) ($payment['original_total_product_price'] ?? 0),
            'shipping_fee'         => isset($payment['shipping_fee']) ? (float) $payment['shipping_fee'] : (float) ($payment['original_shipping_fee'] ?? 0),
            'platform_discount'    => isset($payment['platform_discount']) ? (float) $payment['platform_discount'] : (float) ($payment['payment_platform_discount'] ?? 0),
            'payment_method'       => $payment['payment_method_name'] ?? $apiOrder['payment_method_name'] ?? null,
            'is_cod'               => (bool) ($apiOrder['is_cod'] ?? false),
            'tiktok_create_time'   => $apiOrder['create_time'] ?? null,
        ];

        // timestamps converted
        $maybeTimestamps = [
            'paid_at'      => $this->toCarbonFromApiTimestamp($apiOrder['paid_time'] ?? null),
            'shipped_at'   => $this->toCarbonFromApiTimestamp($apiOrder['rts_time'] ?? $apiOrder['rts_time'] ?? null),
            'delivered_at' => $this->toCarbonFromApiTimestamp($apiOrder['delivery_time'] ?? null),
            'completed_at' => $this->toCarbonFromApiTimestamp($apiOrder['complete_time'] ?? null),
            'cancelled_at' => $this->toCarbonFromApiTimestamp($apiOrder['cancel_time'] ?? null),
        ];

        // find existing
        $existing = Order::where('order_id', $orderId)->where('account_id', $account->id)->first();

        if ($existing) {
            $existing->update(array_merge($updateData, $maybeTimestamps));
            $order = $existing;
        } else {
            $order = Order::create(array_merge(
                ['order_id' => $orderId, 'account_id' => $account->id],
                $createOnly,
                $updateData,
                $maybeTimestamps
            ));
        }

        // items — TikTok kirim 1 line_item PER UNIT, jadi group by sku dulu
        // lalu simpan dengan key tiktok_line_item_id agar tidak saling timpa
        $groupedItems = [];
        foreach ($lineItems as $li) {
            $lineItemId = $li['id'] ?? null;
            $sku        = $li['seller_sku'] ?? $li['sku_id'] ?? null;
            if (!$sku) continue;

            // TikTok sering kirim qty per line_item = 1 (1 item per baris), tapi bisa juga > 1
            $liQty = (int)($li['quantity'] ?? 1);

            if (isset($groupedItems[$sku])) {
                // Akumulasi qty dan diskon karena TikTok split per unit
                $groupedItems[$sku]['quantity']          += $liQty;
                $groupedItems[$sku]['platform_discount'] += (float)($li['platform_discount'] ?? 0);
                $groupedItems[$sku]['seller_discount']   += (float)($li['seller_discount'] ?? 0);
            } else {
                $groupedItems[$sku] = [
                    'tiktok_line_item_id' => $lineItemId,
                    'product_id'          => $li['product_id'] ?? null,
                    'product_name'        => $li['product_name'] ?? null,
                    'sku_id'              => $li['sku_id'] ?? null,
                    'sku_name'            => $li['sku_name'] ?? null,
                    'seller_sku'          => $sku,
                    'quantity'            => $liQty,
                    'original_price'      => isset($li['original_price']) ? (float)$li['original_price'] : (float)($li['sale_price'] ?? 0),
                    'sale_price'          => isset($li['sale_price']) ? (float)$li['sale_price'] : null,
                    'platform_discount'   => (float)($li['platform_discount'] ?? 0),
                    'seller_discount'     => (float)($li['seller_discount'] ?? 0),
                    'currency'            => $li['currency'] ?? $payment['currency'] ?? 'IDR',
                    'product_image'       => $li['sku_image'] ?? null,
                    'item_status'         => $li['display_status'] ?? null,
                ];
            }
        }

        foreach ($groupedItems as $sku => $itemData) {
            $subtotal = $itemData['original_price'] * $itemData['quantity'];

            OrderItem::updateOrCreate(
                [
                    'order_id'   => $order->id,
                    'seller_sku' => $sku,           // key: 1 record per SKU per order
                ],
                array_merge($itemData, [
                    'subtotal' => $subtotal,
                ])
            );
        }

        return $order->id ?? 0;
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

            // Default: last 1 day (24 jam ke belakang)
            $from = $request->filled('date_from')
                ? strtotime($request->date_from)
                : now()->subDay()->timestamp;
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
     *  CRON — Sync order SEMUA akun (24 jam ke belakang) + push ke POS
     *  GET /orders/cron-sync-all?secret=xxx
     *  Diamankan dengan secret key — dipanggil dari cPanel Cron Jobs via curl
     * =================================================================== */
    public function cronSyncAll(Request $request): JsonResponse
    {
        if ($request->query('secret') !== config('app.order_sync_secret')) {
            return response()->json(['status' => 'Unauthorized'], 401);
        }

        @set_time_limit(300);

        // Window: 24 jam ke belakang sampai sekarang
        $from = now()->subDay()->timestamp;
        $to   = now()->timestamp;

        // Ambil SEMUA akun aktif (scopeForUser() otomatis return all saat tidak ada Auth)
        $accounts = AccountShopTiktok::where('status', 'active')
            ->whereNotNull('shop_cipher')
            ->whereNotNull('access_token')
            ->get();

        if ($accounts->isEmpty()) {
            return response()->json([
                'status' => 'skipped',
                'reason' => 'Tidak ada akun aktif dengan shop_cipher.',
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        $summary = [];

        foreach ($accounts as $account) {
            $accountResult = [
                'account'    => $account->shop_name ?? $account->seller_name,
                'synced'     => 0,
                'pos_pushed' => 0,
                'pos_skip'   => 0,
                'pos_fail'   => 0,
                'error'      => null,
            ];

            try {
                // 1. Pastikan access token masih segar
                $accessToken = $this->ensureFreshToken($account);

                // 2. Filter: 24 jam ke belakang
                $filters = [
                    'create_time_ge' => $from,
                    'create_time_lt' => $to,
                ];

                // 3. Paginate: searchOrders → getOrderDetail → saveOrder
                $pageToken  = null;
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

                    // Ambil detail lengkap untuk batch order ini
                    $orderIds     = array_column($orderList, 'id');
                    $detailResult = $this->tiktokService->getOrderDetail(
                        $accessToken,
                        $account->shop_cipher,
                        $orderIds
                    );

                    foreach ($detailResult['orders'] ?? [] as $apiOrder) {
                        $accountResult['synced'] += $this->saveOrder($account, $apiOrder);
                    }

                    if ($pageToken) {
                        usleep(300000); // rate limiting 300ms antar halaman
                    }
                } while ($pageToken && $totalPages < 10);

                // 4. Push order yg belum masuk POS (semua status kecuali UNPAID / CANCELLED)
                $unsynced = Order::with(['items', 'account'])
                    ->where('account_id', $account->id)
                    ->where('is_synced_to_pos', false)
                    ->whereNotIn('order_status', ['UNPAID', 'CANCELLED'])
                    ->latest('tiktok_create_time')
                    ->limit(100)
                    ->get();

                if ($unsynced->isNotEmpty()) {
                    $posResult = $this->posService->pushBatchToPos($unsynced);
                    $accountResult['pos_pushed'] = $posResult['success'];
                    $accountResult['pos_skip']   = $posResult['skipped'];
                    $accountResult['pos_fail']   = $posResult['failed'];
                }

                ActivityLog::record(
                    'orders.cron_sync',
                    "Cron sync {$accountResult['synced']} order dari {$account->shop_name} | POS: +{$accountResult['pos_pushed']}"
                );
            } catch (\Throwable $e) {
                $accountResult['error'] = $e->getMessage();
                Log::error('cronSyncAll: gagal untuk akun ' . $account->id, [
                    'account' => $account->shop_name,
                    'error'   => $e->getMessage(),
                ]);
            }

            $summary[] = $accountResult;

            usleep(500000); // rate limiting 500ms antar akun
        }

        return response()->json([
            'status'   => 'OK',
            'window'   => [
                'from' => date('Y-m-d H:i:s', $from),
                'to'   => date('Y-m-d H:i:s', $to),
            ],
            'accounts' => $summary,
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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

    private function toCarbonFromApiTimestamp($ts): ?Carbon
    {
        if (empty($ts)) return null;
        $ts = (int) $ts;
        // normalize ms -> s
        if ($ts > 1000000000000) {
            $ts = (int) floor($ts / 1000);
        }
        return Carbon::createFromTimestampUTC($ts)->setTimezone(config('app.timezone') ?: date_default_timezone_get());
    }
}
