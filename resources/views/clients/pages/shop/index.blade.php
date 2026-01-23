@extends('clients.layouts.master')

@section('title', renderMeta($pageTitle))

@section('head')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/shop.css') }}">

    <!-- 🔑 Keywords -->
    <meta name="keywords" content="{{ renderMeta($pageKeywords) }}">

    <!-- 📝 Description -->
    <meta name="description" content="{{ renderMeta($pageDescription) }}">

    <!-- 🤖 Robots -->
    @php
        $productCount = $products->count() ?? 0;
    @endphp
    @if ($productCount < 5)
        <meta name="robots" content="noindex, follow" />
    @else
        <meta name="robots" content="index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    @endif

    <!-- 📅 Date -->
    <meta http-equiv="date" content="{{ now()->format('d/m/Y') }}" />

    <!-- 🌐 Open Graph -->
    <meta property="og:title" content="{{ renderMeta($pageTitle) }}">
    <meta property="og:description" content="{{ renderMeta($pageDescription) }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $pageImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ renderMeta($pageTitle) }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ renderMeta($settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM') }}">
    <meta property="og:locale" content="vi_VN">

    <!-- 🐦 Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ renderMeta($pageTitle) }}">
    <meta name="twitter:description" content="{{ renderMeta($pageDescription) }}">
    <meta name="twitter:image" content="{{ $pageImage }}">
    <meta name="twitter:creator" content="{{ renderMeta($settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM') }}">

    <!-- 🔗 Canonical & hreflang -->
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="alternate" hreflang="vi" href="{{ $canonicalUrl }}">
    <link rel="alternate" hreflang="x-default" href="{{ $canonicalUrl }}">
@endsection


@section('foot')
    <script src="{{ asset('clients/assets/js/shop.js') }}"></script>
@endsection

@section('schema')
    @include('clients.templates.schema_shop', [
        'products' => $products?->orderBy('created_at', 'desc')->paginate(30),
    ])
@endsection

