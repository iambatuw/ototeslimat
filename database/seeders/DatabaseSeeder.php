<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Package;
use App\Models\ProductKey;
use App\Models\User;
use App\Support\KeyCode;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Yalnızca yerel deneme verisi. Canlı ortamda çalışmaz.
 * Giriş: admin@demo.test (yönetici), musteri@demo.test (müşteri). Şifre: password
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Deneme verisi canlı ortamda yüklenmez. İlk yönetici için: php artisan guard:admin');
        }

        $admin = User::create(['name' => 'Mağaza Sahibi', 'email' => 'admin@demo.test', 'password' => 'password']);
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $customer = User::create(['name' => 'Deneme Müşteri', 'email' => 'musteri@demo.test', 'password' => 'password']);

        // Posta kutuları sahte: "Bağlantıyı dene" hata verir, bu beklenen.
        $fakeMail = ['mail_host' => 'imap.example.test', 'mail_port' => 993, 'mail_encryption' => 'ssl', 'mail_password' => 'gecersiz'];

        $netflix = Account::create(['platform' => 'netflix', 'title' => 'Netflix Premium 1', 'mail_username' => 'netflix1@example.test'] + $fakeMail);
        // Rastgele bir 2FA anahtarı: kod üretir ama gerçek bir hesaba bağlı değildir.
        $chatgpt = Account::create(['platform' => 'chatgpt', 'title' => 'ChatGPT Plus', 'totp_secret' => $this->randomBase32()]);
        $prime = Account::create(['platform' => 'prime', 'title' => 'Prime Video Aile', 'mail_username' => 'prime@example.test', 'totp_secret' => $this->randomBase32()] + $fakeMail);
        Account::create(['platform' => 'hbomax', 'title' => 'HBO Max Standart', 'mail_username' => 'hbo@example.test'] + $fakeMail);

        $rules = [
            [$netflix, 8, ['valid_days' => 30]],
            [$chatgpt, 6, ['max_uses' => 5, 'cooldown_minutes' => 0]],
            [$prime, 4, []],
        ];

        foreach ($rules as [$account, $count, $rule]) {
            for ($i = 0; $i < $count; $i++) {
                $body = KeyCode::generate();
                $key = ProductKey::create([
                    'account_id' => $account->id,
                    'code' => KeyCode::format($body),
                    'code_hash' => KeyCode::hash($body),
                    'label' => 'Sipariş #'.(2200 + $i),
                ] + $rule);

                if ($i === 0) {
                    $key->forceFill([
                        'redeemed_by' => $customer->id,
                        'redeemed_at' => now()->subDays(2),
                        'expires_at' => $key->valid_days ? now()->subDays(2)->addDays($key->valid_days) : null,
                    ])->save();
                }
            }
        }

        // Paket: müşteri 4 hesaptan 2'sini seçer
        $hbo = Account::where('platform', 'hbomax')->first();
        $package = Package::create(['name' => 'İkili yayın paketi', 'description' => 'Dört platformdan istediğin ikisini seç.', 'pick_count' => 2]);
        $package->accounts()->sync([$netflix->id, $chatgpt->id, $prime->id, $hbo->id]);

        for ($i = 0; $i < 5; $i++) {
            $body = KeyCode::generate();
            $key = ProductKey::create(['package_id' => $package->id, 'code' => KeyCode::format($body), 'code_hash' => KeyCode::hash($body), 'label' => 'Paket sipariş #'.(3100 + $i)]);

            if ($i === 0) {
                $key->forceFill(['redeemed_by' => $customer->id, 'redeemed_at' => now()->subDay()])->save();
                $key->selections()->attach($chatgpt->id);
            }
        }

        // Panel grafiği boş görünmesin diye geçmiş kod kayıtları
        foreach (range(13, 1) as $daysAgo) {
            foreach (range(1, random_int(0, 9)) as $_) {
                ActivityLog::create([
                    'event' => 'code.issued', 'user_id' => $customer->id, 'account_id' => $chatgpt->id, 'ip' => '127.0.0.1',
                    'meta' => ['type' => '2fa', 'code' => (string) random_int(100000, 999999)],
                    'created_at' => now()->subDays($daysAgo)->setTime(random_int(9, 23), random_int(0, 59)),
                ]);
            }
        }
    }

    private function randomBase32(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

        return implode('', array_map(fn () => $alphabet[random_int(0, 31)], range(1, 32)));
    }
}
