@php
    $editing = $package->exists;
    $chosen = array_map('intval', old('accounts', $chosen));
    $byPlatform = $accounts->groupBy(fn ($a) => $a->platformLabel());
@endphp
<x-layouts.app :title="$editing ? $package->name : 'Paket oluştur'">
    <div class="form-page">
        <a href="{{ route('admin.packages.index') }}" class="back">Paketlere dön</a>
        <div class="page-head">
            <div>
                <h1>{{ $editing ? $package->name : 'Paket oluştur' }}</h1>
                <p>Müşteri paketin anahtarını ekleyince bu listeden belirlediğin sayıda hesap seçer.</p>
            </div>
            @if ($editing)<a href="{{ route('admin.keys.index', ['hedef' => 'package:'.$package->id]) }}" class="btn btn--primary">Anahtar üret</a>@endif
        </div>

        <form method="POST" action="{{ $editing ? route('admin.packages.update', $package) : route('admin.packages.store') }}" data-busy novalidate>
            @csrf
            @if ($editing) @method('PUT') @endif

            <section class="card">
                <h2 class="card__title">1. Paket</h2>
                <div class="grid-2 section-gap">
                    <div class="field">
                        <label for="name">Paket adı</label>
                        <input id="name" name="name" type="text" class="input" maxlength="120" required value="{{ old('name', $package->name) }}" placeholder="3 platform seç" @error('name') aria-invalid="true" @enderror>
                        <p class="error">@error('name'){{ $message }}@enderror</p>
                    </div>
                    <div class="field">
                        <label for="pick_count">Müşteri kaç hesap seçsin?</label>
                        <input id="pick_count" name="pick_count" type="number" class="input" min="1" max="100" inputmode="numeric" required value="{{ old('pick_count', $package->pick_count) }}" @error('pick_count') aria-invalid="true" @enderror>
                        <p class="error">@error('pick_count'){{ $message }}@enderror</p>
                    </div>
                </div>
                <div class="field">
                    <label for="description">Müşteriye açıklama <span class="optional">(isteğe bağlı)</span></label>
                    <input id="description" name="description" type="text" class="input" maxlength="300" value="{{ old('description', $package->description) }}" placeholder="İstediğin üç platformu seç.">
                    <p class="error">@error('description'){{ $message }}@enderror</p>
                </div>
                <input type="hidden" name="is_active" value="0">
                <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $package->is_active ?? true))> Paket açık</label>
            </section>

            <section class="card">
                <h2 class="card__title">2. Paketteki hesaplar</h2>
                <p class="{{ $errors->has('accounts') || $errors->has('accounts.*') ? 'error' : 'card__lead' }}">
                    {{ $errors->first('accounts') ?: ($errors->first('accounts.*') ?: 'Müşteri bunların arasından seçer.') }}
                </p>
                @if ($accounts->isEmpty())
                    <p class="muted section-gap">Önce <a href="{{ route('admin.accounts.create') }}">hesap ekle</a>.</p>
                @else
                    @foreach ($byPlatform as $platform => $group)
                        <div class="picklist">
                            <p class="label">{{ $platform }}</p>
                            @foreach ($group as $account)
                                <label class="check">
                                    <input type="checkbox" name="accounts[]" value="{{ $account->id }}" @checked(in_array($account->id, $chosen, true))>
                                    <span class="{{ $account->is_active ? '' : 'muted' }}">{{ $account->title }}{{ $account->is_active ? '' : ', durduruldu' }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                @endif
            </section>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary btn--l" data-busy-text="Kaydediliyor">{{ $editing ? 'Kaydet' : 'Paketi oluştur' }}</button>
                <a href="{{ route('admin.packages.index') }}" class="btn btn--ghost btn--l">Vazgeç</a>
            </div>
        </form>

        @if ($editing)
            <form method="POST" action="{{ route('admin.packages.destroy', $package) }}" class="card section-gap row" data-confirm="{{ $package->name }} silinsin mi? Bağlı anahtarlar artık kod vermez.">
                @csrf @method('DELETE')
                <p class="muted small">Paketi silersen bağlı anahtarlar kod vermeyi bırakır. Hesaplar silinmez.</p>
                <button type="submit" class="btn btn--danger btn--s">Paketi sil</button>
            </form>
        @endif
    </div>
</x-layouts.app>
