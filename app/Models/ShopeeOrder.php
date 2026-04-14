<?php

namespace App\Models;

use App\Models\AccountShopShopee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopeeOrder extends Model
{
    protected $table = 'shopee_orders';

    protected $fillable = [
        'account_id',       // FK → account_shop_shopee.id
        'channel_id',       // FK → marketplace_channels.id
        'warehouse_id',
        'order_sn',         // Shopee order_sn (unique identifier)
        'order_status',
        'buyer_user_id',
        'buyer_username',
        'buyer_name',       // receiver name
        'buyer_phone',      // receiver phone
        'buyer_message',    // message_to_seller
        'shipping_carrier',
        'tracking_number',
        'shipping_address',
        'total_amount',
        'subtotal_amount',  // escrow_amount or total product price
        'shipping_fee',
        'seller_discount',
        'voucher_from_seller',
        'voucher_from_shopee',
        'coin_offset',
        'currency',
        'payment_method',
        'is_cod',
        'create_time',      // Shopee order create_time (unix)
        'update_time',      // Shopee order update_time (unix)
        'pay_time',
        'ship_by_date',
        'days_to_ship',
        'is_synced_to_pos',
        'synced_to_pos_at',
        'pos_order_id',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'account_id'      => 'integer',
            'channel_id'      => 'integer',
            'warehouse_id'    => 'integer',
            'total_amount'    => 'decimal:2',
            'subtotal_amount' => 'decimal:2',
            'shipping_fee'    => 'decimal:2',
            'seller_discount' => 'decimal:2',
            'voucher_from_seller' => 'decimal:2',
            'voucher_from_shopee' => 'decimal:2',
            'coin_offset'     => 'decimal:2',
            'create_time'     => 'integer',
            'update_time'     => 'integer',
            'pay_time'        => 'integer',
            'ship_by_date'    => 'integer',
            'days_to_ship'    => 'integer',
            'is_cod'          => 'boolean',
            'is_synced_to_pos' => 'boolean',
            'synced_to_pos_at' => 'datetime',
            'shipping_address' => 'array',
            'raw_data'        => 'array',
        ];
    }

    // ─── Status Constants (Shopee) ──────────────────────────────────────

    const STATUS_UNPAID             = 'UNPAID';
    const STATUS_READY_TO_SHIP      = 'READY_TO_SHIP';
    const STATUS_PROCESSED          = 'PROCESSED';
    const STATUS_SHIPPED            = 'SHIPPED';
    const STATUS_COMPLETED          = 'COMPLETED';
    const STATUS_IN_CANCEL          = 'IN_CANCEL';
    const STATUS_CANCELLED          = 'CANCELLED';
    const STATUS_INVOICE_PENDING    = 'INVOICE_PENDING';

    const STATUS_LABELS = [
        'UNPAID'            => 'Belum Dibayar',
        'READY_TO_SHIP'     => 'Siap Kirim',
        'PROCESSED'         => 'Diproses',
        'SHIPPED'           => 'Dalam Pengiriman',
        'COMPLETED'         => 'Selesai',
        'IN_CANCEL'         => 'Proses Batal',
        'CANCELLED'         => 'Dibatalkan',
        'INVOICE_PENDING'   => 'Invoice Pending',
    ];

    const STATUS_COLORS = [
        'UNPAID'            => 'bg-yellow-100 text-yellow-700',
        'READY_TO_SHIP'     => 'bg-blue-100 text-blue-700',
        'PROCESSED'         => 'bg-indigo-100 text-indigo-700',
        'SHIPPED'           => 'bg-purple-100 text-purple-700',
        'COMPLETED'         => 'bg-green-100 text-green-700',
        'IN_CANCEL'         => 'bg-orange-100 text-orange-700',
        'CANCELLED'         => 'bg-red-100 text-red-700',
        'INVOICE_PENDING'   => 'bg-slate-100 text-slate-700',
    ];

    // ─── Relationships ──────────────────────────────────────────────────

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountShopShopee::class, 'account_id');
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
        return $this->hasMany(ShopeeOrderItem::class, 'shopee_order_id');
    }

    // ─── Computed Attributes ────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->order_status] ?? ucfirst(str_replace('_', ' ', $this->order_status ?? ''));
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->order_status] ?? 'bg-slate-100 text-slate-600';
    }

    public function getCreatedAtShopeeAttribute(): ?Carbon
    {
        $ts = $this->create_time ?? null;
        if (empty($ts)) return null;
        return Carbon::createFromTimestampUTC((int) $ts)
            ->setTimezone(config('app.timezone') ?: date_default_timezone_get());
    }

    // ─── Scopes ─────────────────────────────────────────────────────────

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
