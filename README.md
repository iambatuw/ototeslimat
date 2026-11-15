# Ototeslimat

Hesap satıcıları için otomatik kod teslim sistemi. Netflix, ChatGPT, Prime Video, HBO Max, Disney+ gibi hesaplar satıyorsan, müşterin her girişte doğrulama kodu ister. Bu sistem kodu senin yerine teslim eder:

1. Sattığın hesabın e-posta kutusunu (IMAP) ve varsa Authenticator (2FA) anahtarını panele eklersin.
2. O hesap için ürün anahtarları üretip müşterilerine verirsin.
3. Müşteri siteye kayıt olur, anahtarı ekler, kodu kendisi alır. Mail kutusuna ya da 2FA anahtarına hiçbir şekilde erişemez.

Her kurulum tek mağazadır. Kuran kişi yöneticidir.

## Özellikler

- **Mail kodu:** Hesabın posta kutusundan son gelen giriş kodu IMAP ile okunur. Netflix'in hane doğrulama bağlantısı da okunabilir.
- **Authenticator (2FA):** 6 haneli kod sunucuda üretilir, 30 saniyelik geri sayımla gösterilir.
- **Anahtar kuralları:** kod hakkı, süre (müşteri anahtarı eklediği an başlar), kodlar arası bekleme. Boş bırakılırsa sınırsız ve süresiz olur. Aynı kodu tekrar görmek hak düşürmez.
- **Yönetim:** panel grafiği, sol listeden hesap seçip sağda anahtar üretme, toplu hesap ekleme, CSV dışa aktarma, eski anahtarları tek tıkla temizleme, müşteri engelleme.
- **Kayıtlar:** Hangi müşteri, hangi anahtarla, ne zaman, hangi IP'den, hangi kodu aldı; başarısız denemeler nedeniyle birlikte.
- **Mağaza ayarları:** marka adı, sekme başlığı, karşılama yazısı, duyuru, destek bağlantısı, tema rengi, logo, sekme simgesi, yeni kaydı açıp kapama.

## Gereksinimler

- PHP 8.3 veya üstü (`openssl`, `pdo_mysql` ya da `pdo_sqlite`, `mbstring`, `fileinfo`, `iconv`)
- Composer 2
- MySQL 8 / MariaDB 10.6 veya üstü (yerelde SQLite yeterli)
- Node.js 20 veya üstü (yalnızca arayüzü derlemek için, sunucuda gerekmez)

## Kurulum

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

`.env` içinde en az şunları doldur:

```
APP_NAME="Mağaza adın"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://alanadin.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

MAIL_MAILER=smtp        # şifre yenileme mailleri için
```

Sonra:

```bash
php artisan migrate --force
php artisan guard:admin senin@adresin.com
npm ci && npm run build      # ya da hazır public/build klasörünü yükle
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Web sunucusunun kök dizini `public/` olmalı. Proje kökünü dışarı açma. Logo ve sekme simgesi `public/uploads` klasörüne yazılır; bu klasör yazılabilir olmalı.

## Güvenlik

- **`APP_KEY` en önemli sırdır.** E-posta şifreleri, 2FA anahtarları ve ürün anahtarları bu anahtarla şifrelenir. Kaybedersen kayıtlı hesapların hiçbiri çalışmaz; yedeğini al. Değiştirmen gerekirse eskisini `APP_PREVIOUS_KEYS` içine yaz.
- Ürün anahtarları veritabanında şifreli durur ve özetle aranır. Veritabanı sızsa bile anahtarlar okunamaz.
- Anahtar denemesi kullanıcı ve IP başına sınırlıdır (varsayılan dakikada 8). Hatalı denemeler kaydedilir.
- Kod hakkı ve bekleme süresi veritabanı kilidiyle uygulanır; aynı anda gelen iki istek hakkı iki kez düşüremez.
- Sayfalar sıkı bir Content-Security-Policy ile gelir. Dış CDN kullanılmaz, fontlar sunucudan yüklenir. Logo için SVG kabul edilmez.
- Kod biçimleri hazır listeden seçilir, serbest regex yazılamaz. Böylece yavaş bir desen sunucuyu kilitleyemez.
- Cloudflare ya da bir proxy kullanıyorsan gerçek IP'ler için `.env` içine `TRUSTED_PROXIES=*` ekle.

## Güncelleme

```bash
composer update --no-dev     # Laravel güvenlik yamaları dahil
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
```

Bağımlılıklarda bilinen açık olup olmadığını görmek için: `composer audit`

## Posta kutusu notları

- **Gmail:** Normal şifre çalışmaz. Hesapta 2 adımlı doğrulamayı açıp **uygulama şifresi** oluştur.
- **Outlook / Hotmail:** IMAP'ta şifreyle girişi kapattığı için desteklenmez.
- **Platform şablonları değişebilir.** Gönderen adresleri ve varsayılan kod biçimleri `config/guard.php` içinde. Kod yanlış okunursa hesabın **Kod biçimi** ayarını değiştir. Netflix giriş kodu 4 haneli; hane doğrulaması için "Netflix hane bağlantısı" biçimini seç.
- **Önbellek:** Aynı hesaba arka arkaya gelen istekler posta kutusunu yormasın diye bulunan kod 20 saniye önbellekte tutulur (`GUARD_EMAIL_CACHE`).

## Yerel geliştirme

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed    # deneme verisi; giriş bilgileri database/seeders/DatabaseSeeder.php içinde
npm run build && php artisan serve
php artisan test
```
