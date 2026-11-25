@props(['title', 'flip' => false, 'bare' => false])
@php
    use App\Models\Setting;
    $brand = Setting::brand();
    $logo = Setting::get('logo');
    $favicon = Setting::get('favicon');
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ Setting::get('tagline') }}">
    <title>{{ $title }} | {{ Setting::get('tab_title') ?: $brand }}</title>
    @if ($favicon)<link rel="icon" href="{{ asset($favicon) }}">@endif
    <x-motion />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style nonce="{{ Vite::cspNonce() }}">{!! App\Support\Theme::css() !!}</style>
</head>
<body>
    <div class="ambient" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
    @if ($bare)
        {{-- Sayfa kendi yerleşimini kurar (kayıt: adım adım). Marka ve hareket düğmesi burada. --}}
        <header class="bare-bar">
            <a href="{{ route('landing') }}" class="brand" aria-label="{{ $brand }} ana sayfa">
                @if ($logo)<img src="{{ asset($logo) }}" alt="" height="26">@endif
                <span>{{ $brand }}</span>
            </a>
            <div class="bare-bar__end">
                {{ $end ?? '' }}
                <x-motion-toggle />
            </div>
        </header>
        <main class="bare">{{ $slot }}</main>
    @else
    <main @class(['auth', 'auth--flip' => $flip])>
        <div class="auth__form">
            <x-motion-toggle class="motion-btn--corner" />
            <div class="auth__box enter">
                <a href="{{ route('landing') }}" class="brand" aria-label="{{ $brand }} ana sayfa">
                    @if ($logo)<img src="{{ asset($logo) }}" alt="" height="26">@endif
                    <span>{{ $brand }}</span>
                </a>
                @if (session('status'))
                    <p class="notice" role="status">{{ session('status') }}</p>
                @endif
                {{ $slot }}
            </div>
        </div>

        <aside class="auth__show" aria-label="Ürün örneği">
            <a href="{{ route('landing') }}" class="brand" aria-label="{{ $brand }} ana sayfa">
                @if ($logo)<img src="{{ asset($logo) }}" alt="" height="26">@endif
                <span>{{ $brand }}</span>
            </a>
            @isset($aside)
                {{ $aside }}
            @else
                <x-demo-card :autoplay="true" />
                <div class="auth__pitch">
                    <h2>Kodun bir tık uzağında.</h2>
                    <p>Platform doğrulama kodu istediğinde düğmeye bas. Kod birkaç saniyede ekranında.</p>
                </div>
            @endisset
        </aside>
    </main>
    @endif
</body>
</html>
