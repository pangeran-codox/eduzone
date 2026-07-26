<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Jejak sinkronisasi attendance_daily/attendance_period -> student_attendance/
    // teacher_attendance di DB utama, dijalankan via job terjadwal.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::create('sync_log', function (Blueprint $table) {
            $table->id(); // bigint identity
            $table->string('source_table', 30);
            $table->uuid('source_id');
            $table->string('target_table', 50); // nama tabel di DB UTAMA, bukan FK lokal
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('attempted_at', 0)->nullable();
            $table->timestamp('synced_at', 0)->nullable();
            $table->timestamp('created_at', 0)->useCurrent();

            $table->index(['source_table', 'source_id'], 'idx_sync_log_source');
        });

        DB::statement("ALTER TABLE sync_log ADD CONSTRAINT sync_log_source_table_check CHECK (source_table IN ('attendance_daily','attendance_period'))");
        DB::statement("ALTER TABLE sync_log ADD CONSTRAINT sync_log_status_check CHECK (status IN ('pending','success','failed'))");
        DB::statement("CREATE INDEX idx_sync_log_pending ON sync_log (status) WHERE (status = 'pending')");
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_log');
    }
};
