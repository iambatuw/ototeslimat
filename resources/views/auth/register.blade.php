@php
    $min = app()->isProduction() ? 10 : 8;
    // Sunucu bir alanı reddettiyse sayfa o adımdan açılır.
    $start = $errors->has('name') ? 0 : ($errors->has('email') ? 1 : ($errors->has('password') ? 2 : 0));
    $steps = ['Adın', 'E-posta', 'Şifre', 'Şifre tekrar'];
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
                <label for="name" class="ask__q">Adın ne?</label>
                <div class="ask__row">
                    <input id="name" name="name" type="text" class="ask__input" placeholder="Adın ve soyadın" value="{{ old('name') }}" autocomplete="name" required maxlength="255"
                        aria-describedby="name-error" @error('name') aria-invalid="true" @enderror>
                    <button type="button" class="btn btn--primary ask__next" data-next>Devam</button>
                </div>
                <p class="ask__hint">Kodlarım sayfasında seni bu adla karşılarız.</p>
                <p class="error" id="name-error" data-error>@error('name'){{ $message }}@enderror</p>
            </fieldset>

            <fieldset class="ask" data-step="1" data-label="{{ $steps[1] }}">
                <legend class="visually-hidden">Adım 2: {{ $steps[1] }}</legend>
                <label for="email" class="ask__q">E-posta adresin?</label>
                <div class="ask__row">
                    <input id="email" name="email" type="email" class="ask__input" placeholder="ornek@posta.com" value="{{ old('email') }}" autocomplete="email" inputmode="email" required maxlength="255"
                        aria-describedby="email-hint email-error" @error('email') aria-invalid="true" @enderror>
                    <button type="button" class="btn btn--primary ask__next" data-next>Devam</button>
                </div>
                <p class="ask__hint" id="email-hint">Giriş yaparken ve şifreni unutursan bu adresi kullanırsın.</p>
                <p class="error" id="email-error" data-error>@error('email'){{ $message }}@enderror</p>
                @error('email')
                    <p class="ask__recover">Bu adresle zaten hesabın varsa <a href="{{ route('login') }}">giriş yap</a> ya da <a href="{{ route('password.request') }}">şifreni sıfırla</a>.</p>
                @enderror
            </fieldset>

            <fieldset class="ask" data-step="2" data-label="{{ $steps[2] }}">
                <legend class="visually-hidden">Adım 3: {{ $steps[2] }}</legend>
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

            <fieldset class="ask" data-step="3" data-label="{{ $steps[3] }}">
                <legend class="visually-hidden">Adım 4: {{ $steps[3] }}</legend>
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
            <p data-signup-lead>Dört kısa soru. Sonra sana teslim edilen anahtarı ekleyip kodunu alırsın.</p>
            <dl class="signup__summary">
                <div><dt>Ad</dt><dd data-summary="name">—</dd></div>
                <div><dt>E-posta</dt><dd data-summary="email">—</dd></div>
                <div><dt>Şifre</dt><dd data-summary="password">—</dd></div>
            </dl>
            <p class="signup__after">Hesabını açınca: anahtarını ekle, hesabın cüzdanına kart olarak gelsin, platform sorduğunda koda bas.</p>
        </aside>
    </div>
</x-layouts.guest>
