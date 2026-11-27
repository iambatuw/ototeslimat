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
- **Giriş ve şifre ekranları:** solda ışığın içinde ürün (örnek kart + kalın başlık), sağda sakin, koyu bir form alanı. Hata satırı yalnızca hata varken yer kaplar.
- **Panel:** özdeş sayı kutuları yok. Verilen kodlar grafiğiyle birlikte tek kartta; anahtar stoğu tek çubuk ve altında satırlar. İlk kurulum adımları yatay bir iz olarak gösterilir, sıradaki adım eylemi taşır.

## Çalışma ekranları

- **Yönetim iskeleti:** solda yüzen cam yan menü (simge + ad); seçili sayfa vurgu rengiyle dolar ve sayfa geçişinde yerine kayar. Telefonda aynı menü alt sekme çubuğuna döner.
- **Hesaplar ve paketler:** platform rozetli kutucuk ızgarası. Rozet, platformun kısa işareti (`platformMark`) ve rengi.
- **Anahtarlar:** üstte tek satırlık üretici (hedef, adet seçici, düğme); yeni anahtarlar tek tek kopyalanabilen çipler olarak çıkar; liste durum haplarıyla süzülür.
- **Müşteriler:** avatarlı satırlar; ad ya da "Ayrıntı" müşteri sayfasına gider. **Müşteri ayrıntısı:** başta avatar, ad, durum; altında küçük sayı kutuları (kayıt, son giriş, anahtar, aldığı kod, 7 günde hata, 7 günde farklı IP; eşik aşılırsa kırmızı); sonra ek hak istekleri, anahtar tablosu (müşteri sütunu yok), son hareketler. Anahtar satırındaki düğmeler sığmazsa alt satıra geçer. **Panel:** bekleyen ek hak istekleri ve paylaşım uyarısı varsa grafiklerin altında, diğer kutulardan önce durur. **Kayıtlar:** zaman akışı; nokta olayın türünü söyler (yeşil başarılı, kırmızı başarısız).
- **Formlar:** solda bölümün ne olduğu, sağda alanlar; kaydet çubuğu altta yapışık durur.
- **Müşteri (Kodlarım): son kullandığın + raflar.** Müşteride çoğu zaman 3-10 hesap olur ve aynı platformdan birkaç tane olabilir (örneğin 3 Steam, her birine farklı zamanlarda girer). Üstte en son kod alınan (ya da müşterinin seçtiği) hesap platform renginde büyük bir kart: kısa işaret, müşterinin verdiği ad büyük, asıl hesap adı küçük alt satırda, durum çipi (kalan gün ya da kalan hak), son kod zamanı, kod düğmeleri ve kod hücreleri doğrudan kart yüzünde (rakamlar kartın en büyük yazısı). Bilgisayarda kart yatay ve kısadır: hesap solda, kod düğmeleri aynı satırda sağda, kod altta tek satır; telefonda dikey. Kod gelince görünmüyorsa ekran ona kayar. Kartta "Bu hesaba ad ver" küçük açılır alanı var; adı yalnızca o müşteri görür (`account_labels`). Altında paket seçimi (seçim hakkı kaldıysa), sonra arama (6+ hesapta) ve platform hapları, sonra raflar: aynı platformdan 2+ hesap kendi rafında ("Steam · 3 hesap"), tek hesaplı platformlar "Diğer hesapların" rafında. Raf satırı: rozet, ad, durum, son kod zamanı, "Kod al". "Kod al" sayfayı yenilemeden o hesabı üstteki karta getirir ve tek kod türü varsa kodu hemen ister; JS yoksa `?hesap=` ile aynı şeyi sunucu yapar. Sıralama en son kullanılandan başlar, kod veremeyenler sonda. Platform rozeti kısa işarettir (`platformMark`); aynı harf ve renge düşenler ayrı işaret taşır (NS, PS, PV). Sağda yapışık "Anahtar ekle" cam kutusu ve "Kod gelmedi mi?" adımları; telefonda alta iner. **Boş cüzdan:** başlık "Cüzdanın boş"; ilk hesabın yerinde kart oranında koyu mor-turkuaz bir kart durur ve kartın kendisi anahtar formudur (vurgu renginden bağımsız). Yanda "Ekleyince ne olur?" üç adım.
- **Kayıt: adım adım.** Yalın misafir iskeleti (`layouts.guest :bare`). Her ekranda tek soru, 4.75rem'e kadar kalın; ilerleme dört kod hücresiyle gösterilir, biten hücrede rakam yeşil onay işaretine döner. Enter sonraki soruya geçer, hatalar adımın altında söylenir, sunucu bir alanı reddederse o adımdan açılır. Sağda adıyla seslenen kısa özet. JS yoksa dört alan alt alta düz form olarak çalışır.

## Hareket

Mağaza sahibi sitenin hareketli olmasını istedi; hareket her yerde var ama her biri kısa ve tek işlidir.

- Sayfa geçişi (View Transitions), liste ve kutucukların sırayla belirmesi, içeriğin hafifçe yükselmesi.
- **Başlıklar animasyonla gelmez** (mağaza sahibi istedi, 2026-11-26): sayfa başlığı ve başlık bloğu yerinde durur, altındaki içerik her açılışta sırayla yükselir. Kodlarım'da kart, araçlar, raf satırları ve yan kutular sırayla gelir.
- Kodun gelişi: posta satırı, ardından dönen ve sırayla oturan karakterler; oturan hücreler hafifçe parlar.
- Düğmelerde ışık geçişi ve kalkma, kutucuklarda imleci izleyen ışık. Satır ve kutucuk üstüne gelince simgeler, avatarlar ve rozetler yerinde durur; yalnızca zemin aydınlanır (mağaza sahibi istedi, 2026-11-27).
- Panelde sayan rakamlar, soldan açılan grafik, dolan stok çubuğu, atan durum noktası.
- Ana sayfada süzülen örnek kart, akan platform şeridi, kaydırma ilerleme çizgisi, yumuşak açılan sorular.

**Kapatma:** yan menüdeki ve üst çubuktaki duraklat düğmesi her şeyi durdurur ve seçim cihazda hatırlanır. Varsayılan olarak hareket herkeste oynar; Ayarlar > Özellikler'deki anahtar açılırsa, cihazında "hareketi azalt" seçili ziyaretçide site hareketsiz başlar. Karar `<head>` içindeki küçük betikte verilir ve `html[data-still]` ile uygulanır; CSS'te `prefers-reduced-motion` sorgusu kullanılmaz.

## Kurallar

- CSP sıkı: satır içi `style=""` yok. Değişken değerler CSSOM (`style.setProperty`) ya da SVG öznitelikleriyle verilir.
- Başlığın üstünde küçük etiket (eyebrow) yok, gradyan yazı yok, özdeş ikonlu kart dizisi yok, uydurma rakam ya da yorum yok.
- İkon gerekiyorsa çizilmiş SVG; unicode işaret ya da emoji ikon yerine kullanılmaz.
