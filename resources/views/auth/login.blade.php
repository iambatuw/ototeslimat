<x-layouts.guest title="Giriş yap">
    <h1>Giriş yap</h1>
    <p>Hesaplarının giriş kodlarını almak için oturum aç.</p>

    <form method="POST" action="{{ route('login') }}" class="stack" data-busy novalidate>
        @csrf
        <div class="field">
            <label for="email">E-posta</label>
            <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" autocomplete="email" inputmode="email" required autofocus @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            <p class="error" id="email-error">@error('email'){{ $message }}@enderror</p>
        </div>
        <div class="field">
            <label for="password">Şifre</label>
            <input id="password" name="password" type="password" class="input" autocomplete="current-password" required @error('password') aria-invalid="true" @enderror>
            <p class="error">@error('password'){{ $message }}@enderror</p>
        </div>
        <label class="check"><input type="checkbox" name="remember" value="1"> Bu cihazda açık kalsın</label>
        <button type="submit" class="btn btn--primary btn--block" data-busy-text="Giriş yapılıyor">Giriş yap</button>
    </form>

    <p class="auth__alt">
        @if (\App\Models\Setting::flag('registration_open'))
            Hesabın yok mu? <a href="{{ route('register') }}">Kayıt ol</a>.
        @endif
        Şifreni mi unuttun? <a href="{{ route('password.request') }}">Yenile</a>.
    </p>
</x-layouts.guest>
