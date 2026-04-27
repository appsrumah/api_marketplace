<?php

namespace App\Http\Controllers;

use App\Models\AccountShopShopee;
use App\Models\ActivityLog;
use App\Models\ShopeeOrder;
use App\Models\ShopeeOrderItem;
use App\Services\ShopeeApiService;
use App\Services\ShopeePosOrderService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShopeeOrderController extends Controller
{
    public function __construct(
        private ShopeeApiService      $shopeeService,
        private ShopeePosOrderService $posService,
    ) {}

    /* ===================================================================
     *  INDEX — Daftar order Shopee dengan filter
     * =================================================================== */
    public function index(Request $request)
    {
        // Redirect ke halaman unified orders dengan filter Shopee
        return redirect()->route(
            'unified.orders.index',
            array_merge(['platform' => 'SHOPEE'], $request->only(['search', 'status', 'account_id', 'date_from', 'date_to', 'page']))
        );

        // ─── Kode lama dipertahankan sebagai referensi ─────────────────
        /** @var User $user */
        $user = Auth::user();

        // Shopee accounts via AccountShopShopee
        $accountIds = AccountShopShopee::when(!$user->isSuperAdmin(), fn($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $query = ShopeeOrder::with(['account', 'items'])->whereIn('account_id', $accountIds);

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
            $query->where(function ($q) use ($search) {
                $q->where('order_sn', 'like', "%{$search}%")
                    ->orWhere('buyer_name', 'like', "%{$search}%")
                    ->orWhere('tracking_number', 'like', "%{$search}%");
            });
        }

        // Filter: date range
        if ($request->filled('date_from')) {
            $query->where('create_time', '>=', strtotime($request->date_from));
        }
        if ($request->filled('date_to')) {
            $query->where('create_time', '<=', strtotime($request->date_to . ' 23:59:59'));
        }

        $orders = $query->latest('create_time')->paginate(25)->withQueryString();

        // Stats
        $statsBase = ShopeeOrder::whereIn('account_id', $accountIds);
        $stats = [
            'total'          => (clone $statsBase)->count(),
            'ready_to_ship'  => (clone $statsBase)->where('order_status', 'READY_TO_SHIP')->count(),
            'shipped'        => (clone $statsBase)->where('order_status', 'SHIPPED')->count(),
            'completed'      => (clone $statsBase)->where('order_status', 'COMPLETED')->count(),
            'cancelled'      => (clone $statsBase)->where('order_status', 'CANCELLED')->count(),
            'unsynced_pos'   => (clone $statsBase)
                ->where('is_synced_to_pos', false)
                ->whereNotIn('order_status', ['UNPAID', 'CANCELLED', 'IN_CANCEL'])
                ->count(),
        ];

        $accounts = AccountShopShopee::when(!$user->isSuperAdmin(), fn($q) => $q->where('user_id', $user->id))
            ->where('status', 'active')
            ->orderBy('seller_name')
            ->get(['id', 'seller_name']);

        return view('shopee.orders.index', compact('orders', 'stats', 'accounts'));
    }

    /* ===================================================================
     *  SHOW — Detail 1 order Shopee
     * =================================================================== */
    public function show(ShopeeOrder $order)
    {
        $order->load(['account', 'items', 'channel', 'warehouse']);

        return view('shopee.orders.show', compact('order'));
    }

    /* ===================================================================
     *  SYNC ORDERS — Tarik order dari Shopee API untuk 1 akun
     * =================================================================== */
    public function syncOrders(Request $request, AccountShopShopee $account)
    {
        try {
            $accessToken = $this->ensureFreshToken($account);
            $shopId      = (int) $account->shop_id;

            // Default: 3 hari ke belakang (Shopee max window = 15 hari)
            $from = $request->filled('date_from')
                ? strtotime($request->date_from)
                : now()->subDays(3)->timestamp;
            $to = $request->filled('date_to')
                ? strtotime($request->date_to . ' 23:59:59')
                : time();

            // Shopee max time range = 15 hari
            if (($to - $from) > 15 * 86400) {
                $from = $to - (15 * 86400);
            }

            $cursor     = '';
            $totalSaved = 0;
            $totalPages = 0;

            do {
                $totalPages++;
                $result = $this->shopeeService->getOrderList(
                    $accessToken,
                    $shopId,
                    $from,
                    $to,
                    50,
                    $cursor,
                );

                $response  = $result['response'] ?? $result;
                $orderList = $response['order_list'] ?? [];
                $more      = $response['more'] ?? false;
                $cursor    = $response['next_cursor'] ?? '';

                if (empty($orderList)) break;

                // Fetch detail for each order
                $orderSns = array_column($orderList, 'order_sn');

                foreach ($orderSns as $orderSn) {
                    $saved = $this->fetchAndSaveOrderDetail($account, $accessToken, $shopId, $orderSn);
                    $totalSaved += $saved;
                }

                if ($more && $cursor) {
                    usleep(300000); // rate limiting
                }
            } while ($more && $cursor && $totalPages < 20);

            // Push to POS
            $posPushed = 0;
            $unsynced  = ShopeeOrder::with(['items', 'account'])
                ->where('account_id', $account->id)
                ->where('is_synced_to_pos', false)
                ->whereNotIn('order_status', ['UNPAID', 'CANCELLED', 'IN_CANCEL'])
                ->latest('create_time')
                ->limit(50)
                ->get();

            if ($unsynced->isNotEmpty()) {
                $posResult = $this->posService->pushBatchToPos($unsynced);
                $posPushed = $posResult['success'] ?? 0;
            }

            ActivityLog::record('shopee.orders.sync', "Sinkronisasi {$totalSaved} order Shopee dari {$account->seller_name} | POS: +{$posPushed}");

            return redirect()->route('shopee.orders.index')
                ->with('success', "Berhasil sinkronisasi {$totalSaved} order Shopee dari {$account->seller_name} ({$totalPages} halaman). Push POS: {$posPushed} order.");
        } catch (\Throwable $e) {
            Log::error('Shopee order sync failed', [
                'account_id' => $account->id,
                'error'      => $e->getMessage(),
            ]);

            return redirect()->route('shopee.orders.index')
                ->with('error', 'Gagal sinkronisasi order Shopee: ' . $e->getMessage());
        }
    }

    /* ===================================================================
     *  PUSH TO POS — Kirim 1 order Shopee ke database POS
     * =================================================================== */
    public function pushToPos(ShopeeOrder $order)
    {
        /** @var User $user */
        $user = Auth::user();
        $accountIds = AccountShopShopee::when(!$user->isSuperAdmin(), fn($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        abort_if(!$accountIds->contains($order->account_id), 403);

        $order->load(['items', 'account']);
        $result = $this->posService->pushOrderToPos($order);

        ActivityLog::record(
            'pos.shopee_push_order',
            ($result['success'] ? '✓ ' : '— ') . "Push Shopee order {$order->order_sn} ke POS: {$result['message']}"
        );

        if (request()->wantsJson()) {
            return response()->json($result);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /* ===================================================================
     *  PUSH ALL TO POS — Batch push order Shopee belum sync (maks 50)
     * =================================================================== */
    public function pushAllToPos(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $accountIds = AccountShopShopee::when(!$user->isSuperAdmin(), fn($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $orders = ShopeeOrder::with(['items', 'account'])
            ->whereIn('account_id', $accountIds)
            ->where('is_synced_to_pos', false)
            ->whereNotIn('order_status', ['UNPAID', 'CANCELLED', 'IN_CANCEL'])
            ->latest('create_time')
            ->limit(50)
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('info', 'Tidak ada order Shopee yang perlu di-push ke POS saat ini.');
        }

        $results = $this->posService->pushBatchToPos($orders);

        ActivityLog::record(
            'pos.shopee_push_batch',
            "Batch push Shopee POS — Berhasil: {$results['success']}, Dilewati: {$results['skipped']}, Gagal: {$results['failed']}"
        );

        $msg  = "✓ Berhasil: {$results['success']} order";
        if ($results['skipped'] > 0) $msg .= " | ⟳ Dilewati: {$results['skipped']}";
        if ($results['failed']  > 0) $msg .= " | ✗ Gagal: {$results['failed']}";

        $type = $results['failed'] > 0 ? 'error' : ($results['success'] > 0 ? 'success' : 'info');

        return back()->with($type, $msg);
    }

    /* ===================================================================
     *  CRON — Sync order Shopee SEMUA akun (3 hari ke belakang) + push POS
     *  GET /shopee/orders/cron-sync-all?secret=xxx
     * =================================================================== */
    public function cronSyncAll(Request $request): JsonResponse
    {
        if ($request->query('secret') !== config('app.order_sync_secret')) {
            return response()->json(['status' => 'Unauthorized'], 401);
        }

        @set_time_limit(300);

        $from = now()->subDays(3)->timestamp;
        $to   = now()->timestamp;

        $accounts = AccountShopShopee::where('status', 'active')
            ->whereNotNull('shop_id')
            ->whereNotNull('access_token')
            ->get();

        if ($accounts->isEmpty()) {
            return response()->json([
                'status' => 'skipped',
                'reason' => 'Tidak ada akun Shopee aktif.',
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        $summary = [];

        foreach ($accounts as $account) {
            $accountResult = [
                'account'    => $account->seller_name,
                'synced'     => 0,
                'pos_pushed' => 0,
                'pos_skip'   => 0,
                'pos_fail'   => 0,
                'error'      => null,
            ];

            try {
                $accessToken = $this->ensureFreshToken($account);
                $shopId      = (int) $account->shop_id;

                $cursor     = '';
                $totalPages = 0;

                do {
                    $totalPages++;
                    $result = $this->shopeeService->getOrderList(
                        $accessToken,
                        $shopId,
                        $from,
                        $to,
                        50,
                        $cursor,
                    );

                    $response  = $result['response'] ?? $result;
                    $orderList = $response['order_list'] ?? [];
                    $more      = $response['more'] ?? false;
                    $cursor    = $response['next_cursor'] ?? '';

                    if (empty($orderList)) break;

                    $orderSns = array_column($orderList, 'order_sn');
                    foreach ($orderSns as $orderSn) {
                        $accountResult['synced'] += $this->fetchAndSaveOrderDetail($account, $accessToken, $shopId, $orderSn);
                    }

                    if ($more && $cursor) {
                        usleep(300000);
                    }
                } while ($more && $cursor && $totalPages < 10);

                // Push to POS
                $unsynced = ShopeeOrder::with(['items', 'account'])
                    ->where('account_id', $account->id)
                    ->where('is_synced_to_pos', false)
                    ->whereNotIn('order_status', ['UNPAID', 'CANCELLED', 'IN_CANCEL'])
                    ->latest('create_time')
                    ->limit(100)
                    ->get();

                if ($unsynced->isNotEmpty()) {
                    $posResult = $this->posService->pushBatchToPos($unsynced);
                    $accountResult['pos_pushed'] = $posResult['success'];
                    $accountResult['pos_skip']   = $posResult['skipped'];
                    $accountResult['pos_fail']   = $posResult['failed'];
                }

                ActivityLog::record(
                    'shopee.orders.cron_sync',
                    "Cron sync {$accountResult['synced']} order Shopee dari {$account->seller_name} | POS: +{$accountResult['pos_pushed']}"
                );
            } catch (\Throwable $e) {
                $accountResult['error'] = $e->getMessage();
                Log::error('Shopee cronSyncAll: gagal untuk akun ' . $account->id, [
                    'account' => $account->seller_name,
                    'error'   => $e->getMessage(),
                ]);
            }

            $summary[] = $accountResult;
            usleep(500000);
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
     *  PRIVATE: Fetch order detail from Shopee API & save to DB
     * =================================================================== */
    private function fetchAndSaveOrderDetail(AccountShopShopee $account, string $accessToken, int $shopId, string $orderSn): int
    {
        try {
            // Shopee v2: GET /api/v2/order/get_order_detail
            $path      = '/api/v2/order/get_order_detail';
            $timestamp = time();
            $sign      = $this->shopeeService->buildShopSign($path, $timestamp, $accessToken, $shopId);

            $response = \Illuminate\Support\Facades\Http::timeout(15)->connectTimeout(10)->get(
                rtrim(config('services.shopee.api_base'), '/') . $path,
                [
                    'partner_id'             => (int) config('services.shopee.partner_id'),
                    'timestamp'              => $timestamp,
                    'sign'                   => $sign,
                    'access_token'           => $accessToken,
                    'shop_id'                => $shopId,
                    'order_sn_list'          => $orderSn,
                    'response_optional_fields' => 'buyer_user_id,buyer_username,estimated_shipping_fee,recipient_address,actual_shipping_fee,goods_to_declare,note,note_update_time,item_list,pay_time,dropshipper,dropshipper_phone,split_up,buyer_cancel_reason,cancel_by,cancel_reason,actual_shipping_fee_confirmed,buyer_cpf_id,fulfillment_flag,pickup_done_time,package_list,shipping_carrier,payment_method,total_amount,buyer_username,invoice_data,checkout_shipping_carrier,reverse_shipping_fee,order_chargeable_weight_gram,edt,prescription_images,prescription_check_status',
                ]
            );

            $data      = $response->json();
            $orderData = $data['response']['order_list'][0] ?? null;

            if (!$orderData) {
                Log::warning("Shopee: order detail kosong untuk {$orderSn}");
                return 0;
            }

            return $this->saveOrder($account, $orderData);
        } catch (\Throwable $e) {
            Log::error("Shopee fetchOrderDetail gagal: {$orderSn}", ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /* ===================================================================
     *  PRIVATE: Save/update satu order Shopee ke DB
     * =================================================================== */
    private function saveOrder(AccountShopShopee $account, array $apiOrder): int
    {
        $orderSn = $apiOrder['order_sn'] ?? null;
        if (!$orderSn) return 0;

        $recipientAddr = $apiOrder['recipient_address'] ?? [];
        $itemList      = $apiOrder['item_list'] ?? [];

        $updateData = [
            'order_status'        => $apiOrder['order_status'] ?? null,
            'buyer_user_id'       => isset($apiOrder['buyer_user_id']) ? (string) $apiOrder['buyer_user_id'] : null,
            'buyer_username'      => $apiOrder['buyer_username'] ?? null,
            'buyer_name'          => $recipientAddr['name'] ?? null,
            'buyer_phone'         => $recipientAddr['phone'] ?? null,
            'buyer_message'       => $apiOrder['note'] ?? $apiOrder['message_to_seller'] ?? null,
            'shipping_carrier'    => $apiOrder['shipping_carrier'] ?? null,
            'tracking_number'     => $apiOrder['package_list'][0]['package_number'] ?? ($apiOrder['tracking_no'] ?? null),
            'shipping_address'    => !empty($recipientAddr) ? $recipientAddr : null,
            'total_amount'        => (float) ($apiOrder['total_amount'] ?? 0),
            'subtotal_amount'     => (float) ($apiOrder['escrow_amount'] ?? $apiOrder['total_amount'] ?? 0),
            'shipping_fee'        => (float) ($apiOrder['estimated_shipping_fee'] ?? $apiOrder['actual_shipping_fee'] ?? 0),
            'seller_discount'     => (float) ($apiOrder['seller_discount'] ?? 0),
            'voucher_from_seller' => (float) ($apiOrder['voucher_from_seller'] ?? 0),
            'voucher_from_shopee' => (float) ($apiOrder['voucher_from_shopee'] ?? 0),
            'coin_offset'         => (float) ($apiOrder['coin_offset'] ?? 0),
            'currency'            => $apiOrder['currency'] ?? 'IDR',
            'payment_method'      => $apiOrder['payment_method'] ?? null,
            'is_cod'              => (bool) ($apiOrder['cod'] ?? false),
            'update_time'         => $apiOrder['update_time'] ?? null,
            'pay_time'            => $apiOrder['pay_time'] ?? null,
            'ship_by_date'        => $apiOrder['ship_by_date'] ?? null,
            'days_to_ship'        => $apiOrder['days_to_ship'] ?? null,
            'raw_data'            => $apiOrder,
        ];

        // Ambil tracking number & shipping carrier dari package_list
        if (!empty($apiOrder['package_list'])) {
            foreach ($apiOrder['package_list'] as $pkg) {
                if (!empty($pkg['package_number'])) {
                    $updateData['tracking_number'] = $pkg['package_number'];
                    if (!empty($pkg['shipping_carrier'])) {
                        $updateData['shipping_carrier'] = $pkg['shipping_carrier'];
                    }
                    break;
                }
            }
        }

        $createOnly = [
            'channel_id'   => $account->channel_id ?? null,
            'warehouse_id' => $account->warehouse_id ?? null,
            'create_time'  => $apiOrder['create_time'] ?? null,
        ];

        $existing = ShopeeOrder::where('order_sn', $orderSn)
            ->where('account_id', $account->id)
            ->first();

        if ($existing) {
            $existing->update($updateData);
            $order = $existing;
        } else {
            $order = ShopeeOrder::create(array_merge(
                ['order_sn' => $orderSn, 'account_id' => $account->id],
                $createOnly,
                $updateData,
            ));
        }

        // Save items
        foreach ($itemList as $apiItem) {
            $itemId  = $apiItem['item_id'] ?? null;
            $modelId = $apiItem['model_id'] ?? 0;

            ShopeeOrderItem::updateOrCreate(
                [
                    'shopee_order_id' => $order->id,
                    'item_id'         => $itemId,
                    'model_id'        => $modelId,
                ],
                [
                    'item_name'              => $apiItem['item_name'] ?? null,
                    'item_sku'               => $apiItem['item_sku'] ?? null,
                    'model_name'             => $apiItem['model_name'] ?? null,
                    'model_sku'              => $apiItem['model_sku'] ?? null,
                    'model_original_price'   => (float) ($apiItem['model_original_price'] ?? 0),
                    'model_discounted_price' => (float) ($apiItem['model_discounted_price'] ?? 0),
                    'quantity_purchased'     => (int) ($apiItem['model_quantity_purchased'] ?? $apiItem['quantity'] ?? 1),
                    'image_url'              => $apiItem['image_info']['image_url'] ?? ($apiItem['item_img'] ?? null),
                    'weight'                 => (float) ($apiItem['weight'] ?? 0),
                    'is_wholesale'           => (bool) ($apiItem['wholesale'] ?? false),
                ]
            );
        }

        return 1;
    }

    /* ===================================================================
     *  PRIVATE: Ensure fresh Shopee token
     * =================================================================== */
    private function ensureFreshToken(AccountShopShopee $account): string
    {
        if ($account->access_token_expire_in && now()->gte($account->access_token_expire_in)) {
            Log::info("Refreshing expired Shopee token for account {$account->id}");

            $shopId    = (int) $account->shop_id;
            $tokenData = $this->shopeeService->refreshAccessToken($account->refresh_token, $shopId);

            $expireIn        = (int) ($tokenData['expire_in'] ?? 14400);
            $refreshExpireIn = (int) ($tokenData['refresh_token_expire_in'] ?? 2592000);

            $account->update([
                'access_token'            => $tokenData['access_token'],
                'access_token_expire_in'  => now()->addSeconds($expireIn),
                'refresh_token'           => $tokenData['refresh_token'] ?? $account->refresh_token,
                'refresh_token_expire_in' => now()->addSeconds($refreshExpireIn),
                'token_obtained_at'       => now(),
                'status'                  => 'active',
            ]);

            $account->refresh();
        }

        return $account->access_token;
    }
}
