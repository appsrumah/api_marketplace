<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountShopTiktok extends Model
{
    protected $table = 'account_shop_tiktok';

    protected $fillable = [
        'access_token',
        'access_token_expire_in',
        'refresh_token',
        'refresh_token_expire_in',
        'seller_name',
        'seller_base_region',
        'shop_id',
        'shop_name',
        'shop_cipher',
        'no_telp',            // ← Nomor HP penerima notifikasi Wablas (koma-separated)
        'status',
        'token_obtained_at',
        'last_sync_at',
        'id_outlet',          // ← ID outlet di sistem POS
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

    /**
     * Ambil daftar nomor HP penerima notifikasi (dari kolom no_telp)
     * @return string[]
     */
    public function getNotifPhones(): array
    {
        if (empty($this->no_telp)) {
            return [];
        }
        return array_filter(
            array_map('trim', explode(',', $this->no_telp))
        );
    }
}
