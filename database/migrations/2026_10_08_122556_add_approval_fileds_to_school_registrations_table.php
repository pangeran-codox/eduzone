<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahan untuk alur approve: akun TU yang dibuat + token set-password.
 * Token disimpan di tabel pendaftaran (bukan password broker Laravel) karena
 * users.email tidak terbukti unik dan route reset kata sandi belum ada.
 * Migration terpisah supaya aman dijalankan baik setelah maupun sebelum
 * migration create_school_registrations_table sudah dijalankan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_registrations', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Simpan HASH token, bukan token mentah
            $table->string('password_setup_token_hash')->nullable()->index();
            $table->timestamp('password_setup_expires_at')->nullable();
            $table->timestamp('password_setup_used_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('school_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'password_setup_token_hash',
                'password_setup_expires_at',
                'password_setup_used_at',
            ]);
        });
    }
};