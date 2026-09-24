<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * attendance_daily belum punya kolom untuk catatan/alasan (mis. "Sakit
 * demam, ada surat dokter") — dibutuhkan untuk fitur input manual
 * Izin/Sakit/Alpa oleh TU (23 Sep 2026). student_attendance/
 * teacher_attendance di DB utama sudah punya kolom ini sejak awal.
 */
return new class extends Migration
{
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::connection('pgsql_absensi')->table('attendance_daily', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('has_anomaly');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_absensi')->table('attendance_daily', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};