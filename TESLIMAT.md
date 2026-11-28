# Müşteriye site teslimi (adım adım)

Bir müşteri siteyi satın aldığında, siteyi **müşterinin kendi alan adında, kendi sunucusunda** kurup teslim etmek için bu rehberi baştan sona uygula. Her şey müşteriye ait olur: alan adı, sunucu, veriler.

Toplam süre: ilk seferde yaklaşık 45 dakika, alıştıktan sonra 15-20 dakika.

Komutların neredeyse hepsi **sunucuda** (Linux) çalışır. Senin bilgisayarında yalnızca sunucuya bağlanırsın.

---

## 0. Satıştan sonra müşteriden iste

Kuruluma başlamadan bu bilgileri topla:

| Ne | Neden |
|---|---|
| Alan adı (örn. `magazaadi.com`) | Site bu adreste açılacak |
| Sunucu (VPS) bilgisi: IP adresi ve root şifresi ya da SSH anahtarı | Siteyi bu sunucuya kuracaksın |
| Yönetici e-postası | Panele bu e-postayla girecek |
| Marka adı, logo (PNG/JPG), istersen vurgu rengi | Siteyi onun markasıyla teslim etmek için |
| Destek bağlantısı (Telegram, WhatsApp, Discord…) | Müşterilerinin "Destek" düğmesi buraya gider |

Müşterinin sunucusu yoksa ona şunu söyle:

> Ubuntu 24.04 kurulu bir VPS alman gerekiyor (1 GB RAM yeterli; Hetzner, DigitalOcean, Contabo ya da bir Türk sağlayıcı olur). Aldıktan sonra IP adresini ve root şifresini bana gönder.

---

## 1. Alan adını sunucuya yönlendir

Müşteri alan adını satın aldığı yerin (GoDaddy, Natro, İsimtescil, Cloudflare…) **DNS ayarlarına** iki kayıt ekler. Müşteriye gönderebileceğin hazır mesaj:

> Alan adının DNS ayarlarına girip şu iki kaydı ekler misin (varsa eski A kayıtlarını sil):
>
> | Tür | Ad / Host | Değer |
> |---|---|---|
> | A | `@` | `SUNUCU_IP` |
> | A | `www` | `SUNUCU_IP` |
>
> Cloudflare kullanıyorsan kayıtların yanındaki turuncu bulutu kapat (gri olsun). Kurulum bitince açabiliriz.

`SUNUCU_IP` yerine sunucunun IP adresini yaz. Değişikliğin yayılması birkaç dakika ile birkaç saat sürer.

Yayıldı mı diye kendi bilgisayarında (PowerShell) bak:

```powershell
nslookup magazaadi.com
```

Çıkan adres sunucu IP'siyle aynıysa hazır. Bu beklenirken 2-5. adımları yapabilirsin; yalnızca 6. adım (SSL) için DNS'in hazır olması gerekir.

---

## 2. Sunucuya bağlan

Kendi bilgisayarında PowerShell aç:

```powershell
ssh root@SUNUCU_IP
```

İlk bağlantıda "Are you sure you want to continue connecting" sorusuna `yes` yaz, sonra root şifresini gir (yazarken ekranda görünmez, normal).

Bundan sonraki tüm komutlar bu sunucu penceresinde çalışır.

---

## 3. Gerekli yazılımları kur (sunucu başına bir kez)

```bash
apt update && apt upgrade -y

apt install -y nginx git unzip curl cron ufw \
  php8.3-fpm php8.3-cli php8.3-sqlite3 php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-intl php8.3-bcmath php8.3-opcache \
  composer certbot python3-certbot-nginx
```

Arayüz dosyalarını derlemek için Node.js 22:

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt install -y nodejs
```

Güvenlik duvarı (yalnızca SSH ve web açık kalsın):

```bash
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable
```

Kontrol: `php -v` (8.3 olmalı), `node -v` (22 olmalı), `composer -V`.

---

## 4. Siteyi indir ve kur

`magazaadi.com` yazan her yeri müşterinin alan adıyla değiştir.

```bash
cd /var/www
git clone https://github.com/iambatuw/ototeslimat.git magazaadi.com
cd /var/www/magazaadi.com

composer install --no-dev --optimize-autoloader
npm ci && npm run build && rm -rf node_modules

