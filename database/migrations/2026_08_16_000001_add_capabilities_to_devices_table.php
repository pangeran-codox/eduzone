<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Ganti device_type dari "pilih 1 tipe" jadi turunan dari capabilities
    // (jsonb array) — device fisik di kenyataan sering punya lebih dari 1
    // metode input sekaligus (RFID + QR + Wajah bersamaan), 'hybrid' lama
    // cuma menutup 1 kombinasi spesifik (RFID+QR) dan tidak scalable.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->jsonb('capabilities')->nullable()->after('device_type');
        });

        // Migrasi device lama: turunkan capabilities dari device_type yang
        // sudah ada, supaya device yang dibuat sebelum kolom ini ada tidak
        // kehilangan info kemampuannya.
        DB::statement(<<<SQL
            UPDATE devices SET capabilities = CASE device_type
                WHEN 'face_camera' THEN '["face"]'::jsonb
                WHEN 'rfid_reader' THEN '["rfid"]'::jsonb
                WHEN 'qr_scanner' THEN '["qr"]'::jsonb
                WHEN 'hybrid' THEN '["rfid", "qr"]'::jsonb
                WHEN 'manual_kiosk' THEN '["manual"]'::jsonb
                ELSE '[]'::jsonb
            END
        SQL);

        DB::statement('ALTER TABLE devices ALTER COLUMN capabilities SET NOT NULL');
        DB::statement("ALTER TABLE devices ALTER COLUMN capabilities SET DEFAULT '[]'::jsonb");

        // Tambah fingerprint_reader — supaya device dengan capabilities
        // tunggal ['fingerprint'] punya label device_type yang akurat,
        // bukan jatuh ke 'hybrid' sebagai kompromi.
        DB::statement('ALTER TABLE devices DROP CONSTRAINT devices_device_type_check');
        DB::statement("ALTER TABLE devices ADD CONSTRAINT devices_device_type_check CHECK (device_type IN ('face_camera','rfid_reader','qr_scanner','fingerprint_reader','hybrid','manual_kiosk'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE devices DROP CONSTRAINT devices_device_type_check');
        DB::statement("ALTER TABLE devices ADD CONSTRAINT devices_device_type_check CHECK (device_type IN ('face_camera','rfid_reader','qr_scanner','hybrid','manual_kiosk'))");

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('capabilities');
        });
    }
};
