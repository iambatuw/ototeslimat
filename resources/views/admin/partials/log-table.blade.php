<div class="table-wrap">
    <table class="table table--stack">
        <thead>
            <tr>
                <th scope="col">Zaman</th>
                <th scope="col">Olay</th>
                <th scope="col">Hesap</th>
                <th scope="col">Kullanıcı</th>
                <th scope="col">IP</th>
                <th scope="col">Ayrıntı</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                <tr>
                    <td class="nowrap"><time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->format('d.m.Y H:i:s') }}">{{ $log->created_at->format('d.m H:i') }}</time></td>
                    <td data-label="Olay"><span class="cell-title">{{ $log->label() }}</span></td>
                    <td data-label="Hesap">{{ $log->account?->title }}</td>
                    <td data-label="Kullanıcı">{{ $log->user?->email }}</td>
                    <td data-label="IP" class="mono nowrap">{{ $log->ip }}</td>
                    <td data-label="Ayrıntı" class="muted">@include('admin.partials.log-meta', ['log' => $log])</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
