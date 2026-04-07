<?php

/**
 * migrate-patch.php — Patch kolom yang hilang dari tabel activity_logs
 * Upload ke: public_html/.../public/migrate-patch.php
 * Buka sekali di browser, lalu HAPUS file ini setelah selesai.
 *
 * Masalah yang diperbaiki:
 * - Tabel activity_logs dibuat tanpa kolom: subject_type, subject_id,
 *   old_values, new_values, level → menyebabkan error saat insert log
 */

define('LARAVEL_ROOT', dirname(__DIR__));
require LARAVEL_ROOT . '/vendor/autoload.php';

$app    = require LARAVEL_ROOT . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$results = [];

// ─── Patch: activity_logs — add missing columns ──────────────────────────────
$missingColumns = [
    'subject_type' => "ADD COLUMN subject_type VARCHAR(255) NULL AFTER action",
    'subject_id'   => "ADD COLUMN subject_id BIGINT NULL AFTER subject_type",
    'old_values'   => "ADD COLUMN old_values JSON NULL AFTER description",
    'new_values'   => "ADD COLUMN new_values JSON NULL AFTER old_values",
    'level'        => "ADD COLUMN level VARCHAR(20) NOT NULL DEFAULT 'info' AFTER new_values",
];

if (!Schema::hasTable('activity_logs')) {
    $results[] = ['status' => '❌', 'msg' => 'Tabel activity_logs TIDAK ADA — jalankan migrate-v2-noseeder.php dulu!'];
} else {
    foreach ($missingColumns as $col => $ddl) {
        try {
            if (!Schema::hasColumn('activity_logs', $col)) {
                DB::statement("ALTER TABLE activity_logs {$ddl}");
                $results[] = ['status' => '✅', 'msg' => "Kolom activity_logs.{$col} berhasil ditambahkan"];
            } else {
                $results[] = ['status' => 'ℹ️', 'msg' => "Kolom activity_logs.{$col} sudah ada — dilewati"];
            }
        } catch (\Throwable $e) {
            $results[] = ['status' => '❌', 'msg' => "Gagal tambah activity_logs.{$col}: " . $e->getMessage()];
        }
    }

    // Tambah index level jika belum ada
    try {
        $indexes = DB::select("SHOW INDEX FROM activity_logs WHERE Key_name = 'idx_al_level'");
        if (empty($indexes)) {
            DB::statement("ALTER TABLE activity_logs ADD INDEX idx_al_level (level)");
            $results[] = ['status' => '✅', 'msg' => 'Index idx_al_level ditambahkan'];
        } else {
            $results[] = ['status' => 'ℹ️', 'msg' => 'Index idx_al_level sudah ada'];
        }
    } catch (\Throwable $e) {
        $results[] = ['status' => '❌', 'msg' => 'Gagal buat index level: ' . $e->getMessage()];
    }

    // Tambah index subject jika belum ada
    try {
        $indexes = DB::select("SHOW INDEX FROM activity_logs WHERE Key_name = 'idx_al_subject'");
        if (empty($indexes)) {
            DB::statement("ALTER TABLE activity_logs ADD INDEX idx_al_subject (subject_type, subject_id)");
            $results[] = ['status' => '✅', 'msg' => 'Index idx_al_subject ditambahkan'];
        } else {
            $results[] = ['status' => 'ℹ️', 'msg' => 'Index idx_al_subject sudah ada'];
        }
    } catch (\Throwable $e) {
        $results[] = ['status' => '❌', 'msg' => 'Gagal buat index subject: ' . $e->getMessage()];
    }
}

// ─── Verifikasi akhir ────────────────────────────────────────────────────────
$finalCols = [
    'subject_type',
    'subject_id',
    'old_values',
    'new_values',
    'level',
    'user_id',
    'action',
    'description',
    'ip_address',
    'user_agent',
    'created_at'
];

$checks = [];
if (Schema::hasTable('activity_logs')) {
    foreach ($finalCols as $col) {
        $checks["activity_logs.{$col}"] = Schema::hasColumn('activity_logs', $col);
    }
}

$failOps = count(array_filter($results, fn($r) => $r['status'] === '❌'));

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Patch — activity_logs</title>
    <style>
        body {
            font-family: 'Segoe UI', monospace;
            background: #0f172a;
            color: #e2e8f0;
            padding: 20px;
            max-width: 900px;
            margin: 0 auto;
        }

        h2 {
            color: #38bdf8;
            border-bottom: 1px solid #334155;
            padding-bottom: 10px;
        }

        h3 {
            color: #94a3b8;
            margin-top: 24px;
        }

        .ok {
            color: #4ade80;
            padding: 4px 0;
        }

        .err {
            color: #f87171;
            padding: 4px 0;
        }

        .inf {
            color: #94a3b8;
            padding: 4px 0;
        }

        table {
            border-collapse: collapse;
            margin-top: 12px;
            width: 100%;
        }

        td,
        th {
            border: 1px solid #334155;
            padding: 8px 14px;
        }

        th {
            background: #1e293b;
            color: #38bdf8;
            font-weight: 600;
        }

        tr:hover {
            background: #1e293b;
        }

        .warn {
            background: #422006;
            color: #fbbf24;
            padding: 12px 16px;
            border-left: 4px solid #f59e0b;
            margin: 16px 0;
            border-radius: 6px;
        }

        .success-box {
            background: #14532d;
            color: #86efac;
            padding: 12px 16px;
            border-left: 4px solid #16a34a;
            margin: 16px 0;
            border-radius: 6px;
        }

        .badge {
            display: inline-block;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
        }

        .badge-ok {
            background: #166534;
            color: #4ade80;
        }

        .badge-err {
            background: #7f1d1d;
            color: #fca5a5;
        }
    </style>
</head>

<body>
    <h2>🔧 Patch — activity_logs missing columns</h2>
    <p style="color:#64748b">Executed at: <?= date('Y-m-d H:i:s') ?></p>

    <?php if ($failOps === 0): ?>
        <div class="success-box">
            ✅ Semua operasi berhasil! Error "Unknown column 'subject_type'" sudah teratasi.
        </div>
    <?php else: ?>
        <div class="warn">
            ⚠️ Ada <?= $failOps ?> operasi yang gagal. Periksa pesan error di bawah.
        </div>
    <?php endif; ?>

    <h3>📋 Hasil Eksekusi</h3>
    <?php foreach ($results as $r): ?>
        <div class="<?= $r['status'] === '✅' ? 'ok' : ($r['status'] === '❌' ? 'err' : 'inf') ?>">
            <?= htmlspecialchars($r['status'] . ' ' . $r['msg']) ?>
        </div>
    <?php endforeach; ?>

    <?php if (!empty($checks)): ?>
        <h3>🔍 Verifikasi Kolom activity_logs</h3>
        <table>
            <tr>
                <th>Kolom</th>
                <th>Status</th>
            </tr>
            <?php foreach ($checks as $name => $exists): ?>
                <tr>
                    <td><?= htmlspecialchars($name) ?></td>
                    <td><span class="badge <?= $exists ? 'badge-ok' : 'badge-err' ?>"><?= $exists ? '✅ Ada' : '❌ TIDAK ADA' ?></span></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <div class="warn">
        ⚠️ Setelah selesai, <strong>HAPUS file ini</strong> dari server demi keamanan.
    </div>
</body>

</html>