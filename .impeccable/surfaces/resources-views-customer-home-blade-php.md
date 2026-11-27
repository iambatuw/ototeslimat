---
version: 1
slug: "resources-views-customer-home-blade-php"
primary_target: "resources/views/customer/home.blade.php"
related_targets: ["resources/views/customer/partials/hero.blade.php","resources/views/customer/partials/arow.blade.php","resources/views/customer/partials/pick.blade.php"]
---

## Scope

Kodlarım (`/kodlarim`), müşteri ekranı. Mode: Operate. Audience: hesap satın almış müşteri, telefon ve bilgisayar yarı yarıya, 3-10 hesap; aynı platformdan birkaç hesap olabilir (örneğin 3 Steam) ve her birine farklı zamanlarda girer. Task: doğru hesabın kodunu birkaç saniyede almak; hesapları kendi adıyla ayırmak; yeni anahtar eklemek; paket anahtarında hesap seçmek. Constraints: Aurora cam teması; CSP; CodePanel JS (bekleme, Steam) korunur. Kullanıcı (2026-11-27) önceki 12 düzenin hepsini reddetti ve "daha gösterişli, hiçbirine benzemeyen" istedi.

## Direction contract

THESIS: Kodlarım bir oyun kütüphanesi gibi: her hesap kapaklı bir kart, tek düğmeyle kod. Reddedilen: liste + ayrıntı bölmeleri, vitrin, raf sırtları, yörünge ve diğer gösterişli düzenler.

OWN-WORLD: Koyu Aurora zemin, koyu kartlar (paper-2, ince çizgi), 16:9 kapak, vurgu renginde dolu düğme, durum hapı kapağın sol üstünde.

STORY: Müşteri girer, son kullandığı hesap ilk kartta. Kartın düğmesine basar, kod kartın içinde gelir. Ayrıntı isterse "Ayrıntılar"ı açar.

FIRST VIEWPORT: Üst çubuk; solda "Kodlarım" ve hesap sayısı, sağda ızgara/liste geçişi ve "Anahtar ekle"; arama + sayılı platform hapları; 3 sütun kart ızgarası.

FORM: Kullanıcının verdiği örneğe göre kütüphane ızgarası. seed: none (pinned) "Kodlarım sayfası için şöyle bişi istiyorum ... basit" (2026-11-27, "Oyunlarım" ekran görüntüsüyle)

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
