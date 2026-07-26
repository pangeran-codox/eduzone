<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Agregat HARIAN per orang, hasil UPSERT dari attendance_events.
    // Sengaja dibuat mirip struktur student_attendance/teacher_attendance di DB utama
    // supaya sinkronisasi via sync_log jadi mapping 1:1.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::create('attendance_daily', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('school_id');
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
            $table->unique(['school_id', 'person_id', 'person_type', 'date'], 'attendance_daily_school_id_person_id_person_type_date_key');
            $table->index(['school_id', 'date'], 'idx_daily_school_date');
        });

        DB::statement("ALTER TABLE attendance_daily ADD CONSTRAINT attendance_daily_person_type_check CHECK (person_type IN ('student','teacher','staff'))");
        DB::statement("ALTER TABLE attendance_daily ADD CONSTRAINT attendance_daily_status_check CHECK (status IN ('Hadir','Terlambat','Sakit','Izin','Alpa'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_daily');
    }
};
