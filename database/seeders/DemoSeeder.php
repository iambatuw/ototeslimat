<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Game;
use App\Models\ProductKey;
use App\Models\User;
use App\Support\KeyCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Yalnızca yerel deneme: altı ürün almış bir müşteri.
 * Giriş: musteri@demo.test, şifre: password
 *
 * Steam ve ChatGPT hesaplarında rastgele üretilmiş 2FA anahtarı vardır, kod gerçekten üretilir.
 * Netflix, Disney+ ve Spotify'ın posta kutusu sahtedir; mail kodu istenince "bağlanılamadı" der.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Deneme verisi canlı ortamda oluşturulmaz.');
        }

        $customer = User::firstOrCreate(['email' => 'musteri@demo.test'], ['name' => 'Deneme Müşteri', 'password' => 'password']);

        if ($customer->keys()->exists()) {
            return;
        }

        $base32 = fn () => collect(range(1, 32))->map(fn () => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'[random_int(0, 31)])->implode('');
        $mailbox = ['mail_host' => 'imap.example.test', 'mail_port' => 993, 'mail_encryption' => 'ssl', 'mail_username' => 'demo@example.test', 'mail_password' => 'demo'];

        // Steam hesapları oyuna bağlı: Kodlarım'da kapakları görünür (Steam'in genel kapak adresi).
        $game = fn (string $name, int $app) => Game::firstOrCreate(['name' => $name, 'platform' => 'steam'], [
            'cover' => "https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/{$app}/library_600x900.jpg",
        ])->id;

        // [platform, hesap adı, hesap alanları, anahtar kuralları, müşterinin verdiği ad]
        $items = [
            ['steam', 'Steam · CS2 Prime', ['totp_secret' => 'steam:'.base64_encode(random_bytes(20)), 'game_id' => $game('Counter-Strike 2', 730)], ['valid_days' => 30, 'period_limit' => 1, 'period_hours' => 24], 'Ana hesap'],
            ['steam', 'Steam · GTA V', ['totp_secret' => 'steam:'.base64_encode(random_bytes(20)), 'game_id' => $game('Grand Theft Auto V', 271590)], ['valid_days' => 30], 'Kardeşimin'],
            ['netflix', 'Netflix Premium 4K', $mailbox, ['valid_days' => 30, 'max_uses' => 20], null],
            ['disney', 'Disney+ Standart', $mailbox, ['valid_days' => 30], null],
            ['chatgpt', 'ChatGPT Plus', ['totp_secret' => $base32()] + $mailbox, ['max_uses' => 15], null],
            ['spotify', 'Spotify Premium Aile', $mailbox, ['valid_days' => 90], null],
        ];

        foreach ($items as [$platform, $title, $fields, $rules, $label]) {
            $account = Account::create(['platform' => $platform, 'title' => $title, 'code_format' => config("guard.platforms.$platform.format")] + $fields);
            $body = KeyCode::generate();
            $key = ProductKey::create(['account_id' => $account->id, 'code' => KeyCode::format($body), 'code_hash' => KeyCode::hash($body)] + $rules);
            $key->forceFill([
                'redeemed_by' => $customer->id,
                'redeemed_at' => now(),
                'expires_at' => isset($rules['valid_days']) ? now()->addDays($rules['valid_days']) : null,
            ])->save();

            if ($label) {
                DB::table('account_labels')->insert(['user_id' => $customer->id, 'account_id' => $account->id, 'label' => $label, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}
