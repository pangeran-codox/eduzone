<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('grade'); // X/XI/XII
            $table->foreignUuid('major_id')->constrained('majors')->cascadeOnDelete();
            $table->string('class_group', 10);
            $table->string('academic_year', 10)->default('2025/2026');
            $table->string('nama_kelas')->nullable();
            $table->integer('kapasitas')->default(36);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'grade', 'major_id', 'class_group', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
