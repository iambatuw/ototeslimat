<x-layouts.app title="Paketler">
    <div class="page-head">
        <div>
            <h1>Paketler</h1>
            <p>Tek anahtarla müşterinin listeden birkaç hesap seçtiği ürünler.</p>
        </div>
        <a href="{{ route('admin.packages.create') }}" class="btn btn--primary">Paket oluştur</a>
    </div>

    @include('admin.partials.catalog-tabs')

    <section class="card card--flush">
        @if ($packages->isEmpty())
            <div class="empty">
                <h2>Henüz paket yok</h2>
                <p>Örneğin "3 platform seç" paketine Netflix, Disney+, HBO Max ve Prime hesaplarını ekle; müşteri istediği üçünü kendisi seçsin.</p>
                <a href="{{ route('admin.packages.create') }}" class="btn btn--primary">İlk paketi oluştur</a>
            </div>
        @else
            <ul class="list" role="list">
                @foreach ($packages as $package)
                    <li class="{{ $package->is_active ? '' : 'is-dim' }}">
                        <a href="{{ route('admin.packages.edit', $package) }}" class="list__row">
                            <span>
                                <span class="list__title">{{ $package->name }}</span>
                                <span class="list__sub">{{ $package->accounts->count() }} hesaptan {{ $package->pick_count }} seçim: {{ $package->accounts->pluck('title')->implode(', ') }}</span>
                            </span>
                            <span class="list__meta"><strong>{{ $package->unused_count }}</strong>satılmamış</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts.app>
