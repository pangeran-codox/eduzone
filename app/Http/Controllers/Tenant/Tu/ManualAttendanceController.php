<?php

namespace App\Http\Controllers\Tenant\Tu;

use App\Http\Controllers\Controller;
use App\Models\Absensi\AttendanceDaily;
use App\Models\Absensi\PeopleRef;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Input manual status Izin/Sakit/Alpa oleh TU — untuk kasus yang tidak
 * mungkin terdeteksi perangkat check-in (siswa/guru tidak masuk sama
 * sekali). Ditulis ke attendance_daily (bukan langsung ke
 * student_attendance/teacher_attendance di DB utama) supaya:
 *   1. Otomatis kebaca dashboard live TU & Kepsek (yang baca tabel ini)
 *   2. Otomatis ikut ke-sync ke DB utama lewat job yang sudah ada
 *      (absensi:sync-daily-to-main / sync-teacher-daily-to-main)
 *
 * Scope SENGAJA cuma siswa & guru — staff_attendance belum ada di DB
 * utama (dikonfirmasi 23 Sep 2026), jadi staff belum bisa diproses di sini.
 *
 * BATASAN DIKETAHUI: kalau device sempat merekam check_in untuk orang
 * yang sama di tanggal yang sama SETELAH input manual ini, job agregasi
 * gateway (Go) berpotensi menimpa status balik jadi "Hadir" di siklus
 * berikutnya. Edge case jarang, belum ditangani.
 */
class ManualAttendanceController extends Controller
{
    private const PERSON_TYPES = ['student', 'teacher'];
    private const MANUAL_STATUSES = ['Sakit', 'Izin', 'Alpa'];

    public function index(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $date = $this->resolveDate($request);

        $people = PeopleRef::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereIn('person_type', self::PERSON_TYPES)
            ->orderBy('full_name')
            ->get(['person_id', 'person_type', 'full_name', 'grade']);

        $existing = AttendanceDaily::query()
            ->where('school_id', $schoolId)
            ->where('date', $date->toDateString())
            ->whereIn('status', self::MANUAL_STATUSES)
            ->orderByDesc('updated_at')
            ->get();

        $existingWithNames = $existing->map(function ($row) use ($people) {
            $person = $people->firstWhere(fn ($p) => $p->person_id === $row->person_id && $p->person_type === $row->person_type);

            return [
                'person_id' => $row->person_id,
                'person_type' => $row->person_type,
                'full_name' => $person->full_name ?? '(tidak ditemukan)',
                'status' => $row->status,
                'notes' => $row->notes,
            ];
        });

        return view('tenant.tu.absensi.manual', [
            'date' => $date->toDateString(),
            'people' => $people,
            'existing' => $existingWithNames,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'person_type' => ['required', Rule::in(self::PERSON_TYPES)],
            'person_id' => ['required', 'uuid'],
            'status' => ['required', Rule::in(self::MANUAL_STATUSES)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $schoolId = $request->user()->school_id;

        $personExists = PeopleRef::query()
            ->where('school_id', $schoolId)
            ->where('person_id', $validated['person_id'])
            ->where('person_type', $validated['person_type'])
            ->where('is_active', true)
            ->exists();

        if (! $personExists) {
            return back()->withErrors([
                'person_id' => 'Orang yang dipilih tidak ditemukan atau tidak aktif di sekolah ini.',
            ]);
        }

        $daily = AttendanceDaily::firstOrNew([
            'school_id' => $schoolId,
            'person_id' => $validated['person_id'],
            'person_type' => $validated['person_type'],
            'date' => $validated['date'],
        ]);

        if (! $daily->exists) {
            $daily->total_events = 0;
            $daily->has_anomaly = false;
        }

        $daily->status = $validated['status'];
        $daily->notes = $validated['notes'] ?? null;
        $daily->save();

        return back()->with('success', 'Status kehadiran berhasil dicatat.');
    }

    private function resolveDate(Request $request): Carbon
    {
        $raw = $request->query('date');

        if ($raw && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $raw)->startOfDay();
            } catch (\Exception) {
                //
            }
        }

        return Carbon::today();
    }
}