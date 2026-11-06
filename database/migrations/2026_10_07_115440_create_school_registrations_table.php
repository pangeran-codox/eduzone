<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pengajuan pendaftaran sekolah (tenant) sebelum sekolah resmi dibuat.
 * Tabel ini SENGAJA tidak memakai BelongsToSchool: belum ada tenant saat pendaftaran.
 * Lihat PERENCANAAN_PENDAFTARAN_TENANT.md bagian 3 dan 5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Data sekolah (dipetakan ke kolom schools saat approve)
            $table->string('school_name');
            $table->string('npsn')->nullable();
            $table->string('level')->nullable();
            $table->text('address')->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('school_phone')->nullable();

            // Data pendaftar (calon akun Tata Usaha)
            $table->string('applicant_name');
            $table->string('applicant_email');
            $table->string('applicant_phone')->nullable();
            $table->string('applicant_position')->nullable();

            // State machine: pending_email | pending_approval | approved | rejected | expired
            $table->string('status')->default('pending_email')->index();

            // Verifikasi email: simpan HASH token, bukan token mentah
            $table->string('email_verification_token_hash')->nullable()->index();
            $table->timestamp('email_verification_expires_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();

            // Review superadmin
            // TODO: pastikan tipe users.id (uuid?) via `model:show User` sebelum menambah FK
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Terisi setelah approve
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();

            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        // Cegah pengajuan ganda yang masih aktif untuk email / NPSN yang sama
        DB::statement(
            "CREATE UNIQUE INDEX school_registrations_active_email_unique
             ON school_registrations (lower(applicant_email))
             WHERE status IN ('pending_email', 'pending_approval')"
        );
        DB::statement(
            "CREATE UNIQUE INDEX school_registrations_active_npsn_unique
             ON school_registrations (npsn)
             WHERE npsn IS NOT NULL AND status IN ('pending_email', 'pending_approval')"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('school_registrations');
    }
};