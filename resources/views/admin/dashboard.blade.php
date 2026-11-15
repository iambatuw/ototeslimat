@php
    $max = max(1, $series->max('value'));
    $w = 1000; $h = 220; $pad = 8;
    $step = ($w - $pad * 2) / max(1, $series->count() - 1);
    $points = $series->values()->map(fn ($p, $i) => [
        round($pad + $i * $step, 1),
        round($h - $pad - ($p['value'] / $max) * ($h - $pad * 2), 1),
    ]);
    $line = $points->map(fn ($p) => $p[0].','.$p[1])->implode(' ');
    $area = 'M'.$points->first()[0].','.($h - $pad).' L'.$points->map(fn ($p) => $p[0].','.$p[1])->implode(' L').' L'.$points->last()[0].','.($h - $pad).' Z';
    $total = $series->sum('value');
@endphp
<x-layouts.app title="Panel">
    <div class="head">
        <div>
            <h1>Panel</h1>
            <p>Satılmayı bekleyen anahtarlar, verilen kodlar ve son hareketler.</p>
        </div>
        <a href="{{ route('admin.accounts.index') }}" class="btn btn--primary">Anahtar üret</a>
    </div>

    @if ($failing > 0)
        <p class="notice notice--error" role="alert">
            Son 24 saatte {{ $failing }} kod isteği başarısız oldu.
            <a href="{{ route('admin.logs', ['event' => 'code.failed']) }}">Nedenlerine bak</a>
        </p>
    @endif

    <dl class="figures">
        <div><dt>Satılmadı</dt><dd class="figure">{{ number_format($stats['unused'], 0, ',', '.') }}</dd></div>
        <div><dt>Kullanımda</dt><dd class="figure">{{ number_format($stats['active'], 0, ',', '.') }}</dd></div>
        <div><dt>Bu ay verilen kod</dt><dd class="figure">{{ number_format($stats['codes_month'], 0, ',', '.') }}</dd></div>
        <div><dt>Açık hesap</dt><dd class="figure">{{ $stats['accounts'] }}</dd></div>
        <div><dt>Müşteri</dt><dd class="figure">{{ number_format($stats['customers'], 0, ',', '.') }}</dd></div>
    </dl>

    <section class="panel chart" aria-labelledby="chart-title">
        <div class="panel__head">
            <h2 id="chart-title" class="section">Son 14 gün</h2>
            <p class="muted small">{{ $total }} kod verildi, en yoğun gün {{ $series->max('value') }}</p>
        </div>
        <figure class="chart__plot">
            <svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" role="img" aria-label="Son 14 günde günlük verilen kod sayısı">
                @foreach ([0.25, 0.5, 0.75] as $g)
                    <line x1="0" x2="{{ $w }}" y1="{{ $pad + ($h - $pad * 2) * $g }}" y2="{{ $pad + ($h - $pad * 2) * $g }}" class="chart__grid" />
                @endforeach
                <path d="{{ $area }}" class="chart__area" />
                <polyline points="{{ $line }}" class="chart__line" vector-effect="non-scaling-stroke" />
            </svg>
            <figcaption class="chart__axis" aria-hidden="true">
                @foreach ($series as $i => $p)
                    <span @class(['is-sparse' => $i % 2 === 1])>{{ $p['label'] }}</span>
                @endforeach
            </figcaption>
        </figure>
        <table class="visually-hidden">
            <caption>Günlük verilen kod</caption>
            <tr><th scope="col">Gün</th><th scope="col">Kod</th></tr>
            @foreach ($series as $p)<tr><td>{{ $p['label'] }}</td><td>{{ $p['value'] }}</td></tr>@endforeach
        </table>
    </section>

    <section class="panel" aria-labelledby="recent-title">
        <div class="panel__head">
            <h2 id="recent-title" class="section">Son hareketler</h2>
            <a href="{{ route('admin.logs') }}" class="btn btn--ghost btn--s">Tüm kayıtlar</a>
        </div>
        @if ($recent->isEmpty())
            <p class="muted">Müşteriler anahtar ekleyip kod aldıkça burada görünür.</p>
        @else
            @include('admin.partials.log-table', ['logs' => $recent])
        @endif
    </section>
</x-layouts.app>
