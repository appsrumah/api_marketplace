<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
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
