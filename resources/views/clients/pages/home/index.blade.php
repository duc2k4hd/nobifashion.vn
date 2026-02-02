@extends('clients.layouts.master')

@section('title',
    renderMeta(
    optional($settings)->site_title ??
    (optional($settings)->site_name ??
    'NOBI FASHION - Shop
    quần áo & phụ kiện thời trang'),
    ))

@section('head')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/home.css') }}">
    @if ((optional($banners->first())->order ?? 1) == 0)
        <link rel="preload" as="image"
            href="{{ asset('clients/assets/img/banners/' . (optional($banners->first())->image ?? 'banner.webp')) }}"
            fetchpriority="high">
    @endif

    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large"/>

    <meta name="keywords"
        content="{{ $settings->seo_keywords ?? 'NOBI FASHION, quần áo, phụ kiện, thời trang nam, thời trang nữ, áo phông, sơ mi, quần jean, váy, mũ nón, thắt lưng' }}">

    <meta name="description"
        content="{{ renderMeta($settings->site_description) ?? 'NOBI FASHION - Shop quần áo & phụ kiện thời trang: hàng mới, giá tốt, giao nhanh 1–3 ngày, đổi size 7 ngày.' }}">

    <meta http-equiv="date" content="{{ \Carbon\Carbon::parse('2025-06-11 13:10:59')->format('d/m/y') }}" />

    <meta property="og:title" content="{{ renderMeta($settings->site_title ?? ($settings->site_name ?? 'NOBI FASHION')) }}">
    <meta property="og:description"
        content="{{ renderMeta($settings->site_description) ?? 'Mua sắm quần áo & phụ kiện thời trang tại NOBI FASHION. Hàng mới, giá tốt, giao nhanh 1–3 ngày.' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image"
        content="{{ asset('clients/assets/img/business/' . ($settings->site_banner ?? null ?: $settings->site_logo ?? 'logo-nobi-fashion.png')) }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt"
        content="{{ renderMeta($settings->site_title ?? ($settings->site_name ?? 'NOBI FASHION')) }}">
    <meta property="og:image:type" content="image/webp">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ renderMeta($settings->site_name ?? 'NOBI FASHION') }}">
    <meta property="og:locale" content="vi_VN">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="{{ renderMeta($settings->site_name ?? 'NOBI FASHION') }}">
    <meta name="twitter:title"
        content="{{ renderMeta($settings->site_title ?? ($settings->site_name ?? 'NOBI FASHION')) }}">
    <meta name="twitter:description"
        content="{{ renderMeta($settings->site_description) ?? 'Thời trang chính hãng, form chuẩn, dễ phối đồ. Giao nhanh 1–3 ngày, đổi size 7 ngày.' }}">
    <meta name="twitter:image"
        content="{{ asset('clients/assets/img/business/' . ($settings->site_banner ?? null ?: $settings->site_logo ?? 'logo-nobi-fashion.png')) }}">
    <meta name="twitter:creator" content="{{ renderMeta($settings->site_name ?? 'NOBI FASHION') }}">

    <link rel="canonical" href="{{ $settings->site_url ?? '/' }}">
    <link rel="alternate" hreflang="vi" href="{{ $settings->site_url ?? '/' }}">
    <link rel="alternate" hreflang="x-default" href="{{ $settings->site_url ?? '/' }}">
@endsection

@section('foot')
    <script defer src="{{ asset('clients/assets/js/home.js') }}"></script>
@endsection

@section('schema')
    @include('clients.templates.schema_home')
@endsection

