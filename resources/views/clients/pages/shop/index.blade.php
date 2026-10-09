@extends('clients.layouts.master')

@section('title', renderMeta($pageTitle))

@section('head')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/shop.css') }}?v={{ file_exists(public_path('clients/assets/css/shop.css')) ? filemtime(public_path('clients/assets/css/shop.css')) : env('APP_VERSION') }}">

    <!-- 🔑 Keywords -->
    <meta name="keywords" content="{{ renderMeta($pageKeywords) }}">

    <!-- 📝 Description -->
    <meta name="description" content="{{ renderMeta($pageDescription) }}">

    <!-- 🤖 Robots -->
    @php
        // Kiểm tra xem có query string không (có dấu ? trong URL)
        $hasQueryString = !empty(request()->getQueryString());
        
        // Nếu có query string hoặc ít sản phẩm thì noindex
        $shouldNoIndex = $hasQueryString;
    @endphp
    @if ($shouldNoIndex)
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
    <script src="{{ asset('clients/assets/js/shop.js') }}?v={{ file_exists(public_path('clients/assets/js/shop.js')) ? filemtime(public_path('clients/assets/js/shop.js')) : env('APP_VERSION') }}"></script>
@endsection

@section('schema')
    @include('clients.templates.schema_shop', [
        'products' => $productsMain,
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
                                    class="nobifashion_shop_products_filter_categories_content_category {{ $category->slug === request()->segment(1) ? 'nobifashion_shop_products_filter_categories_content_category_active' : '' }}">
                                    {{-- Nếu có slug thì hiển thị link --}}
                                    <div class="nobifashion_shop_products_filter_categories_content_category_image">
                                        <a href="/{{ $category->slug }}">
                                            <img width="30px" height="30px"
                                                class="nobifashion_shop_products_filter_categories_content_category_image_img"
                                                src="{{ asset('clients/assets/img/categories/' . ($category->image ?? 'no-image.webp')) }}"
                                                alt="{{ $category->name }}"
                                                onerror="this.onerror=null; this.src='{{ asset('clients/assets/img/categories/no-image.webp') }}';">
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
                        {{-- Form lọc duy nhất đồng bộ tất cả tiêu chí --}}
                        <form id="nobifashion_shop_filter_form" action="{{ url()->current() }}" method="GET">
                            <input type="hidden" name="page" id="shop-filter-page" value="{{ request('page', 1) }}">
                            <input type="hidden" name="perPage" id="shop-filter-perPage" value="{{ $perPage ?? 30 }}">
                            <input type="hidden" name="sort" id="shop-filter-sort" value="{{ $sort ?? 'default' }}">
                            <input type="hidden" name="minPriceRange" id="minPriceRange" value="{{ $minPriceRange ?? '' }}">
                            <input type="hidden" name="maxPriceRange" id="maxPriceRange" value="{{ $maxPriceRange ?? '' }}">
                            <input type="hidden" name="colorRange" id="shop-filter-colorRange" value="{{ $colorRange ?? '' }}">
                            <input type="hidden" name="sizeRange" id="shop-filter-sizeRange" value="{{ $sizeRange ?? '' }}">

                            <!-- Bộ lọc giá -->
                            <div class="nobifashion_shop_products_filter_price">
                                <h4 class="nobifashion_shop_products_filter_price_title">Lọc theo giá</h4>
                                <div class="nobifashion_shop_products_filter_price_content">
                                    <div class="nobifashion_shop_products_filter_price_form">
                                        <label
                                            class="nobifashion_shop_products_filter_price_content_form_label {{ ((int) $minPriceRange === 0 && (int) $maxPriceRange === 500000) ? 'nobifashion_shop_products_filter_price_content_form_label_active' : '' }}"
                                            onclick="setPriceFilter(0, 500000, this)">
                                            Dưới 500.000 VNĐ
                                        </label>

                                        <label
                                            class="nobifashion_shop_products_filter_price_content_form_label {{ ((int) $minPriceRange === 500000 && (int) $maxPriceRange === 1000000) ? 'nobifashion_shop_products_filter_price_content_form_label_active' : '' }}"
                                            onclick="setPriceFilter(500000, 1000000, this)">
                                            500.000 - 1.000.000 VNĐ
                                        </label>

                                        <label
                                            class="nobifashion_shop_products_filter_price_content_form_label {{ ((int) $minPriceRange === 1000000 && (int) $maxPriceRange === 2000000) ? 'nobifashion_shop_products_filter_price_content_form_label_active' : '' }}"
                                            onclick="setPriceFilter(1000000, 2000000, this)">
                                            1.000.000 - 2.000.000 VNĐ
                                        </label>

                                        <label
                                            class="nobifashion_shop_products_filter_price_content_form_label {{ ((int) $minPriceRange === 2000000 && (empty($maxPriceRange) || (int) $maxPriceRange >= 100000000)) ? 'nobifashion_shop_products_filter_price_content_form_label_active' : '' }}"
                                            onclick="setPriceFilter(2000000, 100000000, this)">
                                            Trên 2.000.000 VNĐ
                                        </label>

                                        <label
                                            class="nobifashion_shop_products_filter_price_content_form_label {{ ($minPriceRange === null && $maxPriceRange === null) ? 'nobifashion_shop_products_filter_price_content_form_label_active' : '' }}"
                                            onclick="setPriceFilter('', '', this)">
                                            Tất cả mức giá
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Bộ lọc màu sắc -->
                            <div class="nobifashion_shop_products_filter_color">
                                <div class="nobifashion_shop_filter_header">
                                    <h4 class="nobifashion_shop_products_filter_color_title">
                                        Lọc theo màu sắc
                                    </h4>
                                    <span class="nobifashion_shop_filter_count">
                                        {{ count($availableColors ?? []) }} màu
                                    </span>
                                </div>

                                {{-- Ô tìm kiếm màu sắc nhanh tức thì (0ms) --}}
                                @if (count($availableColors ?? []) > 10)
                                    <div class="nobifashion_shop_filter_search_box">
                                        <input
                                            type="text"
                                            id="shop-filter-color-search"
                                            class="nobifashion_shop_filter_search_input"
                                            placeholder="🔍 Tìm nhanh màu sắc..."
                                        >
                                    </div>
                                @endif

                                <div class="nobifashion_shop_products_filter_color_content">
                                    <div class="nobifashion_shop_products_filter_color_form shop-filter-color-scroll">
                                        <label
                                            class="shop-filter-color-pill {{ empty($colorRange) ? 'nobifashion_shop_products_filter_color_form_label_active' : '' }}"
                                            onclick="setColorFilter('', this)">
                                            <input type="radio" name="_colorRadio"
                                                class="nobifashion_shop_products_filter_color_checkbox"
                                                value="" {{ empty($colorRange) ? 'checked' : '' }}>
                                            Tất cả màu
                                        </label>

                                        @foreach ($availableColors as $color)
                                            <label
                                                class="shop-filter-color-pill shop-color-item {{ (string) $colorRange === (string) $color ? 'nobifashion_shop_products_filter_color_form_label_active' : '' }}"
                                                data-name="{{ mb_strtolower($color) }}"
                                                onclick="setColorFilter('{{ addslashes($color) }}', this)">
                                                <input type="radio" name="_colorRadio"
                                                    class="nobifashion_shop_products_filter_color_checkbox"
                                                    value="{{ $color }}"
                                                    {{ (string) $colorRange === (string) $color ? 'checked' : '' }}>
                                                {{ $color }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- Bộ lọc size -->
                            <div class="nobifashion_shop_products_filter_size">
                                <div class="nobifashion_shop_filter_header">
                                    <h4 class="nobifashion_shop_products_filter_size_title">
                                        Lọc theo kích cỡ
                                    </h4>
                                    <span class="nobifashion_shop_filter_count">
                                        {{ count($availableSizes ?? []) }} size
                                    </span>
                                </div>
                                <div class="nobifashion_shop_products_filter_size_content">
                                    <div class="nobifashion_shop_products_filter_size_form shop-filter-size-scroll">
                                        <label
                                            class="shop-filter-size-pill {{ empty($sizeRange) ? 'nobifashion_shop_products_filter_size_form_label_active' : '' }}"
                                            onclick="setSizeFilter('', this)">
                                            <input type="radio" name="_sizeRadio"
                                                class="nobifashion_shop_products_filter_size_checkbox"
                                                value="" {{ empty($sizeRange) ? 'checked' : '' }}>
                                            Tất cả size
                                        </label>

                                        @foreach ($availableSizes as $size)
                                            <label
                                                class="shop-filter-size-pill {{ (string) $sizeRange === (string) $size ? 'nobifashion_shop_products_filter_size_form_label_active' : '' }}"
                                                onclick="setSizeFilter('{{ addslashes($size) }}', this)">
                                                <input type="radio" name="_sizeRadio"
                                                    class="nobifashion_shop_products_filter_size_checkbox"
                                                    value="{{ $size }}"
                                                    {{ (string) $sizeRange === (string) $size ? 'checked' : '' }}>
                                                Size {{ $size }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Sidebar sản phẩm mới (đã cache siêu tốc O(1)) --}}
                    @if (!empty($sidebarNewProducts) && $sidebarNewProducts->count() > 0)
                        <div class="nobifashion_shop_products_filter_new_products">
                            <h4 class="nobifashion_shop_products_filter_new_products_title">Sản phẩm mới</h4>
                            <div class="nobifashion_shop_products_filter_new_products_description">
                                <p>Khám phá những sản phẩm mới nhất tại Shop {{ $settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM' }}.</p>
                            </div>
                            @foreach ($sidebarNewProducts as $newProd)
                                <div class="nobifashion_shop_products_filter_new_products_item">
                                    <div class="nobifashion_shop_products_filter_new_products_item_image">
                                        <a href="{{ route('client.product.detail', ['slug' => $newProd->slug]) }}">
                                            <img class="nobifashion_shop_products_filter_new_products_item_image_img"
                                                src="{{ asset('clients/assets/img/clothes/' . ($newProd?->primaryImage?->url ?? 'no-image.webp')) }}"
                                                alt="{{ $newProd?->primaryImage?->alt ?? $newProd?->name }}"
                                                title="{{ $newProd?->primaryImage?->title ?? $newProd?->name }}"
                                                loading="lazy">
                                        </a>
                                    </div>
                                    <div class="nobifashion_shop_products_filter_new_products_item_info">
                                        <a href="{{ route('client.product.detail', ['slug' => $newProd->slug]) }}">
                                            <h4 class="nobifashion_shop_products_filter_new_products_item_info_title">
                                                {{ $newProd->name }}
                                            </h4>
                                        </a>
                                        <p class="nobifashion_shop_products_filter_new_products_item_info_price">
                                            {{ number_format($newProd->sale_price ?? $newProd->price, 0, ',', '.') }}đ
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="nobifashion_shop_products_content">
                    <div class="nobifashion_shop_products_content_filter">
                        <div class="nobifashion_shop_products_content_filter_total">
                            Tổng <span id="shop-total-count">{{ number_format($productsMain->total() ?? 0) }}</span> sản phẩm
                        </div>

                        <div class="nobifashion_shop_products_content_filter_delete_all {{ request()->hasAny(['minPriceRange', 'maxPriceRange', 'colorRange', 'sizeRange', 'sort']) ? '' : 'nobifashion_shop_hide' }}" id="shop-clear-filters-box">
                            <button class="nobifashion_shop_products_content_filter_delete_all_btn" type="button" onclick="resetAllShopFilters()">
                                Xóa tất cả bộ lọc
                            </button>
                        </div>

                        <div class="nobifashion_shop_products_content_filter_select">
                            <div class="nobifashion_shop_products_content_filter_select_sort">
                                <label for="shop-sort-select">Sắp xếp theo:</label>
                                <select id="shop-sort-select" onchange="setSortFilter(this.value)">
                                    <option value="default" {{ ($sort ?? 'default') === 'default' ? 'selected' : '' }}>Mặc định</option>
                                    <option value="price-asc" {{ ($sort ?? '') === 'price-asc' ? 'selected' : '' }}>Giá: Thấp đến Cao</option>
                                    <option value="price-desc" {{ ($sort ?? '') === 'price-desc' ? 'selected' : '' }}>Giá: Cao đến Thấp</option>
                                    <option value="newest" {{ ($sort ?? '') === 'newest' ? 'selected' : '' }}>Mới nhất</option>
                                </select>
                            </div>

                            <div class="nobifashion_shop_products_content_filter_select_show">
                                <label for="shop-perpage-select">Hiển thị:</label>
                                <select id="shop-perpage-select" onchange="setPerPageFilter(this.value)">
                                    @foreach ([24, 30, 36, 48, 60, 72, 84, 96] as $val)
                                        <option value="{{ $val }}" {{ (int) ($perPage ?? 30) === $val ? 'selected' : '' }}>
                                            {{ $val }} sản phẩm
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Vùng render danh sách sản phẩm động siêu tốc --}}
                    <div id="shop-products-container" class="nobifashion_shop_products_container">
                        @fragment('shop-product-list')
                            @if (!empty($productsMain) && $productsMain->count() > 0)
                                <div class="nobifashion_shop_products_content_list">
                                    @foreach ($productsMain as $product)
                                        <div class="nobifashion_shop_products_content_list_item">
                                            <div class="nobifashion_shop_products_content_list_item_label">
                                                {{ $product->label ?? '' }}
                                            </div>
                                            <div class="nobifashion_shop_products_content_list_item_image">
                                                <a href="{{ route('client.product.detail', ['slug' => $product->slug]) }}">
                                                    <img class="nobifashion_shop_products_content_list_item_image_img"
                                                        src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                                        alt="{{ $product?->primaryImage?->alt ?? $product?->name }}"
                                                        title="{{ $product?->primaryImage?->title ?? $product?->name }}"
                                                        loading="lazy"
                                                        onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}';">
                                                </a>
                                            </div>
                                            <div class="nobifashion_shop_products_content_list_item_category">
                                                <h5 class="nobifashion_shop_products_content_list_item_category_name">
                                                    {{ $product->primaryCategory ? $product->primaryCategory->name : ($settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM') }}
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
                                                        $star = 5;
                                                        for ($i = 1; $i <= $star; $i++) {
                                                            echo '<svg xmlns="http://www.w3.org/2000/svg" height="10" width="10" viewBox="0 0 640 640"><path fill="#FFD43B" d="M341.5 45.1C337.4 37.1 329.1 32 320.1 32C311.1 32 302.8 37.1 298.7 45.1L225.1 189.3L65.2 214.7C56.3 216.1 48.9 222.4 46.1 231C43.3 239.6 45.6 249 51.9 255.4L166.3 369.9L141.1 529.8C139.7 538.7 143.4 547.7 150.7 553C158 558.3 167.6 559.1 175.7 555L320.1 481.6L464.4 555C472.4 559.1 482.1 558.3 489.4 553C496.7 547.7 500.4 538.8 499 529.8L473.7 369.9L588.1 255.4C594.5 249 596.7 239.6 593.9 231C591.1 222.4 583.8 216.1 574.8 214.7L415 189.3L341.5 45.1z"/></svg>';
                                                        }
                                                    @endphp
                                                </span>
                                                <span class="nobifashion_shop_products_content_list_item_star_count">
                                                    ({{ 50 + ($product->id % 900) }} review)
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
                                                            viewBox="0 0 576 512">
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
                                    <p>Hãy thử chọn màu sắc hoặc khoảng giá khác.</p>
                                    <a href="{{ url()->current() }}" class="nobifashion_shop_products_content_list_empty_button" onclick="if(window.resetAllShopFilters){ window.resetAllShopFilters(); return false; }">
                                        Xóa bộ lọc
                                    </a>
                                </div>
                            @endif

                            @if (!empty($productsMain) && $productsMain->count() > 0)
                                <div class="nobifashion_shop_products_content_pagination">
                                    {{ $productsMain->links('pagination.custom') }}
                                </div>
                            @endif
                        @endfragment
                    </div>
                </div>
            </div>
        </section>
    </main>

    @include('clients.templates.call')
@endsection
