<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['slug' => 'superadmin',  'name' => 'Super Admin',      'color_primary' => '#1e293b', 'color_secondary' => '#334155', 'icon' => 'fa-crown'],
            ['slug' => 'kepsek',      'name' => 'Kepala Sekolah',   'color_primary' => '#4f46e5', 'color_secondary' => '#6366f1', 'icon' => 'fa-school'],
            ['slug' => 'kurikulum',   'name' => 'Kurikulum',        'color_primary' => '#7c3aed', 'color_secondary' => '#8b5cf6', 'icon' => 'fa-book'],
            ['slug' => 'tu',          'name' => 'Tata Usaha',       'color_primary' => '#db2777', 'color_secondary' => '#ec4899', 'icon' => 'fa-file'],
            ['slug' => 'guru_mapel',  'name' => 'Guru Mapel',       'color_primary' => '#0891b2', 'color_secondary' => '#06b6d4', 'icon' => 'fa-chalkboard-teacher'],
            ['slug' => 'wali_kelas',  'name' => 'Wali Kelas',       'color_primary' => '#0369a1', 'color_secondary' => '#0284c7', 'icon' => 'fa-home'],
            ['slug' => 'kesiswaan',   'name' => 'Kesiswaan',        'color_primary' => '#059669', 'color_secondary' => '#10b981', 'icon' => 'fa-users'],
            ['slug' => 'bk',          'name' => 'Bimbingan Konseling', 'color_primary' => '#d97706', 'color_secondary' => '#f59e0b', 'icon' => 'fa-heart'],
            ['slug' => 'toolman',     'name' => 'Toolman',          'color_primary' => '#dc2626', 'color_secondary' => '#ef4444', 'icon' => 'fa-tools'],
            ['slug' => 'siswa',       'name' => 'Siswa',            'color_primary' => '#7c3aed', 'color_secondary' => '#a78bfa', 'icon' => 'fa-user-graduate'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insertOrIgnore([
                ...$role,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
