<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Nambah kolom photo_url ke people_ref, sesuai update absensi_schema.sql
    // dari sisi absensi-gateway - dipakai buat cache URL foto profil yang
    // disinkron dari DB utama, ditampilkan di response check-in Go
    // (person.photo_url, selalu ada - foto asli atau avatar inisial SVG
    // generated kalau belum ada foto).
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::table('people_ref', function (Blueprint $table) {
            $table->text('photo_url')->nullable()->after('full_name');
        });
    }

    public function down(): void
    {
        Schema::table('people_ref', function (Blueprint $table) {
            $table->dropColumn('photo_url');
        });
    }
};
