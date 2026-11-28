{{--
    Kütüphanedeki hesap kartı: üstte görsel (oyunun yatay kapağı, dikey kapak sığdırılmış ya da
    platform renginde platform logosu), altında ad, ne zaman eklendiği, kalan hak ve kod düğmesi
    (iki kod türü varsa yan yana). Kod kartın içinde açılır. Not, bilgiler, son kodlar ve mail yardımı
    kartın üstünde açılan "Ayrıntılar" katmanında; kod da kartın üstünde açılır. Ad satıcınındır (oyuna bağlıysa oyunun adı); müşteri değiştiremez.
    $row (App\Support\Wallet satırı), $picked (adresle ya da yeni eklenerek seçildi mi), $history, $asked, $contact
--}}
@php
    $account = $row['account'];
    $game = $account->game;
    $cover = $game?->coverUrl();
    $wide = $game?->wideUrl();
    $logo = $cover ? null : $account->platformLogo();
    $steam = $account->isSteamGuard() || $account->platform === 'steam';
    $totpName = $steam ? 'Steam Guard' : '2FA';
    $sources = $row['blocked'] ? [] : array_values(array_filter([$account->hasMailbox() ? 'mail' : null, $account->hasTotp() ? '2fa' : null]));
    $two = count($sources) > 1;
    // Kartın adı satıcının verdiği addır; hesap bir oyuna bağlıysa oyunun adı.
    $name = $game?->name ?? $row['title'];
    // Alt satır: platform ve (ad oyunun adıysa) satıcının hesap adı; hesap adı platformla başlıyorsa platform tekrar yazılmaz.
    $titleHasPlatform = str_starts_with(mb_strtolower($row['title']), mb_strtolower($row['platformLabel']));
    $sub = implode(' · ', array_filter([
        $name !== $row['title'] && $titleHasPlatform ? null : $row['platformLabel'],
        $name !== $row['title'] ? $row['title'] : null,
        $row['package'] ? 'Paket: '.$row['package'] : null,
    ]));
    $search = mb_strtolower(implode(' ', array_filter([$name, $row['title'], $row['platformLabel'], $row['package']])));
