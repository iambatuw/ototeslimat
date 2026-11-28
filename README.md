# Geçit

Hesap satıcıları için otomatik kod teslim sistemi. Netflix, ChatGPT, Prime Video, HBO Max, Disney+ gibi abonelikler ya da Steam, Epic Games, EA, Ubisoft, Rockstar, Riot, Battle.net, Xbox, PlayStation, Nintendo gibi oyun hesapları satıyorsan, müşterin her girişte doğrulama kodu ister. Bu sistem kodu senin yerine teslim eder:

1. Sattığın hesabın e-posta kutusunu (IMAP) ve varsa Authenticator (2FA) anahtarını panele eklersin.
2. O hesap için ürün anahtarları üretip müşterilerine verirsin.
3. Müşteri siteye kayıt olup anahtarı ekler ya da hesap açmadan `/onay` sayfasında anahtarı yazar, kodu kendisi alır. Mail kutusuna ya da 2FA anahtarına hiçbir şekilde erişemez.

Her kurulum tek mağazadır. Kuran kişi yöneticidir. Her mağaza sitesi **aylık lisansla** çalışır (aşağıda: Lisans).

### Ürün tanımı

**Geçit**, dijital hesap satan mağazalar için kendi sunucuna kurduğun bir **otomatik doğrulama kodu teslim paneli**dir. Müşterin Netflix'e, Steam'e ya da ChatGPT'ye girerken kod istendiğinde sana yazıp beklemez; Geçit'e girer, tek düğmeyle kodunu alır. Sen uyurken de çalışır.

- **Satıcı için:** hesapları ve posta kutularını bir kez eklersin, anahtar üretip satarsın. Her anahtara kural koyarsın: toplam kod hakkı, geçerlilik süresi, **24 saatte 1 kod** gibi dönemlik hak, kodlar arası bekleme. Hakkı dolan müşteriye tek tıkla **ek hak** verirsin. Kim, ne zaman, hangi kodu aldı; panelde kaç kişinin kod aldığı görünür.
- **Müşteri için:** aldığı bütün hesaplar tek sayfada. En son kullandığı hesap üstte hazır bekler; aynı platformdan birkaç hesabı varsa (örneğin 3 Steam) her birine kendi adını verir.
- **Güvenlik:** müşteri şifreyi, posta kutusunu ya da 2FA anahtarını hiç görmez. E-posta/şifre değiştirme maillerindeki kodlar gösterilmez.

## Özellikler

