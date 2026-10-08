@extends('clients.layouts.master')

@php
    $brandName = $brand->name ?? 'NOBIFASHION';
    $brandSlug = $currentSlug ?? ($brand->slug ?? 'nobifashion');
    $brandCanonicalUrl = route('client.brand.show', $brandSlug);
    $siteName = $settings->site_name ?? ($settings->subname ?? 'NOBI FASHION');

    $brandImageUrl = !empty($brand->logo) && file_exists(public_path('clients/assets/img/brands/' . $brand->logo))
        ? asset('clients/assets/img/brands/' . $brand->logo)
        : asset('clients/assets/img/banners/' . ($settings->site_banner ?? 'default-banner.jpg'));

    $pageTitle = ($brandName) . ' – Gian Hàng Thương Hiệu Chính Hãng | ' . $siteName;
    $pageDescription = Str::limit($brand->description ?? ('Khám phá gian hàng thương hiệu ' . $brandName . ' chính hãng tại ' . $siteName . ': thời trang cao cấp, uy tín, chính hãng, giá tốt và giao hàng toàn quốc.'), 160);
    $pageKeywords = 'thương hiệu ' . strtolower($brandName) . ', gian hàng ' . strtolower($brandName) . ', ' . strtolower($brandName) . ' chính hãng, thời trang ' . strtolower($brandName) . ', mua sắm online, nobi fashion';
@endphp

@section('title', renderMeta($pageTitle))

@section('head')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/brand.css') }}?v={{ env('APP_VERSION', '1.0') }}">

    <!-- 🔑 SEO Meta Tags -->
    <meta name="description" content="{{ renderMeta($pageDescription) }}">
    <meta name="keywords" content="{{ renderMeta($pageKeywords) }}">
    <meta name="author" content="{{ $settings->seo_author ?? $siteName }}">
    <meta name="robots" content="index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta http-equiv="date" content="{{ now()->format('d/m/Y') }}" />

    <!-- 🔗 Canonical & Hreflang -->
    <link rel="canonical" href="{{ $brandCanonicalUrl }}">
    <link rel="alternate" hreflang="vi" href="{{ $brandCanonicalUrl }}">
    <link rel="alternate" hreflang="x-default" href="{{ $brandCanonicalUrl }}">

    <!-- 🌐 Open Graph (Facebook, Zalo) -->
    <meta property="og:locale" content="vi_VN">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ renderMeta($brandName . ' – Gian Hàng Thương Hiệu Chính Hãng') }}">
    <meta property="og:description" content="{{ renderMeta($pageDescription) }}">
    <meta property="og:url" content="{{ $brandCanonicalUrl }}">
    <meta property="og:site_name" content="{{ renderMeta($siteName) }}">
    <meta property="og:image" content="{{ $brandImageUrl }}">
    <meta property="og:image:secure_url" content="{{ $brandImageUrl }}">
    <meta property="og:image:width" content="600">
    <meta property="og:image:height" content="600">
    <meta property="og:image:alt" content="{{ renderMeta($brandName) }}">

    <!-- 🐦 Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ renderMeta($brandName . ' – Gian Hàng Thương Hiệu Chính Hãng') }}">
    <meta name="twitter:description" content="{{ renderMeta($pageDescription) }}">
    <meta name="twitter:image" content="{{ $brandImageUrl }}">
    <meta name="twitter:creator" content="{{ renderMeta($siteName) }}">
@endsection

