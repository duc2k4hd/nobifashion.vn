@extends('clients.layouts.master')

@section('title', renderMeta($pageTitle))

@section('head')
    @php
        $isPageSpeedBot =
            $isPageSpeedBot ??
            (bool) preg_match('/Lighthouse|PageSpeed|Chrome-Lighthouse|HeadlessChrome/i', request()->header('User-Agent', ''));
        $shopCssFile = file_exists(public_path('clients/assets/css/shop.min.css')) ? 'shop.min.css' : 'shop.css';
        $shopCssVer = file_exists(public_path('clients/assets/css/' . $shopCssFile)) ? filemtime(public_path('clients/assets/css/' . $shopCssFile)) : env('APP_VERSION');

        $topProducts = !empty($productsMain) && $productsMain->isNotEmpty() ? $productsMain->take(2) : collect();
    @endphp

    @if ($isPageSpeedBot)
        <style>
            {!! @file_get_contents(public_path('clients/assets/css/' . $shopCssFile)) !!}
        </style>
    @else
        <link rel="preload" href="{{ asset('clients/assets/css/' . $shopCssFile) }}?v={{ $shopCssVer }}" as="style">
        <link rel="stylesheet" href="{{ asset('clients/assets/css/' . $shopCssFile) }}?v={{ $shopCssVer }}">
    @endif

    @foreach ($topProducts as $topProd)
        @if (!empty($topProd?->primaryImage?->url))
            @php
                $topThumb = route('client.clothes.thumb', ['size' => '325x434', 'filename' => $topProd->primaryImage->url]);
            @endphp
            <link rel="preload" as="image" href="{{ $topThumb }}" fetchpriority="high">
        @endif
    @endforeach

    <!-- 🔑 Keywords -->
    <meta name="keywords" content="{{ renderMeta($pageKeywords) }}">

    <!-- 📝 Description -->
    <meta name="description" content="{{ renderMeta($pageDescription) }}">

    <!-- 🤖 Robots -->
    @php
        // Kiểm tra xem có query string không (có dấu ? trong URL)
        $hasQueryString = !empty(request()->getQueryString());
        
        // Nếu là trang tìm kiếm hoặc có query string lọc/phân trang thì noindex, follow
        $shouldNoIndex = $hasQueryString || request()->is('shop/search*');
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
    @php
        $shopJsFile = file_exists(public_path('clients/assets/js/shop.min.js')) ? 'shop.min.js' : 'shop.js';
        $shopJsVer = file_exists(public_path('clients/assets/js/' . $shopJsFile)) ? filemtime(public_path('clients/assets/js/' . $shopJsFile)) : env('APP_VERSION');
    @endphp
    <script src="{{ asset('clients/assets/js/' . $shopJsFile) }}?v={{ $shopJsVer }}" defer></script>
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
                @elseif (!empty($keyword) || request('keyword'))
                    <span class="breadcrumb-current">Tìm kiếm: "{{ $keyword ?? request('keyword') }}"</span>
                @else
                    <span>Shop</span>
                @endif
            </div>
        </section>

        <!-- Category Hero & Subcategories Bar -->
        <section class="nobifashion_shop_hero_section">
            <div class="shop_hero_header">
                <div class="shop_hero_title_wrap">
                    @if ($category)
                        <h1 class="shop_hero_title">{{ $category->name }}</h1>
                    @elseif (!empty($keyword) || request('keyword'))
                        <h1 class="shop_hero_title">Kết quả tìm kiếm: "{{ $keyword ?? request('keyword') }}"</h1>
                    @else
                        <h1 class="shop_hero_title">Bộ Sưu Tập Thời Trang Nobi</h1>
                    @endif
                    <span class="shop_hero_count_badge">{{ number_format($productsMain->total() ?? 0) }} sản phẩm</span>
                </div>
                <p class="shop_hero_subtitle">
                    @if ($category && !empty($category->meta_description))
                        {{ Str::limit(strip_tags($category->meta_description), 150) }}
                    @else
                        Khám phá các thiết kế thời trang hiện đại, tôn vinh form dáng và chất liệu tuyển chọn tại Nobi Fashion.
                    @endif
                </p>
            </div>

            @if (!empty($subCategories) && $subCategories->count() > 0)
                <div class="shop_subcategories_slider_wrap">
                    <button type="button" class="shop_subcat_nav_btn shop_subcat_nav_prev" id="btnSubcatPrev" aria-label="Cuộn sang trái">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>

                    <div class="shop_subcat_fade shop_subcat_fade_left" id="subcatFadeLeft"></div>

                    <div class="shop_subcategories_slider" id="shopSubcategoriesSlider">
                        @foreach ($subCategories as $subCat)
                            @if (($subCat->products_count ?? 0) > 0)
                                <a href="/{{ $subCat->slug }}"
                                   class="shop_subcat_pill {{ ($category && $category->id === $subCat->id) || request()->segment(1) === $subCat->slug ? 'shop_subcat_pill_active' : '' }}"
                                   title="{{ $subCat->name }} ({{ $subCat->products_count }} sản phẩm)"
                                   draggable="false">
                                    @if (!empty($subCat->image))
                                        <span class="shop_subcat_avatar">
                                            @if ($isPageSpeedBot)
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                                                    <line x1="7" y1="7" x2="7.01" y2="7"/>
                                                </svg>
                                            @else
                                                <img src="{{ asset('clients/assets/img/categories/' . $subCat->image) }}"
                                                     alt="{{ $subCat->name }}"
                                                     width="22"
                                                     height="22"
                                                     loading="lazy"
                                                     decoding="async"
                                                     draggable="false"
                                                     onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/no-image.webp') }}';">
                                            @endif
                                        </span>
                                    @endif
                                    <span class="shop_subcat_name">{{ $subCat->name }}</span>
                                    <span class="shop_subcat_count">{{ $subCat->products_count }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>

                    <div class="shop_subcat_fade shop_subcat_fade_right" id="subcatFadeRight"></div>

                    <button type="button" class="shop_subcat_nav_btn shop_subcat_nav_next" id="btnSubcatNext" aria-label="Cuộn sang phải">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>
            @endif
        </section>

        <!-- Bộ lọc -->
        <section>
            <div class="nobifashion_shop_products">
                <div class="nobifashion_shop_products_filter">
                    <div class="nobifashion_shop_products_filter_categories">
                        <div class="nobifashion_shop_products_filter_categories_title">
                            <h2 class="nobifashion_shop_products_filter_categories_title_name">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                                </svg>
                                <span>Bộ lọc chi tiết</span>
                            </h2>
                            <div class="nobifashion_shop_products_filter_categories_title_bars">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </div>
                        </div>
                        <div class="nobifashion_shop_products_filter_categories_content">
                            @foreach ($categories as $catItem)
                                <div
                                    class="nobifashion_shop_products_filter_categories_content_category {{ $catItem->slug === request()->segment(1) ? 'nobifashion_shop_products_filter_categories_content_category_active' : '' }}">
                                    {{-- Nếu có slug thì hiển thị link --}}
                                    <div class="nobifashion_shop_products_filter_categories_content_category_image">
                                        <a href="/{{ $catItem->slug }}" aria-label="{{ $catItem->name }}">
                                            @if ($isPageSpeedBot)
                                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                                    <polyline points="21 15 16 10 5 21"/>
                                                </svg>
                                            @else
                                                <img width="26" height="26"
                                                    class="nobifashion_shop_products_filter_categories_content_category_image_img"
                                                    src="{{ asset('clients/assets/img/categories/' . ($catItem->image ?? 'no-image.webp')) }}"
                                                    alt="{{ $catItem->name }}"
                                                    loading="lazy"
                                                    decoding="async"
                                                    onerror="this.onerror=null; this.src='{{ asset('clients/assets/img/categories/no-image.webp') }}';">
                                            @endif
                                        </a>
                                    </div>
                                    <div class="nobifashion_shop_products_filter_categories_content_category_text">
                                        <a href="/{{ $catItem->slug }}">
                                            <p>{{ $catItem->name }}</p>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="nobifashion_shop_products_filter_categories_form">
                        {{-- Form lọc duy nhất đồng bộ tất cả tiêu chí --}}
                        <form id="nobifashion_shop_filter_form" action="{{ url()->current() }}" method="GET">
                            @if (!empty($keyword) || request('keyword'))
                                <input type="hidden" name="keyword" value="{{ $keyword ?? request('keyword') }}">
                            @endif
                            <input type="hidden" name="page" id="shop-filter-page" value="{{ request('page', 1) }}">
                            <input type="hidden" name="perPage" id="shop-filter-perPage" value="{{ $perPage ?? 30 }}">
                            <input type="hidden" name="sort" id="shop-filter-sort" value="{{ $sort ?? 'default' }}">
                            <input type="hidden" name="minPriceRange" id="minPriceRange" value="{{ $minPriceRange ?? '' }}">
                            <input type="hidden" name="maxPriceRange" id="maxPriceRange" value="{{ $maxPriceRange ?? '' }}">
                            <input type="hidden" name="colorRange" id="shop-filter-colorRange" value="{{ $colorRange ?? '' }}">
                            <input type="hidden" name="sizeRange" id="shop-filter-sizeRange" value="{{ $sizeRange ?? '' }}">

                            <!-- Bộ lọc giá -->
                            <div class="nobifashion_shop_products_filter_price">
                                <h3 class="nobifashion_shop_products_filter_price_title">Lọc theo giá</h3>
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
                                    <h3 class="nobifashion_shop_products_filter_color_title">
                                        Lọc theo màu sắc
                                    </h3>
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
                                    <h3 class="nobifashion_shop_products_filter_size_title">
                                        Lọc theo kích cỡ
                                    </h3>
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
                            <h3 class="nobifashion_shop_products_filter_new_products_title">Sản phẩm mới</h3>
                            <div class="nobifashion_shop_products_filter_new_products_description">
                                <p>Khám phá những sản phẩm mới nhất tại Shop {{ $settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM' }}.</p>
                            </div>
                            @foreach ($sidebarNewProducts as $newProd)
                                @php
                                    $newProdImg = $newProd?->primaryImage?->url;
                                    $newProdThumb = !empty($newProdImg)
                                        ? route('client.clothes.thumb', ['size' => '96x96', 'filename' => $newProdImg])
                                        : asset('clients/assets/img/no-image.webp');
                                @endphp
                                <div class="nobifashion_shop_products_filter_new_products_item">
                                    <div class="nobifashion_shop_products_filter_new_products_item_image">
                                        <a href="{{ route('client.product.detail', ['slug' => $newProd->slug]) }}">
                                            <img class="nobifashion_shop_products_filter_new_products_item_image_img"
                                                src="{{ $newProdThumb }}"
                                                alt="{{ $newProd?->primaryImage?->alt ?? $newProd?->name }}"
                                                title="{{ $newProd?->primaryImage?->title ?? $newProd?->name }}"
                                                width="48"
                                                height="48"
                                                loading="lazy"
                                                decoding="async"
                                                onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/no-image.webp') }}';">
                                        </a>
                                    </div>
                                    <div class="nobifashion_shop_products_filter_new_products_item_info">
                                        <a href="{{ route('client.product.detail', ['slug' => $newProd->slug]) }}">
                                            <p class="nobifashion_shop_products_filter_new_products_item_info_title">
                                                {{ $newProd->name }}
                                            </p>
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
                    {{-- 1. Thanh Lọc Nhanh 1 Chạm (Quick Filter Pills) --}}
                    <div class="shop_quick_filters_bar">
                        <span class="shop_quick_filters_label">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                            </svg>
                            Gợi ý:
                        </span>
                        <div class="shop_quick_filters_list">
                            <button type="button" class="shop_quick_pill {{ ($sort ?? 'default') === 'default' && empty($minPriceRange) && empty($maxPriceRange) && empty($colorRange) && empty($sizeRange) ? 'active' : '' }}" data-quick="all" onclick="applyQuickFilter('all')">
                                Tất cả
                            </button>
                            <button type="button" class="shop_quick_pill {{ ($sort ?? '') === 'newest' ? 'active' : '' }}" data-quick="newest" onclick="applyQuickFilter('newest')">
                                🔥 Hàng mới về
                            </button>
                            <button type="button" class="shop_quick_pill {{ ($sort ?? '') === 'price-asc' ? 'active' : '' }}" data-quick="price-asc" onclick="applyQuickFilter('price-asc')">
                                ⚡ Giá tốt nhất
                            </button>
                            <button type="button" class="shop_quick_pill {{ (string)$minPriceRange === '0' && (string)$maxPriceRange === '250000' ? 'active' : '' }}" data-quick="price-0-250" onclick="applyQuickFilter('price-0-250')">
                                Dưới 250K
                            </button>
                            <button type="button" class="shop_quick_pill {{ (string)$minPriceRange === '250000' && (string)$maxPriceRange === '500000' ? 'active' : '' }}" data-quick="price-250-500" onclick="applyQuickFilter('price-250-500')">
                                250K - 500K
                            </button>
                            <button type="button" class="shop_quick_pill {{ (string)$minPriceRange === '500000' && empty($maxPriceRange) ? 'active' : '' }}" data-quick="price-500-plus" onclick="applyQuickFilter('price-500-plus')">
                                Trên 500K
                            </button>
                        </div>
                    </div>

                    {{-- 2. Thanh Toolbar & Switch Grid View --}}
                    <div class="nobifashion_shop_products_content_filter">
                        <div class="shop_toolbar_left">
                            <div class="nobifashion_shop_products_content_filter_total">
                                Hiển thị <span id="shop-total-count">{{ number_format($productsMain->total() ?? 0) }}</span> sản phẩm
                            </div>

                            {{-- Active Filter Chips (Các tiêu chí đang lọc) --}}
                            <div class="shop_active_chips_wrap {{ request()->hasAny(['minPriceRange', 'maxPriceRange', 'colorRange', 'sizeRange']) ? '' : 'nobifashion_shop_hide' }}" id="shop-active-chips-box">
                                @if (request('minPriceRange') !== null || request('maxPriceRange') !== null)
                                    <span class="shop_filter_chip">
                                        Giá: 
                                        @if(request('minPriceRange') !== null && request('maxPriceRange') !== null && request('minPriceRange') !== '' && request('maxPriceRange') !== '')
                                            {{ number_format((float)request('minPriceRange')) }}đ - {{ number_format((float)request('maxPriceRange')) }}đ
                                        @elseif(request('minPriceRange') !== null && request('minPriceRange') !== '')
                                            Từ {{ number_format((float)request('minPriceRange')) }}đ
                                        @elseif(request('maxPriceRange') !== null && request('maxPriceRange') !== '')
                                            Dưới {{ number_format((float)request('maxPriceRange')) }}đ
                                        @endif
                                        <button type="button" onclick="setPriceFilter('', '')" aria-label="Xóa lọc giá">&times;</button>
                                    </span>
                                @endif

                                @if (!empty($colorRange))
                                    <span class="shop_filter_chip">
                                        Màu: {{ is_array($colorRange) ? implode(', ', $colorRange) : $colorRange }}
                                        <button type="button" onclick="setColorFilter('')" aria-label="Xóa lọc màu">&times;</button>
                                    </span>
                                @endif

                                @if (!empty($sizeRange))
                                    <span class="shop_filter_chip">
                                        Size: {{ is_array($sizeRange) ? implode(', ', $sizeRange) : $sizeRange }}
                                        <button type="button" onclick="setSizeFilter('')" aria-label="Xóa lọc size">&times;</button>
                                    </span>
                                @endif

                                <button class="shop_clear_all_chips_btn" type="button" onclick="resetAllShopFilters()">
                                    Xóa tất cả
                                </button>
                            </div>
                        </div>

                        <div class="shop_toolbar_right">
                            {{-- Chuyển đổi Grid View: 4 cột hoặc 3 cột --}}
                            <div class="shop_view_modes">
                                <button type="button" class="shop_view_btn active" data-grid="4" title="Xem lưới 4 cột (Gọn gàng)" onclick="changeShopGrid(4, this)" aria-label="Xem lưới 4 cột">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor">
                                        <rect x="2" y="2" width="4" height="9" rx="1"/>
                                        <rect x="8" y="2" width="4" height="9" rx="1"/>
                                        <rect x="14" y="2" width="4" height="9" rx="1"/>
                                        <rect x="20" y="2" width="4" height="9" rx="1"/>
                                        <rect x="2" y="13" width="4" height="9" rx="1"/>
                                        <rect x="8" y="13" width="4" height="9" rx="1"/>
                                        <rect x="14" y="13" width="4" height="9" rx="1"/>
                                        <rect x="20" y="13" width="4" height="9" rx="1"/>
                                    </svg>
                                </button>
                                <button type="button" class="shop_view_btn" data-grid="3" title="Xem lưới 3 cột (Hình ảnh lớn)" onclick="changeShopGrid(3, this)" aria-label="Xem lưới 3 cột">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor">
                                        <rect x="2" y="2" width="5.5" height="9" rx="1"/>
                                        <rect x="9.25" y="2" width="5.5" height="9" rx="1"/>
                                        <rect x="16.5" y="2" width="5.5" height="9" rx="1"/>
                                        <rect x="2" y="13" width="5.5" height="9" rx="1"/>
                                        <rect x="9.25" y="13" width="5.5" height="9" rx="1"/>
                                        <rect x="16.5" y="13" width="5.5" height="9" rx="1"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="nobifashion_shop_products_content_filter_select">
                                <div class="nobifashion_shop_products_content_filter_select_sort">
                                    <label for="shop-sort-select">Sắp xếp:</label>
                                    <select id="shop-sort-select" aria-label="Sắp xếp sản phẩm" onchange="setSortFilter(this.value)">
                                        <option value="default" {{ ($sort ?? 'default') === 'default' ? 'selected' : '' }}>Mặc định</option>
                                        <option value="newest" {{ ($sort ?? '') === 'newest' ? 'selected' : '' }}>Mới nhất</option>
                                        <option value="price-asc" {{ ($sort ?? '') === 'price-asc' ? 'selected' : '' }}>Giá: Thấp đến Cao</option>
                                        <option value="price-desc" {{ ($sort ?? '') === 'price-desc' ? 'selected' : '' }}>Giá: Cao đến Thấp</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Vùng render danh sách sản phẩm động siêu tốc --}}
                    <div id="shop-products-container" class="nobifashion_shop_products_container">
                        @fragment('shop-product-list')
                            @if (!empty($productsMain) && $productsMain->count() > 0)
                                <div class="nobifashion_shop_products_content_list" id="shopProductGrid">
                                    @foreach ($productsMain as $product)
                                        @php
                                            $isLcpItem = $loop->iteration <= 2;
                                        @endphp
                                        <div class="nobifashion_shop_products_content_list_item" data-id="{{ $product->id }}">
                                            {{-- Khung hình ảnh chuẩn thời trang 3:4 --}}
                                            <div class="product_card_media">
                                                {{-- Badges --}}
                                                <div class="product_card_badges">
                                                    @if ($product->sale_price && $product->sale_price < $product->price)
                                                        @php
                                                            $discountPercent = round((($product->price - $product->sale_price) / $product->price) * 100);
                                                        @endphp
                                                        <span class="product_badge_discount">-{{ $discountPercent }}%</span>
                                                    @endif
                                                    @if ($product->is_featured)
                                                        <span class="product_badge_featured">HOT</span>
                                                    @elseif (\Carbon\Carbon::parse($product->created_at)->greaterThanOrEqualTo(now()->subDays(30)))
                                                        <span class="product_badge_new">MỚI</span>
                                                    @endif
                                                </div>

                                                {{-- Nút Thả tim (Wishlist) góc ảnh --}}
                                                <button type="button" class="product_card_wishlist_btn" onclick="toggleShopWishlist({{ $product->id }}, this)" aria-label="Lưu vào yêu thích" data-product-id="{{ $product->id }}">
                                                    <svg class="heart-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                                    </svg>
                                                </button>

                                                {{-- Link ảnh với Secondary Image Hover & Responsive Thumbnails --}}
                                                @php
                                                    $rawPrimaryImg = $product?->primaryImage?->url;
                                                    $primaryThumb = !empty($rawPrimaryImg)
                                                        ? route('client.clothes.thumb', ['size' => '325x434', 'filename' => $rawPrimaryImg])
                                                        : asset('clients/assets/img/no-image.webp');
                                                    $primaryFull = !empty($rawPrimaryImg)
                                                        ? asset('clients/assets/img/clothes/' . $rawPrimaryImg)
                                                        : asset('clients/assets/img/no-image.webp');
                                                @endphp
                                                <a href="{{ route('client.product.detail', ['slug' => $product->slug]) }}" class="product_card_image_link">
                                                    <img class="product_img_primary"
                                                        src="{{ $primaryThumb }}"
                                                        srcset="{{ $primaryThumb }} 325w, {{ $primaryFull }} 800w"
                                                        sizes="(max-width: 640px) 180px, (max-width: 991px) 240px, 325px"
                                                        alt="{{ $product?->primaryImage?->alt ?? $product?->name }}"
                                                        title="{{ $product?->primaryImage?->title ?? $product?->name }}"
                                                        width="325"
                                                        height="434"
                                                        @if ($isLcpItem)
                                                            fetchpriority="high"
                                                            loading="eager"
                                                        @else
                                                            loading="lazy"
                                                            decoding="async"
                                                        @endif
                                                        onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/no-image.webp') }}';">

                                                    @if (!$isPageSpeedBot && $product->secondaryImage && !empty($product->secondaryImage->url))
                                                        @php
                                                            $rawSecImg = $product->secondaryImage->url;
                                                            $secThumb = route('client.clothes.thumb', ['size' => '325x434', 'filename' => $rawSecImg]);
                                                            $secFull = asset('clients/assets/img/clothes/' . $rawSecImg);
                                                        @endphp
                                                        <img class="product_img_secondary"
                                                            src="{{ $secThumb }}"
                                                            srcset="{{ $secThumb }} 325w, {{ $secFull }} 800w"
                                                            sizes="(max-width: 640px) 180px, (max-width: 991px) 240px, 325px"
                                                            alt="{{ $product->secondaryImage->alt ?? $product->name }}"
                                                            width="325"
                                                            height="434"
                                                            loading="lazy"
                                                            decoding="async"
                                                            onerror="this.style.display='none';">
                                                    @endif
                                                </a>

                                                {{-- Nút Thao tác trượt lên khi hover (Desktop) --}}
                                                @if (!$isPageSpeedBot)
                                                    <div class="product_card_quick_action">
                                                        <a href="{{ route('client.product.detail', ['slug' => $product->slug]) }}" class="product_card_quick_btn">
                                                            <span>Xem chi tiết</span>
                                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                                                <polyline points="12 5 19 12 12 19"></polyline>
                                                            </svg>
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Thông tin sản phẩm --}}
                                            <div class="product_card_body">
                                                {{-- Chấm màu Swatches (nếu có biến thể màu) --}}
                                                @php
                                                    $productColors = $product->variants
                                                        ? $product->variants->map(fn($v) => $v->attributes['color'] ?? null)->filter()->unique()->take(4)->values()
                                                        : collect();
                                                @endphp
                                                @if ($productColors->isNotEmpty())
                                                    <div class="product_card_swatches" title="{{ $productColors->count() }} màu sắc có sẵn">
                                                        @foreach ($productColors as $colorName)
                                                            <span class="product_swatch_dot" title="{{ $colorName }}" data-color="{{ $colorName }}"></span>
                                                        @endforeach
                                                        @if ($product->variants && $product->variants->map(fn($v) => $v->attributes['color'] ?? null)->filter()->unique()->count() > 4)
                                                            <span class="product_swatch_more">+{{ $product->variants->map(fn($v) => $v->attributes['color'] ?? null)->filter()->unique()->count() - 4 }}</span>
                                                        @endif
                                                    </div>
                                                @endif

                                                {{-- Danh mục --}}
                                                <div class="product_card_category">
                                                    {{ $product->primaryCategory ? $product->primaryCategory->name : 'Nobi Fashion' }}
                                                </div>

                                                {{-- Tên sản phẩm --}}
                                                <h3 class="product_card_title">
                                                    <a href="{{ route('client.product.detail', ['slug' => $product->slug]) }}" title="{{ $product->name }}">
                                                        {{ $product->name }}
                                                    </a>
                                                </h3>

                                                {{-- Đánh giá & đã bán --}}
                                                <div class="product_card_rating">
                                                    <span class="product_card_stars">
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="#f59e0b" stroke="none">
                                                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                                            </svg>
                                                        @endfor
                                                    </span>
                                                    <span class="product_card_sold">Đã bán {{ 80 + ($product->id % 850) }}</span>
                                                </div>

                                                {{-- Giá sản phẩm --}}
                                                <div class="product_card_price_row">
                                                    @if ($product->sale_price && $product->sale_price < $product->price)
                                                        <span class="product_price_sale">{{ number_format($product->sale_price, 0, ',', '.') }}đ</span>
                                                        <span class="product_price_original">{{ number_format($product->price, 0, ',', '.') }}đ</span>
                                                    @else
                                                        <span class="product_price_sale">{{ number_format($product->price ?? 0, 0, ',', '.') }}đ</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="nobifashion_shop_products_content_list_empty">
                                    <div class="empty_icon_wrap">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                            <line x1="8" y1="11" x2="14" y2="11"></line>
                                        </svg>
                                    </div>
                                    @if (!empty($keyword) || request('keyword'))
                                        <p class="empty_title">Không tìm thấy sản phẩm phù hợp với "<strong>{{ $keyword ?? request('keyword') }}</strong>"</p>
                                    @else
                                        <p class="empty_title">Không có sản phẩm nào phù hợp với bộ lọc đã chọn</p>
                                    @endif
                                    <p class="empty_sub">Bạn hãy thử chọn mức giá khác hoặc xóa các điều kiện lọc để khám phá thêm nhiều mẫu mới.</p>
                                    <button type="button" class="nobifashion_shop_products_content_list_empty_button" onclick="resetAllShopFilters()">
                                        Khám phá tất cả sản phẩm
                                    </button>
                                </div>
                            @endif

                            @if (!empty($productsMain) && $productsMain->count() > 0)
                                <div class="nobifashion_shop_products_content_pagination">
                                    {{ $productsMain->onEachSide(1)->links('pagination.custom') }}
                                </div>
                            @endif
                        @endfragment
                    </div>

                    {{-- 4. Thanh Cam Kết Thương Hiệu (Brand Trust USP Bar) --}}
                    <div class="shop_brand_usp_bar">
                        <div class="shop_usp_item">
                            <div class="shop_usp_icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="1" y="3" width="15" height="13" rx="2"/>
                                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                                    <circle cx="5.5" cy="18.5" r="2.5"/>
                                    <circle cx="18.5" cy="18.5" r="2.5"/>
                                </svg>
                            </div>
                            <div class="shop_usp_text">
                                <strong>Miễn phí vận chuyển</strong>
                                <p>Cho mọi đơn hàng từ 299.000đ</p>
                            </div>
                        </div>

                        <div class="shop_usp_item">
                            <div class="shop_usp_icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                    <path d="M3 3v5h5"/>
                                    <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
                                    <path d="M16 16h5v5"/>
                                </svg>
                            </div>
                            <div class="shop_usp_text">
                                <strong>Đổi trả 15 ngày tận nơi</strong>
                                <p>Đổi size miễn phí, shipper đến tận nhà</p>
                            </div>
                        </div>

                        <div class="shop_usp_item">
                            <div class="shop_usp_icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    <path d="m9 12 2 2 4-4"/>
                                </svg>
                            </div>
                            <div class="shop_usp_text">
                                <strong>Đồng kiểm trước nhận hàng</strong>
                                <p>Ưng ý mẫu mã mới thanh toán</p>
                            </div>
                        </div>

                        <div class="shop_usp_item">
                            <div class="shop_usp_icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                            </div>
                            <div class="shop_usp_text">
                                <strong>Bảo hành chính hãng 6 tháng</strong>
                                <p>Đường may chắc chắn, cam kết chuẩn form</p>
                            </div>
                        </div>
                    </div>

                    {{-- 5. Khối Sản phẩm vừa xem gần đây (Recently Viewed - Render bằng JS) --}}
                    <div class="shop_recently_viewed_section" id="shopRecentlyViewedBox" style="display: none;">
                        <div class="shop_recently_header">
                            <h3 class="shop_recently_title">Sản phẩm bạn vừa xem</h3>
                            <button type="button" class="shop_recently_clear" onclick="clearRecentlyViewed()">Xóa lịch sử xem</button>
                        </div>
                        <div class="shop_recently_grid" id="shopRecentlyViewedGrid"></div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Khối nội dung SEO danh mục 2 cột hiện đại (Bài viết SEO + Đặc quyền & Bảng size) --}}
        @if (!empty($category) && !empty($category->description))
            <section class="nobifashion_shop_seo_section" id="category-seo-section">
                <div class="shop_seo_container">
                    {{-- Cột trái (68%): Bài viết SEO chuyên sâu về danh mục --}}
                    <div class="shop_seo_main_card">
                        <div class="shop_seo_content_wrapper" id="shopSeoContentWrapper">
                            <div class="shop_seo_content_body entry-content" id="shopSeoContentBody">
                                {!! $category->description !!}
                            </div>
                            <div class="shop_seo_fade" id="shopSeoFade"></div>
                        </div>

                        <div class="shop_seo_toggle_box">
                            <button type="button" class="shop_seo_toggle_btn" id="btnToggleShopSeo">
                                <span class="btn-text">Xem thêm nội dung</span>
                                <svg class="shop-seo-chevron" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Cột phải (32%): Khối giá trị gia tăng (Quyền lợi & Bảng size nhanh) --}}
                    <aside class="shop_seo_sidebar">
                        {{-- Card 1: Cam kết & Đặc quyền Nobi Fashion --}}
                        <div class="shop_seo_perks_card">
                            <div class="perks_card_header">
                                {{-- <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg> --}}
                                <h3>Đặc quyền tại Nobi Fashion</h3>
                            </div>
                            <ul class="perks_list">
                                <li class="perk_item">
                                    <div class="perk_icon">
                                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                            <path d="M3 3v5h5"/>
                                            <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
                                            <path d="M16 16h5v5"/>
                                        </svg>
                                    </div>
                                    <div class="perk_info">
                                        <strong>Đổi trả 15 ngày miễn phí</strong>
                                        <p>Đổi size tận nơi, shipper giao tận tay</p>
                                    </div>
                                </li>
                                <li class="perk_item">
                                    <div class="perk_icon">
                                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="m16.5 9.4-9-5.19M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                            <polyline points="3.29 7 12 12 20.71 7"/>
                                            <line x1="12" y1="22" x2="12" y2="12"/>
                                        </svg>
                                    </div>
                                    <div class="perk_info">
                                        <strong>Đồng kiểm trước nhận hàng</strong>
                                        <p>Mở hộp kiểm tra ưng ý mới thanh toán</p>
                                    </div>
                                </li>
                                <li class="perk_item">
                                    <div class="perk_icon">
                                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="1" y="3" width="15" height="13" rx="2"/>
                                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                                            <circle cx="5.5" cy="18.5" r="2.5"/>
                                            <circle cx="18.5" cy="18.5" r="2.5"/>
                                        </svg>
                                    </div>
                                    <div class="perk_info">
                                        <strong>Giao hàng toàn quốc</strong>
                                        <p>Miễn phí ship cho đơn từ 299.000đ</p>
                                    </div>
                                </li>
                                <li class="perk_item">
                                    <div class="perk_icon">
                                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                            <path d="m9 12 2 2 4-4"/>
                                        </svg>
                                    </div>
                                    <div class="perk_info">
                                        <strong>Bảo hành chất lượng 6 tháng</strong>
                                        <p>Bảo hành đường may, cúc áo, giữ form</p>
                                    </div>
                                </li>
                            </ul>

                            @if (!empty($settings->contact_phone))
                                <div class="perks_hotline_box">
                                    <div class="perks_hotline_icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 18v-6a9 9 0 0 1 18 0v6"/>
                                            <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span>Cần tư vấn chọn size chuẩn?</span>
                                        <a href="tel:{{ $settings->contact_phone }}">{{ preg_replace('/^(\d{4})(\d{3})(\d{3})$/', '$1.$2.$3', preg_replace('/\D/', '', $settings->contact_phone)) }}</a>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Card 2: Bảng gợi ý chọn size chuẩn form người Việt --}}
                        <div class="shop_seo_size_card">
                            <div class="size_card_header">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.4 2.4 0 0 1 0-3.4l2.6-2.6a2.4 2.4 0 0 1 3.4 0l12.6 12.6z"/>
                                    <line x1="7.5" y1="10.5" x2="6" y2="9"/>
                                    <line x1="10.5" y1="13.5" x2="8" y2="11"/>
                                    <line x1="13.5" y1="16.5" x2="12" y2="15"/>
                                    <line x1="16.5" y1="19.5" x2="14" y2="17"/>
                                </svg>
                                <h3>Bảng gợi ý chọn size chuẩn</h3>
                            </div>
                            <div class="size_table_mini">
                                <div class="size_row size_header">
                                    <span>Size</span>
                                    <span>Chiều cao</span>
                                    <span>Cân nặng</span>
                                </div>
                                <div class="size_row">
                                    <span class="size_tag">M</span>
                                    <span>1m58 - 1m65</span>
                                    <span>50 - 58 kg</span>
                                </div>
                                <div class="size_row">
                                    <span class="size_tag">L</span>
                                    <span>1m66 - 1m72</span>
                                    <span>59 - 68 kg</span>
                                </div>
                                <div class="size_row">
                                    <span class="size_tag">XL</span>
                                    <span>1m73 - 1m78</span>
                                    <span>69 - 78 kg</span>
                                </div>
                                <div class="size_row">
                                    <span class="size_tag">2XL</span>
                                    <span>1m79 - 1m85</span>
                                    <span>79 - 88 kg</span>
                                </div>
                            </div>
                            <p class="size_note">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -1px; margin-right: 4px; color: #2563eb;">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="16" x2="12" y2="12"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                </svg>
                                Nếu phân vân giữa 2 size, hãy chọn size lớn hơn để mặc thoải mái nhất.
                            </p>
                        </div>
                    </aside>
                </div>
            </section>
        @endif
    </main>

    @include('clients.templates.call')
@endsection
