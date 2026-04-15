<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockSyncLog extends Model
{
    protected $table = 'stock_sync_logs';

    protected $fillable = [
        'account_id',
        'platform',
        'account_name',
        'product_id',
        'sku_id',
        'seller_sku',
        'title',
        'old_quantity',
        'pos_stock',
        'pushed_stock',
        'status',
        'error_message',
        'api_response',
        'retry_count',
        'last_retry_at',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'old_quantity'  => 'integer',
            'pos_stock'     => 'integer',
            'pushed_stock'  => 'integer',
            'retry_count'   => 'integer',
            'api_response'  => 'array',
            'synced_at'     => 'datetime',
            'last_retry_at' => 'datetime',
        ];
    }

    /* ---------- Scopes ---------- */

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /* ---------- Relationships ---------- */

    public function produkSaya(): BelongsTo
    {
        return $this->belongsTo(ProdukSaya::class, 'product_id', 'product_id')
            ->where('sku_id', $this->sku_id);
    }

    /* ---------- Helpers ---------- */

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Stok cocok? (pos_stock == pushed_stock dan success)
     */
    public function isStockMatch(): bool
    {
        return $this->status === 'success' && $this->pos_stock === $this->pushed_stock;
    }
}
