<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Alur approval koreksi data absensi - tabel sudah ada TAPI belum diaktifkan
    // logikanya. Jangan bangun UI approval untuk ini di tahap pertama kecuali diminta
    // eksplisit (lihat ARCHITECTURE.md §2.4). Koreksi versi awal masih langsung/manual.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::create('attendance_correction_log', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->string('source_table', 30); // 'attendance_daily' | 'attendance_period'
            $table->uuid('source_id');
            $table->uuid('requested_by');
            $table->text('reason');
            $table->jsonb('data_lama');
            $table->jsonb('data_baru');
            $table->string('status', 20)->default('pending');
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at', 0)->nullable();
            $table->timestamp('created_at', 0)->useCurrent();

            $table->index(['source_table', 'source_id'], 'idx_correction_source');
        });

        DB::statement("ALTER TABLE attendance_correction_log ADD CONSTRAINT attendance_correction_log_source_table_check CHECK (source_table IN ('attendance_daily','attendance_period'))");
        DB::statement("ALTER TABLE attendance_correction_log ADD CONSTRAINT attendance_correction_log_status_check CHECK (status IN ('pending','approved','rejected'))");
        DB::statement("CREATE INDEX idx_correction_pending ON attendance_correction_log (status) WHERE (status = 'pending')");
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_log');
    }
};
