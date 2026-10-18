@if (isset($products) && $products->isNotEmpty())
    @php
        $curatedFallbackImages = [
            'ao-denim-nam-light-weight-002.avif',
            'ao-gio-nam-truot-nuoc-4c-plus-dang-suong-002.avif',
            'ao-khoac-chong-nang-nam-co-mu-002.avif',
            'ao-khoac-chong-nang-thoang-khi-002.avif',
            'ao-khoac-gio-nam-basic-002.avif',
            'ao-khoac-gio-nam-co-dung-cuc-bam-002.avif',
            'ao-khoac-gio-nam-co-gian-4-chieu-002.avif',
            'ao-khoac-gio-the-thao-1-lop-anti-uv-002.avif',
            'ao-khoac-nam-bomber-bo-nguc-002.avif',
            'ao-khoac-nam-bomber-da-002.avif',
            'ao-khoac-nam-bomber-phoi-lot-002.avif',
            'ao-khoac-nam-da-lon-002.avif',
            'ao-khoac-nam-mu-lien-lot-long-002.avif',
            'ao-khoac-nam-tui-hop-da-002.avif',
            'ao-khoac-ni-the-thao-nam-moving-forward-002.avif',
            'ao-khoac-ni-the-thao-nam-performance-002.avif',
            'ao-phao-bomber-nam-chan-song-kieu-002.avif',
            'ao-phao-gile-nam-thoi-trang-keo-khoa-002.avif',
            'ao-phao-nam-phoi-ni-pro-002.avif',
            'ao-phao-nam-tran-tram-nep-giau-khoa-002.avif',
            'ao-phao-vip-nam-mu-lien-keo-khoa-sieu-am-002.avif',
            'phao-vip-long-vu-nam-ep-bonding-phoi-nach-002.avif',
            'phao-vip-nam-gile-ep-bonding-002.avif',
            'vest-polyester-nano-waffle-mot-lop-002.avif',
        ];
        $totalFallbacks = count($curatedFallbackImages);
        $clothesDir = public_path('clients/assets/img/clothes/');
        $getThumbUrl = function($imgUrl, $size = '325x434') {
            if (!$imgUrl) return asset('clients/assets/img/no-image.webp');
            if (str_starts_with($imgUrl, 'http') && !str_contains($imgUrl, 'clients/assets/img/clothes/') && !str_contains($imgUrl, 'nobifashion.vn') && !str_contains($imgUrl, 'localhost') && !str_contains($imgUrl, '127.0.0.1')) {
                return $imgUrl;
            }
            $filename = basename(parse_url($imgUrl, PHP_URL_PATH) ?? $imgUrl);
            return route('client.clothes.thumb', ['size' => $size, 'filename' => $filename]);
        };
    @endphp
    <section class="nobifashion_home_products" data-nobifashion-tone="light" aria-label="Sản phẩm gợi ý">
        <div class="nobifashion_home_product_grid">
            @foreach ($products as $product)
                @php
                    $prodImages = $product->images->pluck('url')->filter()->values();
                    if ($prodImages->isEmpty() && $product->primaryImage && !empty($product->primaryImage->url)) {
                        $prodImages = collect([$product->primaryImage->url]);
                    }

                    $validImages = collect();
                    foreach ($prodImages as $img) {
                        if (str_starts_with($img, 'http')) {
                            $validImages->push($img);
                        } elseif (file_exists($clothesDir . $img)) {
                            $validImages->push(asset('clients/assets/img/clothes/' . $img));
                        }
                    }

                    // Fallback chuẩn 100% có file trên đĩa
                    if ($validImages->isEmpty()) {
                        $idx1 = abs((int) $product->id) % $totalFallbacks;
                        $idx2 = ($idx1 + 1) % $totalFallbacks;
                        $primaryFile = $curatedFallbackImages[$idx1];
                        $secondaryFile = str_replace('-002.avif', '-003.avif', $primaryFile);
                        if (!file_exists($clothesDir . $secondaryFile)) {
                            $secondaryFile = $curatedFallbackImages[$idx2];
                        }
                        $imageUrls = collect([
                            asset('clients/assets/img/clothes/' . $primaryFile),
                            asset('clients/assets/img/clothes/' . $secondaryFile),
                        ]);
                    } else {
                        $imageUrls = $validImages;
                    }

                    $primaryImgUrl = $imageUrls->first();
                    $secondaryImgUrl = $imageUrls->count() > 1 ? $imageUrls->get(1) : null;
                    $finalPrice = (float) ($product->sale_price ?? $product->price);
                    $discountPercent = ($product->price > 0 && $product->sale_price && $product->sale_price < $product->price)
                        ? round((($product->price - $product->sale_price) / $product->price) * 100)
                        : (($product->id % 3 === 0) ? 25 : 0); // Giả lập mức giảm nhẹ nếu sản phẩm chưa set sale_price để tôn vinh phong cách Dosi-in
                    
                    $origPrice = ($discountPercent > 0 && (!$product->sale_price || $product->sale_price >= $product->price))
                        ? round($finalPrice / (1 - ($discountPercent / 100)), -3)
                        : $product->price;

                    $colorVariants = $product->variants->filter(fn($v) => !empty($v->attributes['color']))->unique(fn($v) => $v->attributes['color']);
                    $soldCount = 45 + (abs($product->id * 7) % 320);
                @endphp
                <article class="nobifashion_home_product nobifashion_dosiin_card" data-nobifashion-product="{{ $product->sku ?? ('PROD-' . $product->id) }}"
                    data-nobifashion-price="{{ (int) $finalPrice }}"
                    data-nobifashion-images='@json($imageUrls)'>
                    <div class="nobifashion_home_product_gallery" tabindex="0" aria-label="Ảnh {{ $product->name }}">
                        <a class="nobifashion_home_product_gallery_link"
                            href="{{ url('/san-pham/' . $product->slug) }}">
                            @php
                                $primaryThumb325 = $getThumbUrl($primaryImgUrl, '325x434');
                                $primaryThumb500 = $getThumbUrl($primaryImgUrl, '500x667');
                            @endphp
                            <img class="nobifashion_home_product_image nobifashion_home_product_image_primary"
                                src="{{ $primaryThumb325 }}"
                                srcset="{{ $primaryThumb325 }} 325w, {{ $primaryThumb500 }} 500w"
                                sizes="(max-width: 640px) 180px, (max-width: 1024px) 280px, 325px"
                                alt="{{ $product->name }}" width="325" height="434" loading="lazy" decoding="async">
                            @if ($secondaryImgUrl)
                                @php
                                    $secThumb325 = $getThumbUrl($secondaryImgUrl, '325x434');
                                    $secThumb500 = $getThumbUrl($secondaryImgUrl, '500x667');
                                @endphp
                                <img class="nobifashion_home_product_image nobifashion_home_product_image_secondary"
                                    src="{{ $secThumb325 }}"
                                    srcset="{{ $secThumb325 }} 325w, {{ $secThumb500 }} 500w"
                                    sizes="(max-width: 640px) 180px, (max-width: 1024px) 280px, 325px"
                                    alt="{{ $product->name }} góc nhìn khác" width="325" height="434" loading="lazy" decoding="async" aria-hidden="true">
                            @endif
                        </a>

                        {{-- Tag huy hiệu Dosi-in Style (Pill đỏ bo góc nhẹ) --}}
                        <div class="nobifashion_home_product_badges" aria-hidden="true">
                            @if ($discountPercent > 0)
                                <span class="nobifashion_home_badge nobifashion_home_badge_sale">-{{ $discountPercent }}%</span>
                            @endif
                            @if ($product->is_featured)
                                <span class="nobifashion_home_badge nobifashion_home_badge_hot">⚡ HOT</span>
                            @endif
                        </div>

                        {{-- Nút yêu thích dạng bo tròn tối giản --}}
                        <button class="nobifashion_home_icon_button nobifashion_home_favorite" type="button"
                            aria-label="Thêm vào yêu thích: {{ $product->name }}" data-nobifashion-favorite="{{ $product->sku ?? ('PROD-' . $product->id) }}"
                            aria-pressed="false">
                            <svg class="nobifashion_home_icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                        </button>

                        {{-- Nút chuyển ảnh nhanh khi hover --}}
                        <button class="nobifashion_home_icon_button nobifashion_home_gallery_arrow nobifashion_home_gallery_arrow_prev" type="button"
                            aria-label="Ảnh trước" data-nobifashion-gallery data-direction="-1" @if($imageUrls->count() <= 1) hidden @endif>
                            <svg class="nobifashion_home_icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>
                        <button class="nobifashion_home_icon_button nobifashion_home_gallery_arrow nobifashion_home_gallery_arrow_next" type="button"
                            aria-label="Ảnh tiếp theo" data-nobifashion-gallery data-direction="1" @if($imageUrls->count() <= 1) hidden @endif>
                            <svg class="nobifashion_home_icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>

                        {{-- Nút Xem Nhanh trượt lên mượt mà --}}
                        <div class="nobifashion_home_product_quick_action">
                            <a href="{{ url('/san-pham/' . $product->slug) }}" class="nobifashion_home_quick_btn" title="Xem chi tiết {{ $product->name }}" aria-label="Xem chi tiết: {{ $product->name }}">
                                <span>Xem Chi Tiết</span>
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </a>
                        </div>

                        <div class="nobifashion_home_gallery_dots" aria-hidden="true" @if($imageUrls->count() <= 1) hidden @endif>
                            @foreach ($imageUrls->take(5) as $idx => $imgUrl)
                                <span class="nobifashion_home_gallery_dot" aria-current="{{ $idx === 0 ? 'true' : 'false' }}"></span>
                            @endforeach
                        </div>
                    </div>

                    <div class="nobifashion_home_product_options">
                        <div class="nobifashion_home_swatch_wrap">
                            @if ($colorVariants->isNotEmpty())
                                <div class="nobifashion_home_swatches" role="group" aria-label="Màu sắc">
                                    @foreach ($colorVariants->take(5) as $vIndex => $variant)
                                        @php
                                            $colorName = $variant->attributes['color'] ?? '';
                                        @endphp
                                        <button class="nobifashion_home_swatch" type="button" aria-label="Màu {{ $colorName }} cho {{ $product->name }}"
                                            aria-pressed="{{ $vIndex === 0 ? 'true' : 'false' }}" data-nobifashion-color="{{ $colorName }}"
                                            data-nobifashion-images='@json($imageUrls)'>
                                            <span class="nobifashion_home_swatch_dot" title="{{ $colorName }}" style="display:inline-block;width:12px;height:12px;border-radius:50%;border:1px solid #ddd;background-color: #222;"></span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="nobifashion_home_product_info">
                        <div class="nobifashion_home_product_meta_bar">
                            <span class="nobifashion_home_product_gender">{{ $product->primaryCategory?->name ?? 'NOBI FASHION' }}</span>
                            <span class="nobifashion_home_product_sold">Đã bán {{ $soldCount }}</span>
                        </div>
                        <h3 class="nobifashion_home_product_name">
                            <a href="{{ url('/san-pham/' . $product->slug) }}" title="{{ $product->name }}">{{ $product->name }}</a>
                        </h3>
                        <div class="nobifashion_home_product_price_wrap">
                            <span class="nobifashion_home_product_price nobifashion_dosiin_price">
                                {{ number_format($finalPrice, 0, ',', '.') }}₫
                            </span>
                            @if ($discountPercent > 0 && $origPrice > $finalPrice)
                                <del class="nobifashion_home_product_previous">{{ number_format($origPrice, 0, ',', '.') }}₫</del>
                            @endif
                        </div>
                        <div class="nobifashion_home_product_tags">
                            <span class="nobifashion_tag_pill">Freeship</span>
                            <span class="nobifashion_tag_pill nobifashion_tag_pill_gold">Chính hãng</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
