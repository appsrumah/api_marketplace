<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ChannelAccount;

class MarketplaceChannel extends Model
{
    protected $table = 'marketplace_channels';

    // ─── Channel Slugs (konstanta) ────────────────────────────────────────

    const TIKTOK    = 'tiktok';
    const SHOPEE    = 'shopee';
    const TOKOPEDIA = 'tokopedia';
    const LAZADA    = 'lazada';
    const BLIBLI    = 'blibli';
    const BUKALAPAK = 'bukalapak';
    const ZALORA    = 'zalora';

    // ─── Mass Assignment ──────────────────────────────────────────────────

    protected $fillable = [
        'name',
        'code',        // Kolom di DB: 'code' (bukan 'slug')
        'slug',        // Backward-compat jika migration lama pakai slug
        'logo',
        'logo_url',
        'color',
        'bg_color',
        'text_color',
        'api_base_url',
        'auth_type',
        'country_codes',
        'is_active',
        'sort_order',
        'notes',
    ];

    /** Ambil identifier (code atau slug, tergantung skema DB) */
    public function getIdentifierAttribute(): string
    {
        return $this->code ?? $this->slug ?? strtolower($this->name);
    }

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    /** Akun TikTok yang menggunakan channel ini (backward-compat) */
    public function tiktokAccounts(): HasMany
    {
        return $this->hasMany(AccountShopTiktok::class, 'channel_id');
    }

    /** Akun general (semua platform) */
    public function channelAccounts(): HasMany
    {
        return $this->hasMany(ChannelAccount::class, 'channel_id');
    }

    /** Produk yang terhubung ke channel ini */
    public function products(): HasMany
    {
        return $this->hasMany(ProdukSaya::class, 'channel_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    /** Badge HTML classes untuk UI */
    public function getBadgeClassesAttribute(): string
    {
        return $this->bg_color && $this->text_color
            ? ''   // gunakan inline style
            : 'bg-slate-100 text-slate-600'; // fallback
    }

    /** Inline style untuk badge */
    public function getBadgeStyleAttribute(): string
    {
        $bg   = $this->bg_color   ?? '#f1f5f9';
        $text = $this->text_color ?? '#475569';
        return "background-color:{$bg};color:{$text};";
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
