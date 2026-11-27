{{--
    Üstteki büyük kart: bir hesabın kodu burada alınır. Her hesap için bir tane çizilir,
    yalnızca seçili olan görünür; raftaki "Kod al" başka bir hesabınkini açar.
    $row (App\Support\Wallet satırı), $active, $history (bu hesabın son kodları), $asked (bekleyen ek hak isteği var mı)
--}}
@php
    $account = $row['account'];
    $label = $row['label'];
@endphp
<article class="hero-card" id="hesap-{{ $row['id'] }}" data-hero="{{ $row['id'] }}" data-p="{{ $row['platform'] }}"
    data-game data-poll="{{ config('guard.poll.interval_seconds') }},{{ config('guard.poll.attempts') }}"
    @unless ($active) hidden @endunless aria-labelledby="hero-title-{{ $row['id'] }}">
    <div class="hero-card__head">
        <span class="hero-card__mark" aria-hidden="true">{{ $account->platformMark() }}</span>
        <div class="hero-card__titles">
            <p class="hero-card__kicker">
                {{ $row['platformLabel'] }} · {{ $row['last'] ? 'son kod '.$row['last']->diffForHumans() : 'henüz kod alınmadı' }}
            </p>
            <h2 class="hero-card__title" id="hero-title-{{ $row['id'] }}">
                <span data-label-text="{{ $account->id }}">{{ $label ?? $row['title'] }}</span>
                @if ($label)<span class="hero-card__sub">{{ $row['title'] }}</span>@endif
            </h2>
        </div>
        <span class="hero-card__chip">{{ $row['status'] }}</span>
    </div>

    <p class="hero-card__meta">{{ implode(' · ', array_filter([$row['package'] ? 'Paket: '.$row['package'] : null, ...$row['meta']])) }}</p>
    @if ($asked ?? false)
        <p class="hero-card__asked">Ek hak isteğin satıcında bekliyor.</p>
    @endif

    <div class="hero-card__actions">
        @if ($account->hasMailbox())
            <button type="button" class="btn hero-card__btn" data-get-code data-url="{{ route('code', [$row['key'], $account, 'mail']) }}" data-length="{{ $account->codeLength('mail') }}" data-idle="Mail kodunu al">Mail kodunu al</button>
        @endif
        @if ($account->hasTotp())
            @php($totpLabel = $account->isSteamGuard() ? 'Steam Guard kodunu al' : '2FA kodunu al')
            <button type="button" @class(['btn', 'hero-card__btn', 'hero-card__btn--alt' => $account->hasMailbox()]) data-get-code data-url="{{ route('code', [$row['key'], $account, '2fa']) }}" data-length="{{ $account->codeLength('2fa') }}" data-idle="{{ $totpLabel }}">{{ $totpLabel }}</button>
        @endif

        <details class="hero-card__name">
            <summary>{{ $label ? 'Adını değiştir' : 'Bu hesaba ad ver' }}</summary>
            <form method="POST" action="{{ route('label', $account) }}" class="hero-card__name-form" data-label-form>
                @csrf
                <label for="label-{{ $row['id'] }}" class="visually-hidden">Bu hesap için kendi adın</label>
                <input id="label-{{ $row['id'] }}" name="label" type="text" class="input" value="{{ $label }}" maxlength="40" placeholder="Örn. Ana hesap, Kardeşimin" autocomplete="off">
                <button type="submit" class="btn btn--secondary btn--s">Kaydet</button>
            </form>
        </details>
    </div>

    <div class="code" hidden></div>

    @if ($account->customer_note)
        <p class="hero-card__note"><strong>Satıcının notu:</strong> {{ $account->customer_note }}</p>
    @endif

    @if (($history ?? collect())->isNotEmpty())
        <details class="hero-card__history">
            <summary>Son kodların ({{ $history->count() }})</summary>
            <ol role="list">
                @foreach ($history as $log)
                    <li>
                        <time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d.m.Y H:i') }}</time>
                        <span>{{ ($log->meta['type'] ?? '') === 'mail' ? 'Mail kodu' : (($account->isSteamGuard()) ? 'Steam Guard' : '2FA kodu') }} · {{ $log->created_at->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ol>
        </details>
    @endif
</article>
