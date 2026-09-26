<?php

namespace App\Http\Controllers\Tenant\Tu;

use App\Exports\AttendanceReportExport;
use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Rekap historis absensi (rentang tanggal) untuk TU - ringkasan per orang,
 * BUKAN grid harian. Sengaja ringkasan: tidak ada tabel kalender
 * akademik/hari libur di sistem ini, jadi tidak bisa membedakan "tidak
 * ada baris karena libur" vs "tidak ada baris karena belum tercatat" per
 * tanggal spesifik - grid harian akan salah menandai weekend/libur
 * sebagai Alpa. Ringkasan menghindari masalah itu dengan menghitung
 * persentase dari BARIS YANG ADA saja, dan melaporkan sisanya sebagai
 * "tidak tercatat" (netral), bukan "Alpa".
 *
 * Sumber data: student_attendance/teacher_attendance di DB UTAMA (bukan
 * attendance_daily di eduzone_absensi) - ini tabel yang dituju sync job
 * absensi:sync-daily-to-main / sync-teacher-daily-to-main, dan memang
 * dirancang sebagai rumah jangka panjang data absensi di sisi Eduzone.
 */
class AttendanceReportController extends Controller
{
    private const STATUSES = ['Hadir', 'Terlambat', 'Sakit', 'Izin', 'Alpa'];

    public function index(Request $request)
    {
        $schoolId = $request->user()->school_id;
        [$startDate, $endDate] = $this->resolveRange($request);
        $personType = $request->query('person_type', 'student');
        $classId = $request->query('class_id');

        $classes = SchoolClass::where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('grade')
            ->orderBy('class_group')
            ->get();

        $rows = $this->buildReport($schoolId, $startDate, $endDate, $personType, $classId);

        return view('tenant.tu.laporan.index', [
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'personType' => $personType,
            'classes' => $classes,
            'selectedClassId' => $classId,
            'rows' => $rows,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $schoolId = $request->user()->school_id;
        [$startDate, $endDate] = $this->resolveRange($request);
        $personType = $request->query('person_type', 'student');
        $classId = $request->query('class_id');

        $rows = $this->buildReport($schoolId, $startDate, $endDate, $personType, $classId);
        $filename = "rekap-absensi-{$startDate->toDateString()}-sd-{$endDate->toDateString()}.xlsx";

        return Excel::download(new AttendanceReportExport($rows), $filename);
    }

    public function exportPdf(Request $request)
    {
        $schoolId = $request->user()->school_id;
        [$startDate, $endDate] = $this->resolveRange($request);
        $personType = $request->query('person_type', 'student');
        $classId = $request->query('class_id');

        $rows = $this->buildReport($schoolId, $startDate, $endDate, $personType, $classId);
        $filename = "rekap-absensi-{$startDate->toDateString()}-sd-{$endDate->toDateString()}.pdf";

        $pdf = Pdf::loadView('tenant.tu.laporan.pdf', [
            'rows' => $rows,
            'startDate' => $startDate->translatedFormat('d M Y'),
            'endDate' => $endDate->translatedFormat('d M Y'),
            'personType' => $personType,
            'schoolName' => $request->user()->school->name ?? '',
        ]);

        return $pdf->download($filename);
    }

    private function resolveRange(Request $request): array
    {
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        $startDate = $start && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)
            ? Carbon::parse($start)->startOfDay()
            : Carbon::today()->startOfMonth();

        $endDate = $end && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)
            ? Carbon::parse($end)->startOfDay()
            : Carbon::today();

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    private function buildReport(
        string $schoolId,
        Carbon $start,
        Carbon $end,
        string $personType,
        ?string $classId
    ): Collection {
        if ($personType === 'teacher') {
            $people = Teacher::where('school_id', $schoolId)
                ->where('is_active', true)
                ->orderBy('full_name')
                ->get(['id', 'full_name']);

            $attendance = TeacherAttendance::where('school_id', $schoolId)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->groupBy('teacher_id');
        } else {
            $peopleQuery = Student::where('school_id', $schoolId)->where('status', 'aktif');

            if ($classId) {
                $peopleQuery->where('class_id', $classId);
            }

            $people = $peopleQuery->orderBy('full_name')->get(['id', 'full_name']);

            $attendance = StudentAttendance::where('school_id', $schoolId)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->whereIn('student_id', $people->pluck('id'))
                ->get()
                ->groupBy('student_id');
        }

        $totalCalendarDays = $start->diffInDays($end) + 1;

        return $people->map(function ($person) use ($attendance, $totalCalendarDays) {
            $records = $attendance->get($person->id, collect());

            $counts = array_fill_keys(self::STATUSES, 0);
            foreach ($records as $r) {
                if (isset($counts[$r->status])) {
                    $counts[$r->status]++;
                }
            }

            $totalRecorded = $records->count();
            $hadirEfektif = $counts['Hadir'] + $counts['Terlambat'];

            return [
                'nama' => $person->full_name,
                'counts' => $counts,
                'total_recorded' => $totalRecorded,
                'tidak_tercatat' => max($totalCalendarDays - $totalRecorded, 0),
                'percentage' => $totalRecorded > 0 ? round($hadirEfektif / $totalRecorded * 100, 1) : 0.0,
            ];
        })->values();
    }
}