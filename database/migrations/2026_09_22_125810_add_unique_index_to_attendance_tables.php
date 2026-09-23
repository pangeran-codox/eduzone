<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mencegah insert duplikat dari absensi:sync-daily-to-main dan
 * absensi:sync-teacher-daily-to-main - kedua command itu pakai
 * firstOrNew(school_id, student_id/teacher_id, date) tapi sebelum
 * migration ini tidak ada unique constraint yang menjaminnya di level
 * database. Kalau dua run job tumpang tindih (mis. job sebelumnya
 * belum selesai saat job berikutnya mulai), race ini bisa insert 2
 * baris untuk kombinasi yang sama. Ditemukan saat audit Absensi 22 Sep
 * 2026, belum pernah jadi masalah aktif tapi perlu ditutup sebelum
 * volume data besar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_attendance', function (Blueprint $table) {
            $table->unique(['school_id', 'student_id', 'date'], 'student_attendance_unique_per_day');
        });

        Schema::table('teacher_attendance', function (Blueprint $table) {
            $table->unique(['school_id', 'teacher_id', 'date'], 'teacher_attendance_unique_per_day');
        });
    }

    public function down(): void
    {
        Schema::table('student_attendance', function (Blueprint $table) {
            $table->dropUnique('student_attendance_unique_per_day');
        });

        Schema::table('teacher_attendance', function (Blueprint $table) {
            $table->dropUnique('teacher_attendance_unique_per_day');
        });
    }
};