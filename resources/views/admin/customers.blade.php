<x-layouts.app title="Müşteriler">
    <header class="head">
        <div>
            <h1>Müşteriler</h1>
            <p>Kayıtlı müşteriler. Engellediğin müşteri giriş yapamaz ve kod alamaz.</p>
        </div>
    </header>

    <div class="toolbar">
        @include('admin.partials.people-tabs')
        <form method="GET" class="search" role="search">
            <label for="q" class="visually-hidden">Ara</label>
            <input id="q" name="q" type="search" class="input" value="{{ $filters['q'] ?? '' }}" placeholder="Ad ya da e-posta ara">
            <button type="submit" class="btn btn--secondary btn--s">Ara</button>
        </form>
    </div>

    @if ($customers->isEmpty())
        <div class="blank">
            <div class="blank__art" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
            <h2>{{ array_filter($filters) ? 'Bu aramaya uyan müşteri yok' : 'Henüz müşteri yok' }}</h2>
            <p>Müşteriler siteye kayıt olup anahtar ekledikçe burada listelenir.</p>
        </div>
    @else
        <section class="panel">
            <ul class="people stagger" role="list">
                @foreach ($customers as $customer)
                    <li @class(['person', 'is-dim' => ! $customer->is_active])>
                        <span class="avatar avatar--l" aria-hidden="true">{{ mb_strtoupper(mb_substr($customer->name, 0, 1)) }}</span>
                        <div class="person__who">
                            <p class="person__name"><a href="{{ route('admin.customers.show', $customer) }}" class="person__link">{{ $customer->name }}</a></p>
                            <p class="person__mail">{{ $customer->email }}</p>
                        </div>
                        <dl class="person__facts">
                            <div><dt>Anahtar</dt><dd>{{ $customer->keys_count }}</dd></div>
                            <div><dt>Kayıt</dt><dd>{{ $customer->created_at->format('d.m.Y') }}</dd></div>
                            <div><dt>Son giriş</dt><dd>{{ $customer->last_login_at?->diffForHumans() ?? 'Hiç' }}</dd></div>
                        </dl>
                        <span @class(['state', 'state--on' => $customer->is_active, 'state--bad' => ! $customer->is_active])>{{ $customer->is_active ? 'Aktif' : 'Engelli' }}</span>
                        <div class="person__actions">
                            <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn--secondary btn--s">Ayrıntı</a>
                            <form method="POST" action="{{ route('admin.customers.toggle', $customer) }}" @if ($customer->is_active) data-confirm="{{ $customer->email }} engellensin mi?" @endif>
                                @csrf @method('PATCH')
                                <button type="submit" class="btn {{ $customer->is_active ? 'btn--danger' : 'btn--secondary' }} btn--s">{{ $customer->is_active ? 'Engelle' : 'Engeli kaldır' }}</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
            <x-pager :paginator="$customers" />
        </section>
    @endif
</x-layouts.app>
