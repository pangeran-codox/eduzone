<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_sensitive_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('teacher_id')->unique()->constrained('teachers')->cascadeOnDelete();

            // Semua kolom di bawah disimpan dalam format terenkripsi (lihat EncryptionGrpcService)
            // Format per kolom: base64(iv) . ':' . base64(cipher_text)
            $table->text('nip_encrypted')->nullable();
            $table->text('nuptk_encrypted')->nullable();
            $table->text('birth_place_encrypted')->nullable();
            $table->text('birth_date_encrypted')->nullable();
            $table->text('religion_encrypted')->nullable();
            $table->text('address_encrypted')->nullable();
            $table->text('phone_encrypted')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_sensitive_data');
    }
};