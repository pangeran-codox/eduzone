<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Postgres tidak bisa ALTER CHECK constraint langsung — harus DROP lalu ADD ulang.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        DB::statement('ALTER TABLE attendance_events DROP CONSTRAINT attendance_events_method_check');
        DB::statement("ALTER TABLE attendance_events ADD CONSTRAINT attendance_events_method_check CHECK (method IN ('rfid','qr','face','fingerprint','manual','mobile_gps'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE attendance_events DROP CONSTRAINT attendance_events_method_check');
        DB::statement("ALTER TABLE attendance_events ADD CONSTRAINT attendance_events_method_check CHECK (method IN ('rfid','qr','face','fingerprint','manual'))");
    }
};