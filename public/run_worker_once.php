<?php
/**
 * Temporary worker runner (public/run_worker_once.php)
 * Usage: https://your-domain/run_worker_once.php?secret=YOUR_SECRET
 * - Requires PHP `shell_exec` or `proc_open` enabled on hosting.
 * - Writes output to storage/logs/worker_run.log
 * - Remove this file after use.
 */

$expected = getenv('STOCK_RUN_SECRET') ?: 'kiosq_stock_sync_2026';
$secret   = $_GET['secret'] ?? '';

if ($secret !== $expected) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$baseDir = dirname(__DIR__); // project root (public/..)
$artisan = escapeshellarg($baseDir . DIRECTORY_SEPARATOR . 'artisan');
$logFile = $baseDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'worker_run.log';
$cmd = "php $artisan queue:work --queue=shopee-inventory --max-jobs=1 --tries=2 >> " . escapeshellarg($logFile) . " 2>&1 & echo $!";

// Try shell_exec first
$pid = null;
if (function_exists('shell_exec')) {
    $pid = trim(shell_exec($cmd));
}

// Fallback to proc_open if shell_exec disabled
if (empty($pid) && function_exists('proc_open')) {
    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['file', $logFile, 'a'],
        2 => ['file', $logFile, 'a'],
    ];
    $process = proc_open("php $artisan queue:work --queue=shopee-inventory --max-jobs=1 --tries=2", $descriptorspec, $pipes, $baseDir);
    if (is_resource($process)) {
        $status = proc_get_status($process);
        $pid = $status['pid'] ?? null;
        // We won't close immediately to let it run; proc_close will wait — so we just echo pid and return
    }
}

if ($pid) {
    echo "Worker started (pid={$pid}). Check storage/logs/worker_run.log for output.\n";
    echo "Remove this file after use for security.\n";
} else {
    echo "Failed to start worker: shell_exec/proc_open not available or command failed.\n";
    echo "Check file permissions and enabled PHP functions.\n";
}
