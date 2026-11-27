@php
    use App\Models\Setting;
    $brand = Setting::brand();
    $logo = Setting::get('logo');
    $favicon = Setting::get('favicon');
    $user = auth()->user();
    $canRegister = Setting::flag('registration_open');
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $brand }} oyun kataloğu: satın aldığın oyunun giriş kodunu beklemeden al.">
    <title>Oyunlar | {{ Setting::get('tab_title') ?: $brand }}</title>
    @if ($favicon)<link rel="icon" href="{{ asset($favicon) }}">@endif
    <x-motion />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style nonce="{{ Vite::cspNonce() }}">{!! App\Support\Theme::css() !!}</style>
</head>
<body>
    <div class="ambient" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
    <header class="bar">
        <div class="container"><div class="bar__inner glass">
            <a href="{{ route('landing') }}" class="brand" aria-label="{{ $brand }} ana sayfa">
                @if ($logo)<img src="{{ asset($logo) }}" alt="" height="26">@endif
                <span>{{ $brand }}</span>
            </a>
            <nav class="nav nav--optional" aria-label="Sayfalar">
                <a href="{{ route('landing') }}">Ana sayfa</a>
                <a href="{{ route('games') }}" aria-current="page">Oyunlar</a>
            </nav>
            <div class="bar__end">
                <x-motion-toggle />
                @if ($user)
                    <a href="{{ $user->homePath() }}" class="btn btn--light btn--s">{{ $user->isAdmin() ? 'Panele git' : 'Kodlarıma git' }}</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn--ghost btn--s">Giriş yap</a>
                    @if ($canRegister)<a href="{{ route('register') }}" class="btn btn--light btn--s">Kayıt ol</a>@endif
                @endif
            </div>
        </div></div>
    </header>

    <main class="container games">
        <section class="gbanner" aria-labelledby="games-title">
            <div class="gbanner__text">
                <h1 id="games-title">Oyunlar</h1>
                <p class="lead">Buradaki oyunlardan birini aldıysan girişte istenen kodu {{ $brand }}'ten beklemeden alırsın. Platforma göre süz, oyuna tıkla.</p>
            </div>
            @if ($banner->isNotEmpty())
                <div class="gbanner__art" aria-hidden="true">
                    @foreach ($banner as $item)
                        <img src="{{ $item->coverUrl() }}" alt="" referrerpolicy="no-referrer">
                    @endforeach
                </div>
            @endif
        </section>

        @if ($games->isEmpty())
            <div class="blank">
                <div class="blank__art" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
                <h2>Katalog yakında</h2>
                <p>Satıcı oyunları ekledikçe burada görünecek.</p>
            </div>
        @else
            <div class="gtools glass" data-games-tools>
                <label class="tools__search">
                    <span class="visually-hidden">Oyun ara</span>
                    <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="9" cy="9" r="5.5"/><path d="m13.5 13.5 3 3"/></svg>
                    <input type="search" class="tools__input" placeholder="Oyun adıyla ara" data-games-search autocomplete="off">
                </label>
                @if ($platforms->count() > 1)
                    <div class="tools__chips" role="group" aria-label="Platforma göre süz">
                        <button type="button" class="chipbtn is-on" data-games-filter="" aria-pressed="true">Tümü <span>{{ $games->count() }}</span></button>
                        @foreach ($platforms as $key => $label)
                            <button type="button" class="chipbtn" data-games-filter="{{ $key }}" aria-pressed="false">{{ $label }} <span>{{ $games->where('platform', $key)->count() }}</span></button>
                        @endforeach
                    </div>
                @endif
            </div>

            <p class="games__count" aria-live="polite"><strong data-games-count>{{ $games->count() }}</strong> oyun</p>

            <ul class="gamegrid" role="list" data-games>
                @foreach ($games as $game)
                    <li data-p="{{ $game->platform }}" data-platform="{{ $game->platform }}" data-name="{{ mb_strtolower($game->name) }}">
                        <button type="button" class="gcard" data-game-open
                            data-title="{{ $game->name }}" data-platform-label="{{ $game->platformLabel() }}"
                            data-note="{{ $game->note }}" data-cover="{{ $game->coverUrl() }}" data-mark="{{ $game->platformMark() }}">
                            <span class="gcard__cover">
                                @if ($game->coverUrl())
                                    <img src="{{ $game->coverUrl() }}" alt="" loading="lazy" referrerpolicy="no-referrer">
                                @else
                                    <span class="gcard__blank" aria-hidden="true"><span>{{ $game->platformMark() }}</span>{{ $game->platformLabel() }}</span>
                                @endif
                                @if ($game->featured)<span class="gcard__flag">Öne çıkan</span>@endif
                            </span>
                            <span class="gcard__body">
                                <span class="gcard__name">{{ $game->name }}</span>
                                <span class="gcard__meta">{{ $game->platformLabel() }}</span>
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>
            <p class="games__none" data-games-none hidden>Aramana uyan oyun yok.</p>

            <dialog class="gdialog" data-game-dialog aria-labelledby="gdialog-title">
                <div class="gdialog__cover" data-p="" data-dialog-cover></div>
                <div class="gdialog__body">
                    <p class="gdialog__platform" data-dialog-platform></p>
                    <h2 id="gdialog-title" data-dialog-title></h2>
                    <p class="gdialog__note" data-dialog-note></p>
                    <p class="gdialog__how">Bu oyunu aldıysan satıcının verdiği anahtarı hesabına ekle; giriş kodu istendiğinde Kodlarım sayfasından tek dokunuşla al.</p>
                    <div class="gdialog__do">
                        @if ($user)
                            <a href="{{ $user->homePath() }}" class="btn btn--primary">{{ $user->isAdmin() ? 'Panele git' : 'Kodlarıma git' }}</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn--primary">Giriş yap</a>
                            @if ($canRegister)<a href="{{ route('register') }}" class="btn btn--secondary">Ücretsiz hesap aç</a>@endif
                            @if (Setting::flag('quick_code'))<a href="{{ route('quick') }}" class="btn btn--ghost">Hesap açmadan kodunu al</a>@endif
                        @endif
                    </div>
                </div>
                <form method="dialog"><button class="gdialog__close" aria-label="Kapat"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="m4 4 8 8M12 4l-8 8"/></svg></button></form>
            </dialog>
        @endif
    </main>
</body>
</html>
