@php
    use App\Models\Setting;
    $contact = Setting::get('contact_url');

    // Her anahtar cüzdanda bir ya da daha çok karta dönüşür: tek hesap = bir kart,
    // paket = seçilmiş her hesap için bir kart + seçim hakkı kaldıysa bir "seç" kartı.
    $cards = collect();
    foreach ($keys as $key) {
        $status = $key->status();
        $blocked = in_array($status, ['revoked', 'expired', 'used_up'], true) ? $key->statusLabel() : null;
        $meta = array_values(array_filter([
            $key->max_uses ? $key->usesLeft().' kod hakkı' : 'Sınırsız kod',
            $key->expires_at ? $key->expires_at->format('d.m.Y').' tarihine kadar' : null,
            $key->cooldown_minutes ? 'kodlar arası '.$key->cooldown_minutes.' dk' : null,
        ]));
        // Şeritte bir bakışta görülecek durum: az gün kaldıysa o, yoksa kalan hak.
        $daysLeft = $key->expires_at ? (int) ceil(now()->diffInDays($key->expires_at, false)) : null;
        $chip = match (true) {
            $daysLeft !== null && $daysLeft <= 7 => ($daysLeft <= 0 ? 'Bugün bitiyor' : $daysLeft.' gün kaldı'),
            $key->max_uses !== null => $key->usesLeft().' kod hakkı',
            $daysLeft !== null => $daysLeft.' gün kaldı',
            default => null,
        };

        if (! $key->isPackage()) {
            $cards->push(['kind' => 'account', 'key' => $key, 'account' => $key->account, 'meta' => $meta, 'status' => $chip, 'blocked' => $blocked, 'id' => 'k'.$key->id]);

            continue;
        }

        $package = $key->package;
        $picked = $key->selections;
        $packageDown = $package->trashed() || ! $package->is_active;
        $left = max(0, $package->pick_count - $picked->count());

        foreach ($picked as $account) {
            $cards->push(['kind' => 'account', 'key' => $key, 'account' => $account, 'meta' => ['Paket: '.$package->name, ...$meta], 'status' => $chip,
                'blocked' => $blocked ?? ($packageDown ? 'Paket kapatıldı' : null), 'id' => 'k'.$key->id.'a'.$account->id]);
        }

        if ($left > 0 && ! $blocked && ! $packageDown) {
            $cards->push(['kind' => 'pick', 'key' => $key, 'package' => $package, 'left' => $left, 'picked' => $picked->count(), 'meta' => $meta, 'blocked' => null, 'id' => 'k'.$key->id.'p',
                'choices' => $package->accounts->reject(fn ($a) => $picked->contains('id', $a->id))]);
        }
    }

    // Öndeki kart: az önce eklenen/seçilen anahtar, yoksa kod verebilen ilk kart.
    $opened = session('opened');
    $find = fn (callable $test) => ($i = $cards->search($test)) === false ? null : $i;
    $front = $find(fn ($c) => $c['key']->id === $opened && $c['kind'] === 'pick')
        ?? $find(fn ($c) => $c['key']->id === $opened)
        ?? $find(fn ($c) => ! $c['blocked'] && $c['kind'] === 'account' && ! $c['account']->trashed() && $c['account']->is_active)
        ?? 0;
    $cards = $cards->map(fn ($c, $i) => $c + ['active' => $i === $front]);
    // Öndeki kart yığının başında tam açık durur; diğerleri altında şerit olarak dizilir.
    $cards = collect([$cards[$front] ?? null])->filter()->concat($cards->reject(fn ($c) => $c['active']))->values();
    $usable = $cards->filter(fn ($c) => $c['kind'] === 'account' && ! $c['blocked'])->count();
