<?php

namespace App\Http\Controllers\Tenant\GuruMapel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\GuruMapel\StoreSubjectAttendanceRequest;
use App\Models\Absensi\AttendanceDaily;
use App\Models\Absensi\PeopleRef;
use App\Models\Absensi\SchedulesRef;
use App\Models\SchoolClass;
use App\Models\StudentSubjectAttendance;
use App\Models\Teacher;
use App\Models\TeachingAttendance;
use App\Models\TeachingSubstitution;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Guru mapel mengisi absensi murid PER JAM PELAJARAN.
 *
 * Menulis langsung ke DB utama: satu baris teaching_attendance (jadwal + tanggal) dan satu baris
 * student_subject_attendance per murid. attendance_period (DB absensi) TIDAK dipakai: tidak ada event
 * perangkat yang membawa schedule_id.
 *
 * Aturan:
 * - Hanya HARI INI dan hanya pada hari jadwalnya.
 * - Yang boleh mengisi: guru efektif jam itu. Kalau ada penggantian hari ini (teaching_substitutions),
 *   hanya guru pengganti; kalau tidak, guru pemilik jadwal. Aturan "boleh melihat" sama dengan
 *   AbsensiController::detail() (jadwal orang lain -> 404).
 * - Nilai awal form: murid yang di absensi HARIAN berstatus Sakit/Izin/Alpa otomatis terisi. Hanya dibaca,
 *   tidak pernah ditulis balik ke attendance_daily.
 * - Simpan idempoten: satu sesi per jadwal per tanggal, satu baris per murid per sesi.
 * - teaching_attendance.teacher_id = guru yang benar-benar mengajar (pengganti bila ada penggantian).
 */
class SesiAbsensiController extends Controller
{
    public const STATUSES = ['Hadir', 'Sakit', 'Izin', 'Alpa'];

    /** Status dari absensi harian yang dipakai sebagai nilai awal (selain itu = Hadir). */
    private const PREFILL_FROM_DAILY = ['Sakit', 'Izin', 'Alpa'];

    public function edit(string $scheduleId): View
    {
        [$teacher, $sch, , $today] = $this->authorizeSession($scheduleId);

        $schoolId = auth()->user()->school_id;
        $todayString = $today->toDateString();

        $students = $this->classStudents($schoolId, $sch->class_id);

        $session = TeachingAttendance::query()
            ->where('schedule_id', $sch->schedule_id)
            ->where('date', $todayString)
            ->first();

        $saved = $session
            ? StudentSubjectAttendance::query()->where('teaching_attendance_id', $session->id)->get()->keyBy('student_id')
            : collect();

        [$daily, $dailyUnavailable] = $this->dailyPrefill($schoolId, $students->pluck('person_id')->all(), $todayString);

        $rows = $students->map(function ($student) use ($saved, $daily) {
            $record = $saved->get($student->person_id);

            if ($record && in_array($record->status, self::STATUSES, true)) {
                $status = $record->status;
                $source = 'saved';
                $notes = $record->notes;
            } elseif (isset($daily[$student->person_id])) {
                $status = $daily[$student->person_id];
                $source = 'harian';
                $notes = null;
            } else {
                $status = 'Hadir';
                $source = 'default';
                $notes = null;
            }

            return [
                'id' => $student->person_id,
                'nama' => $student->full_name,
                'status' => $status,
                'source' => $source,
                'notes' => $notes,
            ];
        })->values();

        return view('tenant.guru_mapel.absensi.sesi', [
            'schedule_id' => $sch->schedule_id,
            'subject_name' => $sch->subject_name,
            'class_name' => SchoolClass::find($sch->class_id)?->nama_kelas,
            'start_time' => substr($sch->start_time, 0, 5),
            'end_time' => substr($sch->end_time, 0, 5),
            'date' => $todayString,
            'statuses' => self::STATUSES,
            'rows' => $rows,
            'session_exists' => (bool) $session,
            'topic' => $session?->topic,
            'session_notes' => $session?->notes,
            'daily_unavailable' => $dailyUnavailable,
        ]);
    }

