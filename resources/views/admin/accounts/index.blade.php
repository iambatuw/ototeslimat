@php $platforms = config('guard.platforms'); @endphp
<x-layouts.app title="Hesaplar">
    <div class="page-head">
        <div>
            <h1>Hesaplar</h1>
            <p>Sattığın hesaplar. Düzenlemek ya da anahtar üretmek için birine dokun.</p>
        </div>
        <a href="{{ route('admin.accounts.create') }}" class="btn btn--primary">Hesap ekle</a>
    </div>

    @include('admin.partials.catalog-tabs')

    <section class="card card--flush">
        @if ($accounts->isEmpty() && ! array_filter($filters))
            <div class="empty">
                <h2>Henüz hesap yok</h2>
                <p>Sattığın hesabın kodlarının geldiği e-posta kutusunu ya da 2FA anahtarını ekle. Sonra o hesap için anahtar üretirsin.</p>
                <a href="{{ route('admin.accounts.create') }}" class="btn btn--primary">İlk hesabı ekle</a>
            </div>
        @else
            <form method="GET" class="filters section-gap" role="search">
                <label for="q" class="visually-hidden">Ara</label>
                <input id="q" name="q" type="search" class="input" value="{{ $filters['q'] ?? '' }}" placeholder="Ara">
                <label for="platform" class="visually-hidden">Platform</label>
                <select id="platform" name="platform" class="select" data-autosubmit>
                    <option value="">Tüm platformlar</option>
                    @foreach ($platforms as $key => $p)
                        <option value="{{ $key }}" @selected(($filters['platform'] ?? '') === $key)>{{ $p['label'] }}</option>
                    @endforeach
                </select>
            </form>

            @if ($accounts->isEmpty())
                <div class="empty"><p>Bu aramaya uyan hesap yok.</p></div>
            @else
                <ul class="list" role="list">
                    @foreach ($accounts as $account)
                        <li class="{{ $account->is_active ? '' : 'is-dim' }}">
                            <a href="{{ route('admin.accounts.edit', $account) }}" class="list__row">
                                <span>
                                    <span class="list__title">{{ $account->title }}</span>
                                    <span class="list__sub">{{ implode(', ', array_filter([
                                        $account->platformLabel(),
                                        $account->hasMailbox() ? 'mail' : null,
                                        $account->hasTotp() ? '2FA' : null,
                                        $account->is_active ? null : 'durduruldu',
                                    ])) }}</span>
                                </span>
                                <span class="list__meta"><strong>{{ $account->unused_count }}</strong>satılmamış</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif
    </section>
</x-layouts.app>
