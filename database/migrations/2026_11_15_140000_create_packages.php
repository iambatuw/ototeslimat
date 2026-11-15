<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paket: tek anahtarla müşterinin listeden X hesap seçtiği ürün.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description', 300)->nullable();
            $table->unsignedSmallInteger('pick_count')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('account_package', function (Blueprint $table) {
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->primary(['package_id', 'account_id']);
        });

        Schema::table('product_keys', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->change();
            $table->foreignId('package_id')->nullable()->after('account_id')->constrained()->cascadeOnDelete();
        });

        Schema::create('key_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_key_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['product_key_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('key_selections');

        Schema::table('product_keys', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_id');
        });

        Schema::dropIfExists('account_package');
        Schema::dropIfExists('packages');
    }
};
