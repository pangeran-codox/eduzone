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
            $table->string('nip')->nullable();
            $table->string('nuptk')->nullable();
            $table->string('full_name');
            $table->string('gender')->nullable(); // L/P
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
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
