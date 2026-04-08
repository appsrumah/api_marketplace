<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'sku_id',
        'sku_name',
        'seller_sku',
        'quantity',
        'original_price',
        'sale_price',
        'platform_discount',
        'seller_discount',
        'item_tax',
        'currency',
        'product_image',
    ];

    protected function casts(): array
    {
        return [
            'quantity'          => 'integer',
            'original_price'   => 'decimal:2',
            'sale_price'       => 'decimal:2',
            'platform_discount' => 'decimal:2',
            'seller_discount'  => 'decimal:2',
            'item_tax'         => 'decimal:2',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    // ─── Computed ──────────────────────────────────────────────────────────

    public function getSubtotalAttribute(): float
    {
        return round($this->sale_price * $this->quantity, 2);
    }

    public function getTotalDiscountAttribute(): float
    {
        return round(($this->platform_discount ?? 0) + ($this->seller_discount ?? 0), 2);
    }
}
