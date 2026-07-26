<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Cache read-only jadwal pelajaran dari DB utama, dipakai attendance_period
    // buat tahu absensi per mapel/periode, bukan cuma harian.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::create('schedules_ref', function (Blueprint $table) {
            $table->uuid('schedule_id')->primary(); // id asli dari jadwal DB utama
            $table->uuid('school_id');
            $table->uuid('class_id');
            $table->string('subject_name', 150);
            $table->uuid('teacher_id');
            $table->smallInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamp('synced_at', 0)->useCurrent();

            $table->foreign('school_id')->references('school_id')->on('schools_ref')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE schedules_ref ADD CONSTRAINT schedules_ref_day_of_week_check CHECK (day_of_week >= 1 AND day_of_week <= 7)');
        DB::statement('CREATE INDEX idx_schedules_class_day ON schedules_ref (class_id, day_of_week) WHERE (is_active = true)');
        DB::statement('CREATE INDEX idx_schedules_teacher_day ON schedules_ref (teacher_id, day_of_week) WHERE (is_active = true)');
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules_ref');
    }
};
