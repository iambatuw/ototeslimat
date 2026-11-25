<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Müşterinin bir hesaba kendi verdiği ad ("Ana hesap", "Kardeşimin").
 * Aynı platformdan birkaç hesabı olan müşteri kartları birbirinden ayırsın diye.
 * Yalnızca o müşteri görür; mağaza sahibinin hesap adını değiştirmez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('label', 40);
            $table->timestamps();

            $table->unique(['user_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_labels');
    }
};
