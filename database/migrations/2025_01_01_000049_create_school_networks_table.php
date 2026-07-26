<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Daftar IP/hostname yang diizinkan per sekolah untuk validasi check-in guru via HP.
    // AKTIF dipakai dari awal rilis (bukan fitur dorman) - lihat ARCHITECTURE.md §2.2.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::create('school_networks', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('school_id');
            $table->string('label', 100);
            $table->string('ip_or_hostname');
            $table->boolean('is_dynamic')->default(false);
            $table->boolean('requires_local_verifier')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent();

            $table->foreign('school_id')->references('school_id')->on('schools_ref')->cascadeOnDelete();
        });

        DB::statement('CREATE INDEX idx_school_networks_school ON school_networks (school_id) WHERE (is_active = true)');
    }

    public function down(): void
    {
        Schema::dropIfExists('school_networks');
    }
};
