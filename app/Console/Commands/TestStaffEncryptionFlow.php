<?php

namespace App\Console\Commands;

use App\Models\Staff;
use App\Models\School;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestStaffEncryptionFlow extends Command
{
    protected $signature = 'test:staff-flow {--cleanup : Hapus data test setelah selesai}';

    protected $description = 'Test end-to-end flow: create staff dengan data sensitif, tampilkan hasil decrypt, test search by NIP';

    public function handle()
    {
        $this->info('==========================================');
        $this->info(' TEST END-TO-END: Staff Encryption Flow');
        $this->info('==========================================');

        try {
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
                'username' => 'test_staff_' . Str::random(6),
                'email' => 'test_staff_' . Str::random(6) . '@test.local',
                'password' => Hash::make('password'),
                'role' => 'tu',
            ]);
            $this->line("    User dibuat: {$testUser->id} ({$testUser->username})");

            $testNip = '19900101' . random_int(100000, 999999) . '2002';

            $this->line('');
            $this->info('[2] Membuat Staff baru...');
            $start = microtime(true);

            $staff = Staff::create([
                'school_id' => $school->id,
                'user_id' => $testUser->id,
                'full_name' => 'Staff Test Encryption',
                'email' => 'staff.test@example.com',
                'gender' => 'P',
                'position' => 'Tata Usaha',
                'is_active' => true,
            ]);

            $this->line("    Staff dibuat: {$staff->id}");

            $this->line('');
            $this->info('[3] Mengisi data sensitif (encrypt otomatis via gRPC, batched 1x save)...');

            $staff->nip = $testNip;
            $staff->address = 'Jl. Tata Usaha No. 7, Jember';
            $staff->phone = '081234500001';
            $staff->birth_place = 'Jember';
            $staff->birth_date = '1990-01-01';
            $staff->religion = 'Islam';
            $staff->save();

            $duration = round((microtime(true) - $start) * 1000, 2);
            $this->info("    Semua field sensitif berhasil disimpan ({$duration} ms total)");

            $this->line('');
            $this->info('[4] Verifikasi: kolom di tabel staff HARUS berupa hash, bukan plain...');

            $rawStaffRow = DB::table('staff')->where('id', $staff->id)->first();
            $this->line("    nip_hash di DB : {$rawStaffRow->nip_hash}");

            if (str_contains($rawStaffRow->nip_hash ?? '', $testNip)) {
                $this->error('    ❌ BAHAYA: NIP plain text ditemukan di kolom hash!');
            } else {
                $this->info('    ✅ Aman: kolom hash tidak mengandung plain text NIP');
            }

            $this->line('');
            $this->info('[5] Verifikasi: kolom di tabel staff_sensitive_data HARUS ciphertext...');

            $rawSensitiveRow = DB::table('staff_sensitive_data')->where('staff_id', $staff->id)->first();
            $this->line("    nip_encrypted (raw) : {$rawSensitiveRow->nip_encrypted}");

            if (str_contains($rawSensitiveRow->nip_encrypted ?? '', $testNip)) {
                $this->error('    ❌ BAHAYA: NIP plain text ditemukan di kolom encrypted!');
            } else {
                $this->info('    ✅ Aman: kolom encrypted tidak mengandung plain text NIP');
            }

            $this->line('');
            $this->info('[6] Membaca ulang via Model (harus otomatis decrypt)...');

            $freshStaff = Staff::find($staff->id);
            $start = microtime(true);

            $this->table(
                ['Field', 'Hasil Decrypt', 'Match dengan Input?'],
                [
                    ['nip', $freshStaff->nip, $freshStaff->nip === $testNip ? '✅' : '❌'],
                    ['address', $freshStaff->address, $freshStaff->address === 'Jl. Tata Usaha No. 7, Jember' ? '✅' : '❌'],
                    ['phone', $freshStaff->phone, $freshStaff->phone === '081234500001' ? '✅' : '❌'],
                    ['email (plain)', $freshStaff->email, $freshStaff->email === 'staff.test@example.com' ? '✅' : '❌'],
                    ['birth_place', $freshStaff->birth_place, $freshStaff->birth_place === 'Jember' ? '✅' : '❌'],
                    ['religion', $freshStaff->religion, $freshStaff->religion === 'Islam' ? '✅' : '❌'],
                ]
            );

            $duration = round((microtime(true) - $start) * 1000, 2);
            $this->line("    Total waktu decrypt semua field: {$duration} ms");

            $this->line('');
            $this->info('[7] Test pencarian via Staff::findByNip()...');

            $foundByNip = Staff::findByNip($testNip);
            if ($foundByNip && $foundByNip->id === $staff->id) {
                $this->info("    ✅ Berhasil ditemukan via NIP: {$foundByNip->full_name}");
            } else {
                $this->error('    ❌ Gagal menemukan staff via NIP!');
            }

            $this->line('');
            $this->info('[8] Test pencarian dengan NIP salah (harus tidak ketemu)...');

            $notFound = Staff::findByNip('00000000000000000000');
            if ($notFound === null) {
                $this->info('    ✅ Benar, NIP yang tidak ada tidak ditemukan');
            } else {
                $this->error('    ❌ Aneh, NIP yang salah malah ketemu data!');
            }

            $this->line('');
            $this->info('==========================================');
            $this->info(' SEMUA TEST SELESAI');
            $this->info('==========================================');

            if ($this->option('cleanup')) {
                $this->line('');
                $this->info('[9] Membersihkan data test...');
                $staff->sensitiveData?->delete();
                $staff->delete();
                $testUser->delete();
                $this->info('    Data test berhasil dihapus.');
            } else {
                $this->line('');
                $this->warn("Data test TIDAK dihapus. Staff ID: {$staff->id}");
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