# Tasarım yönü

Reading this as: bir kod teslim aracı, hesap satıcıları ve onların müşterileri için; Apple'ın koyu arayüz dili (macOS / iOS Dark), dial **ENERGY 2 / RHYTHM 2 / MOTION 2**.

## Neden koyu

Müşteriler Netflix, HBO Max, Prime Video, Disney+ gibi kalıcı koyu arayüzlü yayın platformlarından geliyor ve kodu çoğu zaman gece istiyor. Apple'ın kuralı: sabit koyu görünüm, medya odaklı ve "arayüzün geri çekildiği" ürünlerde yerinde. Mağaza sahibi de koyu tema istedi. Tek tema gönderilir; açık tema yok, yarım çalışan ikinci bir tema da yok.

## Kimlik

- **İmza öğe: kod hücreleri.** Doğrulama kodu, telefondaki tek kullanımlık kod alanı gibi her rakamı ayrı bir hücrede gösterilir ve rakamlar sırayla yerine düşer. Ana sayfada, müşteri ekranında ve giriş ekranının vitrininde aynı öğe kullanılır. Ürünü hatırlatan tek şey budur, geri kalan her şey sakin kalır.
- **Vurgu:** tek renk (varsayılan Apple'ın koyu temadaki mavisi `#0a84ff`, mağaza sahibi değiştirebilir). Yalnızca ana eylemde, seçili durumda ve canlı sayaçta kullanılır.
- **Font:** sistemin kendi arayüz fontu. Apple cihazlarda SF Pro, Windows'ta Segoe UI Variable. Ürün kullanıcının işletim sisteminin parçası gibi hissettirmeli; indirme yok, lisans yok. Kod ve anahtarlar sistemin eş aralıklı fontuyla (SF Mono / Cascadia).

## Yüzeyler

- Zemin `#0b0b0d`, yükseltilmiş yüzey `#161618`, ikinci düzey `#1f1f22`. Saf siyah ve saf beyaz yok.
- Kartlar kenar çizgisiyle ayrılır (1px, %8 beyaz); gölge yalnızca gerçekten yükselen öğede (açılır pencere, vitrin kartı).
- **Liquid Glass yalnızca üstteki yüzen çubukta.** İçerik kaydıkça arkasından geçer. Kartta, listede, butonda cam yok. Şeffaflığı azalt ayarında düz yüzeye döner.

## Hareket (MOTION 2)

Her hareketin bir işi var, sık tekrarlanan etkileşimde hareket yok:

1. **Sayfa geçişi:** sayfalar arası gezinmede beyaz flaş yerine kısa çapraz geçiş (View Transitions).
2. **Sayfa açılışı:** içerik bir kez, 8px yukarı kayarak ve sırayla belirir. Kullanıcı ne olduğunu anlar, beklemez.
3. **Kodun gelişi:** rakamlar hücrelerine sırayla düşer. Bu, bekleme anının bittiğini söyleyen tek kutlama.
4. **2FA sayacı:** halka şeklinde geri sayım; süre azaldıkça halka kapanır, son 5 saniyede renk uyarıya döner.
5. **Ana sayfa denemesi:** ziyaretçi örnek karttaki düğmeye basar, yükleniyor durumu ve ardından kodun gelişi oynar.

`prefers-reduced-motion: reduce` açıkken hepsi anında, hareketsiz gösterilir.

## Ritim

Ana sayfa bölümleri birbirinden farklı kurulur: hero (ortada, ürün denemesi), nasıl çalışır (soldan sağa akış), platformlar (tek satır metin), sorular (liste). Üç özdeş kart, uydurma yorum, uydurma rakam yok.
