<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

/**
 * Model untuk tabel account_shop_shopee.
 * Menyimpan data akun toko Shopee yang terintegrasi.
 *
 * @property int         $id
 * @property int|null    $channel_id
 * @property int|null    $user_id
 * @property string|null $seller_name        Nama toko dari Shopee
 * @property string|null $shop_id            Shop ID dari Shopee
 * @property string|null $code               Auth code dari OAuth
 * @property string|null $access_token
 * @property \Carbon\Carbon|null $access_token_expire_in
 * @property string|null $refresh_token
 * @property \Carbon\Carbon|null $refresh_token_expire_in
 * @property int|null    $id_outlet          ID outlet di sistem POS
 * @property string|null $telp_notif         Nomor WA notifikasi
 * @property string      $status             active | expired | revoked
 * @property \Carbon\Carbon|null $token_obtained_at
 * @property \Carbon\Carbon|null $last_sync_at
 * @property \Carbon\Carbon|null $last_update_stock
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AccountShopShopee extends Model
{
    protected $table = 'account_shop_shopee';

    protected $fillable = [
        'channel_id',
        'user_id',
        'seller_name',
        'shop_id',
        'code',
        'access_token',
        'access_token_expire_in',
        'refresh_token',
        'refresh_token_expire_in',
        'id_outlet',
        'telp_notif',
        'status',
        'token_obtained_at',
        'last_sync_at',
        'last_update_stock',
    ];

    protected function casts(): array
    {
        return [
            'access_token_expire_in'  => 'datetime',
            'refresh_token_expire_in' => 'datetime',
            'token_obtained_at'       => 'datetime',
            'last_sync_at'            => 'datetime',
            'last_update_stock'       => 'datetime',
            'id_outlet'               => 'integer',
            'access_token'            => 'encrypted',
            'refresh_token'           => 'encrypted',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    /** Channel marketplace (Shopee) */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketplaceChannel::class, 'channel_id');
    }

    /** User pemilik/pengelola akun ini */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Order-order Shopee yang masuk ke akun ini */
    public function orders(): HasMany
    {
        return $this->hasMany(ShopeeOrder::class, 'account_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    /**
     * Filter akun berdasar user login.
     * Super Admin melihat semua; role lain hanya melihat akun milik sendiri.
     */
    public function scopeForUser($query, ?\App\Models\User $user = null)
    {
        $resolved = $user ?? Auth::user();
        if ($resolved instanceof \App\Models\User && !$resolved->isSuperAdmin()) {
            return $query->where('user_id', $resolved->id);
        }
        return $query;
    }

    // ─── Helpers / Accessors ──────────────────────────────────────────────

    /**
     * Alias shop_name → seller_name agar kompatibel dengan kode yang pakai shop_name.
     */
    public function getShopNameAttribute(): ?string
    {
        return $this->seller_name;
    }

    /** Apakah access token sudah kadaluarsa? */
    public function isTokenExpired(): bool
    {
        if (!$this->access_token_expire_in) {
            return true;
        }
        return now()->gt($this->access_token_expire_in);
    }

    /** Apakah refresh token sudah kadaluarsa? */
    public function isRefreshTokenExpired(): bool
    {
        if (!$this->refresh_token_expire_in) {
            return true;
        }
        return now()->gt($this->refresh_token_expire_in);
    }
}
