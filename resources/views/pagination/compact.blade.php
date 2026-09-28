@if ($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        
        // Vùng trang lân cận: tối đa 1 trang mỗi bên trang hiện tại
        $start = max(1, $currentPage - 1);
        $end = min($lastPage, $currentPage + 1);

        // Đảm bảo luôn hiển thị 3 trang nếu có thể
        if ($currentPage == 1) {
            $end = min($lastPage, 3);
        } elseif ($currentPage == $lastPage) {
            $start = max(1, $lastPage - 2);
        }
    @endphp

    <nav class="nobifashion_pagination_nav" aria-label="Điều hướng phân trang">
        <ul class="pagination nobifashion-pagination-list">
            {{-- Nút Trang trước --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled pagination-prev" aria-disabled="true" aria-label="Trang trước">
                    <span class="page-link" aria-hidden="true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </span>
                </li>
            @else
                <li class="page-item pagination-prev">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Trang trước">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </a>
                </li>
            @endif

            {{-- Trang đầu tiên nếu nằm ngoài vùng start --}}
            @if ($start > 1)
                <li class="page-item page-item-first {{ $currentPage == 1 ? 'active' : '' }}">
                    <a class="page-link" href="{{ $paginator->url(1) }}">1</a>
                </li>
                @if ($start > 2)
                    <li class="page-item disabled page-item-dots page-item-dots-start" aria-disabled="true">
                        <span class="page-link">&hellip;</span>
                    </li>
                @endif
            @endif

            {{-- Các trang ở giữa xung quanh trang hiện tại --}}
            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $currentPage)
                    <li class="page-item active" aria-current="page">
                        <span class="page-link">{{ $page }}</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                    </li>
                @endif
            @endfor

            {{-- Trang cuối cùng nếu nằm ngoài vùng end --}}
            @if ($end < $lastPage)
                @if ($end < $lastPage - 1)
                    <li class="page-item disabled page-item-dots page-item-dots-end" aria-disabled="true">
                        <span class="page-link">&hellip;</span>
                    </li>
                @endif
                <li class="page-item page-item-last {{ $currentPage == $lastPage ? 'active' : '' }}">
                    <a class="page-link" href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a>
                </li>
            @endif

            {{-- Nút Trang sau --}}
            @if ($paginator->hasMorePages())
                <li class="page-item pagination-next">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Trang sau">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                </li>
            @else
                <li class="page-item disabled pagination-next" aria-disabled="true" aria-label="Trang sau">
                    <span class="page-link" aria-hidden="true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
