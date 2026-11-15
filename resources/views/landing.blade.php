@php
    use App\Models\Setting;
    $brand = Setting::brand();
    $logo = Setting::get('logo');
    $favicon = Setting::get('favicon');
    $canRegister = Setting::flag('registration_open');
    $start = $canRegister ? route('register') : route('login');
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ Setting::get('tagline') }}">
    <title>{{ Setting::get('tab_title') ?: $brand }}</title>
    @if ($favicon)<link rel="icon" href="{{ asset($favicon) }}">@endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style nonce="{{ Vite::cspNonce() }}">{!! App\Support\Theme::css() !!}</style>
</head>
<body>
    <header class="bar">
        <div class="container"><div class="bar__inner">
            <a href="{{ route('landing') }}" class="brand" aria-label="{{ $brand }} ana sayfa">
                @if ($logo)<img src="{{ asset($logo) }}" alt="" height="26">@endif
                <span>{{ $brand }}</span>
            </a>
            <nav class="nav nav--optional" aria-label="Sayfa içi">
                <a href="#nasil">Nasıl çalışır</a>
                <a href="#sss">Sorular</a>
            </nav>
            <div class="bar__end">
                <a href="{{ route('login') }}" class="btn btn--ghost btn--s">Giriş yap</a>
                @if ($canRegister)<a href="{{ route('register') }}" class="btn btn--light btn--s">Kayıt ol</a>@endif
            </div>
        </div></div>
    </header>

    <main>
        <section class="container hero enter">
            <h1>{{ Setting::get('tagline') }}</h1>
            <p class="lead">Satın aldığın hesabın doğrulama kodunu satıcıya yazıp beklemeden kendin al. Gece yarısı bile.</p>
            @if (Setting::get('announcement'))
                <p class="notice hero__note">{{ Setting::get('announcement') }}</p>
            @endif
            <div class="hero__actions">
                <a href="{{ $start }}" class="btn btn--primary btn--l">{{ $canRegister ? 'Hemen başla' : 'Giriş yap' }}</a>
            </div>
            <div>
                <x-demo-card />
                <span class="demo-tag">Dene: bu, müşterinin gördüğü kartın aynısı</span>
            </div>
        </section>

        <section class="container section" id="nasil" aria-labelledby="nasil-title">
            <div class="section__head">
                <h2 id="nasil-title">Satıcıya yazmak yok.</h2>
                <p>Platform kod istediğinde cevap zaten burada.</p>
            </div>
            <ol class="flow">
                <li>
                    <h3>Anahtarını ekle</h3>
                    <p>Hesabı aldığında satıcı sana bir ürün anahtarı verir. Kayıt olup bir kere eklersin; hesap listene yerleşir.</p>
                </li>
                <li>
                    <h3>Platforma gir</h3>
                    <p>Satıcının verdiği e-posta ve şifreyle Netflix, ChatGPT ya da hangisiyse ona gir. Platform bir doğrulama kodu ister.</p>
                </li>
                <li>
                    <h3>Kodu buradan al</h3>
                    <p>Hesabının yanındaki düğmeye bas. Mailine gelen kod ya da 2FA kodu saniyeler içinde ekranda.</p>
                </li>
            </ol>
        </section>

        <section class="container section" aria-label="Desteklenen platformlar">
            <p class="platforms">{{ $platforms->implode(', ') }} <span>ve kodunu mail ya da 2FA ile gönderen diğerleri.</span></p>
        </section>

        <section class="container section" id="sss" aria-labelledby="sss-title">
            <div class="section__head">
                <h2 id="sss-title">Sık sorulanlar</h2>
            </div>
            <div class="faq">
                <details>
                    <summary>Kod gelmedi, ne yapmalıyım?</summary>
                    <p>Platformda "kodu tekrar gönder" de, 30 saniye bekleyip düğmeye yeniden bas. Yine gelmezse satıcıyla iletişime geç.</p>
                </details>
                <details>
                    <summary>2FA kodu nedir?</summary>
                    <p>Bazı hesaplar girişte doğrulama uygulamasından 6 haneli kod ister. Bu kod 30 saniyede bir değişir; sayfada geri sayımla gösterilir.</p>
                </details>
                <details>
                    <summary>Hesabın şifresini burada görebilir miyim?</summary>
                    <p>Hayır. Hesap bilgilerini satıcı sana ayrıca iletir. Bu sayfa yalnızca giriş için gereken doğrulama kodunu verir.</p>
                </details>
                <details>
                    <summary>Anahtarımı başkası kullanabilir mi?</summary>
                    <p>Hayır. Anahtar ilk ekleyen hesaba bağlanır ve başka hesapta çalışmaz.</p>
                </details>
            </div>
        </section>

        <section class="container">
            <div class="cta">
                <h2>Anahtarın hazır mı?</h2>
                <a href="{{ $start }}" class="btn btn--primary btn--l">{{ $canRegister ? 'Kayıt ol' : 'Giriş yap' }}</a>
            </div>
        </section>
    </main>

    <footer class="foot">
        <div class="container foot__inner">
            <span>{{ $brand }}</span>
            @if (Setting::get('contact_url'))<a href="{{ Setting::get('contact_url') }}" rel="noopener" target="_blank">Destek</a>@endif
        </div>
    </footer>
</body>
</html>
