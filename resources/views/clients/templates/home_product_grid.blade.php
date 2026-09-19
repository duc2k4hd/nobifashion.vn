@if (isset($products) && $products->isNotEmpty())
    <section class="nobifashion_home_products" data-nobifashion-tone="light" aria-label="Sản phẩm gợi ý">
        <div class="nobifashion_home_container nobifashion_home_product_grid">
            @foreach ($products as $product)
                @php
                    $prodImages = $product->images->pluck('url')->filter()->values();
                    if ($prodImages->isEmpty() && $product->primaryImage) {
                        $prodImages = collect([$product->primaryImage->url]);
                    }
                    $imageUrls = $prodImages->map(function($img) {
                        return str_starts_with($img, 'http') ? $img : asset('clients/assets/img/clothes/' . $img);
                    })->values();
                    if ($imageUrls->isEmpty()) {
                        $imageUrls = collect([asset('clients/assets/img/clothes/no-image.webp')]);
                    }
                    $primaryImgUrl = $imageUrls->first();
                    $finalPrice = (float) ($product->sale_price ?? $product->price);
                    $discountPercent = ($product->price > 0 && $product->sale_price && $product->sale_price < $product->price)
                        ? round((($product->price - $product->sale_price) / $product->price) * 100)
                        : 0;
                    $colorVariants = $product->variants->filter(fn($v) => !empty($v->attributes['color']))->unique(fn($v) => $v->attributes['color']);
                @endphp
                <article class="nobifashion_home_product" data-nobifashion-product="{{ $product->sku ?? ('PROD-' . $product->id) }}"
                    data-nobifashion-price="{{ (int) $finalPrice }}"
                    data-nobifashion-images='@json($imageUrls)'>
                    <div class="nobifashion_home_product_gallery" tabindex="0" aria-label="Ảnh {{ $product->name }}">
                        <a class="nobifashion_home_product_gallery_link"
                            href="{{ url('/san-pham/' . $product->slug) }}">
                            <img class="nobifashion_home_product_image"
                                src="{{ $primaryImgUrl }}"
                                alt="{{ $product->name }}" width="750" height="1000" loading="lazy" decoding="async">
                        </a>
                        <button class="nobifashion_home_icon_button nobifashion_home_gallery_arrow" type="button"
                            aria-label="Ảnh trước" data-nobifashion-gallery data-direction="-1" @if($imageUrls->count() <= 1) hidden @endif>
                            <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
                                <path d="m15 5-7 7 7 7"></path>
                            </svg>
                        </button>
                        <button class="nobifashion_home_icon_button nobifashion_home_gallery_arrow" type="button"
                            aria-label="Ảnh tiếp theo" data-nobifashion-gallery data-direction="1" @if($imageUrls->count() <= 1) hidden @endif>
                            <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
                                <path d="m9 5 7 7-7 7"></path>
                            </svg>
                        </button>
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
                                        <button class="nobifashion_home_swatch" type="button" aria-label="{{ $colorName }}"
                                            aria-pressed="{{ $vIndex === 0 ? 'true' : 'false' }}" data-nobifashion-color="{{ $colorName }}"
                                            data-nobifashion-images='@json($imageUrls)'>
                                            <span class="nobifashion_home_swatch_dot" title="{{ $colorName }}" style="display:inline-block;width:14px;height:14px;border-radius:50%;border:1px solid #ccc;background-color: #222;"></span>
                                        </button>
                                    @endforeach
                                </div>
                                <button class="nobifashion_home_icon_button nobifashion_home_swatch_arrow" type="button"
                                    aria-label="Các màu phía trước" data-nobifashion-swatch-scroll="-1" hidden>
                                    <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
                                        <path d="m15 5-7 7 7 7"></path>
                                    </svg>
                                </button>
                                <button class="nobifashion_home_icon_button nobifashion_home_swatch_arrow" type="button"
                                    aria-label="Các màu tiếp theo" data-nobifashion-swatch-scroll="1" hidden>
                                    <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
                                        <path d="m9 5 7 7-7 7"></path>
                                    </svg>
                                </button>
                            @endif
                        </div>
                        <button class="nobifashion_home_icon_button nobifashion_home_favorite" type="button"
                            aria-label="Thêm vào yêu thích: {{ $product->name }}" data-nobifashion-favorite="{{ $product->sku ?? ('PROD-' . $product->id) }}"
                            aria-pressed="false">
                            <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
                                <path d="M20 9.2c0 3.4-8 9.5-8 9.5S4 12.6 4 9.2C4 4.4 9.5 3.3 12 7c2.5-3.7 8-2.6 8 2.2Z">
                                </path>
                            </svg>
                        </button>
                    </div>
                    <div class="nobifashion_home_product_info">
                        <span class="nobifashion_home_product_gender">{{ $product->primaryCategory?->name ?? 'NOBIFASHION' }}</span>
                        <h3 class="nobifashion_home_product_name">
                            <a href="{{ url('/san-pham/' . $product->slug) }}">{{ $product->name }}</a>
                        </h3>
                        <p class="nobifashion_home_product_price">
                            {{ number_format($finalPrice, 0, ',', '.') }} VND
                            @if ($product->sale_price && $product->sale_price < $product->price)
                                <del class="nobifashion_home_product_previous">{{ number_format($product->price, 0, ',', '.') }} VND</del>
                            @endif
                        </p>
                        @if ($discountPercent > 0)
                            <p class="nobifashion_home_product_flag">Giảm {{ $discountPercent }}%</p>
                        @elseif ($product->is_featured)
                            <p class="nobifashion_home_product_flag">Nổi bật</p>
                        @endif
                        <div class="nobifashion_home_rating" aria-label="5 trên 5 sao, 12 đánh giá">
                            <span class="nobifashion_home_rating_stars" aria-hidden="true">★★★★★</span>
                            <span>5 (12)</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
