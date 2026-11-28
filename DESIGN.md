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
- **Giriş ve şifre ekranları:** solda ışığın içinde ürün (Steam Guard kodu veren örnek kart + kalın başlık), sağda sakin, koyu bir form alanı. Hata satırı yalnızca hata varken yer kaplar.
- **Panel:** özdeş sayı kutuları yok. Verilen kodlar grafiğiyle birlikte tek kartta; anahtar stoğu tek çubuk ve altında satırlar. İlk kurulum adımları yatay bir iz olarak gösterilir, sıradaki adım eylemi taşır.

## Çalışma ekranları

- **Yönetim iskeleti:** solda yüzen cam yan menü (simge + ad); seçili sayfa vurgu rengiyle dolar ve sayfa geçişinde yerine kayar. Telefonda aynı menü alt sekme çubuğuna döner.
- **Hesaplar ve paketler:** platform rozetli kutucuk ızgarası. Rozet, platformun kısa işareti (`platformMark`) ve rengi.
- **Anahtarlar:** üstte tek satırlık üretici (hedef, adet seçici, düğme); yeni anahtarlar tek tek kopyalanabilen çipler olarak çıkar; liste durum haplarıyla süzülür.
- **Müşteriler:** avatarlı satırlar; ad ya da "Ayrıntı" müşteri sayfasına gider. **Müşteri ayrıntısı:** başta avatar, ad, durum; altında küçük sayı kutuları (kayıt, son giriş, anahtar, aldığı kod, 7 günde hata, 7 günde farklı IP; eşik aşılırsa kırmızı); sonra ek hak istekleri, anahtar tablosu (müşteri sütunu yok), son hareketler. Anahtar satırındaki düğmeler sığmazsa alt satıra geçer. **Panel:** bekleyen ek hak istekleri ve paylaşım uyarısı varsa grafiklerin altında, diğer kutulardan önce durur. **Kayıtlar:** zaman akışı; nokta olayın türünü söyler (yeşil başarılı, kırmızı başarısız).
- **Formlar:** solda bölümün ne olduğu, sağda alanlar; kaydet çubuğu altta yapışık durur. **Hesap ekle/düzenle** farklıdır: solda numaralı kartlar (1 Hesap, 2 Kod nereden gelir), sağda yapışık panel (müşterinin göreceği kartın canlı önizlemesi platform renginde, "Kod versin" anahtarı, Kaydet ve Vazgeç). Kod kaynağı iki seçilebilir kutu (Mail kodu, Authenticator/Steam Guard); açılınca alanları kesik çizgili kutuda belirir. Oyun bağlama yalnızca oyun platformunda görünür. Yeni hesapta üstte "Tek hesap / Toplu ekle" sekmeleri. Telefonda panel formun altına iner.
- **Müşteri (Kodlarım): kütüphane.** Sade kart ızgarası (müşteri 2026-11-27 bir oyun mağazasının "Oyunlarım" sayfasını örnek gösterdi, gösterişli denemelerin yerine bunu seçti). Başlık "Kodlarım" ve altında "N hesap kütüphanende"; sağda ızgara / liste geçişi (seçim cihazda hatırlanır) ve "Anahtar ekle" açılır kutusu. 6+ hesapta arama, 2+ platformda sayılı platform hapları ("Steam 2"). Kart: üstte Steam başlığı oranında (460:215) görsel: Steam oyununda oyunun yatay kapağı; başka dikey kapakta arkası bulanık, önde kapağın tamamı; kapak yoksa platform renginde zeminde beyaz platform logosu (`public/img/platforms`, Simple Icons; Disney+ ve CapCut'ta logo yok, platform adı yazılır), sol üstte durum hapı (kalan hak/gün, "Yeni hak … sonra"); altında ad (satıcının verdiği hesap adı; oyuna bağlıysa oyunun adı, müşteri adı değiştiremez), alt satırda platform ve hesap adı, "Eklendi: tarih · Son kod …", kartın dibine yaslı vurgu renginde kod düğmesi (iki kod türü varsa "Mail kodu" ve "2FA kodu" yan yana, ikincisi koyu; böylece kartlar eşit boyda); kod kartın üstünde açılan katmanda çıkar (kart büyümez, ızgara bozulmaz; sağ üstte kapat). 2FA / Steam Guard sayacı biterse sunucunun önceden verdiği sonraki kod kendiliğinden yerine geçer, ikinci hak harcanmaz. Görselin sağ üstündeki yuvarlak "i" düğmesi aynı şekilde "Ayrıntılar" katmanını açar: satıcının notu (varsa düğmede turuncu nokta), hak ve geçerlilik kutuları, son kodlar, mail yardımı; Esc kapatır. Liste görünümünde katman açıkken satır kodu sığacak kadar uzar. Liste görünümünde kapak solda, düğme sağda. Sıra: en son kod alınan önce; kod veremeyenler soluk, en sonda. Adresle (`?hesap=`) ya da yeni eklenen anahtar kartı vurgu çerçevesi alır ve ekrana kaydırılır. **Boş kütüphane: logo duvarı** (müşteri 4 seçenek arasından seçti, 2026-11-27). Başlık "Kodlarım"; altında geniş, yuvarlak köşeli bir bant: kod alınabilen platformların logoları kendi renklerinde karolar halinde eğik (-8°) satırlar, satırlar ters yönlerde çok yavaş kayar (70-85 sn); ortası koyulaşır ve orada "Kütüphanen boş", tek cümle, anahtar alanı + vurgu renginde "Ekle", "Anahtarın yoksa satın aldığın yerden iste". Duraklat / hareketi azalt kaymayı durdurur. Telefonda düğme alanın altına iner.
- **Oyun kataloğu (`/oyunlar`, isteğe bağlı):** üstte başlık ve sağda eğik kapak kolajı olan alçak bir bant (yüksekliği yazıya göre, kolaj 12rem; müşteri büyük bulup küçülttürdü, 2026-11-27); altında yapışık cam araç çubuğu (arama + platform hapları); dikey kapaklı (2:3) kart ızgarası, öne çıkanlarda sarı "Öne çıkan" etiketi, kapak yoksa platform renginde işaretli boş kapak. Kart altında ad, platform simgesiyle platform ve sağda küçük ok. Karta tıklayınca solda kapak, sağda platform, ad, açıklama ve giriş/kayıt düğmeleri olan pencere. Ana sayfada oyun gösterilmez; üst çubuktaki "Oyunlar" düğmesi bu sayfaya götürür (telefonda da görünür). Yönetimde oyun adı yazılırken kapaklı öneri listesi açılır. Kodlarım'da oyuna bağlı hesabın işareti oyunun kapağıdır.
- **Kayıtlar (Son hareketler):** günlere ayrılmış liste (Bugün, Dün, tarih başlıkları yapışık). Solda olayın türünü söyleyen renkli simge kutusu (yeşil onay, kırmızı çarpı, turuncu istek), ortada ne oldu ve ayrıntı, sağda saat, müşteri (ayrıntı sayfasına gider) ve soluk IP.
- **Kayıt: adım adım.** Yalın misafir iskeleti (`layouts.guest :bare`). Her ekranda tek soru, 4.75rem'e kadar kalın; ilerleme dört kod hücresiyle gösterilir, biten hücrede rakam yeşil onay işaretine döner. Enter sonraki soruya geçer, hatalar adımın altında söylenir, sunucu bir alanı reddederse o adımdan açılır. Sağda adıyla seslenen kısa özet. JS yoksa dört alan alt alta düz form olarak çalışır.

## Hareket

Mağaza sahibi sitenin hareketli olmasını istedi; hareket her yerde var ama her biri kısa ve tek işlidir.

- Sayfa geçişi (View Transitions), liste ve kutucukların sırayla belirmesi, içeriğin hafifçe yükselmesi.
- **Başlıklar animasyonla gelmez** (mağaza sahibi istedi, 2026-11-26): sayfa başlığı ve başlık bloğu yerinde durur, altındaki içerik her açılışta sırayla yükselir. Kodlarım'da araç çubuğu ve kartlar sırayla gelir.
- Kodun gelişi: posta satırı, ardından dönen ve sırayla oturan karakterler; oturan hücreler hafifçe parlar.
- Düğmelerde ışık geçişi ve kalkma, kutucuklarda imleci izleyen ışık. Satır ve kutucuk üstüne gelince simgeler, avatarlar ve rozetler yerinde durur; yalnızca zemin aydınlanır (mağaza sahibi istedi, 2026-11-27).
- Panelde sayan rakamlar, soldan açılan grafik, dolan stok çubuğu, atan durum noktası.
- Ana sayfada süzülen örnek kart, akan platform şeridi, kaydırma ilerleme çizgisi, yumuşak açılan sorular.

**Kapatma:** yan menüdeki ve üst çubuktaki duraklat düğmesi her şeyi durdurur ve seçim cihazda hatırlanır. Varsayılan olarak hareket herkeste oynar; Ayarlar > Özellikler'deki anahtar açılırsa, cihazında "hareketi azalt" seçili ziyaretçide site hareketsiz başlar. Karar `<head>` içindeki küçük betikte verilir ve `html[data-still]` ile uygulanır; CSS'te `prefers-reduced-motion` sorgusu kullanılmaz.

## Kurallar

- CSP sıkı: satır içi `style=""` yok. Değişken değerler CSSOM (`style.setProperty`) ya da SVG öznitelikleriyle verilir.
- Başlığın üstünde küçük etiket (eyebrow) yok, gradyan yazı yok, özdeş ikonlu kart dizisi yok, uydurma rakam ya da yorum yok.
- İkon gerekiyorsa çizilmiş SVG; unicode işaret ya da emoji ikon yerine kullanılmaz.
