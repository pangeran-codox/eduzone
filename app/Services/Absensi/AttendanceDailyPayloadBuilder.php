<?php

namespace App\Services\Absensi;

use App\Models\Absensi\AttendanceDaily;
use App\Models\Absensi\PeopleRef;
use Illuminate\Support\Carbon;

/**
 * Logic bersama untuk dashboard live Kepsek & TU — sebelumnya duplikat
 * persis di Kepsek\AttendanceMonitorController dan
 * Tu\AttendanceDashboardController. Diekstrak 25 Sep 2026, TIDAK
 * mengubah payload yang dihasilkan tiap controller (parameter yang
 * lewat menjaga behavior lama identik).
 */
class AttendanceDailyPayloadBuilder
{
    private const STATUSES = ['Hadir', 'Terlambat', 'Sakit', 'Izin', 'Alpa'];

    /**
     * @param int|null $notCheckedInLimit null = jangan sertakan daftar
     *   "belum absen" sama sekali (dipakai Kepsek); angka = sertakan,
     *   dibatasi sejumlah itu (dipakai TU)
     */
    public function build(
        string $schoolId,
        Carbon $date,
        ?string $personType = null,
        int $recentLimit = 20,
        ?int $notCheckedInLimit = null,
    ): array {
        $peopleQuery = PeopleRef::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true);

        if ($personType) {
            $peopleQuery->where('person_type', $personType);
        }

        $people = $peopleQuery->get(['person_id', 'person_type', 'full_name', 'grade']);
        $peopleById = $people->keyBy(fn ($p) => $p->person_type . ':' . $p->person_id);

        $dailyQuery = AttendanceDaily::query()
            ->where('school_id', $schoolId)
            ->where('date', $date->toDateString());

        if ($personType) {
            $dailyQuery->where('person_type', $personType);
        }

        $daily = $dailyQuery->get();

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
                    'last_check_out' => $row->last_check_out,
                    'has_anomaly' => (bool) $row->has_anomaly,
                ];
            }
        }

        usort($recent, fn ($a, $b) => strcmp($b['first_check_in'] ?? '', $a['first_check_in'] ?? ''));
        $recent = array_slice($recent, 0, $recentLimit);

        $totalPeople = $people->count();
        $checkedInCount = $daily->count();

        $payload = [
            'date' => $date->toDateString(),
            'person_type' => $personType,
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

        if ($notCheckedInLimit !== null) {
            $dailyByKey = $daily->keyBy(fn ($r) => $r->person_type . ':' . $r->person_id);

            $notCheckedIn = $people
                ->reject(fn ($p) => $dailyByKey->has($p->person_type . ':' . $p->person_id))
                ->map(fn ($p) => [
                    'person_id' => $p->person_id,
                    'person_type' => $p->person_type,
                    'full_name' => $p->full_name,
                    'grade' => $p->grade,
                ])
                ->values();

            $payload['not_checked_in'] = $notCheckedIn->take($notCheckedInLimit)->values();
        }

        return $payload;
    }
}