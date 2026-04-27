<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTikTokWebhookJob;
use App\Models\TikTokWebhookLog;
use App\Services\TikTokCustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * TikTokWebhookController — Menerima webhook Customer Service dari TikTok Shop.
 *
 * Endpoint: POST /webhooks/tiktok/customer-service
 *
 * PENTING:
 * - Route ini TANPA middleware auth (TikTok yang memanggil)
 * - Harus selalu return 200 OK (TikTok akan retry jika non-200)
 * - Response time harus < 3 detik — gunakan queue untuk processing
 * - Raw payload disimpan untuk debugging
 *
 * Event types yang didukung:
 *   type 13 = NEW_CONVERSATION (percakapan baru / agent join-leave)
 *   type 14 = NEW_MESSAGE (pesan baru dari buyer/agent/system)
 */
class TikTokWebhookController extends Controller
{
    /**
     * Handle incoming webhook dari TikTok Customer Service.
     *
     * Flow:
     *   1. Baca raw body & decode JSON
     *   2. Verifikasi signature (jika dikonfigurasi)
     *   3. Simpan raw payload ke webhook_logs
     *   4. Dispatch job ke queue untuk processing async
     *   5. Return 200 OK segera
     */
    public function handle(Request $request): JsonResponse
    {
        // ⛔ WEBHOOK SEMENTARA DINONAKTIFKAN — untuk pengecekan/debugging
        // Hapus blok ini untuk mengaktifkan kembali.
        Log::info('TikTokWebhook: disabled (maintenance mode), payload ignored', [
            'ip' => $request->ip(),
        ]);
        return response()->json(['code' => 0, 'message' => 'OK']);

        $startTime = microtime(true);

        // 1. Ambil raw body dan decode
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true) ?? [];

        $eventType = (int) ($payload['type'] ?? 0);
        $eventName = TikTokWebhookLog::EVENT_NAMES[$eventType] ?? "UNKNOWN_{$eventType}";

        // 2. Verifikasi signature (opsional, configurable)
        if (config('tiktok_cs.verify_signature', true)) {
            $signature = $request->header('Authorization', '');

            if (!TikTokCustomerService::verifySignature($rawBody, $signature)) {
                Log::warning('TikTokWebhook: Invalid signature', [
                    'event_type' => $eventType,
                    'ip'         => $request->ip(),
                ]);

                // Tetap return 200 agar TikTok tidak retry terus-menerus
                // tapi JANGAN proses payload-nya
                return response()->json([
                    'code'    => 0,
                    'message' => 'OK',
                ]);
            }
        }

        // 3. Simpan raw payload ke webhook_logs
        $log = null;
        if (config('tiktok_cs.log_raw_payload', true)) {
            $log = TikTokWebhookLog::create([
                'event_type'     => $eventType,
                'event_name'     => $eventName,
                'raw_payload'    => $payload,
                'process_status' => TikTokWebhookLog::STATUS_PENDING,
            ]);
        }

        // 4. Dispatch ke queue untuk processing async
        if ($log && in_array($eventType, [13, 14])) {
            ProcessTikTokWebhookJob::dispatch($log->id);
        }

        $elapsed = round((microtime(true) - $startTime) * 1000, 1);

        Log::info('TikTokWebhook: Received', [
            'event_type' => $eventType,
            'event_name' => $eventName,
            'log_id'     => $log?->id,
            'elapsed_ms' => $elapsed,
        ]);

        // 5. Return 200 OK segera (TikTok requirement)
        return response()->json([
            'code'    => 0,
            'message' => 'OK',
        ]);
    }
}
