<?php

namespace App\Console\Commands\Tenant;

use App\Models\School;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Generate user test untuk role tertentu + signed URL auto-login (LOCAL ONLY).
 *
 * ⚠️  Route auto-login SENGAAJA hanya aktif di APP_ENV=local/development.
 * JANGAN dijalankan di production — password dibuat plain dan diketahui semua.
 *
 * Usage:
 *   php artisan tenant:seed-test --school=sma-1
 *   php artisan tenant:seed-test --role=kepsek --school=uuid-sekolah
 *   php artisan tenant:seed-test --all       (generate semua role, 1 user per role per sekolah)
 *   php artisan tenant:seed-test --show-urls  (tampilkan signed URL login langsung buka browser)
 */
class SeedTestUserCommand extends Command
{
    protected $signature = 'tenant:seed-test
                            {--school= : Identifier sekolah (slug / UUID / nama). Default: sekolah pertama aktif.}
                            {--role= : Role tertentu (kepsek, kurikulum, tu, guru_mapel, wali_kelas, kesiswaan, bk, toolman, siswa). Default: semua role.}
                            {--all : Generate untuk SEMUA sekolah aktif (bukan cuma 1)}
                            {--show-urls : Tampilkan signed URL auto-login (1 hari) untuk semua user yang dibuat}
                            {--password= : Password untuk semua user test (default: password)}
                            {--fresh : Hapus user test lama (username prefixed test_) sebelum generate}';

    protected $description = '[LOCAL ONLY] Generate user test per role + signed auto-login URL untuk cepat-test dashboard.';

    /** Default password (mudah diingat untuk development) */
    private const DEFAULT_PASSWORD = 'password';

    /** Role list + label human-readable + avatar initials */
    private const ROLE_META = [
        'kepsek'      => ['name' => 'Kepala Sekolah',  'short' => 'KS'],
        'kurikulum'   => ['name' => 'Staff Kurikulum', 'short' => 'KU'],
        'tu'          => ['name' => 'Tata Usaha',      'short' => 'TU'],
        'guru_mapel'  => ['name' => 'Guru Mapel',      'short' => 'GM'],
        'wali_kelas'  => ['name' => 'Wali Kelas',      'short' => 'WK'],
        'kesiswaan'   => ['name' => 'Staff Kesiswaan', 'short' => 'KSW'],
        'bk'          => ['name' => 'Guru BK',         'short' => 'BK'],
        'toolman'     => ['name' => 'Toolman',         'short' => 'TL'],
        'siswa'       => ['name' => 'Siswa',           'short' => 'SW'],
    ];

