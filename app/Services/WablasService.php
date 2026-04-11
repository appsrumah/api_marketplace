<?php

namespace App\Services;

use App\Models\TiktokWebhookEvent;
use App\Models\WablasBot;
use Illuminate\Support\Facades\Log;

class WablasService
{
    /* ===================================================================
     *  KIRIM PESAN WHATSAPP VIA WABLAS
     *  Bisa kirim ke nomor spesifik atau ke daftar nomor di WablasBot
     * =================================================================== */
    public function send(string $message, ?WablasBot $bot = null, ?string $overridePhone = null): bool
    {
        $bot = $bot ?? $this->getDefaultBot();
        if (!$bot) {
            Log::warning('WablasService: tidak ada Wablas bot aktif, notifikasi dilewati.');
            return false;
        }

        $phones = $overridePhone
            ? [$this->formatPhone($overridePhone)]
            : array_map(fn($p) => $this->formatPhone($p), $bot->getPhoneList());

        if (empty($phones)) {
            Log::warning('WablasService: tidak ada nomor tujuan.');
            return false;
        }

        $allSent = true;
        foreach ($phones as $phone) {
            $sent = $this->doSend($bot, $phone, $message);
            if (!$sent) $allSent = false;
            usleep(200_000); // 200ms jeda antar nomor
        }

        return $allSent;
    }

    /* ===================================================================
     *  KIRIM KE BANYAK NOMOR SEKALIGUS (array of phones)
     * =================================================================== */
    public function sendToPhones(string $message, array $phones, ?WablasBot $bot = null): bool
    {
        $bot = $bot ?? $this->getDefaultBot();
        if (!$bot) {
            Log::warning('WablasService: tidak ada Wablas bot aktif.');
            return false;
        }

        if (empty($phones)) return false;

        $anySent = false;
        foreach ($phones as $phone) {
            $formatted = $this->formatPhone($phone);
            $sent = $this->doSend($bot, $formatted, $message);
            if ($sent) $anySent = true;
            usleep(200_000);
        }

        return $anySent;
    }

    /* ===================================================================
     *  KIRIM NOTIFIKASI BERDASARKAN WEBHOOK EVENT
     *
     *  PRIORITAS PENGIRIMAN:
     *  1. Kirim ke no_telp di account_shop_tiktok (per akun toko)
     *  2. Fallback ke nomor di wablas_bots (jika no_telp kosong)
     * =================================================================== */
    public function notifyFromWebhook(TiktokWebhookEvent $event): bool
    {
        $bot = $this->getDefaultBot();
        if (!$bot) {
            Log::warning('WablasService: tidak ada Wablas bot aktif, notifikasi dilewati.');
            return false;
        }

        // Cek apakah bot ini mau menerima event type ini
        if (!$bot->shouldNotify($event->event_type)) {
            Log::info("WablasService: bot [{$bot->name}] skip event [{$event->event_type}]");
            return false;
        }

        $message = $this->buildMessageFromEvent($event);
        if (!$message) return false;

        // ── PRIORITAS 1: Kirim ke no_telp per akun toko ─────────
        $account = $event->account;
        if ($account && !empty($account->getNotifPhones())) {
            $phones = $account->getNotifPhones();

            Log::info("📱 Wablas: kirim ke no_telp akun [{$account->shop_name}]", [
                'phones' => $phones,
                'event'  => $event->event_type,
            ]);

            $sent = $this->sendToPhones($message, $phones, $bot);

            if ($sent) {
                $event->update(['notified' => true]);
            }

            return $sent;
        }

        // ── FALLBACK: Kirim ke nomor default di wablas_bots ─────
        Log::info("📱 Wablas: no_telp akun kosong, fallback ke bot [{$bot->name}]");

        $sent = $this->send($message, $bot);

        if ($sent) {
            $event->update(['notified' => true]);
        }

        return $sent;
    }

    /* ===================================================================
     *  BUILD PESAN BERDASARKAN EVENT TYPE
     * =================================================================== */
    public function buildMessageFromEvent(TiktokWebhookEvent $event): ?string
    {
        return match ($event->event_type) {
            'ORDER_STATUS_CHANGE'        => $this->buildOrderStatusMessage($event),
            'NEW_MESSAGE',
            'NEW_MESSAGE_LISTENER'       => $this->buildNewMessageNotif($event),
            'CANCELLATION_STATUS_CHANGE' => $this->buildCancellationMessage($event),
            'RETURN_STATUS_CHANGE',
            'REVERSE_STATUS_UPDATE'      => $this->buildReturnMessage($event),
            default                      => null,
        };
    }

