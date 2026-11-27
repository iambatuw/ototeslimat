{{--
    Kayıtlar günlere ayrılmış bir liste olarak gösterilir: solda olayın türünü söyleyen simge,
    ortada ne oldu ve ayrıntı, sağda saat ve kim. IP adresi küçük ve soluk; üstüne gelince tamamı.
--}}
@php
    $tone = fn ($event) => match (true) {
        in_array($event, ['code.issued', 'key.redeemed', 'key.picked', 'auth.registered', 'request.approved', 'key.bonus', 'key.extended', 'account.compensated'], true) => 'good',
        in_array($event, ['code.failed', 'key.invalid', 'auth.failed', 'key.revoked', 'key.shared', 'request.rejected'], true) => 'bad',
        in_array($event, ['request.created'], true) => 'ask',
        default => 'plain',
    };
    $icons = [
        'good' => '<path d="m5 10.5 3.2 3L15 6.5"/>',
        'bad' => '<path d="m6.5 6.5 7 7M13.5 6.5l-7 7"/>',
        'ask' => '<path d="M10 5.5v5M10 14h.01"/>',
        'plain' => '<circle cx="10" cy="10" r="2.2"/>',
    ];
    $dayLabel = function ($at) {
        return match (true) {
            $at->isToday() => 'Bugün',
            $at->isYesterday() => 'Dün',
            default => $at->translatedFormat('j F l'),
        };
    };
@endphp
<div class="feed">
    @foreach ($logs->groupBy(fn ($log) => $log->created_at->format('Y-m-d')) as $day => $items)
        <p class="feed__day">{{ $dayLabel($items->first()->created_at) }}</p>
        <ol class="feed__list" role="list">
            @foreach ($items as $log)
                @php($kind = $tone($log->event))
                <li class="feed__item feed__item--{{ $kind }}">
                    <span class="feed__icon" aria-hidden="true"><svg viewBox="0 0 20 20">{!! $icons[$kind] !!}</svg></span>
                    <div class="feed__body">
                        <p class="feed__title">{{ $log->label() }}@if ($log->account)<span class="feed__what">{{ $log->account->title }}</span>@endif</p>
                        <p class="feed__meta">@include('admin.partials.log-meta', ['log' => $log])</p>
                    </div>
                    <div class="feed__side">
                        <time class="feed__time" datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->format('d.m.Y H:i:s') }}">{{ $log->created_at->format('H:i') }}</time>
                        @if ($log->user && ! $log->user->isAdmin())
                            <a href="{{ route('admin.customers.show', $log->user_id) }}" class="feed__who">{{ $log->user->email }}</a>
                        @elseif ($log->user)
                            <span class="feed__who">{{ $log->user->email }}</span>
                        @endif
                        @if ($log->ip)<span class="feed__ip" title="IP adresi">{{ $log->ip }}</span>@endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endforeach
</div>
