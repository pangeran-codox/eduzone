<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insertOrIgnore([
            'id'         => Str::uuid(),
            'school_id'  => null,
            'role_id'    => null,
            'role'       => 'superadmin',
            'username'   => 'superadmin',
            'email'      => 'superadmin@eduzone.id',
            'password'   => bcrypt('superadmin123'),
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
