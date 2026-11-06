<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prasyarat input absensi per jam pelajaran oleh guru mapel: kedua tabel tujuan hanya punya primary key,
 * jadi menyimpan dua kali menghasilkan baris ganda.
 *
 * - teaching_attendance: satu sesi per jadwal per tanggal
 * - student_subject_attendance: satu baris per murid per sesi
 *
 * Di database lokal tidak ada duplikat (dicek 9 Okt 2026). Data produksi bisa berbeda, jadi migration ini
 * memeriksa duplikat dulu dan berhenti dengan pesan jelas, tidak gagal setengah jalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $dupSessions = DB::selectOne(
            'select count(*) as c from (
                select 1 from teaching_attendance group by schedule_id, date having count(*) > 1
            ) x'
        )->c;

        $dupStudents = DB::selectOne(
            'select count(*) as c from (
                select 1 from student_subject_attendance group by teaching_attendance_id, student_id having count(*) > 1
            ) x'
        )->c;

        if ($dupSessions > 0 || $dupStudents > 0) {
            throw new RuntimeException(
                "Migration dibatalkan: ada data ganda ({$dupSessions} sesi ganda di teaching_attendance, "
                . "{$dupStudents} murid ganda di student_subject_attendance). Bersihkan dulu sebelum memasang unique index."
            );
        }

        Schema::table('teaching_attendance', function (Blueprint $table) {
            $table->unique(['schedule_id', 'date'], 'teaching_attendance_schedule_date_unique');
        });

        Schema::table('student_subject_attendance', function (Blueprint $table) {
            $table->unique(['teaching_attendance_id', 'student_id'], 'student_subject_attendance_session_student_unique');
        });
    }

    public function down(): void
    {
        Schema::table('student_subject_attendance', function (Blueprint $table) {
            $table->dropUnique('student_subject_attendance_session_student_unique');
        });

        Schema::table('teaching_attendance', function (Blueprint $table) {
            $table->dropUnique('teaching_attendance_schedule_date_unique');
        });
    }
};