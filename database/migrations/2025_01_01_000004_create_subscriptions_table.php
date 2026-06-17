<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('plan'); // trial/basic/pro
            $table->date('started_at');
            $table->date('expired_at');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('invoice_no')->nullable();
            $table->string('status')->default('active'); // active/expired/cancelled
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
