<?php

namespace App\Http\Controllers\Tenant\Kepsek;

use App\Http\Controllers\Controller;
use App\Models\Absensi\AttendanceDaily;
use App\Models\Absensi\PeopleRef;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceMonitorController extends Controller
{
    private const STATUSES = ['Hadir', 'Terlambat', 'Sakit', 'Izin', 'Alpa'];

    public function index(Request $request): View
    {
        $date = $this->resolveDate($request);

        return view('tenant.kepsek.absensi.index', [
            'date' => $date->toDateString(),
            'initial' => $this->buildPayload($request->user()->school_id, $date),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $date = $this->resolveDate($request);

        return response()->json(
            $this->buildPayload($request->user()->school_id, $date)
        );
    }

    private function resolveDate(Request $request): Carbon
    {
        $raw = $request->query('date');

        if ($raw && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $raw)->startOfDay();
            } catch (\Exception) {
                // format valid tapi tanggal invalid -> fallback ke hari ini
            }
        }

        return Carbon::today();
    }

    private function buildPayload(string $schoolId, Carbon $date): array
    {
        $people = PeopleRef::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->get(['person_id', 'person_type', 'full_name', 'grade']);

        $peopleById = $people->keyBy(fn ($p) => $p->person_type . ':' . $p->person_id);

        $daily = AttendanceDaily::query()
            ->where('school_id', $schoolId)
            ->where('date', $date->toDateString())
            ->get();

        $statusCounts = array_fill_keys(self::STATUSES, 0);
        $recent = [];

        foreach ($daily as $row) {
            $statusCounts[$row->status] = ($statusCounts[$row->status] ?? 0) + 1;

            if ($row->first_check_in) {
                $person = $peopleById->get($row->person_type . ':' . $row->person_id);

                $recent[] = [
                    'person_id' => $row->person_id,
                    'person_type' => $row->person_type,
                    'full_name' => $person->full_name ?? '(tidak ditemukan di cache)',
                    'grade' => $person->grade ?? null,
                    'status' => $row->status,
                    'first_check_in' => $row->first_check_in,
                    'has_anomaly' => (bool) $row->has_anomaly,
                ];
            }
        }

        usort($recent, fn ($a, $b) => strcmp($b['first_check_in'] ?? '', $a['first_check_in'] ?? ''));
        $recent = array_slice($recent, 0, 15);

        $totalPeople = $people->count();
        $checkedInCount = $daily->count();

        return [
            'date' => $date->toDateString(),
            'total_people' => $totalPeople,
            'checked_in_count' => $checkedInCount,
            'not_checked_in_count' => max($totalPeople - $checkedInCount, 0),
            'attendance_percentage' => $totalPeople > 0
                ? round($checkedInCount / $totalPeople * 100, 1)
                : 0.0,
            'status_counts' => $statusCounts,
            'recent' => $recent,
            'generated_at' => now()->toDateTimeString(),
        ];
    }
}