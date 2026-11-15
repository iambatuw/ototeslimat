@php use App\Models\Setting; @endphp
<x-layouts.app title="Kod al">
    <section class="hero" aria-labelledby="hero-title">
        <h1 id="hero-title">{{ Setting::get('tagline') ?: 'Kodunu al' }}</h1>
        @if (Setting::get('announcement'))
            <p class="hero__note">{{ Setting::get('announcement') }}</p>
        @endif

        <form method="POST" action="{{ route('redeem') }}" class="keybox" data-busy novalidate>
            @csrf
            <label for="key" class="label">Ürün anahtarı</label>
            <div class="keybox__row">
                <input id="key" name="key" type="text" class="input input--lg input--key" value="{{ old('key') }}"
                    placeholder="{{ config('guard.key_prefix') }}-XXXXXXXX-XXXXXXXX" autocomplete="off" autocapitalize="characters"
                    spellcheck="false" maxlength="64" required data-key-input aria-describedby="key-help"
                    @if ($keys->isEmpty()) autofocus @endif @error('key') aria-invalid="true" @enderror>
                <button type="submit" class="btn btn--primary btn--lg" data-busy-text="Kontrol ediliyor">Anahtarı ekle</button>
            </div>
            <p id="key-help" class="{{ $errors->has('key') ? 'error' : 'hint' }}" @error('key') role="alert" @enderror>
                {{ $errors->first('key') ?: 'Satıcının gönderdiği anahtarı yapıştır. Tire ve boşluk yazmasan da olur.' }}
            </p>
        </form>
    </section>

    @if ($keys->isEmpty())
        <ol class="steps steps--row" aria-label="Nasıl çalışır">
            <li><span><strong>Anahtarı ekle.</strong> Siparişinle gelen anahtarı yukarıdaki alana yapıştır.</span></li>
            <li><span><strong>Hesaba gir.</strong> Satıcının verdiği e-posta ve şifreyle platforma giriş yap.</span></li>
            <li><span><strong>Kodu al.</strong> Platform kod sorduğunda hesabının yanındaki düğmeye bas.</span></li>
        </ol>
    @else
        <section aria-labelledby="mine">
            <h2 id="mine" class="section">Hesapların</h2>
            <ul class="games" role="list">
                @foreach ($keys as $key)
                    @php
                        $account = $key->account;
                        $status = $key->status();
                        $off = in_array($status, ['revoked', 'expired', 'used_up'], true) || $account->trashed() || ! $account->is_active;
                    @endphp
                    <li class="game {{ $off ? 'game--off' : '' }}" data-game @if (session('opened') === $key->id) data-open="true" @endif>
                        <div class="game__row">
                            <div>
                                <h3 class="game__title">{{ $account->title }}</h3>
                                <p class="game__meta">{{ implode(', ', array_filter([
                                    $account->platformLabel(),
                                    $key->max_uses ? $key->usesLeft().' / '.$key->max_uses.' kod hakkı kaldı' : null,
                                    $key->expires_at ? $key->expires_at->format('d.m.Y').' tarihine kadar' : null,
                                    $key->cooldown_minutes ? 'kodlar arası '.$key->cooldown_minutes.' dk' : null,
                                    ! $key->max_uses && ! $key->expires_at ? 'sınırsız' : null,
                                ])) }}</p>
                            </div>
                            @if ($off)
                                <span class="muted small">{{ $account->trashed() || ! $account->is_active ? 'Şu an kod verilmiyor' : $key->statusLabel() }}</span>
                            @else
                                <div class="game__actions">
                                    @if ($account->hasMailbox())
                                        <button type="button" class="btn btn--primary" data-get-code data-url="{{ route('code', [$key, 'mail']) }}" data-idle="Mail kodunu al">Mail kodunu al</button>
                                    @endif
                                    @if ($account->hasTotp())
                                        <button type="button" class="btn {{ $account->hasMailbox() ? 'btn--ghost' : 'btn--primary' }}" data-get-code data-url="{{ route('code', [$key, '2fa']) }}" data-idle="2FA kodunu al">2FA kodunu al</button>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="code" hidden></div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.app>
