{{-- Anahtar üretme formu. $field: account_id ya da package_id, $id: hedefin kimliği --}}
<form method="POST" action="{{ route('admin.keys.store') }}" class="gen" data-busy novalidate>
    @csrf
    <input type="hidden" name="{{ $field }}" value="{{ $id }}">
    <div class="gen__grid">
        <div class="field">
            <label for="count">Adet</label>
            <input id="count" name="count" type="number" class="input" min="1" max="500" value="{{ old('count', 10) }}" inputmode="numeric" required @error('count') aria-invalid="true" @enderror>
            <p class="error">@error('count'){{ $message }}@enderror</p>
        </div>
        <div class="field">
            <label for="max_uses">Kod hakkı</label>
            <input id="max_uses" name="max_uses" type="number" class="input" min="1" value="{{ old('max_uses') }}" inputmode="numeric" placeholder="Sınırsız" @error('max_uses') aria-invalid="true" @enderror>
            <p class="error">@error('max_uses'){{ $message }}@enderror</p>
        </div>
        <div class="field">
            <label for="valid_days">Süre (gün)</label>
            <input id="valid_days" name="valid_days" type="number" class="input" min="1" value="{{ old('valid_days') }}" inputmode="numeric" placeholder="Süresiz" @error('valid_days') aria-invalid="true" @enderror>
            <p class="error">@error('valid_days'){{ $message }}@enderror</p>
        </div>
        <div class="field">
            <label for="cooldown_minutes">Kodlar arası (dk)</label>
            <input id="cooldown_minutes" name="cooldown_minutes" type="number" class="input" min="0" value="{{ old('cooldown_minutes', 0) }}" inputmode="numeric" @error('cooldown_minutes') aria-invalid="true" @enderror>
            <p class="error">@error('cooldown_minutes'){{ $message }}@enderror</p>
        </div>
        <div class="field gen__label">
            <label for="label">Not</label>
            <input id="label" name="label" type="text" class="input" value="{{ old('label') }}" maxlength="120" placeholder="Sipariş no, pazar yeri">
            <p class="error">@error('label'){{ $message }}@enderror</p>
        </div>
    </div>
    <div class="gen__foot">
        <p class="hint">Süre, müşteri anahtarı eklediği an başlar. Aynı kodu tekrar görmek hak düşürmez.</p>
        <button type="submit" class="btn btn--primary" data-busy-text="Üretiliyor">Anahtar üret</button>
    </div>
</form>
