<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestAuditLogFlow extends Command
{
    protected $signature = 'test:audit-flow {--cleanup : Hapus data test setelah selesai}';

    protected $description = 'Test apakah perubahan data sensitif tercatat dengan benar di activity_logs';

    public function handle()
    {
        $this->info('==========================================');
        $this->info(' TEST: Audit Log untuk Data Sensitif');
        $this->info('==========================================');

        try {
            $school = School::first();
            if (!$school) {
                $this->error('Tidak ada School. Jalankan seeder dulu.');
                return 1;
            }

            $testUser = User::create([
                'school_id' => $school->id,
                'username' => 'test_audit_' . Str::random(6),
                'email' => 'test_audit_' . Str::random(6) . '@test.local',
                'password' => Hash::make('password'),
                'role' => 'siswa',
            ]);

            // Hitung jumlah log SEBELUM aksi apapun, supaya bisa hitung selisihnya
            $logCountBefore = ActivityLog::where('activity', 'like', 'sensitive_data.%')->count();

            // 1. CREATE student + isi data sensitif
            $this->line('');
            $this->info('[1] Membuat Student baru + isi data sensitif...');

            $student = Student::create([
                'school_id' => $school->id,
                'user_id' => $testUser->id,
                'full_name' => 'Siswa Test Audit',
                'email' => 'test.audit@example.com',
                'gender' => 'L',
                'status' => 'aktif',
            ]);

            $testNisn = '77' . random_int(10000000, 99999999);
            $student->nisn = $testNisn;
            $student->address = 'Jl. Audit Test No. 1';
            $student->save();

            $this->info("    Student dibuat: {$student->id}");

            // 2. Cek log untuk aksi 'created'
            $this->line('');
            $this->info('[2] Mengecek log untuk aksi CREATE...');

            $createLogs = ActivityLog::sensitiveDataHistory('Student', $student->id)
                ->where('activity', 'sensitive_data.created')
                ->get();

            if ($createLogs->isEmpty()) {
                $this->error('    ❌ Tidak ada log untuk aksi created!');
            } else {
                foreach ($createLogs as $log) {
                    $desc = json_decode($log->description, true);
                    $this->info("    ✅ Log ditemukan: activity={$log->activity}, fields=" . implode(',', $desc['fields']));
                    $this->line("       user_id: {$log->user_id}, waktu: {$log->created_at}");
                }
            }

            // 3. UPDATE salah satu field sensitif
            $this->line('');
            $this->info('[3] Mengupdate field phone (trigger log updated)...');

            $student->phone = '081200001111';
            $student->save(); // flush pending sensitive data (termasuk phone) dalam 1x save

            // 4. Cek log untuk aksi 'updated'
            $this->line('');
            $this->info('[4] Mengecek log untuk aksi UPDATE...');

            $updateLogs = ActivityLog::sensitiveDataHistory('Student', $student->id)
                ->where('activity', 'sensitive_data.updated')
                ->get();

            if ($updateLogs->isEmpty()) {
                $this->error('    ❌ Tidak ada log untuk aksi updated!');
            } else {
                foreach ($updateLogs as $log) {
                    $desc = json_decode($log->description, true);
                    $this->info("    ✅ Log ditemukan: activity={$log->activity}, fields=" . implode(',', $desc['fields']));
                }
            }

            // 5. Verifikasi PENTING: pastikan TIDAK ADA plain text NISN/data sensitif di log manapun
            $this->line('');
            $this->info('[5] Verifikasi keamanan: log TIDAK BOLEH mengandung data sensitif asli...');

            $allLogs = ActivityLog::sensitiveDataHistory('Student', $student->id)->get();
            $leaked = false;

            foreach ($allLogs as $log) {
                if (str_contains($log->description, $testNisn) || str_contains($log->description, 'Jl. Audit Test')) {
                    $leaked = true;
                    $this->error("    ❌ BAHAYA: log #{$log->id} mengandung data sensitif asli!");
                }
            }

            if (!$leaked) {
                $this->info('    ✅ Aman: tidak ada plain text data sensitif di log manapun');
            }

            // 6. Hitung total log yang bertambah
            $this->line('');
            $logCountAfter = ActivityLog::where('activity', 'like', 'sensitive_data.%')->count();
            $added = $logCountAfter - $logCountBefore;
            $this->info("[6] Total log baru yang tercatat: {$added} baris (1x create + 1x update = wajar 2 baris)");

            $this->line('');
            $this->info('==========================================');
            $this->info(' SEMUA TEST AUDIT LOG SELESAI');
            $this->info('==========================================');

            if ($this->option('cleanup')) {
                $this->line('');
                $this->info('Membersihkan data test...');
                ActivityLog::sensitiveDataHistory('Student', $student->id)->delete();
                $student->sensitiveData?->delete();
                $student->delete();
                $testUser->delete();
                $this->info('Selesai.');
            } else {
                $this->warn("Student ID: {$student->id} (tidak dihapus, jalankan --cleanup untuk auto-hapus)");
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error('GAGAL: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}