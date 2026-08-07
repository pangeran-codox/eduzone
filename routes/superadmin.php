<?php

use App\Http\Controllers\Superadmin\Auth\SuperadminLoginController;
use App\Http\Controllers\Superadmin\DashboardController;
use App\Http\Controllers\Superadmin\AbsensiHealthController;
use App\Http\Controllers\Superadmin\DeviceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Superadmin Routes
| Prefix  : /superadmin  (didefinisikan di bootstrap/app.php)
| Name    : superadmin.  (didefinisikan di bootstrap/app.php)
| Semua route di sini TIDAK pakai tenant middleware
|--------------------------------------------------------------------------
*/

// ── Auth (guest) ───────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',  [SuperadminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [SuperadminLoginController::class, 'login']);
});

// ── Protected (superadmin only) ────────────────────────────────────────
Route::middleware('superadmin')->group(function () {

    Route::post('/logout', [SuperadminLoginController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Schools management
    Route::prefix('schools')->name('schools.')->group(function () {
        Route::get('/',            [DashboardController::class, 'schools'])->name('index');
    });

    // Users management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [DashboardController::class, 'users'])->name('index');
    });

    // Subscriptions
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [DashboardController::class, 'subscriptions'])->name('index');
    });

    // Logs
    Route::get('/logs', [DashboardController::class, 'logs'])->name('logs');

    // Absensi — health check gateway/DB/sync/device per sekolah
    Route::get('/absensi/health', [AbsensiHealthController::class, 'index'])
        ->name('absensi.health');

    Route::get('/absensi/health/status', [AbsensiHealthController::class, 'status'])
        ->name('absensi.health.status');

    Route::prefix('absensi/devices')->name('absensi.devices.')->group(function () {
    Route::get('/', [DeviceController::class, 'index'])->name('index');
    Route::get('/create', [DeviceController::class, 'create'])->name('create');
    Route::post('/', [DeviceController::class, 'store'])->name('store');
    Route::get('/{device}/edit', [DeviceController::class, 'edit'])->name('edit');
    Route::put('/{device}', [DeviceController::class, 'update'])->name('update');
    Route::delete('/{device}', [DeviceController::class, 'destroy'])->name('destroy');
    Route::post('/{device}/regenerate-key', [DeviceController::class, 'regenerateKey'])->name('regenerate-key');
});

});