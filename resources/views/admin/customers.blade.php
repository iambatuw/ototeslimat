<x-layouts.app title="Müşteriler">
    <div class="head">
        <div>
            <h1>Müşteriler</h1>
            <p>Kayıtlı müşteriler. Engellediğin müşteri giriş yapamaz ve kod alamaz.</p>
        </div>
    </div>

    <form method="GET" class="filters" role="search">
        <div class="field field--grow">
            <label for="q">Ara</label>
            <input id="q" name="q" type="search" class="input" value="{{ $filters['q'] ?? '' }}" placeholder="Ad ya da e-posta">
        </div>
        <button type="submit" class="btn btn--ghost">Ara</button>
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
                            <td data-label="Son giriş">{{ $customer->last_login_at?->diffForHumans() ?? 'Hiç' }}{{ $customer->last_login_ip ? ', '.$customer->last_login_ip : '' }}</td>
                            <td class="actions">
                                <a href="{{ route('admin.logs', ['q' => $customer->email]) }}" class="btn btn--ghost btn--s">Kayıtlar</a>
                                <form method="POST" action="{{ route('admin.customers.toggle', $customer) }}" @if ($customer->is_active) data-confirm="{{ $customer->email }} engellensin mi?" @endif>
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn--ghost btn--s">{{ $customer->is_active ? 'Engelle' : 'Aç' }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-pager :paginator="$customers" />
    @endif
</x-layouts.app>
