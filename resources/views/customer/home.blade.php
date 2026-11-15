@php use App\Models\Setting; @endphp
<x-layouts.app title="Kodlarım">
    <div class="page-head">
        <div>
            <h1>Kodlarım</h1>
            <p>Platform giriş kodu istediğinde hesabının yanındaki düğmeye bas.</p>
        </div>
    </div>

    @if (Setting::get('announcement'))
        <p class="notice">{{ Setting::get('announcement') }}</p>
    @endif

    <form method="POST" action="{{ route('redeem') }}" class="card" data-busy novalidate>
        @csrf
        <label for="key" class="card__title">Anahtar ekle</label>
        <p class="card__lead">Satıcının verdiği anahtarı yapıştır. Tire ve boşluk yazmasan da olur.</p>
        <div class="redeem">
            <input id="key" name="key" type="text" class="input input--l input--key" value="{{ old('key') }}"
                placeholder="{{ config('guard.key_prefix') }}-XXXXXXXX-XXXXXXXX" autocomplete="off" autocapitalize="characters"
                spellcheck="false" maxlength="64" required data-key-input aria-describedby="key-error"
                @if ($keys->isEmpty()) autofocus @endif @error('key') aria-invalid="true" @enderror>
            <button type="submit" class="btn btn--primary btn--l" data-busy-text="Kontrol ediliyor">Ekle</button>
        </div>
        <p id="key-error" class="error" @error('key') role="alert" @enderror>@error('key'){{ $message }}@enderror</p>
    </form>

    <h2 class="list-title">Hesaplarım</h2>

    @if ($keys->isEmpty())
        <div class="card empty">
            <h3>Henüz hesabın yok</h3>
            <p>Yukarıya ilk anahtarını ekle. Hesap burada görünür ve kodunu buradan alırsın.</p>
        </div>
    @else
        <ul class="items" role="list">
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
                    <li class="item">
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
                    <li class="item {{ $blocked || $packageDown ? 'item--off' : '' }}">
                        <div class="item__row">
                            <div>
                                <h3 class="item__title">{{ $package->name }}</h3>
                                <p class="item__meta">{{ implode(', ', array_filter(['paket, '.$picked->count().' / '.$package->pick_count.' hesap seçildi', ...$rules])) }}</p>
                            </div>
                            @if ($blocked)<span class="badge">{{ $blocked }}</span>@endif
                        </div>
                        @if ($package->description)<p class="item__note">{{ $package->description }}</p>@endif

                        @if ($picked->isNotEmpty())
                            <ul class="item__sub" role="list">
                                @foreach ($picked as $account)
                                    <li>@include('customer.partials.account-row', ['meta' => [$account->platformLabel()], 'blocked' => $blocked ?? ($packageDown ? 'Paket kapatıldı' : null)])</li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($left > 0 && ! $blocked && ! $packageDown)
                            <div class="item__sub">
                                <p class="pick-title">{{ $left }} hesap daha seçebilirsin. Seçimini sonradan değiştiremezsin.</p>
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
    @endif
</x-layouts.app>
