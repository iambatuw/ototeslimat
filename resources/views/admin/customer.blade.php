{{-- Müşteri ayrıntısı: anahtarları, kalan hakları, istekleri ve son hareketleri tek sayfada. --}}
<x-layouts.app :title="$customer->name">
    <a href="{{ route('admin.customers.index') }}" class="back">Müşterilere dön</a>

    <header class="head customer-head">
        <div class="customer-head__who">
            <span @class(['avatar', 'avatar--xl', 'is-off' => ! $customer->is_active]) aria-hidden="true">{{ mb_strtoupper(mb_substr($customer->name, 0, 1)) }}</span>
            <div>
                <h1>{{ $customer->name }}</h1>
                <p>{{ $customer->email }} · <span @class(['state', 'state--on' => $customer->is_active, 'state--bad' => ! $customer->is_active])>{{ $customer->is_active ? 'Aktif' : 'Engelli' }}</span></p>
            </div>
        </div>
        <div class="page-head__actions">
            <a href="{{ route('admin.logs', ['q' => $customer->email]) }}" class="btn btn--secondary">Tüm kayıtları</a>
            <form method="POST" action="{{ route('admin.customers.toggle', $customer) }}" @if ($customer->is_active) data-confirm="{{ $customer->email }} engellensin mi? Giriş yapamaz ve kod alamaz." @endif>
                @csrf @method('PATCH')
                <button type="submit" class="btn {{ $customer->is_active ? 'btn--danger' : 'btn--secondary' }}">{{ $customer->is_active ? 'Engelle' : 'Engeli kaldır' }}</button>
            </form>
        </div>
    </header>

    <ul class="kpis" role="list">
        <li><span>Kayıt</span><strong>{{ $customer->created_at->format('d.m.Y') }}</strong></li>
        <li><span>Son giriş</span><strong>{{ $customer->last_login_at?->diffForHumans() ?? 'Hiç' }}</strong></li>
        <li><span>Anahtar</span><strong>{{ $keys->count() }}</strong></li>
        <li><span>Aldığı kod</span><strong>{{ number_format($stats['codes'], 0, ',', '.') }}</strong></li>
        <li><span>Hata (7 gün)</span><strong>{{ $stats['failed'] }}</strong></li>
        <li @class(['is-warn' => $stats['ips'] >= \App\Support\Alerts::sharedThreshold()])><span>Farklı IP (7 gün)</span><strong>{{ $stats['ips'] }}</strong></li>
    </ul>

    @if ($requests->isNotEmpty())
        <section class="panel" aria-labelledby="requests-title">
            <div class="panel__head"><h2 id="requests-title">Ek hak istekleri</h2></div>
            <ul class="rows" role="list">
                @foreach ($requests as $ask)
                    @include('admin.partials.request-row', ['ask' => $ask])
                @endforeach
            </ul>
        </section>
    @endif

    <section class="panel" aria-labelledby="keys-title">
        <div class="panel__head">
            <h2 id="keys-title">Anahtarları</h2>
            <span class="muted small">+1 hak ver ve +30 gün müşteriye hemen yansır</span>
        </div>
        @if ($keys->isEmpty())
            <div class="blank blank--s"><p>Bu müşteri henüz anahtar eklemedi.</p></div>
        @else
            @include('admin.partials.key-table', ['keys' => $keys, 'showCustomer' => false])
        @endif
    </section>

    <section class="panel panel--scroll" aria-labelledby="logs-title">
        <div class="panel__head">
            <h2 id="logs-title">Son hareketler</h2>
            <a href="{{ route('admin.logs', ['q' => $customer->email]) }}" class="btn btn--secondary btn--s">Tümünü gör</a>
        </div>
        @if ($logs->isEmpty())
            <div class="blank blank--s"><p>Henüz hareket yok.</p></div>
        @else
            @include('admin.partials.log-table', ['logs' => $logs])
        @endif
    </section>
</x-layouts.app>
