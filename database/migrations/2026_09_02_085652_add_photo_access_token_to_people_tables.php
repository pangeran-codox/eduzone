<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['students', 'teachers', 'staff'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('photo_access_token', 64)->nullable()->unique()->after('photo');
            });
        }
    }

    public function down(): void
    {
        foreach (['students', 'teachers', 'staff'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('photo_access_token');
            });
        }
    }
};