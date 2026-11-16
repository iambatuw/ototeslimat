{{-- Bir hesabın satırı ve kod paneli. $key, $account, $meta (alt bilgi), $blocked (kod verilmiyorsa nedeni) --}}
@php
    $down = $account->trashed() || ! $account->is_active;
    $reason = $blocked ?? ($down ? 'Şu an kod verilmiyor' : null);
@endphp
<div data-game @if (session('opened') === $key->id) data-open="true" @endif>
    <div class="acct__row">
        <span class="plat" data-p="{{ $account->platform }}" aria-hidden="true">{{ mb_substr($account->platformLabel(), 0, 1) }}</span>
        <div>
            <h3 class="acct__title">{{ $account->title }}</h3>
            <p class="acct__meta">{{ implode(', ', array_filter($meta)) ?: $account->platformLabel() }}</p>
        </div>
        @if ($reason)
            <span class="badge">{{ $reason }}</span>
        @else
            <div class="acct__actions">
                @if ($account->hasMailbox())
                    <button type="button" class="btn btn--primary" data-get-code data-url="{{ route('code', [$key, $account, 'mail']) }}" data-idle="Mail kodunu al">Mail kodunu al</button>
                @endif
                @if ($account->hasTotp())
                    <button type="button" class="btn {{ $account->hasMailbox() ? 'btn--secondary' : 'btn--primary' }}" data-get-code data-url="{{ route('code', [$key, $account, '2fa']) }}" data-idle="2FA kodunu al">2FA kodunu al</button>
                @endif
            </div>
        @endif
    </div>
    <div class="code" hidden></div>
</div>
