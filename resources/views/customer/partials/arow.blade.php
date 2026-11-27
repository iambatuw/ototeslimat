{{--
    Listedeki hesap satırı: dokununca o hesap sağda (telefonda üstte) açılır. JS yoksa bağlantı
    ?hesap= ile aynı şeyi sunucuda yapar. $row (App\Support\Wallet satırı), $active
--}}
@php
    $account = $row['account'];
    $name = $row['label'] ?? $row['title'];
    $search = mb_strtolower(implode(' ', array_filter([$row['title'], $row['label'], $row['platformLabel'], $row['package']])));
    $cover = $account->game?->coverUrl();
@endphp
<li @class(['lrow', 'is-active' => $active, 'is-off' => $row['blocked']]) data-p="{{ $row['platform'] }}" data-row="{{ $row['id'] }}" data-platform="{{ $row['platform'] }}" data-search="{{ $search }}">
    @if ($row['blocked'])
        <div class="lrow__btn" aria-disabled="true">
    @else
        <a href="{{ route('home', ['hesap' => $row['id']]) }}#hesap-{{ $row['id'] }}" class="lrow__btn" data-show="{{ $row['id'] }}" @if ($active) aria-current="true" @endif>
    @endif
        @if ($cover)
            <img class="lrow__mark lrow__mark--cover" src="{{ $cover }}" alt="" loading="lazy" referrerpolicy="no-referrer">
        @else
            <span class="lrow__mark" aria-hidden="true">{{ $account->platformMark() }}</span>
        @endif
        <span class="lrow__text">
            <span class="lrow__name"><span data-label-text="{{ $account->id }}">{{ $name }}</span></span>
            <span class="lrow__sub">{{ $row['label'] ? $row['title'] : $row['platformLabel'] }}</span>
        </span>
        <span @class(['lrow__status', 'lrow__status--off' => $row['blocked']])>{{ $row['blocked'] ?? $row['status'] }}</span>
    @if ($row['blocked'])
        </div>
    @else
        </a>
    @endif
</li>
