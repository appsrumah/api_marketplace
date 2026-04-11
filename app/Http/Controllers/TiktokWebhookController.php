<?php

namespace App\Http\Controllers;

use App\Models\AccountShopTiktok;
use App\Models\TiktokWebhookEvent;
use App\Services\TiktokApiService;
use App\Services\WablasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TiktokWebhookController extends Controller
{
    public function __construct(
        private TiktokApiService $tiktokService,
        private WablasService    $wablasService,
    ) {}

    /* ===================================================================
     *  RECEIVE — Menerima webhook event dari TikTok
     *  Route: POST /webhooks/tiktok
     *
     *  TikTok mengirim JSON body berisi data event.
     *  Kita simpan ke DB, proses, lalu kirim notifikasi Wablas.
     * =================================================================== */
    public function receive(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $data    = json_decode($rawBody, true);

        Log::info('🔔 TikTok Webhook received', [
            'type'    => $data['type'] ?? 'unknown',
            'shop_id' => $data['shop_id'] ?? null,
        ]);

        // ── 1. Validasi dasar ────────────────────────────────────
        $eventType = $data['type'] ?? null;
        if (!$eventType) {
            return response()->json(['code' => 0, 'message' => 'OK'], 200);
        }

        // ── 2. Cari akun berdasarkan shop_id ─────────────────────
        $shopId  = $data['shop_id'] ?? null;
        $account = null;
        if ($shopId) {
            $account = AccountShopTiktok::where('shop_id', $shopId)
                ->where('status', 'active')
                ->first();
        }

        // ── 3. Parse data spesifik berdasarkan event type ────────
        $orderId        = null;
        $orderStatus    = null;
        $conversationId = null;
        $productId      = null;

        $eventData = $data['data'] ?? [];

        switch ($eventType) {
            case 'ORDER_STATUS_CHANGE':
                $orderId     = $eventData['order_id'] ?? null;
                $orderStatus = $eventData['order_status']
                    ?? $eventData['new_status']
                    ?? null;
                break;

            case 'NEW_MESSAGE':
            case 'NEW_MESSAGE_LISTENER':
                $conversationId = $eventData['conversation_id'] ?? null;
                break;

            case 'CANCELLATION_STATUS_CHANGE':
            case 'REVERSE_STATUS_UPDATE':
                $orderId     = $eventData['order_id'] ?? null;
                $orderStatus = $eventData['order_status']
                    ?? $eventData['cancellation_status']
                    ?? $eventData['reverse_order_status']
                    ?? null;
                break;

            case 'RETURN_STATUS_CHANGE':
                $orderId     = $eventData['order_id'] ?? null;
                $orderStatus = $eventData['return_status'] ?? null;
                break;

            case 'PRODUCT_STATUS_CHANGE':
            case 'PRODUCT_INFORMATION_CHANGE':
            case 'PRODUCT_CREATION':
            case 'PRODUCT_CATEGORY_CHANGE':
            case 'PRODUCT_AUDIT_STATUS_CHANGE':
                $productId = $eventData['product_id'] ?? null;
                break;
        }

        // ── 4. Simpan event ke database ──────────────────────────
        $webhookEvent = TiktokWebhookEvent::create([
            'account_id'       => $account?->id,
            'shop_id'          => $shopId,
            'type'             => TiktokWebhookEvent::resolveTypeCode($eventType),
            'event_type'       => $eventType,
            'tiktok_timestamp' => $data['timestamp'] ?? null,
            'order_id'         => $orderId,
            'order_status'     => $orderStatus,
            'conversation_id'  => $conversationId,
            'product_id'       => $productId,
            'payload'          => $data,
            'status'           => 'received',
        ]);

        Log::info('📥 Webhook event saved', [
            'id'         => $webhookEvent->id,
            'event_type' => $eventType,
            'order_id'   => $orderId,
        ]);

        // ── 5. Proses & kirim notifikasi Wablas ─────────────────
        try {
            $webhookEvent->update(['status' => 'processing']);

            $notified = $this->wablasService->notifyFromWebhook($webhookEvent);

            $webhookEvent->update([
                'status'   => 'processed',
                'notified' => $notified,
            ]);

            Log::info('✅ Webhook event processed', [
                'id'       => $webhookEvent->id,
                'notified' => $notified,
            ]);
        } catch (\Throwable $e) {
            $webhookEvent->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('❌ Webhook processing error', [
                'id'    => $webhookEvent->id,
                'error' => $e->getMessage(),
            ]);
        }

        // ── 6. TikTok expects 200 response ──────────────────────
        return response()->json(['code' => 0, 'message' => 'Success'], 200);
    }

    /* ===================================================================
     *  STATUS — Lihat daftar webhook events (UI / debug)
     *  Route: GET /webhooks/tiktok/events
     * =================================================================== */
    public function events(Request $request): JsonResponse
    {
        $events = TiktokWebhookEvent::with('account:id,shop_name,seller_name')
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 50));

        return response()->json($events);
    }

    /* ===================================================================
     *  RETRY — Kirim ulang notifikasi yang gagal
     *  Route: POST /webhooks/tiktok/events/{id}/retry
     * =================================================================== */
    public function retry(TiktokWebhookEvent $event): JsonResponse
    {
        try {
            $notified = $this->wablasService->notifyFromWebhook($event);

            $event->update([
                'status'        => $notified ? 'processed' : 'failed',
                'notified'      => $notified,
                'error_message' => $notified ? null : 'Retry: notifikasi gagal terkirim.',
            ]);

            return response()->json([
                'message'  => $notified ? 'Notifikasi berhasil dikirim ulang.' : 'Gagal mengirim notifikasi.',
                'notified' => $notified,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /* ===================================================================
     *  REGISTER — Daftarkan webhook URL ke TikTok API (per akun)
     *  Route: POST /webhooks/tiktok/register/{account}
     * =================================================================== */
    public function register(Request $request, AccountShopTiktok $account): JsonResponse
    {
        $validated = $request->validate([
            'event_type' => 'required|string',
        ]);

        $webhookUrl = rtrim(config('app.url'), '/') . '/webhooks/tiktok';

        try {
            $result = $this->tiktokService->updateShopWebhook(
                accessToken: $account->access_token,
                shopCipher: $account->shop_cipher,
                address: $webhookUrl,
                eventType: $validated['event_type'],
            );

            Log::info('✅ Webhook registered', [
                'shop'       => $account->shop_name,
                'event_type' => $validated['event_type'],
                'url'        => $webhookUrl,
            ]);

            return response()->json([
                'message'    => "Webhook berhasil didaftarkan untuk event: {$validated['event_type']}",
                'url'        => $webhookUrl,
                'event_type' => $validated['event_type'],
                'response'   => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal mendaftarkan webhook: ' . $e->getMessage(),
            ], 500);
        }
    }

    /* ===================================================================
     *  LIST WEBHOOKS — Lihat webhook yang sudah terdaftar di TikTok
     *  Route: GET /webhooks/tiktok/registered/{account}
     * =================================================================== */
    public function registered(AccountShopTiktok $account): JsonResponse
    {
        try {
            $result = $this->tiktokService->getShopWebhooks(
                accessToken: $account->access_token,
                shopCipher: $account->shop_cipher,
            );

            return response()->json([
                'shop'     => $account->shop_name,
                'webhooks' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal mendapatkan webhooks: ' . $e->getMessage(),
            ], 500);
        }
    }
}
