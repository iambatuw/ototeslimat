<x-layouts.guest title="Şifre yenile">
    <h1>Şifreni yenile</h1>
    <p>Kayıtlı e-posta adresini yaz, yenileme bağlantısını gönderelim.</p>

    <form method="POST" action="{{ route('password.email') }}" class="stack" data-busy novalidate>
        @csrf
        <div class="field">
            <label for="email">E-posta</label>
            <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" autocomplete="email" inputmode="email" required autofocus @error('email') aria-invalid="true" @enderror>
            <p class="error">@error('email'){{ $message }}@enderror</p>
        </div>
        <button type="submit" class="btn btn--primary btn--block" data-busy-text="Gönderiliyor">Bağlantıyı gönder</button>
    </form>

    <p class="auth__alt"><a href="{{ route('login') }}">Girişe dön</a></p>
</x-layouts.guest>
