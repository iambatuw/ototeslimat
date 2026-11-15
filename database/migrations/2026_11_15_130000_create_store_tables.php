<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 16)->default('customer')->index()->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->text('value')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 24)->index();
            $table->string('title');

            // Kod gelen posta kutusu (şifre şifreli saklanır)
            $table->string('mail_host')->nullable();
            $table->unsignedSmallInteger('mail_port')->nullable();
            $table->string('mail_encryption', 8)->nullable();
            $table->string('mail_username')->nullable();
            $table->text('mail_password')->nullable();
            $table->string('mail_from')->nullable();
            $table->string('code_format', 24)->nullable();

            // Authenticator (2FA) gizli anahtarı (şifreli saklanır)
            $table->text('totp_secret')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_code_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->char('code_hash', 64)->unique();
            $table->text('code');
            $table->string('label')->nullable();

            // Kurallar: boş = sınırsız / süresiz
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('cooldown_minutes')->default(0);
            $table->unsignedInteger('valid_days')->nullable();

            $table->foreignId('redeemed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->char('last_code_hash', 64)->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->timestamps();

            $table->index(['account_id', 'created_at']);
            $table->index('redeemed_by');
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_key_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 40);
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('product_keys');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('settings');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'is_active', 'last_login_at', 'last_login_ip']);
        });
    }
};
