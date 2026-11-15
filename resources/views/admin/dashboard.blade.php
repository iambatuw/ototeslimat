@php
    $max = max(1, $series->max('value'));
    $w = 1000; $h = 200; $pad = 8;
    $step = ($w - $pad * 2) / max(1, $series->count() - 1);
    $points = $series->values()->map(fn ($p, $i) => [round($pad + $i * $step, 1), round($h - $pad - ($p['value'] / $max) * ($h - $pad * 2), 1)]);
    $line = $points->map(fn ($p) => $p[0].','.$p[1])->implode(' ');
    $area = 'M'.$points->first()[0].','.($h - $pad).' L'.$points->map(fn ($p) => $p[0].','.$p[1])->implode(' L').' L'.$points->last()[0].','.($h - $pad).' Z';
    $ready = $setup['account'] && $setup['key'] && $setup['sold'];
@endphp
<x-layouts.app title="Panel">
    <div class="page-head">
        <div>
            <h1>Panel</h1>
            <p>Mağazanın kısa özeti.</p>
        </div>
        <a href="{{ route('admin.keys.index') }}" class="btn btn--primary">Anahtar üret</a>
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
            <ol class="setup">
                <li @class(['is-done' => $setup['account']])>
                    <div>
                        <p class="setup__title">Sattığın hesabı ekle</p>
                        <p class="setup__text">Kodun geldiği e-posta kutusunu ya da 2FA anahtarını gir.</p>
                    </div>
                    @unless ($setup['account'])<a href="{{ route('admin.accounts.create') }}" class="btn btn--primary btn--s">Hesap ekle</a>@endunless
                </li>
                <li @class(['is-done' => $setup['key']])>
                    <div>
                        <p class="setup__title">Anahtar üret</p>
                        <p class="setup__text">Her müşterine bir anahtar verirsin. Anahtar o hesaba bağlanır.</p>
                    </div>
                    @if ($setup['account'] && ! $setup['key'])<a href="{{ route('admin.keys.index') }}" class="btn btn--primary btn--s">Anahtar üret</a>@endif
                </li>
                <li @class(['is-done' => $setup['sold']])>
                    <div>
                        <p class="setup__title">Anahtarı müşterine gönder</p>
                        <p class="setup__text">Müşteri siteye kayıt olup anahtarı ekler, kodu kendisi alır.</p>
                    </div>
                </li>
            </ol>
        </section>
    @endunless

    <dl class="figures section-gap">
        <div><dt>Satılmayı bekleyen anahtar</dt><dd>{{ number_format($stats['unused'], 0, ',', '.') }}</dd></div>
        <div><dt>Kullanımdaki anahtar</dt><dd>{{ number_format($stats['active'], 0, ',', '.') }}</dd></div>
        <div><dt>Bu ay verilen kod</dt><dd>{{ number_format($stats['codes_month'], 0, ',', '.') }}</dd></div>
        <div><dt>Müşteri</dt><dd>{{ number_format($stats['customers'], 0, ',', '.') }}</dd></div>
    </dl>

    <section class="card" aria-labelledby="chart-title">
        <div class="card__head">
            <h2 id="chart-title" class="card__title">Son 14 günde verilen kod</h2>
            <span class="muted small">Toplam {{ $series->sum('value') }}</span>
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
                @foreach ($series as $i => $p)<span @class(['is-sparse' => $i % 2 === 1])>{{ $p['label'] }}</span>@endforeach
            </figcaption>
        </figure>
    </section>

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
