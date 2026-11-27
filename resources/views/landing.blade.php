@php
    use App\Models\Setting;
    $brand = Setting::brand();
    $logo = Setting::get('logo');
    $favicon = Setting::get('favicon');
    $canRegister = Setting::flag('registration_open');
    $user = auth()->user();
    $start = $user ? $user->homePath() : ($canRegister ? route('register') : route('login'));
    $startLabel = $user ? ($user->isAdmin() ? 'Panele git' : 'Kodlarıma git') : null;
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ Setting::get('tagline') }}">
    <title>{{ Setting::get('tab_title') ?: $brand }}</title>
    @if ($favicon)<link rel="icon" href="{{ asset($favicon) }}">@endif
    <x-motion />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style nonce="{{ Vite::cspNonce() }}">{!! App\Support\Theme::css() !!} .marquee { --marquee-count: {{ count($platforms) }}; }</style>
</head>
<body>
    <div class="ambient" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
    <div class="progress" aria-hidden="true"></div>
    <header class="bar">
        <div class="container"><div class="bar__inner glass">
            <a href="{{ route('landing') }}" class="brand" aria-label="{{ $brand }} ana sayfa">
                @if ($logo)<img src="{{ asset($logo) }}" alt="" height="26">@endif
                <span>{{ $brand }}</span>
            </a>
            <nav class="nav nav--optional" aria-label="Sayfa içi">
                <a href="#nasil">Nasıl çalışır</a>
                <a href="#sss">Sorular</a>
            </nav>
            <div class="bar__end">
                @if (Setting::flag('games_catalog'))<a href="{{ route('games') }}" class="btn btn--ghost btn--s bar__games">Oyunlar</a>@endif
                <x-motion-toggle />
                @if ($user)
                    <a href="{{ $start }}" class="btn btn--light btn--s">{{ $startLabel }}</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn--ghost btn--s">Giriş yap</a>
                    @if ($canRegister)<a href="{{ route('register') }}" class="btn btn--light btn--s">Kayıt ol</a>@endif
                @endif
            </div>
        </div></div>
    </header>

    <main>
        <section class="container hero">
            <div class="enter">
                <h1>{{ Setting::get('tagline') }}</h1>
                <p class="lead">Satın aldığın hesaba girerken istenen doğrulama kodu için satıcıya yazıp cevap bekleme. Gece yarısı bile tek tıkla ekranında.</p>
                @if (Setting::get('announcement'))
                    <p class="notice hero__note">{{ Setting::get('announcement') }}</p>
                @endif
                <div class="hero__actions">
                    <a href="{{ $start }}" class="btn btn--primary btn--l">{{ $startLabel ?? ($canRegister ? 'Hemen başla' : 'Giriş yap') }}</a>
                    @if (Setting::flag('quick_code'))
                        <a href="{{ route('quick') }}" class="btn btn--ghost btn--l">Hesap açmadan kodunu al</a>
                    @else
                        <a href="#nasil" class="btn btn--ghost btn--l">Nasıl çalışır?</a>
                    @endif
                </div>
            </div>
            <div class="hero__try enter">
                <x-demo-card :autoplay="true" />
            </div>
        </section>

        <section class="container section split" id="nasil" aria-labelledby="nasil-title">
            <div class="split__head reveal">
                <h2 id="nasil-title">Üç adımda kodun elinde.</h2>
                <p>Satıcıya mesaj atıp cevap beklemek yok.</p>
                <a href="{{ $start }}" class="btn btn--primary btn--l">{{ $startLabel ?? ($canRegister ? 'Kayıt ol' : 'Giriş yap') }}</a>
            </div>
            <ol class="steps">
                <li class="step reveal">
                    <div>
                        <h3>Anahtarını ekle</h3>
                        <p>Sipariş verdiğinde sana bir ürün anahtarı teslim edilir. Kayıt ol ve anahtarı hesabına bir kez ekle. Satın aldığın hesap listende görünür.</p>
                    </div>
                    <div class="mini" aria-hidden="true">
                        <span>Ürün anahtarı</span>
                        <div class="mini__field">GK-7H2K9QXD <span class="mini__pill">Ekle</span></div>
                    </div>
                </li>
                <li class="step reveal">
                    <div>
                        <h3>Platforma giriş yap</h3>
                        <p>Sana verilen e-posta ve şifreyle Netflix, ChatGPT ya da hangi platformu aldıysan ona gir. Platform senden bir doğrulama kodu isteyecek.</p>
                    </div>
                    <div class="mini" aria-hidden="true">
                        <strong>Netflix</strong>
                        <span>Mailine gönderdiğimiz kodu gir</span>
                        <div class="mini__cells"><span></span><span></span><span></span><span></span></div>
                    </div>
                </li>
                <li class="step reveal">
                    <div>
                        <h3>Kodunu tek tıkla al</h3>
                        <p>Listendeki hesabın yanında duran düğmeye bas. Maile gelen kod ya da 2FA kodu birkaç saniyede ekranda. Kopyala, platforma yapıştır.</p>
                    </div>
                    <div class="mini" aria-hidden="true">
                        <span>Mail kodu</span>
                        <div class="mini__cells mini__cells--on"><span>5</span><span>8</span><span>2</span><span>1</span></div>
                    </div>
                </li>
            </ol>
        </section>

        <section class="section reveal" aria-labelledby="platform-title">
            <div class="container"><h2 class="platforms" id="platform-title">Aldığın platform hangisiyse, <span>kodu burada.</span></h2></div>
            <div class="marquee">
                @foreach ([false, true] as $copy)
                    <ul class="marquee__row" @if ($copy) aria-hidden="true" @endif>
                        @foreach ($platforms as $platform)<li>{{ $platform }}</li>@endforeach
                    </ul>
                @endforeach
            </div>
        </section>

        <section class="container section split" id="sss" aria-labelledby="sss-title">
            <div class="split__head reveal">
                <h2 id="sss-title">Sık sorulanlar</h2>
                @if (Setting::get('contact_url'))
                    <p>Cevabını bulamadıysan <a href="{{ Setting::get('contact_url') }}" rel="noopener" target="_blank">destekle iletişime geç</a>.</p>
                @endif
            </div>
            <div class="faq reveal">
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

    </main>

</body>
</html>
