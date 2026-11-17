@props(['title'])
@php
    use App\Models\Setting;
    $user = auth()->user();
    $brand = Setting::brand();
    $logo = Setting::get('logo');
    $favicon = Setting::get('favicon');
    $initial = mb_strtoupper(mb_substr($user->name, 0, 1));
    // [rota, aktif sayılacak rota deseni, etiket, simge]
    $nav = $user->isAdmin() ? [
        ['admin.dashboard', 'admin.dashboard', 'Panel', '<rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="5" rx="2"/><rect x="13" y="11" width="8" height="10" rx="2"/><rect x="3" y="14" width="8" height="7" rx="2"/>'],
        ['admin.accounts.index', 'admin.accounts.*|admin.packages.*', 'Hesaplar', '<rect x="3" y="4" width="18" height="13" rx="3"/><path d="M8 21h8M12 17v4"/>'],
        ['admin.keys.index', 'admin.keys.*', 'Anahtarlar', '<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9M16 7l3 3M13.5 9.5l2 2"/>'],
        ['admin.customers.index', 'admin.customers.*|admin.logs', 'Müşteriler', '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.4 3.2-5.5 6.5-5.5s5.9 2.1 6.5 5.5M16 4.6a3.5 3.5 0 0 1 0 6.8M18.5 14.9c1.6.9 2.7 2.6 3 5.1"/>'],
        ['admin.settings', 'admin.settings*', 'Ayarlar', '<path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/>'],
    ] : [];
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ Setting::get('tagline') }}">
    <meta name="robots" content="noindex">
    <title>{{ $title }} | {{ Setting::get('tab_title') ?: $brand }}</title>
    @if ($favicon)<link rel="icon" href="{{ asset($favicon) }}">@endif
    <x-motion />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style nonce="{{ Vite::cspNonce() }}">{!! App\Support\Theme::css() !!}</style>
</head>
<body @class(['has-side' => $nav])>
    <div class="ambient ambient--calm" aria-hidden="true"><i></i><i></i><i></i><i></i></div>

    @if ($nav)
        {{-- Yönetim: solda yüzen cam menü; telefonda alt sekme çubuğuna döner. --}}
        <aside class="side glass">
            <a href="{{ $user->homePath() }}" class="brand" aria-label="{{ $brand }}">
                @if ($logo)<img src="{{ asset($logo) }}" alt="" height="28">@endif
                <span>{{ $brand }}</span>
            </a>
            <form method="GET" action="{{ route('admin.search') }}" class="side__search" role="search">
                <label for="side-q" class="visually-hidden">Ara</label>
                <input id="side-q" name="q" type="search" class="input" placeholder="Ara" value="{{ request()->routeIs('admin.search') ? request('q') : '' }}">
            </form>
            <nav class="side__nav" aria-label="Yönetim">
                @foreach ($nav as [$route, $pattern, $label, $icon])
                    <a href="{{ route($route) }}" @if (request()->routeIs(...explode('|', $pattern))) aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" aria-hidden="true">{!! $icon !!}</svg>
                        <span>{{ $label }}</span>
                    </a>
                @endforeach
            </nav>
            <div class="side__end">
                <div class="side__who">
                    <a href="{{ route('profile') }}" class="who" @if (request()->routeIs('profile')) aria-current="page" @endif>
                        <span class="avatar" aria-hidden="true">{{ $initial }}</span>
                        <span class="who__name">{{ $user->name }}</span>
                    </a>
                    <x-motion-toggle />
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn--secondary btn--s btn--block">Çıkış yap</button>
                </form>
            </div>
        </aside>
    @else
        <header class="bar">
            <div class="container"><div class="bar__inner bar__inner--plain glass">
                <a href="{{ $user->homePath() }}" class="brand" aria-label="{{ $brand }}">
                    @if ($logo)<img src="{{ asset($logo) }}" alt="" height="28">@endif
                    <span>{{ $brand }}</span>
                </a>
                <div class="bar__end">
                    <x-motion-toggle />
                    <a href="{{ route('profile') }}" class="who who--bar">
                        <span class="avatar" aria-hidden="true">{{ $initial }}</span>
                        <span class="who__name">{{ $user->name }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn--secondary btn--s">Çıkış</button>
                    </form>
                </div>
            </div></div>
        </header>
    @endif

    <div class="main-col">
        @if ($nav)
            <div class="topline container">
                <a href="{{ $user->homePath() }}" class="brand">{{ $brand }}</a>
                <div class="bar__end">
                    <x-motion-toggle />
                    <a href="{{ route('profile') }}" class="who who--bar" aria-label="Hesabım"><span class="avatar" aria-hidden="true">{{ $initial }}</span></a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn--secondary btn--s">Çıkış</button>
                    </form>
                </div>
            </div>
        @endif

        <main class="container page enter">
            @if (session('status') && session('status') !== 'password-updated')
                <p class="notice" role="status">{{ session('status') }}</p>
            @endif
            @if (session('error'))
                <p class="notice notice--error" role="alert">{{ session('error') }}</p>
            @endif

            {{ $slot }}
        </main>

    </div>
</body>
</html>
