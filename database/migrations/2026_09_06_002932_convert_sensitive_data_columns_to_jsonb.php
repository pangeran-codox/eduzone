<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tables = [
        'student_sensitive_data' => [
            'nis', 'nisn', 'birth_place', 'birth_date', 'religion', 'address', 'phone',
            'father_name', 'mother_name', 'father_job', 'mother_job', 'parent_address', 'parent_phone',
        ],
        'teacher_sensitive_data' => [
            'nip', 'nuptk', 'birth_place', 'birth_date', 'religion', 'address', 'phone',
        ],
        'staff_sensitive_data' => [
            'nip', 'birth_place', 'birth_date', 'religion', 'address', 'phone',
        ],
    ];

    /**
     * AMAN dijalankan karena semua kolom ini dipastikan masih NULL —
     * fitur input data siswa/guru/staff belum pernah dibangun, dan gRPC
     * belum pernah aktif sebelum perbaikan ini. Kalau ternyata SUDAH ada
     * isi non-NULL yang bukan JSON valid, migration ini akan GAGAL di
     * ::jsonb cast — itu SENGAJA, supaya tidak diam-diam merusak data
     * lama tanpa disadari siapa pun.
     */
    public function up(): void
    {
        foreach ($this->tables as $table => $fields) {
            foreach ($fields as $field) {
                $column = "{$field}_encrypted";
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE jsonb USING {$column}::jsonb");
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table => $fields) {
            foreach ($fields as $field) {
                $column = "{$field}_encrypted";
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE text USING {$column}::text");
            }
        }
    }
};