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
    | bulunan kod bu kadar saniye önbellekte tutulur. Kod bulunamazsa
    | sonuç daha kısa süre (miss) tutulur; bekleyen müşteri sık sorar.
    */
    'email_cache_seconds' => (int) env('GUARD_EMAIL_CACHE', 20),
    'email_miss_cache_seconds' => (int) env('GUARD_EMAIL_MISS_CACHE', 5),

    /*
    | Kod gelmediyse müşteri ekranı kendiliğinden tekrar sorar:
    | kaç saniyede bir ve en fazla kaç kez.
    */
    'poll' => [
        'interval_seconds' => (int) env('GUARD_POLL_INTERVAL', 7),
        'attempts' => (int) env('GUARD_POLL_ATTEMPTS', 17),
    ],

    /*
    | Aylık lisans (App\Support\License). Canlıda (APP_ENV=production) zorunludur: lisans yoksa,
    | bitmişse ya da başka alan adınınsa site kilitlenir. Açık anahtar satıcının guard:license-keygen
    | çıktısıdır; lisansları yalnızca ona karşılık gelen özel anahtar üretebilir.
    */
    'license' => [
        'enforce' => env('APP_ENV', 'production') === 'production',
        'public_key' => '4Wsv06svgBsMZAS8k+VhlIJsTT3aU8E7hhSK/tjdnWA=',
    ],

    /*
    | Spam / Gereksiz klasörlerine de bakılsın mı. Kod mailleri sık sık
    | oraya düşer. Klasör adı bu desene uyarsa taranır.
    */
    'scan_spam' => (bool) env('GUARD_SCAN_SPAM', true),
    'spam_folder_pattern' => '/spam|junk|bulk|istenmeyen|gereksiz|önemsiz|onemsiz|нежелательн|спам/iu',

    'limits' => [
        'redeem_per_minute' => (int) env('GUARD_REDEEM_LIMIT', 8),
        'code_per_minute' => (int) env('GUARD_CODE_LIMIT', 20),
    ],

    /*
    | Platformlar.
    |   from:    kodu gönderen adreste aranacak parçalar (biri tutsa yeter; boşsa süzülmez)
    |   format:  aşağıdaki code_formats anahtarlarından biri
    |   anchors: kodun hemen önünde geçen sözler; kod bunların yakınında aranır
    |   group:   yönetim ekranında gruplama (stream / game / tool)
    |
    | Gönderen adresleri ve kalıplar platformlar değiştirdikçe güncellenmeli;
    | satıcı her hesapta gönderen süzgecini ve kod biçimini ayrıca değiştirebilir.
    | Kaynaklar: her platformun yardım sayfası ve gerçek kod mailleri (2026-11).
    */
    'platforms' => [
        // Dizi, müzik, yapay zekâ
        'netflix' => [
            'label' => 'Netflix', 'group' => 'stream', 'format' => 'digits4',
            'from' => ['netflix.com'],
            'anchors' => ['sign-in code', 'sign in code', 'giriş kodu', 'oturum açma kodu', 'temporary access code', 'geçici erişim kodu', 'access code', 'erişim kodu', 'enter this code', 'bu kodu gir'],
        ],
        'chatgpt' => [
            'label' => 'ChatGPT', 'group' => 'stream', 'format' => 'digits6',
            'from' => ['openai.com'],
            'anchors' => ['chatgpt code is', 'chatgpt kodun', 'temporary verification code', 'verification code', 'doğrulama kodu', 'enter this code', 'code is'],
        ],
        'prime' => [
            'label' => 'Prime Video', 'mark' => 'PV', 'group' => 'stream', 'format' => 'digits6',
            'from' => ['amazon'],
            'anchors' => ['one time password', 'one-time password', 'otp', 'verification code', 'tek kullanımlık şifre', 'doğrulama kodu'],
        ],
        'hbomax' => [
            'label' => 'HBO Max', 'mark' => 'M', 'group' => 'stream', 'format' => 'digits6',
            'from' => ['hbomax.com', 'max.com'],
            'anchors' => ['one-time code', 'verification code', 'doğrulama kodu', 'tek kullanımlık kod'],
        ],
        'disney' => [
            'label' => 'Disney+', 'group' => 'stream', 'format' => 'digits6',
            'from' => ['disneyplus.com', 'disney'],
            'anchors' => ['one-time passcode', 'passcode', 'tek kullanımlık', 'doğrulama kodu'],
        ],
        'canva' => [
            'label' => 'Canva', 'group' => 'tool', 'format' => 'digits6',
            'from' => ['canva.com'],
            'anchors' => ['login code', 'verification code', 'giriş kodu', 'doğrulama kodu'],
        ],
        'capcut' => [
            'label' => 'CapCut', 'group' => 'tool', 'format' => 'digits6',
            'from' => ['capcut.com'],
            'anchors' => ['verification code', 'doğrulama kodu'],
        ],

        // Oyun platformları
        'steam' => [
            'label' => 'Steam', 'group' => 'game', 'format' => 'steam5',
            'from' => ['steampowered.com', 'steamcommunity.com', 'valvesoftware.com'],
            'anchors' => ['login code', 'steam guard code', 'steam guard', 'access code', 'giriş kodu', 'erişim kodu', 'oturum açma kodu'],
        ],
        'epic' => [
            'label' => 'Epic Games', 'group' => 'game', 'format' => 'digits6',
            'from' => ['epicgames.com'],
            'anchors' => ['security code', 'verification code', 'sign-in code', 'two-factor', 'güvenlik kodu', 'doğrulama kodu', 'giriş kodu'],
        ],
        'ea' => [
            'label' => 'EA', 'group' => 'game', 'format' => 'digits6',
            'from' => ['ea.com'],
            'anchors' => ['ea security code', 'security code', 'güvenlik kodu', 'verification code'],
        ],
        'ubisoft' => [
            'label' => 'Ubisoft', 'group' => 'game', 'format' => 'digits6',
            'from' => ['ubisoft.com', 'ubi.com'],
            'anchors' => ['verification code', 'security code', '2-step verification', 'doğrulama kodu', 'güvenlik kodu'],
        ],
        'rockstar' => [
            'label' => 'Rockstar Games', 'group' => 'game', 'format' => 'digits6',
            'from' => ['rockstargames.com'],
            'anchors' => ['verification code', 'security code', 'one-time code', 'doğrulama kodu'],
        ],
        'riot' => [
            'label' => 'Riot Games', 'group' => 'game', 'format' => 'digits6',
            'from' => ['riotgames.com'],
            'anchors' => ['verification code', 'sign-in code', 'login code', 'doğrulama kodu', 'giriş kodu'],
        ],
        // Blizzard; gönderen alan adları support.blizzard.com/article/378072 listesinden.
        // Battle.net Authenticator standart TOTP değildir; yalnızca mail kodu desteklenir.
        'battlenet' => [
            'label' => 'Battle.net', 'group' => 'game', 'format' => 'digits6',
            'from' => ['blizzard.com', 'battle.net'],
            'anchors' => ['security code', 'verification code', 'one-time passcode', 'passcode', 'güvenlik kodu', 'doğrulama kodu', 'tek kullanımlık'],
        ],
        // Microsoft hesabı güvenlik kodu (Xbox, Game Pass, Minecraft). Kod çoğunlukla 6, bazen 7-8 hane.
        // Microsoft hesabında üçüncü taraf Authenticator uygulaması açıksa 2FA da çalışır.
        'xbox' => [
            'label' => 'Xbox / Microsoft', 'mark' => 'X', 'group' => 'game', 'format' => 'digits6to8',
            'from' => ['accountprotection.microsoft.com', 'microsoft.com', 'xbox.com'],
            'anchors' => ['security code', 'single-use code', 'one-time code', 'verification code', 'güvenlik kodu', 'tek kullanımlık kod', 'doğrulama kodu'],
        ],

        // PSN 2 adımlı doğrulaması SMS ya da Authenticator uygulamasıyla çalışır (playstation.com/support/account/2sv-login).
        // Authenticator anahtarı girilirse 2FA kodu verilir; mail kodu yalnızca e-postayla gelen doğrulamalar için.
        'playstation' => [
            'label' => 'PlayStation', 'mark' => 'PS', 'group' => 'game', 'format' => 'digits6',
            'from' => ['playstation.com', 'sonyentertainmentnetwork.com', 'sony.com'],
            'anchors' => ['verification code', 'sign-in code', 'security code', 'doğrulama kodu', 'giriş kodu'],
        ],
        // Nintendo hesabı kimlik doğrulama kodu: no-reply@accounts.nintendo.com. Uzunluk resmi olarak
        // yazılmıyor (hesap açılışında 4 hane), bu yüzden 4-6 hane kabul edilir.
        'nintendo' => [
            'label' => 'Nintendo', 'mark' => 'NS', 'group' => 'game', 'format' => 'digits4to6',
            'from' => ['accounts.nintendo.com', 'nintendo.com', 'nintendo.net'],
            'anchors' => ['verification code', 'identity verification', 'kimlik doğrulama', 'doğrulama kodu'],
        ],

        'other' => ['label' => 'Diğer', 'group' => 'tool', 'format' => 'digits6', 'from' => [], 'anchors' => []],
    ],

    'platform_groups' => [
        'stream' => 'Dizi, müzik, yapay zekâ',
        'game' => 'Oyun',
        'tool' => 'Uygulama ve diğer',
    ],

    /*
    | Her platformda ayrıca aranan genel sözler (platform sözleri bulunamazsa).
    */
    'common_anchors' => [
        'verification code', 'security code', 'one-time code', 'login code', 'your code', 'code is', 'code:',
        'doğrulama kodu', 'güvenlik kodu', 'giriş kodu', 'onay kodu', 'kodunuz', 'kodun', 'kod:',
        'código', 'code de vérification', 'bestätigungscode', 'код',
    ],

    /*
    | Hesabın e-postasını ya da şifresini değiştirmeye yarayan mailler.
    | Bu maillerdeki kod müşteriye ASLA gösterilmez; aksi halde müşteri
    | hesabı kendi adına geçirebilir. Konu satırında ya da kodun hemen
    | önündeki metinde aranır.
    */
    'blocked_purposes' => [
        '/\b(?:change|update|verify)\s+(?:your\s+)?e-?mail(?:\s+address)?\b/iu',
        '/\be-?mail(?:\s+address)?\s+(?:change|update)\b/iu',
        '/\bpassword\s+(?:reset|change)\b|\breset\s+(?:your\s+)?password\b|\bchange\s+(?:your\s+)?password\s+request/iu',
        '/\b(?:account\s+)?recovery\s+code\b|\brecover\s+your\s+account\b/iu',
        '/\baccount\s+change\s+request\b|\bremov(?:e|ing)\s+(?:the\s+)?steam\s+guard/iu',
        // Doğrulayıcı kaldırma (Battle.net, Steam) ve Microsoft güvenlik bilgisi değişikliği
        '/\b(?:remov|detach)\w*\s+(?:the\s+|your\s+|an\s+)?(?:[\w.]+\s+){0,2}authenticator\b/iu',
        '/\bsecurity\s+info(?:rmation)?\b|\badd(?:ing)?\s+(?:a\s+|an\s+)?(?:new\s+)?(?:alias|security\s+info)/iu',
        '/doğrulayıcı(?:yı)?\s+kaldır|güvenlik\s+bilgi/iu',
        // PlayStation: oturum açma kimliği (e-posta) ya da çevrimiçi kimlik değişikliği
        '/\b(?:change|update)\w*\s+(?:your\s+)?(?:sign-in\s+id|online\s+id)\b|\b(?:sign-in\s+id|online\s+id)\s+(?:change|update)/iu',
        '/e-?posta(?:\s+adresi)?(?:ni|nızı|nizi)?\s+değiş|e-?posta\s+değişikli/iu',
        '/şifre(?:ni|nizi)?\s+sıfırla|parola(?:nızı|ni)?\s+sıfırla|şifre\s+değişikli|hesap\s+kurtarma/iu',
    ],

    /*
    | Mail içinden kodu bulan hazır desenler. Satıcı serbest desen yazamaz;
    | bu, hatalı ya da yavaş bir desenin sunucuyu kilitlemesini önler.
    | type=link olanlar kod yerine maildeki bağlantıyı verir.
    | length: bekleme ekranında gösterilen kutu sayısı.
    */
    'code_formats' => [
        'digits4' => ['label' => '4 rakam (4829)', 'length' => 4, 'pattern' => '/(?<![0-9.,:\/#$€£+])([0-9]{4})(?![0-9]|[.,:-][0-9]|\s?(?:px|%|pt))/u'],
        'digits5' => ['label' => '5 rakam (48291)', 'length' => 5, 'pattern' => '/(?<![0-9.,:\/#$€£+])([0-9]{5})(?![0-9]|[.,:-][0-9]|\s?(?:px|%|pt))/u'],
        'digits6' => ['label' => '6 rakam (482913)', 'length' => 6, 'pattern' => '/(?<![0-9.,:\/#$€£+])([0-9]{6}|[0-9]{3}[ -][0-9]{3})(?![0-9]|[.,:-][0-9]|\s?(?:px|%|pt))/u'],
        'digits7' => ['label' => '7 rakam (4829137)', 'length' => 7, 'pattern' => '/(?<![0-9.,:\/#$€£+])([0-9]{7})(?![0-9]|[.,:-][0-9]|\s?(?:px|%|pt))/u'],
        'digits8' => ['label' => '8 rakam (48291375)', 'length' => 8, 'pattern' => '/(?<![0-9.,:\/#$€£+])([0-9]{8})(?![0-9]|[.,:-][0-9]|\s?(?:px|%|pt))/u'],
        'digits4to6' => ['label' => '4-6 rakam (Nintendo)', 'length' => 6, 'pattern' => '/(?<![0-9.,:\/#$€£+])([0-9]{4,6})(?![0-9]|[.,:-][0-9]|\s?(?:px|%|pt))/u'],
        'digits6to8' => ['label' => '6-8 rakam (Microsoft)', 'length' => 6, 'pattern' => '/(?<![0-9.,:\/#$€£+])([0-9]{6,8})(?![0-9]|[.,:-][0-9]|\s?(?:px|%|pt))/u'],
        'steam5' => ['label' => 'Steam Guard, 5 harf ve rakam (F7K2Q)', 'length' => 5, 'pattern' => '/(?<![A-Za-z0-9])([23456789BCDFGHJKMNPQRTVWXY]{5})(?![A-Za-z0-9])/'],
        'alnum6' => ['label' => '6 harf ve rakam (K7Q2ZP)', 'length' => 6, 'pattern' => '/(?<![A-Za-z0-9])(?=[A-Z]*[0-9])([A-Z0-9]{6})(?![A-Za-z0-9])/'],
        'netflix_link' => [
            'label' => 'Netflix hane bağlantısı',
            'type' => 'link',
            'length' => 0,
            'pattern' => '#(https://www\.netflix\.com/[A-Za-z0-9/_\-]*(?:travel|household|verify|update-primary-location)[^\s"\'<>]*)#i',
        ],
    ],

    /*
    | Bilinen posta sağlayıcıları; hesap formunda ve toplu eklemede sunucu bilgisini doldurmak için.
    */
    'mail_presets' => [
        'gmail.com' => ['host' => 'imap.gmail.com', 'port' => 993, 'encryption' => 'ssl'],
        'googlemail.com' => ['host' => 'imap.gmail.com', 'port' => 993, 'encryption' => 'ssl'],
        'yandex.com' => ['host' => 'imap.yandex.com', 'port' => 993, 'encryption' => 'ssl'],
        'yandex.com.tr' => ['host' => 'imap.yandex.com', 'port' => 993, 'encryption' => 'ssl'],
        'yandex.ru' => ['host' => 'imap.yandex.com', 'port' => 993, 'encryption' => 'ssl'],
        'mail.ru' => ['host' => 'imap.mail.ru', 'port' => 993, 'encryption' => 'ssl'],
        'bk.ru' => ['host' => 'imap.mail.ru', 'port' => 993, 'encryption' => 'ssl'],
        'inbox.ru' => ['host' => 'imap.mail.ru', 'port' => 993, 'encryption' => 'ssl'],
        'list.ru' => ['host' => 'imap.mail.ru', 'port' => 993, 'encryption' => 'ssl'],
        'rambler.ru' => ['host' => 'imap.rambler.ru', 'port' => 993, 'encryption' => 'ssl'],
        'gmx.com' => ['host' => 'imap.gmx.com', 'port' => 993, 'encryption' => 'ssl'],
        'gmx.net' => ['host' => 'imap.gmx.net', 'port' => 993, 'encryption' => 'ssl'],
        'gmx.de' => ['host' => 'imap.gmx.net', 'port' => 993, 'encryption' => 'ssl'],
        'web.de' => ['host' => 'imap.web.de', 'port' => 993, 'encryption' => 'ssl'],
        'yahoo.com' => ['host' => 'imap.mail.yahoo.com', 'port' => 993, 'encryption' => 'ssl'],
        'ymail.com' => ['host' => 'imap.mail.yahoo.com', 'port' => 993, 'encryption' => 'ssl'],
        'icloud.com' => ['host' => 'imap.mail.me.com', 'port' => 993, 'encryption' => 'ssl'],
        'me.com' => ['host' => 'imap.mail.me.com', 'port' => 993, 'encryption' => 'ssl'],
        'aol.com' => ['host' => 'imap.aol.com', 'port' => 993, 'encryption' => 'ssl'],
        'zoho.com' => ['host' => 'imap.zoho.com', 'port' => 993, 'encryption' => 'ssl'],
        'firstmail.ltd' => ['host' => 'imap.firstmail.ltd', 'port' => 993, 'encryption' => 'ssl'],
        'firstmailmorty.com' => ['host' => 'imap.firstmail.ltd', 'port' => 993, 'encryption' => 'ssl'],
        'hypercubemail.com' => ['host' => 'imap.firstmail.ltd', 'port' => 993, 'encryption' => 'ssl'],
        'glorzomail.com' => ['host' => 'imap.firstmail.ltd', 'port' => 993, 'encryption' => 'ssl'],
        'microversemail.com' => ['host' => 'imap.firstmail.ltd', 'port' => 993, 'encryption' => 'ssl'],
        'fuhrenmail.com' => ['host' => 'mail.fuhrenmail.com', 'port' => 993, 'encryption' => 'ssl'],
    ],
];
