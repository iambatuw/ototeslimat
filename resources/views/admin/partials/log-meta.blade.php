@php
    $m = $log->meta ?? [];
    $reasons = [
        'not_found' => 'böyle bir anahtar yok',
        'unavailable' => 'anahtar iptal ya da hesap silinmiş',
        'taken' => 'başka hesapta kullanılmış',
        'no_mail' => 'son mailde kod yok',
        'imap_connect' => 'posta kutusuna bağlanılamadı',
        'imap_read' => 'posta okunamadı',
        'totp_secret' => '2FA anahtarı bozuk',
        'no_totp' => 'hesapta 2FA yok',
        'no_mailbox' => 'hesapta posta kutusu yok',
        'inactive' => 'hesap durdurulmuş',
        'revoked' => 'anahtar iptal',
        'expired' => 'süresi dolmuş',
        'used_up' => 'hakkı bitmiş',
        'cooldown' => 'bekleme süresinde',
    ];
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
    @case('settings.updated')
        {{ implode(', ', $m['fields'] ?? []) }}
        @break
@endswitch