- **Mail kodu:** Hesabın posta kutusundan (Spam/Gereksiz klasörü dahil) son gelen giriş kodu IMAP ile okunur. Kod, platformun maildeki kalıbına göre ("login code", "doğrulama kodu" ...) aranır; alt bilgideki posta kodu, yıl, telefon gibi sayılar kod sanılmaz. Netflix'in hane doğrulama bağlantısı da okunabilir.
- **Hesap ele geçirmeye karşı koruma:** E-posta değiştirme, şifre sıfırlama, Steam Guard kaldırma gibi maillerdeki kodlar müşteriye hiç gösterilmez; kayıtlara "kod gizlendi" diye düşer.
- **Kod gelene kadar bekleme:** Mail henüz gelmediyse müşteri ekranı yaklaşık 2 dakika boyunca kendiliğinden tekrar sorar; kod düşünce hücrelere yazılır.
- **Authenticator (2FA):** 6 haneli kod sunucuda üretilir, 30 saniyelik geri sayımla gösterilir. Epic, EA, Ubisoft, ChatGPT gibi standart 2FA anahtarlarıyla çalışır.
- **Steam Guard mobil:** `.maFile` içeriği ya da `shared_secret` yapıştırılır; müşteri 5 karakterlik Steam Guard kodunu görür.
- **Hızlı onay (`/onay`):** Müşteri hesap açmadan yalnızca anahtarıyla kodunu alır. Ayarlar > Özellikler'den kapatılabilir. Hızlı onayda kullanılan anahtar bir hesaba eklenemez (ve tersi); paket anahtarları burada çalışmaz.
- **Anahtar kuralları:** kod hakkı, süre (müşteri anahtarı eklediği an başlar), kodlar arası bekleme ve dönemlik hak ("24 saatte 1 kod", "7 günde 3 kod"; dönem o dönemin ilk koduyla başlar). Boş bırakılırsa sınırsız ve süresiz olur. Aynı kodu tekrar görmek hak düşürmez.
- **Ek hak:** Hakkı ya da dönem hakkı dolan müşteriye Anahtarlar tablosundaki "+1 hak ver" düğmesiyle hak verilir (her basış bir hak); müşteri beklemeden yeniden kod alır. Verilen ek haklar kayıtlarda görünür.
- **Müşteri ek hak ister:** Hakkı dolan müşteri kod ekranında "Satıcıdan hak iste"ye basar; istek panelde "Ek hak istekleri" kutusuna düşer, tek tıkla onaylarsın (+1 hak) ya da reddedersin. Ayarlar > Özellikler'den kapatılabilir.
- **Süre uzatma:** Anahtar satırındaki "+30 gün" bitiş tarihini ileri alır; süresi bitmişse bugünden başlatır.
- **Müşteri ayrıntısı:** Müşteriler listesinden bir kişiye tıklayınca anahtarları, kalan hakları, istekleri, son 7 gündeki hata ve farklı IP sayısı, son hareketleri tek sayfada. Hak verme, süre uzatma ve engelleme oradan da yapılır.
- **Telafi:** Hesap düzenleme sayfasında "Telafi ver": hesap bir süre çalışmadıysa onu kullanan bütün müşterilere tek seferde ek hak ve ek gün.
- **Anahtar taşıma:** Hesap kapandıysa bağlı anahtarları ve paket seçimlerini yeni hesaba aktar; müşterinin verdiği ad korunur, müşteri bir şey yapmaz.
- **Müşteriye not:** Her hesaba kısa bir talimat ("Steam Guard ekranında bu kodu yaz") yazılır; kodun altında ve `/onay` sayfasında görünür.
- **Kod geçmişi:** Müşteri kartının altında o hesaptan aldığı son 5 kodun zamanı.
- **Paylaşım uyarısı:** Bir anahtar 24 saatte 3 (ayarlanabilir) farklı IP'den kullanılırsa panelde "Paylaşılıyor olabilir" kutusu çıkar.
- **Oyun kataloğu (`/oyunlar`):** Varsayılan açık; oyun satmıyorsan Ayarlar > Özellikler'den kapat. Yönetimde **Oyunlar** menüsü: oyunu tek tek ya da liste hâlinde eklersin. Oyun adını yazarken Steam mağazasından kapaklı öneriler çıkar (DLC ve paketler elenir); birine tıklayınca ad, kapak ve ("Steam'den otomatik yaz" açıksa) Türkçe kısa açıklama dolar. Kapaksız eklenen oyuna kapak kendiliğinden aranır; kapaksız kartlarda "Kapak bul", listede "Eksik kapakları bul" düğmesi var. Liste hâlinde eklerken de kapaklar ve açıklamalar otomatik bulunur. İstersen kendi görselini yüklersin ya da https bağlantısı verirsin. Öne çıkarma ve gizleme var. Ziyaretçi ana sayfanın üst çubuğundaki "Oyunlar"dan ayrı sayfaya gider: oyunları kapaklarıyla görür, arar, platforma göre süzer, oyuna tıklayınca giriş/kayıt yolunu görür. Bir hesabı oyuna bağlarsan müşterinin Kodlarım kartında oyunun kapağı görünür. Öneriler için sunucunun Steam'e https ile bağlanabilmesi gerekir (Windows'ta PHP'nin `curl.cainfo` ayarı).
- **Bildirimler:** Ayarlar > Bildirimler'den Telegram botu ve/veya e-posta: hesap kod veremiyor, stok azaldı, anahtar paylaşılıyor olabilir, müşteri ek hak istedi. Aynı konu için birkaç saatte bir mesaj gelir; "Deneme mesajı gönder" ile kontrol edilir. Bot anahtarı şifreli saklanır.
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

## Lisans (aylık üyelik)

Her mağaza kendi alan adında ayrı bir kurulumdur ve aylık lisansla çalışır. Lisans yalnızca canlıda (`APP_ENV=production`) zorunludur; yerelde ve testlerde site lisanssız açılır.

- **Lisans anahtarı** alan adını ve bitiş tarihini taşır, satıcının özel anahtarıyla imzalanır (Ed25519). Sitedeki kod yalnızca doğrular; yeni anahtar üretemez. Başka bir alan adında çalışmaz.
- **Son 7 gün** yönetim panelinin üstünde uyarı çıkar. **Süre bitince 3 gün** daha çalışır (kırmızı uyarı). Sonra site kilitlenir: yönetici giriş yapıp yalnızca **Ayarlar > Lisans** sayfasını görür, müşteriler ve ziyaretçiler "Hizmet geçici olarak durduruldu" sayfasını görür, kod alınamaz. Hiçbir veri silinmez.
- Mağaza sahibi yeni anahtarı **Ayarlar > Lisans** sayfasına yapıştırır; kaydedilir kaydedilmez site açılır.

