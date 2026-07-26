<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Embedding wajah terenkripsi, DIPISAH dari `credentials` karena data
    // biometrik butuh perlakuan khusus (mengikuti pola *_sensitive_data di DB utama).
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::create('face_templates', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('credential_id');
            $table->binary('embedding_encrypted'); // bytea, sudah terenkripsi sebelum masuk sini
            $table->string('model_version', 50);
            $table->decimal('quality_score', 5, 2)->nullable();
            $table->timestamp('created_at', 0)->useCurrent();

            $table->foreign('credential_id')->references('id')->on('credentials')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_templates');
    }
};
