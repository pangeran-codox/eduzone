<?php

namespace App\Http\Controllers\Tenant\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Absensi\AttendanceEvent;
use App\Models\Absensi\PeopleRef;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Rekap absensi sekolah-wide (semua kelas) untuk Kepsek & TU — status hari
 * ini per kelas.
 *
 * Sama seperti dashboard Wali Kelas (App\Http\Controllers\Tenant\WaliKelas\
 * AbsensiController) — query langsung attendance_events mentah, BELUM ada
 * job sync attendance_daily. Status cuma Hadir/Belum Absen + penanda
 * Anomali. TIDAK ada Izin/Sakit/Alpa/Terlambat (lihat catatan di
 * AbsensiController wali kelas untuk alasan lengkap).
 *
 * Kalau nanti ada job sync attendance_daily, controller ini + dashboard
 * wali kelas perlu direvisi bareng buat baca dari sana.
 */
class RekapController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $today = Carbon::today();

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

        $eventsByStudent = AttendanceEvent::where('school_id', $schoolId)
            ->where('person_type', 'student')
            ->whereIn('person_id', $allStudentIds)
            ->whereBetween('recorded_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->orderBy('recorded_at')
            ->get()
            ->groupBy('person_id');

        $rows = $classes->map(function ($class) use ($studentsByClass, $eventsByStudent) {
            $classStudents = $studentsByClass->get($class->id, collect());

            $hadir = 0;
            $anomali = 0;

            foreach ($classStudents as $student) {
                $studentEvents = $eventsByStudent->get($student->person_id, collect());
                $checkIn = $studentEvents->firstWhere('event_type', 'check_in');

                if ($checkIn) {
                    $hadir++;
                }

                if ($studentEvents->contains(fn ($e) => ! $e->is_valid || $e->flagged_reason)) {
                    $anomali++;
                }
            }

            $total = $classStudents->count();

            return [
                'nama_kelas' => $class->nama_kelas,
                'total' => $total,
                'hadir' => $hadir,
                'belum' => $total - $hadir,
                'anomali' => $anomali,
                'persen' => $total > 0 ? round($hadir / $total * 100) : 0,
            ];
        });

        $totals = [
            'total' => $rows->sum('total'),
            'hadir' => $rows->sum('hadir'),
            'belum' => $rows->sum('belum'),
            'anomali' => $rows->sum('anomali'),
        ];
        $totals['persen'] = $totals['total'] > 0 ? round($totals['hadir'] / $totals['total'] * 100) : 0;

        return view('tenant.absensi.rekap', [
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }
}