<div class="deal-card">
    @php
        $discount = ($product->old_price ?? 0) > 0 ? round(100 * (($product->old_price ?? 0) - ($product->price ?? 0)) / max(1, ($product->old_price ?? 1))) : 0;
        $avg = method_exists($product, 'averageRating') ? $product->averageRating() : ($product->avg_rating ?? 5);
        // Randomized sold/stock numbers for visual appeal
        $totalStock = rand(30, 120);
        $soldCount = rand((int) floor($totalStock * 0.2), $totalStock); // at least 20% sold
        $progress = (int) min(100, round(($soldCount / max(1, $totalStock)) * 100));
    @endphp
    @if ($discount > 0)
        <span class="deal-ribbon">-{{ $discount }}%</span>
    @endif
    <a class="deal-thumb" href="/san-pham/{{ $product->slug }}" title="{{ renderMeta($product->name) }}">
        <img src="{{ $product->primaryImage ? asset('clients/assets/img/clothes/' . $product->primaryImage->url) : asset('clients/assets/img/clothes/no-image.webp') }}" alt="{{ renderMeta($product->name) }}">
    </a>
    <div class="deal-info">
        <a class="deal-title" href="/san-pham/{{ $product->slug }}">{{ renderMeta($product->name) }}</a>
        <div class="deal-meta">
            <div class="deal-stars">
                <span class="deal-stars-fill" style="width: {{ min(100, ($avg/5)*100) }}%"></span>
            </div>
            <span class="deal-rating">{{ number_format($avg, 1) }}</span>
        </div>
        <div class="deal-price-row">
            <span class="deal-price">{{ number_format($product->price ?? 0, 0, '.', ',') }}đ</span>
            @if (($product->old_price ?? 0) > ($product->price ?? 0))
                <span class="deal-old">{{ number_format($product->old_price ?? 0, 0, '.', ',') }}đ</span>
            @endif
            <a class="deal-cart" href="/san-pham/{{ $product->slug }}" aria-label="Xem chi tiết">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M0 72C0 58.7 10.7 48 24 48L69.3 48C96.4 48 119.6 67.4 124.4 94L124.8 96L537.5 96C557.5 96 572.6 114.2 568.9 133.9L537.8 299.8C532.1 330.1 505.7 352 474.9 352L171.3 352L176.4 380.3C178.5 391.7 188.4 400 200 400L456 400C469.3 400 480 410.7 480 424C480 437.3 469.3 448 456 448L200.1 448C165.3 448 135.5 423.1 129.3 388.9L77.2 102.6C76.5 98.8 73.2 96 69.3 96L24 96C10.7 96 0 85.3 0 72zM160 528C160 501.5 181.5 480 208 480C234.5 480 256 501.5 256 528C256 554.5 234.5 576 208 576C181.5 576 160 554.5 160 528zM384 528C384 501.5 405.5 480 432 480C458.5 480 480 501.5 480 528C480 554.5 458.5 576 432 576C405.5 576 384 554.5 384 528zM336 142.4C322.7 142.4 312 153.1 312 166.4L312 200L278.4 200C265.1 200 254.4 210.7 254.4 224C254.4 237.3 265.1 248 278.4 248L312 248L312 281.6C312 294.9 322.7 305.6 336 305.6C349.3 305.6 360 294.9 360 281.6L360 248L393.6 248C406.9 248 417.6 237.3 417.6 224C417.6 210.7 406.9 200 393.6 200L360 200L360 166.4C360 153.1 349.3 142.4 336 142.4z"/></svg>
            </a>
        </div>
        <div class="deal-progress"><span style="width: {{ $progress }}%"></span></div>
        <div class="deal-sold">Đã bán: {{ $soldCount }}/{{ $totalStock }}</div>
    </div>
</div>
