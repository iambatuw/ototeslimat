<x-layouts.app title="Paketler">
    <header class="head">
        <div>
            <h1>Paketler</h1>
            <p>Tek anahtarla müşterinin listeden birkaç hesap seçtiği ürünler.</p>
        </div>
        <a href="{{ route('admin.packages.create') }}" class="btn btn--primary">Paket oluştur</a>
    </header>

    <div class="toolbar">
        @include('admin.partials.catalog-tabs')
    </div>

    @if ($packages->isEmpty())
        <div class="blank">
            <div class="blank__art" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
            <h2>Henüz paket yok</h2>
            <p>Örneğin “3 platform seç” paketine Netflix, Disney+, HBO Max ve Prime hesaplarını ekle; müşteri istediği üçünü kendisi seçsin.</p>
            <a href="{{ route('admin.packages.create') }}" class="btn btn--primary">İlk paketi oluştur</a>
        </div>
    @else
        <ul class="tiles tiles--wide stagger" role="list">
            @foreach ($packages as $package)
                <li>
                    <a href="{{ route('admin.packages.edit', $package) }}" @class(['tile', 'spot', 'is-dim' => ! $package->is_active])>
                        <span class="tile__top">
                            <span class="plat plat--pack" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/></svg>
                            </span>
                            <span @class(['state', 'state--on' => $package->is_active])>{{ $package->is_active ? 'Açık' : 'Kapalı' }}</span>
                        </span>
                        <span class="tile__title">{{ $package->name }}</span>
                        <span class="tile__sub">{{ $package->accounts->count() }} hesaptan {{ $package->pick_count }} seçim</span>
                        <span class="chips">
                            @foreach ($package->accounts->take(6) as $account)<span class="chip">{{ $account->title }}</span>@endforeach
                            @if ($package->accounts->count() > 6)<span class="chip">+{{ $package->accounts->count() - 6 }}</span>@endif
                        </span>
                        <span class="tile__foot"><strong>{{ $package->unused_count }}</strong> satılmamış anahtar</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>
