<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 |----------------------------------------------------------------------
 | TikTok Stock Sync Schedule
 |----------------------------------------------------------------------
 | Jadwal: setiap 30 menit.
 | Pastikan cron server sudah menjalankan:
 |   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
 |
 | Dan queue worker sudah jalan:
 |   php artisan queue:work --queue=tiktok-inventory --sleep=3 --tries=3
 */
Schedule::command('tiktok:sync-stock')
    ->everyThirtyMinutes()
    ->withoutOverlapping()   // cegah overlap jika ada banyak produk
    ->runInBackground()      // tidak block scheduler lain
    ->appendOutputTo(storage_path('logs/stock-sync.log'));
