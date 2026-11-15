@php $platforms = config('guard.platforms'); @endphp
<x-layouts.app title="Hesaplar">
    @if ($accounts->isEmpty() && ! array_filter($filters))
        <div class="head"><div><h1>Hesaplar</h1></div></div>
        <div class="empty">
            <h2>Henüz hesap yok</h2>
            <p>Sattığın hesabın kodlarının geldiği e-posta kutusunu ya da Authenticator (2FA) anahtarını ekle. Sonra bu hesaba anahtar üretirsin.</p>
            <a href="{{ route('admin.accounts.create') }}" class="btn btn--primary">İlk hesabı ekle</a>
        </div>
    @else
        <div class="split">
            {{-- Sol: hesap listesi --}}
            <aside class="panel split__list" aria-labelledby="list-title">
                <div class="panel__head">
                    <h1 id="list-title" class="section">Hesaplar</h1>
                    <a href="{{ route('admin.accounts.create') }}" class="btn btn--primary btn--s">Hesap ekle</a>
                </div>
                <form method="GET" class="stack stack--tight" role="search">
                    <label for="q" class="visually-hidden">Hesap ara</label>
                    <input id="q" name="q" type="search" class="input" value="{{ $filters['q'] ?? '' }}" placeholder="Ad ya da e-posta ara">
                    <label for="platform" class="visually-hidden">Platform</label>
                    <select id="platform" name="platform" class="select" data-autosubmit>
                        <option value="">Tüm platformlar</option>
                        @foreach ($platforms as $key => $p)
                            <option value="{{ $key }}" @selected(($filters['platform'] ?? '') === $key)>{{ $p['label'] }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="btn btn--ghost btn--s">Süz</button></noscript>
                </form>

                @if ($accounts->isEmpty())
                    <p class="muted small">Bu süzgece uyan hesap yok.</p>
                @else
                    <ul class="alist" role="list">
                        @foreach ($accounts as $account)
                            <li>
                                <a href="{{ route('admin.accounts.index', array_filter(['hesap' => $account->id, 'q' => $filters['q'] ?? null, 'platform' => $filters['platform'] ?? null])) }}"
                                    class="alist__item {{ $account->is_active ? '' : 'is-dim' }}" @if ($selected?->id === $account->id) aria-current="true" @endif>
                                    <span class="alist__title">{{ $account->title }}</span>
                                    <span class="alist__sub">{{ $account->platformLabel() }}{{ $account->mail_username ? ', '.$account->mail_username : '' }}</span>
                                    <span class="alist__count" title="Satılmayı bekleyen anahtar">{{ $account->unused_count }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </aside>

            {{-- Sağ: seçili hesap --}}
            @if ($selected)
                <div class="split__main">
                    <section class="panel">
                        <div class="panel__head panel__head--top">
                            <div>
                                <h2 class="title">{{ $selected->title }}</h2>
                                <p class="muted small">{{ implode(', ', array_filter([
                                    $selected->platformLabel(),
                                    $selected->hasMailbox() ? 'mail: '.$selected->mail_username : null,
                                    $selected->hasTotp() ? '2FA açık' : null,
                                    $selected->is_active ? null : 'durduruldu',
                                ])) }}</p>
                            </div>
                            <div class="actions-row">
                                <form method="POST" action="{{ route('admin.accounts.test', $selected) }}" data-busy>
                                    @csrf
                                    <button type="submit" class="btn btn--ghost btn--s" data-busy-text="Deneniyor">Bağlantıyı dene</button>
                                </form>
                                <a href="{{ route('admin.accounts.edit', $selected) }}" class="btn btn--ghost btn--s">Düzenle</a>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.keys.store') }}" class="gen" data-busy novalidate>
                            @csrf
                            <input type="hidden" name="account_id" value="{{ $selected->id }}">
                            <div class="gen__grid">
                                <div class="field">
                                    <label for="count">Adet</label>
                                    <input id="count" name="count" type="number" class="input" min="1" max="500" value="{{ old('count', 10) }}" inputmode="numeric" required @error('count') aria-invalid="true" @enderror>
                                    <p class="error">@error('count'){{ $message }}@enderror</p>
                                </div>
                                <div class="field">
                                    <label for="max_uses">Kod hakkı</label>
                                    <input id="max_uses" name="max_uses" type="number" class="input" min="1" value="{{ old('max_uses') }}" inputmode="numeric" placeholder="Sınırsız" @error('max_uses') aria-invalid="true" @enderror>
                                    <p class="error">@error('max_uses'){{ $message }}@enderror</p>
                                </div>
                                <div class="field">
                                    <label for="valid_days">Süre (gün)</label>
                                    <input id="valid_days" name="valid_days" type="number" class="input" min="1" value="{{ old('valid_days') }}" inputmode="numeric" placeholder="Süresiz" @error('valid_days') aria-invalid="true" @enderror>
                                    <p class="error">@error('valid_days'){{ $message }}@enderror</p>
                                </div>
                                <div class="field">
                                    <label for="cooldown_minutes">Kodlar arası (dk)</label>
                                    <input id="cooldown_minutes" name="cooldown_minutes" type="number" class="input" min="0" value="{{ old('cooldown_minutes', 0) }}" inputmode="numeric" @error('cooldown_minutes') aria-invalid="true" @enderror>
                                    <p class="error">@error('cooldown_minutes'){{ $message }}@enderror</p>
                                </div>
                                <div class="field gen__label">
                                    <label for="label">Not</label>
                                    <input id="label" name="label" type="text" class="input" value="{{ old('label') }}" maxlength="120" placeholder="Sipariş no, pazar yeri">
                                    <p class="error">@error('label'){{ $message }}@enderror</p>
                                </div>
                            </div>
                            <div class="gen__foot">
                                <p class="hint">Süre, müşteri anahtarı eklediği an başlar. Aynı kodu tekrar görmek hak düşürmez.</p>
                                <button type="submit" class="btn btn--primary" data-busy-text="Üretiliyor">Anahtar üret</button>
                            </div>
                        </form>
                    </section>

                    @if ($fresh)
                        <section class="panel fresh" aria-labelledby="fresh-title">
                            <div class="panel__head">
                                <h2 id="fresh-title" class="section">Yeni üretilen {{ count($fresh) }} anahtar</h2>
                                <button type="button" class="btn btn--primary btn--s" data-copy="#fresh-list">Hepsini kopyala</button>
                            </div>
                            <textarea id="fresh-list" class="textarea" readonly rows="{{ min(10, count($fresh)) }}">{{ implode("\n", $fresh) }}</textarea>
                        </section>
                    @endif

                    <section class="panel" aria-labelledby="keys-title">
                        <div class="panel__head">
                            <h2 id="keys-title" class="section">Bu hesabın anahtarları</h2>
                            <a href="{{ route('admin.keys.export', ['account' => $selected->id, 'status' => $filters['status'] ?? null]) }}" class="btn btn--ghost btn--s">CSV indir</a>
                        </div>
                        <nav class="tabs" aria-label="Anahtar durumu">
                            @foreach (['' => 'Tümü'] + \App\Models\ProductKey::STATUS_LABELS as $value => $label)
                                <a href="{{ route('admin.accounts.index', array_filter(['hesap' => $selected->id, 'status' => $value, 'q' => $filters['q'] ?? null, 'platform' => $filters['platform'] ?? null])) }}"
                                    @if (($filters['status'] ?? '') === $value) aria-current="page" @endif>{{ $label }}</a>
                            @endforeach
                        </nav>
                        @if ($keys->isEmpty())
                            <p class="muted">{{ ($filters['status'] ?? '') ? 'Bu durumda anahtar yok.' : 'Yukarıdan ilk anahtarları üret.' }}</p>
                        @else
                            @include('admin.partials.key-table', ['keys' => $keys, 'showAccount' => false])
                            <x-pager :paginator="$keys" />
                        @endif
                    </section>

                    <form method="POST" action="{{ route('admin.accounts.destroy', $selected) }}" class="danger" data-confirm="{{ $selected->title }} silinsin mi? Bağlı anahtarlar artık kod vermez.">
                        @csrf @method('DELETE')
                        <p class="muted small">Hesabı silersen bağlı anahtarlar kod vermeyi bırakır. Kayıtlar kalır.</p>
                        <button type="submit" class="btn btn--ghost btn--s">Hesabı sil</button>
                    </form>
                </div>
            @endif
        </div>
    @endif
</x-layouts.app>