@section('content')
    <main class="nobifashion_shop">
        <!-- Breadcrumb -->
        <section>
            <div class="nobifashion_shop_breadcrumb">
                <a href="{{ url('/') }}">Trang chủ</a>
                <span class="separator">>></span>

                @if ($category)
                    @php
                        // Tạo breadcrumb path từ danh mục hiện tại lên danh mục gốc
                        $breadcrumbPath = collect();
                        $currentCategory = $category;

                        while ($currentCategory) {
                            $breadcrumbPath->prepend($currentCategory);
                            $currentCategory = $currentCategory->parent;
                        }
                    @endphp

                    @foreach ($breadcrumbPath as $breadcrumb)
                        @if ($loop->last)
                            <span class="breadcrumb-current">{{ $breadcrumb->name }}</span>
                        @else
                            <a href="{{ route('client.product.category.index', $breadcrumb->slug) }}">{{ $breadcrumb->name }}</a>
                            <span class="separator">>></span>
                        @endif
                    @endforeach
                @else
                    <span>Shop</span>
                @endif
            </div>
        </section>

        <!-- Banner -->
        {{-- <section>
            <div class="nobifashion_shop_banner">
                @if ($banner && $banner->count() > 0)
                    <img class="nobifashion_shop_banner_image"
                        src="{{ asset('clients/assets/img/banners/' . $banner->image) }}" alt="{{ $banner->title }}">
                @endif
            </div>
        </section> --}}

        <!-- Bộ lọc -->
        <section>
            <div class="nobifashion_shop_products">
                <div class="nobifashion_shop_products_filter">
                    <div class="nobifashion_shop_products_filter_categories">
                        <div class="nobifashion_shop_products_filter_categories_title">
                            <h3 class="nobifashion_shop_products_filter_categories_title_name">Lọc sản phẩm</h3>
                            <div class="nobifashion_shop_products_filter_categories_title_bars">
                                <svg focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24">
                                    <path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="nobifashion_shop_products_filter_categories_content">
                            @foreach ($categories as $category)
                                {{-- @php
                                    $productsCategories = \App\Models\Product::active()
                                        ->withAnyCategory($category->category_ids)
                                        ->inRandomOrder()
                                        ->limit(5);
                                @endphp --}}
                                <div
                                    class="nobifashion_shop_products_filter_categories_content_category {{ $category->slug === request()->segment(1) ? '.nobifashion_shop_products_filter_categories_content_category_active' : '' }}">
                                    {{-- Nếu có slug thì hiển thị link --}}
                                    <div class="nobifashion_shop_products_filter_categories_content_category_image">
                                        <a href="/{{ $category->slug }}">
                                            <img width="30px" height="30px"
                                                class="nobifashion_shop_products_filter_categories_content_category_image_img"
                                                src="{{ asset('clients/assets/img/categories/' . ($category->image ?? '')) }}"
                                                alt="{{ $category->name }}">
                                        </a>
                                    </div>
                                    <div class="nobifashion_shop_products_filter_categories_content_category_text">
                                        <a href="/{{ $category->slug }}">
                                            <p>{{ $category->name }}</p>
                                        </a>
                                    </div>
                                    {{-- <div
                                        class="nobifashion_shop_products_filter_categories_content_category_quantity">
                                        <span>{{ $productsCategories ? $productsCategories->get()->count() : 0 }}</span>
                                    </div> --}}
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="nobifashion_shop_products_filter_categories_form">
                        <!-- Bộ lọc giá -->
                        <div class="nobifashion_shop_products_filter_price">
                            <h4 class="nobifashion_shop_products_filter_price_title">Lọc theo giá</h4>
                            <div class="nobifashion_shop_products_filter_price_content">
                                <form id="nobifashion_shop_products_filter_price_content_form"
                                    action="/{{ request()->segment(0) ?? 'shop' }}" method="GET"
                                    class="nobifashion_shop_products_filter_price_form">
                                    {{-- Giữ lại các filter hiện tại --}}
                                    <input type="hidden" name="page" value="{{ $page ?? 1 }}">

                                    <input type="hidden" name="perPage" value="{{ $perPage ?? 30 }}">

                                    @if (isset($colorRange))
                                        <input type="hidden" name="colorRange"
                                            value="{{ is_array($colorRange) ? implode(',', $colorRange) : $colorRange }}">
                                    @endif

                                    @if (isset($sizeRange))
                                        <input type="hidden" name="sizeRange"
                                            value="{{ is_array($sizeRange) ? implode(',', $sizeRange) : $sizeRange }}">
                                    @endif

                                    {{-- Đây là input sẽ được gán giá trị bằng JS --}}
                                    <input type="hidden" name="minPriceRange" id="minPriceRange">
                                    <input type="hidden" name="maxPriceRange" id="maxPriceRange">

                                    <label
                                        class="nobifashion_shop_products_filter_price_content_form_label {{ (int) $maxPriceRange === 500000 ? '.nobifashion_shop_products_filter_price_content_form_label_active' : '' }}"
                                        onclick="setPrice(0, 500000)">
                                        Dưới 500.000 VNĐ
                                    </label>

                                    <label
                                        class="nobifashion_shop_products_filter_price_content_form_label {{ (int) $minPriceRange === 500000 && (int) $maxPriceRange === 1000000 ? '.nobifashion_shop_products_filter_price_content_form_label_active' : '' }}"
                                        onclick="setPrice(500000, 1000000)">
                                        500.000 - 1.000.000 VNĐ
                                    </label>

                                    <label
                                        class="nobifashion_shop_products_filter_price_content_form_label {{ (int) $minPriceRange === 1000000 && (int) $maxPriceRange === 2000000 ? '.nobifashion_shop_products_filter_price_content_form_label_active' : '' }}"
                                        onclick="setPrice(1000000, 2000000)">
                                        1.000.000 - 2.000.000 VNĐ
                                    </label>

                                    <label
                                        class="nobifashion_shop_products_filter_price_content_form_label {{ (int) $minPriceRange === 2000000 && empty($maxPriceRange) ? '.nobifashion_shop_products_filter_price_content_form_label_active' : '' }}"
                                        onclick="setPrice(2000000, 100000000)">
                                        Trên 2.000.000 VNĐ
                                    </label>
                                </form>
                            </div>
                        </div>

                        <!-- Bộ lọc màu sắc -->
                        <div class="nobifashion_shop_products_filter_color">
                            <h4 class="nobifashion_shop_products_filter_color_title">Lọc theo màu sắc
                            </h4>
                            <div class="nobifashion_shop_products_filter_color_content">
                                <form action="/{{ request()->segment(0) ?? 'shop' }}" method="GET"
                                    class="nobifashion_shop_products_filter_color_form">
                                    {{-- Giữ lại các filter hiện tại --}}
                                    <input type="hidden" name="page" value="{{ $page ?? 1 }}">

                                    <input type="hidden" name="perPage" value="{{ $perPage ?? 30 }}">

                                    @if (isset($minPriceRange))
                                        <input type="hidden" name="minPriceRange" value="{{ $minPriceRange }}">
                                    @endif

                                    @if (isset($maxPriceRange))
                                        <input type="hidden" name="maxPriceRange" value="{{ $maxPriceRange }}">
                                    @endif

                                    @foreach (['Đen', 'Trắng', 'Xám', 'Ghi sáng', 'Xanh navy', 'Xanh da trời', 'Xanh lá', 'Xanh rêu', 'Đỏ', 'Đỏ đất', 'Vàng', 'Cam', 'Hồng', 'Hồng pastel', 'Be', 'Nâu', 'Tím', 'Tím than'] as $color)
                                        <label
                                            class="{{ $colorRange === $color ? '.nobifashion_shop_products_filter_color_form_label_active' : '' }}">
                                            <input type="radio" name="colorRange"
                                                class="nobifashion_shop_products_filter_color_checkbox"
                                                value="{{ $color }}" onchange="this.form.submit()"
                                                {{ $colorRange === $color ? 'checked' : '' }}>
                                            {{ $color }}
                                        </label>
                                    @endforeach

                                    <label
                                        class="{{ !isset($colorRange) || empty($colorRange) ? '.nobifashion_shop_products_filter_color_form_label_active' : '' }}">
                                        <input type="radio" name="colorRange"
                                            class="nobifashion_shop_products_filter_color_checkbox" value=""
                                            onchange="this.form.submit()"
                                            {{ !isset($colorRange) || empty($colorRange) ? 'checked' : '' }}>
                                        Bỏ lọc màu sắc
                                    </label>

                                    {{-- Giữ lại các filter hiện tại --}}

                                    @if (isset($sizeRange))
                                        <input type="hidden" name="sizeRange"
                                            value="{{ is_array($sizeRange) ? implode(',', $sizeRange) : $sizeRange }}">
                                    @endif
                                </form>
                            </div>
                        </div>

                        <!-- Bộ lọc size -->
                        <div class="nobifashion_shop_products_filter_size">
                            <h4 class="nobifashion_shop_products_filter_size_title">Lọc theo kích cỡ</h4>
                            <div class="nobifashion_shop_products_filter_size_content">
                                <form action="/{{ request()->segment(0) ?? 'shop' }}" method="GET"
                                    class="nobifashion_shop_products_filter_size_form">

                                    {{-- Giữ lại filter hiện tại --}}
                                    <input type="hidden" name="page" value="{{ $page ?? 1 }}">
                                    <input type="hidden" name="perPage" value="{{ $perPage ?? 30 }}">

                                    @if (isset($minPriceRange))
                                        <input type="hidden" name="minPriceRange" value="{{ $minPriceRange }}">
                                    @endif

                                    @if (isset($maxPriceRange))
                                        <input type="hidden" name="maxPriceRange" value="{{ $maxPriceRange }}">
                                    @endif

                                    @if (isset($colorRange))
                                        <input type="hidden" name="colorRange" value="{{ $colorRange }}">
                                    @endif

                                    {{-- Filter size --}}
                                    @foreach (['S', 'M', 'L', 'XL', 'XXL'] as $size)
                                        <label
                                            class="{{ $sizeRange === $size ? '.nobifashion_shop_products_filter_size_form_label_active' : '' }}">
                                            <input type="radio" name="sizeRange"
                                                class="nobifashion_shop_products_filter_size_checkbox"
                                                value="{{ $size }}" onchange="this.form.submit()"
                                                {{ $sizeRange === $size ? 'checked' : '' }}>
                                            Size {{ $size }}
                                        </label>
                                    @endforeach

                                    {{-- Bỏ lọc --}}
                                    <label
                                        class="{{ empty($sizeRange) ? '.nobifashion_shop_products_filter_size_form_label_active' : '' }}">
                                        <input type="radio" name="sizeRange"
                                            class="nobifashion_shop_products_filter_size_checkbox" value=""
                                            onchange="this.form.submit()" {{ empty($sizeRange) ? 'checked' : '' }}>
                                        Bỏ lọc kích cỡ
                                    </label>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="nobifashion_shop_products_filter_new_products">
                        <h4 class="nobifashion_shop_products_filter_new_products_title">Sản phẩm mới</h4>
                        <div class="nobifashion_shop_products_filter_new_products_description">
                            <p>Khám phá những sản phẩm mới nhất tại Shop {{ $settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM' }}. Chúng tôi luôn cập
                                nhật
                                những mẫu mã mới, chất lượng và phong cách để phục vụ nhu cầu mua sắm của bạn.</p>
                        </div>
                        @if ((clone $products)->new()->count() > 0)
                            @foreach ((clone $products)->new()->inRandomOrder()->limit(4)->get() as $product)
                                <div class="nobifashion_shop_products_filter_new_products_item">
                                    <div class="nobifashion_shop_products_filter_new_products_item_image">
                                        <a href="{{ $product->canonical_url }}">
                                            <img class="nobifashion_shop_products_filter_new_products_item_image_img"
                                                src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                                alt="{{ $product?->primaryImage?->alt ?? $product?->name }}"
                                                title="{{ $product?->primaryImage?->title }}">
                                        </a>
                                    </div>
                                    <div class="nobifashion_shop_products_filter_new_products_item_info">
                                        <a href="{{ $product->canonical_url }}">
                                            <h4 class="nobifashion_shop_products_filter_new_products_item_info_title">
                                                {{ $product->name }}</h4>
                                        </a>
                                        <p class="nobifashion_shop_products_filter_new_products_item_info_price">
                                            {{ number_format($product->price, 0, ',', '.') }}đ</p>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                @php
                    $query = $products; // Builder từ controller

                    $query = $query
                        ->when(isset($minPriceRange) && isset($maxPriceRange), function ($q) use (
                            $minPriceRange,
                            $maxPriceRange,
                        ) {
                            return $q->priceFilter($minPriceRange, $maxPriceRange);
                        })
                        ->when(isset($colorRange), function ($q) use ($colorRange) {
                            return $q->colorFilter($colorRange);
                        })
                        ->when(isset($sizeRange), function ($q) use ($sizeRange) {
                            return $q->sizeFilter($sizeRange);
                        })
                        ->orderBy('created_at', 'desc');

                    $productsMain = $query->paginate((int) ($perPage ?? 30));
                @endphp

                <div class="nobifashion_shop_products_content">
                    <div class="nobifashion_shop_products_content_filter">
                        <div class="nobifashion_shop_products_content_filter_total">
                            Tổng <span>{{ $productsMain->total() ?? 0 }}</span> sản phẩm
                        </div>
                        @if (request()->query())
                            {{-- Có ít nhất 1 bộ lọc đang được áp dụng --}}
                            <div class="nobifashion_shop_products_content_filter_delete_all">
                                <button class="nobifashion_shop_products_content_filter_delete_all_btn"
                                    onclick="window.location.href='/shop'">
                                    Xóa tất cả bộ lọc
                                </button>
                            </div>
                        @endif
                        <div class="nobifashion_shop_products_content_filter_select">
                            <div class="nobifashion_shop_products_content_filter_select_sort">
                                <label for="sort">Sắp xếp theo:</label>
                                <select name="sort" id="sort">
                                    <option value="default">Mặc định</option>
                                    <option value="popularity">Phổ biến</option>
                                    <option value="rating">Đánh giá cao</option>
                                    <option value="price-asc">Giá: Thấp đến Cao</option>
                                    <option value="price-desc">Giá: Cao đến Thấp</option>
                                    <option value="newest">Mới nhất</option>
                                </select>
                            </div>

                            <div class="nobifashion_shop_products_content_filter_select_show">
                                <label for="show">Hiển thị:</label>
                                <form action="/shop" method="GET"
                                    class="nobifashion_shop_products_content_filter_select_show_form">
                                    {{-- Giữ lại các filter hiện tại --}}
                                    <input type="hidden" name="page" value="{{ $page ?? 1 }}">

                                    {{-- Select số sản phẩm --}}
                                    <select name="perPage" id="perPage" onchange="this.form.submit()">
                                        <option value="{{ $perPage }}" selected>Đang hiển thị {{ $perPage }}
                                            sản phẩm</option>

                                        @foreach ([24, 36, 48, 60, 72, 84, 96] as $val)
                                            @if ((int) $perPage !== $val)
                                                <option value="{{ $val }}">{{ $val }} sản phẩm</option>
                                            @endif
                                        @endforeach
                                    </select>

                                    @if (isset($minPriceRange))
                                        <input type="hidden" name="minPriceRange" value="{{ $minPriceRange }}">
                                    @endif

                                    @if (isset($maxPriceRange))
                                        <input type="hidden" name="maxPriceRange" value="{{ $maxPriceRange }}">
                                    @endif

                                    @if (isset($colorRange))
                                        <input type="hidden" name="colorRange"
                                            value="{{ is_array($colorRange) ? implode(',', $colorRange) : $colorRange }}">
                                    @endif

                                    @if (isset($sizeRange))
                                        <input type="hidden" name="sizeRange"
                                            value="{{ is_array($sizeRange) ? implode(',', $sizeRange) : $sizeRange }}">
                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>
                    @if (!empty($productsMain) && $productsMain->count() > 0)
                        <div class="nobifashion_shop_products_content_list">
                            @foreach ($productsMain as $product)
                                <div class="nobifashion_shop_products_content_list_item">
                                    <div class="nobifashion_shop_products_content_list_item_label">
                                        {{ $product->label }}
                                    </div>
                                    <div class="nobifashion_shop_products_content_list_item_image">
                                        <a href="{{ route('client.product.detail', ['slug' => $product->slug]) }}">
                                            <img class="nobifashion_shop_products_content_list_item_image_img"
                                                src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                                alt="{{ $product?->primaryImage?->alt ?? $product?->name }}"
                                                title="{{ $product?->primaryImage?->title ?? $product?->name }}">
                                        </a>
                                    </div>
                                    <div class="nobifashion_shop_products_content_list_item_category">
                                        <h5 class="nobifashion_shop_products_content_list_item_category_name">
                                            {{ $product->primaryCategory && $product->primaryCategory->count() > 0 ? $product->primaryCategory->name : $settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM' }}
                                        </h5>
                                    </div>
                                    <div class="nobifashion_shop_products_content_list_item_title">
                                        <a href="{{ route('client.product.detail', ['slug' => $product->slug]) }}">
                                            <h4 class="nobifashion_shop_products_content_list_item_title_name">
                                                {{ $product->name }}
                                            </h4>
                                        </a>
                                    </div>
                                    <div class="nobifashion_shop_products_content_list_item_star">
                                        <span class="nobifashion_shop_products_content_list_item_star_icon">
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
                                        <span class="nobifashion_shop_products_content_list_item_star_count">
                                            ({{ rand(5, 1000) }} review)
                                        </span>
                                    </div>
                                    <div class="nobifashion_shop_products_content_list_item_price">
                                        @if ($product->sale_price && $product->sale_price < $product->price)
                                            <span class="nobifashion_shop_products_content_list_item_price_new">
                                                {{ number_format($product->sale_price, 0, ',', '.') }}đ
                                            </span>
                                            <span class="nobifashion_shop_products_content_list_item_price_old">
                                                {{ number_format($product->price, 0, ',', '.') }}đ
                                            </span>
                                        @else
                                            <span class="nobifashion_shop_products_content_list_item_price_new">
                                                {{ number_format($product->price ?? 0, 0, ',', '.') }}đ
                                            </span>
                                        @endif
                                    </div>

                                    <div class="nobifashion_shop_products_content_list_item_addtocart">
                                        <a href="{{ route('client.product.detail', ['slug' => $product->slug]) }}"
                                            class="nobifashion_shop_products_content_list_item_addtocart_button">
                                            <button><svg focusable="false" aria-hidden="true"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 576 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
                                                    <path
                                                        d="M0 24C0 10.7 10.7 0 24 0L69.5 0c22 0 41.5 12.8 50.6 32l411 0c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3l-288.5 0 5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5L488 336c13.3 0 24 10.7 24 24s-10.7 24-24 24l-288.3 0c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5L24 48C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96zM252 160c0 11 9 20 20 20l44 0 0 44c0 11 9 20 20 20s20-9 20-20l0-44 44 0c11 0 20-9 20-20s-9-20-20-20l-44 0 0-44c0-11-9-20-20-20s-20 9-20 20l0 44-44 0c-11 0-20 9-20 20z" />
                                                </svg> Xem sản phẩm</button>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="nobifashion_shop_products_content_list_empty">
                            <p>Không có sản phẩm nào phù hợp với bộ lọc của bạn.</p>
                            <p>Hãy thử lọc sản phẩm khác hoặc thử tìm kiếm sản phẩm tương tự.</p>
                            <a href="{{ url('/shop') }}" class="nobifashion_shop_products_content_list_empty_button">
                                Xóa bộ lọc
                            </a>
                        </div>
                    @endif

                    @if (!empty($productsMain) && $productsMain->count() > 0)
                        <div class="nobifashion_shop_products_content_pagination">
                            {{ $productsMain->appends(request()->except('page', 'perPage', 'minPriceRange', 'maxPriceRange', 'colorRange', 'sizeRange'))->links('pagination.custom') }}
                        </div>
                    @endif
                </div>
            </div>
        </section>

        @include('clients.templates.chat')
    </main>

    @include('clients.templates.call')
@endsection
