<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WablasService — Kirim notifikasi WhatsApp via Wablas API
 *
 * Referensi logika dari shopee/get_order_list.php (lama).
 *
 * Config (di services.php / .env):
 *   WABLAS_BASE_URL   — e.g. https://pati.wablas.com
 *   WABLAS_TOKEN      — token Wablas
 *   WABLAS_SECRET_KEY — secret key Wablas (opsional, tergantung server)
 */
class WablasService
{
    private string $baseUrl;
    private string $token;
    private string $secretKey;

    public function __construct()
    {
        $this->baseUrl   = rtrim(config('services.wablas.base_url', 'https://pati.wablas.com'), '/');
        $this->token     = config('services.wablas.token', '');
        $this->secretKey = config('services.wablas.secret_key', '');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Kirim notif order baru ke nomor yg dikonfigurasi di akun
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Kirim pesan order baru ke nomor telp_notif milik akun.
     *
     * @param  Order   $order    Order yang baru saja masuk POS
     * @param  int     $idSo     ID SO yang baru dibuat di POS
     * @param  float   $subtotal Total subtotal order
     * @return bool
     */
    public function sendNewOrderNotification(Order $order, int $idSo, float $subtotal): bool
    {
        $phone = $order->account?->telp_notif ?? '';

        if (empty(trim($phone))) {
            // Tidak ada nomor konfigurasi — skip tanpa error
            return false;
        }

        if (empty($this->token)) {
            Log::warning('WablasService: WABLAS_TOKEN belum dikonfigurasi di .env');
            return false;
        }

        $message = $this->buildOrderMessage($order, $idSo, $subtotal);

        // Support multi nomor dipisah koma (mirip logika Shopee lama)
        $phones = array_filter(array_map('trim', explode(',', $phone)));

        $allOk = true;
        foreach ($phones as $p) {
            $ok = $this->send($p, $message);
            if (!$ok) $allOk = false;
        }

        return $allOk;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Kirim pesan teks bebas
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param  string  $phone    Nomor HP (format bebas; akan di-normalize)
     * @param  string  $message  Isi pesan
     */
    public function send(string $phone, string $message): bool
    {
        $phone = $this->normalizePhone($phone);

        // Build token: jika ada secret_key, gabung seperti format Wablas lama
        $tokenParam = $this->secretKey
            ? "{$this->token}.{$this->secretKey}"
            : $this->token;

        try {
            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/api/send-message", [
                    'token'   => $tokenParam,
                    'phone'   => $phone,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                Log::info("WablasService: Notif terkirim ke {$phone}");
                return true;
            }

            Log::warning('WablasService: Gagal kirim WA', [
                'phone'  => $phone,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('WablasService: Exception saat kirim WA', [
                'phone'   => $phone,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Build pesan notifikasi order baru
    // ─────────────────────────────────────────────────────────────────────────

    private function buildOrderMessage(Order $order, int $idSo, float $subtotal): string
    {
        $shopName  = $order->account?->shop_name ?? 'TikTok Shop';
        $buyerName = $order->buyer_name ?? '-';

        $lines = [];
        $lines[] = "==== ADA ORDER BARU TIKTOK ====";
        $lines[] = "*{$shopName}*";
        $lines[] = "Order ID  : {$order->order_id}";
        $lines[] = "Pembeli   : {$buyerName}";
        $lines[] = "SO ID POS : {$idSo}";
        $lines[] = "";

        foreach ($order->items as $item) {
            $nama  = $item->product_name ?? $item->seller_sku ?? 'Produk';
            $harga = number_format((float) ($item->original_price ?? 0), 0, ',', '.');
            $qty   = (int) ($item->quantity ?? 1);
            $lines[] = "• {$nama}";
            $lines[] = "  Rp {$harga} × {$qty} pcs";
        }

        $lines[] = "";
        $lines[] = "Total     : Rp " . number_format($subtotal, 0, ',', '.');

        if ($order->tracking_number) {
            $lines[] = "Resi      : {$order->tracking_number}";
        }

        return implode("\n", $lines);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Helper: normalisasi nomor HP ke format 62xxx
    // ─────────────────────────────────────────────────────────────────────────

    public function normalizePhone(string $phone): string
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
