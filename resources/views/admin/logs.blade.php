<x-layouts.app title="Kayıtlar">
    <div class="page-head">
        <div>
            <h1>Kayıtlar</h1>
            <p>Kim, ne zaman, hangi adresten ne yaptı. Müşteri "kod gelmedi" derse önce buraya bak.</p>
        </div>
    </div>

    @include('admin.partials.people-tabs')

    <section class="card card--flush">
        <form method="GET" class="filters" role="search">
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

        @if ($logs->isEmpty())
            <div class="empty"><p>{{ array_filter($filters) ? 'Bu aramaya uyan kayıt yok.' : 'Müşteriler anahtar ekleyip kod aldıkça her adım burada görünür.' }}</p></div>
        @else
            @include('admin.partials.log-table', ['logs' => $logs])
            <x-pager :paginator="$logs" />
        @endif
    </section>
</x-layouts.app>
