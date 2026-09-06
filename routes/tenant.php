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

// ── Test Auto-Login (LOCAL ONLY) ──────────────────────────────────────
// Dibutuhkan oleh `php artisan tenant:seed-test --show-urls`.
// Route ini TIDAK pernah aktif di production — APP_ENV selain
// local/development/testing otomatis diblok. Dilindungi juga oleh
// middleware `signed` (URL signed expire 1 hari, tidak bisa dimodifikasi).
Route::middleware('signed')->get('/__test/auto-login', function (Illuminate\Http\Request $request) {
    if (! app()->environment(['local', 'development', 'testing'])) {
        abort(403, 'Test login URL tidak aktif di environment production.');
    }

    $userId   = $request->input('user');
    $schoolId = $request->input('school');

    $user   = App\Models\User::withoutGlobalScopes()->find($userId);
    $school = App\Models\School::withoutGlobalScopes()->find($schoolId);

    if (! $user || ! $school || $user->school_id !== $school->id) {
        abort(403, 'User / School tidak cocok.');
    }

    $school->makeCurrent();
    Illuminate\Support\Facades\Auth::login($user);
    $request->session()->regenerate();

    $role = $user->role;
    $roleDashboardMap = [
        'kepsek'     => 'kepsek.dashboard',
        'kurikulum'  => 'kurikulum.dashboard',
        'tu'         => 'tu.dashboard',
        'guru_mapel' => 'guru.dashboard',
        'wali_kelas' => 'guru.dashboard',
        'kesiswaan'  => 'kesiswaan.dashboard',
        'bk'         => 'bk.dashboard',
        'toolman'    => 'toolman.dashboard',
        'siswa'      => 'siswa.dashboard',
        'superadmin' => 'superadmin.dashboard',
    ];

    $route = $roleDashboardMap[$role] ?? 'dashboard';

    return redirect()->route($route)->with([
        'test-auto-login' => true,
        'logged-in-as'    => "{$user->username} ({$role}) @ {$school->name}",
    ]);
})->name('tenant.test.auto-login');

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
            if (! view()->exists('tenant.kurikulum.dashboard.index')) {
                return response(eduzone_placeholder_dashboard('Dashboard Kurikulum — segera hadir'), 200);
            }

            return view('tenant.kurikulum.dashboard.index', [
                'totalKelasAktif'    => \App\Models\SchoolClass::where('is_active', true)->count(),
                'totalGuruAktif'     => \App\Models\Teacher::where('is_active', true)->count(),
                'totalJadwalAktif'   => \App\Models\Schedule::where('is_active', true)->count(),
                'totalJurusanAktif'  => \App\Models\Major::where('is_active', true)->count(),
            ]);
        })->name('dashboard');
    });

        // ── Tata Usaha ─────────────────────────────────────────────────────
    Route::prefix('tu')->name('tu.')->middleware('role:tu')->group(function () {
        Route::get('/dashboard', function () {
            if (! view()->exists('tenant.tu.dashboard.index')) {
                return response(eduzone_placeholder_dashboard('Dashboard Tata Usaha — segera hadir'), 200);
            }

            return view('tenant.tu.dashboard.index', [
                'totalSiswaAktif' => \App\Models\Student::where('status', 'aktif')->count(),
                'totalGuru' => \App\Models\Teacher::where('is_active', true)->count(),
                'totalStaff' => \App\Models\Staff::where('is_active', true)->count(),
            ]);
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
            if (! view()->exists('tenant.kesiswaan.dashboard.index')) {
                return response(eduzone_placeholder_dashboard('Dashboard Kesiswaan — segera hadir'), 200);
            }

            $monthStart = now()->startOfMonth();
            $monthEnd   = now()->endOfMonth();

            return view('tenant.kesiswaan.dashboard.index', [
                'totalSiswaAktif'    => \App\Models\Student::where('status', 'aktif')->count(),
                'totalPrestasi'      => \App\Models\StudentAchievement::count(),
                'totalKasusSikap'    => \App\Models\StudentSikap::count(),
                'prestasiBulanIni'   => \App\Models\StudentAchievement::whereBetween('created_at', [$monthStart, $monthEnd])->count(),
            ]);
        })->name('dashboard');
    });

    // ── BK ─────────────────────────────────────────────────────────────
    Route::prefix('bk')->name('bk.')->middleware('role:bk')->group(function () {
        Route::get('/dashboard', function () {
            if (! view()->exists('tenant.bk.dashboard.index')) {
                return response(eduzone_placeholder_dashboard('Dashboard BK — segera hadir'), 200);
            }

            $today     = today();
            $monthStart = now()->startOfMonth();
            $monthEnd   = now()->endOfMonth();

            return view('tenant.bk.dashboard.index', [
                'totalSiswaAktif'       => \App\Models\Student::where('status', 'aktif')->count(),
                'totalSesiKonseling'    => \App\Models\CounselingSession::count(),
                'sesiHariIni'           => \App\Models\CounselingSession::whereDate('date', $today)->count(),
                'sesiBulanIni'          => \App\Models\CounselingSession::whereBetween('date', [$monthStart, $monthEnd])->count(),
            ]);
        })->name('dashboard');
    });

    // ── Toolman ────────────────────────────────────────────────────────
    Route::prefix('toolman')->name('toolman.')->middleware('role:toolman')->group(function () {
        Route::get('/dashboard', function () {
            if (! view()->exists('tenant.toolman.dashboard.index')) {
                return response(eduzone_placeholder_dashboard('Dashboard Toolman — segera hadir'), 200);
            }

            $today      = today();
            $monthStart = now()->startOfMonth();
            $monthEnd   = now()->endOfMonth();

            return view('tenant.toolman.dashboard.index', [
                'totalItemInventaris'   => \App\Models\Inventory::count(),
                'totalUnitInventaris'   => (int) \App\Models\Inventory::sum('quantity'),
                'laporanHariIni'        => \App\Models\ToolmanReport::whereDate('date', $today)->count(),
                'laporanBulanIni'       => \App\Models\ToolmanReport::whereBetween('date', [$monthStart, $monthEnd])->count(),
            ]);
        })->name('dashboard');
    });

    // ── Siswa ──────────────────────────────────────────────────────────
    Route::prefix('siswa')->name('siswa.')->middleware('role:siswa')->group(function () {
        Route::get('/dashboard', function () {
            if (! view()->exists('tenant.siswa.dashboard.index')) {
                return response(eduzone_placeholder_dashboard('Dashboard Siswa — segera hadir'), 200);
            }

            $user       = auth()->user();
            $student    = $user?->student;
            $studentId  = $student?->id;
            $classId    = $student?->class_id;

            $monthStart = now()->startOfMonth();
            $monthEnd   = now()->endOfMonth();

            $rataNilai = $studentId
                ? \App\Models\StudentGrade::where('student_id', $studentId)->avg('nilai_akhir')
                : null;

            $kehadiranHadir = $studentId
                ? \App\Models\StudentAttendance::where('student_id', $studentId)
                    ->whereBetween('date', [$monthStart, $monthEnd])
                    ->where('status', 'hadir')
                    ->count()
                : 0;

            $totalKehadiranBulanIni = $studentId
                ? \App\Models\StudentAttendance::where('student_id', $studentId)
                    ->whereBetween('date', [$monthStart, $monthEnd])
                    ->count()
                : 0;

            return view('tenant.siswa.dashboard.index', [
                'totalJadwalMapel'      => $classId ? \App\Models\Schedule::where('class_id', $classId)->where('is_active', true)->distinct('subject')->count('subject') : 0,
                'rataRataNilai'         => $rataNilai ? number_format($rataNilai, 2, ',', '.') : '-',
                'kehadiranHadir'        => $kehadiranHadir,
                'totalKehadiranBulanIni'=> $totalKehadiranBulanIni,
                'totalPrestasiSiswa'    => $studentId ? \App\Models\StudentAchievement::where('student_id', $studentId)->count() : 0,
                'studentName'           => $student?->full_name ?? $user?->username,
                'className'             => $student?->class?->nama_kelas ?? '-',
            ]);
        })->name('dashboard');
    });

    Route::get('/absensi/health', [AbsensiHealthController::class, 'status'])
    ->name('tenant.absensi.health.status');

    // ── Absen HP (semua role — guru, staff, siswa) ──────────────────────
    Route::get('/absen-hp', [\App\Http\Controllers\Tenant\Absensi\AbsenHpController::class, 'show'])
        ->name('absen-hp.show');
    Route::post('/absen-hp', [\App\Http\Controllers\Tenant\Absensi\AbsenHpController::class, 'store'])
        ->name('absen-hp.store');

    // ── Device Absensi (kepsek & TU kelola device milik sekolah sendiri) ─
    Route::prefix('absensi/devices')->name('absensi.devices.')->middleware('role:kepsek,tu')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\Absensi\DeviceController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Tenant\Absensi\DeviceController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Tenant\Absensi\DeviceController::class, 'store'])->name('store');
        Route::get('/{device}/edit', [\App\Http\Controllers\Tenant\Absensi\DeviceController::class, 'edit'])->name('edit');
        Route::put('/{device}', [\App\Http\Controllers\Tenant\Absensi\DeviceController::class, 'update'])->name('update');
        Route::delete('/{device}', [\App\Http\Controllers\Tenant\Absensi\DeviceController::class, 'destroy'])->name('destroy');
        Route::post('/{device}/regenerate-key', [\App\Http\Controllers\Tenant\Absensi\DeviceController::class, 'regenerateKey'])->name('regenerate-key');
    });

        // ── Data Siswa (TU kelola data induk siswa) ─────────────────────────
    Route::prefix('tu/siswa')->name('tu.siswa.')->middleware('role:tu')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\Tu\StudentController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Tenant\Tu\StudentController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Tenant\Tu\StudentController::class, 'store'])->name('store');
        Route::get('/{student}/edit', [\App\Http\Controllers\Tenant\Tu\StudentController::class, 'edit'])->name('edit');
        Route::put('/{student}', [\App\Http\Controllers\Tenant\Tu\StudentController::class, 'update'])->name('update');
        Route::delete('/{student}', [\App\Http\Controllers\Tenant\Tu\StudentController::class, 'destroy'])->name('destroy');
    });

});