<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Cache read-only dari `students`/`teachers`/`staff` di DB utama.
    // Disinkron satu arah, dipakai buat validasi cepat tanpa cross-database query.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::create('people_ref', function (Blueprint $table) {
            $table->uuid('person_id'); // id asli dari students/teachers/staff di DB utama
            $table->uuid('school_id');
            $table->string('person_type', 20);
            $table->string('full_name');
            $table->uuid('class_id')->nullable();
            $table->string('grade', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('synced_at', 0)->useCurrent();

            $table->primary(['person_id', 'person_type']);
            $table->foreign('school_id')->references('school_id')->on('schools_ref')->cascadeOnDelete();
            $table->index('school_id', 'idx_people_ref_school');
        });

        DB::statement("ALTER TABLE people_ref ADD CONSTRAINT people_ref_person_type_check CHECK (person_type IN ('student','teacher','staff'))");
        DB::statement('CREATE INDEX idx_people_ref_class ON people_ref (class_id) WHERE (class_id IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('people_ref');
    }
};
