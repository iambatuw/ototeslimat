---
version: 1
slug: "resources-views-auth-register-blade-php"
primary_target: "resources/views/auth/register.blade.php"
related_targets: []
---

## Scope

Kayıt (`/register`). Mode: Operate. Audience: anahtarını yeni almış müşteri, telefon ya da bilgisayar. Task: ad, e-posta, şifre, şifre tekrar ile hesap açmak (alanlar sabit, kullanıcı onayladı). Constraints: Aurora cam teması; Fortify kayıt uç noktası değişmez; JS kapalıyken düz form olarak çalışır; CSP.

## Direction contract

THESIS: Kayıt dört kısa soruya bölünür; her ekranda tek soru büyük yazıyla sorulur, ilerleme sitenin imzası olan kod hücreleriyle gösterilir. Reddedilen kategori düzeni: dört alanlı sıkışık form + yanda ürün tanıtımı.

OWN-WORLD: Koyu Aurora zemin, sağ üstte mavi-mor ışık. Üstte dört kod hücresi: biten adım yeşil onaylı, sıradaki vurgu çerçeveli, gelecek olan sönük. Soru 4.75rem'e kadar kalın başlık; tek büyük cam giriş alanı, içinde "Devam" düğmesi.

STORY: Ziyaretçi adını yazar, Enter'a basar, sonraki soru gelir; sağdaki özet adıyla seslenir ve girdiklerini toplar; son adımda hesap açılır ve cüzdanına yönlenir.

FIRST VIEWPORT: Solda üstte marka, sağ üstte "Hesabın var mı? Giriş yap". Sol sütunda ilerleme hücreleri ve adım adı, altında büyük soru, altında 720px'e kadar geniş alan + Devam, altında şifre kuralları ya da ipucu, en altta geri bağlantısı. Sağ sütunda (420px) kişisel özet: "Batu, neredeyse bitti." ve girilen bilgiler, sonrasında ne olacağı.

FORM: Adım adım, sıralı listemde 3. Seed: seed:96474d2e65bf2f25

SIGNATURE: Adım geçerken ilerleme hücresinin içindeki rakam onay işaretine döner ve hücre yeşile oturur; yeni soru aşağıdan yükselir.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
