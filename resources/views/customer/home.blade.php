{{--
    Kodlarım. Üstte en son kullandığın (ya da seçtiğin) hesap büyük kart olarak kodu hazır bekler;
    altında arama ve platform hapları, sonra raflar: aynı platformdan birden çok hesap kendi rafında
    ("Steam · 3 hesap"), tek hesaplı platformlar "Diğer hesapların" rafında. Veri: App\Support\Wallet.
--}}
@php
    use App\Models\Setting;
    $contact = Setting::get('contact_url');
    $empty = $rows->isEmpty() && $picks->isEmpty();
    $heroId = $hero['id'] ?? null;
@endphp
<x-layouts.app title="Kodlarım">
    @if (Setting::get('announcement'))
        <p class="notice">{{ Setting::get('announcement') }}</p>
    @endif

    @if ($empty)
        {{-- Boş cüzdan: ilk hesabın yeri anahtar formunun kendisi. Yanda sonra ne olacağı, gerçek arayüz parçalarıyla. --}}
        <div class="wallet wallet--empty">
            <section class="wallet__main" aria-labelledby="wallet-title">
                <header class="wallet__head">
                    <h1 id="wallet-title">Cüzdanın boş</h1>
                    <p>Sana teslim edilen anahtarı ekle; hesabın burada belirir ve kodunu tek dokunuşla alırsın.</p>
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
                        <button type="submit" class="btn hero-card__btn firstcard__go" data-busy-text="Ekleniyor">Cüzdanıma ekle</button>
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
                                <span class="after__strips"><i data-p="steam"></i><i data-p="steam"></i><i data-p="netflix"></i></span>
                            </div>
                            <div><h3>Hesapların platforma göre dizilir</h3><p>Aynı platformdan birkaç hesabın varsa (örneğin 3 Steam) bir rafta toplanır; her birine kendi adını verebilirsin.</p></div>
                        </li>
                        <li>
                            <div class="after__art" aria-hidden="true"><span class="after__cells"><i>5</i><i>8</i><i>2</i><i>1</i></span></div>
                            <div><h3>En son kullandığın en üstte</h3><p>Platform kod sorduğunda düğmeye bas; kod birkaç saniyede ekranında.</p></div>
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
                    <h1 id="wallet-title">Kodların</h1>
                    <p>{{ $rows->count() }} hesap. Platform kod sorduğunda üstteki karttan al; başka hesap için raftan "Kod al"a bas.</p>
                </header>

                @if ($hero)
                    <div class="heroes" data-heroes>
                        <p class="heroes__label" data-hero-label>{{ $hero['last'] ? 'Son kullandığın' : 'Hazır' }}</p>
                        @foreach ($rows->reject(fn ($r) => $r['blocked']) as $row)
                            @include('customer.partials.hero', ['row' => $row, 'active' => $row['id'] === $heroId])
                        @endforeach
                    </div>
                @endif

                {{-- Paketten seçim hakkı: kodun altında, rafların üstünde; kod her zaman en önde. --}}
                @foreach ($picks as $pick)
                    @include('customer.partials.pick', ['pick' => $pick])
                @endforeach

                @if ($rows->count() > 1)
                    <div class="tools" data-tools>
                        @if ($rows->count() >= 6)
                            <label class="tools__search">
                                <span class="visually-hidden">Hesap ara</span>
                                <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="9" cy="9" r="5.5"/><path d="m13.5 13.5 3 3"/></svg>
                                <input type="search" class="tools__input" placeholder="Hesap ara: ad, platform ya da kendi verdiğin ad" data-search-input autocomplete="off">
                            </label>
                        @endif
                        @if ($platforms->count() > 1)
                            <div class="tools__chips" role="group" aria-label="Platforma göre süz">
                                <button type="button" class="chipbtn is-on" data-filter="" aria-pressed="true">Hepsi <span>{{ $rows->count() }}</span></button>
                                @foreach ($platforms as $platform)
                                    <button type="button" class="chipbtn" data-filter="{{ $platform['platform'] }}" aria-pressed="false">{{ $platform['label'] }} <span>{{ $platform['count'] }}</span></button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="shelves" data-shelves>
                        @foreach ($shelves as $shelf)
                            <section class="shelf" data-shelf aria-label="{{ $shelf['label'] }}">
                                <h2 class="shelf__title">{{ $shelf['label'] }} <span>· {{ $shelf['rows']->count() }} hesap</span></h2>
                                <ul class="arows" role="list">
                                    @foreach ($shelf['rows'] as $row)
                                        @include('customer.partials.arow', ['row' => $row, 'active' => $row['id'] === $heroId])
                                    @endforeach
                                </ul>
                            </section>
                        @endforeach
                        <p class="shelves__none" data-none hidden>Aramana uyan hesap yok.</p>
                    </div>
                @elseif ($rows->count() === 1 && $rows->first()['blocked'])
                    <ul class="arows" role="list">@include('customer.partials.arow', ['row' => $rows->first(), 'active' => false])</ul>
                @endif

                @if ($rows->isNotEmpty() && ! $hero)
                    <p class="wallet__warn">Kod verebilen hesabın kalmadı. Yeni bir anahtar ekleyebilir ya da satıcınla iletişime geçebilirsin.</p>
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
                    <button type="submit" class="btn btn--primary btn--l btn--block" data-busy-text="Ekleniyor">Hesabıma ekle</button>
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
