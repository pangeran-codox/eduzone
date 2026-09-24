<?php

namespace App\Http\Controllers\Tenant\GuruMapel;

use App\Http\Controllers\Controller;
use App\Models\Absensi\AttendanceDaily;
use App\Models\Absensi\AttendanceEvent;
use App\Models\Absensi\PeopleRef;
use App\Models\Absensi\SchedulesRef;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Absensi Mengajar Guru Mapel, dipecah jadi 2 halaman biar ringan:
 *   - index(): cuma ringkasan per jadwal hari ini (COUNT query, TIDAK
 *     pernah load daftar nama siswa) - ini yang ditampilkan sebagai card.
 *   - detail($scheduleId): daftar lengkap siswa + status, HANYA untuk
 *     1 jadwal yang diklik. Divalidasi jadwal itu milik guru yang login
 *     (teacher_id cocok) - jangan sampai bisa intip kelas guru lain lewat
 *     URL manipulation schedule_id.
 *
 * Sumber data status murid: attendance_events mentah difilter schedule_id
 * (attendance_period masih kosong total, belum ada proses yang mengisi -
 * dikonfirmasi 23 Sep 2026). Status biner Hadir/Belum Absen, konsisten
 * dengan WaliKelas\AbsensiController.
 */
class AbsensiController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $today = Carbon::today();

        $teacher = Teacher::where('user_id', $user->id)->first();

        if (! $teacher) {
            return view('tenant.guru_mapel.absensi.index', [
                'noTeacher' => true,
                'reason' => 'Akun ini belum terhubung ke data guru.',
                'ownAttendance' => null,
                'schedules' => collect(),
                'date' => $today->toDateString(),
            ]);
        }

        $ownAttendance = AttendanceDaily::where('school_id', $schoolId)
            ->where('person_id', $teacher->id)
            ->where('person_type', 'teacher')
            ->where('date', $today->toDateString())
            ->first();

        $schedulesToday = SchedulesRef::where('school_id', $schoolId)
            ->where('teacher_id', $teacher->id)
            ->where('day_of_week', $today->dayOfWeekIso) // 1=Senin .. 7=Minggu
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        $schedules = $schedulesToday->map(function ($sch) use ($schoolId, $today) {
            $totalSiswa = PeopleRef::where('school_id', $schoolId)
                ->where('person_type', 'student')
                ->where('class_id', $sch->class_id)
                ->where('is_active', true)
                ->count();

            $hadir = AttendanceEvent::where('school_id', $schoolId)
                ->where('schedule_id', $sch->schedule_id)
                ->where('person_type', 'student')
                ->where('event_type', 'check_in')
                ->whereBetween('recorded_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                ->distinct()
                ->count('person_id');

            return [
                'schedule_id' => $sch->schedule_id,
                'subject_name' => $sch->subject_name,
                'start_time' => substr($sch->start_time, 0, 5),
                'end_time' => substr($sch->end_time, 0, 5),
                'total_siswa' => $totalSiswa,
                'hadir' => $hadir,
            ];
        });

        return view('tenant.guru_mapel.absensi.index', [
            'noTeacher' => false,
            'reason' => null,
            'ownAttendance' => $ownAttendance,
            'schedules' => $schedules,
            'date' => $today->toDateString(),
        ]);
    }

    public function detail(Request $request, string $scheduleId): View
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $today = Carbon::today();

        $teacher = Teacher::where('user_id', $user->id)->first();

        abort_if(! $teacher, 403);

        // WAJIB filter teacher_id juga - kalau cuma filter schedule_id,
        // guru A bisa liat kelas guru B dengan nebak/ganti schedule_id di URL.
        $sch = SchedulesRef::where('school_id', $schoolId)
            ->where('schedule_id', $scheduleId)
            ->where('teacher_id', $teacher->id)
            ->first();

        abort_if(! $sch, 404);

        $students = PeopleRef::where('school_id', $schoolId)
            ->where('person_type', 'student')
            ->where('class_id', $sch->class_id)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        $events = AttendanceEvent::where('school_id', $schoolId)
            ->where('schedule_id', $sch->schedule_id)
            ->where('person_type', 'student')
            ->whereBetween('recorded_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->orderBy('recorded_at')
            ->get()
            ->groupBy('person_id');

        $records = $students->map(function ($student) use ($events) {
            $studentEvents = $events->get($student->person_id, collect());
            $checkIn = $studentEvents->firstWhere('event_type', 'check_in');

            return [
                'nama' => $student->full_name,
                'waktu' => $checkIn?->recorded_at?->format('H:i'),
                'status' => $checkIn ? 'Hadir' : 'Belum Absen',
                'has_anomaly' => $studentEvents->contains(fn ($e) => ! $e->is_valid || $e->flagged_reason),
            ];
        })->values();

        return view('tenant.guru_mapel.absensi.detail', [
            'subject_name' => $sch->subject_name,
            'start_time' => substr($sch->start_time, 0, 5),
            'end_time' => substr($sch->end_time, 0, 5),
            'date' => $today->toDateString(),
            'total_siswa' => $students->count(),
            'hadir' => $records->where('status', 'Hadir')->count(),
            'records' => $records,
        ]);
    }
}