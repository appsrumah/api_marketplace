<?php

/**
 * clear-cache.php — Hapus config/route/view cache Laravel
 * HAPUS file ini dari server setelah selesai!
 */
define('LARAVEL_ROOT', dirname(__DIR__));

$results = [];

// ── 1. Hapus manual file cache (tanpa bootstrap Laravel) ─────────────────────
$cacheFiles = [
    LARAVEL_ROOT . '/bootstrap/cache/config.php'  => 'Config cache',
    LARAVEL_ROOT . '/bootstrap/cache/routes-v7.php' => 'Route cache (v7)',
    LARAVEL_ROOT . '/bootstrap/cache/routes.php'  => 'Route cache',
    LARAVEL_ROOT . '/bootstrap/cache/events.php'  => 'Event cache',
];

foreach ($cacheFiles as $path => $label) {
    if (file_exists($path)) {
        if (@unlink($path)) {
            $results[] = ['ok' => true, 'msg' => "✅ {$label} dihapus: " . basename($path)];
        } else {
            $results[] = ['ok' => false, 'msg' => "❌ {$label} gagal dihapus (permission?): " . basename($path)];
        }
    } else {
        $results[] = ['ok' => null, 'msg' => "ℹ️ {$label} tidak ada (sudah bersih): " . basename($path)];
    }
}

// ── 2. Bootstrap Laravel dan jalankan artisan clear ───────────────────────────
$artisanResults = [];
try {
    require LARAVEL_ROOT . '/vendor/autoload.php';
    $app    = require LARAVEL_ROOT . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    $commands = [
        'config:clear'  => [],
        'cache:clear'   => [],
        'route:clear'   => [],
        'view:clear'    => [],
    ];

    foreach ($commands as $cmd => $args) {
        try {
            $exitCode = Illuminate\Support\Facades\Artisan::call($cmd, $args);
            $output   = trim(Illuminate\Support\Facades\Artisan::output());
            $artisanResults[] = [
                'ok'  => $exitCode === 0,
                'cmd' => $cmd,
                'out' => $output ?: '(OK)',
            ];
        } catch (\Throwable $e) {
            $artisanResults[] = [
                'ok'  => false,
                'cmd' => $cmd,
                'out' => 'ERROR: ' . $e->getMessage(),
            ];
        }
    }

    // Baca ulang config setelah clear
    $afterConfig = [
        'queue.default'    => config('queue.default'),
        'session.driver'   => config('session.driver'),
        'cache.default'    => config('cache.default'),
        'app.debug'        => config('app.debug') ? 'true' : 'false',
    ];
} catch (\Throwable $e) {
    $artisanResults[] = ['ok' => false, 'cmd' => 'bootstrap', 'out' => $e->getMessage()];
    $afterConfig = null;
}

