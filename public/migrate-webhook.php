<?php

/**
 * migrate-webhook.php — Migrasi tabel Webhook + Wablas + kolom no_telp
 * Upload ke: public_html/app.oleh2indonesia.com/public/migrate-webhook.php
 * Buka sekali di browser, lalu HAPUS file ini setelah selesai.
 *
 * Cakupan:
 * - CREATE TABLE tiktok_webhook_events  (menyimpan semua event dari TikTok)
 * - CREATE TABLE wablas_bots            (konfigurasi bot Wablas)
 * - ALTER  TABLE account_shop_tiktok    (tambah kolom no_telp)
 * - Seeder: insert default wablas_bots
 */

define('LARAVEL_ROOT', dirname(__DIR__));
require LARAVEL_ROOT . '/vendor/autoload.php';

$app    = require LARAVEL_ROOT . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$results = [];

$nextBatch = function () {
    return DB::table('migrations')->max('batch') + 1;
};

$recordMigration = function (string $name) use ($nextBatch) {
    if (!DB::table('migrations')->where('migration', $name)->exists()) {
        DB::table('migrations')->insert(['migration' => $name, 'batch' => $nextBatch()]);
    }
};

// ═══════════════════════════════════════════════════════════════════════════════
// 1. TABEL tiktok_webhook_events
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('tiktok_webhook_events')) {
        DB::statement("
            CREATE TABLE tiktok_webhook_events (
                id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                account_id       BIGINT UNSIGNED NULL          COMMENT 'FK ke account_shop_tiktok',
                shop_id          VARCHAR(50)     NULL          COMMENT 'TikTok shop_id pengirim event',
                type             TINYINT UNSIGNED NOT NULL     COMMENT '1=ORDER,2=MSG,3=MSG_LISTENER,4=PKG,5=PRODUCT,6=CANCEL,7=RETURN,8=REVERSE,9=DEAUTH,10=EXPIRE,99=OTHER',
                event_type       VARCHAR(80)     NOT NULL      COMMENT 'Nama event asli dari TikTok',
                tiktok_timestamp INT UNSIGNED    NULL          COMMENT 'Unix timestamp event dari TikTok',
                order_id         VARCHAR(80)     NULL          COMMENT 'order_id jika event terkait pesanan',
                order_status     VARCHAR(40)     NULL          COMMENT 'Status order baru',
                conversation_id  VARCHAR(80)     NULL          COMMENT 'conversation_id jika event terkait pesan',
                product_id       VARCHAR(80)     NULL          COMMENT 'product_id jika event terkait produk',
                payload          JSON            NULL          COMMENT 'Raw JSON body dari TikTok',
                status           ENUM('received','processing','processed','failed','ignored') NOT NULL DEFAULT 'received',
                notified         TINYINT(1)      NOT NULL DEFAULT 0 COMMENT 'Sudah dikirim notifikasi Wablas?',
                error_message    TEXT            NULL,
                created_at       TIMESTAMP       NULL,
                updated_at       TIMESTAMP       NULL,

                INDEX idx_wh_account_id (account_id),
                INDEX idx_wh_event_type (event_type),
                INDEX idx_wh_order_id   (order_id),
                INDEX idx_wh_status     (status),
                INDEX idx_wh_created    (created_at),
                INDEX idx_wh_shop_event (shop_id, event_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $recordMigration('2026_04_11_000001_create_tiktok_webhook_events_table');
        $results[] = ['status' => '✅', 'msg' => 'Tabel tiktok_webhook_events dibuat'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Tabel tiktok_webhook_events sudah ada — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal buat tiktok_webhook_events: ' . $e->getMessage()];
}


// ═══════════════════════════════════════════════════════════════════════════════
// 2. TABEL wablas_bots
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('wablas_bots')) {
        DB::statement("
            CREATE TABLE wablas_bots (
                id                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name                   VARCHAR(100) NOT NULL     COMMENT 'Nama label bot',
                server_url             VARCHAR(255) NOT NULL DEFAULT 'https://pati.wablas.com' COMMENT 'Base URL Wablas server',
                token                  TEXT         NOT NULL     COMMENT 'Token API dari Wablas (encrypted)',
                secret_key             VARCHAR(255) NULL         COMMENT 'Secret key tambahan',
                phone_notif            VARCHAR(500) NOT NULL     COMMENT 'Nomor HP penerima (koma-separated)',
                notify_order_status    TINYINT(1)   NOT NULL DEFAULT 1 COMMENT 'Notif status order',
                notify_new_message     TINYINT(1)   NOT NULL DEFAULT 1 COMMENT 'Notif pesan baru',
                notify_cancellation    TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Notif pembatalan',
                notify_return          TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Notif retur',
                notify_product_change  TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Notif perubahan produk',
                is_active              TINYINT(1)   NOT NULL DEFAULT 1,
                user_id                BIGINT UNSIGNED NULL      COMMENT 'User pemilik bot',
                created_at             TIMESTAMP    NULL,
                updated_at             TIMESTAMP    NULL,

                INDEX idx_wb_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $recordMigration('2026_04_11_000002_create_wablas_bots_table');
        $results[] = ['status' => '✅', 'msg' => 'Tabel wablas_bots dibuat'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Tabel wablas_bots sudah ada — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal buat wablas_bots: ' . $e->getMessage()];
}


// ═══════════════════════════════════════════════════════════════════════════════
// 3. ALTER account_shop_tiktok — Tambah kolom no_telp
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasColumn('account_shop_tiktok', 'no_telp')) {
        DB::statement("
            ALTER TABLE account_shop_tiktok
            ADD COLUMN no_telp VARCHAR(500) NULL
            COMMENT 'Nomor HP penerima notifikasi Wablas (koma-separated, bisa banyak)'
            AFTER shop_cipher
        ");
        $recordMigration('2026_04_11_000003_add_no_telp_to_account_shop_tiktok');
        $results[] = ['status' => '✅', 'msg' => 'Kolom no_telp ditambahkan ke account_shop_tiktok'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Kolom no_telp sudah ada — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal ALTER account_shop_tiktok: ' . $e->getMessage()];
}


// ═══════════════════════════════════════════════════════════════════════════════
// 4. SEEDER — Insert default Wablas bot (hanya jika tabel masih kosong)
//    ⚠️  GANTI TOKEN & SECRET_KEY dengan data asli Anda!
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (DB::table('wablas_bots')->count() === 0) {
        DB::table('wablas_bots')->insert([
            'name'                => 'Bot Utama Oleh2',
            'server_url'          => 'https://pati.wablas.com',
            'token'               => '___GANTI_DENGAN_TOKEN_WABLAS_ANDA___',
            'secret_key'          => '___GANTI_DENGAN_SECRET_KEY___',
            'phone_notif'         => '6281234567890',   // ← Ganti nomor Anda
            'notify_order_status' => 1,
            'notify_new_message'  => 1,
            'notify_cancellation' => 1,
            'notify_return'       => 1,
            'notify_product_change' => 0,
            'is_active'           => 1,
            'user_id'             => 1,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
        $results[] = ['status' => '✅', 'msg' => 'Default Wablas bot di-seed (⚠️ GANTI TOKEN!)'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Wablas bot sudah ada data — seed dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal seed wablas_bots: ' . $e->getMessage()];
}


// ═══════════════════════════════════════════════════════════════════════════════
// OUTPUT
// ═══════════════════════════════════════════════════════════════════════════════
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Migrate Webhook + Wablas</title>
    <style>
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            max-width: 720px;
            margin: 40px auto;
            padding: 0 20px;
            background: #0f0f0f;
            color: #e0e0e0;
        }

        h1 {
            color: #7c4dff;
            font-size: 1.6rem;
        }

        h2 {
            color: #b388ff;
            font-size: 1.1rem;
            margin-top: 2em;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        th,
        td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #333;
        }

        th {
            background: #1a1a2e;
            color: #b388ff;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        td {
            font-size: 0.9rem;
        }

        .ok {
            color: #66bb6a;
        }

        .info {
            color: #42a5f5;
        }

        .err {
            color: #ef5350;
        }

        .warn {
            background: #4a3000;
            border: 1px solid #ff9800;
            border-radius: 8px;
            padding: 14px;
            margin: 20px 0;
            font-size: 0.85rem;
            color: #ffe0b2;
        }

        code {
            background: #1a1a2e;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>
    <h1>🔧 Migrate Webhook + Wablas</h1>
    <p>Tanggal: <?= date('d M Y H:i:s T') ?></p>

    <table>
        <tr>
            <th>Status</th>
            <th>Keterangan</th>
        </tr>
        <?php foreach ($results as $r): ?>
            <tr>
                <td class="<?= str_contains($r['status'], '✅') ? 'ok' : (str_contains($r['status'], '❌') ? 'err' : 'info') ?>">
                    <?= $r['status'] ?>
                </td>
                <td><?= htmlspecialchars($r['msg']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="warn">
        ⚠️ <strong>PENTING:</strong><br>
        1. Edit data di tabel <code>wablas_bots</code> → ganti <code>token</code>, <code>secret_key</code>, dan <code>phone_notif</code> dengan data asli Anda.<br>
        2. Isi kolom <code>no_telp</code> di tabel <code>account_shop_tiktok</code> per akun toko.<br>
        3. <strong>HAPUS file ini</strong> setelah selesai: <code>rm public/migrate-webhook.php</code>
    </div>

    <h2>📌 Langkah Selanjutnya</h2>
    <p>Daftarkan webhook URL ke TikTok via SSH:</p>
    <pre style="background:#1a1a2e; padding:12px; border-radius:8px; overflow-x:auto; font-size:0.85rem;">
<code>cd ~/public_html/app.oleh2indonesia.com
php artisan tiktok:register-webhooks</code></pre>

    <p>Atau per akun tertentu:</p>
    <pre style="background:#1a1a2e; padding:12px; border-radius:8px; overflow-x:auto; font-size:0.85rem;">
<code>php artisan tiktok:register-webhooks --account=1</code></pre>

</body>

</html>