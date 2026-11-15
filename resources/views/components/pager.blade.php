@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="pager" aria-label="Sayfalar">
        <span>{{ $paginator->firstItem() }} ile {{ $paginator->lastItem() }} arası, toplam {{ $paginator->total() }}</span>
        <span class="pager__links">
            @if ($paginator->onFirstPage())
                <span class="btn btn--ghost btn--s" aria-disabled="true">Önceki</span>
            @else
                <a class="btn btn--ghost btn--s" href="{{ $paginator->previousPageUrl() }}" rel="prev">Önceki</a>
            @endif
            @if ($paginator->hasMorePages())
                <a class="btn btn--ghost btn--s" href="{{ $paginator->nextPageUrl() }}" rel="next">Sonraki</a>
            @else
                <span class="btn btn--ghost btn--s" aria-disabled="true">Sonraki</span>
            @endif
        </span>
    </nav>
@endif
