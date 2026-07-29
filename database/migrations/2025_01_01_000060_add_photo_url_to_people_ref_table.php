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
    //
    // GUARD hasColumn(): kolom ini sempat sudah ditambahkan manual duluan
    // di database fisik (lewat absensi_schema.sql versi terbaru) sebelum
    // migration ini dibuat - guard ini bikin migration tetap AMAN dijalankan
    // (no-op kalau sudah ada), sambil tetap tercatat "sudah jalan" di
    // tabel migrations Laravel untuk konsistensi riwayat.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        if (! Schema::hasColumn('people_ref', 'photo_url')) {
            Schema::table('people_ref', function (Blueprint $table) {
                $table->text('photo_url')->nullable()->after('full_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('people_ref', 'photo_url')) {
            Schema::table('people_ref', function (Blueprint $table) {
                $table->dropColumn('photo_url');
            });
        }
    }
};
