<x-layouts.app title="Oyunlar">
    <header class="head">
        <div>
            <h1>Oyunlar</h1>
            <p>Sattığın oyunlar. Herkese açık <a href="{{ route('games') }}" target="_blank" rel="noopener">oyunlar sayfasında</a> kapaklarıyla görünür; bir hesabı oyuna bağlarsan müşterinin kartında da kapak çıkar.</p>
        </div>
        @if ($enabled)<a href="{{ route('games') }}" class="btn btn--secondary" target="_blank" rel="noopener">Sayfayı aç</a>@endif
    </header>

    @unless ($enabled)
        <p class="notice">Oyun kataloğu kapalı; oyunlar sayfası ziyaretçilere görünmüyor. <a href="{{ route('admin.settings', 'ozellikler') }}">Ayarlar &gt; Özellikler</a>'den aç.</p>
    @endunless

    <div class="game-admin">
        <form method="POST" action="{{ route('admin.games.store') }}" enctype="multipart/form-data" class="card stack" data-busy novalidate>
            @csrf
            <h2 class="card__title">Oyun ekle</h2>
            @include('admin.games.fields', ['game' => new \App\Models\Game(['platform' => old('platform', 'steam')])])
            <div><button type="submit" class="btn btn--primary" data-busy-text="Ekleniyor">Kataloğa ekle</button></div>
        </form>

        <details class="card more" @if ($errors->bulk->any()) open @endif>
            <summary>Çok oyunun mu var? Liste hâlinde ekle</summary>
            <form method="POST" action="{{ route('admin.games.bulk') }}" class="more__body stack" data-busy novalidate>
                @csrf
                <div class="field">
                    <label for="bulk-platform">Platform</label>
                    <select id="bulk-platform" name="platform" class="select">
                        @foreach ($platforms as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="names">Oyunlar <span class="optional">(her satıra bir oyun)</span></label>
                    <textarea id="names" name="names" class="textarea" rows="6" placeholder="Cyberpunk 2077&#10;Elden Ring&#10;Red Dead Redemption 2"></textarea>
                    <p class="{{ $errors->bulk->has('names') ? 'error' : 'hint' }}">{{ $errors->bulk->first('names') ?: 'Aynı platformda zaten olan oyunlar atlanır. Kapakları sonra tek tek eklersin.' }}</p>
                </div>
                <div><button type="submit" class="btn btn--secondary" data-busy-text="Ekleniyor">Listeyi ekle</button></div>
            </form>
        </details>
    </div>

    <div class="toolbar">
        <form method="GET" class="search" role="search">
            <label for="q" class="visually-hidden">Oyun ara</label>
            <input id="q" name="q" type="search" class="input" value="{{ $filters['q'] ?? '' }}" placeholder="Oyun ara">
            <label for="platform-filter" class="visually-hidden">Platform</label>
            <select id="platform-filter" name="platform" class="select select--s">
                <option value="">Tüm platformlar</option>
                @foreach ($platforms as $key => $label)<option value="{{ $key }}" @selected(($filters['platform'] ?? '') === $key)>{{ $label }}</option>@endforeach
            </select>
            <button type="submit" class="btn btn--secondary btn--s">Ara</button>
        </form>
        <span class="muted small">{{ $total }} oyun</span>
    </div>

    @if ($games->isEmpty())
        <div class="blank">
            <div class="blank__art" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
            <h2>{{ array_filter($filters) ? 'Bu aramaya uyan oyun yok' : 'Katalog boş' }}</h2>
            <p>Yukarıdan ilk oyununu ekle ya da listeni yapıştır.</p>
        </div>
    @else
        <ul class="gamegrid gamegrid--admin stagger" role="list">
            @foreach ($games as $game)
                <li @class(['gcard', 'is-dim' => ! $game->is_visible]) data-p="{{ $game->platform }}">
                    <a href="{{ route('admin.games.edit', $game) }}" class="gcard__cover" aria-label="{{ $game->name }} düzenle">
                        @if ($game->coverUrl())
                            <img src="{{ $game->coverUrl() }}" alt="" loading="lazy" referrerpolicy="no-referrer">
                        @else
                            <span class="gcard__blank" aria-hidden="true"><span>{{ $game->platformMark() }}</span>Kapak yok</span>
                        @endif
                        @if ($game->featured)<span class="gcard__flag">Öne çıkan</span>@endif
                        @unless ($game->is_visible)<span class="gcard__flag gcard__flag--off">Gizli</span>@endunless
                    </a>
                    <div class="gcard__body">
                        <p class="gcard__name">{{ $game->name }}</p>
                        <p class="gcard__meta">{{ $game->platformLabel() }} · {{ $game->accounts_count }} hesap</p>
                        <div class="gcard__do">
                            <a href="{{ route('admin.games.edit', $game) }}" class="btn btn--secondary btn--s">Düzenle</a>
                            <form method="POST" action="{{ route('admin.games.toggle', $game) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="field" value="featured">
                                <button type="submit" class="btn btn--ghost btn--s">{{ $game->featured ? 'Öne çıkanı kaldır' : 'Öne çıkar' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.games.toggle', $game) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="field" value="is_visible">
                                <button type="submit" class="btn btn--ghost btn--s">{{ $game->is_visible ? 'Gizle' : 'Göster' }}</button>
                            </form>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        <x-pager :paginator="$games" />
    @endif
</x-layouts.app>
