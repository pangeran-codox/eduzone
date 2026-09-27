<?php

namespace App\Console\Commands\Absensi;

use App\Models\Absensi\AttendanceDaily;
use App\Models\Absensi\ManualAttendanceOverride;
use App\Models\Absensi\SyncLog;
use App\Models\TeacherAttendance;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

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

                // Override manual TU (kalau ada) SELALU menang dibanding
                // hasil agregasi gateway - sama seperti versi siswa di
                // SyncAttendanceDailyToMain, lihat ReapplyManualAttendanceOverrides
                // untuk penjelasan lengkap.
                $override = ManualAttendanceOverride::where([
                    'school_id' => $row->school_id,
                    'person_id' => $row->person_id,
                    'person_type' => 'teacher',
                    'date' => $row->date->toDateString(),
                ])->first();

                $attendance->fill([
                    'check_in' => $row->first_check_in,
                    'check_out' => $row->last_check_out,
                    'status' => $override->status ?? $row->status,
                    'notes' => $override->notes
                        ?? ($row->notes
                            ?? ($row->has_anomaly
                                ? 'Ditandai anomali oleh sistem absensi — perlu verifikasi manual.'
                                : $attendance->notes)),
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