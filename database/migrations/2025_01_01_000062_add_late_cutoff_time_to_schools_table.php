<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cache late_cutoff_time dari schools (DB utama) - dipakai
    // internal/aggregation di absensi-gateway buat status "Terlambat".
    // GUARD hasColumn(): kemungkinan sudah ditambahkan duluan lewat update
    // absensi_schema.sql fisik (pola yang sama seperti photo_url &
    // ref_sync_state sebelumnya) - aman dijalankan di kondisi apapun.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        if (! Schema::hasColumn('schools_ref', 'late_cutoff_time')) {
            Schema::table('schools_ref', function (Blueprint $table) {
                $table->time('late_cutoff_time')->nullable()->after('geofence_radius_meters');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('schools_ref', 'late_cutoff_time')) {
            Schema::table('schools_ref', function (Blueprint $table) {
                $table->dropColumn('late_cutoff_time');
            });
        }
    }
};