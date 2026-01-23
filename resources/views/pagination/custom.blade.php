@php
    $perPage = request('perPage');
    $minPriceRange = request('minPriceRange');
    $maxPriceRange = request('maxPriceRange');
    $colorRange = request('colorRange');
    $sizeRange = request('sizeRange');
    $orderBy = request('orderBy');
    $prev_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#ff0000" d="M576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320C64 461.4 178.6 576 320 576C461.4 576 576 461.4 576 320zM188.7 308.7L292.7 204.7C297.3 200.1 304.2 198.8 310.1 201.2C316 203.6 320 209.5 320 216L320 272L416 272C433.7 272 448 286.3 448 304L448 336C448 353.7 433.7 368 416 368L320 368L320 424C320 430.5 316.1 436.3 310.1 438.8C304.1 441.3 297.2 439.9 292.7 435.3L188.7 331.3C182.5 325.1 182.5 314.9 188.7 308.7z"/></svg>';
    $next_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#ff0000" d="M64 320C64 461.4 178.6 576 320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320zM451.3 331.3L347.3 435.3C342.7 439.9 335.8 441.2 329.9 438.8C324 436.4 320 430.5 320 424L320 368L224 368C206.3 368 192 353.7 192 336L192 304C192 286.3 206.3 272 224 272L320 272L320 216C320 209.5 323.9 203.7 329.9 201.2C335.9 198.7 342.8 200.1 347.3 204.7L451.3 308.7C457.5 314.9 457.5 325.1 451.3 331.3z"/></svg>';
@endphp
@if ($paginator->hasPages())
    <ul class="pagination">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <li class="page-item disabled"><span class="page-link">{!! $prev_icon ?? '&laquo;' !!}</span></li>
        @else
            <li class="page-item"><a href="{{ $paginator->previousPageUrl() }}{{ isset($perPage) ? "&perPage=$perPage" : "" }}{{ (isset($minPriceRange) && isset($maxPriceRange)) ? "&minPriceRange=$minPriceRange&maxPriceRange=$maxPriceRange" : "" }}{{ isset($colorRange) ? "&colorRange=$colorRange" : "" }}{{ isset($sizeRange) ? "&sizeRange=$sizeRange" : "" }}" class="page-link" rel="prev">{!! $prev_icon ?? '&laquo;' !!}</a></li>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item"><a href="{{ $url }}{{ isset($perPage) ? "&perPage=$perPage" : "" }}{{ (isset($minPriceRange) && isset($maxPriceRange)) ? "&minPriceRange=$minPriceRange&maxPriceRange=$maxPriceRange" : "" }}{{ isset($colorRange) ? "&colorRange=$colorRange" : "" }}{{ isset($sizeRange) ? "&sizeRange=$sizeRange" : "" }}" class="page-link">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <li class="page-item"><a href="{{ $paginator->nextPageUrl() }}{{ isset($perPage) ? "&perPage=$perPage" : "" }}{{ (isset($minPriceRange) && isset($maxPriceRange)) ? "&minPriceRange=$minPriceRange&maxPriceRange=$maxPriceRange" : "" }}{{ isset($colorRange) ? "&colorRange=$colorRange" : "" }}{{ isset($sizeRange) ? "&sizeRange=$sizeRange" : "" }}" class="page-link" rel="next">{!! $next_icon ?? '&raquo;' !!}</a></li>
        @else
            <li class="page-item disabled"><span class="page-link">{!! $next_icon ?? '&raquo;' !!}</span></li>
        @endif
    </ul>
@endif