cp .env.example .env
php artisan key:generate
touch database/database.sqlite
```

### .env dosyasını düzenle

```bash
nano .env
```

Şu satırları bul ve böyle yap (diğerlerine dokunma):

```
APP_NAME="Müşterinin Marka Adı"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://magazaadi.com
LOG_LEVEL=warning
QUEUE_CONNECTION=sync
SESSION_SECURE_COOKIE=true
```

`SESSION_SECURE_COOKIE` satırı dosyada yoksa en alta ekle. Kaydet ve çık: `Ctrl+O`, `Enter`, `Ctrl+X`.

> **E-posta (isteğe bağlı):** Şifre sıfırlama ve günlük özet e-postaları için müşterinin bir SMTP hesabı varsa `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` satırlarını doldur. Yoksa boş bırak; site çalışır, yalnızca e-posta gitmez.

Veritabanını hazırla ve dosya izinlerini ayarla:

```bash
php artisan migrate --force

chown -R www-data:www-data storage bootstrap/cache database public/uploads
chmod -R 775 storage bootstrap/cache database public/uploads

php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## 5. nginx ayarı (siteyi alan adına bağla)

```bash
nano /etc/nginx/sites-available/magazaadi.com
```

İçine şunu yapıştır (`magazaadi.com` geçen 3 yeri değiştir):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name magazaadi.com www.magazaadi.com;
    root /var/www/magazaadi.com/public;
    index index.php;

    client_max_body_size 8M;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Yüklenen dosyalar klasöründe PHP asla çalışmasın
    location ~ ^/uploads/.*\.php$ { deny all; }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known) { deny all; }
}
```

Etkinleştir ve kontrol et:

```bash
ln -s /etc/nginx/sites-available/magazaadi.com /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
```

`nginx -t` çıktısında `syntax is ok` ve `test is successful` görmelisin.

---

## 6. SSL (https) kur

DNS yayıldıktan sonra (1. adımdaki `nslookup` doğru IP'yi gösteriyorsa):

```bash
certbot --nginx -d magazaadi.com -d www.magazaadi.com
```

- E-posta sorarsa müşterinin ya da kendi e-postanı yaz (sertifika bitimine yakın uyarı gelir).
- Şartları sorarsa `Y`, haber bülteni sorusuna `N`.
- Yönlendirme sorarsa "Redirect" seç: http her zaman https'e gider.

Sertifika kendiliğinden yenilenir; bir şey yapmana gerek yok.

---

## 7. Zamanlanmış görev (günlük özet için)

```bash
echo "* * * * * www-data cd /var/www/magazaadi.com && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/magazaadi-com
```

---

## 8. Müşterinin yönetici hesabını oluştur

```bash
cd /var/www/magazaadi.com
sudo -u www-data php artisan guard:admin musteri@eposta.com --name="Müşterinin Adı"
```

Şifre sorar: en az 10 karakter, harf ve rakam. Geçici bir şifre ver; müşteri ilk girişte sol alttaki adına tıklayıp **Hesabım** sayfasından değiştirsin.

---

## 9. Paneli müşterinin markasıyla hazırla

Tarayıcıda `https://magazaadi.com/login` adresinden müşterinin hesabıyla gir:

1. **Ayarlar > Genel:** marka adı, karşılama yazısı, destek bağlantısı.
2. **Ayarlar > Görünüm:** logo, sekme simgesi, vurgu rengi.
3. **Ayarlar > Özellikler:** müşteri oyun satmıyorsa Oyun kataloğunu kapat.
4. **Ayarlar > Bildirimler:** müşteri isterse Telegram bildirimlerini birlikte kurun.

İstersen ilk hesabı ve birkaç anahtarı da birlikte ekleyip müşteriye nasıl çalıştığını göster.

---

## 10. Teslim öncesi kontrol listesi

- [ ] `https://magazaadi.com` açılıyor, adres çubuğunda kilit simgesi var
- [ ] `http://` ve `www.` adresleri `https://magazaadi.com`'a gidiyor
- [ ] Yönetici girişi çalışıyor
- [ ] Logo ve marka adı doğru
- [ ] Bir deneme hesabı ve anahtarı eklendi, müşteri tarafından kod alındı (sonra silinebilir)
- [ ] Bir mail kodlu hesap eklendiyse mail kodu geldi (posta kutusu bağlantısı çalışıyor)
- [ ] `.env` içindeki `APP_KEY` satırı yedeklendi (aşağıya bak)

