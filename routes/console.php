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

/*
 * Günlük özet: dünün rakamları yöneticilere e-postayla gider. Ayarlar > Özellikler'den açılır.
 * Sunucuda dakikada bir "php artisan schedule:run" çalıştıran bir cron satırı gerekir.
 */
Artisan::command('guard:daily-summary {--force : Ayar kapalı olsa da gönder}', function () {
    if (! $this->option('force') && ! App\Models\Setting::flag('daily_summary')) {
        $this->line('Günlük özet kapalı.');

        return 0;
    }

    $admins = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->pluck('email');

    if ($admins->isEmpty()) {
        $this->warn('Gönderilecek yönetici yok.');

        return 0;
    }

    Illuminate\Support\Facades\Mail::to($admins->all())->send(new App\Mail\DailySummary(
        App\Support\StoreInsights::day(now()->subDay()),
        App\Support\StoreInsights::troubledAccounts(),
        App\Support\StoreInsights::lowStock(),
    ));

    $this->info($admins->count().' yöneticiye gönderildi.');

    return 0;
})->purpose('Dünün özetini yöneticilere e-postayla gönderir');

Illuminate\Support\Facades\Schedule::command('guard:daily-summary')->dailyAt('09:00');

/*
 * Lisans (aylık üyelik). Yalnızca satıcının kendi bilgisayarında çalıştırılır: özel anahtar ile
 * mağaza sitelerine lisans anahtarı üretir. Özel anahtar asla depoya ya da mağaza sunucusuna konmaz.
 */
$licenseKeyPath = fn (?string $path) => $path ?: (getenv('USERPROFILE') ?: getenv('HOME')).DIRECTORY_SEPARATOR.'.gecit-lisans'.DIRECTORY_SEPARATOR.'ozel-anahtar.txt';

Artisan::command('guard:license-keygen {--path=}', function () use ($licenseKeyPath) {
    $path = $licenseKeyPath($this->option('path'));
    if (is_file($path)) {
        $this->error("Özel anahtar zaten var: {$path}. Üzerine yazılmadı; yeni anahtar eski lisansları geçersiz kılar.");

        return 1;
    }

    $pair = sodium_crypto_sign_keypair();
    @mkdir(dirname($path), 0700, true);
    file_put_contents($path, base64_encode(sodium_crypto_sign_secretkey($pair)));

    $this->info("Özel anahtar kaydedildi: {$path}");
    $this->line('Bu dosyayı yedekle ve kimseyle paylaşma. Kaybedersen yeni lisans üretemezsin.');
    $this->newLine();
    $this->line('config/guard.php > license.public_key değerine şunu yaz:');
    $this->line(base64_encode(sodium_crypto_sign_publickey($pair)));
})->purpose('Lisans imzalamak için özel/açık anahtar çifti üretir (satıcı bilgisayarında, bir kez)');

Artisan::command('guard:license {domain} {--days=30} {--until=} {--note=} {--key=}', function (string $domain) use ($licenseKeyPath) {
    $path = $licenseKeyPath($this->option('key'));
    if (! is_file($path)) {
        $this->error("Özel anahtar bulunamadı: {$path}. Önce guard:license-keygen çalıştır.");

        return 1;
    }

    $until = $this->option('until')
        ? Illuminate\Support\Carbon::parse($this->option('until'))->endOfDay()
        : now()->addDays((int) $this->option('days'))->endOfDay();

    $token = App\Support\License::issue($domain, $until, trim(file_get_contents($path)), $this->option('note'));

    $this->info(App\Support\License::host($domain).' için lisans, bitiş: '.$until->format('d.m.Y H:i'));
    $this->newLine();
    $this->line($token);
    $this->newLine();
    $this->line('Bu anahtarı mağaza sahibine gönder: Yönetim > Ayarlar > Lisans sayfasına yapıştırır.');
})->purpose('Bir mağaza sitesi için lisans anahtarı üretir (örn. guard:license ornek.com --days=30)');
