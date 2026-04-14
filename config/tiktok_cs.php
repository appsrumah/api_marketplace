<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TikTok Customer Service — Webhook & API Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk integrasi webhook Customer Service TikTok Shop.
    | Webhook menerima notifikasi realtime saat ada pesan baru dari pembeli.
    |
    | Dokumentasi resmi:
    | https://partner.tiktokshop.com/docv2/page/6507ead7b99d5302be949ba9
    |
    */

    // ── Webhook Verification ───────────────────────────────────────────────
    // Secret key untuk memverifikasi signature webhook dari TikTok.
    // Didapat saat mendaftarkan webhook URL di TikTok Shop Partner Center.
    'webhook_secret' => env('TIKTOK_CS_WEBHOOK_SECRET', ''),

    // Aktifkan/nonaktifkan verifikasi signature webhook.
    // Set false saat development agar mudah testing manual via Postman/ngrok.
    'verify_signature' => env('TIKTOK_CS_VERIFY_SIGNATURE', true),

    // ── API Settings ───────────────────────────────────────────────────────
    // Base URL API — gunakan production atau sandbox.
    'api_base' => env('TIKTOK_API_BASE', 'https://open-api.tiktokglobalshop.com'),

    // App credentials (menggunakan key yg sama dengan TikTokApiService)
    'app_key'    => env('TIKTOK_APP_KEY', ''),
    'app_secret' => env('TIKTOK_APP_SECRET', ''),

    // ── Timeout ────────────────────────────────────────────────────────────
    // Max waktu (detik) untuk HTTP request ke TikTok API
    'api_timeout' => env('TIKTOK_CS_API_TIMEOUT', 15),

    // Max waktu (detik) untuk menunggu koneksi terbuka
    'api_connect_timeout' => env('TIKTOK_CS_CONNECT_TIMEOUT', 5),

    // ── Queue ──────────────────────────────────────────────────────────────
    // Queue name untuk processing webhook (gunakan default jika shared)
    'queue_name' => env('TIKTOK_CS_QUEUE', 'default'),

    // Max retries untuk job processing
    'queue_max_tries' => env('TIKTOK_CS_QUEUE_TRIES', 3),

    // ── Conversation Sync ──────────────────────────────────────────────────
    // Batas jumlah percakapan per halaman saat sync via API
    'sync_page_size' => 20,

    // Max halaman saat sync (untuk menghindari infinite loop)
    'sync_max_pages' => 50,

    // ── Logging ────────────────────────────────────────────────────────────
    // Simpan raw payload webhook ke tabel tiktok_webhook_logs
    'log_raw_payload' => env('TIKTOK_CS_LOG_PAYLOAD', true),

    // Retensi log webhook (hari). Log lebih lama akan dihapus oleh prune command.
    'log_retention_days' => env('TIKTOK_CS_LOG_RETENTION', 30),

    // ── Broadcasting ───────────────────────────────────────────────────────
    // Channel prefix untuk notifikasi realtime via Laravel Broadcasting
    'broadcast_channel_prefix' => 'tiktok-cs',

];
