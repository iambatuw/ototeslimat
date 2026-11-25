<x-layouts.app title="Kayıtlar">
    <header class="head">
        <div>
            <h1>Kayıtlar</h1>
            <p>Kim, ne zaman, hangi adresten ne yaptı. Müşteri “kod gelmedi” derse önce buraya bak.</p>
        </div>
    </header>

    <div class="toolbar">
        @include('admin.partials.people-tabs')
        <form method="GET" class="search" role="search">
            <label for="q" class="visually-hidden">Ara</label>
            <input id="q" name="q" type="search" class="input" value="{{ $filters['q'] ?? '' }}" placeholder="Anahtar, IP ya da e-posta ara">
            <label for="event" class="visually-hidden">Olay</label>
            <select id="event" name="event" class="select" data-autosubmit>
                <option value="">Tüm olaylar</option>
                @foreach (\App\Models\ActivityLog::EVENTS as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['event'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn--secondary btn--s">Ara</button>
        </form>
    </div>

    @if ($logs->isEmpty())
        <div class="blank blank--s"><p>{{ array_filter($filters) ? 'Bu aramaya uyan kayıt yok.' : 'Müşteriler anahtar ekleyip kod aldıkça her adım burada görünür.' }}</p></div>
    @else
        <section class="panel">
            @include('admin.partials.log-table', ['logs' => $logs])
            <x-pager :paginator="$logs" />
        </section>
    @endif
</x-layouts.app>
