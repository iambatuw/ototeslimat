<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dönemlik kod hakkı ("24 saatte 1 kod") ve satıcının elle verdiği ek hak.
 * Dönem, dönemin ilk kodu alındığı an başlar; süre dolunca sayaç sıfırlanır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_keys', function (Blueprint $table) {
            $table->unsignedInteger('period_limit')->nullable()->after('cooldown_minutes');
            $table->unsignedSmallInteger('period_hours')->default(24)->after('period_limit');
            $table->unsignedInteger('period_count')->default(0)->after('period_hours');
            $table->timestamp('period_started_at')->nullable()->after('period_count');
            $table->unsignedInteger('bonus_uses')->default(0)->after('period_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('product_keys', function (Blueprint $table) {
            $table->dropColumn(['period_limit', 'period_hours', 'period_count', 'period_started_at', 'bonus_uses']);
        });
    }
};
