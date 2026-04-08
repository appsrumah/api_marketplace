<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ChannelAccount;

class Warehouse extends Model
{
    protected $fillable = [
        'created_by',
        'name',
        'code',
        'pos_outlet_id',
        'address',
        'city',
        'province',
        'postal_code',
        'phone',
        'email',
        'pic_name',
        'is_active',
        'is_default',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    /** User yang membuat warehouse */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Akun TikTok yang menggunakan warehouse ini */
    public function tiktokAccounts(): HasMany
    {
        return $this->hasMany(AccountShopTiktok::class, 'warehouse_id');
    }

    /** Akun channel general yang menggunakan warehouse ini */
    public function channelAccounts(): HasMany
    {
        return $this->hasMany(ChannelAccount::class, 'warehouse_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    /** Alamat singkat (kota + provinsi) */
    public function getShortAddressAttribute(): string
    {
        return implode(', ', array_filter([$this->city, $this->province]));
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
