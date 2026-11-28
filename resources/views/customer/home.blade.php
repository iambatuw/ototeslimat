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
        {{--
            Boş kütüphane: kod alınabilen platformların logolarından eğik, yavaşça kayan bir duvar;
            ortada anahtar alanı. Duraklat düğmesi ve "hareketi azalt" kaymayı durdurur.
        --}}
        @php
            $wallLogos = collect(['steam', 'netflix', 'chatgpt', 'playstation', 'disney', 'xbox', 'epic', 'capcut', 'ubisoft', 'prime', 'riot', 'ea', 'nintendo', 'battlenet', 'canva', 'rockstar', 'hbomax'])
                ->filter(fn ($p) => is_file(public_path("img/platforms/{$p}.svg")))->values();
        @endphp
        <div class="libpage libpage--empty">
            <header class="libpage__head">
                <div><h1>Kodlarım</h1></div>
            </header>

            <section class="logowall" aria-labelledby="empty-title">
                <div class="logowall__rows" aria-hidden="true">
                    @foreach (range(0, 3) as $r)
                        @php($wallRow = $wallLogos->slice($r * 4)->concat($wallLogos->take($r * 4)))
                        <div class="logowall__row">
                            @foreach ([0, 1] as $copy)
                                @foreach ($wallRow as $logo)
                                    <span class="logowall__tile" data-p="{{ $logo }}"><img src="{{ asset("img/platforms/{$logo}.svg") }}" alt=""></span>
                                @endforeach
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('redeem') }}" class="logowall__form" data-busy novalidate>
                    @csrf
                    <h2 id="empty-title">Kütüphanen boş</h2>
                    <p class="logowall__lead">Siparişle gelen <span class="mono">{{ config('guard.key_prefix') }}-</span> ile başlayan anahtarı ekle; hesabın kapağıyla burada belirsin. Tire ve boşluk yazmasan da olur.</p>
                    <label for="key" class="visually-hidden">Anahtar</label>
                    <div class="keycard__row">
                        <input id="key" name="key" type="text" class="keycard__input" value="{{ old('key') }}"
                            placeholder="{{ config('guard.key_prefix') }}-XXXXXXXX-XXXXXXXX" autocomplete="off" autocapitalize="characters"
                            spellcheck="false" maxlength="64" required autofocus data-key-input aria-describedby="key-error"
                            @error('key') aria-invalid="true" @enderror>
                        <button type="submit" class="keycard__go" data-busy-text="Ekleniyor">Ekle</button>
                    </div>
                    <p id="key-error" class="keycard__error" @error('key') role="alert" @enderror>@error('key'){{ $message }}@enderror</p>
                    <p class="keycard__help">Anahtarın yoksa satın aldığın yerden iste.@if ($contact) <a href="{{ $contact }}" rel="noopener" target="_blank">Destek</a>@endif</p>
                </form>
            </section>
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
