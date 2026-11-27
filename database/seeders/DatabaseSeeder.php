<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Yalnızca yerel geliştirme için: yönetici hesabı ve altı ürün almış bir deneme müşterisi.
 * Yönetici: admin@demo.test / password, müşteri: musteri@demo.test / password
 * Canlı ortamda çalışmaz; orada ilk yönetici için: php artisan guard:admin
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Canlı ortamda seed çalışmaz. İlk yönetici için: php artisan guard:admin');
        }

        $admin = User::create(['name' => 'Mağaza Sahibi', 'email' => 'admin@demo.test', 'password' => 'password']);
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->call(DemoSeeder::class);
    }
}
