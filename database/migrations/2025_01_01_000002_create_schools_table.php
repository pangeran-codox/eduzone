<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('npsn')->nullable();
            $table->string('nss')->nullable();
            $table->string('level')->nullable(); // SD/SMP/SMA/SMK
            $table->string('status')->nullable(); // Negeri/Swasta
            $table->string('accreditation')->nullable(); // A/B/C/Belum Terakreditasi
            $table->text('address')->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('logo')->nullable();
            $table->string('principal_name')->nullable();
            $table->text('principal_nip')->nullable(); // text - akan disimpan terenkripsi
            $table->text('vision')->nullable();
            $table->text('mission')->nullable();
            $table->string('motto')->nullable();
            $table->string('bank_name')->nullable();
            $table->text('bank_account_number')->nullable(); // text - akan disimpan terenkripsi
            $table->text('bank_account_name')->nullable(); // text - akan disimpan terenkripsi
            $table->string('subscription_plan')->default('trial'); // trial/basic/pro
            $table->date('subscription_until')->nullable();
            $table->integer('max_users')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamp('onboarded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};