<nav class="segmented" aria-label="Müşteriler ve kayıtlar">
    <a href="{{ route('admin.customers.index') }}" @if (request()->routeIs('admin.customers.*')) aria-current="page" @endif>Müşteriler</a>
    <a href="{{ route('admin.logs') }}" @if (request()->routeIs('admin.logs')) aria-current="page" @endif>Kayıtlar</a>
</nav>
