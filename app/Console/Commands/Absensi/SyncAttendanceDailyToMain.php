<?php

namespace App\Console\Commands\Absensi;

use App\Models\Absensi\AttendanceDaily;
use App\Models\Absensi\SyncLog;
use App\Models\StudentAttendance;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Tahap 2 sinkronisasi absensi: attendance_daily (pgsql_absensi) ->
 * student_attendance (DB utama), tercatat di sync_log.
 *
 * SCOPE SEKARANG CUMA SISWA (person_type = 'student') — migration
 * teacher_attendance belum ada/belum dibagikan. Kalau nanti ada, tinggal
 * duplikasi command ini (atau parameterize target model) buat guru.
 *
 * person_id di attendance_daily == students.id di DB utama (PeopleRef
 * adalah cache read-only satu arah dari tabel students, jadi ID-nya
 * identik, bukan mapping terpisah).
 *
 * Idempotent lewat updateOrCreate berdasarkan (school_id, student_id,
 * date) — TAPI migration student_attendance tidak punya unique constraint
 * di kombinasi itu (cuma primary key UUID biasa). Job ini aman dijalankan
 * berulang selama TIDAK dijalankan concurrent (dua proses bersamaan bisa
 * balapan insert duplikat). Kalau butuh jaminan lebih ketat, tambahkan
 * migration unique index (school_id, student_id, date) di student_attendance.
 */
class SyncAttendanceDailyToMain extends Command
{
    protected $signature = 'absensi:sync-daily-to-main {--date= : Tanggal (YYYY-MM-DD), default hari ini}';

    protected $description = 'Sync attendance_daily siswa (pgsql_absensi) ke student_attendance (DB utama), tercatat di sync_log';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $rows = AttendanceDaily::where('date', $date->toDateString())
            ->where('person_type', 'student')
            ->get();

        if ($rows->isEmpty()) {
            $this->info("Tidak ada attendance_daily siswa untuk {$date->toDateString()}. Tidak ada yang di-sync.");

            return self::SUCCESS;
        }

        $synced = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $log = SyncLog::create([
                'source_table' => 'attendance_daily',
                'source_id' => $row->id,
                'target_table' => 'student_attendance',
                'status' => 'pending',
                'attempted_at' => now(),
            ]);

            try {
                $attendance = StudentAttendance::firstOrNew([
                    'school_id' => $row->school_id,
                    'student_id' => $row->person_id,
                    'date' => $row->date->toDateString(),
                ]);

                $attendance->fill([
                    'check_in' => $row->first_check_in,
                    'check_out' => $row->last_check_out,
                    'status' => $row->status,
                    'notes' => $row->has_anomaly
                        ? 'Ditandai anomali oleh sistem absensi — perlu verifikasi manual.'
                        : $attendance->notes,
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
