<?php

namespace App\Http\Controllers\Tenant\Tu;

use App\Http\Controllers\Controller;
use App\Models\Absensi\AttendanceDaily;
use App\Models\Absensi\PeopleRef;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceDashboardController extends Controller
{
    private const PERSON_TYPES = ['student', 'teacher', 'staff'];
    private const STATUSES = ['Hadir', 'Terlambat', 'Sakit', 'Izin', 'Alpa'];

    public function index(Request $request): View
    {
        $date = $this->resolveDate($request);
        $personType = $this->resolvePersonType($request);

        return view('tenant.tu.absensi.index', [
            'date' => $date->toDateString(),
            'personType' => $personType,
            'initial' => $this->buildPayload($request->user()->school_id, $date, $personType),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $date = $this->resolveDate($request);
        $personType = $this->resolvePersonType($request);

        return response()->json(
            $this->buildPayload($request->user()->school_id, $date, $personType)
        );
    }

    private function resolveDate(Request $request): Carbon
    {
        $raw = $request->query('date');

        if ($raw && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $raw)->startOfDay();
            } catch (\Exception) {
                // format valid tapi tanggal invalid (mis. 2026-02-31) -> fallback ke hari ini
            }
        }

        return Carbon::today();
    }

    private function resolvePersonType(Request $request): ?string
    {
        $type = $request->query('person_type');

        return in_array($type, self::PERSON_TYPES, true) ? $type : null;
    }

    private function buildPayload(string $schoolId, Carbon $date, ?string $personType): array
    {
        $peopleQuery = PeopleRef::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true);

        if ($personType) {
            $peopleQuery->where('person_type', $personType);
        }

        $people = $peopleQuery->get(['person_id', 'person_type', 'full_name', 'grade']);

        $dailyQuery = AttendanceDaily::query()
            ->where('school_id', $schoolId)
            ->where('date', $date->toDateString());

        if ($personType) {
            $dailyQuery->where('person_type', $personType);
        }

        $daily = $dailyQuery->get()
            ->keyBy(fn ($row) => $row->person_type . ':' . $row->person_id);

        $peopleById = $people->keyBy(fn ($p) => $p->person_type . ':' . $p->person_id);

        $statusCounts = array_fill_keys(self::STATUSES, 0);
        $recent = [];

        foreach ($daily as $key => $row) {
            $statusCounts[$row->status] = ($statusCounts[$row->status] ?? 0) + 1;

            if ($row->first_check_in) {
                $person = $peopleById->get($key);

                $recent[] = [
                    'person_id' => $row->person_id,
                    'person_type' => $row->person_type,
                    'full_name' => $person->full_name ?? '(tidak ditemukan di cache)',
                    'grade' => $person->grade ?? null,
                    'status' => $row->status,
                    'first_check_in' => $row->first_check_in,
                    'last_check_out' => $row->last_check_out,
                    'has_anomaly' => (bool) $row->has_anomaly,
                ];
            }
        }

        usort($recent, fn ($a, $b) => strcmp($b['first_check_in'] ?? '', $a['first_check_in'] ?? ''));
        $recent = array_slice($recent, 0, 20);

        $notCheckedIn = $people
            ->reject(fn ($p) => $daily->has($p->person_type . ':' . $p->person_id))
            ->map(fn ($p) => [
                'person_id' => $p->person_id,
                'person_type' => $p->person_type,
                'full_name' => $p->full_name,
                'grade' => $p->grade,
            ])
            ->values();

        return [
            'date' => $date->toDateString(),
            'person_type' => $personType,
            'total_people' => $people->count(),
            'status_counts' => $statusCounts,
            'not_checked_in_count' => $notCheckedIn->count(),
            'not_checked_in' => $notCheckedIn->take(50)->values(),
            'recent' => $recent,
            'generated_at' => now()->toDateTimeString(),
        ];
    }
}