---

## 11. Müşteriye teslim mesajı

Hazır şablon:

> Siten hazır: https://magazaadi.com
>
> **Yönetim paneli:** https://magazaadi.com/login
> **E-posta:** musteri@eposta.com
> **Geçici şifre:** (ayrı mesajla gönder)
>
> İlk girişte sol alttaki adına tıklayıp **Hesabım** sayfasından şifreni değiştir.
>
> Başlamak için:
> 1. **Hesaplar > Hesap ekle:** sattığın hesabı ekle (mail kutusu ve/veya 2FA anahtarı).
> 2. **Anahtarlar:** o hesap için anahtar üret, müşterine ver.
> 3. Müşterin siteye kayıt olup anahtarı ekler ve kodunu kendisi alır.
>
> Önemli: Sunucudaki `.env` dosyasında yazan `APP_KEY` değerini güvenli bir yere kaydet. Bu anahtar kaybolursa kayıtlı hesap şifreleri açılamaz.

---

## 12. Yedekleme

Sitenin tüm verisi üç yerde durur:

- `database/database.sqlite` → hesaplar, anahtarlar, müşteriler
- `.env` → özellikle `APP_KEY` (şifreli bilgileri açan anahtar)
- `public/uploads/` → logo ve sekme simgesi

Elle yedek almak için sunucuda:

```bash
cd /var/www/magazaadi.com
tar czf /root/yedek-$(date +%F).tar.gz database/database.sqlite .env public/uploads
```

Her gece otomatik yedek almak istersen:

```bash
echo "30 3 * * * root cd /var/www/magazaadi.com && tar czf /root/yedek-\$(date +\%F).tar.gz database/database.sqlite .env public/uploads && find /root -name 'yedek-*.tar.gz' -mtime +14 -delete" > /etc/cron.d/magazaadi-com-yedek
```

Bu, son 14 günün yedeğini tutar.

---

## 13. Güncelleme (yeni sürüm çıktığında)

```bash
cd /var/www/magazaadi.com
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build && rm -rf node_modules
php artisan migrate --force
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache database public/uploads
```

Güncellemeden önce 12. adımdaki gibi bir yedek al.

---

## 14. Sık karşılaşılan sorunlar

| Belirti | Çözüm |
|---|---|
| Site açılmıyor, "Bu siteye ulaşılamıyor" | DNS henüz yayılmamış ya da yanlış. `nslookup magazaadi.com` ile IP'yi kontrol et. |
| 502 Bad Gateway | PHP çalışmıyor: `systemctl restart php8.3-fpm` |
| 500 Server Error | Ayrıntı için: `tail -n 50 /var/www/magazaadi.com/storage/logs/laravel.log`. Çoğu zaman dosya izni: 4. adımdaki `chown` / `chmod` komutlarını tekrar çalıştır. |
| Sayfa stilsiz, düz yazı gibi görünüyor | Arayüz derlenmemiş: `npm ci && npm run build` |
| `certbot` hata veriyor | DNS henüz bu sunucuyu göstermiyor ya da Cloudflare bulutu turuncu. Bekle ya da bulutu gri yap, sonra tekrar dene. |
| Mail kodu gelmiyor, "posta kutusuna bağlanılamadı" | Hesabın IMAP ayarları yanlış ya da sağlayıcı 993 portunu engelliyor. Gmail/Outlook için "uygulama şifresi" gerekir (README > Posta kutusu notları). |
| `.env` değiştirdim ama etki etmedi | `php artisan config:cache` çalıştır. |
| Giriş yapınca hemen çıkış yapıyor | `.env` içinde `APP_URL` https ile ve doğru alan adıyla yazılmalı; sonra `php artisan config:cache`. |

---

## Aynı sunucuya ikinci bir müşteri

Birden fazla müşteriyi aynı sunucuda barındırabilirsin: her müşteri için 3. adımı atla, 4-11. adımları yeni alan adıyla tekrarla. Her sitenin klasörü, veritabanı ve `APP_KEY`'i ayrıdır; veriler birbirine karışmaz.
