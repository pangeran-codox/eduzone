<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dana_bos', function (Blueprint $table) {
            $table->increments('id_bos');
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('tahun_ajaran', 20);
            $table->string('semester'); // Ganjil/Genap
            $table->string('triwulan')->nullable(); // 1/2/3/4
            $table->decimal('jumlah_diterima', 15, 2)->default(0);
            $table->date('tanggal_terima');
            $table->text('keterangan')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dana_bos');
    }
};
