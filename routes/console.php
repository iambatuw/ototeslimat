<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

Artisan::command('guard:admin {email} {--name=Yönetici}', function (string $email) {
    $password = $this->secret('Şifre (en az 10 karakter, harf ve rakam)');

    $validator = Validator::make(
        ['email' => $email, 'password' => $password],
        ['email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', Password::min(10)->letters()->numbers()]],
    );

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    $user = User::create(['name' => $this->option('name'), 'email' => strtolower($email), 'password' => $password]);
    $user->forceFill(['role' => User::ROLE_ADMIN])->save();

    $this->info("Yönetici oluşturuldu: {$user->email}");
})->purpose('İlk yönetici hesabını oluşturur');

/*
 * Yerel geliştirme sunucusu, OPcache açık. "artisan serve" her istekte bütün dosyaları yeniden derler;
 * bu komut derlenmiş hâllerini bellekte tutar ve sayfalar birkaç kat hızlı açılır.
 * Canlıda kullanılmaz: orada nginx/Apache + PHP-FPM ve php.ini içinde OPcache gerekir.
 */
Artisan::command('guard:serve {--host=127.0.0.1} {--port=8000}', function () {
    $flags = [
        '-d', 'opcache.enable=1',
        '-d', 'opcache.enable_cli=1',
        '-d', 'opcache.revalidate_freq=0',          // dosya değişince hemen görülür
        '-d', 'opcache.memory_consumption=192',
        '-d', 'opcache.max_accelerated_files=20000',
    ];

    // Eklenti php.ini içinde zaten yüklüyse ikinci kez yüklemek uyarı verir.
    if (! extension_loaded('Zend OPcache')) {
        array_unshift($flags, '-d', 'zend_extension=opcache');
    }

    $address = $this->option('host').':'.$this->option('port');
    $router = base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php');

    $this->info("Sunucu http://{$address} adresinde. Durdurmak için Ctrl+C.");

    $process = new Symfony\Component\Process\Process([PHP_BINARY, ...$flags, '-S', $address, $router], public_path());
    $process->setTimeout(null);

    return $process->run(fn ($type, $line) => $this->output->write($line));
})->purpose('Yerel sunucuyu OPcache açık başlatır');
