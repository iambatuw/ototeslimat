<x-layouts.app title="Paketler">
    @if ($packages->isEmpty())
        <div class="head"><div><h1>Paketler</h1></div></div>
        <div class="empty">
            <h2>Henüz paket yok</h2>
            <p>Paket, tek anahtarla müşterinin listeden birkaç hesap seçtiği üründür. Örneğin "3 platform seç" paketine Netflix, Disney+, HBO Max ve Prime hesaplarını ekle; müşteri istediği üçünü seçsin.</p>
            <a href="{{ route('admin.packages.create') }}" class="btn btn--primary">İlk paketi oluştur</a>
        </div>
    @else
        <div class="split">
            <aside class="panel split__list" aria-labelledby="list-title">
                <div class="panel__head">
                    <h1 id="list-title" class="section">Paketler</h1>
                    <a href="{{ route('admin.packages.create') }}" class="btn btn--primary btn--s">Paket oluştur</a>
                </div>
                <ul class="alist" role="list">
                    @foreach ($packages as $package)
                        <li>
                            <a href="{{ route('admin.packages.index', ['paket' => $package->id]) }}"
                                class="alist__item {{ $package->is_active ? '' : 'is-dim' }}" @if ($selected?->id === $package->id) aria-current="true" @endif>
                                <span class="alist__title">{{ $package->name }}</span>
                                <span class="alist__sub">{{ $package->accounts_count }} hesaptan {{ $package->pick_count }} seçim</span>
                                <span class="alist__count" title="Satılmayı bekleyen anahtar">{{ $package->unused_count }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </aside>

            @if ($selected)
                <div class="split__main">
                    <section class="panel">
                        <div class="panel__head panel__head--top">
                            <div>
                                <h2 class="title">{{ $selected->name }}</h2>
                                <p class="muted small">{{ implode(', ', array_filter([
                                    $selected->accounts->count().' hesaptan '.$selected->pick_count.' tanesi seçilir',
                                    $selected->is_active ? null : 'kapalı',
                                ])) }}</p>
                            </div>
                            <a href="{{ route('admin.packages.edit', $selected) }}" class="btn btn--ghost btn--s">Düzenle</a>
                        </div>
                        <p class="pkg-accounts">
                            @foreach ($selected->accounts as $account)
                                <a href="{{ route('admin.accounts.index', ['hesap' => $account->id]) }}" class="{{ $account->is_active && ! $account->trashed() ? '' : 'is-dim' }}">{{ $account->title }}</a>
                            @endforeach
                        </p>
                        @include('admin.partials.key-gen', ['field' => 'package_id', 'id' => $selected->id])
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
                            <h2 id="keys-title" class="section">Bu paketin anahtarları</h2>
                            <a href="{{ route('admin.keys.export', ['package' => $selected->id, 'status' => $filters['status'] ?? null]) }}" class="btn btn--ghost btn--s">CSV indir</a>
                        </div>
                        <nav class="tabs" aria-label="Anahtar durumu">
                            @foreach (['' => 'Tümü'] + \App\Models\ProductKey::STATUS_LABELS as $value => $label)
                                <a href="{{ route('admin.packages.index', array_filter(['paket' => $selected->id, 'status' => $value])) }}"
                                    @if (($filters['status'] ?? '') === $value) aria-current="page" @endif>{{ $label }}</a>
                            @endforeach
                        </nav>
                        @if ($keys->isEmpty())
                            <p class="muted">{{ ($filters['status'] ?? '') ? 'Bu durumda anahtar yok.' : 'Yukarıdan ilk anahtarları üret.' }}</p>
                        @else
                            @include('admin.partials.key-table', ['keys' => $keys, 'showAccount' => false, 'showPicks' => true])
                            <x-pager :paginator="$keys" />
                        @endif
                    </section>

                    <form method="POST" action="{{ route('admin.packages.destroy', $selected) }}" class="danger" data-confirm="{{ $selected->name }} silinsin mi? Bağlı anahtarlar artık kod vermez.">
                        @csrf @method('DELETE')
                        <p class="muted small">Paketi silersen bağlı anahtarlar kod vermeyi bırakır. Hesaplar silinmez.</p>
                        <button type="submit" class="btn btn--ghost btn--s">Paketi sil</button>
                    </form>
                </div>
            @endif
        </div>
    @endif
</x-layouts.app>
