<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix('superadmin')
                ->name('superadmin.')
                ->group(base_path('routes/superadmin.php'));

            Route::middleware('web')
                ->group(base_path('routes/tenant.php'));

            // Kiosk absensi (device fisik) - SENGAJA tanpa prefix/name di sini,
            // karena routes/kiosk.php sudah punya Route::prefix('kiosk') sendiri
            // di dalam filenya. Juga sengaja tanpa middleware tenant/auth -
            // lihat komentar di routes/kiosk.php untuk alasannya.
            Route::middleware('web')
                ->group(base_path('routes/kiosk.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Tenant middleware alias — dipakai di route group yang butuh tenant context
        $middleware->alias([
            'tenant'         => \App\Http\Middleware\InitializeTenancy::class,
            'role'           => \App\Http\Middleware\RoleMiddleware::class,
            'active'         => \App\Http\Middleware\EnsureUserIsActive::class,
            'superadmin'     => \App\Http\Middleware\SuperadminOnly::class,
            'horizon.auth'   => \App\Http\Middleware\SuperadminOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();