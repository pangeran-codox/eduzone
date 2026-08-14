<?php

namespace App\Console\Commands\Absensi;

use App\Models\Absensi\AttendanceDaily;
use App\Models\Absensi\AttendanceEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Tahap 1 sinkronisasi absensi: attendance_events (mentah, insert-only,
 * per-tap/scan) -> attendance_daily (rekap 1 baris per orang per hari).
 *
 * Cuma memproses orang yang PUNYA minimal 1 event hari itu — orang yang
 * sama sekali tidak punya event TIDAK dibuatkan baris "Alpa" di sini,
 * karena job ini tidak berwenang menyimpulkan itu. Alasannya:
 * event_type di attendance_events cuma check_in/check_out/unknown, tidak
 * ada kategori administratif (Izin/Sakit/Alpa) — itu keputusan manusia
 * (TU/wali kelas) yang belum ada modul inputnya. "Tidak ada event" di sini
 * cuma berarti "belum absen", bukan otomatis "Alpa".
 *
 * Konsekuensinya status di attendance_daily yang dihasilkan job ini SELALU
 * 'Hadir' — tidak pernah 'Terlambat' (belum ada config jam masuk sekolah
 * buat dibandingkan), 'Sakit'/'Izin'/'Alpa' (butuh input manual terpisah).
 * Kalau modul itu sudah ada, gabungkan hasilnya dengan baris yang dibuat
 * job ini saat proses sync ke DB utama (Tahap 2), jangan diubah di sini.
 */
class AggregateAttendanceDaily extends Command
{
    protected $signature = 'absensi:aggregate-daily {--date= : Tanggal (YYYY-MM-DD), default hari ini}';

    protected $description = 'Agregasi attendance_events mentah jadi rekap harian per orang di attendance_daily';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $events = AttendanceEvent::whereNotNull('person_id')
            ->whereBetween('recorded_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->orderBy('recorded_at')
            ->get();

        if ($events->isEmpty()) {
            $this->info("Tidak ada attendance_events untuk {$date->toDateString()}. Tidak ada yang diproses.");

            return self::SUCCESS;
        }

        // Group per (school_id, person_id, person_type) — pakai concat
        // string sebagai key groupBy karena Eloquent groupBy butuh key
        // tunggal, bukan kombinasi kolom.
        $grouped = $events->groupBy(fn ($event) => implode('|', [
            $event->school_id,
            $event->person_id,
            $event->person_type,
        ]));

        $count = 0;

        foreach ($grouped as $key => $personEvents) {
            [$schoolId, $personId, $personType] = explode('|', $key);

            $firstCheckIn = $personEvents->where('event_type', 'check_in')->first();
            $lastCheckOut = $personEvents->where('event_type', 'check_out')->last();
            $hasAnomaly = $personEvents->contains(fn ($e) => ! $e->is_valid || $e->flagged_reason);

            AttendanceDaily::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'person_id' => $personId,
                    'person_type' => $personType,
                    'date' => $date->toDateString(),
                ],
                [
                    'first_check_in' => $firstCheckIn?->recorded_at?->format('H:i:s'),
                    'last_check_out' => $lastCheckOut?->recorded_at?->format('H:i:s'),
                    'status' => 'Hadir',
                    'primary_method' => $firstCheckIn?->method ?? $personEvents->first()->method,
                    'total_events' => $personEvents->count(),
                    'has_anomaly' => $hasAnomaly,
                ]
            );

            $count++;
        }

        $this->info("Selesai — {$count} baris attendance_daily di-upsert untuk {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
