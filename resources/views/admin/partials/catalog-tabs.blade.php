<nav class="segmented" aria-label="Hesaplar ve paketler">
    <a href="{{ route('admin.accounts.index') }}" @if (request()->routeIs('admin.accounts.index')) aria-current="page" @endif>Hesaplar</a>
    <a href="{{ route('admin.packages.index') }}" @if (request()->routeIs('admin.packages.index')) aria-current="page" @endif>Paketler</a>
</nav>
