<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Metode absensi tiap orang (hash UID RFID / token QR / dll).
    // Satu orang bisa punya beberapa metode sekaligus.
    protected $connection = 'pgsql_absensi';

    public function up(): void
    {
        Schema::create('credentials', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('school_id');
            $table->uuid('person_id');
            $table->string('person_type', 20);
            $table->string('method', 20);
            $table->string('credential_hash', 128)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('enrolled_at', 0)->useCurrent();
            $table->timestamp('revoked_at', 0)->nullable();

            $table->foreign('school_id')->references('school_id')->on('schools_ref')->cascadeOnDelete();
            $table->foreign(['person_id', 'person_type'])->references(['person_id', 'person_type'])->on('people_ref')->cascadeOnDelete();
            $table->unique(['school_id', 'method', 'credential_hash'], 'credentials_school_id_method_credential_hash_key');
            $table->index(['person_id', 'person_type'], 'idx_credentials_person');
        });

        DB::statement("ALTER TABLE credentials ADD CONSTRAINT credentials_person_type_check CHECK (person_type IN ('student','teacher','staff'))");
        DB::statement("ALTER TABLE credentials ADD CONSTRAINT credentials_method_check CHECK (method IN ('rfid','qr','face','fingerprint','manual'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('credentials');
    }
};
