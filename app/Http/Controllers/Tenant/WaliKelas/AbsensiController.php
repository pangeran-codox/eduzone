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
 * Dashboard absensi harian untuk wali kelas — lihat status hadir/terlambat/
 * izin/sakit/alpa/belum-absen semua siswa di kelas yang dia pegang, hari ini.
 *
 * Alur resolve "kelas mana yang dipegang wali kelas ini":
 *   User (login) -> Teacher (via user_id) -> HomeroomAssignment aktif
 *   (via teacher_id) -> class_id
 * BUKAN lewat kolom langsung di users (draft sebelumnya salah asumsi soal
 * ini) — homeroom_assignments adalah sumber kebenarannya, dan sengaja
 * dipisah dari teachers.is_homeroom (boolean) karena satu guru secara
 * teori bisa punya riwayat homeroom di tahun ajaran berbeda.
 *
 * people_ref & attendance_daily ada di database terpisah (pgsql_absensi,
 * lihat SKILL.md). person_type di kedua tabel itu pakai 'student' (BUKAN
 * 'siswa') — CHECK constraint di DB cuma terima 'student'/'teacher'/'staff'.
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
        // JANGAN ::find(), selalu where() (lihat SKILL.md).
        $studentsInClass = PeopleRef::where('school_id', $schoolId)
            ->where('person_type', 'student')
            ->where('class_id', $assignment->class_id)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        $studentIds = $studentsInClass->pluck('person_id');

        $dailyRecords = AttendanceDaily::where('school_id', $schoolId)
            ->where('person_type', 'student')
            ->where('date', $today->toDateString())
            ->whereIn('person_id', $studentIds)
            ->get()
            ->keyBy('person_id');

        $records = $studentsInClass->map(function ($student) use ($dailyRecords) {
            $daily = $dailyRecords->get($student->person_id);

            return [
                'nama' => $student->full_name,
                'waktu' => $daily?->first_check_in ? Carbon::parse($daily->first_check_in)->format('H:i') : null,
                'metode' => $this->formatMetode($daily?->primary_method),
                'status' => $daily?->status ?? 'Belum Absen',
                'has_anomaly' => (bool) ($daily?->has_anomaly ?? false),
            ];
        })->values();

        $stats = [
            'hadir' => $records->where('status', 'Hadir')->count(),
            'terlambat' => $records->where('status', 'Terlambat')->count(),
            'izin' => $records->whereIn('status', ['Izin', 'Sakit'])->count(),
            'alpa' => $records->where('status', 'Alpa')->count(),
            'belum' => $records->where('status', 'Belum Absen')->count(),
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
            'manual' => 'Manual',
            default => $metode,
        };
    }
}
