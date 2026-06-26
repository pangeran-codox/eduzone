<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // Hash untuk lookup/search exact-match (data asli disimpan terenkripsi di teacher_sensitive_data)
            $table->string('nip_hash', 64)->nullable()->index();
            $table->string('nuptk_hash', 64)->nullable()->index();

            $table->string('full_name'); // tetap plain, perlu searchable/sortable
            $table->string('email')->nullable(); // plain - dipakai aktif untuk notifikasi
            $table->string('gender')->nullable(); // L/P
            $table->string('last_education')->default('S1'); // D3/S1/S2/S3
            $table->string('education_major')->nullable();
            $table->string('employment_status')->nullable(); // PNS/PPPK/Honorer/GTY/GTT
            $table->date('joined_date')->nullable();
            $table->foreignUuid('major_id')->nullable()->constrained('majors')->nullOnDelete();
            $table->boolean('is_homeroom')->default(false);
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};