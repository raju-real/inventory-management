@props(['items'])
@if ($items->hasPages() || $items->total() > 0)
    <div class="pagination-wrap">{{ $items->onEachSide(1)->links() }}
        @if (! $items->hasPages())<p class="small text-muted">Showing {{ $items->count() }} of {{ $items->total() }} results</p>@endif
    </div>
@endif
