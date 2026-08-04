<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
| Prefix  : /  (tidak ada prefix, sudah dari bootstrap/app.php)
| Semua route di sini pakai middleware: auth, active, tenant
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'tenant'])->group(function () {

    // ── Kepala Sekolah ─────────────────────────────────────────────────
    Route::prefix('kepsek')->name('kepsek.')->middleware('role:kepsek')->group(function () {
        Route::get('/dashboard', function () {
            return view()->exists('tenant.kepsek.dashboard.index')
                ? view('tenant.kepsek.dashboard.index')
                : response('<h1>Dashboard Kepala Sekolah — segera hadir</h1>', 200);
        })->name('dashboard');
    });

    // ── Kurikulum ──────────────────────────────────────────────────────
    Route::prefix('kurikulum')->name('kurikulum.')->middleware('role:kurikulum')->group(function () {
        Route::get('/dashboard', function () {
            return view()->exists('tenant.kurikulum.dashboard.index')
                ? view('tenant.kurikulum.dashboard.index')
                : response('<h1>Dashboard Kurikulum — segera hadir</h1>', 200);
        })->name('dashboard');
    });

    // ── Tata Usaha ─────────────────────────────────────────────────────
    Route::prefix('tu')->name('tu.')->middleware('role:tu')->group(function () {
        Route::get('/dashboard', function () {
            return view()->exists('tenant.tu.dashboard.index')
                ? view('tenant.tu.dashboard.index')
                : response('<h1>Dashboard Tata Usaha — segera hadir</h1>', 200);
        })->name('dashboard');
    });

    // ── Guru (guru_mapel + wali_kelas) ─────────────────────────────────
    Route::prefix('guru')->name('guru.')->middleware('role:guru_mapel,wali_kelas')->group(function () {
        Route::get('/dashboard', function () {
            return view()->exists('tenant.guru.dashboard.index')
                ? view('tenant.guru.dashboard.index')
                : response('<h1>Dashboard Guru — segera hadir</h1>', 200);
        })->name('dashboard');
    });

    Route::middleware('role:wali_kelas')->group(function () {
    Route::get('/absensi', [\App\Http\Controllers\Tenant\WaliKelas\AbsensiController::class, 'dashboard'])
        ->name('wali_kelas.absensi.dashboard');
    });

    // ── Kesiswaan ──────────────────────────────────────────────────────
    Route::prefix('kesiswaan')->name('kesiswaan.')->middleware('role:kesiswaan')->group(function () {
        Route::get('/dashboard', function () {
            return view()->exists('tenant.kesiswaan.dashboard.index')
                ? view('tenant.kesiswaan.dashboard.index')
                : response('<h1>Dashboard Kesiswaan — segera hadir</h1>', 200);
        })->name('dashboard');
    });

    // ── BK ─────────────────────────────────────────────────────────────
    Route::prefix('bk')->name('bk.')->middleware('role:bk')->group(function () {
        Route::get('/dashboard', function () {
            return view()->exists('tenant.bk.dashboard.index')
                ? view('tenant.bk.dashboard.index')
                : response('<h1>Dashboard BK — segera hadir</h1>', 200);
        })->name('dashboard');
    });

    // ── Toolman ────────────────────────────────────────────────────────
    Route::prefix('toolman')->name('toolman.')->middleware('role:toolman')->group(function () {
        Route::get('/dashboard', function () {
            return view()->exists('tenant.toolman.dashboard.index')
                ? view('tenant.toolman.dashboard.index')
                : response('<h1>Dashboard Toolman — segera hadir</h1>', 200);
        })->name('dashboard');
    });

    // ── Siswa ──────────────────────────────────────────────────────────
    Route::prefix('siswa')->name('siswa.')->middleware('role:siswa')->group(function () {
        Route::get('/dashboard', function () {
            return view()->exists('tenant.siswa.dashboard.index')
                ? view('tenant.siswa.dashboard.index')
                : response('<h1>Dashboard Siswa — segera hadir</h1>', 200);
        })->name('dashboard');
    });

});
