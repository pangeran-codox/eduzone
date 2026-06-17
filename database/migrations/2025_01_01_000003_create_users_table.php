<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
            $table->string('role')->nullable(); // superadmin/kepsek/kurikulum/tu/guru_mapel/wali_kelas/kesiswaan/bk/toolman/siswa
            $table->string('username', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('password', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['school_id', 'email']);
            $table->unique(['school_id', 'username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
