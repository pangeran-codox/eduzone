<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Terminal absensi fisik (kamera face-recognition, RFID reader, QR scanner, kiosk manual).
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('school_id');
            $table->string('device_code', 50);
            $table->string('name', 150);
            $table->string('device_type', 20);
            $table->string('location', 150)->nullable();
            $table->uuid('default_class_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('api_key_hash', 64)->nullable();
            $table->timestamp('last_seen_at', 0)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent();

            $table->foreign('school_id')->references('school_id')->on('schools_ref')->cascadeOnDelete();
            $table->unique(['school_id', 'device_code'], 'devices_school_id_device_code_key');
        });

        DB::statement("ALTER TABLE devices ADD CONSTRAINT devices_device_type_check CHECK (device_type IN ('face_camera','rfid_reader','qr_scanner','hybrid','manual_kiosk'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
