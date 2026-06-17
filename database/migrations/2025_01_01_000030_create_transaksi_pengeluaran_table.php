<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_pengeluaran', function (Blueprint $table) {
            $table->increments('id_pengeluaran');
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('no_transaksi', 50);
            $table->date('tanggal_transaksi');
            $table->unsignedInteger('id_kategori');
            $table->foreign('id_kategori')->references('id_kategori_pengeluaran')->on('kategori_pengeluaran')->cascadeOnDelete();
            $table->text('keterangan');
            $table->string('tujuan')->nullable();
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->string('metode_pembayaran')->default('Tunai'); // Tunai/Transfer/Cek/Giro
            $table->string('no_bukti')->nullable();
            $table->string('file_bukti')->nullable();
            $table->string('tahun_ajaran')->nullable();
            $table->string('semester')->nullable(); // Ganjil/Genap
            $table->boolean('is_from_bos')->default(false);
            $table->string('status')->default('Paid'); // Pending/Paid/Cancelled
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_pengeluaran');
    }
};
