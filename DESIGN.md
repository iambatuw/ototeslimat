# Tasarım yönü: Aurora cam

Bir kod teslim aracı; hesap satıcıları ve onların müşterileri için. Koyu zemin, arkada yavaş hareket eden renkli ışık, üstünde Liquid Glass yüzeyler.

## Neden koyu

Müşteriler Netflix, HBO Max, Prime Video gibi koyu arayüzlü platformlardan geliyor ve kodu çoğu zaman gece istiyor. Tek tema gönderilir; açık tema yok.

## Kimlik

- **İmza öğe: kod hücreleri.** Kod, her karakteri ayrı bir hücrede gösterilir. Gelirken karakterler kısa süre döner, sonra soldan sağa sırayla yerine oturur. Ana sayfada, giriş ekranında ve müşteri ekranında aynı öğe kullanılır.
- **Aurora:** sabit arka plan katmanında dört bulanık ışık alanı (vurgu rengi + mor, turkuaz, pembe). Çalışma ekranlarında (`ambient--calm`) daha kısık durur ki tablolar rahat okunsun.
- **Cam (`.glass`):** yalnızca işlev taşıyan yüzen katmanda: üst çubuk ve örnek kod kartı. Parlama sabit ve hafiftir, imleci yumuşakça izler; hover ile açılıp kapanmaz, kart eğilmez. Şeffaflığı azalt ayarında düz yüzeye döner.
- **Vurgu:** tek renk, mağaza sahibi ayarlardan değiştirir (varsayılan `#0a84ff`).
- **Font:** sistemin arayüz fontu (SF Pro / Segoe UI Variable). Başlıklar kalın (700-800). Kod ve anahtarlar eş aralıklı.

## Yerleşim

- **Ana sayfa:** hero iki sütun; solda başlık ve eylem, sağda çalışan örnek kart. Ortalanmış hero yok. "Nasıl çalışır" ve "Sık sorulanlar" bölünmüş bölümdür: başlık solda sabit kalır, içerik sağda akar. Her adımın yanında anlattığı arayüz parçası durur. Ayrı bir kapanış çağrısı bloğu yok; eylem düğmesi "nasıl çalışır" başlığının altındadır.
- **Giriş ekranları:** solda ışığın içinde ürün (örnek kart + kalın başlık), sağda sakin, koyu bir form alanı. Hata satırı yalnızca hata varken yer kaplar.
- **Panel:** özdeş sayı kutuları yok. Verilen kodlar grafiğiyle birlikte tek kartta; anahtar stoğu tek çubuk ve altında satırlar. İlk kurulum adımları yatay bir iz olarak gösterilir, sıradaki adım eylemi taşır.

## Çalışma ekranları

- **Yönetim iskeleti:** solda yüzen cam yan menü (simge + ad); seçili sayfa vurgu rengiyle dolar ve sayfa geçişinde yerine kayar. Telefonda aynı menü alt sekme çubuğuna döner.
- **Hesaplar ve paketler:** platform rozetli kutucuk ızgarası. Rozet, platformun baş harfi ve rengi.
- **Anahtarlar:** üstte tek satırlık üretici (hedef, adet seçici, düğme); yeni anahtarlar tek tek kopyalanabilen çipler olarak çıkar; liste durum haplarıyla süzülür.
- **Müşteriler:** avatarlı satırlar. **Kayıtlar:** zaman akışı; nokta olayın türünü söyler (yeşil başarılı, kırmızı başarısız).
- **Formlar:** solda bölümün ne olduğu, sağda alanlar; kaydet çubuğu altta yapışık durur.
- **Müşteri (Kodlarım):** solda hesap kartları, sağda yapışık "Anahtar ekle" kutusu ve "Kod gelmedi mi?" adımları.

## Hareket

Mağaza sahibi sitenin hareketli olmasını istedi; hareket her yerde var ama her biri kısa ve tek işlidir.

- Sayfa geçişi (View Transitions), başlığın kelime kelime gelişi, liste ve kutucukların sırayla belirmesi.
- Kodun gelişi: posta satırı, ardından dönen ve sırayla oturan karakterler; oturan hücreler hafifçe parlar.
- Düğmelerde ışık geçişi ve kalkma, kutucuklarda imleci izleyen ışık, rozetlerde küçük dönme.
- Panelde sayan rakamlar, soldan açılan grafik, dolan stok çubuğu, atan durum noktası.
- Ana sayfada süzülen örnek kart, akan platform şeridi, kaydırma ilerleme çizgisi, yumuşak açılan sorular.

**Kapatma:** altbilgideki "Hareketi durdur" düğmesi her şeyi durdurur ve seçim cihazda hatırlanır. Varsayılan olarak hareket herkeste oynar; Ayarlar > Özellikler'deki anahtar açılırsa, cihazında "hareketi azalt" seçili ziyaretçide site hareketsiz başlar. Karar `<head>` içindeki küçük betikte verilir ve `html[data-still]` ile uygulanır; CSS'te `prefers-reduced-motion` sorgusu kullanılmaz.

## Kurallar

- CSP sıkı: satır içi `style=""` yok. Değişken değerler CSSOM (`style.setProperty`) ya da SVG öznitelikleriyle verilir.
- Başlığın üstünde küçük etiket (eyebrow) yok, gradyan yazı yok, özdeş ikonlu kart dizisi yok, uydurma rakam ya da yorum yok.
- İkon gerekiyorsa çizilmiş SVG; unicode işaret ya da emoji ikon yerine kullanılmaz.