@section('schema')
    <!-- 🌐 SCHEMA CHUẨN GIAN HÀNG THƯƠNG HIỆU (SCHEMA.ORG) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": "Brand",
                "@id": "{{ $brandCanonicalUrl }}#brand",
                "name": "{{ $brandName }}",
                "url": "{{ $brandCanonicalUrl }}",
                "logo": "{{ $brandImageUrl }}",
                "description": "{{ renderMeta($pageDescription) }}",
                "slogan": "{{ $brand->campaign ?? '#SUMMER 2026' }}",
                @if(!empty($brand->rating_score) && (float)$brand->rating_score > 0)
                "aggregateRating": {
                    "@type": "AggregateRating",
                    "ratingValue": "{{ number_format((float)$brand->rating_score, 1) }}",
                    "bestRating": "5",
                    "worstRating": "1",
                    "ratingCount": "{{ max(1, (int)($brand->followers_count ?? 150)) }}"
                },
                @endif
                "sameAs": [
                    "{{ url('/') }}"
                ]
            },
            {
                "@type": "CollectionPage",
                "@id": "{{ $brandCanonicalUrl }}#webpage",
                "url": "{{ $brandCanonicalUrl }}",
                "name": "{{ renderMeta($pageTitle) }}",
                "description": "{{ renderMeta($pageDescription) }}",
                "inLanguage": "vi",
                "isPartOf": {
                    "@type": "WebSite",
                    "@id": "{{ url('/') }}#website",
                    "name": "{{ renderMeta($siteName) }}",
                    "url": "{{ url('/') }}"
                },
                "about": {
                    "@id": "{{ $brandCanonicalUrl }}#brand"
                },
                "breadcrumb": {
                    "@id": "{{ $brandCanonicalUrl }}#breadcrumb"
                },
                "primaryImageOfPage": {
                    "@type": "ImageObject",
                    "url": "{{ $brandImageUrl }}"
                }
            },
            {
                "@type": "BreadcrumbList",
                "@id": "{{ $brandCanonicalUrl }}#breadcrumb",
                "itemListElement": [
                    {
                        "@type": "ListItem",
                        "position": 1,
                        "name": "Trang chủ",
                        "item": "{{ url('/') }}"
                    },
                    {
                        "@type": "ListItem",
                        "position": 2,
                        "name": "Thương hiệu",
                        "item": "{{ route('client.brand.list') }}"
                    },
                    {
                        "@type": "ListItem",
                        "position": 3,
                        "name": "{{ $brandName }}",
                        "item": "{{ $brandCanonicalUrl }}"
                    }
                ]
            }
            @if(isset($bestsellerProducts) && $bestsellerProducts->isNotEmpty())
            ,{
                "@type": "ItemList",
                "@id": "{{ $brandCanonicalUrl }}#itemlist",
                "name": "Sản phẩm nổi bật của thương hiệu {{ $brandName }}",
                "numberOfItems": {{ $bestsellerProducts->count() }},
                "itemListElement": [
                    @foreach($bestsellerProducts as $idx => $prod)
                    {
                        "@type": "ListItem",
                        "position": {{ $loop->iteration }},
                        "name": "{{ $prod->name }}",
                        "url": "{{ route('client.product.detail', $prod->slug) }}"
                    }@if(!$loop->last),@endif
                    @endforeach
                ]
            }
            @endif
            @if(!empty($brand->faqs) && is_array($brand->faqs))
            ,{
                "@type": "FAQPage",
                "@id": "{{ $brandCanonicalUrl }}#faq",
                "mainEntity": [
                    @foreach($brand->faqs as $fIndex => $faq)
                    {
                        "@type": "Question",
                        "name": "{{ addslashes($faq['question'] ?? '') }}",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "{{ addslashes($faq['answer'] ?? '') }}"
                        }
                    }@if(!$loop->last),@endif
                    @endforeach
                ]
            }
            @endif
        ]
    }
    </script>
    <!-- 🌐 END SCHEMA CHUẨN GIAN HÀNG THƯƠNG HIỆU -->
@endsection