    /* ── Order Status Change ─────────────────────────────────────── */
    private function buildOrderStatusMessage(TiktokWebhookEvent $event): string
    {
        $data     = $event->payload['data'] ?? $event->payload ?? [];
        $orderId  = $event->order_id ?? ($data['order_id'] ?? '-');
        $status   = $event->order_status ?? ($data['order_status'] ?? '-');
        $label    = $event->getOrderStatusLabel();
        $shopName = $event->account?->shop_name ?? $event->shop_id ?? '-';

        $lines   = [];
        $lines[] = '══════════════════════';
        $lines[] = '🎵 *TIKTOK SHOP — STATUS ORDER*';
        $lines[] = '══════════════════════';
        $lines[] = "🏪 Toko: *{$shopName}*";
        $lines[] = "📋 Order: {$orderId}";
        $lines[] = "📌 Status: {$label}";
        $lines[] = '';

        // Info tambahan sesuai status
        if (in_array($status, ['AWAITING_SHIPMENT', 'AWAITING_COLLECTION'])) {
            $lines[] = '⚡ _Segera proses pengiriman!_';
        } elseif ($status === 'COMPLETED') {
            $lines[] = '🎉 _Pesanan telah selesai._';
        } elseif (in_array($status, ['CANCELLED', 'IN_CANCEL'])) {
            $lines[] = '❌ _Pesanan dibatalkan._';
        }

        $lines[] = '';
        $lines[] = '⏰ ' . now()->format('d M Y H:i:s') . ' WIB';

        return implode("\n", $lines);
    }

    /* ── New Message / Chat ──────────────────────────────────────── */
    private function buildNewMessageNotif(TiktokWebhookEvent $event): string
    {
        $data           = $event->payload['data'] ?? $event->payload ?? [];
        $conversationId = $event->conversation_id ?? ($data['conversation_id'] ?? '-');
        $shopName       = $event->account?->shop_name ?? $event->shop_id ?? '-';

        $lines   = [];
        $lines[] = '══════════════════════';
        $lines[] = '💬 *TIKTOK SHOP — PESAN BARU*';
        $lines[] = '══════════════════════';
        $lines[] = "🏪 Toko: *{$shopName}*";
        $lines[] = "💬 Chat ID: {$conversationId}";
        $lines[] = '';
        $lines[] = '📱 _Segera cek pesan dari pembeli di Seller Center TikTok!_';
        $lines[] = '';
        $lines[] = '⏰ ' . now()->format('d M Y H:i:s') . ' WIB';

        return implode("\n", $lines);
    }

    /* ── Cancellation ────────────────────────────────────────────── */
    private function buildCancellationMessage(TiktokWebhookEvent $event): string
    {
        $data     = $event->payload['data'] ?? $event->payload ?? [];
        $orderId  = $event->order_id ?? ($data['order_id'] ?? '-');
        $shopName = $event->account?->shop_name ?? $event->shop_id ?? '-';

        $lines   = [];
        $lines[] = '══════════════════════';
        $lines[] = '⚠️ *TIKTOK SHOP — PEMBATALAN*';
        $lines[] = '══════════════════════';
        $lines[] = "🏪 Toko: *{$shopName}*";
        $lines[] = "📋 Order: {$orderId}";
        $lines[] = '';
        $lines[] = '❗ _Ada permintaan pembatalan, segera cek!_';
        $lines[] = '';
        $lines[] = '⏰ ' . now()->format('d M Y H:i:s') . ' WIB';

        return implode("\n", $lines);
    }

    /* ── Return / Refund ─────────────────────────────────────────── */
    private function buildReturnMessage(TiktokWebhookEvent $event): string
    {
        $data     = $event->payload['data'] ?? $event->payload ?? [];
        $orderId  = $event->order_id ?? ($data['order_id'] ?? '-');
        $shopName = $event->account?->shop_name ?? $event->shop_id ?? '-';

        $lines   = [];
        $lines[] = '══════════════════════';
        $lines[] = '🔄 *TIKTOK SHOP — RETUR/REFUND*';
        $lines[] = '══════════════════════';
        $lines[] = "🏪 Toko: *{$shopName}*";
        $lines[] = "📋 Order: {$orderId}";
        $lines[] = '';
        $lines[] = '❗ _Ada permintaan retur/refund, segera proses!_';
        $lines[] = '';
        $lines[] = '⏰ ' . now()->format('d M Y H:i:s') . ' WIB';

        return implode("\n", $lines);
    }

    /* ===================================================================
     *  HELPER — Get default active bot
     * =================================================================== */
    private function getDefaultBot(): ?WablasBot
    {
        return WablasBot::where('is_active', true)->first();
    }

    /* ===================================================================
     *  HELPER — Actual HTTP call to Wablas
     * =================================================================== */
    private function doSend(WablasBot $bot, string $phone, string $message): bool
    {
        $tokenString = $bot->secret_key
            ? "{$bot->token}.{$bot->secret_key}"
            : $bot->token;

        $url = rtrim($bot->server_url, '/') . '/api/send-message'
            . '?token=' . urlencode($tokenString)
            . '&phone=' . urlencode($phone)
            . '&message=' . urlencode($message);

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $result   = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $decoded = json_decode($result, true);

            if ($httpCode === 200 && ($decoded['status'] ?? false)) {
                Log::info("✅ Wablas: pesan terkirim ke {$phone}");
                return true;
            }

            Log::warning("⚠️ Wablas: gagal kirim ke {$phone}", [
                'http_code' => $httpCode,
                'response'  => $decoded ?? $result,
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('WablasService error: ' . $e->getMessage());
            return false;
        }
    }

    /* ===================================================================
     *  HELPER — Format nomor HP ke 62xxx
     * =================================================================== */
    private function formatPhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = str_replace(['+', "'", '-', ' '], '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }
}