@section('content')
    <main class="nobifashion_main">
        <!-- Hero: Left categories + Center slider + Right side banners -->
        <section class="nobifashion_main_slider_main_hero">

            <!-- Left: Categories with hover submenus -->
            <aside class="nobifashion_main_slider_main_cats">
                <h2 class="nobifashion_main_slider_main_cats_title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M96 160C96 124.7 124.7 96 160 96L480 96C515.3 96 544 124.7 544 160L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 160zM160 160L160 224L224 224L224 160L160 160zM480 160L288 160L288 224L480 224L480 160zM160 288L160 352L224 352L224 288L160 288zM480 288L288 288L288 352L480 352L480 288zM160 416L160 480L224 480L224 416L160 416zM480 416L288 416L288 480L480 480L480 416z"/></svg>
                    Danh mục sản phẩm
                </h2>
                <ul class="nobifashion_main_slider_main_cats_list">
                    @foreach($categories as $cat)
                        <li class="nobifashion_main_slider_main_cats_item">
                            <button class="nobifashion_main_slider_main_cats_btn">{{ $cat->name }}<span>›</span></button>
                            @if($cat->children && $cat->children->count())
                                <div class="nobifashion_main_slider_main_cats_sub">
                                    <h3 class="nobifashion_main_slider_main_cats_sub_title">{{ $cat->name }}</h3>
                                    @foreach($cat->children as $child)
                                        <div class="nobifashion_main_slider_main_cats_sub_item">
                                            <a class="nobifashion_main_slider_main_cats_sub_link" href="/{{ $child->slug }}">{{ $child->name }}</a>
                                            @if($child->children && $child->children->count())
                                                <div class="nobifashion_main_slider_main_cats_sub2">
                                                    <h3 class="nobifashion_main_slider_main_cats_sub_title">{{ $child->name }}</h3>
                                                    @foreach($child->children as $grand)
                                                        <a href="/{{ $grand->slug }}">{{ $grand->name }}</a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </aside>

            <!-- Center: Slider from database banners -->
            <div class="nobifashion_main_slider_main_slider" id="homeSlider">
                <div class="nobifashion_main_slider_main_slider_track">
                    @foreach($banners as $i => $banner)
                        <div class="nobifashion_main_slider_main_slide">
                            <img {{ $i === 0 ? 'loading=eager fetchpriority=high' : 'loading=lazy' }} src="{{ asset('clients/assets/img/banners/' . ($banner->image_desktop ?? 'no-banner.webp')) }}" alt="{{ $banner->title ?? 'Banner' }}">
                        </div>
                    @endforeach
                </div>
                <div class="nobifashion_main_slider_main_nav">
                    <button type="button" id="homePrev">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#ffffff" d="M41.4 342.6C28.9 330.1 28.9 309.8 41.4 297.3L169.4 169.3C178.6 160.1 192.3 157.4 204.3 162.4C216.3 167.4 224 179.1 224 192L224 256L560 256C586.5 256 608 277.5 608 304L608 336C608 362.5 586.5 384 560 384L224 384L224 448C224 460.9 216.2 472.6 204.2 477.6C192.2 482.6 178.5 479.8 169.3 470.7L41.3 342.7z"/></svg>
                    </button>
                    <button type="button" id="homeNext">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#ffffff" d="M598.6 297.4C611.1 309.9 611.1 330.2 598.6 342.7L470.6 470.7C461.4 479.9 447.7 482.6 435.7 477.6C423.7 472.6 416 460.9 416 448L416 384L80 384C53.5 384 32 362.5 32 336L32 304C32 277.5 53.5 256 80 256L416 256L416 192C416 179.1 423.8 167.4 435.8 162.4C447.8 157.4 461.5 160.2 470.7 169.3L598.7 297.3z"/></svg>
                    </button>
                </div>
                <div class="nobifashion_main_slider_main_dots" id="homeDots"></div>
            </div>

            <!-- Right: side banners (external links for testing) -->
            <aside class="nobifashion_main_slider_main_side">
                @foreach($home_banner as $banner)
                    <a href="{{ $banner->link }}" target="{{ $banner->taget }}" rel="noopener">
                        <img src="{{ asset('clients/assets/img/banners/' . ($banner->image_desktop ?? 'no-banner.webp')) }}" alt="{{ $banner->title ?? 'Banner' }}">
                    </a>
                @endforeach
            </aside>

            <script>
                (function(){
                    const slider = document.getElementById('homeSlider');
                    if(!slider) return;
                    const track = slider.querySelector('.nobifashion_main_slider_main_slider_track');
                    const slides = Array.from(track.children);
                    const prev = document.getElementById('homePrev');
                    const next = document.getElementById('homeNext');
                    const dotsWrap = document.getElementById('homeDots');
                    let index = 0, timer;
                    function renderDots(){
                        dotsWrap.innerHTML = '';
                        slides.forEach((_,i)=>{
                            const b = document.createElement('button');
                            if(i===index) b.classList.add('active');
                            b.addEventListener('click',()=>{ go(i); });
                            dotsWrap.appendChild(b);
                        });
                    }
                    function go(i){
                        index = (i+slides.length)%slides.length;
                        track.style.transform = `translateX(-${index*100}%)`;
                        renderDots();
                        restart();
                    }
                    function restart(){ clearInterval(timer); timer=setInterval(()=>go(index+1), 4000); }
                    prev.addEventListener('click',()=>go(index-1));
                    next.addEventListener('click',()=>go(index+1));
                    renderDots();
                    restart();
                })();
            </script>
        </section>

        <hr>

        {{-- Flash Sale --}}
        @if ($flashSale && $flashSale->items && $flashSale->items->isNotEmpty())
            @php
                // Normalize flash sale end time to seconds (timezone-aware); JS expects ms
                $normalizeTs = function ($v) {
                    if ($v instanceof \Illuminate\Support\Carbon) {
                        return $v->timezone(config('app.timezone', 'Asia/Ho_Chi_Minh'))->timestamp;
                    }
                    if (is_numeric($v)) {
                        $n = (int) $v;
                        return $n > 2147483647 ? (int) floor($n / 1000) : $n; // ms to s
                    }
                    if (!empty($v)) {
                        return \Illuminate\Support\Carbon::parse($v, config('app.timezone', 'Asia/Ho_Chi_Minh'))->timestamp;
                    }
                    return null;
                };
                $homeFlashEndRaw = $flashSale->end_time ?? ($flashSale->ends_at ?? ($flashSale->endAt ?? null));
                $homeFlashEnd = $normalizeTs($homeFlashEndRaw) ?: now()->addHours(6)->timestamp;
            @endphp
            <script>
                const timeFlashSale = {{ $homeFlashEnd * 1000 }}; // milliseconds
            </script>
            <section class="nobifashion_flash_sale">
                <div class="nobifashion_flash_sale_header">
                    <h2 class="nobifashion_flash_sale_title">
                        FLASH SALE ⚡
                    </h2>
                    <div class="nobifashion_flash_sale_timer">
                        <span class="nobifashion_flash_sale_timer_days">0</span>
                        <small>Ngày</small>
                        <span class="nobifashion_flash_sale_timer_hours">0</span>
                        <small>Giờ</small>
                        <span class="nobifashion_flash_sale_timer_minutes">0</span>
                        <small>Phút</small>
                        <span class="nobifashion_flash_sale_timer_seconds">0</span>
                        <small>Giây</small>
                    </div>
                    <a href="{{ route('client.deals.index') }}" class="nobifashion_flash_sale_viewall">Xem tất cả</a>
                </div>

                <div class="nobifashion_flash_sale_wrapper">
                    <div class="nobifashion_flash_sale_list" id="flash-sale-scroll">
                        <!-- Lặp sản phẩm -->

                        @foreach ($flashSale->items as $productSale)
                            @if($productSale->is_active && ($productSale->stock > $productSale->sold) && $productSale->product && $productSale->product->is_active)
                                <div class="nobifashion_flash_sale_item">
                                    <div class="nobifashion_flash_sale_badge">
                                        {{ $productSale->product->primaryCategory->name ?? 'Sản phẩm' }}
                                    </div>
                                    <a href="/san-pham/{{ $productSale->product->slug ?? '' }}">
                                        <img src="{{ asset('clients/assets/img/clothes/' . ($productSale->product->primaryImage->url ?? 'no-image.webp')) }}"
                                            alt="{{ $productSale->product->primaryImage->alt ?? renderMeta($productSale->product->name ?? 'Sản phẩm thời trang') }}"
                                            class="nobifashion_flash_sale_img">
                                    </a>
                                    <div class="nobifashion_flash_sale_info">
                                        <h3 class="nobifashion_flash_sale_name">
                                            <a href="/san-pham/{{ $productSale->product->slug ?? '' }}">
                                                {{ renderMeta($productSale->product->name ?? 'Tên sản phẩm') }}
                                            </a>
                                        </h3>
                                        @php
                                            $originalPrice = $productSale->original_price ?? $productSale->product->price ?? 0;
                                            $salePrice = $productSale->sale_price ?? 0;
                                            $discountPercent = $originalPrice > 0 ? round((1 - $salePrice / $originalPrice) * 100) : 0;
                                        @endphp
                                        <div class="nobifashion_flash_sale_discount">
                                            ⚡-{{ $discountPercent }}%
                                        </div>
                                        <div class="nobifashion_flash_sale_prices">
                                            <div class="nobifashion_flash_sale_price sale">
                                                {{ number_format($salePrice, 0, ',', '.') }} ₫
                                            </div>
                                            <div class="nobifashion_flash_sale_price original">
                                                {{ number_format($originalPrice, 0, ',', '.') }} ₫
                                            </div>
                                        </div>

                                        <div class="nobifashion_flash_sale_sold">
                                            @php
                                                $sold = $productSale->sold ?? 0;
                                                $stock = $productSale->stock ?? 0;
                                                $percentSold = $stock > 0 ? ($sold / $stock) * 100 : 0;
                                                $remaining = max(0, $stock - $sold);
                                            @endphp

                                            @if ($remaining <= 0)
                                                <span class="flash-sale-sold-out">⚠️ HẾT HÀNG</span>
                                            @elseif($sold < 5 && $remaining > 0)
                                                <span class="flash-sale-hot">🔥 ĐANG BÁN CHẠY</span>
                                            @elseif($percentSold >= 90 && $remaining > 0)
                                                <span class="flash-sale-low">⚠️ SẮP HẾT HÀNG</span>
                                            @else
                                                <span class="flash-sale-sold">ĐÃ BÁN {{ $sold }}</span>
                                            @endif
                                            <style>
                                                .flash-sale-hot {
                                                    color: #e53935;
                                                    font-weight: bold;
                                                }

                                                .flash-sale-low {
                                                    color: #ff9800;
                                                    font-weight: bold;
                                                }

                                                .flash-sale-sold {
                                                    color: #423d3d;
                                                }

                                                .flash-sale-sold-out {
                                                    color: #b91c1c;
                                                    font-weight: bold;
                                                }
                                            </style>
                                        </div>
                                        <div class="nobifashion_flash_sale_progress">
                                            <div class="nobifashion_flash_sale_progress_fill"
                                                style="width: {{ $stock > 0 ? min(100, round(($sold / $stock) * 100)) : 0 }}%;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach

                    </div>

                    <!-- Nút điều hướng -->
                    <div class="nobifashion_flash_sale_nav nobifashion_flash_sale_prev">&#10094;</div>
                    <div class="nobifashion_flash_sale_nav nobifashion_flash_sale_next">&#10095;</div>
                </div>
            </section>
        @endif


        <hr>

        <!-- Danh mục nổi bật -->
        <section class="nobifashion_main_categories">
            <div class="nobifashion_main_categories_title">
                <h2 class="nobifashion_main_categories_title_name">Danh mục nổi bật</h2>
                <ul class="nobifashion_main_categories_title_parent">
                    @foreach ($categories as $category)
                        <li><a href="/{{ $category->slug ?? '' }}">{{ renderMeta($category->name ?? 'Danh mục') }}</a>
                        </li>
                    @endforeach
                </ul>
                <div class="nobifashion_main_categories_title_actions">
                    <div class="nobifashion_main_categories_title_actions_prev">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <path
                                d="M48 256a208 208 0 1 1 416 0A208 208 0 1 1 48 256zm464 0A256 256 0 1 0 0 256a256 256 0 1 0 512 0zM217.4 376.9c4.2 4.5 10.1 7.1 16.3 7.1c12.3 0 22.3-10 22.3-22.3l0-57.7 96 0c17.7 0 32-14.3 32-32l0-32c0-17.7-14.3-32-32-32l-96 0 0-57.7c0-12.3-10-22.3-22.3-22.3c-6.2 0-12.1 2.6-16.3 7.1L117.5 242.2c-3.5 3.8-5.5 8.7-5.5 13.8s2 10.1 5.5 13.8l99.9 107.1z" />
                        </svg>
                    </div>
                    <div class="nobifashion_main_categories_title_actions_next">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <path
                                d="M0 256a256 256 0 1 0 512 0A256 256 0 1 0 0 256zm395.3 11.3l-112 112c-4.6 4.6-11.5 5.9-17.4 3.5s-9.9-8.3-9.9-14.8l0-64-96 0c-17.7 0-32-14.3-32-32l0-32c0-17.7 14.3-32 32-32l96 0 0-64c0-6.5 3.9-12.3 9.9-14.8s12.9-1.1 17.4 3.5l112 112c6.2 6.2 6.2 16.4 0 22.6z" />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="nobifashion_main_categories_list">
                @foreach ($categories as $category)
                    @foreach ($category->children as $child)
                        @php
                            $productCount = App\Models\Product::active()->inCategory($child->id)->count();
                        @endphp

                        <div class="nobifashion_main_categories_item">
                            <a href="/{{ $child->slug }}">
                                <img loading="lazy" draggable="false"
                                    class="nobifashion_main_categories_item_img"
                                    src="{{ asset('clients/assets/img/categories/' . ($child->image ?? 'category.webp')) }}"
                                    alt="{{ $child->name }}">
                                <h3 class="nobifashion_main_categories_item_title">{{ $child->name }}</h3>
                                <p class="nobifashion_main_categories_item_quantity">{{ $productCount }} sản phẩm</p>
                            </a>
                        </div>
                    @endforeach
                @endforeach
            </div>

        </section>

        <hr>

        <!-- Ảnh khuyến mãi -->
        {{-- <section>
            <div class="nobifashion_main_promotion">
                @foreach ($vouchers->limit(3)->get() as $coupon)
                    <div class="nobifashion_main_promotion_item">
                        <img draggable="false" loading="lazy" class="nobifashion_main_promotion_item_img"
                            src="{{ asset('clients/assets/img/banners/' . ($coupon->image ?? 'banner.webp')) }}"
                            alt="{{ $coupon->name ?? 'Khuyến mãi thời trang NOBI FASHION' }}">
                        <div class="nobifashion_main_promotion_item_info">
                            <h4 class="nobifashion_main_promotion_item_info_title">
                                {{ $coupon->name ?? 'Khuyến mãi hấp dẫn' }}</h4>
                            <p class="nobifashion_main_promotion_item_info_desc">
                                {{ $coupon->description ?? 'Ưu đãi giới hạn: freeship, giảm % và quà tặng cho đơn hàng thời trang.' }}
                            </p>
                            <button class="nobifashion_main_promotion_item_info_btn">Khám phá ngay</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </section> --}}

        <hr>

        <!-- Sản phẩm phổ biến -->
        <section>
            <div class="nobifashion_main_popular_products">
                <div class="nobifashion_main_popular_products_title">
                    <h2 class="nobifashion_main_popular_products_title_name">Sản phẩm phổ biến</h2>
                    <div class="nobifashion_main_popular_products_title_view_all">
                        <a class="nobifashion_main_popular_products_title_view_all_active" href="{{ route('client.product.shop.index') }}">Xem tất cả</a>
                        @foreach ($categories as $category)
                            <a href="/{{ $category->slug ?? '' }}">{{ renderMeta($category->name ?? 'Danh mục') }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="nobifashion_main_popular_products_list">
                    @if ($productsFeatured->count() > 0)
                        @foreach ($productsFeatured as $product)
                            <div class="nobifashion_main_popular_products_item">
                                <div class="nobifashion_main_popular_products_item_label">
                                    <span class="nobifashion_main_popular_products_item_label_text">🔥 Đang thịnh
                                        hành</span>
                                </div>
                                <div class="nobifashion_main_popular_products_item_img">
                                    <img draggable="false" loading="lazy" width="160" height="160"
                                        class="nobifashion_main_popular_products_item_img_img"
                                        src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                        alt="{{ $product?->primary_image?->alt ?? renderMeta($product?->name ?? 'Sản phẩm thời trang') }}">
                                    <a class="nobifashion_main_popular_products_item_img_khung"
                                        href="/san-pham/{{ $product?->slug ?? '' }}">
                                        <img draggable="false" loading="lazy" width="160" height="160"
                                            src="{{ asset('clients/assets/img/frame/' . ($product?->frame ?? 'frame-default.webp')) }}"
                                            alt="Khung ảnh sản phẩm"
                                            title="{{ renderMeta($product?->name ?? 'Sản phẩm thời trang') }}">
                                    </a>
                                </div>
                                <div class="nobifashion_main_popular_products_item_info">
                                    <h4 class="nobifashion_main_popular_products_item_info_category">
                                        {{ renderMeta(optional($product?->primaryCategory)->name ?? 'Danh mục sản phẩm') }}
                                    </h4>
                                    <a href="/san-pham/{{ $product?->slug ?? '' }}">
                                        <h3 class="nobifashion_main_popular_products_item_info_title">
                                            {{ renderMeta($product?->name ?? 'Tên sản phẩm') }}</h3>
                                    </a>
                                    <div class="nobifashion_main_popular_products_item_info_rating">
                                        <span class="nobifashion_main_popular_products_item_info_rating_star">
                                            @php
                                                $star = rand(4, 5);
                                                for ($i = 1; $i <= $star; $i++) {
                                                    if ($star == 4) {
                                                        echo '<svg xmlns="http://www.w3.org/2000/svg" height="10" width="10" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#FFD43B" d="M341.5 45.1C337.4 37.1 329.1 32 320.1 32C311.1 32 302.8 37.1 298.7 45.1L225.1 189.3L65.2 214.7C56.3 216.1 48.9 222.4 46.1 231C43.3 239.6 45.6 249 51.9 255.4L166.3 369.9L141.1 529.8C139.7 538.7 143.4 547.7 150.7 553C158 558.3 167.6 559.1 175.7 555L320.1 481.6L464.4 555C472.4 559.1 482.1 558.3 489.4 553C496.7 547.7 500.4 538.8 499 529.8L473.7 369.9L588.1 255.4C594.5 249 596.7 239.6 593.9 231C591.1 222.4 583.8 216.1 574.8 214.7L415 189.3L341.5 45.1z"/></svg>';

                                                        if ($i == 4) {
                                                            echo '<svg xmlns="http://www.w3.org/2000/svg" height="10" width="10" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#FFD43B" d="M320.1 417.6C330.1 417.6 340 419.9 349.1 424.6L423.5 462.5L410.5 380C407.3 359.8 414 339.3 428.4 324.8L487.4 265.7L404.9 252.6C384.7 249.4 367.2 236.7 357.9 218.5L319.9 144.1L319.9 417.7zM489.4 553C482.1 558.3 472.4 559.1 464.4 555L320.1 481.6L175.8 555C167.8 559.1 158.1 558.3 150.8 553C143.5 547.7 139.8 538.8 141.2 529.8L166.4 369.9L52 255.4C45.6 249 43.4 239.6 46.2 231C49 222.4 56.3 216.1 65.3 214.7L225.2 189.3L298.8 45.1C302.9 37.1 311.2 32 320.2 32C329.2 32 337.5 37.1 341.6 45.1L415 189.3L574.9 214.7C583.8 216.1 591.2 222.4 594 231C596.8 239.6 594.5 249 588.2 255.4L473.7 369.9L499 529.8C500.4 538.7 496.7 547.7 489.4 553z"/></svg>';
                                                            break;
                                                        }
                                                    }
                                                    if ($star == 5) {
                                                        echo '<svg xmlns="http://www.w3.org/2000/svg" height="10" width="10" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#FFD43B" d="M341.5 45.1C337.4 37.1 329.1 32 320.1 32C311.1 32 302.8 37.1 298.7 45.1L225.1 189.3L65.2 214.7C56.3 216.1 48.9 222.4 46.1 231C43.3 239.6 45.6 249 51.9 255.4L166.3 369.9L141.1 529.8C139.7 538.7 143.4 547.7 150.7 553C158 558.3 167.6 559.1 175.7 555L320.1 481.6L464.4 555C472.4 559.1 482.1 558.3 489.4 553C496.7 547.7 500.4 538.8 499 529.8L473.7 369.9L588.1 255.4C594.5 249 596.7 239.6 593.9 231C591.1 222.4 583.8 216.1 574.8 214.7L415 189.3L341.5 45.1z"/></svg>';
                                                    }
                                                }
                                            @endphp
                                        </span>
                                        <span class="nobifashion_main_popular_products_item_info_rating_count">
                                            <a href="/san-pham/{{ $product?->slug ?? '' }}">({{ rand(10, 1000) }}
                                                review)</a>
                                        </span>
                                    </div>
                                    <div class="nobifashion_main_popular_products_item_info_price">
                                        @if (!empty($product?->sale_price) && $product?->sale_price < $product?->price)
                                            <span
                                                class="nobifashion_main_popular_products_item_info_price_new">{{ number_format($product?->sale_price ?? 0, 0, ',', '.') }}đ</span>
                                            <span
                                                class="nobifashion_main_popular_products_item_info_price_old">{{ number_format($product?->price ?? $product?->sale_price, 0, ',', '.') }}đ</span>
                                        @else
                                            <span class="nobifashion_main_popular_products_item_info_price_new">
                                                {{ number_format($product?->price ?? 0, 0, ',', '.') }} ₫
                                            </span>
                                        @endif
                                    </div>
                                    <a href="/san-pham/{{ $product?->slug ?? '' }}">
                                        <div class="nobifashion_main_popular_products_item_info_actions">
                                            <button
                                                class="nobifashion_main_popular_products_item_info_actions_add_to_cart">Xem
                                                sản phẩm</button>
                                            <button
                                                class="nobifashion_main_popular_products_item_info_actions_wishlist">Yêu
                                                thích</button>
                                            <button class="nobifashion_main_popular_products_item_info_actions_compare">So
                                                sánh</button>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        @endforeach

                    @endif
                </div>
            </div>
            <a class="nobifashion_main_popular_product_show_all" href="{{ route('client.product.shop.index') }}">Xem tất cả</a>
        </section>

        <hr>

        <section>
            <!-- Danh mục sản phẩm thời trang -->
            <h2 class="nobifashion_main_product_category_title">Thời trang NOBI FASHION</h2>
            <div class="nobifashion_main_product_category">
                <!-- Banner bên trái -->
                <div class="nobifashion_main_product_category_banner">
                    <img src="{{ asset('clients/assets/img/banners/banner-category.png') }}" alt="Banner Thời trang NOBI FASHION" />
                </div>

                <!-- Sản phẩm bên phải -->
                <div class="nobifashion_main_product_category_products">
                    @if ($productClothing->count() > 0)
                        @foreach ($productClothing as $product)
                            <div class="nobifashion_main_product_category_item">
                                <a draggable="false" href="/san-pham/{{ $product?->slug ?? '' }}">
                                    <img loading="lazy" src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                        alt="{{ $product?->primary_image?->alt ?? renderMeta($product?->name ?? 'Sản phẩm thời trang') }}">
                                    <div draggable="false" class="nobifashion_main_product_category_name">
                                        {{ renderMeta($product?->name ?? 'Tên sản phẩm') }}</div>
                                    <div class="nobifashion_main_product_category_price">
                                        <span
                                            class="nobifashion_main_product_category_price_current">{{ number_format($product?->sale_price ?? $product?->price ?? 0, 0, ',', '.') }}
                                            ₫</span>
                                    </div>
                                    <div class="nobifashion_main_product_category_actions">
                                        <a draggable="false" href="/san-pham/{{ $product?->slug ?? '' }}"
                                            class="nobifashion_main_product_category_actions_show">Xem sản phẩm</a>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </section>

        <hr>

        @include('clients.templates.call')

        <hr>

        <!-- Tính năng nổi bật -->
        <section>
            <div class="nobifashion_main_features_highlight">
                <div class="nobifashion_main_features_highlight_items">
                    <div class="nobifashion_main_features_highlight_items_item">
                        <img draggable="false" loading="lazy"
                            src="{{ asset('clients/assets/img/other/giao-hang-free-re8243t34.png') }}"
                            alt="🚚 Miễn phí vận chuyển">
                        <h3 class="nobifashion_main_features_highlight_items_item_title">🚚 Miễn phí vận chuyển</h3>
                        <p class="nobifashion_main_features_highlight_items_item_desc">Miễn phí giao hàng cho đơn từ
                            1.000.000đ tại NOBI FASHION.</p>
                    </div>
                    <div class="nobifashion_main_features_highlight_items_item">
                        <img draggable="false" loading="lazy"
                            src="{{ asset('clients/assets/img/other/ho-tro-24-7-398fhf384hf.jpg') }}"
                            alt="🤝 Hỗ trợ khách hàng 24/7">
                        <h3 class="nobifashion_main_features_highlight_items_item_title">🤝 Hỗ trợ khách hàng 24/7</h3>
                        <p class="nobifashion_main_features_highlight_items_item_desc">Đội ngũ CSKH luôn sẵn sàng hỗ trợ
                            bạn.</p>
                    </div>
                    <div class="nobifashion_main_features_highlight_items_item">
                        <img draggable="false" loading="lazy"
                            src="{{ asset('clients/assets/img/other/chinh_sach_doi_tra_hang-3489yfurhf34.jpg') }}"
                            alt="🔁 Chính sách đổi trả linh hoạt">
                        <h3 class="nobifashion_main_features_highlight_items_item_title">🔁 Chính sách đổi trả linh hoạt
                        </h3>
                        <p class="nobifashion_main_features_highlight_items_item_desc">Đổi size trong 7 ngày. Hỗ trợ nhanh
                            chóng, thuận tiện.</p>
                    </div>
                    <div class="nobifashion_main_features_highlight_items_item">
                        <img draggable="false" loading="lazy"
                            src="{{ asset('clients/assets/img/other/cam-ket-hang-chinh-hang-4387fy8734.png') }}"
                            alt="🏷️ Cam kết chính hãng">
                        <h3 class="nobifashion_main_features_highlight_items_item_title">🏷️ Cam kết chính hãng</h3>
                        <p class="nobifashion_main_features_highlight_items_item_desc">Sản phẩm chính hãng 100%, nguồn gốc
                            rõ ràng.</p>
                    </div>
                    <div class="nobifashion_main_features_highlight_items_item">
                        <img draggable="false" loading="lazy"
                            src="{{ asset('clients/assets/img/other/hinh-thuc-thanh-toan-an-toan-348yy82y4rf.jpg') }}"
                            alt="💳 Thanh toán an toàn">
                        <h3 class="nobifashion_main_features_highlight_items_item_title">💳 Thanh toán an toàn</h3>
                        <p class="nobifashion_main_features_highlight_items_item_desc">Nhiều phương thức thanh toán linh
                            hoạt, bảo mật.</p>
                    </div>
                </div>
            </div>
        </section>

        @if (session('welcome'))
            <script>
                showCustomToast(@json(session('welcome')), 'success');
            </script>
        @endif

        @include('clients.templates.chat')
    </main>
@endsection
