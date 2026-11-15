@php $platforms = config('guard.platforms'); @endphp
<x-layouts.app title="Hesaplar">
    <div class="page-head">
        <div>
            <h1>Hesaplar</h1>
            <p>Sattığın hesaplar ve kodlarının nereden geldiği.</p>
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
            <form method="GET" class="filters" role="search">
                <label for="q" class="visually-hidden">Ara</label>
                <input id="q" name="q" type="search" class="input" value="{{ $filters['q'] ?? '' }}" placeholder="Ad ya da e-posta ara">
                <label for="platform" class="visually-hidden">Platform</label>
                <select id="platform" name="platform" class="select" data-autosubmit>
                    <option value="">Tüm platformlar</option>
                    @foreach ($platforms as $key => $p)
                        <option value="{{ $key }}" @selected(($filters['platform'] ?? '') === $key)>{{ $p['label'] }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn--secondary btn--s">Ara</button>
            </form>

            @if ($accounts->isEmpty())
                <div class="empty"><p>Bu aramaya uyan hesap yok.</p></div>
            @else
                <div class="table-wrap">
                    <table class="table table--stack">
                        <thead>
                            <tr>
                                <th scope="col">Hesap</th>
                                <th scope="col">Kod nereden gelir</th>
                                <th scope="col" class="num">Satılmamış anahtar</th>
                                <th scope="col">Son kod</th>
                                <th scope="col"><span class="visually-hidden">İşlem</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($accounts as $account)
                                <tr class="{{ $account->is_active ? '' : 'is-dim' }}">
                                    <td>
                                        <span class="cell-title">{{ $account->title }}</span>
                                        <span class="cell-sub">{{ $account->platformLabel() }}{{ $account->is_active ? '' : ', durduruldu' }}</span>
                                    </td>
                                    <td data-label="Kod">
                                        @if ($account->hasMailbox())<span class="badge">Mail</span>@endif
                                        @if ($account->hasTotp())<span class="badge">2FA</span>@endif
                                        @if ($account->mail_username)<span class="cell-sub">{{ $account->mail_username }}</span>@endif
                                    </td>
                                    <td data-label="Satılmamış" class="num">{{ $account->unused_count }}</td>
                                    <td data-label="Son kod">{{ $account->last_code_at?->diffForHumans() ?? 'Henüz yok' }}</td>
                                    <td class="actions">
                                        <a href="{{ route('admin.keys.index', ['hedef' => 'account:'.$account->id]) }}" class="btn btn--secondary btn--s">Anahtar üret</a>
                                        <a href="{{ route('admin.accounts.edit', $account) }}" class="btn btn--ghost btn--s">Düzenle</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </section>
</x-layouts.app>
