<?php
/**
 * clear-views.php — Hapus compiled view cache Laravel di production.
 * Gunakan saat view Blade diupdate tapi perubahan tidak terlihat.
 *
 * Upload ke: public_html/app.oleh2indonesia.com/public/clear-views.php
 * Buka SEKALI di browser, lalu HAPUS file ini setelah selesai.
 */

define('LARAVEL_ROOT', dirname(__DIR__));
require LARAVEL_ROOT . '/vendor/autoload.php';

$app    = require LARAVEL_ROOT . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$viewCachePath = storage_path('framework/views');
$deleted = 0;
$errors  = [];

if (is_dir($viewCachePath)) {
    foreach (glob($viewCachePath . '/*.php') as $file) {
        if (unlink($file)) {
            $deleted++;
        } else {
            $errors[] = basename($file);
        }
    }
}

header('Content-Type: text/plain; charset=utf-8');
echo "=== clear-views.php ===\n";
echo "Deleted : {$deleted} compiled view(s)\n";
if ($errors) {
    echo "Failed  : " . implode(', ', $errors) . "\n";
}
echo "Status  : " . ($errors ? 'PARTIAL' : 'OK') . "\n";
echo "\n⚠️  Hapus file ini dari server sekarang!\n";
