@php($min = app()->isProduction() ? 10 : 8)
<x-layouts.guest title="Kayıt ol" :flip="true">
    <h1>Hesabını aç</h1>
    <p>Bir kez kayıt ol; aldığın bütün hesapların kodları tek yerde dursun.</p>

    <form method="POST" action="{{ route('register') }}" class="stack" data-busy novalidate>
        @csrf
        <div class="grid-2">
            <div class="field">
                <label for="name">Adın</label>
                <input id="name" name="name" type="text" class="input" value="{{ old('name') }}" autocomplete="name" required autofocus @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                <p class="error" id="name-error">@error('name'){{ $message }}@enderror</p>
            </div>
            <div class="field">
                <label for="email">E-posta</label>
                <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" autocomplete="email" inputmode="email" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                <p class="error" id="email-error">@error('email'){{ $message }}@enderror</p>
            </div>
        </div>

        <div class="field">
            <label for="password">Şifre</label>
            <div class="pass">
                <input id="password" name="password" type="password" class="input" autocomplete="new-password" required aria-describedby="password-rules" @error('password') aria-invalid="true" @enderror>
                <button type="button" class="pass__toggle" data-pass-toggle="#password, #password_confirmation" aria-pressed="false">Göster</button>
            </div>
            <ul class="rules" id="password-rules" data-rules="#password" data-min="{{ $min }}">
                <li data-rule="length">En az {{ $min }} karakter</li>
                <li data-rule="letter">Bir harf</li>
                <li data-rule="digit">Bir rakam</li>
            </ul>
            <p class="error">@error('password'){{ $message }}@enderror</p>
        </div>

        <div class="field">
            <label for="password_confirmation">Şifre tekrar</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" required aria-describedby="password-match">
            <p class="match" id="password-match" data-match="#password, #password_confirmation" aria-live="polite"></p>
        </div>

        <button type="submit" class="btn btn--primary btn--l btn--block">Hesabımı aç</button>
    </form>

    <p class="auth__switch">Zaten hesabın var mı? <a href="{{ route('login') }}">Giriş yap</a></p>

    <x-slot:aside>
        <ol class="journey">
            <li class="is-now">
                <span class="journey__dot">1</span>
                <div>
                    <h3>Hesabını aç</h3>
                    <p>Şu an buradasın. Ad, e-posta ve bir şifre yeterli.</p>
                </div>
            </li>
            <li>
                <span class="journey__dot">2</span>
                <div>
                    <h3>Ürün anahtarını ekle</h3>
                    <p>Siparişinle birlikte teslim edilen anahtarı bir kez gir.</p>
                    <div class="mini" aria-hidden="true">
                        <div class="mini__field">GK-7H2K9QXD <span class="mini__pill">Ekle</span></div>
                    </div>
                </div>
            </li>
            <li>
                <span class="journey__dot">3</span>
                <div>
                    <h3>Kodunu al</h3>
                    <p>Platform kod istediğinde tek tıkla ekranında.</p>
                    <div class="mini" aria-hidden="true">
                        <div class="mini__cells mini__cells--on"><span>5</span><span>8</span><span>2</span><span>1</span></div>
                    </div>
                </div>
            </li>
        </ol>
        <div class="auth__pitch">
            <h2>Üç adım, sonra hep bir tık.</h2>
        </div>
    </x-slot:aside>
</x-layouts.guest>
