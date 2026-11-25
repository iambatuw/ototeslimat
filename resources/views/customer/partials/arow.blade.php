{{-- Raftaki hesap satırı. $row (App\Support\Wallet satırı), $active (üstte açık olan mı) --}}
@php
    $account = $row['account'];
    $name = $row['label'] ?? $row['title'];
    $search = mb_strtolower(implode(' ', array_filter([$row['title'], $row['label'], $row['platformLabel'], $row['package']])));
@endphp
<li @class(['arow', 'is-active' => $active, 'is-off' => $row['blocked']]) data-p="{{ $row['platform'] }}" data-row="{{ $row['id'] }}" data-platform="{{ $row['platform'] }}" data-search="{{ $search }}">
    <span class="arow__mark" aria-hidden="true">{{ $account->platformMark() }}</span>
    <div class="arow__text">
        <p class="arow__name"><span data-label-text="{{ $account->id }}">{{ $name }}</span>@if ($row['label'])<span class="arow__sub"><span class="arow__dot" aria-hidden="true"> · </span>{{ $row['title'] }}</span>@endif</p>
        <p class="arow__meta">{{ $row['blocked'] ?? $row['status'] }}@if ($row['package']) · Paket: {{ $row['package'] }}@endif</p>
    </div>
    <p class="arow__last">
        @if ($active)
            <span class="arow__here">Yukarıda açık</span>
        @else
            {{ $row['last'] ? 'Son kod '.$row['last']->diffForHumans() : 'Hiç kod alınmadı' }}
        @endif
    </p>
    @if ($row['blocked'])
        <span class="arow__off">Kod verilmiyor</span>
    @else
        <a href="{{ route('home', ['hesap' => $row['id']]) }}#hesap-{{ $row['id'] }}" class="btn btn--secondary btn--s arow__go" data-show="{{ $row['id'] }}"
            aria-label="{{ $name }} kodunu al">Kod al</a>
    @endif
</li>
