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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style nonce="{{ Vite::cspNonce() }}">{!! App\Support\Theme::css() !!}</style>
</head>
<body>
    <main class="auth">
        <div class="auth__box">
            <a href="{{ url('/') }}" class="wordmark" aria-label="{{ $brand }} ana sayfa">
                @if ($logo)<img src="{{ asset($logo) }}" alt="" class="wordmark__logo" height="28">@endif
                <span>{{ $brand }}</span>
            </a>
            @if (session('status'))
                <p class="notice" role="status">{{ session('status') }}</p>
            @endif
            {{ $slot }}
        </div>
    </main>
</body>
</html>
