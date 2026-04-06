<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Seed pengaturan default sistem.
     * Nilai bisa diubah melalui UI Settings (hanya Super Admin).
     */
    public function run(): void
    {
        $settings = [

            // ── Umum ─────────────────────────────────────────────────────
            [
                'key'          => 'app.name',
                'value'        => 'OmniSeller',
                'type'         => 'string',
                'group'        => 'general',
                'label'        => 'Nama Aplikasi',
                'description'  => 'Nama yang ditampilkan di header dan email.',
                'is_public'    => true,
                'is_encrypted' => false,
            ],
            [
                'key'          => 'app.logo',
                'value'        => null,
                'type'         => 'string',
                'group'        => 'general',
                'label'        => 'URL Logo Aplikasi',
                'description'  => 'URL atau path logo untuk header & email.',
                'is_public'    => true,
                'is_encrypted' => false,
            ],
            [
                'key'          => 'app.timezone',
                'value'        => 'Asia/Jakarta',
                'type'         => 'string',
                'group'        => 'general',
                'label'        => 'Zona Waktu',
                'description'  => 'Zona waktu default untuk tampilan tanggal/waktu.',
                'is_public'    => true,
                'is_encrypted' => false,
            ],

            // ── Sinkronisasi Stok ─────────────────────────────────────────
            [
                'key'          => 'sync.auto_enabled',
                'value'        => '1',
                'type'         => 'boolean',
                'group'        => 'sync',
                'label'        => 'Auto Sync Stok',
                'description'  => 'Aktifkan sinkronisasi stok otomatis via cron.',
                'is_public'    => false,
                'is_encrypted' => false,
            ],
            [
                'key'          => 'sync.interval_minutes',
                'value'        => '30',
                'type'         => 'integer',
                'group'        => 'sync',
                'label'        => 'Interval Sync (menit)',
                'description'  => 'Seberapa sering stok otomatis di-sync. Minimum 5 menit.',
                'is_public'    => false,
                'is_encrypted' => false,
            ],
            [
                'key'          => 'sync.batch_size',
                'value'        => '50',
                'type'         => 'integer',
                'group'        => 'sync',
                'label'        => 'Batch Size Sync',
                'description'  => 'Jumlah produk per batch saat sync massal.',
                'is_public'    => false,
                'is_encrypted' => false,
            ],
            [
                'key'          => 'sync.pos_api_url',
                'value'        => null,
                'type'         => 'string',
                'group'        => 'sync',
                'label'        => 'URL API POS',
                'description'  => 'Endpoint API sistem POS untuk ambil data stok.',
                'is_public'    => false,
                'is_encrypted' => false,
            ],
            [
                'key'          => 'sync.pos_api_key',
                'value'        => null,
                'type'         => 'string',
                'group'        => 'sync',
                'label'        => 'API Key POS',
                'description'  => 'Kunci API untuk autentikasi ke sistem POS.',
                'is_public'    => false,
                'is_encrypted' => true,
            ],

            // ── Notifikasi ────────────────────────────────────────────────
            [
                'key'          => 'notification.email_enabled',
                'value'        => '0',
                'type'         => 'boolean',
                'group'        => 'notification',
                'label'        => 'Notifikasi Email',
                'description'  => 'Kirim notifikasi via email untuk event penting.',
                'is_public'    => false,
                'is_encrypted' => false,
            ],
            [
                'key'          => 'notification.email_recipients',
                'value'        => null,
                'type'         => 'array',
                'group'        => 'notification',
                'label'        => 'Penerima Email Notifikasi',
                'description'  => 'Daftar email penerima, format JSON array.',
                'is_public'    => false,
                'is_encrypted' => false,
            ],
            [
                'key'          => 'notification.low_stock_threshold',
                'value'        => '5',
                'type'         => 'integer',
                'group'        => 'notification',
                'label'        => 'Threshold Stok Rendah',
                'description'  => 'Kirim notifikasi jika stok produk di bawah nilai ini.',
                'is_public'    => false,
                'is_encrypted' => false,
            ],

            // ── Keamanan ──────────────────────────────────────────────────
            [
                'key'          => 'security.login_max_attempts',
                'value'        => '5',
                'type'         => 'integer',
                'group'        => 'security',
                'label'        => 'Maks. Percobaan Login',
                'description'  => 'Jumlah percobaan login gagal sebelum akun dikunci sementara.',
                'is_public'    => false,
                'is_encrypted' => false,
            ],
            [
                'key'          => 'security.session_lifetime_minutes',
                'value'        => '120',
                'type'         => 'integer',
                'group'        => 'security',
                'label'        => 'Durasi Sesi (menit)',
                'description'  => 'Sesi login otomatis berakhir setelah durasi ini.',
                'is_public'    => false,
                'is_encrypted' => false,
            ],

            // ── API & Integrasi ────────────────────────────────────────────
            [
                'key'          => 'api.cron_secret_key',
                'value'        => null,
                'type'         => 'string',
                'group'        => 'api',
                'label'        => 'Cron Secret Key',
                'description'  => 'Kunci rahasia untuk mengamankan endpoint cron job.',
                'is_public'    => false,
                'is_encrypted' => true,
            ],
            [
                'key'          => 'api.tiktok_app_id',
                'value'        => null,
                'type'         => 'string',
                'group'        => 'api',
                'label'        => 'TikTok App ID',
                'description'  => 'App ID dari TikTok Open Platform.',
                'is_public'    => false,
                'is_encrypted' => false,
            ],
            [
                'key'          => 'api.tiktok_app_secret',
                'value'        => null,
                'type'         => 'string',
                'group'        => 'api',
                'label'        => 'TikTok App Secret',
                'description'  => 'App Secret dari TikTok Open Platform.',
                'is_public'    => false,
                'is_encrypted' => true,
            ],

            // ── Tampilan ──────────────────────────────────────────────────
            [
                'key'          => 'appearance.items_per_page',
                'value'        => '15',
                'type'         => 'integer',
                'group'        => 'appearance',
                'label'        => 'Item per Halaman',
                'description'  => 'Jumlah item default pada setiap halaman listing.',
                'is_public'    => false,
                'is_encrypted' => false,
            ],
        ];

        foreach ($settings as $data) {
            SystemSetting::updateOrCreate(['key' => $data['key']], $data);
        }

        $this->command->info('✅  System settings berhasil di-seed (' . count($settings) . ' settings).');
    }
}
