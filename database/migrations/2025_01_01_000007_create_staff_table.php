<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('full_name'); // tetap plain, perlu searchable/sortable
            $table->string('nip_hash', 64)->nullable()->index(); // hash untuk lookup, data asli di staff_sensitive_data
            $table->string('email')->nullable(); // plain - dipakai aktif untuk notifikasi
            $table->string('gender')->nullable(); // L/P
            $table->string('position')->nullable();
            $table->date('joined_date')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};