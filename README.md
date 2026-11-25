# Kodvar

Hesap satıcıları için otomatik kod teslim sistemi. Netflix, ChatGPT, Prime Video, HBO Max, Disney+ gibi abonelikler ya da Steam, Epic Games, EA, Ubisoft, Rockstar, Riot gibi oyun hesapları satıyorsan, müşterin her girişte doğrulama kodu ister. Bu sistem kodu senin yerine teslim eder:

1. Sattığın hesabın e-posta kutusunu (IMAP) ve varsa Authenticator (2FA) anahtarını panele eklersin.
2. O hesap için ürün anahtarları üretip müşterilerine verirsin.
3. Müşteri siteye kayıt olup anahtarı ekler ya da hesap açmadan `/onay` sayfasında anahtarı yazar, kodu kendisi alır. Mail kutusuna ya da 2FA anahtarına hiçbir şekilde erişemez.

Her kurulum tek mağazadır. Kuran kişi yöneticidir.

## Özellikler

- **Mail kodu:** Hesabın posta kutusundan (Spam/Gereksiz klasörü dahil) son gelen giriş kodu IMAP ile okunur. Kod, platformun maildeki kalıbına göre ("login code", "doğrulama kodu" ...) aranır; alt bilgideki posta kodu, yıl, telefon gibi sayılar kod sanılmaz. Netflix'in hane doğrulama bağlantısı da okunabilir.
- **Hesap ele geçirmeye karşı koruma:** E-posta değiştirme, şifre sıfırlama, Steam Guard kaldırma gibi maillerdeki kodlar müşteriye hiç gösterilmez; kayıtlara "kod gizlendi" diye düşer.
- **Kod gelene kadar bekleme:** Mail henüz gelmediyse müşteri ekranı yaklaşık 2 dakika boyunca kendiliğinden tekrar sorar; kod düşünce hücrelere yazılır.
- **Authenticator (2FA):** 6 haneli kod sunucuda üretilir, 30 saniyelik geri sayımla gösterilir. Epic, EA, Ubisoft, ChatGPT gibi standart 2FA anahtarlarıyla çalışır.
- **Steam Guard mobil:** `.maFile` içeriği ya da `shared_secret` yapıştırılır; müşteri 5 karakterlik Steam Guard kodunu görür.
- **Hızlı onay (`/onay`):** Müşteri hesap açmadan yalnızca anahtarıyla kodunu alır. Ayarlar > Özellikler'den kapatılabilir. Hızlı onayda kullanılan anahtar bir hesaba eklenemez (ve tersi); paket anahtarları burada çalışmaz.
- **Anahtar kuralları:** kod hakkı, süre (müşteri anahtarı eklediği an başlar), kodlar arası bekleme. Boş bırakılırsa sınırsız ve süresiz olur. Aynı kodu tekrar görmek hak düşürmez.
- **Paketler:** Tek anahtarla müşteri listeden belirlediğin sayıda hesap seçer, örneğin "4 platformdan 2'sini seç". Seçim kalıcıdır. Kod hakkı, süre ve bekleme anahtarın tamamına uygulanır.
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
- **Önbellek:** Aynı hesaba arka arkaya gelen istekler posta kutusunu yormasın diye bulunan kod 20 saniye (`GUARD_EMAIL_CACHE`), "kod yok" sonucu 5 saniye (`GUARD_EMAIL_MISS_CACHE`) önbellekte tutulur.
- **Bekleme:** Müşteri ekranı `GUARD_POLL_INTERVAL` saniyede bir, en fazla `GUARD_POLL_ATTEMPTS` kez yeniden sorar (varsayılan 7 sn × 17).
- **Spam klasörü:** Varsayılan olarak taranır; kapatmak için `GUARD_SCAN_SPAM=false`.
- **Desteklenen platformlar ve kod biçimleri:** Netflix (4 rakam), ChatGPT, Prime Video, HBO Max, Disney+, Spotify, YouTube, Canva, CapCut, Epic Games, EA, Ubisoft, Rockstar, Riot (6 rakam), Steam (5 harf/rakam, Steam Guard alfabesi). Kaynak: platformların yardım sayfaları ve kod maillerinin bilinen kalıpları (2026-11). Hiçbiri gerçek bir hesapla denenmedi; yeni bir platform hesabı eklediğinde **Bağlantıyı dene** ile okunan kodu ve mail konusunu kontrol et.

## Yerel geliştirme

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed    # deneme verisi; giriş bilgileri database/seeders/DatabaseSeeder.php içinde
npm run build && php artisan guard:serve
php artisan test
```

## Günlük özet e-postası

Ayarlar > Özellikler'den açılır. Her sabah 09:00'da yöneticilere dünün özeti gider (verilen kod, hatalar, kod veremeyen hesaplar, azalan stok). Çalışması için sunucuda iki şey gerekir:

1. `.env` içinde gerçek bir e-posta ayarı (`MAIL_MAILER=smtp` ve ilgili `MAIL_*` satırları). Varsayılan `log` ayarında e-posta gönderilmez, yalnızca `storage/logs` içine yazılır.
2. Zamanlayıcıyı dakikada bir çalıştıran cron satırı:

```
* * * * * cd /site/yolu && php artisan schedule:run >> /dev/null 2>&1
```

Elle denemek için: `php artisan guard:daily-summary --force`
