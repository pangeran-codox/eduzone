<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_anggaran', function (Blueprint $table) {
            $table->increments('id_pengajuan');
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('judul', 150);
            $table->unsignedInteger('kategori_pengeluaran')->nullable();
            $table->foreign('kategori_pengeluaran')->references('id_kategori_pengeluaran')->on('kategori_pengeluaran')->nullOnDelete();
            $table->decimal('jumlah_diajukan', 15, 2);
            $table->date('tanggal_pengajuan');
            $table->text('keperluan')->nullable();
            $table->string('status')->default('Pending'); // Pending/Approved/Rejected
            $table->text('catatan_reviewer')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_anggaran');
    }
};
