<?php

namespace App\Http\Controllers\Tenant\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Teacher;
use App\Models\TeachingSubstitution;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Penugasan guru pengganti per tanggal, dikelola Kurikulum.
 *
 * Jadwal reguler (schedules) TIDAK diubah - yang dicatat cuma
 * pengecualian per tanggal di teaching_substitutions. Nilai kolom
 * schedules.day berupa nama hari Indonesia ('Senin'..'Minggu'), sama
 * dengan pemetaan di Api\SyncController.
 */
class SubstitutionController extends Controller
{
    private const DAY_NAMES = [
        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
        5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
    ];

    public function index(Request $request)
    {
        $date = $this->resolveDate($request);
        $dayName = self::DAY_NAMES[$date->dayOfWeekIso];

        $schedules = Schedule::where('is_active', true)
            ->where('day', $dayName)
            ->whereNotNull('teacher_id')
            ->orderBy('start_time')
            ->orderBy('grade')
            ->orderBy('major')
            ->orderBy('class_group')
            ->get();

        // Semua guru (termasuk nonaktif) buat lookup nama; cuma yang aktif
        // yang boleh dipilih sebagai pengganti.
        $teacherNames = Teacher::pluck('full_name', 'id');
        $teachers = Teacher::where('is_active', true)->orderBy('full_name')->get(['id', 'full_name']);

        $substitutions = TeachingSubstitution::where('date', $date->toDateString())
            ->get()
            ->keyBy('schedule_id');

        $rows = $schedules->map(function ($s) use ($substitutions, $teacherNames) {
            $sub = $substitutions->get($s->id);

            return [
                'schedule_id' => $s->id,
                'time' => substr($s->start_time, 0, 5) . ' – ' . substr($s->end_time, 0, 5),
                'class_name' => trim("{$s->grade} {$s->major} {$s->class_group}"),
                'subject' => $s->subject,
                'original_teacher_id' => $s->teacher_id,
                'original_teacher' => $teacherNames->get($s->teacher_id, '(guru tidak ditemukan)'),
                'substitution' => $sub ? [
                    'id' => $sub->id,
                    'teacher' => $teacherNames->get($sub->substitute_teacher_id, '(guru tidak ditemukan)'),
                    'reason' => $sub->reason,
                ] : null,
            ];
        });

        return view('tenant.kurikulum.pengganti.index', [
            'date' => $date->toDateString(),
            'dayName' => $dayName,
            'rows' => $rows,
            'teachers' => $teachers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => ['required', 'uuid'],
            'date' => ['required', 'date_format:Y-m-d'],
            'substitute_teacher_id' => ['required', 'uuid'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $schedule = Schedule::where('is_active', true)->find($validated['schedule_id']);

        if (! $schedule || ! $schedule->teacher_id) {
            return back()->withErrors(['schedule_id' => 'Jadwal tidak ditemukan atau tidak aktif.']);
        }

        $date = Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay();
        $dayName = self::DAY_NAMES[$date->dayOfWeekIso];

        if ($dayName !== $schedule->day) {
            return back()->withErrors([
                'date' => "Jadwal ini hari {$schedule->day}, tapi tanggal yang dipilih jatuh di hari {$dayName}.",
            ]);
        }

        if ($validated['substitute_teacher_id'] === $schedule->teacher_id) {
            return back()->withErrors(['substitute_teacher_id' => 'Guru pengganti tidak boleh sama dengan guru asli.']);
        }

        $substitute = Teacher::where('is_active', true)->find($validated['substitute_teacher_id']);

        if (! $substitute) {
            return back()->withErrors(['substitute_teacher_id' => 'Guru pengganti tidak ditemukan atau tidak aktif.']);
        }

        TeachingSubstitution::updateOrCreate(
            [
                'schedule_id' => $schedule->id,
                'date' => $date->toDateString(),
            ],
            [
                'school_id' => $request->user()->school_id,
                'original_teacher_id' => $schedule->teacher_id,
                'substitute_teacher_id' => $substitute->id,
                'reason' => $validated['reason'] ?? null,
                'recorded_by' => $request->user()->id,
            ]
        );

        $redirect = redirect()
            ->route('kurikulum.pengganti.index', ['date' => $date->toDateString()])
            ->with('success', "{$substitute->full_name} ditugaskan menggantikan pada {$date->toDateString()}.");

        // Peringatan (tidak memblokir) kalau pengganti sudah punya jadwal
        // lain yang bentrok jamnya di hari yang sama.
        $clash = Schedule::where('is_active', true)
            ->where('teacher_id', $substitute->id)
            ->where('day', $schedule->day)
            ->where('id', '!=', $schedule->id)
            ->where('start_time', '<', $schedule->end_time)
            ->where('end_time', '>', $schedule->start_time)
            ->exists();

        if ($clash) {
            $redirect->with('warning', "Perhatian: {$substitute->full_name} sudah punya jadwal mengajar lain yang bentrok di jam ini.");
        }

        return $redirect;
    }

    public function destroy(Request $request, string $id)
    {
        abort_if(! Str::isUuid($id), 404);

        $substitution = TeachingSubstitution::findOrFail($id);
        $date = $substitution->date->toDateString();
        $substitution->delete();

        return redirect()
            ->route('kurikulum.pengganti.index', ['date' => $date])
            ->with('success', 'Penggantian dibatalkan.');
    }

    private function resolveDate(Request $request): Carbon
    {
        $raw = $request->query('date');

        if ($raw && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $raw)->startOfDay();
            } catch (\Exception) {
                // tanggal tidak valid -> jatuh ke hari ini
            }
        }

        return Carbon::today();
    }
}