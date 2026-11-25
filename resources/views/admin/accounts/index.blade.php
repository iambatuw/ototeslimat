@php $platforms = config('guard.platforms'); @endphp
<x-layouts.app title="Hesaplar">
    <header class="head">
        <div>
            <h1>Hesaplar</h1>
            <p>Sattığın hesaplar. Düzenlemek ya da anahtar üretmek için birine dokun.</p>
        </div>
        <a href="{{ route('admin.accounts.create') }}" class="btn btn--primary">Hesap ekle</a>
    </header>

    <div class="toolbar">
        @include('admin.partials.catalog-tabs')
        @unless ($accounts->isEmpty() && ! array_filter($filters))
            <form method="GET" class="toolbar__filters" role="search">
                <label for="q" class="visually-hidden">Ara</label>
                <input id="q" name="q" type="search" class="input" value="{{ $filters['q'] ?? '' }}" placeholder="Hesap ara">
                <label for="platform" class="visually-hidden">Platform</label>
                <select id="platform" name="platform" class="select" data-autosubmit>
                    <option value="">Tüm platformlar</option>
                    @foreach ($platforms as $key => $p)
                        <option value="{{ $key }}" @selected(($filters['platform'] ?? '') === $key)>{{ $p['label'] }}</option>
                    @endforeach
                </select>
            </form>
        @endunless
    </div>

    @if ($accounts->isEmpty() && ! array_filter($filters))
        <div class="blank">
            <div class="blank__art" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
            <h2>Henüz hesap yok</h2>
            <p>Sattığın hesabın kodlarının geldiği e-posta kutusunu ya da 2FA anahtarını ekle. Sonra o hesap için anahtar üretirsin.</p>
            <a href="{{ route('admin.accounts.create') }}" class="btn btn--primary">İlk hesabı ekle</a>
        </div>
    @elseif ($accounts->isEmpty())
        <div class="blank blank--s"><p>Bu aramaya uyan hesap yok.</p></div>
    @else
        <ul class="tiles stagger" role="list">
            @foreach ($accounts as $account)
                <li>
                    <a href="{{ route('admin.accounts.edit', $account) }}" @class(['tile', 'spot', 'is-dim' => ! $account->is_active])>
                        <span class="tile__top">
                            <span class="plat" data-p="{{ $account->platform }}" aria-hidden="true">{{ $account->platformMark() }}</span>
                            <span @class(['state', 'state--on' => $account->is_active])>{{ $account->is_active ? 'Kod veriyor' : 'Durduruldu' }}</span>
                        </span>
                        <span class="tile__title">{{ $account->title }}</span>
                        <span class="tile__sub">{{ $account->platformLabel() }}</span>
                        <span class="chips">
                            @if ($account->hasMailbox())<span class="chip">Mail kodu</span>@endif
                            @if ($account->hasTotp())<span class="chip">2FA</span>@endif
                        </span>
                        <span class="tile__foot"><strong>{{ $account->unused_count }}</strong> satılmamış anahtar</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>
