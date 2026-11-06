<?php

namespace App\Http\Controllers\Tenant\GuruMapel;

use App\Http\Controllers\Controller;
use App\Models\Absensi\AttendanceDaily;
use App\Models\Absensi\AttendanceEvent;
use App\Models\Absensi\PeopleRef;
use App\Models\Absensi\SchedulesRef;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeachingSubstitution;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Absensi Mengajar Guru Mapel, dipecah jadi 2 halaman biar ringan:
 *   - index(): ringkasan per jadwal hari ini (COUNT query, TIDAK pernah
 *     load daftar nama siswa) - ditampilkan sebagai card.
 *   - detail($scheduleId): daftar lengkap siswa + status, HANYA untuk
 *     1 jadwal yang diklik.
 *
 * GURU PENGGANTI: jadwal reguler (schedules_ref) tetap atas nama guru
 * asli. Pengecualian per tanggal dicatat Kurikulum di
 * teaching_substitutions (DB utama). Hari ini guru pengganti melihat
 * kelas yang dia gantikan, dan guru asli melihat penanda "digantikan".
 * Status murid tidak terpengaruh karena event absensi terikat ke
 * schedule_id, bukan ke guru.
 *
 * Sumber data status murid: attendance_events mentah difilter
 * schedule_id (attendance_period masih kosong). Status biner
 * Hadir/Belum Absen, konsisten dengan WaliKelas\AbsensiController.
 */
class AbsensiController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $today = Carbon::today();
        $todayString = $today->toDateString();

        $teacher = Teacher::where('user_id', $user->id)->first();

        if (! $teacher) {
            return view('tenant.guru_mapel.absensi.index', [
                'noTeacher' => true,
                'reason' => 'Akun ini belum terhubung ke data guru.',
                'ownAttendance' => null,
                'schedules' => collect(),
                'date' => $todayString,
            ]);
        }

        $ownAttendance = AttendanceDaily::where('school_id', $schoolId)
            ->where('person_id', $teacher->id)
            ->where('person_type', 'teacher')
            ->where('date', $todayString)
            ->first();

        // Penggantian hari ini yang melibatkan guru ini (sebagai asli atau pengganti).
        $substitutionsToday = TeachingSubstitution::where('date', $todayString)
            ->where(function ($q) use ($teacher) {
                $q->where('original_teacher_id', $teacher->id)
                    ->orWhere('substitute_teacher_id', $teacher->id);
            })
            ->get();

        $awayBySchedule = $substitutionsToday
            ->where('original_teacher_id', $teacher->id)
            ->keyBy('schedule_id');

        $coveringBySchedule = $substitutionsToday
            ->where('substitute_teacher_id', $teacher->id)
            ->keyBy('schedule_id');

        $ownSchedules = SchedulesRef::where('school_id', $schoolId)
            ->where('teacher_id', $teacher->id)
            ->where('day_of_week', $today->dayOfWeekIso) // 1=Senin .. 7=Minggu
            ->where('is_active', true)
            ->get();

        $coveredSchedules = $coveringBySchedule->isEmpty()
            ? collect()
            : SchedulesRef::where('school_id', $schoolId)
                ->whereIn('schedule_id', $coveringBySchedule->keys())
                ->where('day_of_week', $today->dayOfWeekIso)
                ->where('is_active', true)
                ->get();

        // concat (bukan merge): merge Eloquent memakai primary key model,
        // dan konfigurasi key SchedulesRef belum pernah dicek langsung.
        $schedulesToday = $ownSchedules->concat($coveredSchedules)->sortBy('start_time')->values();

        $relatedTeacherIds = $substitutionsToday
            ->flatMap(fn ($s) => [$s->original_teacher_id, $s->substitute_teacher_id])
            ->unique()
            ->values();

        $teacherNames = $relatedTeacherIds->isEmpty()
            ? collect()
            : Teacher::whereIn('id', $relatedTeacherIds)->pluck('full_name', 'id');

        $classNames = SchoolClass::whereIn('id', $schedulesToday->pluck('class_id')->filter()->unique())
            ->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->nama_kelas]);

        $schedules = $schedulesToday->map(function ($sch) use (
            $schoolId, $today, $classNames, $teacherNames, $awayBySchedule, $coveringBySchedule
        ) {
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

            $covering = $coveringBySchedule->get($sch->schedule_id);
            $away = $awayBySchedule->get($sch->schedule_id);

            return [
                'schedule_id' => $sch->schedule_id,
                'subject_name' => $sch->subject_name,
                'class_name' => $classNames->get($sch->class_id),
                'start_time' => substr($sch->start_time, 0, 5),
                'end_time' => substr($sch->end_time, 0, 5),
                'total_siswa' => $totalSiswa,
                'hadir' => $hadir,
                'covering_for' => $covering ? $teacherNames->get($covering->original_teacher_id, 'guru lain') : null,
                'substituted_by' => $away ? $teacherNames->get($away->substitute_teacher_id, 'guru lain') : null,
            ];
        });

        return view('tenant.guru_mapel.absensi.index', [
            'noTeacher' => false,
            'reason' => null,
            'ownAttendance' => $ownAttendance,
            'schedules' => $schedules,
            'date' => $todayString,
        ]);
    }

    public function detail(Request $request, string $scheduleId): View
    {
        abort_if(! Str::isUuid($scheduleId), 404);

        $user = auth()->user();
        $schoolId = $user->school_id;
        $today = Carbon::today();

        $teacher = Teacher::where('user_id', $user->id)->first();

        abort_if(! $teacher, 403);

        $sch = SchedulesRef::where('school_id', $schoolId)
            ->where('schedule_id', $scheduleId)
            ->first();

        abort_if(! $sch, 404);

        // WAJIB: yang boleh buka cuma guru asli jadwal ini, atau guru yang
        // hari ini tercatat sebagai penggantinya. Tanpa pengecekan ini guru
        // lain bisa melihat kelas orang lewat URL.
        $isOwner = $sch->teacher_id === $teacher->id;

        $substitutionToday = TeachingSubstitution::where('schedule_id', $scheduleId)
            ->where('date', $today->toDateString())
            ->first();

        $isSubstituteToday = $substitutionToday
            && $substitutionToday->substitute_teacher_id === $teacher->id;

        abort_if(! $isOwner && ! $isSubstituteToday, 404);

        $note = null;
        if ($substitutionToday) {
            $names = Teacher::whereIn('id', [
                $substitutionToday->original_teacher_id,
                $substitutionToday->substitute_teacher_id,
            ])->pluck('full_name', 'id');

            $note = $isSubstituteToday
                ? 'Anda menggantikan ' . $names->get($substitutionToday->original_teacher_id, 'guru lain') . ' hari ini'
                : 'Digantikan ' . $names->get($substitutionToday->substitute_teacher_id, 'guru lain') . ' hari ini';
        }

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
            'class_name' => SchoolClass::find($sch->class_id)?->nama_kelas,
            'substitution_note' => $note,
            'start_time' => substr($sch->start_time, 0, 5),
            'end_time' => substr($sch->end_time, 0, 5),
            'date' => $today->toDateString(),
            'total_siswa' => $students->count(),
            'hadir' => $records->where('status', 'Hadir')->count(),
            'records' => $records,
        ]);
    }
}