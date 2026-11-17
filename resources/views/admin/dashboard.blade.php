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
    <header class="head">
        <div>
            <h1 class="words">Panel</h1>
            <p>Mağazanda bugün neler oluyor, tek bakışta.</p>
        </div>
        <div class="page-head__actions">
            <form method="GET" action="{{ route('admin.search') }}" class="search search--head" role="search">
                <label for="head-q" class="visually-hidden">Ara</label>
                <input id="head-q" name="q" type="search" class="input" placeholder="Anahtar, müşteri ya da hesap ara">
            </form>
            <a href="{{ route('admin.accounts.create') }}" class="btn btn--secondary">Hesap ekle</a>
            <a href="{{ route('admin.keys.index') }}" class="btn btn--primary">Anahtar üret</a>
        </div>
    </header>

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
        <p class="total"><strong data-count="{{ $total }}">{{ $fmt($total) }}</strong> <span>son 14 günde, bu ay {{ $fmt($stats['codes_month']) }}</span></p>
        <figure class="chart__plot">
            <svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" role="img" aria-label="Son 14 günde günlük verilen kod sayısı">
                @foreach ([0.25, 0.5, 0.75] as $g)
                    <line x1="0" x2="{{ $w }}" y1="{{ $pad + ($h - $pad * 2) * $g }}" y2="{{ $pad + ($h - $pad * 2) * $g }}" class="chart__grid" />
                @endforeach
                <path d="{{ $area }}" class="chart__area" />
                <polyline points="{{ $line }}" pathLength="1" class="chart__line" vector-effect="non-scaling-stroke" />
            </svg>
            <figcaption class="chart__axis" aria-hidden="true">
                @foreach ($series as $i => $p)<span @class(['is-sparse' => $i % 2 === 1])>{{ $p['label'] }}</span>@endforeach
            </figcaption>
        </figure>
    </section>

    <section class="card" aria-labelledby="stock-title">
        <h2 id="stock-title" class="card__title">Anahtarlar</h2>
        <p class="total"><strong data-count="{{ $keys }}">{{ $fmt($keys) }}</strong> <span>geçerli anahtar</span></p>
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

    <div class="insights">
        <section class="panel" aria-labelledby="health-title">
            <div class="panel__head">
                <h2 id="health-title">Hesap sağlığı</h2>
                <span class="muted small">son 24 saat</span>
            </div>
            @if ($troubled->isEmpty())
                <p class="allgood"><span class="state state--on">Sorun yok</span> Kod veremeyen hesap yok.</p>
            @else
                <ul class="rows" role="list">
                    @foreach ($troubled as $row)
                        <li>
                            <a href="{{ route('admin.accounts.edit', $row->account) }}" class="rowlink">
                                <span class="plat plat--s" data-p="{{ $row->account->platform }}" aria-hidden="true">{{ mb_substr($row->account->platformLabel(), 0, 1) }}</span>
                                <span><strong>{{ $row->account->title }}</strong><small>{{ $row->reason ? 'Son neden: '.$row->reason : 'Kod verilemedi' }}{{ $row->issued ? ', '.$row->issued.' kod verildi' : '' }}</small></span>
                                <span class="state state--bad">{{ $row->failed }} hata</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="panel" aria-labelledby="stock-low-title">
            <div class="panel__head">
                <h2 id="stock-low-title">Stok</h2>
                <span class="muted small">{{ \App\Support\StoreInsights::lowStockThreshold() }} ve altı</span>
            </div>
            @if ($lowStock->isEmpty())
                <p class="allgood"><span class="state state--on">Yeterli</span> Anahtarı azalan hesap ya da paket yok.</p>
            @else
                <ul class="rows" role="list">
                    @foreach ($lowStock->take(5) as $item)
                        <li>
                            <a href="{{ route('admin.keys.index', ['hedef' => $item->target]) }}" class="rowlink">
                                <span><strong>{{ $item->title }}</strong><small>{{ $item->left === 0 ? 'Satılacak anahtar kalmadı' : $item->left.' anahtar kaldı' }}</small></span>
                                <span class="btn btn--secondary btn--s">Üret</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                @if ($lowStock->count() > 5)<p class="allgood">ve {{ $lowStock->count() - 5 }} tane daha.</p>@endif
            @endif
        </section>

        <section class="panel" aria-labelledby="platform-title">
            <div class="panel__head">
                <h2 id="platform-title">Platformlar</h2>
                <span class="muted small">son 30 gün</span>
            </div>
            @if ($platforms->isEmpty())
                <p class="allgood">Kod verildikçe platformlara göre dağılım burada görünür.</p>
            @else
                @php($top = max(1, $platforms->max('issued')))
                <ul class="bars" role="list">
                    @foreach ($platforms as $platform)
                        <li>
                            <span>{{ $platform->label }}</span>
                            <svg viewBox="0 0 100 8" preserveAspectRatio="none" aria-hidden="true"><rect width="{{ round($platform->issued / $top * 100, 1) }}" height="8" /></svg>
                            <span><strong>{{ $fmt($platform->issued) }}</strong>@if ($platform->failed) <em>{{ $platform->failed }} hata</em>@endif</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="panel" aria-labelledby="top-title">
            <div class="panel__head">
                <h2 id="top-title">En çok kod alanlar</h2>
                <span class="muted small">son 30 gün</span>
            </div>
            @if ($topCustomers->isEmpty())
                <p class="allgood">Müşteriler kod aldıkça burada sıralanır.</p>
            @else
                <ul class="rows" role="list">
                    @foreach ($topCustomers as $row)
                        <li>
                            <a href="{{ route('admin.logs', ['q' => $row->user->email, 'event' => 'code.issued']) }}" class="rowlink">
                                <span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($row->user->name, 0, 1)) }}</span>
                                <span><strong>{{ $row->user->name }}</strong><small>{{ $row->user->email }}</small></span>
                                <span class="count">{{ $fmt($row->codes) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- Kurulum adımları görünürken boş hareket kutusu yer kaplamasın. --}}
    @unless (! $ready && $recent->isEmpty())
    <section class="panel panel--scroll" aria-labelledby="recent-title">
        <div class="panel__head">
            <h2 id="recent-title">Son hareketler</h2>
            <a href="{{ route('admin.logs') }}" class="btn btn--secondary btn--s">Tümünü gör</a>
        </div>
        @if ($recent->isEmpty())
            <div class="blank blank--s"><p>Müşteriler anahtar ekleyip kod aldıkça burada görünür.</p></div>
        @else
            @include('admin.partials.log-table', ['logs' => $recent])
        @endif
    </section>
    @endunless
</x-layouts.app>
