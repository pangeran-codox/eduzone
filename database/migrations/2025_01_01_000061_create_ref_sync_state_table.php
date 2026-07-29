<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Nyatet progress sinkronisasi MASUK (Laravel -> absensi-gateway) untuk
    // schools_ref/people_ref/schedules_ref. Ditulis oleh Go (internal/sync/puller.go),
    // Laravel TIDAK menulis ke tabel ini - cukup dibaca kalau nanti butuh
    // dashboard monitoring status sync.
    //
    // GUARD hasTable(): sama seperti migration photo_url, tabel ini
    // kemungkinan sudah dibuat manual duluan lewat absensi_schema.sql -
    // guard bikin migration aman dijalankan tanpa dobel-create/dobel-insert.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        if (Schema::hasTable('ref_sync_state')) {
            return;
        }

        Schema::create('ref_sync_state', function (Blueprint $table) {
            $table->string('resource', 30)->primary();
            $table->timestampTz('last_synced_at')->nullable();
            $table->string('last_status', 20)->default('never');
            $table->text('last_error')->nullable();
            $table->integer('last_record_count')->nullable();
            $table->timestamp('updated_at', 0)->useCurrent();
        });

        DB::statement("ALTER TABLE ref_sync_state ADD CONSTRAINT ref_sync_state_resource_check CHECK (resource IN ('schools','people','schedules'))");
        DB::statement("ALTER TABLE ref_sync_state ADD CONSTRAINT ref_sync_state_last_status_check CHECK (last_status IN ('never','success','failed'))");

        DB::connection('pgsql_absensi')->table('ref_sync_state')->insert([
            ['resource' => 'schools', 'last_status' => 'never', 'updated_at' => now()],
            ['resource' => 'people', 'last_status' => 'never', 'updated_at' => now()],
            ['resource' => 'schedules', 'last_status' => 'never', 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_sync_state');
    }
};
