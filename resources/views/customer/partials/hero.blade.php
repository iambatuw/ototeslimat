{{--
    Seçili hesabın ayrıntısı. Her hesap için bir tane çizilir, yalnızca seçili olan görünür.
    Üstte hesap, altında kod sahnesi (kod burada alınır), sonra bilgiler, not, son kodlar.
    $row (App\Support\Wallet satırı), $active, $history, $asked, $contact
--}}
@php
    $account = $row['account'];
    $label = $row['label'];
    $cover = $account->game?->coverUrl();
    $steam = $account->isSteamGuard() || $account->platform === 'steam';
    $totpName = $steam ? 'Steam Guard' : '2FA';
    $sources = array_values(array_filter([$account->hasMailbox() ? 'mail' : null, $account->hasTotp() ? '2fa' : null]));
@endphp
<article class="dt" id="hesap-{{ $row['id'] }}" data-hero="{{ $row['id'] }}" data-p="{{ $row['platform'] }}"
    data-game data-poll="{{ config('guard.poll.interval_seconds') }},{{ config('guard.poll.attempts') }}"
    @unless ($active) hidden @endunless aria-labelledby="dt-title-{{ $row['id'] }}">

    <header class="dt__head">
        @if ($cover)
            <img class="dt__cover" src="{{ $cover }}" alt="" referrerpolicy="no-referrer">
        @else
            <span class="dt__mark" aria-hidden="true">{{ $account->platformMark() }}</span>
        @endif
        <div class="dt__who">
            <p class="dt__platform">{{ $row['platformLabel'] }}@if ($row['package']) · Paket: {{ $row['package'] }}@endif</p>
            <h2 class="dt__name" id="dt-title-{{ $row['id'] }}"><span data-label-text="{{ $account->id }}">{{ $label ?? $row['title'] }}</span></h2>
            @if ($label)<p class="dt__sub">{{ $row['title'] }}</p>@endif
            <p class="dt__last">{{ $row['last'] ? 'Son kod '.$row['last']->diffForHumans() : 'Henüz kod alınmadı' }}</p>
        </div>
        <details class="dt__rename">
            <summary aria-label="{{ $label ? 'Adını değiştir' : 'Bu hesaba ad ver' }}">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 16h3l8.5-8.5a2.1 2.1 0 0 0-3-3L4 13v3Z"/></svg>
                <span>{{ $label ? 'Adını değiştir' : 'Ad ver' }}</span>
            </summary>
            <form method="POST" action="{{ route('label', $account) }}" class="dt__rename-form" data-label-form>
                @csrf
                <label for="label-{{ $row['id'] }}">Bu hesap için kendi adın</label>
                <div class="dt__rename-row">
                    <input id="label-{{ $row['id'] }}" name="label" type="text" class="input" value="{{ $label }}" maxlength="40" placeholder="Örn. Ana hesap, Kardeşimin" autocomplete="off">
                    <button type="submit" class="btn btn--secondary btn--s">Kaydet</button>
                </div>
                <p class="hint">Aynı platformdan birkaç hesabın varsa ayırmana yarar. Yalnızca sen görürsün.</p>
            </form>
        </details>
    </header>

    {{-- Kod sahnesi: hesabın platform renginde, kodun alındığı yer --}}
    <div class="stage">
        @if ($asked)
            <p class="stage__asked">Ek hak isteğin satıcında bekliyor. Onaylayınca buradan kod alabilirsin.</p>
        @endif
        <div @class(['stage__actions', 'stage__actions--two' => count($sources) > 1])>
            @if (in_array('mail', $sources, true))
                <button type="button" class="stage__btn" data-get-code data-url="{{ route('code', [$row['key'], $account, 'mail']) }}" data-length="{{ $account->codeLength('mail') }}"
                    data-idle="Mail kodunu getir" data-again="Yeni mail kodu" data-retry="Mail kodunu tekrar dene">Mail kodunu getir</button>
            @endif
            @if (in_array('2fa', $sources, true))
                <button type="button" @class(['stage__btn', 'stage__btn--alt' => count($sources) > 1]) data-get-code data-url="{{ route('code', [$row['key'], $account, '2fa']) }}" data-length="{{ $account->codeLength('2fa') }}"
                    data-idle="{{ $totpName }} kodunu göster" data-again="Yeni {{ $totpName }} kodu" data-retry="{{ $totpName }} kodunu tekrar dene">{{ $totpName }} kodunu göster</button>
            @endif
        </div>
        <p class="stage__hint">
            @if (count($sources) > 1)
                Platform kodu e-postaya gönderdiyse mail kodunu, uygulama kodu istiyorsa {{ $totpName }} kodunu al.
            @elseif (($sources[0] ?? null) === 'mail')
                Platformda giriş yapıp kodu iste, sonra düğmeye bas. Mail gelene kadar sayfa bekler.
            @else
                Kod 30 saniyede bir yenilenir; platform sorduğunda düğmeye bas.
            @endif
        </p>
        <div class="code" hidden></div>
    </div>

    @if ($account->customer_note)
        <p class="dt__note"><strong>Satıcının notu</strong>{{ $account->customer_note }}</p>
    @endif

    <dl class="dt__facts">
        @foreach ($row['facts'] as [$title, $value, $extra])
            <div>
                <dt>{{ $title }}</dt>
                <dd>{{ $value }}</dd>
                @if ($extra)<dd class="dt__extra">{{ $extra }}</dd>@endif
            </div>
        @endforeach
    </dl>

    @if ($history->isNotEmpty() || $account->hasMailbox())
        <div class="dt__more">
            @if ($history->isNotEmpty())
                <section class="dt__history" aria-label="Son kodların">
                    <h3>Son kodların</h3>
                    <ol role="list">
                        @foreach ($history as $log)
                            <li>
                                <span>{{ ($log->meta['type'] ?? '') === 'mail' ? 'Mail kodu' : $totpName.' kodu' }}</span>
                                <time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->format('d.m.Y H:i') }}">{{ $log->created_at->diffForHumans() }}</time>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif
            @if ($account->hasMailbox())
                <section class="dt__help" aria-label="Kod gelmedi mi?">
                    <h3>Mail kodu gelmedi mi?</h3>
                    <ol role="list">
                        <li>Platformda "kodu tekrar gönder"e bas.</li>
                        <li>Sayfa 2 dakika kendiliğinden bekler; kapatma.</li>
                        <li>Yine gelmezse @if ($contact)<a href="{{ $contact }}" rel="noopener" target="_blank">destekle iletişime geç</a>.@else satıcınla iletişime geç.@endif</li>
                    </ol>
                </section>
            @endif
        </div>
    @endif
</article>
