<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TiktokAuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/* ═══════════════════════════════════════════════════════════════════════════
 | AUTH — Login / Register / Logout (publik, tidak perlu login)
 ═══════════════════════════════════════════════════════════════════════════ */

Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/* ═══════════════════════════════════════════════════════════════════════════
 | CRON — Publik, diamankan dengan secret key (tanpa sesi login)
 | Dipanggil oleh curl dari cPanel Cron Jobs, bukan oleh browser
 ═══════════════════════════════════════════════════════════════════════════ */
Route::prefix('stock')->name('stock.')->group(function () {
    Route::get('/cron-sync-all', [StockController::class, 'cronSyncAll'])->name('cron-sync-all');
    Route::get('/run-queue',     [StockController::class, 'runQueue'])->name('run-queue');
});

/* ═══════════════════════════════════════════════════════════════════════════
 | PROTECTED — Semua route di bawah wajib login & akun aktif
 ═══════════════════════════════════════════════════════════════════════════ */
Route::middleware(['auth', 'check.active'])->group(function () {

    /* ---------- Dashboard ---------- */
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /* ---------- Profil Pengguna ---------- */
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/',             [ProfileController::class, 'edit'])->name('edit');
        Route::put('/',             [ProfileController::class, 'update'])->name('update');
        Route::put('/password',     [ProfileController::class, 'updatePassword'])->name('password');
    });

    /* ---------- Manajemen Pengguna (Super Admin Only) ---------- */
    Route::prefix('users')->name('users.')->middleware('super_admin')->group(function () {
        Route::get('/',                           [UserController::class, 'index'])->name('index');
        Route::get('/create',                     [UserController::class, 'create'])->name('create');
        Route::post('/',                          [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit',                [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}',                     [UserController::class, 'update'])->name('update');
        Route::patch('/{user}/toggle-active',     [UserController::class, 'toggleActive'])->name('toggle-active');
        Route::delete('/{user}',                  [UserController::class, 'destroy'])->name('destroy');
    });

    /* ---------- TikTok Auth Flow ---------- */
    Route::prefix('tiktok')->name('tiktok.')->group(function () {
        Route::get('/connect',            [TiktokAuthController::class, 'redirect'])->name('connect');
        Route::get('/callback',           [TiktokAuthController::class, 'callback'])->name('callback');
        Route::post('/{account}/sync',    [TiktokAuthController::class, 'syncProducts'])->name('sync');
        Route::delete('/{account}',       [TiktokAuthController::class, 'destroy'])->name('destroy');
        Route::post('/internal-callback', [TiktokAuthController::class, 'internalCallback'])->name('internal-callback');
    });

    /* ---------- Products ---------- */
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');

    /* ---------- Stock Sync ---------- */
    Route::prefix('stock')->name('stock.')->group(function () {
        Route::get('/',               [StockController::class, 'dashboard'])->name('dashboard');
        Route::get('/sync-all',       [StockController::class, 'syncAll'])->name('sync-all');
        Route::post('/run-queue-web', [StockController::class, 'runQueueWeb'])->name('run-queue-web');
        Route::get('/{account}/sync', [StockController::class, 'syncAccount'])->name('sync-account');
        Route::post('/{account}/set-outlet', [StockController::class, 'setOutlet'])->name('set-outlet');

        // ── Testing Only ─────────────────────────────────────────────
        Route::get('/test',                          [StockController::class, 'testStock'])->name('test');
        Route::get('/test/{account}/pos-stock',      [StockController::class, 'testPosStock'])->name('test-pos');
        Route::get('/test/{account}/push-one',       [StockController::class, 'testPushOne'])->name('test-push-one');
    });
}); // end auth middleware group