@section('content')
    <div class="nobifashion_brand_page">
        <!-- Hero: Thông tin thương hiệu & Slider banner -->
        <header class="nobifashion_brand_hero">
            <div class="nobifashion_brand_hero_inner">
                <section class="nobifashion_brand_profile" aria-label="Thông tin thương hiệu">
                    <div class="nobifashion_brand_profile_top">
                        <div class="nobifashion_brand_logo">
                            @if(!empty($brand->logo) && file_exists(public_path('clients/assets/img/brands/' . $brand->logo)))
                                <img src="{{ asset('clients/assets/img/brands/' . $brand->logo) }}" alt="{{ $brand->name }}" style="width: 100%; height: 100%; object-fit: contain; border-radius: 50%;">
                            @else
                                <span>{{ strtoupper(substr($brand->name ?? 'NOBI', 0, 4)) }}</span>
                            @endif
                        </div>
                        <div class="nobifashion_brand_identity">
                            <p class="nobifashion_brand_campaign">{{ $brand->campaign ?? '#SUMMER 2026' }}</p>
                            <h1>{{ $brand->name ?? 'NOBIFASHION' }} <span aria-label="Đã xác minh" title="Đã xác minh chính hãng">●</span></h1>
                            <p>{{ number_format($brand->followers_count ?? 2000, 0, ',', '.') }} người theo dõi</p>
                        </div>
                    </div>

                    <div class="nobifashion_brand_stats">
                        <div><b id="nobifashion_brand_product_count">{{ number_format($totalProductsCount ?? 0, 0, ',', '.') }}</b><span>Sản phẩm</span></div>
                        <div><b>{{ number_format($brand->rating_score ?? 4.9, 1) }}/5</b><span>Đánh giá</span></div>
                        <div><b>{{ $brand->joined_years ?? 9 }} năm</b><span>Tham gia</span></div>
                    </div>

                    <div class="nobifashion_brand_intro_wrapper">
                        <p class="nobifashion_brand_intro" id="nobifashion_brand_intro">
                            {{ $brand->description ?? 'Được hình thành trong thời đại 4.0, NOBIFASHION áp dụng sức mạnh của công nghệ vào thời trang để đưa ra giải pháp mua sắm đồ cơ bản cho nam giới với mô hình tiện lợi hơn, tiết kiệm hơn — khách hàng có thể mua cả tủ đồ đảm bảo chất lượng, made in Vietnam, giá tốt, giao hàng nhanh chóng.' }}
                        </p>
                        <button type="button" class="nobifashion_brand_more" id="nobifashion_brand_more">
                            <span>Xem thêm</span> <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    </div>

                    <div class="nobifashion_brand_actions">
                        <button type="button" class="nobifashion_brand_follow" id="nobifashion_brand_follow">
                            <span>♙</span> Theo dõi
                        </button>
                    </div>
                </section>

                <section class="nobifashion_brand_slider" aria-label="Banner thương hiệu">
                    @php
                        $slides = $brand->banner_slides;
                        if (empty($slides) || !is_array($slides)) {
                            $slides = [
                                ['tag' => "#SUMMER 2026\nLOADING.", 'title' => 'Một bầu trời', 'highlight' => $brand->name ?? 'NOBIFASHION', 'image' => null],
                                ['tag' => "EVERYDAY\nESSENTIALS.", 'title' => 'Đơn giản để', 'highlight' => 'thoải mái hơn', 'image' => null],
                                ['tag' => "MADE IN\nVIETNAM.", 'title' => 'Chất lượng cho', 'highlight' => 'mỗi ngày', 'image' => null],
                            ];
                        }
                    @endphp

                    @foreach($slides as $index => $slide)
                        @php
                            $slideImg = !empty($slide['image']) && file_exists(public_path('clients/assets/img/brands/' . $slide['image']))
                                ? asset('clients/assets/img/brands/' . $slide['image'])
                                : null;
                        @endphp
                        <div class="nobifashion_brand_slide {{ $index === 0 ? 'nobifashion_brand_slide_active' : '' }}"
                             @if($slideImg) style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.85) 0%, rgba(15, 23, 42, 0.45) 50%, rgba(15, 23, 42, 0.85) 100%), url('{{ $slideImg }}') center/cover no-repeat;" @endif>
                            <p>{!! nl2br(e($slide['tag'] ?? '')) !!}</p>
                            <div>{{ $slide['title'] ?? '' }}<br><strong>{{ $slide['highlight'] ?? '' }}</strong></div>
                        </div>
                    @endforeach
                    <div class="nobifashion_brand_dots" id="nobifashion_brand_dots"></div>
                </section>
            </div>
        </header>

        <!-- Main Content -->
        <main>
            <!-- 1. Sản phẩm bán chạy -->
            <section class="nobifashion_brand_section nobifashion_brand_section_products">
                <div class="nobifashion_brand_heading">
                    <h2>Sản phẩm bán chạy</h2>
                </div>
                <div class="nobifashion_brand_product_grid" id="nobifashion_brand_bestsellers">
                    @forelse($bestsellerProducts as $product)
                        @php
                            $hasSale = $product->sale_price && (float) $product->sale_price > 0 && (float) $product->sale_price < (float) $product->price;
                            $curPrice = $hasSale ? $product->sale_price : $product->price;
                            $imgUrl = $product->primaryImage?->url
                                ? asset('clients/assets/img/clothes/' . $product->primaryImage->url)
                                : asset('clients/assets/img/clothes/no-image.webp');
                        @endphp
                        <article class="nobifashion_brand_product">
                            <a href="{{ route('client.product.detail', $product->slug) }}" class="nobifashion_brand_product_visual" style="aspect-ratio: 1/1.05; display: block;">
                                <img src="{{ $imgUrl }}" alt="{{ $product->name }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                            </a>
                            <span class="nobifashion_brand_badge">{{ $brand->name ?? 'NOBIFASHION' }}</span>
                            <button type="button" class="nobifashion_brand_favorite" data-id="{{ $product->id }}" aria-label="Thêm vào yêu thích">♡</button>
                            <div class="nobifashion_brand_product_body">
                                <p class="nobifashion_brand_product_brand">{{ $brand->name ?? 'NOBIFASHION' }}</p>
                                <a href="{{ route('client.product.detail', $product->slug) }}" class="nobifashion_brand_product_title" title="{{ $product->name }}">{{ $product->name }}</a>
                                <span class="nobifashion_brand_product_price">{{ number_format($curPrice, 0, ',', '.') }} ₫</span>
                                @if($hasSale)
                                    <span class="nobifashion_brand_product_old">{{ number_format($product->price, 0, ',', '.') }} ₫</span>
                                @endif
                                <div class="nobifashion_brand_product_meta">
                                    <span class="nobifashion_brand_stars">★★★★★</span>
                                    <span class="nobifashion_brand_sold">Còn {{ $product->stock_quantity }} sp</span>
                                </div>
                            </div>
                        </article>
                    @empty
                        <p style="grid-column: 1 / -1; text-align: center; color: #64748b; padding: 20px 0;">Đang cập nhật sản phẩm nổi bật của thương hiệu...</p>
                    @endforelse
                </div>
            </section>

            <!-- 2. Danh mục sản phẩm -->
            @if(isset($brandCategories) && $brandCategories->isNotEmpty())
                <section class="nobifashion_brand_section nobifashion_brand_category_section">
                    <div class="nobifashion_brand_heading">
                        <h2>Danh mục sản phẩm</h2>
                    </div>
                    <div class="nobifashion_brand_category_grid" id="nobifashion_brand_categories">
                        <button class="nobifashion_brand_category nobifashion_brand_category_active" data-category-id="" type="button">
                            <span class="nobifashion_brand_category_thumb nobifashion_brand_category_thumb_all">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                                    <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                                    <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
                                    <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                                </svg>
                            </span>
                            <span class="nobifashion_brand_category_info">
                                <b>Tất cả sản phẩm</b>
                                <small>{{ number_format($totalProductsCount) }} sản phẩm</small>
                            </span>
                            <span class="nobifashion_brand_category_arrow">›</span>
                        </button>
                        @foreach($brandCategories as $category)
                            <button class="nobifashion_brand_category" data-category-id="{{ $category->id }}" type="button">
                                <span class="nobifashion_brand_category_thumb">
                                    <img src="{{ $category->display_image }}" alt="{{ $category->name }}" loading="lazy" onerror="this.src='{{ asset('clients/assets/img/categories/no-image.webp') }}'">
                                </span>
                                <span class="nobifashion_brand_category_info">
                                    <b>{{ $category->name }}</b>
                                    <small>{{ number_format($category->products_count) }} sản phẩm</small>
                                </span>
                                <span class="nobifashion_brand_category_arrow">›</span>
                            </button>
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- 3. Gợi ý cho bạn (Danh sách sản phẩm động) -->
            <section class="nobifashion_brand_section nobifashion_brand_recommend_section">
                <div class="nobifashion_brand_heading">
                    <h2 id="nobifashion_brand_recommend_title">Gợi ý cho bạn</h2>
                </div>
                <div class="nobifashion_brand_product_grid" id="nobifashion_brand_recommend">
                    @forelse($recommendProducts as $product)
                        @php
                            $hasSale = $product->sale_price && (float) $product->sale_price > 0 && (float) $product->sale_price < (float) $product->price;
                            $curPrice = $hasSale ? $product->sale_price : $product->price;
                            $imgUrl = $product->primaryImage?->url
                                ? asset('clients/assets/img/clothes/' . $product->primaryImage->url)
                                : asset('clients/assets/img/clothes/no-image.webp');
                        @endphp
                        <article class="nobifashion_brand_product">
                            <a href="{{ route('client.product.detail', $product->slug) }}" class="nobifashion_brand_product_visual" style="aspect-ratio: 1/1.05; display: block;">
                                <img src="{{ $imgUrl }}" alt="{{ $product->name }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                            </a>
                            <span class="nobifashion_brand_badge">{{ $brand->name ?? 'NOBIFASHION' }}</span>
                            <button type="button" class="nobifashion_brand_favorite" data-id="{{ $product->id }}" aria-label="Thêm vào yêu thích">♡</button>
                            <div class="nobifashion_brand_product_body">
                                <p class="nobifashion_brand_product_brand">{{ $brand->name ?? 'NOBIFASHION' }}</p>
                                <a href="{{ route('client.product.detail', $product->slug) }}" class="nobifashion_brand_product_title" title="{{ $product->name }}">{{ $product->name }}</a>
                                <span class="nobifashion_brand_product_price">{{ number_format($curPrice, 0, ',', '.') }} ₫</span>
                                @if($hasSale)
                                    <span class="nobifashion_brand_product_old">{{ number_format($product->price, 0, ',', '.') }} ₫</span>
                                @endif
                                <div class="nobifashion_brand_product_meta">
                                    <span class="nobifashion_brand_stars">★★★★★</span>
                                    <span class="nobifashion_brand_sold">Còn {{ $product->stock_quantity }} sp</span>
                                </div>
                            </div>
                        </article>
                    @empty
                        <p style="grid-column: 1 / -1; text-align: center; color: #64748b; padding: 20px 0;">Chưa có sản phẩm nào cho thương hiệu này.</p>
                    @endforelse
                </div>

                @if($recommendProducts->hasMorePages())
                    <button type="button" class="nobifashion_brand_load_more" id="nobifashion_brand_load_more"
                        data-url="{{ route('client.brand.products', $brand->slug ?? $currentSlug) }}"
                        data-next-page="2">
                        Xem thêm sản phẩm
                    </button>
                @endif
            </section>

            <!-- 4. Câu hỏi thường gặp FAQ -->
            @php
                $faqs = $brand->faqs;
            @endphp
            @if(!empty($faqs) && is_array($faqs))
                <section class="nobifashion_brand_faq_section">
                    <h2>Câu hỏi thường gặp</h2>
                    <p>Gian hàng chính hãng {{ $brand->name ?? 'NOBIFASHION' }} giải đáp thắc mắc của bạn.</p>
                    <div class="nobifashion_brand_faq" id="nobifashion_brand_faq">
                        @foreach($faqs as $faq)
                            <article class="nobifashion_brand_faq_item">
                                <button class="nobifashion_brand_faq_question" type="button">
                                    <span>{{ $faq['question'] ?? '' }}</span>
                                    <span class="nobifashion_brand_faq_icon">⌄</span>
                                </button>
                                <div class="nobifashion_brand_faq_answer">{{ $faq['answer'] ?? '' }}</div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </main>

        <!-- Toast thông báo -->
        <div class="nobifashion_brand_toast" id="nobifashion_brand_toast" role="status" aria-live="polite"></div>
    </div>
@endsection

@section('foot')
    <script>
        window.nobifashion_brand_config = {
            productsApi: "{{ route('client.brand.products', $brand->slug ?? $currentSlug) }}",
            brandName: "{{ $brand->name ?? 'NOBIFASHION' }}"
        };
    </script>
    <script src="{{ asset('clients/assets/js/brand.js') }}?v={{ env('APP_VERSION', '1.0') }}"></script>
@endsection

