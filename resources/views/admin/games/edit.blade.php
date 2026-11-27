<x-layouts.app :title="$game->name">
    <a href="{{ route('admin.games.index') }}" class="back">Oyunlara dön</a>
    <header class="head">
        <div>
            <h1>{{ $game->name }}</h1>
            <p>{{ $game->platformLabel() }} · {{ $game->accounts_count }} hesap bu oyuna bağlı. Hesabı oyuna bağlamak için hesabın düzenleme sayfasında "Katalogdaki oyun"u seç.</p>
        </div>
    </header>

    <div class="game-edit">
        <form method="POST" action="{{ route('admin.games.update', $game) }}" enctype="multipart/form-data" class="card stack" data-busy novalidate>
            @csrf @method('PUT')
            @include('admin.games.fields', ['game' => $game])
            @if ($game->cover)
                <input type="hidden" name="remove_cover" value="0">
                <label class="check"><input type="checkbox" name="remove_cover" value="1"> Kapağı kaldır</label>
            @endif
            <div><button type="submit" class="btn btn--primary" data-busy-text="Kaydediliyor">Kaydet</button></div>
        </form>

        <div class="gcard gcard--preview" data-p="{{ $game->platform }}" aria-label="Önizleme">
            <div class="gcard__cover">
                @if ($game->coverUrl())
                    <img src="{{ $game->coverUrl() }}" alt="" referrerpolicy="no-referrer">
                @else
                    <span class="gcard__blank" aria-hidden="true"><span>{{ $game->platformMark() }}</span>Kapak yok</span>
                @endif
                @if ($game->featured)<span class="gcard__flag">Öne çıkan</span>@endif
            </div>
            <div class="gcard__body">
                <p class="gcard__name">{{ $game->name }}</p>
                <p class="gcard__meta">{{ $game->platformLabel() }}</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.games.destroy', $game) }}" class="danger" data-confirm="{{ $game->name }} katalogdan silinsin mi? Bağlı hesaplar kalır.">
        @csrf @method('DELETE')
        <p class="muted small">Oyunu silersen bağlı hesaplar ve anahtarlar çalışmaya devam eder; yalnızca kapak bağlantısı kalkar.</p>
        <button type="submit" class="btn btn--danger btn--s">Oyunu sil</button>
    </form>
</x-layouts.app>
