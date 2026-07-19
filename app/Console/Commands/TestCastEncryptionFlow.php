<?php

namespace App\Console\Commands;

use App\Models\CounselingSession;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentRecord;
use App\Models\StudentSikap;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestCastEncryptionFlow extends Command
{
    protected $signature = 'test:cast-flow {--cleanup : Hapus data test setelah selesai}';

    protected $description = 'Test cepat EncryptedAttribute cast untuk counseling_sessions, student_records, student_sikap';

    public function handle()
    {
        $this->info('==========================================');
        $this->info(' TEST: EncryptedAttribute Cast (3 tabel)');
        $this->info('==========================================');

        try {
            $school = School::first();
            $student = Student::first();
            $staff = Staff::first();
            $class = SchoolClass::first();

            if (!$school || !$student || !$staff || !$class) {
                $this->error('Butuh minimal 1 School, Student, Staff, dan SchoolClass di database.');
                $this->error('School: ' . ($school ? '✓' : '✗ kosong'));
                $this->error('Student: ' . ($student ? '✓' : '✗ kosong, jalankan test:student-flow dulu'));
                $this->error('Staff: ' . ($staff ? '✓' : '✗ kosong, jalankan test:staff-flow dulu'));
                $this->error('SchoolClass: ' . ($class ? '✓' : '✗ kosong'));
                return 1;
            }

            $results = [];

            // ===== 1. CounselingSession =====
            $this->line('');
            $this->info('[1] Test CounselingSession (topic, result)...');

            $topicText = 'Masalah keluarga - orang tua bercerai';
            $resultText = 'Siswa sudah ditindaklanjuti, kondisi mulai stabil';

            $session = CounselingSession::create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'staff_id' => $staff->id,
                'date' => now()->toDateString(),
                'topic' => $topicText,
                'result' => $resultText,
            ]);

            $rawSession = DB::table('counseling_sessions')->where('id', $session->id)->first();
            $freshSession = CounselingSession::find($session->id);

            $this->checkAndReport('CounselingSession.topic', $topicText, $rawSession->topic, $freshSession->topic, $results);
            $this->checkAndReport('CounselingSession.result', $resultText, $rawSession->result, $freshSession->result, $results);

            // ===== 2. StudentRecord =====
            $this->line('');
            $this->info('[2] Test StudentRecord (description)...');

            $descText = 'Siswa terlibat insiden ringan, sudah diberi pembinaan';

            $record = StudentRecord::create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'activity' => 'Pembinaan',
                'date' => now()->toDateString(),
                'description' => $descText,
            ]);

            $rawRecord = DB::table('student_records')->where('id', $record->id)->first();
            $freshRecord = StudentRecord::find($record->id);

            $this->checkAndReport('StudentRecord.description', $descText, $rawRecord->description, $freshRecord->description, $results);

            // ===== 3. StudentSikap =====
            $this->line('');
            $this->info('[3] Test StudentSikap (catatan_sikap, catatan_wakel)...');

            $catatanSikapText = 'Siswa cukup aktif, kadang kurang fokus saat pelajaran';
            $catatanWakelText = 'Perlu perhatian lebih pada kehadiran semester depan';

            $sikap = StudentSikap::create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'class_id' => $class->id,
                'academic_year' => '2025/2026',
                'semester' => 'Ganjil',
                'sikap_spiritual' => 'B',
                'sikap_sosial' => 'B',
                'catatan_sikap' => $catatanSikapText,
                'ekskul' => 'Pramuka, Basket',
                'catatan_wakel' => $catatanWakelText,
            ]);

            $rawSikap = DB::table('student_sikap')->where('id', $sikap->id)->first();
            $freshSikap = StudentSikap::find($sikap->id);

            $this->checkAndReport('StudentSikap.catatan_sikap', $catatanSikapText, $rawSikap->catatan_sikap, $freshSikap->catatan_sikap, $results);
            $this->checkAndReport('StudentSikap.catatan_wakel', $catatanWakelText, $rawSikap->catatan_wakel, $freshSikap->catatan_wakel, $results);

            // Cek ekskul TIDAK terenkripsi (sengaja plain)
            $this->line('');
            $this->info('[4] Verifikasi ekskul TIDAK dienkripsi (sengaja plain)...');
            if ($rawSikap->ekskul === 'Pramuka, Basket') {
                $this->info('    ✅ Benar, ekskul tersimpan plain (tidak dienkripsi)');
            } else {
                $this->error('    ❌ Aneh, ekskul tidak sesuai ekspektasi: ' . $rawSikap->ekskul);
            }

            // Ringkasan
            $this->line('');
            $this->info('==========================================');
            $this->info(' RINGKASAN HASIL');
            $this->info('==========================================');
            $this->table(['Field', 'Status'], $results);

            $allPassed = !in_array('❌', array_column($results, 1));

            if ($allPassed) {
                $this->info('🎉 SEMUA TEST PASS!');
            } else {
                $this->error('⚠️  ADA YANG GAGAL, cek tabel di atas.');
            }

            if ($this->option('cleanup')) {
                $this->line('');
                $this->info('Membersihkan data test...');
                $session->delete();
                $record->delete();
                $sikap->delete();
                $this->info('Selesai.');
            } else {
                $this->warn('Data test tidak dihapus. Jalankan ulang dengan --cleanup untuk auto-hapus.');
            }

            return $allPassed ? 0 : 1;
        } catch (\Throwable $e) {
            $this->error('GAGAL: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    protected function checkAndReport(string $label, string $original, ?string $raw, ?string $decrypted, array &$results): void
    {
        $rawIsCiphertext = $raw !== null && !str_contains($raw, $original) && str_contains($raw, ':');
        $decryptedMatches = $decrypted === $original;

        if ($rawIsCiphertext) {
            $this->info("    ✅ Raw di DB adalah ciphertext (bukan plain text)");
        } else {
            $this->error("    ❌ BAHAYA: Raw di DB sepertinya masih plain text atau format salah!");
            $this->line("       Raw value: {$raw}");
        }

        if ($decryptedMatches) {
            $this->info("    ✅ Hasil decrypt cocok dengan input asli");
        } else {
            $this->error("    ❌ Hasil decrypt TIDAK cocok! Expected: {$original}, Got: {$decrypted}");
        }

        $results[] = [$label, ($rawIsCiphertext && $decryptedMatches) ? '✅' : '❌'];
    }
}
