<?php

use App\Http\Controllers\Auth\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Guest\SchoolRegistrationController;
use App\Http\Controllers\Guest\PasswordSetupController;


// Landing page
Route::get('/', function () {
    return view('welcome');
});

Route::prefix('daftar-sekolah')
    ->name('guest.school-registration.')
    ->group(function () {
        Route::get('/', [SchoolRegistrationController::class, 'create'])->name('create');
 
        // 10 percobaan per 60 menit per IP (validasi gagal juga dihitung)
        Route::post('/', [SchoolRegistrationController::class, 'store'])
            ->middleware('throttle:10,60')
            ->name('store');
 
        Route::get('/terima-kasih', [SchoolRegistrationController::class, 'thanks'])->name('thanks');
 
        Route::get('/verifikasi/{registration}/{token}', [SchoolRegistrationController::class, 'verify'])
            ->whereUuid('registration')
            ->middleware('throttle:20,1')
            ->name('verify');
    });

    Route::prefix('atur-kata-sandi')
    ->name('guest.password-setup.')
    ->group(function () {
        Route::get('/{registration}/{token}', [PasswordSetupController::class, 'show'])
            ->whereUuid('registration')
            ->middleware('throttle:20,1')
            ->name('show');
 
        Route::post('/{registration}/{token}', [PasswordSetupController::class, 'store'])
            ->whereUuid('registration')
            ->middleware('throttle:10,1')
            ->name('store');
    });

// Auth — guest only
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// Dashboard router — redirect ke dashboard sesuai role
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->middleware(['auth', 'active', 'tenant']);
