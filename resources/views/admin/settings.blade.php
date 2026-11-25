@php use App\Http\Controllers\Admin\SettingsController; @endphp
<x-layouts.app title="Ayarlar">
    <header class="head">
        <div>
            <h1>Ayarlar</h1>
            <p>Mağazanın adı, rengi ve müşterilerin göreceği yazılar.</p>
        </div>
    </header>

    <div class="toolbar">
        <nav class="segmented" aria-label="Ayar bölümleri">
            @foreach (SettingsController::TABS as $key => $label)
                <a href="{{ route('admin.settings', $key) }}" @if ($tab === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    </div>

    <div class="settings">
        <section class="panel panel--pad">
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
                    <div class="theme-preview" data-theme-preview aria-hidden="true">
                        <span class="btn btn--primary btn--s">Mail kodunu al</span>
                        <span class="cells"><span class="cell is-in"><span>4</span></span><span class="cell is-in"><span>7</span></span><span class="cell is-in"><span>2</span></span><span class="cell is-in"><span>9</span></span></span>
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
                    <div class="switch-row">
                        <div>
                            <p class="cell-title">Hızlı onay sayfası</p>
                            <p class="muted small">Müşteri hesap açmadan <a href="{{ route('quick') }}" target="_blank" rel="noopener">{{ route('quick') }}</a> adresinde anahtarını yazıp kodunu alır. Hızlı onayda kullanılan anahtar bir hesaba eklenemez. Paket anahtarları burada çalışmaz.</p>
                        </div>
                        <input type="hidden" name="quick_code" value="0">
                        <input type="checkbox" class="switch" name="quick_code" value="1" aria-label="Hızlı onay sayfası" @checked($settings['quick_code'])>
                    </div>
                    <div class="switch-row">
                        <div>
                            <p class="cell-title">Sistemin “hareketi azalt” ayarına uy</p>
                            <p class="muted small">Açıkken, cihazında animasyonları kapatmış ziyaretçide site hareketsiz başlar. Kapalıyken animasyonlar herkeste oynar; ziyaretçi sayfanın altındaki düğmeyle durdurabilir.</p>
                        </div>
                        <input type="hidden" name="respect_reduced_motion" value="0">
                        <input type="checkbox" class="switch" name="respect_reduced_motion" value="1" aria-label="Sistemin hareketi azalt ayarına uy" @checked($settings['respect_reduced_motion'])>
                    </div>
                    <div class="switch-row">
                        <div>
                            <p class="cell-title">Günlük özet e-postası</p>
                            <p class="muted small">Her sabah yöneticilere dünün özeti gider: verilen kod, hatalar, bozuk hesaplar, azalan stok. Sunucuda zamanlanmış görev ve e-posta ayarı gerekir.</p>
                        </div>
                        <input type="hidden" name="daily_summary" value="0">
                        <input type="checkbox" class="switch" name="daily_summary" value="1" aria-label="Günlük özet e-postası" @checked($settings['daily_summary'])>
                    </div>
                    <div class="field">
                        <label for="low_stock_threshold">Stok uyarı eşiği</label>
                        <input id="low_stock_threshold" name="low_stock_threshold" type="number" class="input" min="0" max="1000" inputmode="numeric" value="{{ old('low_stock_threshold', $settings['low_stock_threshold']) }}" @error('low_stock_threshold') aria-invalid="true" @enderror>
                        <p class="{{ $errors->has('low_stock_threshold') ? 'error' : 'hint' }}">{{ $errors->first('low_stock_threshold') ?: 'Bir hesabın ya da paketin satılmamış anahtarı bu sayıya düşünce panelde uyarı çıkar.' }}</p>
                    </div>
                    <div><button type="submit" class="btn btn--primary" data-busy-text="Kaydediliyor">Kaydet</button></div>
                </form>
            @endif
        </section>
    </div>
</x-layouts.app>
