---
version: 1
slug: "resources-views-customer-home-blade-php"
primary_target: "resources/views/customer/home.blade.php"
related_targets: ["resources/views/customer/partials/account-row.blade.php","resources/views/customer/partials/wallet-card.blade.php"]
---

## Scope

Kodlarım (`/kodlarim`), müşteri ekranı. Mode: Operate. Audience: hesap satın almış müşteri, telefon ve bilgisayar yarı yarıya, 3-10 hesap. Task: platform kod istediğinde doğru hesabın kodunu birkaç saniyede almak; yeni anahtar eklemek; paket anahtarında hesap seçmek. Constraints: Aurora cam teması (DESIGN.md) korunur; CSP, satır içi style yok; mevcut kod paneli JS'i (CodePanel, bekleme, Steam) çalışmaya devam eder.

## Direction contract

THESIS: Hesaplar cüzdandaki kartlar gibi platform renginde üst üste durur; seçilen kart öne gelir ve kod kartın yüzünde belirir. Reddedilen kategori düzeni: gri liste satırları + sağda form.

OWN-WORLD: Koyu Aurora zemin. Her hesap platform renginde (gradyanlı) dolu bir kart; arkadaki kartlar yalnızca üst şeritleri görünecek şekilde yığılır. Kod hücreleri kartın yüzünde, yarı saydam koyu hücreler. Anahtar ekleme ve yardım sağda sakin bir cam kutuda.

STORY: Müşteri hesaplarını bir bakışta renginden tanır, dokunduğu kart öne gelir, kod düğmesine basar, kod kartın yüzüne düşer, kopyalar. Yeni anahtarı sağdan ekler; yeni kart yığına katılır.

FIRST VIEWPORT: Üst çubuk. Solda "Kodların" başlığı ve kısa yönerge; altında yığın: öndeki kart yığının başında, 46rem genişlikte, başlık, durum çipi (kalan hak ya da kalan gün), meta, kod düğmeleri ve kod hücreleri doğrudan kart yüzünde; arkadaki kartların renkli şeritleri öndeki kartın altında sıralanır. Sağda 22rem "Anahtar ekle" cam kutusu ve "Kod gelmedi mi?" adımları. Telefonda yığın tam genişlik, anahtar kutusu altta.

ADAPTATION (taslaktan sapma): Taslakta arkadaki kartların şeritleri öndeki kartın üstündeydi. Müşteride 3-10 hesap olduğu ve sayfa yarı yarıya telefondan açıldığı için şeritler üstte dursa kod 6-10 şerit (yaklaşık 400-600px) aşağıda, telefonda ilk ekranın dışında kalırdı. Ürün ilkesi "kod en önde" olduğu için öndeki kart yığının başına alındı, şeritler altına.

FORM: Cüzdan, sıralı listemde 5. Seed: seed:74395a1e17376a9d

SIGNATURE: Yığındaki bir karta dokununca kart View Transition ile öne kayar, önceki öndeki kart yığına geri iner. Kod gelince hücreler kartın yüzünde döner ve oturur.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
