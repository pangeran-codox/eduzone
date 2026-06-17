<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        // ── School 1: SMA Negeri 1 Demo ───────────────────────────────
        $school1Id = Str::uuid()->toString();

        DB::table('schools')->insert([
            'id'                => $school1Id,
            'name'              => 'SMA Negeri 1 Demo',
            'slug'              => 'sman1-demo',
            'npsn'              => '12345678',
            'level'             => 'SMA',
            'status'            => 'Negeri',
            'accreditation'     => 'A',
            'city'              => 'Bandung',
            'province'          => 'Jawa Barat',
            'subscription_plan' => 'basic',
            'subscription_until'=> now()->addYear(),
            'max_users'         => 500,
            'is_active'         => true,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // User per role untuk school 1
        $this->createSchoolUsers($school1Id, 'demo');

        // ── School 2: SMK Negeri 2 Demo ────────────────────────────────
        $school2Id = Str::uuid()->toString();

        DB::table('schools')->insert([
            'id'                => $school2Id,
            'name'              => 'SMK Negeri 2 Demo',
            'slug'              => 'smkn2-demo',
            'npsn'              => '87654321',
            'level'             => 'SMK',
            'status'            => 'Negeri',
            'accreditation'     => 'B',
            'city'              => 'Surabaya',
            'province'          => 'Jawa Timur',
            'subscription_plan' => 'trial',
            'subscription_until'=> now()->addDays(30),
            'max_users'         => 100,
            'is_active'         => true,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $this->createSchoolUsers($school2Id, 'demo2');
    }

    private function createSchoolUsers(string $schoolId, string $suffix): void
    {
        $roles = [
            ['role' => 'kepsek',     'username' => "kepsek_{$suffix}",     'email' => "kepsek@{$suffix}.sch.id"],
            ['role' => 'kurikulum',  'username' => "kurikulum_{$suffix}",  'email' => "kurikulum@{$suffix}.sch.id"],
            ['role' => 'tu',         'username' => "tu_{$suffix}",         'email' => "tu@{$suffix}.sch.id"],
            ['role' => 'guru_mapel', 'username' => "guru_{$suffix}",       'email' => "guru@{$suffix}.sch.id"],
            ['role' => 'wali_kelas', 'username' => "wakel_{$suffix}",      'email' => "wakel@{$suffix}.sch.id"],
            ['role' => 'kesiswaan',  'username' => "kesiswaan_{$suffix}",  'email' => "kesiswaan@{$suffix}.sch.id"],
            ['role' => 'bk',         'username' => "bk_{$suffix}",         'email' => "bk@{$suffix}.sch.id"],
            ['role' => 'toolman',    'username' => "toolman_{$suffix}",    'email' => "toolman@{$suffix}.sch.id"],
            ['role' => 'siswa',      'username' => "siswa_{$suffix}",      'email' => "siswa@{$suffix}.sch.id"],
        ];

        foreach ($roles as $r) {
            DB::table('users')->insert([
                'id'        => Str::uuid()->toString(),
                'school_id' => $schoolId,
                'role'      => $r['role'],
                'username'  => $r['username'],
                'email'     => $r['email'],
                'password'  => bcrypt('password123'),
                'is_active' => true,
                'created_at'=> now(),
                'updated_at'=> now(),
            ]);
        }
    }
}
