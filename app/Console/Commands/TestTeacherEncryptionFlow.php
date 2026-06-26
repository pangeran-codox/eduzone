<?php

namespace App\Console\Commands;

use App\Models\Teacher;
use App\Models\School;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestTeacherEncryptionFlow extends Command
{
    protected $signature = 'test:teacher-flow {--cleanup : Hapus data test setelah selesai}';

    protected $description = 'Test end-to-end flow: create teacher dengan data sensitif, tampilkan hasil decrypt, test search by NIP/NUPTK';

    public function handle()
    {
        $this->info('==========================================');
        $this->info(' TEST END-TO-END: Teacher Encryption Flow');
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
                'username' => 'test_guru_' . Str::random(6),
                'email' => 'test_guru_' . Str::random(6) . '@test.local',
                'password' => Hash::make('password'),
                'role' => 'guru_mapel',
            ]);
            $this->line("    User dibuat: {$testUser->id} ({$testUser->username})");

            // 2. Generate NIP/NUPTK unik untuk test
            $testNip = '19850101' . random_int(100000, 999999) . '1001';
            $testNuptk = (string) random_int(1000000000000000, 9999999999999999);

            $this->line('');
            $this->info('[2] Membuat Teacher baru...');
            $start = microtime(true);

            $teacher = Teacher::create([
                'school_id' => $school->id,
                'user_id' => $testUser->id,
                'full_name' => 'Guru Test Encryption',
                'email' => 'guru.test@example.com', // plain
                'gender' => 'L',
                'last_education' => 'S1',
                'employment_status' => 'PNS',
                'is_active' => true,
            ]);

            $this->line("    Teacher dibuat: {$teacher->id}");

            // 3. Set field sensitif - trigger encrypt otomatis
            $this->line('');
            $this->info('[3] Mengisi data sensitif (encrypt otomatis via gRPC)...');

            $teacher->nip = $testNip;
            $teacher->nuptk = $testNuptk;
            $teacher->address = 'Jl. Pendidikan No. 5, Jember';
            $teacher->phone = '081234567899';
            $teacher->birth_place = 'Jember';
            $teacher->birth_date = '1985-01-01';
            $teacher->religion = 'Islam';
            $teacher->save(); // commit hash kolom (nip_hash, nuptk_hash) ke tabel teachers

            $duration = round((microtime(true) - $start) * 1000, 2);
            $this->info("    Semua field sensitif berhasil disimpan ({$duration} ms total)");

            // 4. Verifikasi kolom hash di tabel teachers
            $this->line('');
            $this->info('[4] Verifikasi: kolom di tabel teachers HARUS berupa hash, bukan plain...');

            $rawTeacherRow = DB::table('teachers')->where('id', $teacher->id)->first();
            $this->line("    nip_hash di DB   : {$rawTeacherRow->nip_hash}");
            $this->line("    nuptk_hash di DB : {$rawTeacherRow->nuptk_hash}");

            if (str_contains($rawTeacherRow->nip_hash ?? '', $testNip)) {
                $this->error('    ❌ BAHAYA: NIP plain text ditemukan di kolom hash!');
            } else {
                $this->info('    ✅ Aman: kolom hash tidak mengandung plain text NIP');
            }

            // 5. Verifikasi ciphertext di teacher_sensitive_data
            $this->line('');
            $this->info('[5] Verifikasi: kolom di tabel teacher_sensitive_data HARUS ciphertext...');

            $rawSensitiveRow = DB::table('teacher_sensitive_data')->where('teacher_id', $teacher->id)->first();
            $this->line("    nip_encrypted (raw) : {$rawSensitiveRow->nip_encrypted}");

            if (str_contains($rawSensitiveRow->nip_encrypted ?? '', $testNip)) {
                $this->error('    ❌ BAHAYA: NIP plain text ditemukan di kolom encrypted!');
            } else {
                $this->info('    ✅ Aman: kolom encrypted tidak mengandung plain text NIP');
            }

            // 6. Baca ulang via Model - harus otomatis decrypt
            $this->line('');
            $this->info('[6] Membaca ulang via Model (harus otomatis decrypt)...');

            $freshTeacher = Teacher::find($teacher->id);
            $start = microtime(true);

            $this->table(
                ['Field', 'Hasil Decrypt', 'Match dengan Input?'],
                [
                    ['nip', $freshTeacher->nip, $freshTeacher->nip === $testNip ? '✅' : '❌'],
                    ['nuptk', $freshTeacher->nuptk, $freshTeacher->nuptk === $testNuptk ? '✅' : '❌'],
                    ['address', $freshTeacher->address, $freshTeacher->address === 'Jl. Pendidikan No. 5, Jember' ? '✅' : '❌'],
                    ['phone', $freshTeacher->phone, $freshTeacher->phone === '081234567899' ? '✅' : '❌'],
                    ['email (plain)', $freshTeacher->email, $freshTeacher->email === 'guru.test@example.com' ? '✅' : '❌'],
                    ['birth_place', $freshTeacher->birth_place, $freshTeacher->birth_place === 'Jember' ? '✅' : '❌'],
                    ['religion', $freshTeacher->religion, $freshTeacher->religion === 'Islam' ? '✅' : '❌'],
                ]
            );

            $duration = round((microtime(true) - $start) * 1000, 2);
            $this->line("    Total waktu decrypt semua field: {$duration} ms");

            // 7. Test search by NIP
            $this->line('');
            $this->info('[7] Test pencarian via Teacher::findByNip()...');

            $foundByNip = Teacher::findByNip($testNip);
            if ($foundByNip && $foundByNip->id === $teacher->id) {
                $this->info("    ✅ Berhasil ditemukan via NIP: {$foundByNip->full_name}");
            } else {
                $this->error('    ❌ Gagal menemukan teacher via NIP!');
            }

            // 8. Test search by NUPTK
            $this->line('');
            $this->info('[8] Test pencarian via Teacher::findByNuptk()...');

            $foundByNuptk = Teacher::findByNuptk($testNuptk);
            if ($foundByNuptk && $foundByNuptk->id === $teacher->id) {
                $this->info("    ✅ Berhasil ditemukan via NUPTK: {$foundByNuptk->full_name}");
            } else {
                $this->error('    ❌ Gagal menemukan teacher via NUPTK!');
            }

            // 9. Negative test
            $this->line('');
            $this->info('[9] Test pencarian dengan NIP salah (harus tidak ketemu)...');

            $notFound = Teacher::findByNip('00000000000000000000');
            if ($notFound === null) {
                $this->info('    ✅ Benar, NIP yang tidak ada tidak ditemukan');
            } else {
                $this->error('    ❌ Aneh, NIP yang salah malah ketemu data!');
            }

            $this->line('');
            $this->info('==========================================');
            $this->info(' SEMUA TEST SELESAI');
            $this->info('==========================================');

            // 10. Cleanup kalau diminta
            if ($this->option('cleanup')) {
                $this->line('');
                $this->info('[10] Membersihkan data test...');
                $teacher->sensitiveData?->delete();
                $teacher->delete();
                $testUser->delete();
                $this->info('    Data test berhasil dihapus.');
            } else {
                $this->line('');
                $this->warn("Data test TIDAK dihapus. Teacher ID: {$teacher->id}");
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