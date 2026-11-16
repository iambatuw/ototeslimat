<x-layouts.guest title="Giriş yap">
    <h1>Tekrar hoş geldin</h1>
    <p>Kodlarını almak için hesabına giriş yap.</p>

    <form method="POST" action="{{ route('login') }}" class="stack" data-busy novalidate>
        @csrf
        <div class="field">
            <label for="email">E-posta</label>
            <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" autocomplete="email" inputmode="email" required autofocus @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            <p class="error" id="email-error">@error('email'){{ $message }}@enderror</p>
        </div>
        <div class="field">
            <label for="password">Şifre</label>
            <div class="pass">
                <input id="password" name="password" type="password" class="input" autocomplete="current-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                <button type="button" class="pass__toggle" data-pass-toggle="#password" aria-pressed="false">Göster</button>
            </div>
            <p class="error" id="password-error">@error('password'){{ $message }}@enderror</p>
        </div>
        <div class="auth__row">
            <label class="check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Bu cihazda açık kalsın</label>
            <a href="{{ route('password.request') }}">Şifremi unuttum</a>
        </div>
        <button type="submit" class="btn btn--primary btn--l btn--block">Giriş yap</button>
    </form>

    @if (\App\Models\Setting::flag('registration_open'))
        <div class="auth__alt">
            <span>Henüz hesabın yok mu?</span>
            <a href="{{ route('register') }}" class="btn btn--secondary btn--block">Yeni hesap aç</a>
        </div>
    @endif
</x-layouts.guest>
