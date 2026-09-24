<?php

namespace App\Services\Absensi;

use App\Models\Absensi\AttendanceDaily;
use App\Models\Absensi\PeopleRef;
use App\Models\School;
use Illuminate\Support\Carbon;

/**
 * Rekap kehadiran hari ini LINTAS SEKOLAH untuk dashboard Superadmin.
 * Beda dari AttendanceOverviewService versi TU/Kepsek yang scoped ke
 * 1 sekolah - di sini sengaja query semua sekolah aktif sekaligus,
 * PeopleRef/AttendanceDaily di-groupBy school_id lalu digabung ke School
 * (DB utama) di level PHP, karena dua-duanya beda connection (pgsql vs
 * pgsql_absensi), tidak bisa JOIN SQL langsung.
 */
class AttendanceOverviewService
{
    public function todayReport(): array
    {
        $today = Carbon::today();

        $schools = School::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $peopleCounts = PeopleRef::where('is_active', true)
            ->selectRaw('school_id, count(*) as total')
            ->groupBy('school_id')
            ->pluck('total', 'school_id');

        $dailyCounts = AttendanceDaily::where('date', $today->toDateString())
            ->selectRaw('school_id, count(*) as total')
            ->groupBy('school_id')
            ->pluck('total', 'school_id');

        $anomalyCounts = AttendanceDaily::where('date', $today->toDateString())
            ->where('has_anomaly', true)
            ->selectRaw('school_id, count(*) as total')
            ->groupBy('school_id')
            ->pluck('total', 'school_id');

        $rows = $schools->map(function ($school) use ($peopleCounts, $dailyCounts, $anomalyCounts) {
            $totalPeople = (int) ($peopleCounts[$school->id] ?? 0);
            $checkedIn = (int) ($dailyCounts[$school->id] ?? 0);

            return [
                'school_id' => $school->id,
                'school_name' => $school->name,
                'total_people' => $totalPeople,
                'checked_in' => $checkedIn,
                'not_checked_in' => max($totalPeople - $checkedIn, 0),
                'percentage' => $totalPeople > 0 ? round($checkedIn / $totalPeople * 100, 1) : 0.0,
                'anomali' => (int) ($anomalyCounts[$school->id] ?? 0),
            ];
        })->values();

        $totals = [
            'total_people' => $rows->sum('total_people'),
            'checked_in' => $rows->sum('checked_in'),
            'anomali' => $rows->sum('anomali'),
        ];
        $totals['percentage'] = $totals['total_people'] > 0
            ? round($totals['checked_in'] / $totals['total_people'] * 100, 1)
            : 0.0;

        return [
            'date' => $today->toDateString(),
            'schools' => $rows,
            'totals' => $totals,
            'checked_at' => now()->toIso8601String(),
        ];
    }
}