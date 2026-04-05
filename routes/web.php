<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TiktokAuthController;
use Illuminate\Support\Facades\Route;

/* ---------- Dashboard ---------- */

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

/* ---------- TikTok Auth Flow ---------- */
Route::prefix('tiktok')->name('tiktok.')->group(function () {
    Route::get('/connect', [TiktokAuthController::class, 'redirect'])->name('connect');
    Route::get('/callback', [TiktokAuthController::class, 'callback'])->name('callback');
    Route::post('/{account}/sync', [TiktokAuthController::class, 'syncProducts'])->name('sync');
    Route::delete('/{account}', [TiktokAuthController::class, 'destroy'])->name('destroy');
    Route::post('/internal-callback', [TiktokAuthController::class, 'internalCallback'])->name('internal-callback');
});

/* ---------- Products ---------- */
Route::get('/products', [ProductController::class, 'index'])->name('products.index');

/* ---------- Stock Sync ---------- */
Route::prefix('stock')->name('stock.')->group(function () {
    Route::get('/', [StockController::class, 'dashboard'])->name('dashboard');
    Route::get('/sync-all', [StockController::class, 'syncAll'])->name('sync-all');
    Route::get('/cron-sync-all', [StockController::class, 'cronSyncAll'])->name('cron-sync-all');
    Route::get('/run-queue', [StockController::class, 'runQueue'])->name('run-queue');
    Route::post('/run-queue-web', [StockController::class, 'runQueueWeb'])->name('run-queue-web');
    Route::get('/{account}/sync', [StockController::class, 'syncAccount'])->name('sync-account');
    Route::post('/{account}/set-outlet', [StockController::class, 'setOutlet'])->name('set-outlet');

    // ── Testing Only ──────────────────────────────────────────────
    Route::get('/test', [StockController::class, 'testStock'])->name('test');
    Route::get('/test/{account}/pos-stock', [StockController::class, 'testPosStock'])->name('test-pos');
    Route::get('/test/{account}/push-one', [StockController::class, 'testPushOne'])->name('test-push-one');
});
