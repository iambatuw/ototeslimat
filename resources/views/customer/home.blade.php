{{--
    Kodlarım. Boşsa ilk anahtar formu; doluysa kütüphane: her hesap bir kart, kod kartın
    içinde alınır. Veri: App\Support\Wallet.
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
            Kütüphane: her hesap bir kart (kapak, ad, eklendiği gün, kalan hak, tek kod düğmesi); kod kartın
            içinde açılır. Sağ üstte ızgara / liste görünümü. 6+ hesapta arama, platform hapları süzer.
        --}}
        <div class="libpage">
            <header class="libpage__head">
                <div>
                    <h1>Kodlarım</h1>
                    <p>{{ $rows->count() }} hesap kütüphanende</p>
                </div>
                <div class="libpage__tools">
                    @if ($rows->isNotEmpty())
                        <div class="viewswitch" role="group" aria-label="Görünüm">
                            <button type="button" data-view="list" aria-pressed="false" aria-label="Liste">
                                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 5h12M4 10h12M4 15h12"/></svg>
                            </button>
                            <button type="button" data-view="grid" aria-pressed="true" aria-label="Izgara">
                                <svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3.5" y="3.5" width="5" height="5" rx="1"/><rect x="11.5" y="3.5" width="5" height="5" rx="1"/><rect x="3.5" y="11.5" width="5" height="5" rx="1"/><rect x="11.5" y="11.5" width="5" height="5" rx="1"/></svg>
                            </button>
                        </div>
                    @endif
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
                </div>
            </header>

            {{-- Paketten seçim hakkı varsa önce o: seçilmeden kod alınamaz. --}}
            @foreach ($picks as $pick)
                @include('customer.partials.pick', ['pick' => $pick])
            @endforeach

            @if ($rows->isNotEmpty())
                @if ($platforms->count() > 1 || $rows->count() >= 6)
                    <div class="libbar">
                        @if ($rows->count() >= 6)
                            <label class="libbar__search">
                                <span class="visually-hidden">Hesap ara</span>
                                <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="9" cy="9" r="5.5"/><path d="m13.5 13.5 3 3"/></svg>
                                <input type="search" placeholder="Hesap ara" data-search-input autocomplete="off">
                            </label>
                        @endif
                        @if ($platforms->count() > 1)
                            <div class="libbar__chips" role="group" aria-label="Platforma göre süz">
                                <button type="button" class="chipbtn is-on" data-filter="" aria-pressed="true">Tümü <span>{{ $rows->count() }}</span></button>
                                @foreach ($platforms as $platform)
                                    <button type="button" class="chipbtn" data-filter="{{ $platform['platform'] }}" aria-pressed="false">{{ $platform['label'] }} <span>{{ $platform['count'] }}</span></button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <div class="lib" data-shelves data-lib>
                    <ul class="lib__grid" role="list" data-shelf>
                        @foreach ($rows as $row)
                            @include('customer.partials.hero', [
                                'row' => $row,
                                'picked' => $row['id'] === $heroId && (request()->filled('hesap') || session('opened')),
                                'history' => $history->get($row['account']->id, collect()),
                                'asked' => in_array($row['key']->id, $pendingRequests, true),
                                'contact' => $contact,
                            ])
                        @endforeach
                    </ul>
                    <p class="lib__none" data-none hidden>Aramana uyan hesap yok.</p>
                </div>
            @endif
        </div>
    @endif
</x-layouts.app>
