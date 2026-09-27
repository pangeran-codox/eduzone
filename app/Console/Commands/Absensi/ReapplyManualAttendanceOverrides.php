<?php

namespace App\Console\Commands\Absensi;

use App\Models\Absensi\AttendanceDaily;
use App\Models\Absensi\ManualAttendanceOverride;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Perbaikan race condition yang didokumentasikan di
 * ManualAttendanceController: agregasi real-time gateway bisa menimpa
 * status manual (Sakit/Izin/Alpa) balik jadi "Hadir" kalau device sempat
 * merekam check-in di hari yang sama SETELAH input manual dibuat.
 *
 * Jalan periodik, baca manual_attendance_overrides (sumber kebenaran)
 * dan paksa ulang attendance_daily.status/notes supaya cocok, kalau
 * sempat ditimpa. Cuma sentuh status & notes - first_check_in/
 * last_check_out/total_events/has_anomaly dari gateway dibiarkan apa
 * adanya (tetap informatif meski status resminya beda).
 */
class ReapplyManualAttendanceOverrides extends Command
{
    protected $signature = 'absensi:reapply-manual-overrides {--days=2 : Jumlah hari ke belakang yang dicek}';

    protected $description = 'Perbaiki attendance_daily yang tertimpa gateway, kembalikan sesuai manual_attendance_overrides';

    public function handle(): int
    {
        $since = Carbon::today()->subDays((int) $this->option('days'));

        $overrides = ManualAttendanceOverride::where('date', '>=', $since->toDateString())->get();

        if ($overrides->isEmpty()) {
            $this->info('Tidak ada override manual dalam rentang yang dicek.');

            return self::SUCCESS;
        }

        $fixed = 0;

        foreach ($overrides as $override) {
            $daily = AttendanceDaily::firstOrNew([
                'school_id' => $override->school_id,
                'person_id' => $override->person_id,
                'person_type' => $override->person_type,
                'date' => $override->date->toDateString(),
            ]);

            $previousStatus = $daily->exists ? $daily->status : null;

            $needsFix = ! $daily->exists
                || $daily->status !== $override->status
                || $daily->notes !== $override->notes;

            if (! $needsFix) {
                continue;
            }

            if (! $daily->exists) {
                $daily->total_events = 0;
                $daily->has_anomaly = false;
            }

            $daily->status = $override->status;
            $daily->notes = $override->notes;
            $daily->save();

            $fixed++;

            $this->warn("Diperbaiki: {$override->person_type} {$override->person_id} ({$override->date->toDateString()}) — tertimpa jadi '{$previousStatus}', dikembalikan ke '{$override->status}'");
        }

        $this->info("Selesai — {$fixed} baris diperbaiki dari {$overrides->count()} override yang dicek.");

        return self::SUCCESS;
    }
}