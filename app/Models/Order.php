<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\OrderItem;
use Carbon\Carbon;

class Order extends Model
{
    protected $fillable = [
        'account_id',
        'channel_id',
        'warehouse_id',
        'order_id',
        'platform',
        'order_status',
        'buyer_user_id',
        'buyer_name',
        'buyer_phone',
        'buyer_message',
        'shipping_type',
        'shipping_provider',
        'tracking_number',
        'shipping_address',
        'total_amount',
        'subtotal_amount',
        'shipping_fee',
        'seller_discount',
        'platform_discount',
        'currency',
        'payment_method',
        'payment_status',
        'is_cod',
        'is_buyer_request_cancel',
        'is_on_hold_order',
        'is_replacement_order',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
        'tiktok_create_time',
        'tiktok_update_time',
        'is_synced_to_pos',
        'synced_to_pos_at',
        'pos_order_id',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'account_id' => 'integer',
            'channel_id' => 'integer',
            'warehouse_id' => 'integer',
            'total_amount' => 'decimal:2',
            'subtotal_amount' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'seller_discount' => 'decimal:2',
            'platform_discount' => 'decimal:2',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'synced_to_pos_at' => 'datetime',
            'tiktok_create_time' => 'integer',
            'tiktok_update_time' => 'integer',
            'is_synced_to_pos' => 'boolean',
            'is_cod' => 'boolean',
            'shipping_address' => 'array',
            'raw_data' => 'array',
        ];
    }

    // ─── Status Constants ──────────────────────────────────────────────────

    const STATUS_UNPAID             = 'UNPAID';
    const STATUS_ON_HOLD            = 'ON_HOLD';
    const STATUS_AWAITING_SHIPMENT  = 'AWAITING_SHIPMENT';
    const STATUS_PARTIALLY_SHIPPING = 'PARTIALLY_SHIPPING';
    const STATUS_AWAITING_COLLECTION = 'AWAITING_COLLECTION';
    const STATUS_IN_TRANSIT         = 'IN_TRANSIT';
    const STATUS_DELIVERED          = 'DELIVERED';
    const STATUS_COMPLETED          = 'COMPLETED';
    const STATUS_CANCELLED          = 'CANCELLED';

    const STATUS_LABELS = [
        'UNPAID'              => 'Belum diBayar',
        'ON_HOLD'             => 'Pesanan Baru',
        'AWAITING_SHIPMENT'   => 'Siap Kirim',
        'PARTIALLY_SHIPPING'  => 'Sebagian Dikirim',
        'AWAITING_COLLECTION' => 'Menunggu Pickup',
        'IN_TRANSIT'          => 'Dalam Pengiriman',
        'DELIVERED'           => 'Terkirim',
        'COMPLETED'           => 'Selesai',
        'CANCELLED'           => 'Dibatalkan',
    ];

    const STATUS_COLORS = [
        'UNPAID'              => 'bg-yellow-100 text-yellow-700',
        'ON_HOLD'             => 'bg-orange-100 text-orange-700',
        'AWAITING_SHIPMENT'   => 'bg-blue-100 text-blue-700',
        'PARTIALLY_SHIPPING'  => 'bg-indigo-100 text-indigo-700',
        'AWAITING_COLLECTION' => 'bg-cyan-100 text-cyan-700',
        'IN_TRANSIT'          => 'bg-purple-100 text-purple-700',
        'DELIVERED'           => 'bg-emerald-100 text-emerald-700',
        'COMPLETED'           => 'bg-green-100 text-green-700',
        'CANCELLED'           => 'bg-red-100 text-red-700',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountShopTiktok::class, 'account_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketplaceChannel::class, 'channel_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    // ─── Computed Attributes ───────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->order_status] ?? ucfirst(str_replace('_', ' ', $this->order_status ?? ''));
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->order_status] ?? 'bg-slate-100 text-slate-600';
    }

    /**
     * Accessor: created_at from TikTok (converted to app timezone).
     */
    public function getCreatedAtTiktokAttribute(): ?Carbon
    {
        $ts = $this->tiktok_create_time ?? null;
        if (empty($ts)) return null;
        $ts = (int) $ts;
        if ($ts > 1000000000000) {
            $ts = (int) floor($ts / 1000);
        }
        return Carbon::createFromTimestampUTC($ts)->setTimezone(config('app.timezone') ?: date_default_timezone_get());
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeStatus($query, string $status)
    {
        return $query->where('order_status', $status);
    }

    public function scopeNotSyncedToPos($query)
    {
        return $query->where('is_synced_to_pos', false);
    }

    public function scopeByAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }
}
