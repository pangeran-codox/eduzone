<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Pengecualian jadwal per TANGGAL: "tanggal X, jadwal Y diajar guru Z".
    // Jadwal reguler (schedules) sengaja tidak diubah, karena jadwal itu
    // berulang tiap minggu dan tidak punya konsep "cuma untuk 1 tanggal".
    public function up(): void
    {
        Schema::create('teaching_substitutions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->date('date');
            $table->foreignUuid('original_teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignUuid('substitute_teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->string('reason', 255)->nullable();
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Satu jadwal cuma boleh punya 1 pengganti per tanggal.
            $table->unique(['schedule_id', 'date']);
            $table->index(['school_id', 'date']);
            $table->index(['substitute_teacher_id', 'date']);
            $table->index(['original_teacher_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_substitutions');
    }
};