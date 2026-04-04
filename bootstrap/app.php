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
            'tiktok/internal-callback', // external POST from callbacknew.php
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
