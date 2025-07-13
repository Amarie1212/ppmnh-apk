<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
        $middleware->alias([
        'role' => \App\Http\Middleware\Role::class,

         ]);
        $middleware->api(append: [
            HandleCors::class, // <-- Tambahkan middleware CORS bawaan Laravel
            // Jika Anda menggunakan Laravel Sanctum untuk autentikasi API SPA (seperti Ionic):
            EnsureFrontendRequestsAreStateful::class, // <-- Tambahkan ini jika pakai Sanctum
            'throttle:api', // Middleware throttling API (biasanya sudah ada/direkomendasikan)
            \Illuminate\Routing\Middleware\SubstituteBindings::class, // Middleware untuk route model binding
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
