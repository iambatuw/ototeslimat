---
version: 1
slug: "resources-views-customer-home-blade-php"
primary_target: "resources/views/customer/home.blade.php"
related_targets: ["resources/views/customer/partials/hero.blade.php","resources/views/customer/partials/arow.blade.php","resources/views/customer/partials/pick.blade.php"]
---

## Scope

Kodlarım (`/kodlarim`), müşteri ekranı. Mode: Operate. Audience: hesap satın almış müşteri, telefon ve bilgisayar yarı yarıya, 3-10 hesap; aynı platformdan birkaç hesap olabilir (örneğin 3 Steam) ve her birine farklı zamanlarda girer. Task: doğru hesabın kodunu birkaç saniyede almak; hesapları kendi adıyla ayırmak; yeni anahtar eklemek; paket anahtarında hesap seçmek. Constraints: Aurora cam teması; CSP; CodePanel JS (bekleme, Steam) korunur.

## Direction contract

THESIS: En son kullandığın hesap üstte büyük kart olarak kodu hazır bekler; diğerleri platform raflarında, aranabilir ve süzülebilir. Reddedilen düzenler: tek düz liste (aynı platformlar karışır) ve üst üste kart yığını (kullanıcı 2026-11-26'da sıfırdan istedi).

OWN-WORLD: Koyu Aurora zemin. Büyük kart platform renginde gradyan, kod hücreleri kartın yüzünde, müşterinin verdiği ad büyük, asıl hesap adı küçük. Raflar sakin koyu satırlar; rozet platform renginde kısa işaret; seçili satır platform renginden yatay ışık alır.

STORY: Müşteri girer, en son kullandığı hesap üstte hazır. Başka bir Steam lazımsa "Steam · 3 hesap" rafından kendi verdiği adla bulur, "Kod al"a basar; hesap yukarı gelir ve kod kendiliğinden istenir. Hesaba ad vermek için kartta "Bu hesaba ad ver".

FIRST VIEWPORT: Üst çubuk. Solda "Kodların" başlığı ve bir cümle; altında "Son kullandığın" etiketi ve büyük kart (işaret, kicker: platform · son kod zamanı, ad, alt ad, durum çipi, meta, kod düğmesi, "Bu hesaba ad ver", kod hücreleri). Altında paket seçimi varsa o, sonra arama + haplar, sonra raflar. Sağda 22rem "Anahtar ekle" cam kutusu ve "Kod gelmedi mi?". Telefonda tek sütun.

FORM: Son kullandığın + raflar (Bul ve al ile Platform rafları karışımı; kullanıcının yönlendirmesi), sıralı listemde 5. Seed: seed:d552057c727a76ea

SIGNATURE: Raftaki "Kod al" hesabı yukarıdaki karta getirir (kart hafifçe yükselerek değişir), tek kod türü varsa kodu hemen ister; raftaki satır "Yukarıda açık" olur ve URL `?hesap=` ile güncellenir.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
