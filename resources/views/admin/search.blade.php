@php $total = $keys->count() + $customers->count() + $accounts->count(); @endphp
<x-layouts.app title="Arama">
    <header class="head">
        <div>
            <h1 class="words">Arama</h1>
            <p>Tam anahtar, anahtar notu, müşteri adı ya da e-postası, hesap adı.</p>
        </div>
    </header>

    <form method="GET" action="{{ route('admin.search') }}" class="search search--page" role="search">
        <label for="search-q" class="visually-hidden">Ara</label>
        <input id="search-q" name="q" type="search" class="input input--l" value="{{ $q }}" placeholder="Ne arıyorsun?" autofocus>
        <button type="submit" class="btn btn--primary btn--l">Ara</button>
    </form>

    @if (mb_strlen($q) < 2)
        <div class="blank blank--s"><p>Aramak için en az iki karakter yaz.</p></div>
    @elseif ($total === 0)
        <div class="blank blank--s"><p>“{{ $q }}” için sonuç yok. Anahtarlar yalnızca tam hâliyle ya da notuyla bulunur.</p></div>
    @else
        @if ($accounts->isNotEmpty())
            <section class="panel" aria-labelledby="found-accounts">
                <div class="panel__head"><h2 id="found-accounts">Hesaplar</h2></div>
                <ul class="rows stagger" role="list">
                    @foreach ($accounts as $account)
                        <li>
                            <a href="{{ route('admin.accounts.edit', $account) }}" class="rowlink">
                                <span class="plat plat--s" data-p="{{ $account->platform }}" aria-hidden="true">{{ $account->platformMark() }}</span>
                                <span><strong>{{ $account->title }}</strong><small>{{ $account->platformLabel() }}{{ $account->mail_username ? ', '.$account->mail_username : '' }}</small></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($customers->isNotEmpty())
            <section class="panel" aria-labelledby="found-customers">
                <div class="panel__head"><h2 id="found-customers">Müşteriler</h2></div>
                <ul class="rows stagger" role="list">
                    @foreach ($customers as $customer)
                        <li>
                            <a href="{{ route('admin.customers.index', ['q' => $customer->email]) }}" class="rowlink">
                                <span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($customer->name, 0, 1)) }}</span>
                                <span><strong>{{ $customer->name }}</strong><small>{{ $customer->email }}, {{ $customer->keys_count }} anahtar</small></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($keys->isNotEmpty())
            <section class="panel" aria-labelledby="found-keys">
                <div class="panel__head"><h2 id="found-keys">Anahtarlar</h2></div>
                <ul class="rows stagger" role="list">
                    @foreach ($keys as $key)
                        <li>
                            <a href="{{ route('admin.keys.index', ['q' => $key->code]) }}" class="rowlink">
                                <span><strong class="mono">{{ $key->code }}</strong><small>{{ implode(', ', array_filter([$key->targetTitle(), $key->statusLabel(), $key->customer?->email, $key->label])) }}</small></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    @endif
</x-layouts.app>
