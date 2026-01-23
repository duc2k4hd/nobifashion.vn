<div class="nobifashion_product_new">
    <h3 class="nobifashion_single_desc_tabs_describe_product_new_title">✨ Sản phẩm mới</h3>
    <div style="display: flex; align-items: center; justify-content: center; margin: 1rem 0;">
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
        <span style="padding: 0 12px; color: #f74a4a; font-weight: bold;">Sản phẩm mới</span>
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
    </div>
    <div class="nobifashion_single_desc_tabs_describe_product_new_grid">
        @if ($productNew->isNotEmpty())
            @foreach ($productNew as $new)
                <!-- Item -->
                <div class="nobifashion_single_desc_tabs_describe_product_new_item">
                    <div class="nobifashion_single_desc_tabs_describe_product_new_img">
                        <a href="/san-pham/{{ $new->slug ?? 'san-pham-moi' }}">
                            <img src="{{ asset('clients/assets/img/clothes/' . ($new->primaryImage->url ?? 'no-image.webp')) }}"
                                alt="Áo Thun Nam Basic">
                            <span class="nobifashion_single_desc_tabs_describe_product_new_badge">New</span>
                        </a>
                    </div>
                    <div class="nobifashion_single_desc_tabs_describe_product_new_info">
                        <h4 class="nobifashion_single_desc_tabs_describe_product_new_name">
                            <a href="/san-pham/{{ $new->slug ?? 'san-pham-moi' }}">{{ $new->name }}</a>
                        </h4>
                        <p class="nobifashion_single_desc_tabs_describe_product_new_price">
                            {{ number_format($new->sale_price ?? $new->price, 0, ',', '.') }}đ</p>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