    public function handle(): int
    {
        if (! app()->environment(['local', 'development', 'testing'])) {
            $this->error('❌ Command ini HANYA boleh dijalankan di environment LOCAL/DEVELOPMENT/TESTING (bukan production).');
            return self::FAILURE;
        }

        $password = $this->option('password') ?? self::DEFAULT_PASSWORD;
        $fresh = (bool) $this->option('fresh');
        $showUrls = (bool) $this->option('show-urls');
        $roleFilter = $this->option('role');
        $useAllSchools = (bool) $this->option('all');

        // Validasi role filter
        if ($roleFilter && ! isset(self::ROLE_META[$roleFilter])) {
            $rolesList = implode(', ', array_keys(self::ROLE_META));
            $this->error("❌ Role tidak valid: \"{$roleFilter}\". Pilih salah satu: {$rolesList}");
            return self::FAILURE;
        }

        // Ambil daftar sekolah target
        $singleSchool = $useAllSchools ? null : $this->resolveOneSchool($this->option('school'));
        $schools = $useAllSchools
            ? School::withoutGlobalScopes()->where('is_active', true)->get()
            : collect($singleSchool ? [$singleSchool] : []);

        if ($schools->isEmpty()) {
            $this->error('❌ Tidak ada sekolah target. Periksa --school atau --all.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info("🎯 Menyiapkan {$schools->count()} sekolah target.");
        if ($roleFilter) {
            $this->line("   (Hanya role: <comment>{$roleFilter}</comment>)");
        }
        if ($fresh) {
            $this->warn('   (--fresh AKTIF: user test_ lama akan dihapus duluan)');
        }
        $this->newLine();

        $allSignedUrls = [];
        $createdUsersCount = 0;

        foreach ($schools as $school) {
            $school->makeCurrent();

            if ($fresh) {
                $deleted = User::withoutGlobalScopes()
                    ->where('school_id', $school->id)
                    ->where('username', 'like', 'test_%')
                    ->delete();
                if ($deleted > 0) {
                    $this->line("   🧹 [{$school->slug}] Hapus {$deleted} user test lama (prefix test_).");
                }
            }

            $rolesToCreate = $roleFilter
                ? [$roleFilter => self::ROLE_META[$roleFilter]]
                : self::ROLE_META;

            foreach ($rolesToCreate as $role => $meta) {
                $username = "test_{$role}";
                $email = "test+{$role}@" . ($school->slug ?? 'eduzone') . '.local';

                // Cek atau buat user
                $user = User::withoutGlobalScopes()->firstOrNew([
                    'school_id' => $school->id,
                    'username'  => $username,
                ]);

                $wasFresh = ! $user->exists;
                $user->fill([
                    'role'      => $role,
                    'email'     => $email,
                    'password'  => bcrypt($password),
                    'is_active' => true,
                ]);
                $user->save();

                $createdUsersCount++;

                if ($this->getOutput()->isVerbose() || $wasFresh) {
                    $this->line(
                        "   " . ($wasFresh ? '✨' : '♻️') .
                        " [{$school->slug}] {$meta['name']}:" .
                        " <info>{$username}</info>" .
                        " / pass: <comment>{$password}</comment>" .
                        ($wasFresh ? '' : ' <comment>(sudah ada, update password)</comment>')
                    );
                }

                if ($showUrls) {
                    $signedUrl = URL::temporarySignedRoute(
                        'tenant.test.auto-login',
                        now()->addDay(),
                        ['user' => $user->id, 'school' => $school->id]
                    );
                    $allSignedUrls[] = (object) [
                        'school'   => $school->name,
                        'role'     => $meta['name'],
                        'username' => $username,
                        'url'      => $signedUrl,
                    ];
                }
            }
        }

        // ── Ringkasan ─────────────────────────────────────────────────────
        $this->newLine();
        $this->info("🎉 Selesai: {$createdUsersCount} user test siap dipakai.");
        $this->line('   Password default semua user: <comment>' . $password . '</comment>');

        if ($showUrls && ! empty($allSignedUrls)) {
            $this->newLine();
            $this->info('🔑 Signed URL auto-login (BERLAKU 1 HARI, LOKAL SAJA):');
            $this->table(
                ['Sekolah', 'Role', 'Username', 'Login URL'],
                collect($allSignedUrls)->map(fn ($row) => [
                    $row->school,
                    $row->role,
                    $row->username,
                    $row->url,
                ])
            );
        }

        $this->newLine();
        $this->line('💡 Mau cek scope tenant setelah login? Di Tinker jalankan:');
        $this->line('   <info>\App\Models\Student::query()->dumpTenantScope();</info>');

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────

    private function resolveOneSchool(?string $identifier): ?School
    {
        if ($identifier) {
            // By UUID - cuma dicoba kalau formatnya emang UUID valid, biar
            // nggak crash di level Postgres (kolom id bertipe uuid, string
            // bukan-UUID bikin error tipe database, bukan "tidak ketemu" biasa).
            if (Str::isUuid($identifier)) {
                $s = School::withoutGlobalScopes()->find($identifier);
                if ($s) return $s;
            }

            // By slug
            $s = School::withoutGlobalScopes()->where('slug', $identifier)->first();
            if ($s) return $s;

            // By nama LIKE
            $s = School::withoutGlobalScopes()
                ->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($identifier) . '%'])
                ->where('is_active', true)
                ->first();
            if ($s) return $s;

            $this->warn("⚠️  Tidak ketem sekolah dengan identifier \"{$identifier}\", fallback ke sekolah aktif pertama.");
        }

        return School::withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('onboarded_at', 'desc')
            ->first();
    }
}
