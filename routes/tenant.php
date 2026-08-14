<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tenant\AbsensiHealthController;

/*
|--------------------------------------------------------------------------
| Tenant Routes
| Prefix  : /  (tidak ada prefix, sudah dari bootstrap/app.php)
| Semua route di sini pakai middleware: auth, active, tenant
|--------------------------------------------------------------------------
*/

// Helper: HTML placeholder + tombol logout, dipakai semua dashboard yang
// belum punya view sungguhan. Sementara saja — begitu view
// tenant.{role}.dashboard.index ada, closure ini otomatis tidak dipakai lagi
// (lihat pengecekan view()->exists() di tiap route di bawah).
if (! function_exists('eduzone_placeholder_dashboard')) {
    function eduzone_placeholder_dashboard(string $title): string
    {
        $logoutUrl = route('logout');
        $csrf = csrf_token();

        return <<<HTML
            <h1>{$title}</h1>
            <form method="POST" action="{$logoutUrl}" style="margin-top:1rem;">
                <input type="hidden" name="_token" value="{$csrf}">
                <button type="submit">Keluar</button>
            </form>
        HTML;
    }
}

Route::middleware(['auth', 'active', 'tenant'])->group(function () {

    // ── Kepala Sekolah ─────────────────────────────────────────────────
    Route::prefix('kepsek')->name('kepsek.')->middleware('role:kepsek')->group(function () {
        Route::get('/dashboard', function () {
            return view()->exists('tenant.kepsek.dashboard.index')
                ? view('tenant.kepsek.dashboard.index')
                : response(eduzone_placeholder_dashboard('Dashboard Kepala Sekolah — segera hadir'), 200);
        })->name('dashboard');
    });

    // ── Kurikulum ──────────────────────────────────────────────────────
    Route::prefix('kurikulum')->name('kurikulum.')->middleware('role:kurikulum')->group(function () {
        Route::get('/dashboard', function () {
            return view()->exists('tenant.kurikulum.dashboard.index')
                ? view('tenant.kurikulum.dashboard.index')
                : response(eduzone_placeholder_dashboard('Dashboard Kurikulum — segera hadir'), 200);
        })->name('dashboard');
    });

    // ── Tata Usaha ─────────────────────────────────────────────────────
    Route::prefix('tu')->name('tu.')->middleware('role:tu')->group(function () {
        Route::get('/dashboard', function () {
            return view()->exists('tenant.tu.dashboard.index')
                ? view('tenant.tu.dashboard.index')
                : response(eduzone_placeholder_dashboard('Dashboard Tata Usaha — segera hadir'), 200);
        })->name('dashboard');
    });

    // ── Guru (guru_mapel + wali_kelas) ─────────────────────────────────
    Route::prefix('guru')->name('guru.')->middleware('role:guru_mapel,wali_kelas')->group(function () {
        Route::get('/dashboard', function () {
            return view()->exists('tenant.guru.dashboard.index')
                ? view('tenant.guru.dashboard.index')
                : response(eduzone_placeholder_dashboard('Dashboard Guru — segera hadir'), 200);
        })->name('dashboard');
    });

    Route::middleware('role:kepsek,tu')->group(function () {
    Route::get('/absensi/rekap', [\App\Http\Controllers\Tenant\Absensi\RekapController::class, 'index'])
        ->name('absensi.rekap.index');
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
                : response(eduzone_placeholder_dashboard('Dashboard Kesiswaan — segera hadir'), 200);
        })->name('dashboard');
    });

    // ── BK ─────────────────────────────────────────────────────────────
    Route::prefix('bk')->name('bk.')->middleware('role:bk')->group(function () {
        Route::get('/dashboard', function () {
            return view()->exists('tenant.bk.dashboard.index')
                ? view('tenant.bk.dashboard.index')
                : response(eduzone_placeholder_dashboard('Dashboard BK — segera hadir'), 200);
        })->name('dashboard');
    });

    // ── Toolman ────────────────────────────────────────────────────────
    Route::prefix('toolman')->name('toolman.')->middleware('role:toolman')->group(function () {
        Route::get('/dashboard', function () {
            return view()->exists('tenant.toolman.dashboard.index')
                ? view('tenant.toolman.dashboard.index')
                : response(eduzone_placeholder_dashboard('Dashboard Toolman — segera hadir'), 200);
        })->name('dashboard');
    });

    // ── Siswa ──────────────────────────────────────────────────────────
    Route::prefix('siswa')->name('siswa.')->middleware('role:siswa')->group(function () {
        Route::get('/dashboard', function () {
            return view()->exists('tenant.siswa.dashboard.index')
                ? view('tenant.siswa.dashboard.index')
                : response(eduzone_placeholder_dashboard('Dashboard Siswa — segera hadir'), 200);
        })->name('dashboard');
    });

    Route::get('/absensi/health', [AbsensiHealthController::class, 'status'])
    ->name('tenant.absensi.health.status');


});