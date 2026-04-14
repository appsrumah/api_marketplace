<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TikTokWebhookLog extends Model
{
    protected $table = 'tiktok_webhook_logs';

    protected $fillable = [
        'event_type',
        'event_name',
        'account_id',
        'raw_payload',
        'process_status',
        'process_error',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type'   => 'integer',
            'account_id'   => 'integer',
            'raw_payload'  => 'array',
            'processed_at' => 'datetime',
        ];
    }

    // ─── Status Constants ──────────────────────────────────────────────────

    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_FAILED     = 'failed';

    // ─── Event Name Map ────────────────────────────────────────────────────

    const EVENT_NAMES = [
        13 => 'NEW_CONVERSATION',
        14 => 'NEW_MESSAGE',
    ];

    // ─── Scopes ────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('process_status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('process_status', self::STATUS_FAILED);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    /** Tandai log sebagai completed */
    public function markCompleted(): void
    {
        $this->update([
            'process_status' => self::STATUS_COMPLETED,
            'processed_at'   => now(),
        ]);
    }

    /** Tandai log sebagai failed */
    public function markFailed(string $error): void
    {
        $this->update([
            'process_status' => self::STATUS_FAILED,
            'process_error'  => $error,
            'processed_at'   => now(),
        ]);
    }

    /**
     * Prune (hapus) log lama berdasar retensi config.
     *
     * @return int Jumlah baris dihapus
     */
    public static function prune(): int
    {
        $days = config('tiktok_cs.log_retention_days', 30);

        return static::where('created_at', '<', now()->subDays($days))->delete();
    }
}
