{{-- Bir hesabın satırı ve kod paneli. $key: anahtar, $account: hesap, $meta: alt bilgi parçaları, $blocked: anahtar artık kod vermiyorsa nedeni --}}
@php
    $down = $account->trashed() || ! $account->is_active;
    $reason = $blocked ?? ($down ? 'Şu an kod verilmiyor' : null);
@endphp
<li class="game {{ $reason ? 'game--off' : '' }}" data-game @if (session('opened') === $key->id) data-open="true" @endif>
    <div class="game__row">
        <div>
            <h3 class="game__title">{{ $account->title }}</h3>
            <p class="game__meta">{{ implode(', ', array_filter($meta)) }}</p>
        </div>
        @if ($reason)
            <span class="muted small">{{ $reason }}</span>
        @else
            <div class="game__actions">
                @if ($account->hasMailbox())
                    <button type="button" class="btn btn--primary" data-get-code data-url="{{ route('code', [$key, $account, 'mail']) }}" data-idle="Mail kodunu al">Mail kodunu al</button>
                @endif
                @if ($account->hasTotp())
                    <button type="button" class="btn {{ $account->hasMailbox() ? 'btn--ghost' : 'btn--primary' }}" data-get-code data-url="{{ route('code', [$key, $account, '2fa']) }}" data-idle="2FA kodunu al">2FA kodunu al</button>
                @endif
            </div>
        @endif
    </div>
    <div class="code" hidden></div>
</li>
