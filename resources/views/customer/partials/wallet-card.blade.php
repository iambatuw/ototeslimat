{{--
    Cüzdandaki tek kart. $card: kind (account|pick), key, account?, package?, meta, blocked, id, active.
    Kartın sekmesi (üst şerit) her zaman görünür; yüzü yalnızca öndeki kartta açılır.
--}}
@php
    $isPick = $card['kind'] === 'pick';
    $account = $card['account'] ?? null;
    $platform = $isPick ? 'pack' : $account->platform;
    $title = $isPick ? $card['package']->name : $account->title;
    $label = $isPick ? 'Paket' : $account->platformLabel();
    $down = ! $isPick && ($account->trashed() || ! $account->is_active);
    $reason = $card['blocked'] ?? ($down ? 'Şu an kod verilmiyor' : null);
    $faceId = 'face-'.$card['id'];
@endphp
<article @class(['wcard', 'is-front' => $card['active'], 'is-off' => $reason]) data-wcard data-p="{{ $platform }}"
    @unless ($isPick) data-game data-poll="{{ config('guard.poll.interval_seconds') }},{{ config('guard.poll.attempts') }}" @endunless
    @if ($card['active'] && session('opened') === $card['key']->id) data-open="true" @endif>
    <h2 class="wcard__head">
        <button type="button" class="wcard__tab" data-wcard-tab aria-controls="{{ $faceId }}" aria-expanded="{{ $card['active'] ? 'true' : 'false' }}">
            <span class="wcard__mark" aria-hidden="true">
                @if ($isPick)
                    <svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/></svg>
                @else
                    {{ $account->platformMark() }}
                @endif
            </span>
            <span class="wcard__name">{{ $title }}</span>
            {{-- Şerit platformu değil durumu söyler: kalan hak, kalan gün ya da neden kod verilmediği. --}}
            <span class="wcard__state">{{ $reason ?? ($isPick ? $card['picked'].' / '.$card['package']->pick_count.' hesap seçildi' : ($card['status'] ?? $label)) }}</span>
        </button>
    </h2>

    <div class="wcard__face" id="{{ $faceId }}" @unless ($card['active']) hidden @endunless>
        @php($metaLine = implode(' · ', array_filter([$isPick ? null : $label, ...$card['meta']])))
        @if ($metaLine !== '')<p class="wcard__meta">{{ $metaLine }}</p>@endif

        @if ($isPick)
            @if ($card['package']->description)<p class="wcard__note">{{ $card['package']->description }}</p>@endif
            <p class="wcard__note">{{ $card['left'] }} hesap daha seçebilirsin. Seçim kalıcıdır; sonradan değiştiremezsin.</p>
            <ul class="wcard__choices" role="list">
                @foreach ($card['choices'] as $choice)
                    @php($unavailable = $choice->trashed() || ! $choice->is_active)
                    <li>
                        <form method="POST" action="{{ route('pick', $card['key']) }}" data-confirm="{{ $choice->title }} seçilsin mi? Sonradan değiştiremezsin.">
                            @csrf
                            <input type="hidden" name="account_id" value="{{ $choice->id }}">
                            <button type="submit" class="wcard__choice" data-p="{{ $choice->platform }}" @disabled($unavailable)>
                                <span class="wcard__dot" aria-hidden="true">{{ $choice->platformMark() }}</span>
                                <span>
                                    <span class="wcard__choice-title">{{ $choice->title }}</span>
                                    <span class="wcard__choice-sub">{{ $unavailable ? 'Şu an seçilemiyor' : $choice->platformLabel() }}</span>
                                </span>
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @elseif ($reason)
            <p class="wcard__note">{{ $reason }}. Yeni anahtar için satıcınla iletişime geç.</p>
        @else
            <div class="wcard__actions">
                @if ($account->hasMailbox())
                    <button type="button" class="btn wcard__btn" data-get-code data-url="{{ route('code', [$card['key'], $account, 'mail']) }}" data-length="{{ $account->codeLength('mail') }}" data-idle="Mail kodunu al">Mail kodunu al</button>
                @endif
                @if ($account->hasTotp())
                    @php($totpLabel = $account->isSteamGuard() ? 'Steam Guard kodunu al' : '2FA kodunu al')
                    <button type="button" @class(['btn', 'wcard__btn', 'wcard__btn--alt' => $account->hasMailbox()]) data-get-code data-url="{{ route('code', [$card['key'], $account, '2fa']) }}" data-length="{{ $account->codeLength('2fa') }}" data-idle="{{ $totpLabel }}">{{ $totpLabel }}</button>
                @endif
            </div>
            <div class="code" hidden></div>
        @endif
    </div>
</article>
