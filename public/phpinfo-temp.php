<?php
// HAPUS file ini setelah dicek!
$projectPath = dirname(__DIR__);

// exec() dinonaktifkan di web â€” tidak apa-apa, cron tetap bisa jalan
// Pilih binary berdasarkan versi web PHP yang aktif (lsphp = PHP 8.3)
// Pada CloudLinux cPanel: /opt/alt/phpXX/usr/bin/php adalah CLI-nya
$webMajorMinor = PHP_MAJOR_VERSION . PHP_MINOR_VERSION; // contoh: 83
$candidates = [
    "/opt/alt/php{$webMajorMinor}/usr/bin/php",  // match versi web (paling tepat)
    '/usr/local/bin/php',
    '/usr/bin/php',
];

$chosen = null;
foreach ($candidates as $bin) {
    if (file_exists($bin)) {
        $chosen = $bin;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cron Setup</title>
    <style>
        body {
            font-family: monospace;
            background: #1a1a2e;
            color: #eee;
            padding: 24px;
        }

        h2 {
            color: #00d4ff;
        }

        h3 {
            color: #aaddff;
            margin-top: 28px;
        }

        .box {
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 14px 18px;
            margin: 10px 0;
        }

        .ok {
            color: #00ff88;
        }

        .warn {
            color: #ffcc00;
        }

        .err {
            color: #ff4444;
        }

        .copy {
            background: #161b22;
            border: 1px solid #444;
            border-radius: 4px;
            padding: 10px 14px;
            display: block;
            margin: 8px 0;
            word-break: break-all;
            font-size: 13px;
            color: #79c0ff;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
        }

        td,
        th {
            border: 1px solid #333;
            padding: 8px 14px;
        }

        th {
            background: #21262d;
            color: #58a6ff;
        }

        .step {
            background: #21262d;
            border-left: 4px solid #00d4ff;
            padding: 12px 16px;
            margin: 12px 0;
            border-radius: 0 6px 6px 0;
        }
    </style>
</head>

<body>

    <h2>âš™ï¸ Cron Job Setup â€” TikTok Stock Sync</h2>

    <h3>Info Server</h3>
    <div class="box">
        <b>Web PHP binary:</b> <?= PHP_BINARY ?><br>
        <b>Web PHP version:</b> <?= PHP_VERSION ?> (PHP <?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?>)<br>
        <b>Project path:</b> <?= htmlspecialchars($projectPath) ?><br>
        <b>exec() tersedia:</b> <?= function_exists('exec') ? '<span class="ok">Ya</span>' : '<span class="warn">Tidak (dinonaktifkan di web â€” normal, cron tetap bisa jalan)</span>' ?>
    </div>

    <h3>Binary PHP CLI yang Tersedia</h3>
    <table>
        <tr>
            <th>Binary</th>
            <th>Ada?</th>
            <th>Keterangan</th>
        </tr>
        <?php foreach ($candidates as $bin): ?>
            <tr>
                <td><?= htmlspecialchars($bin) ?></td>
                <td class="<?= file_exists($bin) ? 'ok' : 'err' ?>"><?= file_exists($bin) ? 'âœ… Ada' : 'âŒ Tidak ada' ?></td>
                <td><?= ($bin === $chosen) ? '<b class="ok">â† DIPILIH (match versi PHP web)</b>' : '' ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if ($chosen): ?>

        <h3>âœ… Perintah Cron â€” Siap Copy-Paste ke cPanel</h3>

        <div class="step">
            <b>Cron 1 â€” Queue Worker</b> (eksekusi job update stok ke TikTok, tiap menit)<br>
            <small class="warn">Kolom waktu di cPanel: semua set ke <b>*</b></small>
            <code class="copy">* * * * * cd <?= $projectPath ?> && <?= $chosen ?> artisan queue:work --queue=tiktok-inventory --stop-when-empty --max-time=55 >> /dev/null 2>&1</code>
        </div>

        <div class="step">
            <b>Cron 2 â€” Laravel Scheduler</b> (trigger sync otomatis tiap 30 menit)<br>
            <small class="warn">Kolom waktu di cPanel: semua set ke <b>*</b></small>
            <code class="copy">* * * * * cd <?= $projectPath ?> && <?= $chosen ?> artisan schedule:run >> /dev/null 2>&1</code>
        </div>

        <h3>Cara Pasang di cPanel</h3>
        <ol>
            <li>Login cPanel â†’ cari <b>Cron Jobs</b> â†’ klik</li>
            <li>Scroll ke <b>Add New Cron Job</b></li>
            <li>Semua kolom waktu (<i>Minute, Hour, Day, Month, Weekday</i>) isi <b>*</b></li>
            <li>Paste salah satu command di atas ke kolom <b>Command</b></li>
            <li>Klik <b>Add Line</b></li>
            <li>Ulangi untuk command ke-2</li>
            <li><b>Hapus file ini dari server setelah selesai!</b></li>
        </ol>

    <?php else: ?>
        <div class="err box">âŒ Tidak ada PHP CLI yang ditemukan. Hubungi support hosting dan tanyakan path PHP CLI untuk PHP 8.3.</div>
    <?php endif; ?>

    <div class="box warn">
        âš ï¸ <b>Setelah selesai setup cron, HAPUS file ini!</b><br>
        File ini menampilkan path server yang sebaiknya tidak publik.
    </div>

</body>

</html>
