<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // QR token yang berotasi (anti screenshot-share) - tabel sudah ada TAPI belum
    // diaktifkan logikanya. Fokus rilis pertama: QR statis per credential dulu.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::create('qr_tokens', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('device_id');
            $table->string('token', 20);
            $table->timestamp('valid_from', 0);
            $table->timestamp('valid_until', 0);
            $table->timestamp('used_at', 0)->nullable();
            $table->foreignId('used_by_event_id')->nullable()->constrained('attendance_events');

            $table->foreign('device_id')->references('id')->on('devices')->cascadeOnDelete();
            $table->unique(['device_id', 'token'], 'qr_tokens_device_id_token_key');
        });

        DB::statement('CREATE INDEX idx_qr_tokens_active ON qr_tokens (device_id, valid_until) WHERE (used_at IS NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_tokens');
    }
};
