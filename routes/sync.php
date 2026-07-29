<?php

use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route Internal Sync (server-to-server, absensi-gateway -> Laravel)
|--------------------------------------------------------------------------
|
| Kontrak lengkap ada di docs/laravel-sync-contract.md (repo absensi-gateway).
| SENGAJA tanpa middleware 'web' - ini bukan halaman browser, tidak butuh
| session/CSRF. Cuma dilindungi middleware sync.token (X-Sync-Token).
|
| Terdaftar di bootstrap/app.php:
|   Route::middleware('sync.token')
|       ->prefix('api/internal/sync')
|       ->group(base_path('routes/sync.php'));
*/

Route::get('/schools', [SyncController::class, 'schools'])->name('sync.schools');
Route::get('/people', [SyncController::class, 'people'])->name('sync.people');
Route::get('/schedules', [SyncController::class, 'schedules'])->name('sync.schedules');
