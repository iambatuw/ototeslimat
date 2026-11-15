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
                        $status = $key->status();
                        $blocked = in_array($status, ['revoked', 'expired', 'used_up'], true) ? $key->statusLabel() : null;
                        $rules = [
                            $key->max_uses ? $key->usesLeft().' / '.$key->max_uses.' kod hakkı kaldı' : null,
                            $key->expires_at ? $key->expires_at->format('d.m.Y').' tarihine kadar' : null,
                            $key->cooldown_minutes ? 'kodlar arası '.$key->cooldown_minutes.' dk' : null,
                            ! $key->max_uses && ! $key->expires_at ? 'sınırsız' : null,
                        ];
                    @endphp

                    @if (! $key->isPackage())
                        @include('customer.partials.account-row', ['account' => $key->account, 'meta' => [$key->account->platformLabel(), ...$rules]])
                    @else
                        @php
                            $package = $key->package;
                            $picked = $key->selections;
                            $left = max(0, $package->pick_count - $picked->count());
                            $packageDown = $package->trashed() || ! $package->is_active;
                            $choices = $package->accounts->reject(fn ($a) => $picked->contains('id', $a->id));
                        @endphp
                        <li class="pkg">
                            <div class="pkg__head">
                                <h3 class="game__title">{{ $package->name }}</h3>
                                <p class="game__meta">{{ implode(', ', array_filter([
                                    $picked->count().' / '.$package->pick_count.' hesap seçildi',
                                    ...$rules,
                                ])) }}</p>
                                @if ($package->description)<p class="pkg__desc">{{ $package->description }}</p>@endif
                            </div>

                            @if ($picked->isNotEmpty())
                                <ul class="games games--nested" role="list" aria-label="Seçtiğin hesaplar">
                                    @foreach ($picked as $account)
                                        @include('customer.partials.account-row', ['meta' => [$account->platformLabel()], 'blocked' => $blocked ?? ($packageDown ? 'Paket kapatıldı' : null)])
                                    @endforeach
                                </ul>
                            @endif

                            @if ($left > 0 && ! $blocked && ! $packageDown)
                                <div class="pkg__pick">
                                    <p class="label">{{ $left }} hesap daha seçebilirsin. Seçim kalıcıdır.</p>
                                    <ul class="choices" role="list">
                                        @foreach ($choices as $account)
                                            @php($unavailable = $account->trashed() || ! $account->is_active)
                                            <li>
                                                <form method="POST" action="{{ route('pick', $key) }}" data-confirm="{{ $account->title }} seçilsin mi? Sonradan değiştiremezsin.">
                                                    @csrf
                                                    <input type="hidden" name="account_id" value="{{ $account->id }}">
                                                    <button type="submit" class="choice" @disabled($unavailable)>
                                                        <span class="choice__title">{{ $account->title }}</span>
                                                        <span class="choice__sub">{{ $unavailable ? 'Şu an seçilemiyor' : $account->platformLabel() }}</span>
                                                    </button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </li>
                    @endif
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.app>
