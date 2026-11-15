<x-layouts.app title="Hesabım">
    <div class="page-head">
        <div>
            <h1>Hesabım</h1>
            <p>{{ $user->name }}, {{ $user->email }}</p>
        </div>
    </div>

    @if (session('status') === 'password-updated')
        <p class="notice" role="status">Şifren değişti.</p>
    @endif

    <form method="POST" action="{{ route('user-password.update') }}" class="card stack form-page" data-busy novalidate>
        @csrf
        @method('PUT')
        <h2 class="card__title">Şifreyi değiştir</h2>
        <div class="field">
            <label for="current_password">Mevcut şifre</label>
            <input id="current_password" name="current_password" type="password" class="input" autocomplete="current-password" required @error('current_password', 'updatePassword') aria-invalid="true" @enderror>
            <p class="error">@error('current_password', 'updatePassword'){{ $message }}@enderror</p>
        </div>
        <div class="grid-2">
            <div class="field">
                <label for="password">Yeni şifre</label>
                <input id="password" name="password" type="password" class="input" autocomplete="new-password" required @error('password', 'updatePassword') aria-invalid="true" @enderror>
                <p class="error">@error('password', 'updatePassword'){{ $message }}@enderror</p>
            </div>
            <div class="field">
                <label for="password_confirmation">Yeni şifre tekrar</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" required>
                <p class="error"></p>
            </div>
        </div>
        <div><button type="submit" class="btn btn--primary" data-busy-text="Kaydediliyor">Şifreyi kaydet</button></div>
    </form>
</x-layouts.app>
