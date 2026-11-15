<?php

return [

    /*
    | Ürün anahtarlarının başına gelen kısa önek. Örnek: GK-7Q4M2K9P-A8ZC6LQ3
    | Sonradan değiştirmek eski anahtarları bozmaz; doğrulama öneki yok sayar.
    */
    'key_prefix' => env('GUARD_KEY_PREFIX', 'GK'),

    /*
    | Cloudflare ya da bir proxy arkasındaysa gerçek ziyaretçi IP'si için
    | TRUSTED_PROXIES=* (veya virgülle proxy IP'leri). Boşsa kapalı.
    */
    'trusted_proxies' => env('TRUSTED_PROXIES'),

    /*
    | Mail kodunda kaç dakika öncesine kadar gelen mailler geçerli sayılır.
    */
    'email_lookback_minutes' => (int) env('GUARD_EMAIL_LOOKBACK', 15),

    /*
    | Aynı hesaba arka arkaya gelen istekler posta kutusunu yormasın diye
    | bulunan kod bu kadar saniye önbellekte tutulur.
    */
    'email_cache_seconds' => (int) env('GUARD_EMAIL_CACHE', 20),

    'limits' => [
        'redeem_per_minute' => (int) env('GUARD_REDEEM_LIMIT', 8),
        'code_per_minute' => (int) env('GUARD_CODE_LIMIT', 12),
    ],

    /*
    | Platformlar. from: kodu gönderen adreste aranacak parça (boşsa süzülmez).
    | format: aşağıdaki code_formats anahtarlarından biri.
    | Gönderen adresleri ve kod biçimleri platformlar değiştirdikçe güncellenmeli;
    | satıcı her hesapta ikisini de ayrıca değiştirebilir.
    */
    'platforms' => [
        'netflix' => ['label' => 'Netflix', 'from' => 'netflix.com', 'format' => 'digits4'],
        'chatgpt' => ['label' => 'ChatGPT', 'from' => 'openai.com', 'format' => 'digits6'],
        'prime' => ['label' => 'Prime Video', 'from' => 'amazon', 'format' => 'digits6'],
        'hbomax' => ['label' => 'HBO Max', 'from' => 'hbomax.com', 'format' => 'digits6'],
        'disney' => ['label' => 'Disney+', 'from' => 'disneyplus.com', 'format' => 'digits6'],
        'spotify' => ['label' => 'Spotify', 'from' => 'spotify.com', 'format' => 'digits6'],
        'youtube' => ['label' => 'YouTube Premium', 'from' => 'google.com', 'format' => 'digits6'],
        'canva' => ['label' => 'Canva', 'from' => 'canva.com', 'format' => 'digits6'],
        'capcut' => ['label' => 'CapCut', 'from' => 'capcut.com', 'format' => 'digits6'],
        'other' => ['label' => 'Diğer', 'from' => null, 'format' => 'digits6'],
    ],

    /*
    | Mail içinden kodu bulan hazır desenler. Satıcı serbest desen yazamaz;
    | bu, hatalı ya da yavaş bir desenin sunucuyu kilitlemesini önler.
    | type=link olanlar kod yerine maildeki bağlantıyı verir.
    */
    'code_formats' => [
        'digits4' => ['label' => '4 rakam (4829)', 'pattern' => '/\b([0-9]{4})\b/'],
        'digits6' => ['label' => '6 rakam (482913)', 'pattern' => '/\b([0-9]{6})\b/'],
        'digits8' => ['label' => '8 rakam (48291375)', 'pattern' => '/\b([0-9]{8})\b/'],
        'alnum6' => ['label' => '6 harf ve rakam (K7Q2ZP)', 'pattern' => '/\b(?=[A-Z]*[0-9])([A-Z0-9]{6})\b/'],
        'netflix_link' => [
            'label' => 'Netflix hane bağlantısı',
            'type' => 'link',
            'pattern' => '#(https://www\.netflix\.com/[A-Za-z0-9/_\-]*(?:travel|household|verify|update-primary-location)[^\s"\'<>]*)#i',
        ],
    ],

    /*
    | Bilinen posta sağlayıcıları; hesap formunda sunucu bilgisini doldurmak için.
    */
    'mail_presets' => [
        'gmail.com' => ['host' => 'imap.gmail.com', 'port' => 993, 'encryption' => 'ssl'],
        'googlemail.com' => ['host' => 'imap.gmail.com', 'port' => 993, 'encryption' => 'ssl'],
        'yandex.com' => ['host' => 'imap.yandex.com', 'port' => 993, 'encryption' => 'ssl'],
        'yandex.com.tr' => ['host' => 'imap.yandex.com', 'port' => 993, 'encryption' => 'ssl'],
        'mail.ru' => ['host' => 'imap.mail.ru', 'port' => 993, 'encryption' => 'ssl'],
        'rambler.ru' => ['host' => 'imap.rambler.ru', 'port' => 993, 'encryption' => 'ssl'],
        'gmx.com' => ['host' => 'imap.gmx.com', 'port' => 993, 'encryption' => 'ssl'],
        'yahoo.com' => ['host' => 'imap.mail.yahoo.com', 'port' => 993, 'encryption' => 'ssl'],
        'icloud.com' => ['host' => 'imap.mail.me.com', 'port' => 993, 'encryption' => 'ssl'],
    ],
];
