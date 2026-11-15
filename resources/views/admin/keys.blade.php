<x-layouts.app title="Anahtarlar">
    <div class="head">
        <div>
            <h1>Anahtarlar</h1>
            <p>Tüm hesapların anahtarları. Bir müşteri anahtarını yazdıysa tam haliyle burada arayabilirsin.</p>
        </div>
        <div class="actions-row">
            <a href="{{ route('admin.keys.export', request()->query()) }}" class="btn btn--ghost">CSV indir</a>
            <form method="POST" action="{{ route('admin.keys.purge') }}" data-confirm="İptal edilmiş, süresi dolmuş ve hakkı bitmiş tüm anahtarlar silinsin mi? Kayıtlar kalır.">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn--ghost">Eskileri temizle</button>
            </form>
        </div>
    </div>

    <form method="GET" class="filters" role="search">
        <div class="field field--grow">
            <label for="q">Ara</label>
            <input id="q" name="q" type="search" class="input" value="{{ $filters['q'] ?? '' }}" placeholder="Tam anahtar ya da not">
        </div>
        <div class="field">
            <label for="f-account">Hesap</label>
            <select id="f-account" name="account" class="select">
                <option value="">Tümü</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected(($filters['account'] ?? null) == $account->id)>{{ $account->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="f-status">Durum</label>
            <select id="f-status" name="status" class="select">
                <option value="">Tümü</option>
                @foreach (\App\Models\ProductKey::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn--ghost">Süz</button>
    </form>

    @if ($keys->isEmpty())
        <div class="empty">
            <h2>{{ array_filter($filters) ? 'Bu süzgece uyan anahtar yok' : 'Henüz anahtar yok' }}</h2>
            <p>{{ array_filter($filters) ? 'Süzgeci temizleyip tekrar bak.' : 'Hesaplar sayfasında bir hesap seçip anahtar üret.' }}</p>
            <a href="{{ array_filter($filters) ? route('admin.keys.index') : route('admin.accounts.index') }}" class="btn btn--ghost">{{ array_filter($filters) ? 'Süzgeci temizle' : 'Hesaplara git' }}</a>
        </div>
    @else
        @include('admin.partials.key-table', ['keys' => $keys, 'showAccount' => true])
        <x-pager :paginator="$keys" />
    @endif
</x-layouts.app>
