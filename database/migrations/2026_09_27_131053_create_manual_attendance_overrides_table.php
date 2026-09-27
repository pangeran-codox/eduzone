<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Keputusan manual TU (Izin/Sakit/Alpa) disimpan TERPISAH dari
    // attendance_daily, supaya jadi rujukan "benar" yang kebal ditimpa
    // saat gateway meng-agregasi ulang attendance_daily dari
    // attendance_events (race condition yang didokumentasikan di
    // ManualAttendanceController). attendance_daily tetap ditulis
    // langsung juga untuk efek instan - tabel inilah yang dipakai buat
    // "menyembuhkan" balik kalau ketiban timpaan gateway (lihat
    // ReapplyManualAttendanceOverrides).
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::create('manual_attendance_overrides', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('school_id');
            $table->uuid('person_id');
            $table->string('person_type', 20);
            $table->date('date');
            $table->string('status', 20);
            $table->text('notes')->nullable();
            $table->uuid('recorded_by')->nullable(); // users.id di DB utama, tidak di-FK (beda database)
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent();

            $table->foreign('school_id')->references('school_id')->on('schools_ref')->cascadeOnDelete();
            $table->unique(['school_id', 'person_id', 'person_type', 'date'], 'manual_override_unique');
            $table->index(['school_id', 'date'], 'idx_manual_override_school_date');
        });

        DB::statement("ALTER TABLE manual_attendance_overrides ADD CONSTRAINT manual_override_person_type_check CHECK (person_type IN ('student','teacher'))");
        DB::statement("ALTER TABLE manual_attendance_overrides ADD CONSTRAINT manual_override_status_check CHECK (status IN ('Sakit','Izin','Alpa'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_attendance_overrides');
    }
};