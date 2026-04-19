<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProdukSaya extends Model
{
    protected $table = 'produk_saya';

    protected $fillable = [
        'account_id',
        'channel_id',      // ← FK ke marketplace_channels (denormalized)
        'product_id',
        'sku_id',
        'platform',
        'title',
        'product_status',
        'quantity',
        'last_pushed_stock',
        'last_pushed_at',
        'price',
        'seller_sku',
        'status_info',
        'current_status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'last_pushed_stock' => 'integer',
            'last_pushed_at' => 'datetime',
            'price'    => 'decimal:2',
        ];
    }

    /* ---------- Relationships ---------- */

    /**
     * Polymorphic-style account relationship:
     * - platform=TIKTOK/TOKOPEDIA → AccountShopTiktok
     * - platform=SHOPEE           → AccountShopShopee
     */
    public function account(): BelongsTo
    {
        if ($this->platform === 'SHOPEE') {
            return $this->belongsTo(AccountShopShopee::class, 'account_id');
        }

        return $this->belongsTo(AccountShopTiktok::class, 'account_id');
    }

    /** TikTok account (explicit) */
    public function tiktokAccount(): BelongsTo
    {
        return $this->belongsTo(AccountShopTiktok::class, 'account_id');
    }

    /** Shopee account (explicit) */
    public function shopeeAccount(): BelongsTo
    {
        return $this->belongsTo(AccountShopShopee::class, 'account_id');
    }

    /** Channel marketplace langsung (denormalized dari account.channel_id) */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketplaceChannel::class, 'channel_id');
    }

    /** Detail produk dari TikTok API (join by product_id) */
    public function detail(): HasOne
    {
        return $this->hasOne(ProductDetail::class, 'product_id', 'product_id');
    }

    /* ---------- Computed ---------- */

    /** Nama toko (works for both platforms) */
    public function getAccountNameAttribute(): string
    {
        if ($this->relationLoaded('tiktokAccount') && $this->tiktokAccount) {
            return $this->tiktokAccount->shop_name ?: $this->tiktokAccount->seller_name ?: '-';
        }
        if ($this->relationLoaded('shopeeAccount') && $this->shopeeAccount) {
            return $this->shopeeAccount->seller_name ?: '-';
        }
        // Fallback: lazy load
        if ($this->platform === 'SHOPEE') {
            return $this->shopeeAccount?->seller_name ?: '-';
        }
        return $this->tiktokAccount?->shop_name ?: $this->tiktokAccount?->seller_name ?: '-';
    }

    /** Platform label for display */
    public function getPlatformLabelAttribute(): string
    {
        return match ($this->platform) {
            'TIKTOK'    => 'TikTok',
            'TOKOPEDIA' => 'Tokopedia',
            'SHOPEE'    => 'Shopee',
            default     => $this->platform ?? '-',
        };
    }

    /** Platform color for badges */
    public function getPlatformColorAttribute(): string
    {
        return match ($this->platform) {
            'TIKTOK'    => 'bg-slate-800 text-white',
            'TOKOPEDIA' => 'bg-green-600 text-white',
            'SHOPEE'    => 'bg-orange-500 text-white',
            default     => 'bg-surface-container text-on-surface',
        };
    }

    /* ---------- Scopes ---------- */

    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeActive($query)
    {
        return $query->where('product_status', 'ACTIVATE');
    }
}
