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
            $table->string('nis')->nullable();
            $table->string('nisn')->nullable();
            $table->string('full_name');
            $table->string('gender')->nullable(); // L/P
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('grade')->nullable(); // X/XI/XII
            $table->foreignUuid('major_id')->nullable()->constrained('majors')->nullOnDelete();
            $table->string('class_group')->nullable(); // 1/2/3/4
            $table->foreignUuid('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('father_job')->nullable();
            $table->string('mother_job')->nullable();
            $table->text('parent_address')->nullable();
            $table->string('parent_phone')->nullable();
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
