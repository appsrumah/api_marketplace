<?php

/**
 * Jalankan migration: stock_sync_logs
 * URL: https://app.oleh2indonesia.com/migrate-stock-sync-logs.php
 *
 * ⚠ HAPUS FILE INI setelah berhasil dijalankan!
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "<h2>🔧 Migration: stock_sync_logs</h2><pre>";

try {
    \Illuminate\Support\Facades\Artisan::call('migrate', [
        '--path'  => 'database/migrations/2026_04_15_100001_create_stock_sync_logs_table.php',
        '--force' => true,
    ]);
    echo \Illuminate\Support\Facades\Artisan::output();
    echo "\n✅ Migration berhasil!";
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine();
}

echo "</pre>";
echo "<p style='color:red;font-weight:bold'>⚠ HAPUS FILE INI setelah berhasil!</p>";
