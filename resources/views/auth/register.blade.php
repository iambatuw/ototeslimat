@php
    $min = app()->isProduction() ? 10 : 8;
    // Sunucu bir alanı reddettiyse sayfa o adımdan açılır.
    $start = $errors->has('username') ? 0 : ($errors->has('password') ? 1 : 0);
    $steps = ['Kullanıcı adı', 'Şifre', 'Şifre tekrar'];
@endphp
<x-layouts.guest title="Kayıt ol" :bare="true">
    <x-slot:end>
        <span class="bare-bar__ask"><span class="bare-bar__q">Hesabın var mı?</span> <a href="{{ route('login') }}">Giriş yap</a></span>
    </x-slot:end>

    <div class="signup">
        <form method="POST" action="{{ route('register') }}" class="signup__form" data-signup data-start="{{ $start }}" novalidate>
            @csrf
            <h1 class="visually-hidden">Hesabını aç</h1>

            <div class="signup__progress" data-signup-progress hidden>
                <ol class="steps-cells" role="list">
                    @foreach ($steps as $i => $label)
                        <li class="steps-cells__cell" data-cell="{{ $i }}">
                            <span class="steps-cells__num">{{ $i + 1 }}</span>
                            <svg class="steps-cells__tick" viewBox="0 0 20 20" aria-hidden="true"><path d="m5 10.5 3.2 3.2L15 7"/></svg>
                        </li>
                    @endforeach
                </ol>
                <p class="signup__where" aria-live="polite" data-signup-where></p>
            </div>

            <fieldset class="ask" data-step="0" data-label="{{ $steps[0] }}">
                <legend class="visually-hidden">Adım 1: {{ $steps[0] }}</legend>
                <label for="username" class="ask__q">Kullanıcı adın ne olsun?</label>
                <div class="ask__row">
                    <input id="username" name="username" type="text" class="ask__input" placeholder="ornek_kullanici" value="{{ old('username') }}" autocomplete="username" required minlength="3" maxlength="40"
                        aria-describedby="username-error" @error('username') aria-invalid="true" @enderror>
                    <button type="button" class="btn btn--primary ask__next" data-next>Devam</button>
                </div>
                <p class="ask__hint">Giriş yaparken ve kütüphanende bu kullanıcı adını kullanırsın. E-posta gerekmez.</p>
                <p class="error" id="username-error" data-error>@error('username'){{ $message }}@enderror</p>
                @error('username')
                    <p class="ask__recover">Bu kullanıcı adıyla zaten hesabın varsa <a href="{{ route('login') }}">giriş yap</a>.</p>
                @enderror
            </fieldset>

            <fieldset class="ask" data-step="1" data-label="{{ $steps[1] }}">
                <legend class="visually-hidden">Adım 2: {{ $steps[1] }}</legend>
                <label for="password" class="ask__q">Bir şifre belirle.</label>
                <div class="ask__row ask__row--pass">
                    <input id="password" name="password" type="password" class="ask__input" placeholder="En az {{ $min }} karakter" autocomplete="new-password" required
                        aria-describedby="password-rules password-error" @error('password') aria-invalid="true" @enderror>
                    <button type="button" class="pass__toggle" data-pass-toggle="#password, #password_confirmation" aria-pressed="false">Göster</button>
                    <button type="button" class="btn btn--primary ask__next" data-next>Devam</button>
                </div>
                <ul class="rules" id="password-rules" data-rules="#password" data-min="{{ $min }}">
                    <li data-rule="length">En az {{ $min }} karakter</li>
                    <li data-rule="letter">Bir harf</li>
                    <li data-rule="digit">Bir rakam</li>
                </ul>
                <p class="error" id="password-error" data-error>@error('password'){{ $message }}@enderror</p>
            </fieldset>

            <fieldset class="ask" data-step="2" data-label="{{ $steps[2] }}">
                <legend class="visually-hidden">Adım 3: {{ $steps[2] }}</legend>
                <label for="password_confirmation" class="ask__q">Şifreni bir kez daha yaz.</label>
                <div class="ask__row">
                    <input id="password_confirmation" name="password_confirmation" type="password" class="ask__input" placeholder="Aynı şifre" autocomplete="new-password" required
                        aria-describedby="password-match confirm-error">
                    <button type="submit" class="btn btn--primary ask__next" data-busy-text="Açılıyor">Hesabımı aç</button>
                </div>
                <p class="match" id="password-match" data-match="#password, #password_confirmation" aria-live="polite"></p>
                <p class="error" id="confirm-error" data-error></p>
            </fieldset>

            <button type="button" class="signup__back" data-back hidden>
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
                <span data-back-label>Geri</span>
            </button>
        </form>

        <aside class="signup__side" aria-label="Kayıt özeti">
            <h2 data-signup-greet>Bir dakikada hesabın hazır.</h2>
            <p data-signup-lead>Üç kısa adım. E-posta gerekmez; kullanıcı adınla hesabını hemen aç.</p>
            <dl class="signup__summary">
                <div><dt>Kullanıcı adı</dt><dd data-summary="username">—</dd></div>
                <div><dt>Şifre</dt><dd data-summary="password">—</dd></div>
            </dl>
            <p class="signup__after">Hesabını açınca: anahtarını ekle, hesabın cüzdanına kart olarak gelsin, platform sorduğunda koda bas.</p>
        </aside>
    </div>
</x-layouts.guest>
