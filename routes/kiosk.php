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
| sekolah ditentukan dari {device} (device_code) di URL, bukan dari sesi user.
| Endpoint POST divalidasi via header X-Device-Key (lihat CheckInController).
|
| Perlu didaftarkan di bootstrap/app.php, konsisten dengan cara tenant.php /
| superadmin.php didaftarkan di situ. Cek dulu pola yang sudah ada, tapi
| kurang lebih begini:
|
|   ->withRouting(
|       web: __DIR__.'/../routes/web.php',
|       then: function () {
|           Route::middleware('web')->group(base_path('routes/tenant.php'));
|           Route::middleware('web')->group(base_path('routes/superadmin.php'));
|           Route::middleware('web')->group(base_path('routes/kiosk.php')); // <- tambahkan ini
|       },
|   )
|
*/

Route::prefix('kiosk')->name('kiosk.')->group(function () {
    Route::get('/{deviceCode}', [CheckInController::class, 'show'])->name('checkin.show');
    Route::post('/{deviceCode}/checkin', [CheckInController::class, 'store'])->name('checkin.store');
});
