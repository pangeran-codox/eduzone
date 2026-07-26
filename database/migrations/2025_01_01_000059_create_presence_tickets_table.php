<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Tiket presence buat local_verifiers (solusi CGNAT) - tabel sudah ada TAPI
    // belum diaktifkan logikanya, nempel sama status dorman local_verifiers.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::create('presence_tickets', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('verifier_id');
            $table->string('nonce', 64);
            $table->text('signature');
            $table->timestamp('issued_at', 0)->useCurrent();
            $table->timestamp('expires_at', 0);
            $table->timestamp('used_at', 0)->nullable();
            // Sengaja TANPA foreign key ke attendance_events (beda dengan qr_tokens.used_by_event_id
            // yang punya FK) - konsisten dengan schema asli 01_schema.sql & DB yang sudah dibuat manual.
            $table->unsignedBigInteger('used_by_event_id')->nullable();

            $table->foreign('verifier_id')->references('id')->on('local_verifiers')->cascadeOnDelete();
            $table->unique(['verifier_id', 'nonce'], 'presence_tickets_verifier_id_nonce_key');
        });

        DB::statement('CREATE INDEX idx_presence_tickets_active ON presence_tickets (verifier_id, expires_at) WHERE (used_at IS NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('presence_tickets');
    }
};
