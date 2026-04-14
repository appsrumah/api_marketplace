<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model generik untuk akun channel marketplace (Shopee, Lazada, Tokopedia, dst.).
 * TikTok Shop khusus tetap menggunakan AccountShopTiktok untuk backward-compat.
 */
class ChannelAccount extends Model
{
    protected $fillable = [
        'channel_id',
        'user_id',
        'warehouse_id',
        'account_alias',
        'shop_id',
        'shop_name',
        'seller_name',
        'region',
        'access_token',
        'access_token_expires_at',
        'refresh_token',
        'refresh_token_expires_at',
        'extra_credentials',
        'status',
        'token_obtained_at',
        'last_sync_at',
        'last_update_stock',
    ];

    protected function casts(): array
    {
        return [
            'access_token_expires_at'  => 'datetime',
            'refresh_token_expires_at' => 'datetime',
            'token_obtained_at'        => 'datetime',
            'last_sync_at'             => 'datetime',
            'last_update_stock'        => 'datetime',
            'extra_credentials'        => 'encrypted:array',
            'access_token'             => 'encrypted',
            'refresh_token'            => 'encrypted',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketplaceChannel::class, 'channel_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    // ─── Token Helpers ─────────────────────────────────────────────────────

    public function isTokenExpired(): bool
    {
        if (!$this->access_token_expires_at) {
            return true;
        }
        return now()->gt($this->access_token_expires_at);
    }

    public function isRefreshTokenExpired(): bool
    {
        if (!$this->refresh_token_expires_at) {
            return true;
        }
        return now()->gt($this->refresh_token_expires_at);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByChannel($query, string $channelCode)
    {
        return $query->whereHas('channel', fn($q) => $q->where('code', $channelCode));
    }
}
