<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Kunci Ed25519 per device - bagian dari lapisan device signing.
    // Tabel sudah aktif dibuat tapi LOGIKA signing belum diaktifkan (lihat SKILL.md).
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::create('device_keys', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('device_id');
            $table->text('public_key');
            $table->string('algorithm', 20)->default('ed25519');
            $table->boolean('is_active')->default(true);
            $table->timestamp('registered_at', 0)->useCurrent();
            $table->timestamp('revoked_at', 0)->nullable();

            $table->foreign('device_id')->references('id')->on('devices')->cascadeOnDelete();
        });

        DB::statement('CREATE INDEX idx_device_keys_device ON device_keys (device_id) WHERE (is_active = true)');
    }

    public function down(): void
    {
        Schema::dropIfExists('device_keys');
    }
};
