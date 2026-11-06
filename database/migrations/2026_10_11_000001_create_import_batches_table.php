<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu baris = satu unggahan Excel (kelas | siswa | guru | jadwal).
 * Lihat PERENCANAAN_IMPOR_EXCEL.md. Data per baris Excel TIDAK disimpan di sini: file diunggah dibaca ulang
 * saat impor (dan divalidasi ulang), sehingga hasilnya selalu mengikuti kondisi database saat itu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type', 20);                     // kelas | siswa | guru | jadwal
            $table->string('original_name');
            $table->string('file_path');
            // validated | queued | running | completed | stopped | failed
            $table->string('status', 20)->default('validated');

            $table->unsignedInteger('total_rows')->default(0);       // baris data tidak kosong
            $table->unsignedInteger('valid_rows')->default(0);       // lolos validasi (dry-run)
            $table->unsignedInteger('error_rows')->default(0);       // bermasalah (tidak diimpor)
            $table->unsignedInteger('processed_rows')->default(0);   // penunjuk posisi saat impor
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->jsonb('errors')->nullable();     // galat dry-run: [{line, column, message}] (dibatasi)
            $table->jsonb('failures')->nullable();   // galat saat impor: [{line, message}] (dibatasi)
            $table->jsonb('notes')->nullable();      // info/peringatan: ["Jurusan baru akan dibuat: ..."]
            $table->text('message')->nullable();

            $table->string('credentials_path')->nullable();   // laporan akun (kata sandi awal): kedaluwarsa 24 jam
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
