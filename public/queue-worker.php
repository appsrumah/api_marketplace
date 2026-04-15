<?php

/**
 * queue-worker.php — Standalone Queue Worker via HTTP
 *
 * KENAPA FILE INI DIBUAT?
 * ======================
 * Pada shared hosting (cPanel CloudLinux), cron `php artisan queue:work`
 * sering GAGAL karena `php` di cron PATH bukan PHP 8.3.
 * Solusi: panggil file ini via curl (sama seperti cron-sync-all & order sync).
 *
 * CARA KERJA:
 * 1. Curl memanggil file ini
 * 2. File ini langsung kirim response "OK" ke curl (< 1 detik)
 * 3. PHP tetap berjalan di background via fastcgi_finish_request()
 * 4. Proses 1 job dari queue tiktok-inventory
 * 5. Selesai → proses PHP mati
 *
 * CRON (tiap menit) — 1 baris untuk TikTok + Shopee sekaligus:
 * * * * * curl -s "https://app.oleh2indonesia.com/queue-worker.php?secret=kiosq_stock_sync_2026" > /dev/null 2>&1
 *
 * KEAMANAN: Dilindungi oleh secret key.
 */

// ── 0. Auth ──────────────────────────────────────────────────────────────
$secret = $_GET['secret'] ?? '';
if ($secret !== 'kiosq_stock_sync_2026') {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'Unauthorized']);
    exit;
}

// ── 1. Buat PHP tetap hidup meskipun curl disconnect ─────────────────────
ignore_user_abort(true);
set_time_limit(600); // 10 menit max

// ── 2. Kirim response ke curl SEGERA → curl selesai dalam < 1 detik ─────
header('Content-Type: application/json');
$startResponse = json_encode([
    'status' => 'started',
    'time'   => date('Y-m-d H:i:s'),
    'info'   => 'Worker dimulai di background. Cek Live Monitor untuk progress.',
]);

// Flush output ke client
echo $startResponse;

// Tutup koneksi ke client — PHP tetap jalan di background
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    // Fallback untuk non-FPM
    if (ob_get_level() > 0) ob_end_flush();
    flush();
    if (function_exists('litespeed_finish_request')) {
        litespeed_finish_request(); // LiteSpeed (umum di cPanel)
    }
}

// ── 3. Bootstrap Laravel ─────────────────────────────────────────────────
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app    = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// ── 4. Cek apakah ada job di queue ───────────────────────────────────────
try {
    $jobCount = DB::table('jobs')
        ->whereIn('queue', ['tiktok-inventory', 'shopee-inventory'])
        ->whereNull('reserved_at')
        ->count();

    if ($jobCount === 0) {
        Log::debug('queue-worker.php: Queue kosong (tiktok + shopee), tidak ada yang diproses.');
        exit;
    }

    Log::info("queue-worker.php: {$jobCount} job menunggu (tiktok+shopee). Memproses 1 job...");
} catch (\Throwable $e) {
    Log::error('queue-worker.php: Gagal cek jobs table: ' . $e->getMessage());
    exit;
}

// ── 5. Proses 1 job ─────────────────────────────────────────────────────
try {
    $exitCode = Artisan::call('queue:work', [
        '--queue'    => 'tiktok-inventory,shopee-inventory', // proses keduanya, TikTok diprioritaskan
        '--max-jobs' => 1,       // 1 job per panggilan — aman untuk timeout
        '--tries'    => 2,
        '--timeout'  => 540,     // 9 menit max per job (di bawah set_time_limit)
        '--memory'   => 256,     // MB — shared hosting biasanya 256-512
    ]);

    $output    = trim(Artisan::output());
    $remaining = DB::table('jobs')->whereIn('queue', ['tiktok-inventory', 'shopee-inventory'])->count();
    $elapsed   = round(microtime(true) - LARAVEL_START, 1);

    Log::info("queue-worker.php: Selesai dalam {$elapsed}s", [
        'exit_code'      => $exitCode,
        'jobs_remaining' => $remaining,
        'output'         => $output ?: '(kosong)',
    ]);
} catch (\Throwable $e) {
    Log::error('queue-worker.php: ERROR — ' . $e->getMessage(), [
        'file' => $e->getFile() . ':' . $e->getLine(),
    ]);
}
