<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_sensitive_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('staff_id')->unique()->constrained('staff')->cascadeOnDelete();

            $table->text('nip_encrypted')->nullable();
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
        Schema::dropIfExists('staff_sensitive_data');
    }
};