<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_pemasukan', function (Blueprint $table) {
            $table->increments('id_kategori_pemasukan');
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('nama_kategori', 100);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_pemasukan');
    }
};
