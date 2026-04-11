<?php

/**
 * queue-check.php — Diagnostik Queue + Test Process Job
 *
 * Buka: https://app.oleh2indonesia.com/queue-check.php?secret=kiosq_stock_sync_2026
 * Test 1 job: https://app.oleh2indonesia.com/queue-check.php?secret=kiosq_stock_sync_2026&action=process
 *
 * HAPUS file ini setelah debugging selesai!
 */

$secret = $_GET['secret'] ?? '';
if ($secret !== 'kiosq_stock_sync_2026') {
    http_response_code(401);
    die('Unauthorized');
}

header('Content-Type: text/plain; charset=utf-8');
echo "=== QUEUE DIAGNOSTIK — " . date('Y-m-d H:i:s') . " ===\n\n";

// ── PHP Info ─────────────────────────────────────────────────────────────
echo "[PHP]\n";
echo "  Version       : " . PHP_VERSION . "\n";
echo "  Binary        : " . PHP_BINARY . "\n";
echo "  SAPI          : " . php_sapi_name() . "\n";
echo "  max_exec_time : " . ini_get('max_execution_time') . "s\n";
echo "  memory_limit  : " . ini_get('memory_limit') . "\n";
echo "  fastcgi_finish: " . (function_exists('fastcgi_finish_request') ? 'YES' : 'NO') . "\n";
echo "  litespeed_fin : " . (function_exists('litespeed_finish_request') ? 'YES' : 'NO') . "\n\n";

// ── Bootstrap Laravel ────────────────────────────────────────────────────
echo "[LARAVEL]\n";
try {
    require __DIR__ . '/../vendor/autoload.php';
    $app    = require __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "  Bootstrap : OK\n";
} catch (\Throwable $e) {
    die("  Bootstrap : FAILED — " . $e->getMessage() . "\n\n*** Fix ini dulu! ***\n");
}

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

echo "  Queue driver  : " . config('queue.default') . "\n";
echo "  retry_after   : " . config('queue.connections.database.retry_after') . "s\n";
echo "  sync_secret   : " . (config('app.stock_sync_secret') ? 'SET (' . strlen(config('app.stock_sync_secret')) . ' chars)' : 'NOT SET!') . "\n\n";

// ── Database ─────────────────────────────────────────────────────────────
echo "[DATABASE]\n";
try {
    echo "  Main DB : " . DB::connection()->getDatabaseName() . " — OK\n";
} catch (\Throwable $e) {
    echo "  Main DB : FAILED — " . $e->getMessage() . "\n";
}
try {
    echo "  POS DB  : " . DB::connection('pos')->getDatabaseName() . " — OK\n";
} catch (\Throwable $e) {
    echo "  POS DB  : FAILED — " . $e->getMessage() . "\n";
}
echo "\n";

