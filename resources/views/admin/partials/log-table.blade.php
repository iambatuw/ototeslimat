{{-- Kayıtlar bir zaman akışı olarak gösterilir: nokta olayın türünü söyler. --}}
@php
    $tone = fn ($event) => match (true) {
        in_array($event, ['code.issued', 'key.redeemed', 'key.picked', 'auth.registered'], true) => 'good',
        in_array($event, ['code.failed', 'key.invalid', 'auth.failed', 'key.revoked'], true) => 'bad',
        default => 'plain',
    };
@endphp
<ol class="feed stagger" role="list">
    @foreach ($logs as $log)
        <li class="feed__item feed__item--{{ $tone($log->event) }}">
            <time class="feed__time" datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->format('d.m.Y H:i:s') }}">{{ $log->created_at->format('d.m H:i') }}</time>
            <span class="feed__dot" aria-hidden="true"></span>
            <div class="feed__body">
                <p class="feed__title">{{ $log->label() }}@if ($log->account) <span>{{ $log->account->title }}</span>@endif</p>
                <p class="feed__meta">@include('admin.partials.log-meta', ['log' => $log])</p>
            </div>
            <div class="feed__who">
                @if ($log->user)<span>{{ $log->user->email }}</span>@endif
                @if ($log->ip)<span class="mono">{{ $log->ip }}</span>@endif
            </div>
        </li>
    @endforeach
</ol>
