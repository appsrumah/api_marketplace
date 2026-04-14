<?php

/**
 * migrate-telp-notif.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Tambah kolom `telp_notif` ke tabel `account_shop_tiktok`.
 * Kolom ini digunakan untuk menyimpan nomor WhatsApp yang menerima
 * notifikasi order baru via Wablas (bisa multi nomor, pisah koma).
 *
 * Cara pakai:
 *   1. Upload file ini ke folder public/ di server
 *   2. Buka di browser: https://yourdomain.com/migrate-telp-notif.php
 *   3. HAPUS file ini setelah berhasil dijalankan
 * ─────────────────────────────────────────────────────────────────────────────
 */

define('LARAVEL_ROOT', dirname(__DIR__));
require LARAVEL_ROOT . '/vendor/autoload.php';

$app    = require LARAVEL_ROOT . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$results = [];

// ─── 1. Tambah kolom telp_notif ke account_shop_tiktok ───────────────────────
try {
    if (!Schema::hasColumn('account_shop_tiktok', 'telp_notif')) {
        DB::statement("
            ALTER TABLE account_shop_tiktok
            ADD COLUMN telp_notif VARCHAR(255) NULL
                AFTER id_outlet
                COMMENT 'Nomor WA notifikasi order baru (pisah koma untuk multi nomor)'
        ");
        $results[] = ['status' => '✅', 'msg' => 'Kolom <b>telp_notif</b> berhasil ditambahkan ke tabel <b>account_shop_tiktok</b>'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Kolom <b>telp_notif</b> sudah ada — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal tambah kolom telp_notif: ' . htmlspecialchars($e->getMessage())];
}

// ─── 2. Catat di tabel migrations Laravel ────────────────────────────────────
$migrationName = '2026_04_14_200001_add_telp_notif_to_account_shop_tiktok';
try {
    if (!DB::table('migrations')->where('migration', $migrationName)->exists()) {
        $batch = (int) DB::table('migrations')->max('batch') + 1;
        DB::table('migrations')->insert([
            'migration' => $migrationName,
            'batch'     => $batch,
        ]);
        $results[] = ['status' => '✅', 'msg' => "Migration <b>{$migrationName}</b> dicatat di tabel migrations (batch {$batch})"];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => "Migration <b>{$migrationName}</b> sudah tercatat — dilewati"];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '⚠️', 'msg' => 'Kolom berhasil dibuat tapi gagal catat migrations: ' . htmlspecialchars($e->getMessage())];
}

// ─── Verifikasi akhir ─────────────────────────────────────────────────────────
try {
    $hasCol = Schema::hasColumn('account_shop_tiktok', 'telp_notif');
    $results[] = [
        'status' => $hasCol ? '✅' : '❌',
        'msg'    => 'Verifikasi: kolom telp_notif ' . ($hasCol ? '<b>ADA</b> di tabel' : '<b>TIDAK ADA</b> — ada masalah'),
    ];
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Verifikasi gagal: ' . htmlspecialchars($e->getMessage())];
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrate: telp_notif</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f3f4f6;
            padding: 32px 16px;
            color: #1f2937;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
            max-width: 680px;
            margin: 0 auto;
            padding: 32px;
        }

        h1 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .subtitle {
            color: #6b7280;
            font-size: .9rem;
            margin-bottom: 24px;
        }

        .result {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: .92rem;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }

        .result.ok {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .result.err {
            background: #fef2f2;
            border-color: #fecaca;
        }

        .result.info {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .result.warn {
            background: #fffbeb;
            border-color: #fde68a;
        }

        .icon {
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .warning-box {
            margin-top: 24px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 14px 16px;
            font-size: .88rem;
            color: #92400e;
        }

        .warning-box strong {
            display: block;
            margin-bottom: 4px;
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>🗄️ Migrate: <code>telp_notif</code></h1>
        <p class="subtitle">Tambah kolom nomor WhatsApp notifikasi order baru ke tabel <code>account_shop_tiktok</code></p>

        <?php foreach ($results as $r): ?>
            <?php
            $cls = 'info';
            if (str_starts_with($r['status'], '✅')) $cls = 'ok';
            if (str_starts_with($r['status'], '❌')) $cls = 'err';
            if (str_starts_with($r['status'], '⚠')) $cls = 'warn';
            ?>
            <div class="result <?= $cls ?>">
                <span class="icon"><?= $r['status'] ?></span>
                <span><?= $r['msg'] ?></span>
            </div>
        <?php endforeach; ?>

        <div class="warning-box">
            <strong>⚠️ Penting — Hapus file ini setelah selesai!</strong>
            File ini memberikan akses langsung ke database. Segera hapus
            <code>public/migrate-telp-notif.php</code> dari server setelah berhasil dijalankan.
        </div>
    </div>
</body>

</html>