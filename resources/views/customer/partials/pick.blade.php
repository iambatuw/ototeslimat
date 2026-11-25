{{-- Paket anahtarında seçim hakkı kaldıysa: listeden hesap seç. $pick (App\Support\Wallet picks öğesi) --}}
<section class="pick" aria-labelledby="pick-{{ $pick['key']->id }}">
    <div class="pick__head">
        <span class="pick__mark" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/></svg></span>
        <div>
            <h2 id="pick-{{ $pick['key']->id }}" class="pick__title">{{ $pick['package']->name }}</h2>
            <p class="pick__lead">{{ $pick['picked'] }} / {{ $pick['package']->pick_count }} hesap seçildi. {{ $pick['left'] }} hesap daha seçebilirsin; seçim kalıcıdır.</p>
        </div>
    </div>
    @if ($pick['package']->description)<p class="pick__note">{{ $pick['package']->description }}</p>@endif
    <ul class="pick__choices" role="list">
        @foreach ($pick['choices'] as $choice)
            @php($unavailable = $choice->trashed() || ! $choice->is_active)
            <li>
                <form method="POST" action="{{ route('pick', $pick['key']) }}" data-confirm="{{ $choice->title }} seçilsin mi? Sonradan değiştiremezsin.">
                    @csrf
                    <input type="hidden" name="account_id" value="{{ $choice->id }}">
                    <button type="submit" class="pick__choice" data-p="{{ $choice->platform }}" @disabled($unavailable)>
                        <span class="pick__dot" aria-hidden="true">{{ $choice->platformMark() }}</span>
                        <span>
                            <span class="pick__choice-title">{{ $choice->title }}</span>
                            <span class="pick__choice-sub">{{ $unavailable ? 'Şu an seçilemiyor' : $choice->platformLabel() }}</span>
                        </span>
                    </button>
                </form>
            </li>
        @endforeach
    </ul>
</section>
