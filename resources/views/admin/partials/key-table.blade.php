@php($showCustomer = $showCustomer ?? true)
<div class="table-wrap">
    <table class="table table--stack">
        <thead>
            <tr>
                <th scope="col">Anahtar</th>
                <th scope="col">Ne için</th>
                <th scope="col">Durum</th>
                @if ($showCustomer)<th scope="col">Müşteri</th>@endif
                <th scope="col" class="num">Verilen kod</th>
                <th scope="col"><span class="visually-hidden">İşlem</span></th>
            </tr>
        </thead>
        <tbody class="stagger">
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
                        <span @class(['state', 'state--on' => $status === 'active', 'state--new' => $status === 'unused', 'state--bad' => in_array($status, ['revoked', 'expired', 'used_up'], true)])>{{ $key->statusLabel() }}</span>
                        @if ($key->expires_at && $status === 'active')<span class="cell-sub">{{ $key->expires_at->format('d.m.Y') }} bitiş</span>@endif
                    </td>
                    @if ($showCustomer)
                    <td data-label="Müşteri">
                        @if ($key->customer)
                            <a href="{{ route('admin.customers.show', $key->customer) }}" class="link-quiet">{{ $key->customer->email }}</a>
                        @else
                            {{ $key->redeemed_at ? 'Hızlı onay' : '' }}
                        @endif
                    </td>
                    @endif
                    <td data-label="Verilen kod" class="num">
                        {{ $key->uses_count }}{{ $key->max_uses ? ' / '.$key->max_uses : '' }}
                        @if ($key->period_limit && $key->redeemed_at)
                            @php($resets = $key->periodResetsAt())
                            <span class="cell-sub">{{ $resets ? 'Yeni hak '.$resets->format('d.m H:i') : 'Bu dönem '.$key->periodLeft().'/'.$key->period_limit }}</span>
                        @endif
                        @if ($key->bonus_uses)<span class="cell-sub">+{{ $key->bonus_uses }} ek hak</span>@endif
                    </td>
                    <td class="actions"><div class="row-actions">
                        <button type="button" class="btn btn--secondary btn--s" data-copy="{{ $key->code }}">Kopyala</button>
                        @if ($key->redeemed_at && ! in_array($status, ['revoked', 'expired'], true) && ($key->max_uses || $key->period_limit || $key->cooldown_minutes))
                            <form method="POST" action="{{ route('admin.keys.bonus', $key) }}" class="bonus">
                                @csrf @method('PATCH')
                                <input type="hidden" name="amount" value="1">
                                <button type="submit" class="btn btn--secondary btn--s" title="Müşteriye hemen kullanabileceği 1 ek kod hakkı ver" aria-label="{{ $key->customer?->email ?? 'Müşteriye' }} 1 ek kod hakkı ver">+1 hak ver</button>
                            </form>
                        @endif
                        @if ($status !== 'revoked' && ($key->expires_at || (! $key->redeemed_at && $key->valid_days)))
                            <form method="POST" action="{{ route('admin.keys.extend', $key) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="days" value="30">
                                <button type="submit" class="btn btn--secondary btn--s" title="Süreyi 30 gün uzat; bitmişse bugünden başlar">+30 gün</button>
                            </form>
                        @endif
                        @if ($status === 'revoked')
                            <form method="POST" action="{{ route('admin.keys.restore', $key) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn--ghost btn--s">Geri aç</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.keys.revoke', $key) }}" data-confirm="Bu anahtar iptal edilsin mi? Müşteri kod alamaz.">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn--danger btn--s">İptal et</button>
                            </form>
                        @endif
                    </div></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
