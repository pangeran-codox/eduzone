<?php

namespace App\Http\Controllers\Tenant\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\Absensi\AttendanceEvent;
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
 * CATATAN SCOPE (per keputusan — query langsung attendance_events, belum
 * ada job sync attendance_daily): status yang bisa ditentukan cuma
 * "Hadir" (ada event check_in hari ini) vs "Belum Absen". Status
 * Izin/Sakit/Alpa/Terlambat TIDAK bisa diturunkan dari attendance_events —
 * event_type di tabel ini cuma check_in/check_out/unknown (lihat CHECK
 * constraint migration), tidak ada kategori administratif semacam itu.
 * Begitu modul izin/sakit manual atau job sync attendance_daily ada,
 * dashboard ini perlu direvisi buat menggabungkan sumber data itu.
 *
 * Alur resolve "kelas mana yang dipegang wali kelas ini":
 *   User (login) -> Teacher (via user_id) -> HomeroomAssignment aktif
 *   (via teacher_id) -> class_id
 *
 * people_ref & attendance_events ada di database terpisah (pgsql_absensi).
 * person_type di kedua tabel itu pakai 'student' (BUKAN 'siswa').
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

        // Ambil semua event hari ini buat siswa-siswa di kelas ini, urut
        // waktu — supaya firstWhere('event_type', 'check_in') di bawah
        // otomatis dapat tap PALING AWAL kalau ada beberapa check_in
        // (misal tap ulang karena gagal pertama kali).
        $events = AttendanceEvent::where('school_id', $schoolId)
            ->where('person_type', 'student')
            ->whereIn('person_id', $studentIds)
            ->whereBetween('recorded_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->orderBy('recorded_at')
            ->get();

        $eventsByStudent = $events->groupBy('person_id');

        $records = $studentsInClass->map(function ($student) use ($eventsByStudent) {
            $studentEvents = $eventsByStudent->get($student->person_id, collect());
            $checkIn = $studentEvents->firstWhere('event_type', 'check_in');
            $hasAnomaly = $studentEvents->contains(fn ($e) => ! $e->is_valid || $e->flagged_reason);

            return [
                'nama' => $student->full_name,
                'waktu' => $checkIn?->recorded_at?->format('H:i'),
                'metode' => $this->formatMetode($checkIn?->method),
                'status' => $checkIn ? 'Hadir' : 'Belum Absen',
                'has_anomaly' => $hasAnomaly,
            ];
        })->values();

        $stats = [
            'hadir' => $records->where('status', 'Hadir')->count(),
            'belum' => $records->where('status', 'Belum Absen')->count(),
            'anomali' => $records->where('has_anomaly', true)->count(),
        ];

        return view('tenant.absensi.dashboard', [
            'noClass' => false,
            'reason' => null,
            'stats' => $stats,
            'records' => $records,
            'class' => $class,
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