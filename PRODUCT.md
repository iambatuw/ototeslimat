# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Müşteri (son kullanıcı):** Bir hesap satıcısından Netflix, ChatGPT, Steam gibi bir hesap (ya da birkaç hesap) satın almış kişi. Platformda oturum açarken doğrulama kodu istendiğinde bu siteye gelir, kodu alır, platforma yazar. Kodlarım sayfasını telefondan ve bilgisayardan yaklaşık yarı yarıya açar; platform çoğu zaman başka bir ekranda (TV, bilgisayar) açıktır. Bir müşteride genelde 3-10 hesap olur; aynı platformdan birkaç hesap olabilir (örneğin 3 Steam) ve müşteri her birine farklı zamanlarda girer, bu yüzden hesaplara kendi adını verebilir ve en son kullandığı hesap öne gelir.
- **Mağaza sahibi (yönetici):** Kodu satın alıp kendi mağazası olarak kuran satıcı. Hesapları, posta kutularını, 2FA anahtarlarını ve ürün anahtarlarını yönetim panelinden yönetir.

## Product Purpose

Hesap satıcıları için otomatik kod teslimi. Müşteri satıcıya yazıp beklemeden, gece yarısı bile, giriş kodunu kendisi alır. Başarı: müşteri kod istendiği anda birkaç saniyede doğru kodu görür ve satıcıya mesaj atmaz.

## Positioning

Müşteri hesabın posta kutusuna ya da 2FA anahtarına hiç erişmeden yalnızca kodu görür. Kod, posta kutusundaki maildan (Spam dahil) platformun kalıbına göre okunur ya da 2FA/Steam Guard anahtarından sunucuda üretilir. E-posta/şifre değiştirme maillerindeki kodlar hiç gösterilmez.

## Operating Context

- Müşteri akışı: kayıt ol, ürün anahtarını ekle (ya da hesap açmadan `/onay` sayfasında anahtarla kod al), platformda giriş yap, kod istendiğinde düğmeye bas. Mail kodu gelmediyse ekran yaklaşık 2 dakika kendiliğinden tekrar sorar.
- Anahtar kuralları: kod hakkı, geçerlilik süresi (anahtar eklendiği an başlar), kodlar arası bekleme. Paket anahtarı: müşteri listeden belirli sayıda hesap seçer, seçim kalıcıdır.
- Platformlar: Netflix, ChatGPT, Prime Video, HBO Max, Disney+, Spotify, YouTube Premium, Canva, CapCut, Steam, Epic Games, EA, Ubisoft, Rockstar, Riot, Battle.net, Xbox/Microsoft, PlayStation, Nintendo.

## Capabilities and Constraints

- Laravel + Blade, Vite ile derlenen düz CSS/JS. Sıkı CSP: satır içi `style=""` yok.
- Kayıt formu alanları sabit: ad, e-posta, şifre, şifre tekrar (kullanıcı onayladı, 2026-11-25).
- Arayüz dili Türkçe.
- Her kurulum tek mağaza; kuran kişi yönetici. Kaynak kodu satılır, bu yüzden güvenlik ve güncellenebilirlik önceliklidir.

## Brand Commitments

- Site adı varsayılan olarak **Geçit** (APP_NAME); mağaza sahibi marka adını, renk vurgusunu, logoyu ayarlardan değiştirir.
- Görsel dünya DESIGN.md'deki "Aurora cam": kullanıcı yeni sayfalarda da bu temanın kalmasını istedi (2026-11-25), yerleşim yenilenebilir.
- Mağaza sahibi hareketli bir site istedi; hareket her yerde var, duraklat düğmesiyle durdurulur.

## Evidence on Hand

Gerçek müşteri yorumu, satış rakamı ya da kullanıcı sayısı yok; uydurulmaz. Test hesabı yok: platform mail kalıpları araştırmaya dayanır.

## Product Principles

1. Kod en önde: müşteri sayfaya kod için gelir, ona giden yol tek dokunuş olmalı.
2. Müşteri hiçbir sırrı görmez: şifre, posta kutusu, 2FA anahtarı asla ekrana gelmez.
3. Durum her zaman açık: kod bekleniyor, geldi, süresi doldu, hakkı bitti ayrı ayrı anlaşılır.
4. Satıcıya yazma ihtiyacını ortadan kaldır: ne yapacağını ekran söyler.
