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
        <div class="container bar__inner">
            <a href="{{ route('landing') }}" class="brand" aria-label="{{ $brand }} ana sayfa">
                @if ($logo)<img src="{{ asset($logo) }}" alt="" height="28">@endif
                <span>{{ $brand }}</span>
            </a>
            <nav class="nav" aria-label="Sayfa içi">
                <a href="#nasil">Nasıl çalışır</a>
                <a href="#platformlar">Platformlar</a>
                <a href="#sss">Sorular</a>
            </nav>
            <div class="bar__end">
                <a href="{{ route('login') }}" class="btn btn--ghost btn--s">Giriş yap</a>
                @if ($canRegister)<a href="{{ route('register') }}" class="btn btn--primary btn--s">Kayıt ol</a>@endif
            </div>
        </div>
    </header>

    <main>
        <section class="container hero">
            <h1>{{ Setting::get('tagline') }}</h1>
            <p class="lead">Satın aldığın hesabın doğrulama kodunu satıcıya yazıp beklemeden, kendin al. Gece yarısı bile.</p>
            @if (Setting::get('announcement'))
                <p class="notice hero__note">{{ Setting::get('announcement') }}</p>
            @endif
            <div class="hero__actions">
                <a href="{{ $start }}" class="btn btn--primary btn--l">{{ $canRegister ? 'Hemen başla' : 'Giriş yap' }}</a>
                <a href="#nasil" class="btn btn--secondary btn--l">Nasıl çalışır?</a>
            </div>

            {{-- Ürünün kendisi: müşterinin göreceği kod kartı --}}
            <div class="item hero__demo" aria-label="Örnek ekran">
                <div class="item__row">
                    <div>
                        <p class="item__title">Netflix Premium</p>
                        <p class="item__meta">Netflix, sınırsız</p>
                    </div>
                    <span class="btn btn--primary" aria-hidden="true">Mail kodunu al</span>
                </div>
                <div class="code">
                    <p class="code__label">Mail kodu</p>
                    <p class="code__value">4829</p>
                    <div class="code__side"><span class="btn btn--secondary btn--s" aria-hidden="true">Kopyala</span></div>
                    <p class="code__note">Kod az önce gelen mailden alındı.</p>
                </div>
            </div>
            <span class="demo-tag">Örnek ekran</span>
        </section>

        <section class="container section" id="nasil" aria-labelledby="nasil-title">
            <div class="section__head">
                <h2 id="nasil-title">Üç adımda kodun elinde</h2>
                <p>Satıcıya mesaj atmana gerek yok. Platform kod istediğinde buradan alırsın.</p>
            </div>
            <ol class="steps">
                <li>
                    <h3>Hesabını aç</h3>
                    <p>Bir kere kayıt ol. Aldığın bütün hesaplar burada toplanır.</p>
                </li>
                <li>
                    <h3>Anahtarını gir</h3>
                    <p>Satıcının verdiği ürün anahtarını yapıştır. Hesap listene eklenir.</p>
                </li>
                <li>
                    <h3>Kodu al</h3>
                    <p>Platform giriş kodu sorduğunda düğmeye bas, kodu kopyala.</p>
                </li>
            </ol>
        </section>

        <section class="container section" id="platformlar" aria-labelledby="platform-title">
            <div class="section__head">
                <h2 id="platform-title">Desteklenen platformlar</h2>
                <p>Giriş kodunu e-postayla gönderen ya da iki adımlı doğrulama (2FA) kullanan servisler.</p>
            </div>
            <ul class="chips">
                @foreach ($platforms as $platform)
                    <li>{{ $platform }}</li>
                @endforeach
                <li>ve e-postayla kod gönderen diğerleri</li>
            </ul>
        </section>

        <section class="container section" id="sss" aria-labelledby="sss-title">
            <div class="section__head">
                <h2 id="sss-title">Sık sorulanlar</h2>
            </div>
            <div class="faq">
                <details>
                    <summary>Anahtarı nereden alırım?</summary>
                    <p>Hesabı satın aldığında satıcı, hesap bilgileriyle birlikte sana bir ürün anahtarı gönderir. Bu anahtarı giriş yaptıktan sonra ekle.</p>
                </details>
                <details>
                    <summary>Kod gelmedi, ne yapmalıyım?</summary>
                    <p>Önce platformda "kodu tekrar gönder" de, 30 saniye bekleyip "Mail kodunu al"a yeniden bas. Yine gelmezse satıcıyla iletişime geç.</p>
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
                    <summary>Bir anahtarı başkası da kullanabilir mi?</summary>
                    <p>Hayır. Anahtar ilk ekleyen hesaba bağlanır ve başka hesapta çalışmaz.</p>
                </details>
            </div>
        </section>

        <section class="container section">
            <div class="card cta">
                <h2>Anahtarın hazır mı?</h2>
                <p>Giriş yap, anahtarını ekle, kodunu al.</p>
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
