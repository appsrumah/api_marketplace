<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdukSaya extends Model
{
    protected $table = 'produk_saya';

    protected $fillable = [
        'account_id',
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
