<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cache read-only dari data master `schools` di DB utama EduZone.
    // Disinkron SATU ARAH (utama -> absensi). Jangan tulis balik dari sini.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::create('schools_ref', function (Blueprint $table) {
            // Bukan default gen_random_uuid() - school_id ini SAMA PERSIS dengan
            // id di tabel `schools` DB utama, jadi harus di-set eksplisit saat sync.
            $table->uuid('school_id')->primary();
            $table->string('name');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('geofence_radius_meters')->default(150);
            $table->boolean('is_active')->default(true);
            $table->timestamp('synced_at', 0)->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools_ref');
    }
};
