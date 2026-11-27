@php
    $m = $log->meta ?? [];
    $reasons = \App\Models\ActivityLog::REASONS;
    $types = ['mail' => 'mail', '2fa' => '2FA'];
@endphp
@switch($log->event)
    @case('code.issued')
        {{ $types[$m['type'] ?? ''] ?? '' }}: <span class="mono">{{ str($m['code'] ?? '')->limit(40) }}</span>
        @break
    @case('code.failed')
    @case('key.invalid')
        {{ $reasons[$m['reason'] ?? ''] ?? ($m['reason'] ?? '') }}@if (! empty($m['input'])), girilen <span class="mono">{{ $m['input'] }}</span>@endif
        @break
    @case('key.created')
        {{ $m['count'] ?? 0 }} adet{{ ! empty($m['label']) ? ', '.$m['label'] : '' }}
        @break
    @case('game.saved')
    @case('game.deleted')
    @case('package.saved')
    @case('package.deleted')
        {{ $m['name'] ?? '' }}
        @break
    @case('key.purged')
        {{ $m['count'] ?? 0 }} anahtar
        @break
    @case('account.created')
        {{ isset($m['bulk']) ? $m['bulk'].' hesap, toplu' : ($m['platform'] ?? '') }}
        @break
    @case('account.tested')
        {{ ! empty($m['ok']) ? 'çalışıyor' : 'hata verdi' }}
        @break
    @case('account.updated')
        {{ implode(', ', $m['fields'] ?? []) }}
        @break
    @case('auth.failed')
        {{ $m['email'] ?? '' }}
        @break
    @case('customer.toggled')
        {{ $m['email'] ?? '' }} {{ ! empty($m['active']) ? 'açıldı' : 'engellendi' }}
        @break
    @case('key.bonus')
        +{{ $m['amount'] ?? 1 }} kod hakkı
        @break
    @case('key.extended')
        +{{ $m['days'] ?? 0 }} gün
        @break
    @case('key.shared')
        24 saatte {{ $m['ips'] ?? 0 }} farklı IP
        @break
    @case('account.compensated')
        {{ $m['keys'] ?? 0 }} anahtar, +{{ $m['rights'] ?? 0 }} hak, +{{ $m['days'] ?? 0 }} gün
        @break
    @case('key.moved')
        {{ $m['from'] ?? '' }} hesabından {{ $m['to'] ?? '' }} hesabına, {{ $m['count'] ?? 0 }} anahtar
        @break
    @case('request.created')
        {{ \App\Models\RightRequest::REASONS[$m['reason'] ?? ''] ?? '' }}
        @break
    @case('settings.updated')
        {{ implode(', ', $m['fields'] ?? []) }}
        @break
@endswitch
