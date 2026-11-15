<div class="table-wrap">
    <table class="table table--stack">
        <thead>
            <tr>
                <th scope="col">Anahtar</th>
                @if ($showAccount)<th scope="col">Hesap</th>@endif
                @if ($showPicks ?? false)<th scope="col">Seçilen hesaplar</th>@endif
                <th scope="col">Kural</th>
                <th scope="col">Durum</th>
                <th scope="col">Müşteri</th>
                <th scope="col" class="num">Kod</th>
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
                    @if ($showAccount)<td data-label="Hesap">{{ $key->targetTitle() }}</td>@endif
                    @if ($showPicks ?? false)<td data-label="Seçilen">{{ $key->selections->pluck("title")->implode(", ") ?: "Henüz yok" }}</td>@endif
                    <td data-label="Kural">{{ $key->rulesSummary() }}</td>
                    <td data-label="Durum">
                        {{ $key->statusLabel() }}
                        @if ($key->expires_at && $status === 'active')<span class="cell-sub">{{ $key->expires_at->format('d.m.Y') }} bitiş</span>@endif
                    </td>
                    <td data-label="Müşteri">{{ $key->customer?->email }}</td>
                    <td data-label="Kod" class="num">{{ $key->uses_count }}{{ $key->max_uses ? ' / '.$key->max_uses : '' }}</td>
                    <td class="actions">
                        <button type="button" class="btn btn--ghost btn--s" data-copy="{{ $key->code }}">Kopyala</button>
                        @if ($status === 'revoked')
                            <form method="POST" action="{{ route('admin.keys.restore', $key) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn--ghost btn--s">Geri aç</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.keys.revoke', $key) }}" data-confirm="Bu anahtar iptal edilsin mi? Müşteri kod alamaz.">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn--ghost btn--s">İptal</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
