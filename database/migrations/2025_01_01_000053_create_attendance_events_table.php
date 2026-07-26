<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // PENTING: tabel ini INSERT-ONLY. Jangan buat kode yang UPDATE/DELETE baris di sini
    // (lihat SKILL.md & ARCHITECTURE.md §2.4). Rencana ke depan: REVOKE UPDATE, DELETE
    // di level DB role setelah alur insert-only teruji.
    // row_hash/prev_hash/signature: kolom hash chaining & device signing sudah ada,
    // TAPI belum diisi/divalidasi - lapisan keamanan ini belum diaktifkan.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::create('attendance_events', function (Blueprint $table) {
            $table->id(); // bigint identity
            $table->uuid('school_id');
            $table->uuid('device_id')->nullable();
            $table->uuid('schedule_id')->nullable();
            $table->uuid('person_id')->nullable(); // null = gagal dikenali / tidak match
            $table->string('person_type', 20)->nullable();
            $table->string('method', 20);
            $table->string('event_type', 15);
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->boolean('is_valid')->default(true);
            $table->string('flagged_reason', 100)->nullable();
            $table->jsonb('raw_payload')->nullable();
            $table->timestamp('recorded_at', 0)->useCurrent();
            $table->string('row_hash', 64)->nullable();
            $table->string('prev_hash', 64)->nullable();
            $table->text('signature')->nullable();

            $table->foreign('school_id')->references('school_id')->on('schools_ref')->cascadeOnDelete();
            $table->foreign('device_id')->references('id')->on('devices')->nullOnDelete();
            $table->foreign('schedule_id')->references('schedule_id')->on('schedules_ref');

            $table->index(['school_id', 'recorded_at'], 'idx_events_school_date');
            $table->index(['person_id', 'person_type', 'recorded_at'], 'idx_events_person');
            $table->index(['device_id', 'recorded_at'], 'idx_events_device');
        });

        DB::statement("ALTER TABLE attendance_events ADD CONSTRAINT attendance_events_person_type_check CHECK (person_type IN ('student','teacher','staff'))");
        DB::statement("ALTER TABLE attendance_events ADD CONSTRAINT attendance_events_method_check CHECK (method IN ('rfid','qr','face','fingerprint','manual'))");
        DB::statement("ALTER TABLE attendance_events ADD CONSTRAINT attendance_events_event_type_check CHECK (event_type IN ('check_in','check_out','unknown'))");
        DB::statement('CREATE INDEX idx_events_schedule ON attendance_events (schedule_id, recorded_at) WHERE (schedule_id IS NOT NULL)');
        DB::statement('CREATE INDEX idx_events_unrecognized ON attendance_events (school_id, recorded_at) WHERE (person_id IS NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_events');
    }
};
