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
            <div class="table-wrap">
                <table class="table table--stack">
                    <thead>
                        <tr>
                            <th scope="col">Paket</th>
                            <th scope="col">İçindeki hesaplar</th>
                            <th scope="col" class="num">Satılmamış anahtar</th>
                            <th scope="col"><span class="visually-hidden">İşlem</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($packages as $package)
                            <tr class="{{ $package->is_active ? '' : 'is-dim' }}">
                                <td>
                                    <span class="cell-title">{{ $package->name }}</span>
                                    <span class="cell-sub">{{ $package->accounts->count() }} hesaptan {{ $package->pick_count }} tanesi seçilir{{ $package->is_active ? '' : ', kapalı' }}</span>
                                </td>
                                <td data-label="Hesaplar">{{ $package->accounts->pluck('title')->implode(', ') }}</td>
                                <td data-label="Satılmamış" class="num">{{ $package->unused_count }}</td>
                                <td class="actions">
                                    <a href="{{ route('admin.keys.index', ['hedef' => 'package:'.$package->id]) }}" class="btn btn--secondary btn--s">Anahtar üret</a>
                                    <a href="{{ route('admin.packages.edit', $package) }}" class="btn btn--ghost btn--s">Düzenle</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.app>
