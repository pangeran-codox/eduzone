<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Solusi untuk sekolah dengan ISP residensial ber-CGNAT (IP publik tidak stabil).
    // Tabel sudah ada tapi BELUM AKTIF - tahap awal cukup GPS + IP publik stabil.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::create('local_verifiers', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('school_id');
            $table->string('internal_hostname');
            $table->text('public_key');
            $table->string('algorithm', 20)->default('ed25519');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_heartbeat_at', 0)->nullable();
            $table->timestamp('created_at', 0)->useCurrent();

            $table->foreign('school_id')->references('school_id')->on('schools_ref')->cascadeOnDelete();
        });

        DB::statement('CREATE INDEX idx_local_verifiers_school ON local_verifiers (school_id) WHERE (is_active = true)');
    }

    public function down(): void
    {
        Schema::dropIfExists('local_verifiers');
    }
};
