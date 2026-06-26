<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\School;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestStudentEncryptionFlow extends Command
{
    protected $signature = 'test:student-flow {--cleanup : Hapus data test setelah selesai}';

    protected $description = 'Test end-to-end flow: create student dengan data sensitif, tampilkan hasil decrypt, test search by NISN';

    public function handle()
    {
        $this->info('==========================================');
        $this->info(' TEST END-TO-END: Student Encryption Flow');
        $this->info('==========================================');

        try {
            // 1. Pastikan ada school & user buat dipakai
            $this->line('');
            $this->info('[1] Menyiapkan data prasyarat (school, user)...');

            $school = School::first();
            if (!$school) {
                $this->error('Tidak ada data School di database. Jalankan seeder dulu.');
                return 1;
            }
            $this->line("    School dipakai: {$school->id} ({$school->name})");

            $testUser = User::create([
                'school_id' => $school->id,
                'username' => 'test_siswa_' . Str::random(6),
                'email' => 'test_' . Str::random(6) . '@test.local',
                'password' => Hash::make('password'),
                'role' => 'siswa',
            ]);
            $this->line("    User dibuat: {$testUser->id} ({$testUser->username})");

            // 2. Generate NISN unik untuk test (supaya gak collide kalau dijalankan berkali-kali)
            $testNisn = '99' . random_int(10000000, 99999999);
            $testNis = '88' . random_int(1000000, 9999999);

            $this->line('');
            $this->info('[2] Membuat Student baru...');
            $start = microtime(true);

            $student = Student::create([
                'school_id' => $school->id,
                'user_id' => $testUser->id,
                'full_name' => 'Siswa Test Encryption',
                'email' => 'siswa.test@example.com', // plain, langsung di create
                'gender' => 'L',
                'grade' => 'X',
                'status' => 'aktif',
            ]);

            $this->line("    Student dibuat: {$student->id}");

            // 3. Set field sensitif - ini akan trigger encrypt otomatis
            $this->line('');
            $this->info('[3] Mengisi data sensitif (encrypt otomatis via gRPC)...');

            $student->nisn = $testNisn;
            $student->nis = $testNis;
            $student->address = 'Jl. Test Encryption No. 123, Jember';
            $student->phone = '081234567890';
            $student->birth_place = 'Jember';
            $student->birth_date = '2008-05-15';
            $student->religion = 'Islam';
            $student->father_name = 'Bapak Test';
            $student->mother_name = 'Ibu Test';
            $student->father_job = 'Wiraswasta';
            $student->mother_job = 'Ibu Rumah Tangga';
            $student->parent_address = 'Jl. Test Encryption No. 123, Jember';
            $student->parent_phone = '081298765432';
            $student->save(); // commit hash kolom (nisn_hash, nis_hash) ke tabel students

            $duration = round((microtime(true) - $start) * 1000, 2);
            $this->info("    Semua field sensitif berhasil disimpan ({$duration} ms total)");

            // 4. Cek langsung ke database - pastikan kolom di tabel students TIDAK plain text
            $this->line('');
            $this->info('[4] Verifikasi: kolom di tabel students HARUS berupa hash, bukan plain...');

            $rawStudentRow = DB::table('students')->where('id', $student->id)->first();
            $this->line("    nisn_hash di DB : {$rawStudentRow->nisn_hash}");
            $this->line("    nis_hash di DB  : {$rawStudentRow->nis_hash}");

            if (str_contains($rawStudentRow->nisn_hash ?? '', $testNisn)) {
                $this->error('    ❌ BAHAYA: NISN plain text ditemukan di kolom hash!');
            } else {
                $this->info('    ✅ Aman: kolom hash tidak mengandung plain text NISN');
            }

            // 5. Cek langsung ke tabel student_sensitive_data - pastikan ciphertext, bukan plain
            $this->line('');
            $this->info('[5] Verifikasi: kolom di tabel student_sensitive_data HARUS ciphertext...');

            $rawSensitiveRow = DB::table('student_sensitive_data')->where('student_id', $student->id)->first();
            $this->line("    nisn_encrypted (raw) : {$rawSensitiveRow->nisn_encrypted}");
            $this->line("    address_encrypted (raw, dipotong): " . substr($rawSensitiveRow->address_encrypted, 0, 50) . '...');

            if (str_contains($rawSensitiveRow->nisn_encrypted ?? '', $testNisn)) {
                $this->error('    ❌ BAHAYA: NISN plain text ditemukan di kolom encrypted!');
            } else {
                $this->info('    ✅ Aman: kolom encrypted tidak mengandung plain text NISN');
            }

            // 6. Ambil ulang dari Model - harus otomatis ke-decrypt jadi plain text asli
            $this->line('');
            $this->info('[6] Membaca ulang via Model (harus otomatis decrypt)...');

            $freshStudent = Student::find($student->id);
            $start = microtime(true);

            $this->table(
                ['Field', 'Hasil Decrypt', 'Match dengan Input?'],
                [
                    ['nisn', $freshStudent->nisn, $freshStudent->nisn === $testNisn ? '✅' : '❌'],
                    ['nis', $freshStudent->nis, $freshStudent->nis === $testNis ? '✅' : '❌'],
                    ['address', $freshStudent->address, $freshStudent->address === 'Jl. Test Encryption No. 123, Jember' ? '✅' : '❌'],
                    ['phone', $freshStudent->phone, $freshStudent->phone === '081234567890' ? '✅' : '❌'],
                    ['email (plain)', $freshStudent->email, $freshStudent->email === 'siswa.test@example.com' ? '✅' : '❌'],
                    ['birth_place', $freshStudent->birth_place, $freshStudent->birth_place === 'Jember' ? '✅' : '❌'],
                    ['father_name', $freshStudent->father_name, $freshStudent->father_name === 'Bapak Test' ? '✅' : '❌'],
                ]
            );

            $duration = round((microtime(true) - $start) * 1000, 2);
            $this->line("    Total waktu decrypt semua field: {$duration} ms");

            // 7. Test search by NISN (pakai hash, harus ketemu)
            $this->line('');
            $this->info('[7] Test pencarian via Student::findByNisn()...');

            $foundByNisn = Student::findByNisn($testNisn);
            if ($foundByNisn && $foundByNisn->id === $student->id) {
                $this->info("    ✅ Berhasil ditemukan via NISN: {$foundByNisn->full_name}");
            } else {
                $this->error('    ❌ Gagal menemukan student via NISN!');
            }

            // 8. Test search dengan NISN yang salah (harus TIDAK ketemu)
            $this->line('');
            $this->info('[8] Test pencarian dengan NISN salah (harus tidak ketemu)...');

            $notFound = Student::findByNisn('0000000000');
            if ($notFound === null) {
                $this->info('    ✅ Benar, NISN yang tidak ada tidak ditemukan');
            } else {
                $this->error('    ❌ Aneh, NISN yang salah malah ketemu data!');
            }

            $this->line('');
            $this->info('==========================================');
            $this->info(' SEMUA TEST SELESAI');
            $this->info('==========================================');

            // 9. Cleanup kalau diminta
            if ($this->option('cleanup')) {
                $this->line('');
                $this->info('[9] Membersihkan data test...');
                $student->sensitiveData?->delete();
                $student->delete();
                $testUser->delete();
                $this->info('    Data test berhasil dihapus.');
            } else {
                $this->line('');
                $this->warn("Data test TIDAK dihapus. Student ID: {$student->id}");
                $this->warn('Jalankan ulang dengan --cleanup untuk auto-hapus, atau hapus manual nanti.');
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error('GAGAL: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}