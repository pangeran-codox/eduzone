<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // Hash untuk lookup/search exact-match (data asli disimpan terenkripsi di student_sensitive_data)
            $table->string('nis_hash', 64)->nullable()->index();
            $table->string('nisn_hash', 64)->nullable()->index();

            $table->string('full_name'); // tetap plain, perlu searchable/sortable
            $table->string('email')->nullable(); // plain - dipakai aktif untuk notifikasi, gak perlu dienkripsi
            $table->string('gender')->nullable(); // L/P
            $table->string('grade')->nullable(); // X/XI/XII
            $table->foreignUuid('major_id')->nullable()->constrained('majors')->nullOnDelete();
            $table->string('class_group')->nullable(); // 1/2/3/4
            $table->foreignUuid('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->date('joined_date')->nullable();
            $table->string('status')->default('aktif'); // aktif/lulus/pindah/drop_out
            $table->string('photo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};