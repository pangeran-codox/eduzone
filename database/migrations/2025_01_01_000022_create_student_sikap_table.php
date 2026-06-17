<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_sikap', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('academic_year')->default('2025/2026');
            $table->string('semester')->default('Ganjil'); // Ganjil/Genap
            $table->string('sikap_spiritual')->default('B'); // SB/B/C/K
            $table->string('sikap_sosial')->default('B'); // SB/B/C/K
            $table->text('catatan_sikap')->nullable();
            $table->text('ekskul')->nullable();
            $table->text('catatan_wakel')->nullable();
            $table->integer('ketidakhadiran_sakit')->default(0);
            $table->integer('ketidakhadiran_izin')->default(0);
            $table->integer('ketidakhadiran_alpa')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_sikap');
    }
};
