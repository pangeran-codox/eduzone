<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('realisasi_bos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedInteger('id_bos');
            $table->foreign('id_bos')->references('id_bos')->on('dana_bos')->cascadeOnDelete();
            $table->unsignedInteger('id_pengeluaran');
            $table->foreign('id_pengeluaran')->references('id_pengeluaran')->on('transaksi_pengeluaran')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realisasi_bos');
    }
};
