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

            // Internal sync (absensi-gateway Go -> Laravel, server-to-server).
            // SENGAJA TANPA middleware 'web' - bukan halaman browser, tidak
            // butuh session/CSRF. Cuma dilindungi 'sync.token' (X-Sync-Token).
            // Lihat routes/sync.php & docs/laravel-sync-contract.md (repo gateway).
            Route::middleware('sync.token')
                ->prefix('api/internal/sync')
                ->group(base_path('routes/sync.php'));

            // Serving foto siswa/guru/staff via token opaque - SENGAJA tanpa
            // middleware 'web' (tidak butuh session/CSRF untuk request gambar).
            // Dipanggil dari photo_url hasil sync ke absensi-gateway, dan
            // dari mana pun di UI Laravel yang perlu tampilkan foto orang.
            // Lihat PersonPhotoController & HasPhotoAccessToken trait.
            Route::get('/media/person-photo/{token}', \App\Http\Controllers\PersonPhotoController::class)
                ->name('media.person-photo');
        },
    )
        ->withMiddleware(function (Middleware $middleware) {
            $middleware->alias([
                'role'           => \App\Http\Middleware\RoleMiddleware::class,
                'active'         => \App\Http\Middleware\EnsureUserIsActive::class,
                'superadmin'     => \App\Http\Middleware\SuperadminOnly::class,
                'horizon.auth'   => \App\Http\Middleware\SuperadminOnly::class,
                'sync.token'     => \App\Http\Middleware\VerifySyncToken::class,
            ]);

            // FIX 6 Sep 2026 — 'tenant' sebelumnya alias tunggal ke
            // InitializeTenancy (yang cuma set search_path, TIDAK resolve
            // tenant). Sekarang jadi grup: resolusi tenant DULU, baru guard
            // Spatie yang menolak request kalau resolusi gagal.
            $middleware->group('tenant', [
                \App\Http\Middleware\InitializeTenancy::class,
                \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,
            ]);
        })
        ->withExceptions(function (Exceptions $exceptions) {
            $exceptions->render(function (\Spatie\Multitenancy\Exceptions\NoCurrentTenant $e, \Illuminate\Http\Request $request) {
                auth()->logout();
                $request->session()->invalidate();

                return redirect()->route('login')->withErrors([
                    'login' => 'Sesi sekolah kamu tidak valid. Silakan login ulang.',
                ]);
            });
    })->create();