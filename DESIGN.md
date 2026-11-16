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

## Hareket

1. Sayfa geçişi: kısa çapraz geçiş (View Transitions).
2. Ana sayfa başlığı kelime kelime gelir; alt bölümler ekrana girince bir kez yükselir.
3. Kodun gelişi: posta satırı "bekleniyor" durumundan mailin düşmesine geçer, ardından karakterler dönerek yerine oturur.
4. 2FA sayacı: halka şeklinde geri sayım, son 5 saniyede uyarı rengi.
5. Arka plan ışığı çok yavaş süzülür; altbilgideki "Hareketi durdur" düğmesiyle durdurulur.

`prefers-reduced-motion: reduce` açıkken hepsi anında ve hareketsiz gösterilir.

## Kurallar

- CSP sıkı: satır içi `style=""` yok. Değişken değerler CSSOM (`style.setProperty`) ya da SVG öznitelikleriyle verilir.
- Başlığın üstünde küçük etiket (eyebrow) yok, gradyan yazı yok, özdeş ikonlu kart dizisi yok, uydurma rakam ya da yorum yok.
- İkon gerekiyorsa çizilmiş SVG; unicode işaret ya da emoji ikon yerine kullanılmaz.
