<x-layouts.app title="Kayıtlar">
    <div class="head">
        <div>
            <h1>Kayıtlar</h1>
            <p>Kim, hangi anahtarla, ne zaman ve hangi adresten işlem yaptı. Müşteri "kod gelmedi" derse önce buraya bak.</p>
        </div>
    </div>

    <form method="GET" class="filters" role="search">
        <div class="field field--grow">
            <label for="q">Ara</label>
            <input id="q" name="q" type="search" class="input" value="{{ $filters['q'] ?? '' }}" placeholder="Tam anahtar, IP ya da e-posta">
        </div>
        <div class="field">
            <label for="event">Olay</label>
            <select id="event" name="event" class="select">
                <option value="">Tümü</option>
                @foreach (\App\Models\ActivityLog::EVENTS as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['event'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn--ghost">Süz</button>
    </form>

    @if ($logs->isEmpty())
        <div class="empty">
            <h2>{{ array_filter($filters) ? 'Bu süzgece uyan kayıt yok' : 'Henüz kayıt yok' }}</h2>
            <p>{{ array_filter($filters) ? 'Süzgeci temizleyip tekrar bak.' : 'Müşteriler anahtar ekleyip kod aldıkça her adım burada görünür.' }}</p>
        </div>
    @else
        @include('admin.partials.log-table', ['logs' => $logs])
        <x-pager :paginator="$logs" />
    @endif
</x-layouts.app>
