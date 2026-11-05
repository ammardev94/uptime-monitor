<nav class="py-lg-5 pt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <!-- Previous Page Link -->
        @if ($paginator->onFirstPage())
            <li class="page-item disabled">
                <span class="page-link">«</span>
            </li>
        @else
            <li class="page-item">
                <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">«</a>
            </li>
        @endif

        <!-- Pagination Links -->
        @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
            @if ($page == $paginator->currentPage())
                <li class="page-item active">
                    <span class="page-link">{{ $page }}</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </li>
            @endif
        @endforeach

        <!-- Next Page Link -->
        @if ($paginator->hasMorePages())
            <li class="page-item">
                <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">»</a>
            </li>
        @else
            <li class="page-item disabled">
                <span class="page-link">»</span>
            </li>
        @endif
    </ul>
</nav>
