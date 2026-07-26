<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Agregat per PERIODE/mapel (beda dari attendance_daily yang per hari) - dipakai
    // absensi mapel oleh guru_mapel. Catatan: person_type di sini cuma student/teacher
    // (staff tidak relevan untuk absensi per-periode mapel), beda dengan tabel lain.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::create('attendance_period', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('school_id');
            $table->uuid('schedule_id');
            $table->uuid('person_id');
            $table->string('person_type', 20);
            $table->date('date');
            $table->time('first_check_in')->nullable();
            $table->time('last_check_out')->nullable();
            $table->string('status', 20)->default('Hadir');
            $table->string('primary_method', 20)->nullable();
            $table->integer('total_events')->default(0);
            $table->boolean('has_anomaly')->default(false);
            $table->timestamp('updated_at', 0)->useCurrent();

            $table->foreign('school_id')->references('school_id')->on('schools_ref')->cascadeOnDelete();
            $table->foreign('schedule_id')->references('schedule_id')->on('schedules_ref');
            $table->unique(['school_id', 'schedule_id', 'person_id', 'person_type', 'date'], 'attendance_period_school_id_schedule_id_person_id_person_ty_key');
            $table->index(['school_id', 'date'], 'idx_period_school_date');
            $table->index(['schedule_id', 'date'], 'idx_period_schedule');
            $table->index(['person_id', 'person_type', 'date'], 'idx_period_person');
        });

        DB::statement("ALTER TABLE attendance_period ADD CONSTRAINT attendance_period_person_type_check CHECK (person_type IN ('student','teacher'))");
        DB::statement("ALTER TABLE attendance_period ADD CONSTRAINT attendance_period_status_check CHECK (status IN ('Hadir','Terlambat','Sakit','Izin','Alpa'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_period');
    }
};
