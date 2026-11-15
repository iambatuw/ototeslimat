@php use App\Http\Controllers\Admin\SettingsController; @endphp
<x-layouts.app title="Ayarlar">
    <div class="page-head">
        <div>
            <h1>Ayarlar</h1>
            <p>Mağazanın adı, rengi ve müşterilerin göreceği yazılar.</p>
        </div>
    </div>

    <div class="settings">
        <nav class="settings__nav" aria-label="Ayar bölümleri">
            @foreach (SettingsController::TABS as $key => $label)
                <a href="{{ route('admin.settings', $key) }}" @if ($tab === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <section class="card">
            @if ($tab === 'genel')
                <form method="POST" action="{{ route('admin.settings.update', 'genel') }}" class="stack" data-busy novalidate>
                    @csrf
                    <h2 class="card__title">Mağaza kimliği</h2>
                    <div class="grid-2">
                        <div class="field">
                            <label for="brand_name">Marka adı</label>
                            <input id="brand_name" name="brand_name" type="text" class="input" maxlength="60" value="{{ old('brand_name', $settings['brand_name']) }}" placeholder="{{ config('app.name') }}">
                            <p class="error">@error('brand_name'){{ $message }}@enderror</p>
                        </div>
                        <div class="field">
                            <label for="tab_title">Sekme başlığı</label>
                            <input id="tab_title" name="tab_title" type="text" class="input" maxlength="80" value="{{ old('tab_title', $settings['tab_title']) }}" placeholder="Boşsa marka adı">
                            <p class="error">@error('tab_title'){{ $message }}@enderror</p>
                        </div>
                    </div>
                    <div class="field">
                        <label for="tagline">Karşılama yazısı</label>
                        <input id="tagline" name="tagline" type="text" class="input" maxlength="160" value="{{ old('tagline', $settings['tagline']) }}">
                        <p class="{{ $errors->has('tagline') ? 'error' : 'hint' }}">{{ $errors->first('tagline') ?: 'Müşterinin ana sayfasındaki büyük başlık.' }}</p>
                    </div>
                    <div class="field">
                        <label for="announcement">Duyuru <span class="optional">(isteğe bağlı)</span></label>
                        <input id="announcement" name="announcement" type="text" class="input" maxlength="300" value="{{ old('announcement', $settings['announcement']) }}" placeholder="Örneğin: Netflix kodları 5 dakika gecikmeli gelebilir.">
                        <p class="error">@error('announcement'){{ $message }}@enderror</p>
                    </div>
                    <div class="field">
                        <label for="contact_url">Destek bağlantısı <span class="optional">(isteğe bağlı)</span></label>
                        <input id="contact_url" name="contact_url" type="url" class="input" maxlength="300" value="{{ old('contact_url', $settings['contact_url']) }}" placeholder="https://t.me/kullaniciadi">
                        <p class="{{ $errors->has('contact_url') ? 'error' : 'hint' }}">{{ $errors->first('contact_url') ?: 'Telegram, WhatsApp ya da mağaza sayfası. Sayfanın altında görünür.' }}</p>
                    </div>
                    <div><button type="submit" class="btn btn--primary" data-busy-text="Kaydediliyor">Kaydet</button></div>
                </form>
            @elseif ($tab === 'gorunum')
                <form method="POST" action="{{ route('admin.settings.update', 'gorunum') }}" enctype="multipart/form-data" class="stack" data-busy novalidate>
                    @csrf
                    <h2 class="card__title">Renk ve logo</h2>
                    <div class="field">
                        <label for="theme_color">Tema rengi</label>
                        <div class="color">
                            <input id="theme_color" name="theme_color" type="color" class="color__swatch" value="{{ old('theme_color', $settings['theme_color']) }}" data-color-sync="#theme_color_text">
                            <input id="theme_color_text" type="text" class="input mono color__text" value="{{ old('theme_color', $settings['theme_color']) }}" maxlength="7" aria-label="Tema rengi kodu" data-color-sync="#theme_color">
                        </div>
                        <p class="{{ $errors->has('theme_color') ? 'error' : 'hint' }}">{{ $errors->first('theme_color') ?: 'Düğmeler ve vurgular bu renkte olur. Yazı rengi okunurluğa göre kendiliğinden seçilir.' }}</p>
                    </div>
                    <div class="grid-2">
                        <div class="field">
                            <label for="logo">Logo</label>
                            @if ($settings['logo'])<img src="{{ asset($settings['logo']) }}" alt="Şu anki logo" class="preview" height="40">@endif
                            <input id="logo" name="logo" type="file" class="input" accept="image/png,image/jpeg,image/webp">
                            <p class="{{ $errors->has('logo') ? 'error' : 'hint' }}">{{ $errors->first('logo') ?: 'PNG, JPG ya da WEBP, en fazla 1 MB.' }}</p>
                            @if ($settings['logo'])
                                <input type="hidden" name="remove_logo" value="0">
                                <label class="check"><input type="checkbox" name="remove_logo" value="1"> Logoyu kaldır</label>
                            @endif
                        </div>
                        <div class="field">
                            <label for="favicon">Sekme simgesi</label>
                            @if ($settings['favicon'])<img src="{{ asset($settings['favicon']) }}" alt="Şu anki sekme simgesi" class="preview" height="32" width="32">@endif
                            <input id="favicon" name="favicon" type="file" class="input" accept="image/png,image/x-icon,.ico">
                            <p class="{{ $errors->has('favicon') ? 'error' : 'hint' }}">{{ $errors->first('favicon') ?: 'Kare PNG ya da ICO.' }}</p>
                        </div>
                    </div>
                    <div><button type="submit" class="btn btn--primary" data-busy-text="Kaydediliyor">Görünümü kaydet</button></div>
                </form>
            @else
                <form method="POST" action="{{ route('admin.settings.update', 'ozellikler') }}" class="stack" data-busy novalidate>
                    @csrf
                    <h2 class="card__title">Özellikler</h2>
                    <div class="switch-row">
                        <div>
                            <p class="cell-title">Yeni müşteri kaydı</p>
                            <p class="muted small">Kapalıyken kayıt sayfası açılmaz; yalnızca var olan müşteriler girer.</p>
                        </div>
                        <input type="hidden" name="registration_open" value="0">
                        <input type="checkbox" class="switch" name="registration_open" value="1" aria-label="Yeni müşteri kaydı" @checked($settings['registration_open'])>
                    </div>
                    <div><button type="submit" class="btn btn--primary" data-busy-text="Kaydediliyor">Kaydet</button></div>
                </form>
            @endif
        </section>
    </div>
</x-layouts.app>
