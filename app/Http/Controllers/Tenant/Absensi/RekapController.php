<?php

namespace App\Http\Controllers\Tenant\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Absensi\AttendanceDaily;
use App\Models\Absensi\PeopleRef;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Rekap absensi sekolah-wide (semua kelas) untuk Kepsek & TU — per kelas,
 * bisa filter tanggal.
 *
 * DIUPGRADE 25 Sep 2026: sebelumnya query attendance_events mentah (cuma
 * Hadir/Belum Absen), sekarang baca AttendanceDaily — dapat 5 status
 * penuh (Hadir/Terlambat/Sakit/Izin/Alpa), konsisten dengan dashboard
 * live Kepsek/TU/Wali Kelas. Filter tanggal ditambahkan sekaligus.
 */
class RekapController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $date = $this->resolveDate($request);

        $classes = SchoolClass::where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('grade')
            ->orderBy('class_group')
            ->get();

        // PeopleRef pakai composite primary key (person_id, person_type) —
        // JANGAN ::find(), tapi groupBy() di atas Collection biasa aman.
        $studentsByClass = PeopleRef::where('school_id', $schoolId)
            ->where('person_type', 'student')
            ->where('is_active', true)
            ->get()
            ->groupBy('class_id');

        $allStudentIds = $studentsByClass->flatten()->pluck('person_id');

        $dailyByStudent = AttendanceDaily::where('school_id', $schoolId)
            ->where('person_type', 'student')
            ->where('date', $date->toDateString())
            ->whereIn('person_id', $allStudentIds)
            ->get()
            ->keyBy('person_id');

        $rows = $classes->map(function ($class) use ($studentsByClass, $dailyByStudent) {
            $classStudents = $studentsByClass->get($class->id, collect());

            $statusCounts = ['Hadir' => 0, 'Terlambat' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alpa' => 0];
            $anomali = 0;
            $belum = 0;

            foreach ($classStudents as $student) {
                $row = $dailyByStudent->get($student->person_id);

                if ($row) {
                    $statusCounts[$row->status] = ($statusCounts[$row->status] ?? 0) + 1;

                    if ($row->has_anomaly) {
                        $anomali++;
                    }
                } else {
                    $belum++;
                }
            }

            $total = $classStudents->count();
            $hadir = $statusCounts['Hadir'] + $statusCounts['Terlambat'];

            return [
                'nama_kelas' => $class->nama_kelas,
                'total' => $total,
                'hadir' => $hadir,
                'terlambat' => $statusCounts['Terlambat'],
                'sakit' => $statusCounts['Sakit'],
                'izin' => $statusCounts['Izin'],
                'alpa' => $statusCounts['Alpa'],
                'belum' => $belum,
                'anomali' => $anomali,
                'persen' => $total > 0 ? round($hadir / $total * 100) : 0,
            ];
        });

        $totals = [
            'total' => $rows->sum('total'),
            'hadir' => $rows->sum('hadir'),
            'terlambat' => $rows->sum('terlambat'),
            'sakit' => $rows->sum('sakit'),
            'izin' => $rows->sum('izin'),
            'alpa' => $rows->sum('alpa'),
            'belum' => $rows->sum('belum'),
            'anomali' => $rows->sum('anomali'),
        ];
        $totals['persen'] = $totals['total'] > 0 ? round($totals['hadir'] / $totals['total'] * 100) : 0;

        return view('tenant.absensi.rekap', [
            'date' => $date->toDateString(),
            'rows' => $rows,
            'totals' => $totals,
        ]);
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