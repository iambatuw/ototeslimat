{{-- Ek hak isteği: kim, hangi hesap, neden; onayla (+1 hak) ya da reddet. $ask (RightRequest) --}}
<li class="ask-row">
    <div class="ask-row__who">
        <a href="{{ route('admin.customers.show', $ask->user_id) }}"><strong>{{ $ask->user?->email }}</strong></a>
        <small>{{ $ask->account?->title ?? $ask->key?->targetTitle() }} · {{ $ask->reasonLabel() }} · {{ $ask->created_at->diffForHumans() }}</small>
    </div>
    @if ($ask->status === 'pending')
        <form method="POST" action="{{ route('admin.requests.handle', $ask) }}" class="ask-row__do">
            @csrf @method('PATCH')
            <button type="submit" name="decision" value="approve" class="btn btn--primary btn--s">+1 hak ver</button>
            <button type="submit" name="decision" value="reject" class="btn btn--ghost btn--s">Reddet</button>
        </form>
    @else
        <span @class(['state', 'state--on' => $ask->status === 'approved', 'state--bad' => $ask->status === 'rejected'])>{{ $ask->status === 'approved' ? 'Onaylandı' : 'Reddedildi' }}</span>
    @endif
</li>
