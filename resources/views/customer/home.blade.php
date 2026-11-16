@php
    use App\Models\Setting;
    $firstName = explode(' ', trim(auth()->user()->name))[0];
    $contact = Setting::get('contact_url');
@endphp
<x-layouts.app title="Kodlarım">
    <header class="head">
        <div>
            <h1 class="words">Merhaba, {{ $firstName }}</h1>
            <p>Platform senden kod istediğinde hesabının düğmesine bas. Kod birkaç saniyede burada.</p>
        </div>
    </header>

    @if (Setting::get('announcement'))
        <p class="notice">{{ Setting::get('announcement') }}</p>
    @endif

    <div @class(['desk', 'desk--empty' => $keys->isEmpty()])>
        <div class="desk__main">
            @if ($keys->isEmpty())
                <div class="blank">
                    <div class="blank__art" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
                    <h2>İlk hesabını ekle</h2>
                    <p>Siparişinle birlikte sana teslim edilen ürün anahtarını “Anahtar ekle” kutusuna yapıştır. Hesabın burada görünür.</p>
                </div>
            @else
                <ul class="accts stagger" role="list">
                    @foreach ($keys as $key)
                        @php
                            $status = $key->status();
                            $blocked = in_array($status, ['revoked', 'expired', 'used_up'], true) ? $key->statusLabel() : null;
                            $rules = [
                                $key->max_uses ? $key->usesLeft().' kod hakkın kaldı' : null,
                                $key->expires_at ? $key->expires_at->format('d.m.Y').' tarihine kadar' : null,
                                $key->cooldown_minutes ? 'kodlar arası '.$key->cooldown_minutes.' dk' : null,
                            ];
                        @endphp

                        @if (! $key->isPackage())
                            <li class="acct spot">
                                @include('customer.partials.account-row', ['account' => $key->account, 'meta' => [$key->account->platformLabel(), ...$rules]])
                            </li>
                        @else
                            @php
                                $package = $key->package;
                                $picked = $key->selections;
                                $left = max(0, $package->pick_count - $picked->count());
                                $packageDown = $package->trashed() || ! $package->is_active;
                                $choices = $package->accounts->reject(fn ($a) => $picked->contains('id', $a->id));
                            @endphp
                            <li @class(['acct', 'acct--pack', 'spot', 'acct--off' => $blocked || $packageDown])>
                                <div class="acct__row">
                                    <span class="plat plat--pack" aria-hidden="true">
                                        <svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/></svg>
                                    </span>
                                    <div>
                                        <h2 class="acct__title">{{ $package->name }}</h2>
                                        <p class="acct__meta">{{ implode(', ', array_filter(['Paket', ...$rules])) }}</p>
                                    </div>
                                    @if ($blocked)
                                        <span class="badge">{{ $blocked }}</span>
                                    @else
                                        <div class="pips" role="img" aria-label="{{ $picked->count() }} / {{ $package->pick_count }} hesap seçildi">
                                            @for ($i = 0; $i < $package->pick_count; $i++)<i @class(['is-on' => $i < $picked->count()])></i>@endfor
                                            <span>{{ $picked->count() }} / {{ $package->pick_count }}</span>
                                        </div>
                                    @endif
                                </div>
                                @if ($package->description)<p class="acct__note">{{ $package->description }}</p>@endif

                                @if ($picked->isNotEmpty())
                                    <ul class="acct__sub" role="list">
                                        @foreach ($picked as $account)
                                            <li>@include('customer.partials.account-row', ['meta' => [$account->platformLabel()], 'blocked' => $blocked ?? ($packageDown ? 'Paket kapatıldı' : null)])</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($left > 0 && ! $blocked && ! $packageDown)
                                    <div class="acct__pick">
                                        <p>{{ $left }} hesap daha seçebilirsin. Seçimini sonradan değiştiremezsin.</p>
                                        <ul class="choices" role="list">
                                            @foreach ($choices as $account)
                                                @php($unavailable = $account->trashed() || ! $account->is_active)
                                                <li>
                                                    <form method="POST" action="{{ route('pick', $key) }}" data-confirm="{{ $account->title }} seçilsin mi? Sonradan değiştiremezsin.">
                                                        @csrf
                                                        <input type="hidden" name="account_id" value="{{ $account->id }}">
                                                        <button type="submit" class="choice" @disabled($unavailable)>
                                                            <span class="plat plat--s" data-p="{{ $account->platform }}" aria-hidden="true">{{ mb_substr($account->platformLabel(), 0, 1) }}</span>
                                                            <span>
                                                                <span class="choice__title">{{ $account->title }}</span>
                                                                <span class="choice__sub">{{ $unavailable ? 'Şu an seçilemiyor' : $account->platformLabel() }}</span>
                                                            </span>
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
            @endif
        </div>

        <aside class="desk__side">
            <form method="POST" action="{{ route('redeem') }}" class="keybox glass" data-busy novalidate>
                @csrf
                <label for="key" class="keybox__title">Anahtar ekle</label>
                <p class="keybox__lead">Sana teslim edilen ürün anahtarını yapıştır. Tire ve boşluk yazmasan da olur.</p>
                <input id="key" name="key" type="text" class="input input--l input--key" value="{{ old('key') }}"
                    placeholder="{{ config('guard.key_prefix') }}-XXXXXXXX-XXXXXXXX" autocomplete="off" autocapitalize="characters"
                    spellcheck="false" maxlength="64" required data-key-input aria-describedby="key-error"
                    @if ($keys->isEmpty()) autofocus @endif @error('key') aria-invalid="true" @enderror>
                <p id="key-error" class="error" @error('key') role="alert" @enderror>@error('key'){{ $message }}@enderror</p>
                <button type="submit" class="btn btn--primary btn--l btn--block">Hesabıma ekle</button>
            </form>

            <section class="tips" aria-labelledby="tips-title">
                <h2 id="tips-title">Kod gelmedi mi?</h2>
                <ol>
                    <li>Platformda “kodu tekrar gönder”e bas.</li>
                    <li>30 saniye bekle, sonra düğmeye yeniden bas.</li>
                    <li>Yine gelmezse @if ($contact)<a href="{{ $contact }}" rel="noopener" target="_blank">destekle iletişime geç</a>.@else satıcınla iletişime geç.@endif</li>
                </ol>
            </section>
        </aside>
    </div>
</x-layouts.app>
