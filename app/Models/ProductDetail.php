<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDetail extends Model
{
    protected $fillable = [
        'account_id',
        'product_id',
        'platform',
        'title',
        'description',
        'category_id',
        'category_name',
        'main_images',
        'video',
        'skus',
        'product_status',
        'product_attributes',
        'size_chart',
        'brand_id',
        'brand_name',
        'package_weight',
        'package_length',
        'package_width',
        'package_height',
        'package_dimensions_unit',
        'product_certifications',
        'delivery_options',
        'integrated_platform_statuses',
        'tiktok_create_time',
        'tiktok_update_time',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'main_images'                   => 'array',
            'video'                         => 'array',
            'skus'                          => 'array',
            'product_attributes'            => 'array',
            'size_chart'                    => 'array',
            'product_certifications'        => 'array',
            'delivery_options'              => 'array',
            'integrated_platform_statuses'  => 'array',
            'raw_data'                      => 'array',
            'package_weight'                => 'decimal:3',
            'package_length'                => 'decimal:2',
            'package_width'                 => 'decimal:2',
            'package_height'                => 'decimal:2',
            'tiktok_create_time'            => 'integer',
            'tiktok_update_time'            => 'integer',
        ];
    }

    // ─── Status Constants ──────────────────────────────────────────────────

    const STATUS_DRAFT    = 'DRAFT';
    const STATUS_PENDING  = 'PENDING';
    const STATUS_LIVE     = 'LIVE';
    const STATUS_SELLER_DEACTIVATED = 'SELLER_DEACTIVATED';
    const STATUS_PLATFORM_DEACTIVATED = 'PLATFORM_DEACTIVATED';
    const STATUS_FROZEN   = 'FROZEN';
    const STATUS_DELETED  = 'DELETED';

    const STATUS_LABELS = [
        'DRAFT'                  => 'Draft',
        'PENDING'                => 'Menunggu Review',
        'LIVE'                   => 'Aktif',
        'SELLER_DEACTIVATED'     => 'Nonaktif (Seller)',
        'PLATFORM_DEACTIVATED'   => 'Nonaktif (Platform)',
        'FROZEN'                 => 'Dibekukan',
        'DELETED'                => 'Dihapus',
    ];

    const STATUS_COLORS = [
        'DRAFT'                  => 'bg-slate-100 text-slate-600',
        'PENDING'                => 'bg-yellow-100 text-yellow-700',
        'LIVE'                   => 'bg-green-100 text-green-700',
        'SELLER_DEACTIVATED'     => 'bg-red-100 text-red-700',
        'PLATFORM_DEACTIVATED'   => 'bg-orange-100 text-orange-700',
        'FROZEN'                 => 'bg-blue-100 text-blue-700',
        'DELETED'                => 'bg-gray-100 text-gray-500',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountShopTiktok::class, 'account_id');
    }

    // ─── Computed ──────────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->product_status] ?? ucfirst(str_replace('_', ' ', $this->product_status ?? ''));
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->product_status] ?? 'bg-slate-100 text-slate-600';
    }

    public function getMainImageUrlAttribute(): ?string
    {
        $images = $this->main_images;
        return is_array($images) && count($images) > 0 ? ($images[0]['url'] ?? $images[0]) : null;
    }

    public function getCreatedAtTiktokAttribute(): ?\Carbon\Carbon
    {
        return $this->tiktok_create_time
            ? \Carbon\Carbon::createFromTimestamp($this->tiktok_create_time)
            : null;
    }
}
