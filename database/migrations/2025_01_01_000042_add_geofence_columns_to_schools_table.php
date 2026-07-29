<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Kolom GPS untuk geofencing check-in guru via HP (absensi-gateway).
    // Tanpa ini, endpoint sync/schools tidak bisa mengirim data yang
    // dibutuhkan gateway untuk validasi radius GPS - lihat
    // laravel-sync-contract.md di repo absensi-gateway.
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('postal_code');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->integer('geofence_radius_meters')->default(150)->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'geofence_radius_meters']);
        });
    }
};
