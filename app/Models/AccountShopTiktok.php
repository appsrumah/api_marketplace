<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountShopTiktok extends Model
{
    protected $table = 'account_shop_tiktok';

    protected $fillable = [
        'channel_id',         // ← FK ke marketplace_channels
        'user_id',            // ← User pemilik akun
        'warehouse_id',       // ← FK ke warehouses
        'access_token',
        'access_token_expire_in',
        'refresh_token',
        'refresh_token_expire_in',
        'seller_name',
        'seller_base_region',
        'shop_id',
        'shop_name',
        'shop_cipher',
        'status',
        'token_obtained_at',
        'last_sync_at',
        'id_outlet',          // ← ID outlet di sistem POS (legacy; gunakan warehouse_id)
        'last_update_stock',  // ← Waktu terakhir stok di-push ke TikTok
    ];

    protected function casts(): array
    {
        return [
            'access_token_expire_in'  => 'datetime',
            'refresh_token_expire_in' => 'datetime',
            'token_obtained_at'       => 'datetime',
            'last_sync_at'            => 'datetime',
            'last_update_stock'       => 'datetime',  // ← tambah
            'id_outlet'               => 'integer',   // ← tambah
            'access_token'            => 'encrypted',
            'refresh_token'           => 'encrypted',
        ];
    }

    /* ---------- Relationships ---------- */

    public function produk(): HasMany
    {
        return $this->hasMany(ProdukSaya::class, 'account_id');
    }

    /** Channel marketplace (misal: TikTok Shop) */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketplaceChannel::class, 'channel_id');
    }

    /** User pemilik/pengelola akun ini */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Warehouse/outlet yang dikaitkan ke akun ini */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /* ---------- Scopes ---------- */

    /**
     * Scope: filter akun berdasar user login.
     * Super Admin melihat semua; role lain hanya melihat akun milik sendiri.
     */
    public function scopeForUser($query, ?\App\Models\User $user = null)
    {
        $resolved = $user ?? \Illuminate\Support\Facades\Auth::user();
        if ($resolved instanceof \App\Models\User && !$resolved->isSuperAdmin()) {
            return $query->where('user_id', $resolved->id);
        }
        return $query;
    }

    /* ---------- Helpers ---------- */

    public function isTokenExpired(): bool
    {
        if (!$this->access_token_expire_in) {
            return true;
        }
        return now()->gt($this->access_token_expire_in);
    }

    public function isRefreshTokenExpired(): bool
    {
        if (!$this->refresh_token_expire_in) {
            return true;
        }
        return now()->gt($this->refresh_token_expire_in);
    }
}
