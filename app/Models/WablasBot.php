<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WablasBot extends Model
{
    protected $table = 'wablas_bots';

    protected $fillable = [
        'name',
        'server_url',
        'token',
        'secret_key',
        'phone_notif',
        'notify_order_status',
        'notify_new_message',
        'notify_cancellation',
        'notify_return',
        'notify_product_change',
        'is_active',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'notify_order_status'  => 'boolean',
            'notify_new_message'   => 'boolean',
            'notify_cancellation'  => 'boolean',
            'notify_return'        => 'boolean',
            'notify_product_change' => 'boolean',
            'is_active'            => 'boolean',
            'token'                => 'encrypted',
        ];
    }

    /* ── Relationships ───────────────────────────────────────────── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /* ── Helpers ─────────────────────────────────────────────────── */

    /**
     * Apakah event tertentu perlu di-notifikasi?
     */
    public function shouldNotify(string $eventType): bool
    {
        return match ($eventType) {
            'ORDER_STATUS_CHANGE'        => $this->notify_order_status,
            'NEW_MESSAGE',
            'NEW_MESSAGE_LISTENER'       => $this->notify_new_message,
            'CANCELLATION_STATUS_CHANGE' => $this->notify_cancellation,
            'RETURN_STATUS_CHANGE',
            'REVERSE_STATUS_UPDATE'      => $this->notify_return,
            'PRODUCT_STATUS_CHANGE',
            'PRODUCT_INFORMATION_CHANGE' => $this->notify_product_change,
            default                      => false,
        };
    }

    /**
     * Ambil semua nomor HP penerima (split koma)
     */
    public function getPhoneList(): array
    {
        return array_filter(
            array_map('trim', explode(',', $this->phone_notif))
        );
    }
}
