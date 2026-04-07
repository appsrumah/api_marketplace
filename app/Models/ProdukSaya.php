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
        'price',
        'seller_sku',
        'status_info',
        'current_status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price'    => 'decimal:2',
        ];
    }

    /* ---------- Relationships ---------- */

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountShopTiktok::class, 'account_id');
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
