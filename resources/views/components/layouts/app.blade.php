@php
    use App\Models\Setting;
    $user = auth()->user();
    $brand = Setting::brand();
    $logo = Setting::get('logo');
    $favicon = Setting::get('favicon');
    // [rota, aktif sayılacak rota deseni, etiket]
    $nav = $user->isAdmin() ? [
        ['admin.dashboard', 'admin.dashboard', 'Panel'],
        ['admin.accounts.index', 'admin.accounts.*', 'Hesaplar'],
        ['admin.keys.index', 'admin.keys.*', 'Anahtarlar'],
        ['admin.customers.index', 'admin.customers.*', 'Müşteriler'],
        ['admin.logs', 'admin.logs', 'Kayıtlar'],
        ['admin.settings', 'admin.settings*', 'Ayarlar'],
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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style nonce="{{ Vite::cspNonce() }}">{!! App\Support\Theme::css() !!}</style>
</head>
<body>
    <header class="bar">
        <div class="bar__inner">
            <a href="{{ url('/') }}" class="wordmark" aria-label="{{ $brand }} ana sayfa">
                @if ($logo)<img src="{{ asset($logo) }}" alt="" class="wordmark__logo" height="28">@endif
                <span>{{ $brand }}</span>
            </a>
            @if ($nav)
                <nav class="nav" aria-label="Yönetim menüsü">
                    @foreach ($nav as [$route, $pattern, $label])
                        <a href="{{ route($route) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                </nav>
            @else
                <span class="nav nav--empty" aria-hidden="true"></span>
            @endif
            <div class="bar__end">
                <a href="{{ route('profile') }}" @if (request()->routeIs('profile')) aria-current="page" @endif>{{ $user->name }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="linkbtn">Çıkış</button>
                </form>
            </div>
        </div>
    </header>

    <main class="container page" id="icerik">
        @if (session('status') && session('status') !== 'password-updated')
            <p class="notice" role="status">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p class="notice notice--error" role="alert">{{ session('error') }}</p>
        @endif

        {{ $slot }}
    </main>

    <footer class="foot">
        <div class="container foot__inner">
            <span>{{ $brand }}</span>
            @if (Setting::get('contact_url'))<a href="{{ Setting::get('contact_url') }}" rel="noopener" target="_blank">Destek</a>@endif
        </div>
    </footer>
</body>
</html>
