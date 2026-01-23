<div style="display: flex; align-items: center; justify-content: center; margin: 1rem 0;">
    <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
    <span style="padding: 0 12px; color: #f74a4a; font-weight: bold;">Sản phẩm liên quan</span>
    <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
</div>
<div class="nobifashion_single_product_related">
    <h3 class="nobifashion_single_product_related_title">📦 Sản phẩm liên quan</h3>

    <div class="nobifashion_single_product_related_grid">
        @if($productRelated->isNotEmpty())
            @foreach ($productRelated as $related)
                <!-- Item -->
                <div class="nobifashion_single_product_related_item">
                    <a href="/san-pham/{{ $related->slug ?? 'san-pham-lien-quan' }}" class="nobifashion_single_product_related_img">
                        <img src="{{ asset('clients/assets/img/clothes/'. ($related->primaryImage->url ?? 'no-image.webp')) }}" alt="{{ $related->name }}">
                        @if($related->is_featured)
                            <span class="nobifashion_single_product_related_badge">Hot</span>
                        @elseif($related->created_at->diffInDays(now()) <= 30)
                            <span class="nobifashion_single_product_related_badge">New</span>
                        @endif
                    </a>
                    <div class="nobifashion_single_product_related_info">
                        <a href="/san-pham/{{ $related->slug ?? 'san-pham-lien-quan' }}" class="nobifashion_single_product_related_name">{{ $related->name }}</a>
                        <p class="nobifashion_single_product_related_price">{{ number_format($related?->sale_price ?? $related?->price ?? 0, 0, ',', '.') }}đ</p>
                    </div>
                </div>
            @endforeach
        @else
            
        @endif
    </div>
</div>
