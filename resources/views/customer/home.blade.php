{{--
    Kodlarım. Boşsa ilk anahtar formu; doluysa kod masası: solda hesaplar (aynı platformdan birden
    çok hesap kendi grubunda), sağda seçili hesabın ayrıntısı ve kod sahnesi. Veri: App\Support\Wallet.
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
        {{--
            Kod masası: solda hesapların (arama, platform grupları), sağda seçili hesabın ayrıntısı.
            Kod, ayrıntının en üstündeki sahnede alınır. Telefonda ayrıntı üstte, liste altta.
        --}}
        <div class="desk">
            <header class="desk__head">
                <div>
                    <h1>Kodlarım</h1>
                    <p>{{ $rows->count() }} hesap. Platform kod istediğinde hesabı seç, düğmeye bas.</p>
                </div>
                <details class="addkey" @if ($errors->has('key')) open @endif>
                    <summary class="btn btn--secondary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        Anahtar ekle
                    </summary>
                    <form method="POST" action="{{ route('redeem') }}" class="addkey__form glass" data-busy novalidate>
                        @csrf
                        <label for="key" class="addkey__title">Yeni anahtar</label>
                        <p class="addkey__lead">Siparişinle gelen anahtarı yapıştır. Tire ve boşluk yazmasan da olur.</p>
                        <div class="addkey__row">
                            <input id="key" name="key" type="text" class="input input--key" value="{{ old('key') }}"
                                placeholder="{{ config('guard.key_prefix') }}-XXXXXXXX-XXXXXXXX" autocomplete="off" autocapitalize="characters"
                                spellcheck="false" maxlength="64" required data-key-input aria-describedby="key-error"
                                @error('key') aria-invalid="true" @enderror>
                            <button type="submit" class="btn btn--primary" data-busy-text="Ekleniyor">Ekle</button>
                        </div>
                        <p id="key-error" class="error" @error('key') role="alert" @enderror>@error('key'){{ $message }}@enderror</p>
                    </form>
                </details>
            </header>

            {{-- Paketten seçim hakkı varsa önce o: seçilmeden kod alınamaz. --}}
            @foreach ($picks as $pick)
                @include('customer.partials.pick', ['pick' => $pick])
            @endforeach

            @if ($rows->isNotEmpty())
                <div class="desk__body">
                    <section class="desk__detail" data-heroes aria-label="Seçili hesap">
                        @foreach ($rows->reject(fn ($r) => $r['blocked']) as $row)
                            @include('customer.partials.hero', [
                                'row' => $row,
                                'active' => $row['id'] === $heroId,
                                'history' => $history->get($row['account']->id, collect()),
                                'asked' => in_array($row['key']->id, $pendingRequests, true),
                                'contact' => $contact,
                            ])
                        @endforeach
                        @unless ($hero)
                            <div class="dt dt--none">
                                <h2>Kod verebilen hesabın kalmadı</h2>
                                <p>Yeni bir anahtar ekleyebilir ya da satıcınla iletişime geçebilirsin.@if ($contact) <a href="{{ $contact }}" rel="noopener" target="_blank">Destek</a>@endif</p>
                            </div>
                        @endunless
                    </section>

                    <aside class="desk__list" aria-label="Hesapların">
                        @if ($rows->count() >= 6)
                            <label class="list__search">
                                <span class="visually-hidden">Hesap ara</span>
                                <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="9" cy="9" r="5.5"/><path d="m13.5 13.5 3 3"/></svg>
                                <input type="search" placeholder="Hesap ara" data-search-input autocomplete="off">
                            </label>
                        @endif
                        @if ($platforms->count() > 1)
                            <div class="list__chips" role="group" aria-label="Platforma göre süz">
                                <button type="button" class="chipbtn is-on" data-filter="" aria-pressed="true">Hepsi</button>
                                @foreach ($platforms as $platform)
                                    <button type="button" class="chipbtn" data-filter="{{ $platform['platform'] }}" aria-pressed="false">{{ $platform['label'] }}</button>
                                @endforeach
                            </div>
                        @endif
                        <div class="list__groups" data-shelves>
                            @foreach ($shelves as $shelf)
                                <section class="list__group" data-shelf aria-label="{{ $shelf['label'] }}">
                                    <h2>{{ $shelf['label'] }} <span>{{ $shelf['rows']->count() }}</span></h2>
                                    <ul role="list">
                                        @foreach ($shelf['rows'] as $row)
                                            @include('customer.partials.arow', ['row' => $row, 'active' => $row['id'] === $heroId])
                                        @endforeach
                                    </ul>
                                </section>
                            @endforeach
                            <p class="list__none" data-none hidden>Aramana uyan hesap yok.</p>
                        </div>
                    </aside>
                </div>
            @endif
        </div>
    @endif
</x-layouts.app>
