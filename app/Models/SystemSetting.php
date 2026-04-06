<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Konfigurasi global aplikasi berbasis key-value.
 * Gunakan metode statis get() / set() untuk akses cepat.
 *
 * Contoh:
 *   SystemSetting::get('app.name', 'OmniSeller')
 *   SystemSetting::set('sync.auto_interval', 30)
 */
class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_public',
        'is_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_public'    => 'boolean',
            'is_encrypted' => 'boolean',
        ];
    }

    // ─── Static Access ─────────────────────────────────────────────────────

    /** Ambil nilai setting berdasarkan key */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }
        return static::castValue($setting->value, $setting->type);
    }

    /** Simpan/perbarui nilai setting */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value]
        );
    }

    /** Cast nilai ke tipe yang sesuai */
    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float'   => (float) $value,
            'json',
            'array'   => json_decode($value, true),
            default   => $value,
        };
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    // ─── Kelompok Setting yang Tersedia ────────────────────────────────────

    const GROUPS = [
        'general'      => 'Umum',
        'sync'         => 'Sinkronisasi Stok',
        'notification' => 'Notifikasi',
        'security'     => 'Keamanan',
        'api'          => 'API & Integrasi',
        'appearance'   => 'Tampilan',
    ];
}