// ── Jobs Table ───────────────────────────────────────────────────────────
echo "[JOBS]\n";
try {
    $now = now()->timestamp;
    $stats = [
        'available' => DB::table('jobs')->where('queue', 'tiktok-inventory')->whereNull('reserved_at')->where('available_at', '<=', $now)->count(),
        'delayed'   => DB::table('jobs')->where('queue', 'tiktok-inventory')->whereNull('reserved_at')->where('available_at', '>', $now)->count(),
        'reserved'  => DB::table('jobs')->where('queue', 'tiktok-inventory')->whereNotNull('reserved_at')->count(),
    ];
    $total = array_sum($stats);
    echo "  Available (siap proses) : {$stats['available']}\n";
    echo "  Delayed (backoff/retry) : {$stats['delayed']}\n";
    echo "  Reserved (sedang jalan) : {$stats['reserved']}\n";
    echo "  Total                   : {$total}\n";

    if ($total > 0) {
        echo "\n  First 3 jobs:\n";
        $sample = DB::table('jobs')->where('queue', 'tiktok-inventory')->orderBy('id')->limit(3)
            ->get(['id', 'attempts', 'reserved_at', 'available_at', 'created_at']);
        foreach ($sample as $j) {
            $resAt = $j->reserved_at ? date('Y-m-d H:i:s', $j->reserved_at) : 'NULL';
            echo "    #{$j->id} attempts={$j->attempts} reserved_at={$resAt} available=" . date('H:i:s', $j->available_at) . "\n";
        }
    }
} catch (\Throwable $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// ── Failed Jobs ──────────────────────────────────────────────────────────
echo "[FAILED JOBS]\n";
try {
    $failedCount = DB::table('failed_jobs')->where('queue', 'tiktok-inventory')->count();
    echo "  Total: {$failedCount}\n";
    if ($failedCount > 0) {
        $latest = DB::table('failed_jobs')->where('queue', 'tiktok-inventory')
            ->orderByDesc('failed_at')->first(['failed_at', 'exception']);
        echo "  Latest: " . $latest->failed_at . "\n";
        echo "  Error : " . mb_substr(explode("\n", $latest->exception)[0], 0, 200) . "\n";
    }
} catch (\Throwable $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// ── Cache Progress ───────────────────────────────────────────────────────
echo "[CACHE PROGRESS]\n";
try {
    $accounts = DB::table('account_shop_tiktoks')->whereNotNull('id_outlet')->pluck('seller_name', 'id');
    $any = false;
    foreach ($accounts as $id => $name) {
        $p = Cache::get("stock_sync_progress_{$id}");
        if ($p) {
            $any = true;
            echo "  [{$id}] {$name}: {$p['status']} ({$p['current']}/{$p['total']})\n";
        }
    }
    if (!$any) echo "  (kosong — belum ada job yang pernah berjalan)\n";
} catch (\Throwable $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// ── ACTION: Process 1 Job ────────────────────────────────────────────────
$action = $_GET['action'] ?? '';
if ($action === 'process') {
    echo "========================================\n";
    echo "  PROCESSING 1 JOB NOW...\n";
    echo "========================================\n\n";

    ignore_user_abort(true);
    set_time_limit(600);

    try {
        $before = DB::table('jobs')->where('queue', 'tiktok-inventory')->count();
        $start  = microtime(true);

        $exitCode = Artisan::call('queue:work', [
            '--queue'    => 'tiktok-inventory',
            '--max-jobs' => 1,
            '--tries'    => 2,
            '--timeout'  => 540,
            '--memory'   => 256,
        ]);

        $output  = trim(Artisan::output());
        $after   = DB::table('jobs')->where('queue', 'tiktok-inventory')->count();
        $elapsed = round(microtime(true) - $start, 1);

        echo "  Exit code  : {$exitCode}\n";
        echo "  Before     : {$before} jobs\n";
        echo "  After      : {$after} jobs\n";
        echo "  Elapsed    : {$elapsed}s\n";
        echo "  Output     :\n---\n{$output}\n---\n\n";

        if ($before === $after) {
            echo "  ⚠ Job count tidak berubah! Kemungkinan:\n";
            echo "    - Job gagal dan akan di-retry (cek failed_jobs)\n";
            echo "    - Job stuck (reserved_at di-set tapi proses mati)\n";
        } else {
            echo "  ✅ Berhasil memproses " . ($before - $after) . " job!\n";
        }

        Log::info("queue-check.php: processed", compact('exitCode', 'elapsed', 'after', 'output'));
    } catch (\Throwable $e) {
        echo "  ❌ ERROR: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "  Trace: " . mb_substr($e->getTraceAsString(), 0, 500) . "\n";
    }
} elseif ($action === 'clear-stuck') {
    echo "========================================\n";
    echo "  CLEARING STUCK JOBS...\n";
    echo "========================================\n\n";

    try {
        $released = DB::table('jobs')
            ->where('queue', 'tiktok-inventory')
            ->whereNotNull('reserved_at')
            ->update(['reserved_at' => null, 'attempts' => 0]);
        echo "  Released: {$released} stuck jobs\n";
    } catch (\Throwable $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
    }
} elseif ($action === 'flush') {
    echo "========================================\n";
    echo "  FLUSHING ALL JOBS + RE-DISPATCH...\n";
    echo "========================================\n\n";

    try {
        $deleted = DB::table('jobs')->where('queue', 'tiktok-inventory')->delete();
        echo "  Deleted: {$deleted} jobs\n";

        // Clear cache progress
        $accounts = DB::table('account_shop_tiktoks')->pluck('id');
        foreach ($accounts as $id) {
            Cache::forget("stock_sync_progress_{$id}");
        }
        echo "  Cache cleared\n\n";
        echo "  Sekarang klik 'Sync Semua Akun' di dashboard untuk dispatch ulang.\n";
    } catch (\Throwable $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "[ACTIONS]\n";
    echo "  ?action=process     → Test proses 1 job (akan menampilkan output & waktu)\n";
    echo "  ?action=clear-stuck → Lepas semua stuck jobs (reserved_at → NULL)\n";
    echo "  ?action=flush       → Hapus SEMUA jobs (untuk mulai bersih)\n";
}

echo "\n=== END ===\n";
