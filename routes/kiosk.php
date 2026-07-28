<?php

use App\Http\Controllers\Kiosk\CheckInController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route Kiosk (device absensi fisik)
|--------------------------------------------------------------------------
|
| SENGAJA terpisah dari routes/tenant.php - kiosk BUKAN halaman user login,
| jadi TIDAK pakai middleware ['auth','active','tenant']. Identitas & scope
| sekolah ditentukan dari {deviceCode} di URL, bukan dari sesi user.
|
| KEPUTUSAN FINAL (lihat ARCHITECTURE.md §2.6): check-in device dipegang
| PENUH oleh `absensi-gateway` (Go), lewat proxy NPM /gateway. Route di sini
| cuma buat RENDER halaman - route POST checkin yang dulu ada di sini sudah
| dihapus, jangan ditambah lagi kecuali keputusan §2.6 berubah.
|
| Terdaftar di bootstrap/app.php:
|   Route::middleware('web')->group(base_path('routes/kiosk.php'));
*/

Route::prefix('kiosk')->name('kiosk.')->group(function () {
    Route::get('/{deviceCode}', [CheckInController::class, 'show'])->name('checkin.show');
});