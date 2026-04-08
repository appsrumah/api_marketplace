<?php

/**
 * seed-master.php — Insert data master: roles, permissions, marketplace channels,
 *                   warehouse default, system settings, dan mapping role_id user.
 *
 * Upload ke: public_html/.../public/seed-master.php
 * Buka sekali di browser, lalu HAPUS file ini setelah selesai.
 *
 * ⚠️ Semua INSERT dilindungi pengecekan count() = 0 → aman dijalankan ulang.
 * ⚠️ Khusus mapping role_id: hanya mengisi user yang role_id-nya masih NULL.
 */

define('LARAVEL_ROOT', dirname(__DIR__));
require LARAVEL_ROOT . '/vendor/autoload.php';

$app    = require LARAVEL_ROOT . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$results = [];

// ═══════════════════════════════════════════════════════════════════════════════
// 1. ROLES
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('roles')) {
        $results[] = ['status' => '❌', 'msg' => 'Tabel roles tidak ada — jalankan migrate-v2-noseeder.php dulu!'];
    } elseif (DB::table('roles')->count() === 0) {
        $now = now();
        $roles = [
            ['name' => 'super_admin', 'label' => 'Super Admin',      'level' => 100],
            ['name' => 'admin',       'label' => 'Admin',            'level' => 80],
            ['name' => 'manager',     'label' => 'Manager',          'level' => 60],
            ['name' => 'staff_admin', 'label' => 'Staff Admin',      'level' => 40],
            ['name' => 'finance',     'label' => 'Finance',          'level' => 35],
            ['name' => 'cs',          'label' => 'Customer Service', 'level' => 25],
            ['name' => 'operator',    'label' => 'Operator',         'level' => 20],
        ];
        foreach ($roles as $r) {
            DB::table('roles')->insert(array_merge($r, ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now]));
        }
        $results[] = ['status' => '✅', 'msg' => 'Seeded ' . count($roles) . ' roles'];
    } else {
        $count = DB::table('roles')->count();
        $results[] = ['status' => 'ℹ️', 'msg' => "Roles sudah terisi ({$count} data) — dilewati"];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal seed roles: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 2. PERMISSIONS
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('permissions')) {
        $results[] = ['status' => '❌', 'msg' => 'Tabel permissions tidak ada!'];
    } elseif (DB::table('permissions')->count() === 0) {
        $now = now();
        $permissions = [
            // dashboard
            ['name' => 'dashboard.view',       'label' => 'Lihat Dashboard',        'group' => 'dashboard'],
            // users
            ['name' => 'users.view',            'label' => 'Lihat Pengguna',         'group' => 'users'],
            ['name' => 'users.create',          'label' => 'Tambah Pengguna',        'group' => 'users'],
            ['name' => 'users.edit',            'label' => 'Edit Pengguna',          'group' => 'users'],
            ['name' => 'users.delete',          'label' => 'Hapus Pengguna',         'group' => 'users'],
            // products
            ['name' => 'products.view',         'label' => 'Lihat Produk',           'group' => 'products'],
            ['name' => 'products.create',       'label' => 'Tambah Produk',          'group' => 'products'],
            ['name' => 'products.edit',         'label' => 'Edit Produk',            'group' => 'products'],
            ['name' => 'products.delete',       'label' => 'Hapus Produk',           'group' => 'products'],
            ['name' => 'products.sync',         'label' => 'Sinkron Produk',         'group' => 'products'],
            // orders
            ['name' => 'orders.view',           'label' => 'Lihat Pesanan',          'group' => 'orders'],
            ['name' => 'orders.process',        'label' => 'Proses Pesanan',         'group' => 'orders'],
            ['name' => 'orders.cancel',         'label' => 'Batalkan Pesanan',       'group' => 'orders'],
            ['name' => 'orders.sync',           'label' => 'Sinkron Pesanan',        'group' => 'orders'],
            // stock
            ['name' => 'stock.view',            'label' => 'Lihat Stok',             'group' => 'stock'],
            ['name' => 'stock.sync',            'label' => 'Sinkron Stok',           'group' => 'stock'],
            ['name' => 'stock.manage',          'label' => 'Kelola Stok',            'group' => 'stock'],
            // channels
            ['name' => 'channels.view',         'label' => 'Lihat Channel',          'group' => 'channels'],
            ['name' => 'channels.manage',       'label' => 'Kelola Channel',         'group' => 'channels'],
            ['name' => 'channels.connect',      'label' => 'Hubungkan Channel',      'group' => 'channels'],
            ['name' => 'channels.disconnect',   'label' => 'Putuskan Channel',       'group' => 'channels'],
            // warehouses
            ['name' => 'warehouses.view',       'label' => 'Lihat Gudang',           'group' => 'warehouses'],
            ['name' => 'warehouses.manage',     'label' => 'Kelola Gudang',          'group' => 'warehouses'],
            // reports
            ['name' => 'reports.view',          'label' => 'Lihat Laporan',          'group' => 'reports'],
            ['name' => 'reports.export',        'label' => 'Export Laporan',         'group' => 'reports'],
            // settings
            ['name' => 'settings.view',         'label' => 'Lihat Pengaturan',       'group' => 'settings'],
            ['name' => 'settings.edit',         'label' => 'Edit Pengaturan',        'group' => 'settings'],
            ['name' => 'activity_logs.view',    'label' => 'Lihat Log Aktivitas',    'group' => 'settings'],
            ['name' => 'activity_logs.clear',   'label' => 'Hapus Log Aktivitas',    'group' => 'settings'],
        ];
        foreach ($permissions as $p) {
            DB::table('permissions')->insert(array_merge($p, ['created_at' => $now, 'updated_at' => $now]));
        }
        $results[] = ['status' => '✅', 'msg' => 'Seeded ' . count($permissions) . ' permissions'];
    } else {
        $count = DB::table('permissions')->count();
        $results[] = ['status' => 'ℹ️', 'msg' => "Permissions sudah terisi ({$count} data) — dilewati"];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal seed permissions: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 3. MARKETPLACE CHANNELS
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('marketplace_channels')) {
        $results[] = ['status' => '❌', 'msg' => 'Tabel marketplace_channels tidak ada!'];
    } elseif (DB::table('marketplace_channels')->count() === 0) {
        $now = now();
        $channels = [
            ['code' => 'TIKTOK',    'name' => 'TikTok Shop',          'color' => '#000000', 'sort_order' => 1],
            ['code' => 'SHOPEE',    'name' => 'Shopee',               'color' => '#EE4D2D', 'sort_order' => 2],
            ['code' => 'TOKOPEDIA', 'name' => 'Tokopedia',            'color' => '#42B549', 'sort_order' => 3],
            ['code' => 'LAZADA',    'name' => 'Lazada',               'color' => '#0F146D', 'sort_order' => 4],
            ['code' => 'BUKALAPAK', 'name' => 'Bukalapak',            'color' => '#E31E52', 'sort_order' => 5],
            ['code' => 'BLIBLI',    'name' => 'Blibli',               'color' => '#0095DA', 'sort_order' => 6],
            ['code' => 'WEBSITE',   'name' => 'Website / WooCommerce', 'color' => '#96588A', 'sort_order' => 7],
            ['code' => 'OFFLINE',   'name' => 'Offline / POS',        'color' => '#6b7280', 'sort_order' => 8],
        ];
        foreach ($channels as $c) {
            DB::table('marketplace_channels')->insert(array_merge($c, ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now]));
        }
        $results[] = ['status' => '✅', 'msg' => 'Seeded ' . count($channels) . ' marketplace channels'];
    } else {
        $count = DB::table('marketplace_channels')->count();
        $results[] = ['status' => 'ℹ️', 'msg' => "Marketplace channels sudah terisi ({$count} data) — dilewati"];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal seed marketplace channels: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 4. DEFAULT WAREHOUSE
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('warehouses')) {
        $results[] = ['status' => '❌', 'msg' => 'Tabel warehouses tidak ada!'];
    } elseif (DB::table('warehouses')->count() === 0) {
        DB::table('warehouses')->insert([
            'name'       => 'Gudang Utama',
            'code'       => 'WH-MAIN',
            'is_default' => 1,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $results[] = ['status' => '✅', 'msg' => 'Seeded 1 default warehouse (Gudang Utama / WH-MAIN)'];
    } else {
        $count = DB::table('warehouses')->count();
        $results[] = ['status' => 'ℹ️', 'msg' => "Warehouses sudah terisi ({$count} data) — dilewati"];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal seed warehouse: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 5. SYSTEM SETTINGS
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('system_settings')) {
        $results[] = ['status' => '❌', 'msg' => 'Tabel system_settings tidak ada!'];
    } elseif (DB::table('system_settings')->count() === 0) {
        $now = now();
        $settings = [
            ['key' => 'app_name',             'value' => 'Kios Q Omni-Channel',  'type' => 'string',  'group' => 'general', 'label' => 'Nama Aplikasi'],
            ['key' => 'default_currency',     'value' => 'IDR',                  'type' => 'string',  'group' => 'general', 'label' => 'Mata Uang Default'],
            ['key' => 'sync_interval_minutes', 'value' => '30',                   'type' => 'integer', 'group' => 'sync',    'label' => 'Interval Sinkronisasi (menit)'],
            ['key' => 'auto_sync_stock',      'value' => '1',                    'type' => 'boolean', 'group' => 'sync',    'label' => 'Auto Sinkron Stok'],
            ['key' => 'auto_sync_orders',     'value' => '1',                    'type' => 'boolean', 'group' => 'sync',    'label' => 'Auto Sinkron Pesanan'],
            ['key' => 'stock_buffer',         'value' => '0',                    'type' => 'integer', 'group' => 'stock',   'label' => 'Buffer Stok'],
            ['key' => 'low_stock_threshold',  'value' => '5',                    'type' => 'integer', 'group' => 'stock',   'label' => 'Batas Stok Rendah'],
        ];
        foreach ($settings as $s) {
            DB::table('system_settings')->insert(array_merge($s, ['created_at' => $now, 'updated_at' => $now]));
        }
        $results[] = ['status' => '✅', 'msg' => 'Seeded ' . count($settings) . ' system settings'];
    } else {
        $count = DB::table('system_settings')->count();
        $results[] = ['status' => 'ℹ️', 'msg' => "System settings sudah terisi ({$count} data) — dilewati"];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal seed system settings: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 6. MAPPING users.role → role_id  (hanya user yang belum punya role_id)
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('roles') || !Schema::hasColumn('users', 'role_id')) {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Skip mapping role_id — tabel roles atau kolom role_id belum ada'];
    } else {
        $usersWithoutRoleId = DB::table('users')->whereNull('role_id')->count();
        if ($usersWithoutRoleId > 0) {
            $roleMap = DB::table('roles')->pluck('id', 'name');
            $updated = 0;
            foreach ($roleMap as $name => $id) {
                $count = DB::table('users')
                    ->where('role', $name)
                    ->whereNull('role_id')
                    ->update(['role_id' => $id]);
                $updated += $count;
            }
            $results[] = ['status' => '✅', 'msg' => "Mapped role_id untuk {$updated} user (dari kolom 'role')"];
        } else {
            $results[] = ['status' => 'ℹ️', 'msg' => 'Semua users sudah punya role_id — dilewati'];
        }
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal mapping role_id: ' . $e->getMessage()];
}

// ─── Statistik ──────────────────────────────────────────────────────────────
$successOps = count(array_filter($results, fn($r) => $r['status'] === '✅'));
$skipOps    = count(array_filter($results, fn($r) => $r['status'] === 'ℹ️'));
$failOps    = count(array_filter($results, fn($r) => $r['status'] === '❌'));

// ─── Ringkasan data yang terseed ─────────────────────────────────────────────
$summary = [];
$tables  = ['roles', 'permissions', 'marketplace_channels', 'warehouses', 'system_settings'];
foreach ($tables as $tbl) {
    try {
        $summary[$tbl] = Schema::hasTable($tbl) ? DB::table($tbl)->count() : 'N/A';
    } catch (\Throwable $e) {
        $summary[$tbl] = 'Error';
    }
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Seed Master Data</title>
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

        .stats {
            display: flex;
            gap: 16px;
            margin: 16px 0;
        }

        .stat-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 12px 20px;
            text-align: center;
            flex: 1;
        }

        .stat-card .number {
            font-size: 28px;
            font-weight: 700;
        }

        .stat-card .label {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 4px;
        }

        .stat-ok .number {
            color: #4ade80;
        }

        .stat-skip .number {
            color: #94a3b8;
        }

        .stat-err .number {
            color: #f87171;
        }

        .badge {
            display: inline-block;
            font-size: 13px;
            padding: 3px 10px;
            border-radius: 12px;
            background: #1e293b;
            color: #38bdf8;
            border: 1px solid #334155;
        }
    </style>
</head>

<body>
    <h2>🌱 Seed Master Data — Kios Q Omni-Channel</h2>
    <p style="color:#64748b">Executed at: <?= date('Y-m-d H:i:s') ?></p>

    <?php if ($failOps === 0 && $successOps > 0): ?>
        <div class="success-box">
            ✅ Seeding berhasil! Data master sudah tersedia di database.
        </div>
    <?php elseif ($failOps === 0 && $successOps === 0): ?>
        <div class="success-box">
            ℹ️ Semua data sudah ada sebelumnya — tidak ada yang perlu di-seed.
        </div>
    <?php else: ?>
        <div class="warn">
            ⚠️ Ada <?= $failOps ?> seeder yang gagal. Pastikan migration sudah dijalankan.
        </div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat-card stat-ok">
            <div class="number"><?= $successOps ?></div>
            <div class="label">✅ Berhasil</div>
        </div>
        <div class="stat-card stat-skip">
            <div class="number"><?= $skipOps ?></div>
            <div class="label">ℹ️ Dilewati</div>
        </div>
        <div class="stat-card stat-err">
            <div class="number"><?= $failOps ?></div>
            <div class="label">❌ Gagal</div>
        </div>
    </div>

    <h3>📋 Hasil Seeding</h3>
    <?php foreach ($results as $r): ?>
        <div class="<?= $r['status'] === '✅' ? 'ok' : ($r['status'] === '❌' ? 'err' : 'inf') ?>">
            <?= htmlspecialchars($r['status'] . ' ' . $r['msg']) ?>
        </div>
    <?php endforeach; ?>

    <h3>📊 Jumlah Data di Database</h3>
    <table>
        <tr>
            <th>Tabel</th>
            <th>Jumlah Baris</th>
        </tr>
        <?php foreach ($summary as $tbl => $count): ?>
            <tr>
                <td><?= htmlspecialchars($tbl) ?></td>
                <td><span class="badge"><?= htmlspecialchars((string)$count) ?></span></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td>users</td>
            <td><span class="badge"><?= Schema::hasTable('users') ? DB::table('users')->count() : 'N/A' ?></span></td>
        </tr>
    </table>

    <div class="warn">
        ⚠️ Setelah selesai, <strong>HAPUS file ini</strong> dari server demi keamanan.
    </div>
</body>

</html>