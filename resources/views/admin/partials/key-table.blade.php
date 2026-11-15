<div class="table-wrap">
    <table class="table table--stack">
        <thead>
            <tr>
                <th scope="col">Anahtar</th>
                <th scope="col">Ne için</th>
                <th scope="col">Durum</th>
                <th scope="col">Müşteri</th>
                <th scope="col" class="num">Verilen kod</th>
                <th scope="col"><span class="visually-hidden">İşlem</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($keys as $key)
                @php($status = $key->status())
                <tr class="{{ in_array($status, ['revoked', 'expired', 'used_up'], true) ? 'is-dim' : '' }}">
                    <td>
                        <span class="mono nowrap">{{ $key->code }}</span>
                        @if ($key->label)<span class="cell-sub">{{ $key->label }}</span>@endif
                    </td>
                    <td data-label="Ne için">
                        {{ $key->targetTitle() }}
                        @if ($key->isPackage() && $key->selections->isNotEmpty())<span class="cell-sub">Seçilen: {{ $key->selections->pluck('title')->implode(', ') }}</span>@endif
                        @if ($key->rulesSummary() !== 'Sınırsız')<span class="cell-sub">{{ $key->rulesSummary() }}</span>@endif
                    </td>
                    <td data-label="Durum">
                        <span @class(['badge', 'badge--accent' => $status === 'active'])>{{ $key->statusLabel() }}</span>
                        @if ($key->expires_at && $status === 'active')<span class="cell-sub">{{ $key->expires_at->format('d.m.Y') }} bitiş</span>@endif
                    </td>
                    <td data-label="Müşteri">{{ $key->customer?->email ?? '' }}</td>
                    <td data-label="Verilen kod" class="num">{{ $key->uses_count }}{{ $key->max_uses ? ' / '.$key->max_uses : '' }}</td>
                    <td class="actions">
                        <button type="button" class="btn btn--secondary btn--s" data-copy="{{ $key->code }}">Kopyala</button>
                        @if ($status === 'revoked')
                            <form method="POST" action="{{ route('admin.keys.restore', $key) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn--ghost btn--s">Geri aç</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.keys.revoke', $key) }}" data-confirm="Bu anahtar iptal edilsin mi? Müşteri kod alamaz.">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn--ghost btn--s">İptal et</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
