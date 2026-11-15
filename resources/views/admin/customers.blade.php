<x-layouts.app title="Müşteriler">
    <div class="page-head">
        <div>
            <h1>Müşteriler</h1>
            <p>Kayıtlı müşteriler. Engellediğin müşteri giriş yapamaz ve kod alamaz.</p>
        </div>
    </div>

    @include('admin.partials.people-tabs')

    <section class="card card--flush">
        <form method="GET" class="filters" role="search">
            <label for="q" class="visually-hidden">Ara</label>
            <input id="q" name="q" type="search" class="input" value="{{ $filters['q'] ?? '' }}" placeholder="Ad ya da e-posta ara">
            <button type="submit" class="btn btn--secondary btn--s">Ara</button>
        </form>

        @if ($customers->isEmpty())
            <div class="empty">
                <h2>{{ array_filter($filters) ? 'Bu aramaya uyan müşteri yok' : 'Henüz müşteri yok' }}</h2>
                <p>Müşteriler siteye kayıt olup anahtar ekledikçe burada listelenir.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="table table--stack">
                    <thead>
                        <tr>
                            <th scope="col">Müşteri</th>
                            <th scope="col" class="num">Anahtar</th>
                            <th scope="col">Kayıt</th>
                            <th scope="col">Son giriş</th>
                            <th scope="col"><span class="visually-hidden">İşlem</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr class="{{ $customer->is_active ? '' : 'is-dim' }}">
                                <td>
                                    <span class="cell-title">{{ $customer->name }}</span>
                                    <span class="cell-sub">{{ $customer->email }}{{ $customer->is_active ? '' : ', engelli' }}</span>
                                </td>
                                <td data-label="Anahtar" class="num">{{ $customer->keys_count }}</td>
                                <td data-label="Kayıt">{{ $customer->created_at->format('d.m.Y') }}</td>
                                <td data-label="Son giriş">{{ $customer->last_login_at?->diffForHumans() ?? 'Hiç' }}</td>
                                <td class="actions">
                                    <a href="{{ route('admin.logs', ['q' => $customer->email]) }}" class="btn btn--secondary btn--s">Hareketleri</a>
                                    <form method="POST" action="{{ route('admin.customers.toggle', $customer) }}" @if ($customer->is_active) data-confirm="{{ $customer->email }} engellensin mi?" @endif>
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn {{ $customer->is_active ? 'btn--danger' : 'btn--secondary' }} btn--s">{{ $customer->is_active ? 'Engelle' : 'Engeli kaldır' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pager :paginator="$customers" />
        @endif
    </section>
</x-layouts.app>
