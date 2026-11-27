{{-- Oyun alanları: ekleme ve düzenleme ortak. $game, $platforms --}}
<div class="grid-2" data-game-form data-suggest="{{ route('admin.games.suggest') }}">
    <div class="field suggest">
        <label for="name">Oyun adı</label>
        <input id="name" name="name" type="text" class="input" value="{{ old('name', $game->name) }}" maxlength="120" required placeholder="Battlefield 1" autocomplete="off" data-game-name @error('name') aria-invalid="true" @enderror>
        <p class="{{ $errors->has('name') ? 'error' : 'hint' }}">{{ $errors->first('name') ?: 'Yazmaya başla; kapaklı öneriler çıkar, birine tıklarsan ad ve kapak dolar.' }}</p>
        <ul class="suggest__list" role="listbox" aria-label="Oyun önerileri" data-suggest-list hidden></ul>
    </div>
    <div class="field">
        <label for="platform">Platform</label>
        <select id="platform" name="platform" class="select">
            @foreach ($platforms as $key => $label)<option value="{{ $key }}" @selected(old('platform', $game->platform) === $key)>{{ $label }}</option>@endforeach
        </select>
        <p class="error">@error('platform'){{ $message }}@enderror</p>
    </div>
</div>
<div class="grid-2">
    <div class="field">
        <label for="cover_file">Kapak görseli</label>
        <input id="cover_file" name="cover_file" type="file" class="input" accept="image/png,image/jpeg,image/webp">
        <p class="{{ $errors->has('cover_file') ? 'error' : 'hint' }}">{{ $errors->first('cover_file') ?: 'Dikey kapak en iyisi (örn. 600×900). PNG, JPG ya da WEBP, en fazla 2 MB.' }}</p>
    </div>
    <div class="field">
        <label for="cover_url">ya da kapak bağlantısı</label>
        <input id="cover_url" name="cover_url" type="url" class="input" value="{{ old('cover_url', str_starts_with((string) $game->cover, 'https://') ? $game->cover : '') }}" maxlength="500" placeholder="https://.../kapak.jpg" data-game-cover @error('cover_url') aria-invalid="true" @enderror>
        <p class="{{ $errors->has('cover_url') ? 'error' : 'hint' }}">{{ $errors->first('cover_url') ?: 'Öneriden seçince kendiliğinden dolar. Dosya yüklersen o kullanılır.' }}</p>
        <img class="cover-preview" alt="Kapak önizlemesi" referrerpolicy="no-referrer" data-cover-preview hidden>
    </div>
</div>
<div class="field">
    <label for="note">Açıklama <span class="optional">(oyuna tıklayınca görünür)</span></label>
    <input id="note" name="note" type="text" class="input" value="{{ old('note', $game->note) }}" maxlength="300" placeholder="Deluxe sürüm, çevrim dışı oynanır">
    <p class="error">@error('note'){{ $message }}@enderror</p>
</div>
<div class="row">
    <input type="hidden" name="featured" value="0">
    <label class="check"><input type="checkbox" name="featured" value="1" @checked(old('featured', $game->featured))> Öne çıkar</label>
    <input type="hidden" name="is_visible" value="0">
    <label class="check"><input type="checkbox" name="is_visible" value="1" @checked(old('is_visible', $game->is_visible ?? true))> Katalogda göster</label>
</div>
