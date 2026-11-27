<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Oyun kataloğu: satıcının sattığı oyunlar (kapak, platform, öne çıkan). Herkese açık
 * /oyunlar sayfasında listelenir; Ayarlar > Özellikler'den açılır, varsayılan kapalı.
 * Bir hesap bir oyuna bağlanabilir; müşterinin kartında oyunun kapağı görünür.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('platform', 30);
            $table->string('cover', 500)->nullable();    // uploads/... ya da https:// bağlantısı
            $table->string('note', 300)->nullable();     // kartın açıklaması
            $table->boolean('featured')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['is_visible', 'featured']);
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('game_id')->nullable()->after('platform')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('game_id');
        });
        Schema::dropIfExists('games');
    }
};
