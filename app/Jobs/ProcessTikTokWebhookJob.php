<?php

namespace App\Jobs;

use App\Models\TikTokWebhookLog;
use App\Services\TikTokCustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessTikTokWebhookJob — Proses webhook CS di background queue.
 *
 * Webhook controller langsung return 200 OK,
 * lalu dispatch job ini untuk proses payload secara async.
 * Ini memastikan response time < 3 detik sesuai requirement TikTok.
 */
class ProcessTikTokWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Max retry */
    public int $tries;

    /** Timeout per job (detik) */
    public int $timeout = 60;

    /** Backoff (detik) antar retry */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly int $webhookLogId,
    ) {
        $this->tries = (int) config('tiktok_cs.queue_max_tries', 3);
        $this->onQueue(config('tiktok_cs.queue_name', 'default'));
    }

    public function handle(TikTokCustomerService $service): void
    {
        $log = TikTokWebhookLog::find($this->webhookLogId);

        if (!$log) {
            Log::warning('ProcessTikTokWebhookJob: Log not found', [
                'log_id' => $this->webhookLogId,
            ]);
            return;
        }

        // Skip jika sudah completed (idempotent)
        if ($log->process_status === TikTokWebhookLog::STATUS_COMPLETED) {
            return;
        }

        $payload = $log->raw_payload ?? [];

        $service->handleWebhook($payload, $log);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $log = TikTokWebhookLog::find($this->webhookLogId);
        $log?->markFailed('Job failed: ' . $exception->getMessage());

        Log::error('ProcessTikTokWebhookJob failed permanently', [
            'log_id' => $this->webhookLogId,
            'error'  => $exception->getMessage(),
        ]);
    }
}
