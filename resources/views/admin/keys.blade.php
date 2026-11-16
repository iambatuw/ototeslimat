@php
    $hasTargets = $accounts->isNotEmpty() || $packages->isNotEmpty();
    $activeStatus = $filters['status'] ?? '';
    $statusUrl = fn ($value) => route('admin.keys.index', array_filter(['q' => $filters['q'] ?? null, 'status' => $value]));
@endphp
<x-layouts.app title="Anahtarlar">
    <header class="head">
        <div>
            <h1 class="words">Anahtarlar</h1>
            <p>Müşterine verdiğin her anahtar bir hesaba ya da pakete bağlıdır.</p>
        </div>
    </header>

    @if (! $hasTargets)
        <div class="blank">
            <div class="blank__art" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
            <h2>Önce bir hesap ekle</h2>
            <p>Anahtar, sattığın bir hesaba bağlı üretilir. Hesabı ekledikten sonra buraya dön.</p>
            <a href="{{ route('admin.accounts.create') }}" class="btn btn--primary">Hesap ekle</a>
        </div>
    @else
        {{-- Üretim: tek satırlık bir araç. Sonuç hemen altında, kopyalanmaya hazır çıkar. --}}
        <form method="POST" action="{{ route('admin.keys.store') }}" class="maker glass" data-busy novalidate>
            @csrf
            <div class="maker__row">
                <div class="field">
                    <label for="target">Ne için anahtar üretilsin?</label>
                    <select id="target" name="target" class="select" required @if ($errors->has('account_id') || $errors->has('package_id')) aria-invalid="true" @endif>
                        <option value="">Hesap ya da paket seç</option>
                        @if ($accounts->isNotEmpty())
                            <optgroup label="Hesaplar">
                                @foreach ($accounts as $account)
                                    <option value="account:{{ $account->id }}" @selected($target === 'account:'.$account->id)>{{ $account->title }} ({{ $account->platformLabel() }})</option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if ($packages->isNotEmpty())
                            <optgroup label="Paketler">
                                @foreach ($packages as $package)
                                    <option value="package:{{ $package->id }}" @selected($target === 'package:'.$package->id)>{{ $package->name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    <p class="error">{{ $errors->first('account_id') ?: $errors->first('package_id') }}</p>
                </div>
                <div class="field">
                    <label for="count">Adet</label>
                    <div class="stepper" data-stepper>
                        <button type="button" data-step="-1" aria-label="Bir azalt">
                            <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3.5 8h9"/></svg>
                        </button>
                        <input id="count" name="count" type="number" class="input" min="1" max="500" value="{{ old('count', 1) }}" inputmode="numeric" required @error('count') aria-invalid="true" @enderror>
                        <button type="button" data-step="1" aria-label="Bir artır">
                            <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3.5 8h9M8 3.5v9"/></svg>
                        </button>
                    </div>
                    <p class="error">@error('count'){{ $message }}@enderror</p>
                </div>
                <button type="submit" class="btn btn--primary btn--l">Anahtar üret</button>
            </div>

            <details class="more" @if ($errors->hasAny(['max_uses', 'valid_days', 'cooldown_minutes', 'label'])) open @endif>
                <summary>Sınır ve süre ekle <span class="optional">(boş bırakırsan sınırsız ve süresiz)</span></summary>
                <div class="more__body">
                    <div class="grid-3">
                        <div class="field">
                            <label for="max_uses">Kod hakkı</label>
                            <input id="max_uses" name="max_uses" type="number" class="input" min="1" value="{{ old('max_uses') }}" inputmode="numeric" placeholder="Sınırsız" @error('max_uses') aria-invalid="true" @enderror>
                            <p class="{{ $errors->has('max_uses') ? 'error' : 'hint' }}">{{ $errors->first('max_uses') ?: 'Müşteri en fazla kaç kod alabilir.' }}</p>
                        </div>
                        <div class="field">
                            <label for="valid_days">Geçerlilik (gün)</label>
                            <input id="valid_days" name="valid_days" type="number" class="input" min="1" value="{{ old('valid_days') }}" inputmode="numeric" placeholder="Süresiz" @error('valid_days') aria-invalid="true" @enderror>
                            <p class="{{ $errors->has('valid_days') ? 'error' : 'hint' }}">{{ $errors->first('valid_days') ?: 'Müşteri anahtarı eklediği an başlar.' }}</p>
                        </div>
                        <div class="field">
                            <label for="cooldown_minutes">Kodlar arası bekleme (dk)</label>
                            <input id="cooldown_minutes" name="cooldown_minutes" type="number" class="input" min="0" value="{{ old('cooldown_minutes', 0) }}" inputmode="numeric" @error('cooldown_minutes') aria-invalid="true" @enderror>
                            <p class="{{ $errors->has('cooldown_minutes') ? 'error' : 'hint' }}">{{ $errors->first('cooldown_minutes') ?: '0 ise bekleme yok.' }}</p>
                        </div>
                    </div>
                    <div class="field">
                        <label for="label">Not <span class="optional">(yalnızca sen görürsün)</span></label>
                        <input id="label" name="label" type="text" class="input" value="{{ old('label') }}" maxlength="120" placeholder="Sipariş no, pazar yeri">
                        <p class="error">@error('label'){{ $message }}@enderror</p>
                    </div>
                </div>
            </details>
        </form>

        @if ($fresh)
            <section class="fresh" aria-labelledby="fresh-title">
                <div class="fresh__head">
                    <div>
                        <h2 id="fresh-title">{{ count($fresh) }} yeni anahtar hazır</h2>
                        <p>Bu liste sayfadan ayrılınca kapanır; anahtarlar aşağıdaki tabloda kalır.</p>
                    </div>
                    <button type="button" class="btn btn--primary btn--s" data-copy="#fresh-list">Hepsini kopyala</button>
                </div>
                <pre id="fresh-list" class="visually-hidden">{{ implode("\n", $fresh) }}</pre>
                <ul class="fresh__list stagger" role="list">
                    @foreach ($fresh as $code)
                        <li><button type="button" class="keychip" data-copy="{{ $code }}" data-label="{{ $code }}">{{ $code }}</button></li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="panel" aria-labelledby="list-title">
            <div class="panel__head">
                <h2 id="list-title">Tüm anahtarlar</h2>
                <div class="row">
                    <a href="{{ route('admin.keys.export', request()->query()) }}" class="btn btn--secondary btn--s">CSV indir</a>
                    <form method="POST" action="{{ route('admin.keys.purge') }}" data-confirm="İptal edilmiş, süresi dolmuş ve hakkı bitmiş anahtarlar silinsin mi? Kayıtlar kalır.">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn--ghost btn--s">Eskileri temizle</button>
                    </form>
                </div>
            </div>
            <div class="panel__tools">
                <nav class="pills" aria-label="Duruma göre süz">
                    <a href="{{ $statusUrl(null) }}" @if ($activeStatus === '') aria-current="true" @endif>Hepsi</a>
                    @foreach (\App\Models\ProductKey::STATUS_LABELS as $value => $label)
                        <a href="{{ $statusUrl($value) }}" @if ($activeStatus === $value) aria-current="true" @endif>{{ $label }}</a>
                    @endforeach
                </nav>
                <form method="GET" class="search" role="search">
                    @if ($activeStatus)<input type="hidden" name="status" value="{{ $activeStatus }}">@endif
                    <label for="q" class="visually-hidden">Ara</label>
                    <input id="q" name="q" type="search" class="input" value="{{ $filters['q'] ?? '' }}" placeholder="Tam anahtar ya da not ara">
                    <button type="submit" class="btn btn--secondary btn--s">Ara</button>
                </form>
            </div>
            @if ($keys->isEmpty())
                <div class="blank blank--s"><p>{{ array_filter($filters) ? 'Bu aramaya uyan anahtar yok.' : 'Henüz anahtar yok. Yukarıdan ilkini üret.' }}</p></div>
            @else
                @include('admin.partials.key-table', ['keys' => $keys])
                <x-pager :paginator="$keys" />
            @endif
        </section>
    @endif
</x-layouts.app>