// ── Cek sisa file cache ───────────────────────────────────────────────────────
$cacheDir = LARAVEL_ROOT . '/bootstrap/cache/';
$remaining = [];
foreach (glob($cacheDir . '*.php') as $f) {
    $remaining[] = basename($f);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Clear Cache — Laravel</title>
    <style>
        body {
            font-family: monospace;
            background: #0d1117;
            color: #c9d1d9;
            padding: 24px;
        }

        h2 {
            color: #58a6ff;
        }

        h3 {
            color: #79c0ff;
            margin-top: 28px;
            border-bottom: 1px solid #30363d;
            padding-bottom: 6px;
        }

        .ok {
            color: #3fb950;
            font-weight: bold;
        }

        .err {
            color: #f85149;
            font-weight: bold;
        }

        .inf {
            color: #8b949e;
        }

        .warn {
            color: #d29922;
            font-weight: bold;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin: 10px 0;
        }

        td,
        th {
            border: 1px solid #30363d;
            padding: 8px 14px;
        }

        th {
            background: #161b22;
            color: #58a6ff;
        }

        .box {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 12px 16px;
            margin: 10px 0;
        }

        .success {
            background: #0d2818;
            border-left: 4px solid #3fb950;
            padding: 12px 16px;
            margin: 10px 0;
            border-radius: 0 6px 6px 0;
        }

        .alert {
            background: #2d1c1c;
            border-left: 4px solid #f85149;
            padding: 12px 16px;
            margin: 10px 0;
            border-radius: 0 6px 6px 0;
        }
    </style>
</head>

<body>

    <h2>🧹 Clear Cache — Laravel</h2>
    <p class="warn">⚠️ HAPUS file ini dari server setelah selesai!</p>

    <h3>1. Hapus File Cache Manual</h3>
    <?php foreach ($results as $r): ?>
        <div class="<?= $r['ok'] === true ? 'ok' : ($r['ok'] === false ? 'err' : 'inf') ?>">
            <?= htmlspecialchars($r['msg']) ?>
        </div>
    <?php endforeach; ?>

    <h3>2. Artisan Cache Clear</h3>
    <table>
        <tr>
            <th>Command</th>
            <th>Status</th>
            <th>Output</th>
        </tr>
        <?php foreach ($artisanResults as $r): ?>
            <tr>
                <td>php artisan <?= htmlspecialchars($r['cmd']) ?></td>
                <td class="<?= $r['ok'] ? 'ok' : 'err' ?>"><?= $r['ok'] ? '✅' : '❌' ?></td>
                <td><?= htmlspecialchars($r['out']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h3>3. File Tersisa di bootstrap/cache/</h3>
    <div class="box">
        <?php foreach ($remaining as $f): ?>
            <span class="<?= in_array($f, ['packages.php', 'services.php']) ? 'ok' : 'warn' ?>"><?= htmlspecialchars($f) ?></span>&nbsp;&nbsp;
        <?php endforeach; ?>
        <?php if (empty($remaining)): ?>
            <span class="inf">(kosong)</span>
        <?php endif; ?>
        <br><small class="inf">Yang normal ada: packages.php dan services.php. Jika masih ada config.php → problem!</small>
    </div>

    <?php if ($afterConfig): ?>
        <h3>4. Config Laravel Setelah Clear (nilai seharusnya dari .env)</h3>
        <table>
            <tr>
                <th>Config Key</th>
                <th>Nilai</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>queue.default</td>
                <td><b><?= htmlspecialchars($afterConfig['queue.default']) ?></b></td>
                <td><?= $afterConfig['queue.default'] === 'database' ? '<span class="ok">✅ BENAR</span>' : '<span class="err">❌ Masih salah! Cek .env server</span>' ?></td>
            </tr>
            <tr>
                <td>session.driver</td>
                <td><?= htmlspecialchars($afterConfig['session.driver']) ?></td>
                <td class="ok">✅</td>
            </tr>
            <tr>
                <td>cache.default</td>
                <td><?= htmlspecialchars($afterConfig['cache.default']) ?></td>
                <td class="ok">✅</td>
            </tr>
            <tr>
                <td>app.debug</td>
                <td><?= htmlspecialchars($afterConfig['app.debug']) ?></td>
                <td class="ok">✅</td>
            </tr>
        </table>

        <?php if ($afterConfig['queue.default'] === 'database'): ?>
            <div class="success">
                ✅ <b>Config cache berhasil di-clear!</b><br>
                queue.default sekarang = <b>database</b> — Jobs akan masuk ke tabel jobs.<br>
                Selanjutnya: buka <a href="/migrate.php" style="color:#58a6ff">/migrate.php</a> untuk pastikan tabel sessions & cache ada,
                lalu coba <a href="/stock/sync-all" style="color:#58a6ff">/stock/sync-all</a>.
            </div>
        <?php else: ?>
            <div class="alert">
                ❌ queue.default masih <b><?= htmlspecialchars($afterConfig['queue.default']) ?></b>.<br>
                Pastikan di .env server: <b>QUEUE_CONNECTION=database</b> (bukan sync).
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <p class="warn" style="margin-top: 32px;">⚠️ Setelah selesai, HAPUS file ini dari server!</p>
</body>

</html>