### Satıcı (sen) için

Bir kez, kendi bilgisayarında:

```bash
php artisan guard:license-keygen
```

Özel anahtarı `%USERPROFILE%\.gecit-lisans\ozel-anahtar.txt` dosyasına yazar ve açık anahtarı ekrana basar. Açık anahtar `config/guard.php` > `license.public_key` içinde durur. **Özel anahtarı yedekle, depoya ya da mağaza sunucusuna koyma**; kaybedersen yeni lisans üretemezsin, sızarsa herkes lisans üretebilir.

Her ödemede:

```bash
php artisan guard:license magaza-adi.com --days=30
php artisan guard:license magaza-adi.com --until=2027-01-31 --note="Ocak"
```

Çıkan anahtarı mağaza sahibine gönder. `www.` öneki ve büyük/küçük harf fark etmez.

Not: Mağaza sitesini alıcının kendi sunucusuna kurarsan, kodu değiştirebilen biri kilidi kaldırabilir. Kilidin gerçekten bağlayıcı olması için siteleri kendi sunucunda barındır.

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
- **Desteklenen platformlar ve kod biçimleri:** Netflix (4 rakam), ChatGPT, Prime Video, HBO Max, Disney+, Canva, CapCut, Epic Games, EA, Ubisoft, Rockstar, Riot, Battle.net (6 rakam), Xbox / Microsoft (6-8 rakam), PlayStation (6 rakam), Nintendo (4-6 rakam), Steam (5 harf/rakam, Steam Guard alfabesi). Battle.net Authenticator standart TOTP olmadığı için Battle.net hesaplarında yalnızca mail kodu çalışır; Xbox/Microsoft, PlayStation ve Nintendo hesabında Authenticator uygulamasıyla 2 adımlı doğrulama açıksa kurulum anahtarı girilerek 2FA kodu da verilir. PlayStation girişte genelde SMS ya da Authenticator ister; mail kodu yalnızca e-postayla gelen doğrulamalar içindir. Kaynak: platformların yardım sayfaları ve kod maillerinin bilinen kalıpları (2026-11). Hiçbiri gerçek bir hesapla denenmedi; yeni bir platform hesabı eklediğinde **Bağlantıyı dene** ile okunan kodu ve mail konusunu kontrol et.

## Yerel geliştirme

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed    # yönetici + 6 ürünlü deneme müşterisi
npm run build && php artisan guard:serve
php artisan test
```

### Deneme hesapları (yerel)

`php artisan migrate --seed` sonrası http://localhost:8000/login adresinden:

| Hesap | E-posta | Şifre | Ne görürsün |
|---|---|---|---|
| Yönetici | `admin@demo.test` | `password` | Panel, hesaplar, anahtarlar, müşteriler |
| Müşteri | `musteri@demo.test` | `password` | Kodlarım: 2 Steam (CS2 hesabı 24 saatte 1 kod), Netflix, Disney+, ChatGPT, Prime Video |
| Yeni müşteri | `bos@demo.test` | `password` | Anahtarı yok: boş Kodlarım ekranı |

Müşteri hesabında Steam ve ChatGPT kodları gerçekten üretilir (rastgele deneme anahtarı). Netflix, Disney+ ve Prime Video'nun posta kutusu sahtedir; mail kodu istenince bağlantı hatası verir. Yalnızca deneme verisini yeniden kurmak için: `php artisan db:seed --class=DemoSeeder`.

Bu hesaplar yalnızca yerel geliştirme içindir; seed canlı ortamda (`APP_ENV=production`) çalışmaz. Canlı sitede ilk yöneticiyi `php artisan guard:admin senin@adresin.com` ile kendi şifrenle oluştur. Deneme hesaplarını canlıya taşıma, şifrelerini paylaşılmış say.

## Günlük özet e-postası

Ayarlar > Özellikler'den açılır. Her sabah 09:00'da yöneticilere dünün özeti gider (verilen kod, hatalar, kod veremeyen hesaplar, azalan stok). Çalışması için sunucuda iki şey gerekir:

1. `.env` içinde gerçek bir e-posta ayarı (`MAIL_MAILER=smtp` ve ilgili `MAIL_*` satırları). Varsayılan `log` ayarında e-posta gönderilmez, yalnızca `storage/logs` içine yazılır.
2. Zamanlayıcıyı dakikada bir çalıştıran cron satırı:

```
* * * * * cd /site/yolu && php artisan schedule:run >> /dev/null 2>&1
```

Elle denemek için: `php artisan guard:daily-summary --force`
