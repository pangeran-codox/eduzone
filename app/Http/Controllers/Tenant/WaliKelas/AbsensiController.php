<?php

namespace App\Http\Controllers\Tenant\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\Absensi\AttendanceDaily;
use App\Models\Absensi\PeopleRef;
use App\Models\HomeroomAssignment;
use App\Models\SchoolClass;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Dashboard absensi harian untuk wali kelas — status hadir hari ini per
 * siswa di kelas yang dia pegang.
 *
 * DIUPGRADE 24 Sep 2026: sebelumnya query langsung attendance_events
 * (cuma bisa Hadir/Belum Absen). Sekarang baca AttendanceDaily, sama
 * seperti Kepsek\AttendanceMonitorController & Tu\AttendanceDashboardController
 * — otomatis dapat status Terlambat/Sakit/Izin/Alpa begitu sync job
 * (absensi:sync-daily-to-main) atau input manual TU mengisinya.
 *
 * Alur resolve "kelas mana yang dipegang wali kelas ini":
 *   User (login) -> Teacher (via user_id) -> HomeroomAssignment aktif
 *   (via teacher_id) -> class_id
 *
 * people_ref & attendance_daily ada di database terpisah (pgsql_absensi).
 * person_type pakai 'student' (BUKAN 'siswa').
 */
class AbsensiController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $today = Carbon::today();

        $teacher = Teacher::where('user_id', $user->id)->first();

        if (! $teacher) {
            return view('tenant.absensi.dashboard', [
                'noClass' => true,
                'reason' => 'Akun ini belum terhubung ke data guru.',
                'stats' => [],
                'records' => collect(),
                'class' => null,
            ]);
        }

        $assignment = HomeroomAssignment::where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->first();

        if (! $assignment) {
            return view('tenant.absensi.dashboard', [
                'noClass' => true,
                'reason' => 'Belum ada penugasan wali kelas aktif untuk akun ini.',
                'stats' => [],
                'records' => collect(),
                'class' => null,
            ]);
        }

        $class = SchoolClass::find($assignment->class_id);

        // people_ref PAKAI composite primary key (person_id, person_type) —
        // JANGAN ::find(), selalu where().
        $studentsInClass = PeopleRef::where('school_id', $schoolId)
            ->where('person_type', 'student')
            ->where('class_id', $assignment->class_id)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        $studentIds = $studentsInClass->pluck('person_id');

        $daily = AttendanceDaily::where('school_id', $schoolId)
            ->where('person_type', 'student')
            ->where('date', $today->toDateString())
            ->whereIn('person_id', $studentIds)
            ->get()
            ->keyBy('person_id');

        $records = $studentsInClass->map(function ($student) use ($daily) {
            $row = $daily->get($student->person_id);

            return [
                'nama' => $student->full_name,
                'waktu' => $row?->first_check_in,
                'metode' => $this->formatMetode($row?->primary_method),
                'status' => $row->status ?? 'Belum Absen',
                'notes' => $row->notes ?? null,
                'has_anomaly' => (bool) ($row->has_anomaly ?? false),
            ];
        })->values();

        $statuses = ['Hadir', 'Terlambat', 'Sakit', 'Izin', 'Alpa'];
        $stats = array_fill_keys($statuses, 0);
        $stats['belum'] = $records->where('status', 'Belum Absen')->count();
        $stats['anomali'] = $records->where('has_anomaly', true)->count();

        foreach ($statuses as $s) {
            $stats[$s] = $records->where('status', $s)->count();
        }

        return view('tenant.absensi.dashboard', [
            'noClass' => false,
            'reason' => null,
            'stats' => $stats,
            'records' => $records,
            'class' => $class,
            'totalSiswa' => $studentsInClass->count(),
        ]);
    }

    private function formatMetode(?string $metode): ?string
    {
        return match ($metode) {
            'rfid' => 'RFID',
            'qr' => 'QR',
            'face' => 'Wajah',
            'fingerprint' => 'Sidik Jari',
            'manual' => 'Manual',
            default => $metode,
        };
    }
}