@endphp
<li @class(['gcard', 'is-picked' => $picked, 'is-off' => $row['blocked']]) data-row="{{ $row['id'] }}" data-platform="{{ $row['platform'] }}" data-search="{{ $search }}">
    <article class="gcard__in" id="hesap-{{ $row['id'] }}" data-hero="{{ $row['id'] }}" data-p="{{ $row['platform'] }}"
        data-game data-poll="{{ config('guard.poll.interval_seconds') }},{{ config('guard.poll.attempts') }}"
        @if ($picked) data-open="true" @endif aria-labelledby="gc-title-{{ $row['id'] }}">

        <div class="gcard__art" aria-hidden="true">
            @if ($wide)
                <img class="gcard__img" src="{{ $wide }}" alt="" loading="lazy" referrerpolicy="no-referrer">
            @elseif ($cover)
                {{-- Dikey kapak: arkada bulanık hâli kartı doldurur, önde kapağın tamamı sığar. --}}
                <img class="gcard__blur" src="{{ $cover }}" alt="" loading="lazy" referrerpolicy="no-referrer">
                <img class="gcard__poster" src="{{ $cover }}" alt="" loading="lazy" referrerpolicy="no-referrer">
            @elseif ($logo)
                <img class="gcard__logo" src="{{ $logo }}" alt="" loading="lazy">
            @else
                <span class="gcard__word">{{ $row['platformLabel'] }}</span>
            @endif
            <span @class(['gcard__status', 'gcard__status--off' => $row['blocked']])>{{ $row['blocked'] ?? $row['status'] }}</span>
        </div>
        <button type="button" @class(['gcard__infobtn', 'has-note' => $account->customer_note]) data-info-open aria-controls="info-{{ $row['id'] }}" aria-expanded="false">
            <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="7.25"/><path d="M10 9v4.5M10 6.4v.1"/></svg>
            <span>Ayrıntılar</span>
        </button>

        <div class="gcard__body">
            <h2 class="gcard__name" id="gc-title-{{ $row['id'] }}">{{ $name }}</h2>
            <p class="gcard__sub">{{ $sub }}</p>
            <p class="gcard__date">Eklendi: {{ $row['key']->redeemed_at?->format('d.m.Y') }}@if ($row['last']) · Son kod {{ $row['last']->diffForHumans() }}@endif</p>

            @if ($account->login_username || $account->login_password)
                <div class="gcard__creds">
                    @if ($account->login_username)
                        <div class="gcard__cred">
                            <span class="gcard__cred-k">Kullanıcı</span>
                            <span class="gcard__cred-v mono">{{ $account->login_username }}</span>
                            <button type="button" class="gcard__cred-copy" data-copy="{{ $account->login_username }}">Kopyala</button>
                        </div>
                    @endif
                    @if ($account->login_password)
                        <div class="gcard__cred">
                            <span class="gcard__cred-k">Şifre</span>
                            <span class="gcard__cred-v mono">{{ $account->login_password }}</span>
                            <button type="button" class="gcard__cred-copy" data-copy="{{ $account->login_password }}">Kopyala</button>
                        </div>
                    @endif
                </div>
            @endif

            @if ($asked)
                <p class="gcard__asked">Ek hak isteğin satıcında bekliyor.</p>
            @endif

            @if ($sources)
                {{-- İki kod türü varsa düğmeler yan yana ve kısa adlı: kartların boyu eşit kalır. --}}
                <div @class(['gcard__actions', 'gcard__actions--two' => $two])>
                    @if (in_array('mail', $sources, true))
                        <button type="button" class="gcard__btn" data-get-code data-url="{{ route('code', [$row['key'], $account, 'mail']) }}" data-length="{{ $account->codeLength('mail') }}"
                            data-idle="{{ $two ? 'Mail kodu' : 'Mail kodunu getir' }}" data-again="Yeni mail kodu" data-retry="Tekrar dene">{{ $two ? 'Mail kodu' : 'Mail kodunu getir' }}</button>
                    @endif
                    @if (in_array('2fa', $sources, true))
                        <button type="button" @class(['gcard__btn', 'gcard__btn--alt' => $two]) data-get-code data-url="{{ route('code', [$row['key'], $account, '2fa']) }}" data-length="{{ $account->codeLength('2fa') }}"
                            data-idle="{{ $two ? $totpName.' kodu' : $totpName.' kodunu göster' }}" data-again="Yeni {{ $totpName }} kodu" data-retry="Tekrar dene">{{ $two ? $totpName.' kodu' : $totpName.' kodunu göster' }}</button>
                    @endif
                </div>
            @endif
        </div>

        {{-- Kod ve ayrıntılar kartın üstünde açılan katmanlarda: kart büyümez, ızgara bozulmaz. --}}
        @if ($sources)
            <div class="code" hidden></div>
            <button type="button" class="gcard__close" data-code-close aria-label="Kodu kapat">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m5 5 10 10M15 5 5 15"/></svg>
            </button>
        @endif

        <section class="gcard__info" id="info-{{ $row['id'] }}" data-info hidden aria-label="{{ $name }} ayrıntıları">
            <div class="gcard__info-head">
                <h3>Ayrıntılar</h3>
                <button type="button" class="gcard__x" data-info-close aria-label="Ayrıntıları kapat">
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m5 5 10 10M15 5 5 15"/></svg>
                </button>
            </div>
            @if ($account->customer_note)
                <p class="gcard__note"><strong>Satıcının notu</strong>{{ $account->customer_note }}</p>
            @endif
            <dl class="gcard__facts">
                @foreach ($row['facts'] as [$title, $value, $extra])
                    <div><dt>{{ $title }}</dt><dd>{{ $value }}@if ($extra) <small>{{ $extra }}</small>@endif</dd></div>
                @endforeach
            </dl>
            @if ($history->isNotEmpty())
                <div>
                    <h4>Son kodların</h4>
                    <ol class="gcard__list" role="list">
                        @foreach ($history as $log)
                            <li>
                                <span>{{ ($log->meta['type'] ?? '') === 'mail' ? 'Mail kodu' : $totpName.' kodu' }}</span>
                                <time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->format('d.m.Y H:i') }}">{{ $log->created_at->diffForHumans() }}</time>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
            @if ($account->hasMailbox() && ! $row['blocked'])
                <div>
                    <h4>Mail kodu gelmedi mi?</h4>
                    <ol class="gcard__steps" role="list">
                        <li>Platformda "kodu tekrar gönder"e bas.</li>
                        <li>Sayfa 2 dakika kendiliğinden bekler; kapatma.</li>
                        <li>Yine gelmezse @if ($contact)<a href="{{ $contact }}" rel="noopener" target="_blank">destekle iletişime geç</a>.@else satıcınla iletişime geç.@endif</li>
                    </ol>
                </div>
            @endif
        </section>
    </article>
</li>
