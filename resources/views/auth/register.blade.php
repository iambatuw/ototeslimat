<x-layouts.guest title="Kayıt ol">
    <h1>Kayıt ol</h1>
    <p>Hesabını aç, satıcından aldığın anahtarı gir, kodlarını buradan al.</p>

    <form method="POST" action="{{ route('register') }}" class="stack" data-busy novalidate>
        @csrf
        <div class="field">
            <label for="name">Adın</label>
            <input id="name" name="name" type="text" class="input" value="{{ old('name') }}" autocomplete="name" required autofocus @error('name') aria-invalid="true" @enderror>
            <p class="error">@error('name'){{ $message }}@enderror</p>
        </div>
        <div class="field">
            <label for="email">E-posta</label>
            <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" autocomplete="email" inputmode="email" required @error('email') aria-invalid="true" @enderror>
            <p class="error">@error('email'){{ $message }}@enderror</p>
        </div>
        <div class="field">
            <label for="password">Şifre</label>
            <input id="password" name="password" type="password" class="input" autocomplete="new-password" required aria-describedby="password-hint" @error('password') aria-invalid="true" @enderror>
            <p class="{{ $errors->has('password') ? 'error' : 'hint' }}" id="password-hint">{{ $errors->first('password') ?: 'En az 8 karakter, harf ve rakam birlikte.' }}</p>
        </div>
        <div class="field">
            <label for="password_confirmation">Şifre tekrar</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" required>
            <p class="error"></p>
        </div>
        <button type="submit" class="btn btn--primary btn--block" data-busy-text="Hesap açılıyor">Hesabımı aç</button>
    </form>

    <p class="auth__alt">Zaten hesabın var mı? <a href="{{ route('login') }}">Giriş yap</a>.</p>
</x-layouts.guest>
