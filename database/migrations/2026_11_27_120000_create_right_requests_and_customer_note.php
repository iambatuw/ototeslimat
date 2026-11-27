<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Müşterinin satıcıdan ek kod hakkı istemesi ve hesaba müşteriye görünen kurulum notu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('right_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_key_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 20);                 // used_up, period, cooldown
            $table->string('status', 10)->default('pending'); // pending, approved, rejected
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->string('customer_note', 300)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('right_requests');
        Schema::table('accounts', fn (Blueprint $table) => $table->dropColumn('customer_note'));
    }
};
