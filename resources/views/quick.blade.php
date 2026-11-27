<x-layouts.guest title="Hızlı onay" :flip="true">
    @if (! $key)
        <h1>Kodunu al</h1>
        <p>Hesap açmana gerek yok. Sana verilen anahtarı yaz, doğrulama kodu bu sayfaya gelsin.</p>

        <form method="POST" action="{{ route('quick.open') }}" class="stack" data-busy novalidate>
            @csrf
            <div class="field">
                <label for="key">Anahtar</label>
                <input id="key" name="key" type="text" class="input mono" value="{{ old('key') }}" placeholder="{{ config('guard.key_prefix') }}-XXXXXXXX-XXXXXXXX"
                    autocomplete="off" autocapitalize="characters" spellcheck="false" required autofocus data-key-input @error('key') aria-invalid="true" aria-describedby="key-error" @enderror>
                <p class="error" id="key-error">@error('key'){{ $message }}@enderror</p>
            </div>
            <button type="submit" class="btn btn--primary btn--l btn--block" data-busy-text="Kontrol ediliyor">Devam et</button>
        </form>

        <ol class="quick-steps" role="list">
            <li><span><strong>Hesaba giriş yap.</strong> Sana verilen bilgilerle platformun kendi sitesinde ya da uygulamasında oturum aç.</span></li>
            <li><span><strong>Kodu iste.</strong> Platform doğrulama kodu sorduğunda bu sayfada kodu al düğmesine bas.</span></li>
            <li><span><strong>Bekle.</strong> Mail kodu gelene kadar sayfa kendiliğinden bakar; kod düşünce burada görünür.</span></li>
        </ol>

        <div class="auth__alt">
            <span>Birden çok anahtarın mı var?</span>
            <a href="{{ route('login') }}" class="btn btn--secondary btn--block">Hesabınla giriş yap</a>
        </div>
    @else
        @php
            $blocked = in_array($key->status(), ['revoked', 'expired', 'used_up'], true) ? $key->statusLabel() : null;
            $rules = [
                $key->max_uses ? $key->usesLeft().' kod hakkın kaldı' : null,
                $key->expires_at ? $key->expires_at->format('d.m.Y').' tarihine kadar' : null,
                $key->periodLabel() ? $key->periodLabel().($key->periodResetsAt() ? ', yeni hak '.$key->periodResetsAt()->format('d.m H:i') : '') : null,
                $key->bonus_uses ? 'satıcıdan '.$key->bonus_uses.' ek hak' : null,
                $key->cooldown_minutes ? 'kodlar arası '.$key->cooldown_minutes.' dk' : null,
            ];
        @endphp
        <h1>{{ $account->platformLabel() }} kodun</h1>
        <p>Platformda giriş yap, kod istediğinde aşağıdaki düğmeye bas. Bu sayfayı kapatma.</p>

        <div class="acct spot quick-acct">
            @include('customer.partials.account-row', [
                'meta' => [$account->platformLabel(), ...$rules],
                'blocked' => $blocked,
                'urlFor' => fn (string $type) => route('quick.code', $type),
            ])
        </div>

        @if ($account->customer_note)
            <p class="notice"><strong>Satıcının notu:</strong> {{ $account->customer_note }}</p>
        @endif

        <form method="POST" action="{{ route('quick.close') }}" class="quick-close">
            @csrf
            <button type="submit" class="btn btn--ghost btn--s">Başka anahtar gir</button>
        </form>
    @endif
</x-layouts.guest>
