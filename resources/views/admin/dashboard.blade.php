@php
    $max = max(1, $series->max('value'));
    $w = 1000; $h = 200; $pad = 8;
    $step = ($w - $pad * 2) / max(1, $series->count() - 1);
    $points = $series->values()->map(fn ($p, $i) => [round($pad + $i * $step, 1), round($h - $pad - ($p['value'] / $max) * ($h - $pad * 2), 1)]);
    $line = $points->map(fn ($p) => $p[0].','.$p[1])->implode(' ');
    $area = 'M'.$points->first()[0].','.($h - $pad).' L'.$points->map(fn ($p) => $p[0].','.$p[1])->implode(' L').' L'.$points->last()[0].','.($h - $pad).' Z';
    $ready = $setup['account'] && $setup['key'] && $setup['sold'];
    $now = ! $setup['account'] ? 'account' : (! $setup['key'] ? 'key' : 'sold');
    $total = $series->sum('value');
    $keys = $stats['unused'] + $stats['active'];
    $freeWidth = $keys ? round($stats['unused'] / $keys * 100, 1) : 0;
    $fmt = fn ($n) => number_format($n, 0, ',', '.');
@endphp
<x-layouts.app title="Panel">
    <div class="page-head">
        <div>
            <h1>Panel</h1>
            <p>Mağazanda bugün neler oluyor, tek bakışta.</p>
        </div>
        <div class="page-head__actions">
            <a href="{{ route('admin.accounts.create') }}" class="btn btn--secondary">Hesap ekle</a>
            <a href="{{ route('admin.keys.index') }}" class="btn btn--primary">Anahtar üret</a>
        </div>
    </div>

    @if ($failing > 0)
        <p class="notice notice--error" role="alert">
            Son 24 saatte {{ $failing }} kod isteği başarısız oldu. <a href="{{ route('admin.logs', ['event' => 'code.failed']) }}">Nedenlerine bak</a>
        </p>
    @endif

    @unless ($ready)
        <section class="card section-gap" aria-labelledby="setup-title">
            <div class="card__head">
                <div>
                    <h2 id="setup-title" class="card__title">Başlarken</h2>
                    <p class="card__lead">Mağazanı üç adımda satışa hazırla.</p>
                </div>
            </div>
            @php
                $steps = [
                    ['account', 'Sattığın hesabı ekle', 'Kodun geldiği e-posta kutusunu ya da 2FA anahtarını gir.', route('admin.accounts.create'), 'Hesap ekle'],
                    ['key', 'Anahtar üret', 'Her müşteriye bir anahtar verirsin. Anahtar o hesaba bağlanır.', route('admin.keys.index'), 'Anahtar üret'],
                    ['sold', 'Anahtarı müşterine gönder', 'Müşteri siteye kayıt olup anahtarı ekler, kodu kendisi alır.', null, null],
                ];
            @endphp
            <ol class="setup">
                @foreach ($steps as $i => [$id, $title, $text, $url, $action])
                    <li @class(['is-done' => $setup[$id], 'is-now' => ! $setup[$id] && $now === $id]) @if (! $setup[$id] && $now === $id) aria-current="step" @endif>
                        <span class="setup__dot">
                            @if ($setup[$id])
                                <svg viewBox="0 0 16 16" role="img" aria-label="Tamamlandı"><path d="m3 8.5 3.2 3L13 4.5"/></svg>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>
                        <p class="setup__title">{{ $title }}</p>
                        <p class="setup__text">{{ $text }}</p>
                        @if ($url && $now === $id)<a href="{{ $url }}" class="btn btn--primary btn--s">{{ $action }}</a>@endif
                    </li>
                @endforeach
            </ol>
        </section>
    @endunless

    <div class="overview">
    <section class="card" aria-labelledby="chart-title">
        <h2 id="chart-title" class="card__title">Verilen kodlar</h2>
        <p class="total"><strong>{{ $fmt($total) }}</strong> <span>son 14 günde, bu ay {{ $fmt($stats['codes_month']) }}</span></p>
        <figure class="chart__plot">
            <svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" role="img" aria-label="Son 14 günde günlük verilen kod sayısı">
                @foreach ([0.25, 0.5, 0.75] as $g)
                    <line x1="0" x2="{{ $w }}" y1="{{ $pad + ($h - $pad * 2) * $g }}" y2="{{ $pad + ($h - $pad * 2) * $g }}" class="chart__grid" />
                @endforeach
                <path d="{{ $area }}" class="chart__area" />
                <polyline points="{{ $line }}" class="chart__line" vector-effect="non-scaling-stroke" />
            </svg>
            <figcaption class="chart__axis" aria-hidden="true">
                @foreach ($series as $i => $p)<span @class(['is-sparse' => $i % 2 === 1])>{{ $p['label'] }}</span>@endforeach
            </figcaption>
        </figure>
    </section>

    <section class="card" aria-labelledby="stock-title">
        <h2 id="stock-title" class="card__title">Anahtarlar</h2>
        <p class="total"><strong>{{ $fmt($keys) }}</strong> <span>geçerli anahtar</span></p>
        <div class="stock">
            <svg viewBox="0 0 100 12" preserveAspectRatio="none" role="img" aria-label="{{ $stats['unused'] }} anahtar satılmayı bekliyor, {{ $stats['active'] }} anahtar kullanımda">
                @if ($keys)
                    <rect class="stock__free" width="{{ $freeWidth }}" height="12" />
                    <rect class="stock__used" x="{{ $freeWidth }}" width="{{ 100 - $freeWidth }}" height="12" />
                @endif
            </svg>
        </div>
        <ul class="facts">
            <li><a href="{{ route('admin.keys.index') }}"><span class="dot dot--free"></span>Satılmayı bekleyen <strong>{{ $fmt($stats['unused']) }}</strong></a></li>
            <li><a href="{{ route('admin.keys.index') }}"><span class="dot dot--used"></span>Kullanımda <strong>{{ $fmt($stats['active']) }}</strong></a></li>
            <li><a href="{{ route('admin.accounts.index') }}"><span class="dot"></span>Aktif hesap <strong>{{ $fmt($stats['accounts']) }}</strong></a></li>
            <li><a href="{{ route('admin.customers.index') }}"><span class="dot"></span>Müşteri <strong>{{ $fmt($stats['customers']) }}</strong></a></li>
        </ul>
    </section>
    </div>

    <section class="card card--flush section-gap" aria-labelledby="recent-title">
        <div class="card__head">
            <h2 id="recent-title" class="card__title">Son hareketler</h2>
            <a href="{{ route('admin.logs') }}" class="btn btn--secondary btn--s">Tümünü gör</a>
        </div>
        @if ($recent->isEmpty())
            <div class="empty"><p>Müşteriler anahtar ekleyip kod aldıkça burada görünür.</p></div>
        @else
            @include('admin.partials.log-table', ['logs' => $recent])
        @endif
    </section>
</x-layouts.app>
