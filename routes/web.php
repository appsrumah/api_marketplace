<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductDetailController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopeeAuthController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TikTokWebhookController;
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

Route::prefix('orders')->name('orders.')->group(function () {
    Route::get('/cron-sync-all', [OrderController::class, 'cronSyncAll'])->name('cron-sync-all');
});

Route::get('/tiktok/cron-refresh-token', [TiktokAuthController::class, 'cronRefreshToken'])
    ->name('tiktok.cron-refresh-token');

/* ═══════════════════════════════════════════════════════════════════════════
 | WEBHOOKS — Endpoint untuk menerima notifikasi dari platform eksternal
 | Tanpa auth middleware — dipanggil oleh TikTok server
 ═══════════════════════════════════════════════════════════════════════════ */
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/tiktok/customer-service', [TikTokWebhookController::class, 'handle'])
        ->name('tiktok.customer-service');
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

    /* ---------- Shopee Auth Flow ---------- */
    Route::prefix('shopee')->name('shopee.')->group(function () {
        Route::get('/redirect',                          [ShopeeAuthController::class, 'redirect'])->name('redirect');
        Route::get('/callback',                          [ShopeeAuthController::class, 'callback'])->name('callback');
        Route::post('/accounts/{account}/refresh-token', [ShopeeAuthController::class, 'refreshToken'])->name('refresh-token');
        Route::delete('/accounts/{account}/disconnect',  [ShopeeAuthController::class, 'disconnect'])->name('disconnect');
    });

    /* ---------- TikTok Auth Flow ---------- */
    Route::prefix('tiktok')->name('tiktok.')->group(function () {
        Route::get('/connect',            [TiktokAuthController::class, 'redirect'])->name('connect');
        Route::get('/callback',           [TiktokAuthController::class, 'callback'])->name('callback');
        Route::post('/{account}/sync',    [TiktokAuthController::class, 'syncProducts'])->name('sync');
        Route::delete('/{account}',       [TiktokAuthController::class, 'destroy'])->name('destroy');
        Route::post('/internal-callback', [TiktokAuthController::class, 'internalCallback'])->name('internal-callback');
    });

    /* ---------- Pusat Integrasi Marketplace ---------- */
    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/',                        [IntegrationController::class, 'index'])->name('index');
        Route::get('/{account}',               [IntegrationController::class, 'show'])->name('show');
        Route::post('/{channel}/connect',      [IntegrationController::class, 'connect'])->name('connect');
        Route::put('/{account}/update',        [IntegrationController::class, 'update'])->name('update');
        Route::post('/{account}/refresh-token', [IntegrationController::class, 'refreshToken'])->name('refresh-token');
        Route::delete('/{account}/disconnect', [IntegrationController::class, 'disconnect'])->name('disconnect');
    });

    /* ---------- Products ---------- */
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    /* ---------- Product Detail (from TikTok API) ---------- */
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/{productId}/detail',       [ProductDetailController::class, 'show'])->name('detail');
        Route::get('/{productId}/edit',         [ProductDetailController::class, 'edit'])->name('edit');
        Route::put('/{productId}',              [ProductDetailController::class, 'update'])->name('update');
        Route::get('/test/{account}/{productId}', [ProductDetailController::class, 'testFetchOne'])->name('test-fetch');
    });

    /* ---------- Orders ---------- */
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/',                          [OrderController::class, 'index'])->name('index');
        Route::post('/push-all-pos',             [OrderController::class, 'pushAllToPos'])->name('push-all-pos');
        Route::get('/{order}',                   [OrderController::class, 'show'])->name('show');
        Route::post('/{order}/push-pos',         [OrderController::class, 'pushToPos'])->name('push-pos');
        Route::post('/{account}/sync',           [OrderController::class, 'syncOrders'])->name('sync');
        Route::get('/test/{account}',            [OrderController::class, 'testFetchOne'])->name('test-fetch');
    });

    /* ---------- Stock Sync ---------- */
    Route::prefix('stock')->name('stock.')->group(function () {
        Route::get('/',               [StockController::class, 'dashboard'])->name('dashboard');
        Route::get('/sync-all',       [StockController::class, 'syncAll'])->name('sync-all');
        Route::get('/sync-progress',  [StockController::class, 'syncProgress'])->name('sync-progress');
        Route::post('/clear-failed',  [StockController::class, 'clearFailed'])->name('clear-failed');
        Route::post('/run-queue-web', [StockController::class, 'runQueueWeb'])->name('run-queue-web');
        Route::get('/{account}/sync', [StockController::class, 'syncAccount'])->name('sync-account');
        Route::post('/{account}/set-outlet', [StockController::class, 'setOutlet'])->name('set-outlet');

        // ── Testing Only ─────────────────────────────────────────────
        Route::get('/test',                          [StockController::class, 'testStock'])->name('test');
        Route::get('/test/{account}/pos-stock',      [StockController::class, 'testPosStock'])->name('test-pos');
        Route::get('/test/{account}/push-one',       [StockController::class, 'testPushOne'])->name('test-push-one');
    });
}); // end auth middleware group
