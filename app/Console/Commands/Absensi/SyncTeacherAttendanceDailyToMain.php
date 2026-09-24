<?php

namespace App\Console\Commands\Absensi;

use App\Models\Absensi\AttendanceDaily;
use App\Models\Absensi\SyncLog;
use App\Models\TeacherAttendance;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Tahap 2 sinkronisasi absensi guru: attendance_daily (pgsql_absensi) ->
 * teacher_attendance (DB utama), tercatat di sync_log.
 *
 * Duplikat dari SyncAttendanceDailyToMain, scope person_type='teacher'.
 * Lihat docblock command itu untuk detail idempotency & catatan race
 * condition (unique constraint) yang sama berlaku di sini.
 */
class SyncTeacherAttendanceDailyToMain extends Command
{
    protected $signature = 'absensi:sync-teacher-daily-to-main {--date= : Tanggal (YYYY-MM-DD), default hari ini}';

    protected $description = 'Sync attendance_daily guru (pgsql_absensi) ke teacher_attendance (DB utama), tercatat di sync_log';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $rows = AttendanceDaily::where('date', $date->toDateString())
            ->where('person_type', 'teacher')
            ->get();

        if ($rows->isEmpty()) {
            $this->info("Tidak ada attendance_daily guru untuk {$date->toDateString()}. Tidak ada yang di-sync.");

            return self::SUCCESS;
        }

        $synced = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $log = SyncLog::create([
                'source_table' => 'attendance_daily',
                'source_id' => $row->id,
                'target_table' => 'teacher_attendance',
                'status' => 'pending',
                'attempted_at' => now(),
            ]);

            try {
                $attendance = TeacherAttendance::firstOrNew([
                    'school_id' => $row->school_id,
                    'teacher_id' => $row->person_id,
                    'date' => $row->date->toDateString(),
                ]);

                $attendance->fill([
                    'check_in' => $row->first_check_in,
                    'check_out' => $row->last_check_out,
                    'status' => $row->status,
                    'notes' => $row->notes
                        ?? ($row->has_anomaly
                            ? 'Ditandai anomali oleh sistem absensi — perlu verifikasi manual.'
                            : $attendance->notes),
                ]);
                $attendance->save();

                $log->update(['status' => 'success', 'synced_at' => now()]);
                $synced++;
            } catch (\Throwable $e) {
                $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
                $failed++;
                $this->error("Gagal sync attendance_daily {$row->id}: {$e->getMessage()}");
            }
        }

        $this->info("Selesai — {$synced} berhasil, {$failed} gagal untuk {$date->toDateString()}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}