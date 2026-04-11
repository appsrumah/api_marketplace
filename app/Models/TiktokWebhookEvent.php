<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TiktokWebhookEvent extends Model
{
    protected $table = 'tiktok_webhook_events';

    /* ── Event Type Constants ────────────────────────────────────── */
    public const TYPE_ORDER_STATUS_CHANGE              = 1;
    public const TYPE_NEW_MESSAGE                      = 2;
    public const TYPE_NEW_MESSAGE_LISTENER             = 3;
    public const TYPE_PACKAGE_UPDATE                   = 4;
    public const TYPE_PRODUCT_STATUS_CHANGE            = 5;
    public const TYPE_CANCELLATION_STATUS_CHANGE       = 6;
    public const TYPE_RETURN_STATUS_CHANGE             = 7;
    public const TYPE_REVERSE_STATUS_UPDATE            = 8;
    public const TYPE_SELLER_DEAUTHORIZATION           = 9;
    public const TYPE_UPCOMING_AUTHORIZATION_EXPIRATION = 10;
    public const TYPE_OTHER                            = 99;

    /* ── Mapping event_type string → int ─────────────────────────── */
    public const EVENT_TYPE_MAP = [
        'ORDER_STATUS_CHANGE'               => self::TYPE_ORDER_STATUS_CHANGE,
        'NEW_MESSAGE'                       => self::TYPE_NEW_MESSAGE,
        'NEW_MESSAGE_LISTENER'              => self::TYPE_NEW_MESSAGE_LISTENER,
        'PACKAGE_UPDATE'                    => self::TYPE_PACKAGE_UPDATE,
        'PRODUCT_STATUS_CHANGE'             => self::TYPE_PRODUCT_STATUS_CHANGE,
        'CANCELLATION_STATUS_CHANGE'        => self::TYPE_CANCELLATION_STATUS_CHANGE,
        'RETURN_STATUS_CHANGE'              => self::TYPE_RETURN_STATUS_CHANGE,
        'REVERSE_STATUS_UPDATE'             => self::TYPE_REVERSE_STATUS_UPDATE,
        'SELLER_DEAUTHORIZATION'            => self::TYPE_SELLER_DEAUTHORIZATION,
        'UPCOMING_AUTHORIZATION_EXPIRATION' => self::TYPE_UPCOMING_AUTHORIZATION_EXPIRATION,
    ];

    /* ── Order Status Labels (untuk notifikasi) ──────────────────── */
    public const ORDER_STATUS_LABELS = [
        'UNPAID'            => '⏳ Belum Dibayar',
        'ON_HOLD'           => '⏸️ Ditahan',
        'AWAITING_SHIPMENT' => '📦 Siap Kirim',
        'AWAITING_COLLECTION' => '🚚 Menunggu Pickup',
        'PARTIALLY_SHIPPING' => '🚛 Sebagian Dikirim',
        'IN_TRANSIT'        => '🚚 Dalam Pengiriman',
        'DELIVERED'         => '✅ Sampai Tujuan',
        'COMPLETED'         => '🎉 Selesai',
        'CANCELLED'         => '❌ Dibatalkan',
        'IN_CANCEL'         => '⚠️ Proses Batal',
    ];

    protected $fillable = [
        'account_id',
        'shop_id',
        'type',
        'event_type',
        'tiktok_timestamp',
        'order_id',
        'order_status',
        'conversation_id',
        'product_id',
        'payload',
        'status',
        'notified',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'payload'          => 'array',
            'tiktok_timestamp' => 'integer',
            'notified'         => 'boolean',
        ];
    }

    /* ── Relationships ───────────────────────────────────────────── */

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountShopTiktok::class, 'account_id');
    }

    /* ── Helpers ─────────────────────────────────────────────────── */

    /**
     * Resolve event_type string → integer type code
     */
    public static function resolveTypeCode(string $eventType): int
    {
        return self::EVENT_TYPE_MAP[$eventType] ?? self::TYPE_OTHER;
    }

    /**
     * Label order status untuk notifikasi
     */
    public function getOrderStatusLabel(): string
    {
        return self::ORDER_STATUS_LABELS[$this->order_status] ?? $this->order_status ?? '-';
    }
}
