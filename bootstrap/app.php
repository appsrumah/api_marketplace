<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Exclude route internal-callback dari CSRF
        $middleware->validateCsrfTokens(except: [
            'tiktok/internal-callback',            // external POST from callbacknew.php
            'stock/sync-all',                      // cron/external trigger update stok
            'stock/cron-sync-all',                 // curl cron trigger
            'stock/run-queue',                     // curl cron queue worker
            'webhooks/tiktok/customer-service',    // TikTok CS webhook (external POST)
        ]);

        // Alias middleware
        $middleware->alias([
            'super_admin'  => \App\Http\Middleware\EnsureSuperAdmin::class,
            'check.active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'permission'   => \App\Http\Middleware\RequirePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
