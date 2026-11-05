{{-- Numbered pagination styled for this app. Use with a LengthAwarePaginator: $items->onEachSide(1)->links('pagination.app') --}}
@if ($paginator->total() > 0)
<nav class="pager" role="navigation" aria-label="Pagination">
  <div class="small muted">
    Showing <strong>{{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }}</strong> of <strong>{{ number_format($paginator->total()) }}</strong>
  </div>
  @if ($paginator->hasPages())
  <div class="pager-links">
    @if ($paginator->onFirstPage())
      <span class="pager-btn disabled" aria-disabled="true">‹ Prev</span>
    @else
      <a class="pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Prev</a>
    @endif

    @foreach ($elements as $element)
      @if (is_string($element))
        <span class="pager-btn gap">…</span>
      @endif
      @if (is_array($element))
        @foreach ($element as $page => $url)
          @if ($page == $paginator->currentPage())
            <span class="pager-btn current" aria-current="page">{{ $page }}</span>
          @else
            <a class="pager-btn" href="{{ $url }}">{{ $page }}</a>
          @endif
        @endforeach
      @endif
    @endforeach

    @if ($paginator->hasMorePages())
      <a class="pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Next ›</a>
    @else
      <span class="pager-btn disabled" aria-disabled="true">Next ›</span>
    @endif
  </div>
  @endif
</nav>
@endif
