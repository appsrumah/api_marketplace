<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopeeOrderItem extends Model
{
    protected $table = 'shopee_order_items';

    protected $fillable = [
        'shopee_order_id',
        'item_id',
        'item_name',
        'item_sku',
        'model_id',
        'model_name',
        'model_sku',
        'model_original_price',
        'model_discounted_price',
        'quantity_purchased',
        'image_url',
        'weight',
        'is_wholesale',
    ];

    protected function casts(): array
    {
        return [
            'shopee_order_id'         => 'integer',
            'item_id'                 => 'integer',
            'model_id'                => 'integer',
            'model_original_price'    => 'decimal:2',
            'model_discounted_price'  => 'decimal:2',
            'quantity_purchased'      => 'integer',
            'weight'                  => 'decimal:3',
            'is_wholesale'            => 'boolean',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────────────

    public function shopeeOrder(): BelongsTo
    {
        return $this->belongsTo(ShopeeOrder::class, 'shopee_order_id');
    }

    // ─── Computed ───────────────────────────────────────────────────────

    /** SKU efektif: pakai model_sku jika ada, fallback item_sku */
    public function getEffectiveSkuAttribute(): string
    {
        return trim($this->model_sku ?: $this->item_sku ?: '');
    }

    public function getSubtotalAttribute(): float
    {
        $price = (float) ($this->model_discounted_price ?: $this->model_original_price ?: 0);
        return round($price * ($this->quantity_purchased ?? 1), 2);
    }

    public function getDiscountAttribute(): float
    {
        $orig  = (float) ($this->model_original_price ?? 0);
        $disc  = (float) ($this->model_discounted_price ?? 0);
        if ($orig > 0 && $disc > 0 && $orig > $disc) {
            return round(($orig - $disc) * ($this->quantity_purchased ?? 1), 2);
        }
        return 0;
    }
}
