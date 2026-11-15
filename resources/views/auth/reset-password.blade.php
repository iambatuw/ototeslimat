<x-layouts.guest title="Yeni şifre">
    <h1>Yeni şifre belirle</h1>
    <p>Bu şifreyle bundan sonra giriş yapacaksın.</p>

    <form method="POST" action="{{ route('password.update') }}" class="stack" data-busy novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="field">
            <label for="email">E-posta</label>
            <input id="email" name="email" type="email" class="input" value="{{ old('email', $request->email) }}" autocomplete="email" required @error('email') aria-invalid="true" @enderror>
            <p class="error">@error('email'){{ $message }}@enderror</p>
        </div>
        <div class="field">
            <label for="password">Yeni şifre</label>
            <input id="password" name="password" type="password" class="input" autocomplete="new-password" required autofocus @error('password') aria-invalid="true" @enderror>
            <p class="error">@error('password'){{ $message }}@enderror</p>
        </div>
        <div class="field">
            <label for="password_confirmation">Yeni şifre tekrar</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" required>
            <p class="error"></p>
        </div>
        <button type="submit" class="btn btn--primary btn--block" data-busy-text="Kaydediliyor">Şifremi kaydet</button>
    </form>
</x-layouts.guest>
