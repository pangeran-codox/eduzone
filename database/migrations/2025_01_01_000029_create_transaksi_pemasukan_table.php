<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_pemasukan', function (Blueprint $table) {
            $table->increments('id_pemasukan');
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('no_transaksi', 50);
            $table->date('tanggal_transaksi');
            $table->unsignedInteger('id_kategori');
            $table->foreign('id_kategori')->references('id_kategori_pemasukan')->on('kategori_pemasukan')->cascadeOnDelete();
            $table->text('keterangan');
            $table->string('sumber')->nullable();
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->string('metode_pembayaran')->default('Tunai'); // Tunai/Transfer/Cek/Giro
            $table->string('no_bukti')->nullable();
            $table->string('file_bukti')->nullable();
            $table->string('tahun_ajaran')->default('2025/2026');
            $table->string('semester')->default('Ganjil'); // Ganjil/Genap
            $table->string('status')->default('Verified'); // Pending/Verified/Rejected
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_pemasukan');
    }
};