    public function store(StoreSubjectAttendanceRequest $request, string $scheduleId): RedirectResponse
    {
        [$teacher, $sch, , $today] = $this->authorizeSession($scheduleId);

        $schoolId = auth()->user()->school_id;
        $todayString = $today->toDateString();
        $data = $request->validated();

        // Daftar murid yang dikirim harus PERSIS sama dengan murid aktif kelas ini.
        $classIds = $this->classStudents($schoolId, $sch->class_id)->pluck('person_id')->all();
        $submittedIds = array_keys($data['students']);

        if (array_diff($submittedIds, $classIds) || array_diff($classIds, $submittedIds)) {
            return back()->withInput()->withErrors([
                'general' => 'Daftar murid tidak sesuai dengan kelas ini. Muat ulang halaman lalu coba lagi.',
            ]);
        }

        try {
            DB::transaction(function () use ($data, $teacher, $sch, $schoolId, $todayString) {
                $session = TeachingAttendance::query()
                    ->where('schedule_id', $sch->schedule_id)
                    ->where('date', $todayString)
                    ->lockForUpdate()
                    ->first();

                $payload = [
                    'teacher_id' => $teacher->id,
                    'topic' => filled($data['topic'] ?? null) ? $data['topic'] : null,
                    'notes' => filled($data['notes'] ?? null) ? $data['notes'] : null,
                ];

                if ($session) {
                    $session->update($payload);
                } else {
                    $session = TeachingAttendance::create(array_merge($payload, [
                        'school_id' => $schoolId,
                        'schedule_id' => $sch->schedule_id,
                        'date' => $todayString,
                        'start_time' => $sch->start_time,
                        'end_time' => $sch->end_time,
                        'created_at' => now(), // $timestamps = false di model
                    ]));
                }

                $existing = StudentSubjectAttendance::query()
                    ->where('teaching_attendance_id', $session->id)
                    ->get()
                    ->keyBy('student_id');

                foreach ($data['students'] as $studentId => $row) {
                    $record = $existing->get($studentId) ?? new StudentSubjectAttendance([
                        'school_id' => $schoolId,
                        'teaching_attendance_id' => $session->id,
                        'student_id' => $studentId,
                        'schedule_id' => $sch->schedule_id,
                        'date' => $todayString,
                        'created_at' => now(),
                    ]);

                    $record->status = $row['status'];
                    $record->notes = filled($row['notes'] ?? null) ? $row['notes'] : null;
                    $record->save();
                }
            });
        } catch (UniqueConstraintViolationException $e) {
            // Dua guru/dua tab menyimpan sesi yang sama pada saat bersamaan: unique index yang menang.
            return back()->withInput()->withErrors([
                'general' => 'Sesi ini sedang disimpan dari tempat lain. Muat ulang halaman, lalu coba lagi.',
            ]);
        } catch (QueryException $e) {
            report($e);

            return back()->withInput()->withErrors([
                'general' => 'Absensi gagal disimpan karena kesalahan database. Tidak ada data yang berubah; coba lagi atau hubungi admin.',
            ]);
        }

        return redirect()
            ->route('guru_mapel.absensi.sesi.edit', $scheduleId)
            ->with('success', 'Absensi jam pelajaran tersimpan. Anda bisa mengubahnya lagi selama hari ini.');
    }

    /**
     * @return array{0: Teacher, 1: SchedulesRef, 2: TeachingSubstitution|null, 3: Carbon}
     */
    private function authorizeSession(string $scheduleId): array
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

        $substitution = TeachingSubstitution::where('schedule_id', $scheduleId)
            ->where('date', $today->toDateString())
            ->first();

        // Boleh MELIHAT: guru pemilik jadwal atau pengganti hari ini (sama dengan detail()). Selain itu 404.
        $involved = $sch->teacher_id === $teacher->id
            || ($substitution && $substitution->substitute_teacher_id === $teacher->id);
        abort_if(! $involved, 404);

        // Boleh MENGISI: hanya guru efektif jam itu.
        $effectiveTeacherId = $substitution ? $substitution->substitute_teacher_id : $sch->teacher_id;
        if ($teacher->id !== $effectiveTeacherId) {
            $this->deny(
                redirect()->route('guru_mapel.absensi.detail', $scheduleId),
                'Jam ini digantikan guru lain hari ini. Hanya guru pengganti yang dapat mengisi absensinya.'
            );
        }

        if ((int) $sch->day_of_week !== $today->dayOfWeekIso || ! $sch->is_active) {
            $this->deny(
                redirect()->route('guru_mapel.absensi.index'),
                'Absensi hanya bisa diisi pada hari jadwalnya.'
            );
        }

        return [$teacher, $sch, $substitution, $today];
    }

    private function deny(RedirectResponse $redirect, string $message): never
    {
        throw new HttpResponseException($redirect->with('error', $message));
    }

    /** Murid aktif kelas dari DB absensi (sama dengan AbsensiController::detail()). person_id = students.id. */
    private function classStudents(string $schoolId, string $classId): Collection
    {
        return PeopleRef::where('school_id', $schoolId)
            ->where('person_type', 'student')
            ->where('class_id', $classId)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Status absensi harian yang dipakai sebagai nilai awal. Hanya Sakit/Izin/Alpa.
     * Kalau DB absensi tidak bisa dibaca, form tetap jalan dengan semua murid Hadir.
     *
     * @return array{0: array<string,string>, 1: bool} [person_id => status, daily_unavailable]
     */
    private function dailyPrefill(string $schoolId, array $personIds, string $date): array
    {
        if ($personIds === []) {
            return [[], false];
        }

        try {
            $map = AttendanceDaily::where('school_id', $schoolId)
                ->where('person_type', 'student')
                ->where('date', $date)
                ->whereIn('person_id', $personIds)
                ->whereIn('status', self::PREFILL_FROM_DAILY)
                ->pluck('status', 'person_id')
                ->all();

            return [$map, false];
        } catch (QueryException $e) {
            report($e);

            return [[], true];
        }
    }
}