@endphp
<x-layouts.app title="Kodlarım">
    @if (Setting::get('announcement'))
        <p class="notice">{{ Setting::get('announcement') }}</p>
    @endif

    @if ($cards->isEmpty())
        {{-- Boş cüzdan: ilk kartın yeri anahtar formunun kendisi. Yanda sonra ne olacağı, gerçek arayüz parçalarıyla. --}}
        <div class="wallet wallet--empty">
            <section class="wallet__main" aria-labelledby="wallet-title">
                <header class="wallet__head">
                    <h1 id="wallet-title" class="words">Cüzdanın boş</h1>
                    <p>Sana teslim edilen anahtarı ekle; hesabın burada bir kart olarak belirir ve kodunu kartın üstünden alırsın.</p>
                </header>

                <form method="POST" action="{{ route('redeem') }}" class="firstcard" data-busy novalidate>
                    @csrf
                    <div class="firstcard__top">
                        <span class="firstcard__mark" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        </span>
                        <label for="key" class="firstcard__title">İlk anahtarını ekle</label>
                    </div>
                    <p class="firstcard__lead">Sipariş mesajında <span class="mono">{{ config('guard.key_prefix') }}-</span> ile başlayan anahtar. Tire ve boşluk yazmasan da olur.</p>
                    <div class="firstcard__row">
                        <input id="key" name="key" type="text" class="firstcard__input" value="{{ old('key') }}"
                            placeholder="{{ config('guard.key_prefix') }}-XXXXXXXX-XXXXXXXX" autocomplete="off" autocapitalize="characters"
                            spellcheck="false" maxlength="64" required autofocus data-key-input aria-describedby="key-error"
                            @error('key') aria-invalid="true" @enderror>
                        <button type="submit" class="btn wcard__btn firstcard__go" data-busy-text="Ekleniyor">Cüzdanıma ekle</button>
                    </div>
                    <p id="key-error" class="firstcard__error" @error('key') role="alert" @enderror>@error('key'){{ $message }}@enderror</p>
                </form>
            </section>

            <aside class="wallet__side" aria-labelledby="next-title">
                <section class="after">
                    <h2 id="next-title">Ekleyince ne olur?</h2>
                    <ol class="after__steps" role="list">
                        <li>
                            <div class="after__art" aria-hidden="true"><span class="after__key">{{ config('guard.key_prefix') }}-7H2K9QXD</span></div>
                            <div><h3>Anahtar hesabına bağlanır</h3><p>Bir kez eklersin; başka biri aynı anahtarı kullanamaz.</p></div>
                        </li>
                        <li>
                            <div class="after__art" aria-hidden="true">
                                <span class="after__strips"><i data-p="netflix"></i><i data-p="steam"></i><i data-p="chatgpt"></i></span>
                            </div>
                            <div><h3>Hesabın kart olarak gelir</h3><p>Her hesap kendi renginde bir kart; kaç hesabın varsa üst üste dizilir.</p></div>
                        </li>
                        <li>
                            <div class="after__art" aria-hidden="true"><span class="after__cells"><i>5</i><i>8</i><i>2</i><i>1</i></span></div>
                            <div><h3>Kodu karttan alırsın</h3><p>Platform kod sorduğunda karttaki düğmeye bas; kod birkaç saniyede kartın üstünde.</p></div>
                        </li>
                    </ol>
                    <p class="after__note">Anahtarın yoksa satın aldığın yerden iste.@if ($contact) <a href="{{ $contact }}" rel="noopener" target="_blank">Destek</a>@endif</p>
                </section>
            </aside>
        </div>
    @else
    <div class="wallet">
        <section class="wallet__main" aria-labelledby="wallet-title">
            <header class="wallet__head">
                <h1 id="wallet-title" class="words">Kodların</h1>
                <p>
                    @if ($cards->count() === 1)
                        Platform kod istediğinde kartın üstündeki düğmeye bas; kod kartın yüzüne gelir.
                    @else
                        {{ $cards->count() }} kart. Bir karta dokun, öne gelsin; kod kartın yüzüne gelir.
                    @endif
                </p>
            </header>

                <div class="stack" data-stack aria-label="Hesap kartların">
                    @foreach ($cards as $card)
                        @include('customer.partials.wallet-card', ['card' => $card])
                    @endforeach
                </div>
                @if ($usable === 0)
                    <p class="wallet__warn">Kod verebilen kartın kalmadı. Yeni bir anahtar ekleyebilir ya da satıcınla iletişime geçebilirsin.</p>
                @endif
        </section>

        <aside class="wallet__side" aria-label="Anahtar ekle ve yardım">
            <form method="POST" action="{{ route('redeem') }}" class="addkey glass" data-busy novalidate>
                @csrf
                <label for="key" class="addkey__title">Anahtar ekle</label>
                <p class="addkey__lead">Siparişinle gelen anahtarı yapıştır. Tire ve boşluk yazmasan da olur.</p>
                <input id="key" name="key" type="text" class="input input--l input--key" value="{{ old('key') }}"
                    placeholder="{{ config('guard.key_prefix') }}-XXXXXXXX-XXXXXXXX" autocomplete="off" autocapitalize="characters"
                    spellcheck="false" maxlength="64" required data-key-input aria-describedby="key-error"
                    @error('key') aria-invalid="true" @enderror>
                <p id="key-error" class="error" @error('key') role="alert" @enderror>@error('key'){{ $message }}@enderror</p>
                <button type="submit" class="btn btn--primary btn--l btn--block" data-busy-text="Ekleniyor">Cüzdanıma ekle</button>
            </form>

            <section class="help" aria-labelledby="help-title">
                <h2 id="help-title">Kod gelmedi mi?</h2>
                <ol>
                    <li>Platformda “kodu tekrar gönder”e bas.</li>
                    <li>Mail kodunda sayfa 2 dakika kendiliğinden bekler; kapatma.</li>
                    <li>Yine gelmezse @if ($contact)<a href="{{ $contact }}" rel="noopener" target="_blank">destekle iletişime geç</a>.@else satıcınla iletişime geç.@endif</li>
                </ol>
            </section>
        </aside>
    </div>
    @endif
</x-layouts.app>
