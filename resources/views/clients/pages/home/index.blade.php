@extends('clients.layouts.master')

@section('body_class', 'nobifashion_home_page')

@section('title', renderMeta(optional($settings)->site_title ?? (optional($settings)->site_name ?? 'NOBI FASHION - Shop
    quần áo & phụ kiện thời trang')))

@section('head')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/home.css') }}?v={{ filemtime(public_path('clients/assets/css/home.css')) }}">
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="keywords" content="{{ $settings->seo_keywords ?? 'NOBI FASHION, quần áo, phụ kiện, thời trang' }}">
    <meta name="description"
        content="{{ renderMeta($settings->site_description) ?? 'NOBI FASHION - Shop quần áo & phụ kiện thời trang' }}">
    <link rel="canonical" href="{{ $settings->site_url ?? '/' }}">
@endsection

@section('foot')
    <script defer src="{{ asset('clients/assets/js/home.js') }}?v={{ filemtime(public_path('clients/assets/js/home.js')) }}"></script>
@endsection

@section('schema')
    @include('clients.templates.schema_home')
@endsection

@section('content')
    <main id="nobifashion_home_main" tabindex="-1">
        <h1 class="nobifashion_home_sr_only">{{ $settings->site_name }} - Shop quần áo &amp; phụ kiện thời trang
        </h1>
        <section class="nobifashion_home_banner" data-nobifashion-tone="dark"
            aria-labelledby="nobifashion_home_banner_0_title">
            <a class="nobifashion_home_banner_link" href="#">
                <picture class="nobifashion_home_banner_media">
                    <source media="(max-width: 959px)"
                        srcset="{{ asset('clients/assets/img/banners/phoi-do-mua-dong.webp') }}">
                    <img src="{{ asset('clients/assets/img/banners/phoi-do-mua-dong.webp') }}"
                        alt="Bộ Sưu Tập Phối Đồ Mùa Đông 2026 - {{ optional($settings)->site_name ?? 'NOBI FASHION' }}" width="1600" height="800" fetchpriority="high" decoding="async">
                    </source>
                </picture>
                <video class="nobifashion_home_banner_video" id="nobifashion_home_banner_0_video" muted loop playsinline
                    preload="none"
                    data-nobifashion-video-desktop="{{ asset('clients/assets/img/banners/Trinh-dien-thoi-trang.mp4') }}"
                    data-nobifashion-video-mobile="{{ asset('clients/assets/img/banners/Trinh-dien-thoi-trang.mp4') }}"
                    aria-label="Trình diễn Bộ Sưu Tập Thời Trang Mùa Đông"></video>
                <div class="nobifashion_home_banner_copy">
                    <div class="nobifashion_home_banner_badge">
                        <span class="nobifashion_home_banner_badge_text">WINTER COLLECTION 2026</span>
                    </div>
                    <h2 class="nobifashion_home_banner_title" id="nobifashion_home_banner_0_title">Bộ Sưu Tập Phối Đồ Mùa Đông 2026</h2>
                    <p class="nobifashion_home_banner_description">Cảm hứng thể thao mùa đông hiện đại & phong cách giữ nhiệt thời thượng. Khám phá những thiết kế áo khoác phao, áo len và trang phục ấm áp đẳng cấp nhất.</p>
                </div>
            </a>
            <button class="nobifashion_home_icon_button nobifashion_home_video_toggle" type="button"
                aria-label="Phát video" data-nobifashion-video-toggle="nobifashion_home_banner_0_video"
                aria-controls="nobifashion_home_banner_0_video" aria-pressed="false">
                <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
                    <path d="m9 5 10 7-10 7Z"></path>
                </svg>
            </button>
        </section>

        {{-- ====================================================================
             3D COVERFLOW HERO BANNER MAIN
             Dữ liệu lấy trực tiếp từ bảng banners (Controller query & cache)
             Tất cả class đều chứa tiền tố: nobifashion_home_banner_main_
             Phân trang dấu chấm to (Large Dots)
             ==================================================================== --}}
        @if(!empty($homeMainBanners) && $homeMainBanners->isNotEmpty())
        <section class="nobifashion_home_banner_main_section" aria-label="Bộ sưu tập nổi bật 3D Coverflow">
            {{-- Lớp ánh sáng nền đổi màu mờ ảo theo banner trung tâm (Ambient Lighting Backdrop) --}}
            <div class="nobifashion_home_banner_main_ambient" aria-hidden="true">
                <div class="nobifashion_home_banner_main_ambient_glow" id="nobifashion_home_banner_main_ambient_glow"></div>
            </div>

            <div class="nobifashion_home_banner_main_container">
                {{-- Sân khấu 3D Carousel --}}
                <div class="nobifashion_home_banner_main_carousel" id="nobifashion_home_banner_main_carousel" tabindex="0" role="region" aria-roledescription="carousel" aria-label="Banner 3D Coverflow">
                    <div class="nobifashion_home_banner_main_stage" id="nobifashion_home_banner_main_stage">
                        @foreach($homeMainBanners as $bannerItem)
                            @php
                                $isFirst = $loop->first;
                                $desktopFilename = basename($bannerItem->image_desktop ?: $bannerItem->image_mobile);
                                $mobileFilename = basename($bannerItem->image_mobile ?: $bannerItem->image_desktop);

                                $desktopUrl = $desktopFilename ? asset('clients/assets/img/banners/' . $desktopFilename) : asset('clients/assets/no-image.webp');
                                $mobileUrl = $mobileFilename ? asset('clients/assets/img/banners/' . $mobileFilename) : $desktopUrl;
                                $brandTitle = Str::upper($bannerItem->title ?? 'NOBI FASHION');
                                $tagBadge = 'SALE';
                            @endphp
                            <article class="nobifashion_home_banner_main_card {{ $isFirst ? 'nobifashion_home_banner_main_card_active' : '' }}"
                                     data-nobifashion-index="{{ $loop->index }}"
                                     data-nobifashion-image="{{ $desktopUrl }}"
                                     role="group"
                                     aria-roledescription="slide"
                                     aria-label="{{ $loop->iteration }} trên {{ $homeMainBanners->count() }}"
                                     aria-hidden="{{ $isFirst ? 'false' : 'true' }}">
                                <div class="nobifashion_home_banner_main_card_inner">
                                    <a class="nobifashion_home_banner_main_card_link"
                                       href="{{ $bannerItem->link ?: '#' }}"
                                       target="{{ $bannerItem->taget ?: '_self' }}"
                                       title="{{ $bannerItem->title }}"
                                       data-nobifashion-index="{{ $loop->index }}">
                                        <div class="nobifashion_home_banner_main_card_media">
                                            <picture class="nobifashion_home_banner_main_picture">
                                                @if($mobileUrl && $mobileUrl !== $desktopUrl)
                                                    <source media="(max-width: 768px)" srcset="{{ $mobileUrl }}">
                                                @endif
                                                <img class="nobifashion_home_banner_main_card_img"
                                                     src="{{ $desktopUrl }}"
                                                     alt="{{ $bannerItem->title }}"
                                                     width="740"
                                                     height="420"
                                                     loading="{{ $loop->iteration <= 2 ? 'eager' : 'lazy' }}"
                                                     decoding="async"
                                                     {{ $isFirst ? 'fetchpriority=high' : '' }}
                                                     onerror="this.onerror=null; this.src='{{ asset('clients/assets/no-image.webp') }}';">
                                            </picture>
                                            <div class="nobifashion_home_banner_main_card_overlay" aria-hidden="true"></div>
                                        </div>

                                        {{-- Huy hiệu pill thương hiệu & tag SALE góc dưới bên trái --}}
                                        <div class="nobifashion_home_banner_main_card_badge_box">
                                            <span class="nobifashion_home_banner_main_card_brand_badge">{{ $brandTitle }}</span>
                                            <span class="nobifashion_home_banner_main_card_sale_badge">{{ $tagBadge }}</span>
                                        </div>

                                        @if(!empty($bannerItem->description))
                                        <div class="nobifashion_home_banner_main_card_info">
                                            <span class="nobifashion_home_banner_main_card_desc">{{ $bannerItem->description }}</span>
                                        </div>
                                        @endif
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    {{-- Nút mũi tên chuyển slide Trái / Phải dạng tròn glassmorphism --}}
                    <button type="button"
                            class="nobifashion_home_banner_main_nav_btn nobifashion_home_banner_main_nav_prev"
                            id="nobifashion_home_banner_main_prev_btn"
                            aria-label="Xem banner trước">
                        <svg class="nobifashion_home_banner_main_nav_icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    <button type="button"
                            class="nobifashion_home_banner_main_nav_btn nobifashion_home_banner_main_nav_next"
                            id="nobifashion_home_banner_main_next_btn"
                            aria-label="Xem banner kế tiếp">
                        <svg class="nobifashion_home_banner_main_nav_icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>

                {{-- Thanh phân trang dấu chấm to (Large Dots Pagination) theo yêu cầu --}}
                <nav class="nobifashion_home_banner_main_pagination" aria-label="Điều hướng các mục banner">
                    @foreach($homeMainBanners as $pageItem)
                        <button type="button"
                                class="nobifashion_home_banner_main_pagination_dot {{ $loop->first ? 'nobifashion_home_banner_main_pagination_dot_active' : '' }}"
                                data-nobifashion-index="{{ $loop->index }}"
                                aria-label="Chuyển tới slide {{ $loop->iteration }}"
                                aria-current="{{ $loop->first ? 'true' : 'false' }}">
                            <span class="nobifashion_home_banner_main_pagination_dot_inner"></span>
                        </button>
                    @endforeach
                </nav>
            </div>
        </section>
        @endif

        {{-- ====================================================================
             KHÁM PHÁ THEO DANH MỤC - MODERN TABS & AESTHETIC SQUIRCLE CARDS
             ==================================================================== --}}
        <section class="nobifashion_home_categories_section" id="nobifashion_home_categories" data-nobifashion-tone="light"
            aria-labelledby="nobifashion_home_categories_title">
            <div class="nobifashion_home_container">
                <div class="nobifashion_home_categories_header">
                    <div class="nobifashion_home_categories_title_box">
                        <span class="nobifashion_home_categories_badge">BỘ SƯU TẬP NOBI</span>
                        <h2 class="nobifashion_home_categories_title" id="nobifashion_home_categories_title">Khám Phá Danh Mục</h2>
                        <p class="nobifashion_home_categories_subtitle">Lựa chọn trang phục & phụ kiện phù hợp theo phong cách của bạn</p>
                    </div>

                    {{-- Bộ lọc Tabs phân loại hiện đại (Department Switcher) --}}
                    <div class="nobifashion_home_categories_tabs" role="tablist" aria-label="Bộ lọc danh mục">
                        @foreach ($categories as $rootCat)
                            @php
                                $slug = $rootCat->slug;
                            @endphp
                            <button type="button"
                                    class="nobifashion_home_categories_tab_btn {{ $loop->first ? 'nobifashion_home_categories_tab_active' : '' }}"
                                    data-nobifashion-tab="cat-{{ $rootCat->id }}"
                                    role="tab"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                @if(str_contains($slug, 'nam'))
                                    <svg class="nobifashion_home_categories_tab_icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                @elseif(str_contains($slug, 'nu'))
                                    <svg class="nobifashion_home_categories_tab_icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M6 3h12l4 6-10 12L2 9z"></path>
                                    </svg>
                                @elseif(str_contains($slug, 'tre-em'))
                                    <svg class="nobifashion_home_categories_tab_icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9"></circle>
                                        <path d="M9 10h.01"></path>
                                        <path d="M15 10h.01"></path>
                                        <path d="M9.5 15a3.5 3.5 0 0 0 5 0"></path>
                                    </svg>
                                @else
                                    <svg class="nobifashion_home_categories_tab_icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                    </svg>
                                @endif
                                <span class="nobifashion_home_categories_tab_name">{{ $rootCat->name }}</span>
                                <span class="nobifashion_home_categories_tab_count">{{ $rootCat->children->count() }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Khung hiển thị danh mục theo từng Tab --}}
                <div class="nobifashion_home_categories_content">
                    @foreach ($categories as $rootCat)
                        <div class="nobifashion_home_categories_panel {{ $loop->first ? 'nobifashion_home_categories_panel_active' : '' }}"
                             id="nobifashion_cat_panel_cat-{{ $rootCat->id }}"
                             role="tabpanel"
                             {!! $loop->first ? '' : 'style="display: none;"' !!}>
                            <div class="nobifashion_home_categories_grid">
                                @foreach ($rootCat->children as $category)
                                    <a class="nobifashion_home_category_card" href="{{ url('/' . $category->slug) }}" title="{{ $category->name }}">
                                        <div class="nobifashion_home_category_media">
                                            <img class="nobifashion_home_category_img"
                                                 src="{{ $category->image_url }}"
                                                 alt="{{ $category->name }}"
                                                 width="120"
                                                 height="120"
                                                 loading="lazy"
                                                 decoding="async"
                                                 onerror="this.onerror=null; this.src='{{ asset('clients/assets/img/categories/no-image.webp') }}';">
                                        </div>
                                        <span class="nobifashion_home_category_name">{{ $category->name }}</span>
                                        <span class="nobifashion_home_category_explore" aria-hidden="true">
                                            Khám phá
                                            <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="9 18 15 12 9 6"></polyline>
                                            </svg>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="nobifashion_home_categories_footer">
                    <button class="nobifashion_home_pill nobifashion_home_all_categories_btn" type="button"
                        data-nobifashion-open="menu" aria-controls="nobifashion_home_menu" aria-expanded="false">
                        <span>Xem tất cả danh mục sản phẩm</span>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
        </section>

        <section class="nobifashion_home_banner" data-nobifashion-tone="dark"
            aria-labelledby="nobifashion_home_banner_1_title">
            <a class="nobifashion_home_banner_link" href="#">
                <picture class="nobifashion_home_banner_media">
                    <source media="(max-width: 959px)"
                        srcset="{{ asset('clients/assets/img/banners/ky-niem-mua-dong-2026.webp') }}">
                    <img src="{{ asset('clients/assets/img/banners/ky-niem-mua-dong-2026.webp') }}"
                        alt="Kỷ niệm đồ Thu/Đông 2026" width="1600" height="800" loading="lazy" decoding="async">
                    </source>
                </picture>
                <div class="nobifashion_home_banner_copy">
                    <h2 class="nobifashion_home_banner_title" id="nobifashion_home_banner_1_title">Kỷ niệm đồ Thu/Đông 2026</h2>
                </div>
            </a>
        </section>
        <div class="nobifashion_home_spacer" aria-hidden="true"></div>
        <section class="nobifashion_home_banner" data-nobifashion-tone="dark"
            aria-labelledby="nobifashion_home_banner_2_title">
            <a class="nobifashion_home_banner_link" href="#">
                <picture class="nobifashion_home_banner_media">
                    <source media="(max-width: 959px)"
                        srcset="{{ asset('clients/assets/img/banners/bo-suu-tap-quan-jeans.webp') }}">
                    <img src="{{ asset('clients/assets/img/banners/bo-suu-tap-quan-jeans.webp') }}"
                        alt="Bo Sưu Tập Quần Jeans" width="1600" height="800" loading="lazy" decoding="async">
                    </source>
                </picture>
                <video class="nobifashion_home_banner_video" id="nobifashion_home_banner_2_video" muted loop playsinline
                    preload="none"
                    data-nobifashion-video-desktop="{{ asset('clients/assets/img/banners/bo-suu-tap-quan-jeans.webp') }}"
                    data-nobifashion-video-mobile="{{ asset('clients/assets/img/banners/bo-suu-tap-quan-jeans.webp') }}"
                    aria-label="WOMEN Jeans"></video>
                <div class="nobifashion_home_banner_copy">
                    <h2 class="nobifashion_home_banner_title" id="nobifashion_home_banner_2_title">Bộ Sưu Tập Quần Jeans
                    </h2>
                    <p class="nobifashion_home_banner_description">Đa dạng phom dáng cho bạn lựa chọn.
                        *Áp dụng dịch vụ lên lai quần &amp; thêm lựa chọn kích cỡ tại Nobi Fashion online.</p>
                </div>
            </a>
            <button class="nobifashion_home_icon_button nobifashion_home_video_toggle" type="button"
                aria-label="Phát video" data-nobifashion-video-toggle="nobifashion_home_banner_2_video"
                aria-controls="nobifashion_home_banner_2_video" aria-pressed="false">
                <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
                    <path d="m9 5 10 7-10 7Z"></path>
                </svg>
            </button>
        </section>
        <div class="nobifashion_home_spacer" aria-hidden="true"></div>
        <section class="nobifashion_home_banner" data-nobifashion-tone="dark"
            aria-labelledby="nobifashion_home_banner_3_title">
            <a class="nobifashion_home_banner_link" href="#">
                    <source media="(max-width: 959px)"
                        srcset="{{ asset('clients/assets/img/banners/bo-suu-tap-ao-khoac-gio.webp') }}">
                    <img src="{{ asset('clients/assets/img/banners/bo-suu-tap-ao-khoac-gio.webp') }}"
                        alt="Sản Phẩm Hot Bán Chạy" width="1600" height="800" loading="lazy" decoding="async">
                    </source>
                </picture>
                <div class="nobifashion_home_banner_copy">
                    <div class="nobifashion_home_banner_badge">
                        <img src="{{ asset('clients/assets/img/banners/bo-suu-tap-ao-khoac-gio.webp') }}"
                            alt="Áo Khoác Gió" width="90" height="30" loading="lazy">
                    </div>
                    <h2 class="nobifashion_home_banner_title" id="nobifashion_home_banner_3_title">Áo Khoác Gió</h2>
                    <p class="nobifashion_home_banner_description">Đa dạng phom dáng cho bạn lựa chọn. *Áp dụng dịch vụ
                        lên lai quần &amp; thêm lựa chọn kích cỡ tại Nobi Fashion online.</p>
                    <p class="nobifashion_home_banner_price">980.000 VND<del
                            class="nobifashion_home_banner_old_price">1.275.000
                            VND</del>
                    </p>
                </div>
            </a>
        </section>
                                @include('clients.templates.home_product_grid', ['products' => $productsFeatured->take(4)])

        <section class="nobifashion_home_banner" data-nobifashion-tone="dark"
            aria-labelledby="nobifashion_home_banner_4_title">
            <a class="nobifashion_home_banner_link" href="#">
                <picture class="nobifashion_home_banner_media">
                    <source media="(max-width: 959px)"
                        srcset="{{ asset('clients/assets/img/banners/do-chay-bo.webp') }}">
                    <img src="{{ asset('clients/assets/img/banners/do-chay-bo.webp') }}" alt="Đồ chạy bộ"
                        width="1600" height="800" loading="lazy" decoding="async">
                    </source>
                </picture>
                <div class="nobifashion_home_banner_copy">
                    <div class="nobifashion_home_banner_badge">
                        <img src="{{ asset('clients/assets/img/banners/do-chay-bo.webp') }}"
                            alt="NEW COLOR" width="90" height="30" loading="lazy">
                    </div>
                    <h2 class="nobifashion_home_banner_title" id="nobifashion_home_banner_4_title">Bộ Sưu Tập Đồ Chạy Bộ
                    </h2>
                    <p class="nobifashion_home_banner_description">Đắm chìm trong thiết kế tinh xảo của các vận động viên
                    </p>
                    <p class="nobifashion_home_banner_price">Từ 399.000 VND</p>
                </div>
            </a>
        </section>
        <div class="nobifashion_home_spacer" aria-hidden="true"></div>
        <section class="nobifashion_home_banner" data-nobifashion-tone="dark"
            aria-labelledby="nobifashion_home_banner_5_title">
            <a class="nobifashion_home_banner_link" href="#">
                <picture class="nobifashion_home_banner_media">
                    <source media="(max-width: 959px)"
                        srcset="{{ asset('clients/assets/img/banners/quan-lot-nam.webp') }}">
                    <img src="{{ asset('clients/assets/img/banners/quan-lot-nam.webp') }}" alt="Quần lót nam"
                        width="1600" height="800" loading="lazy" decoding="async">
                    </source>
                </picture>
                <div class="nobifashion_home_banner_copy">
                    <h2 class="nobifashion_home_banner_title" id="nobifashion_home_banner_5_title">Gợi Ý Trang Phục Mặc Nhà
                    </h2>
                    <p class="nobifashion_home_banner_description">Thoải Mái Nhưng Vẫn Chỉn Chu Với Những Lựa Chọn Này.
                    </p>
                    <p class="nobifashion_home_banner_price">399.000 VND<del
                            class="nobifashion_home_banner_old_price">499.000
                            VND</del>
                    </p>
                </div>
            </a>
        </section>
                @include('clients.templates.home_product_grid', ['products' => $productClothing->slice(0, 4)])

        <section class="nobifashion_home_banner" data-nobifashion-tone="dark"
            aria-labelledby="nobifashion_home_banner_6_title">
            <a class="nobifashion_home_banner_link" href="#">
                <picture class="nobifashion_home_banner_media">
                    <source media="(max-width: 959px)"
                        srcset="{{ asset('clients/assets/img/banners/ao-thun-nam-nu.webp') }}">
                    <img src="{{ asset('clients/assets/img/banners/ao-thun-nam-nu.webp') }}" alt="WOMEN T-shirts"
                        width="1600" height="800" loading="lazy" decoding="async">
                    </source>
                </picture>
                <div class="nobifashion_home_banner_copy">
                    <div class="nobifashion_home_banner_badge">
                        <img src="{{ asset('clients/assets/img/banners/ao-thun-nam-nu.webp') }}"
                            alt="Best seller" width="90" height="30" loading="lazy">
                    </div>
                    <h2 class="nobifashion_home_banner_title" id="nobifashion_home_banner_6_title">Bộ Sưu Tập Áo Thun Nam Nữ
                    </h2>
                    <p class="nobifashion_home_banner_description">Sản phẩm bán chạy trong tuần qua.</p>
                    <p class="nobifashion_home_banner_price">399.000 VND<del
                            class="nobifashion_home_banner_old_price">499.000
                            VND</del>
                    </p>
                </div>
            </a>
        </section>
        <div class="nobifashion_home_spacer" aria-hidden="true"></div>
        <section class="nobifashion_home_banner" data-nobifashion-tone="dark"
            aria-labelledby="nobifashion_home_banner_7_title">
            <a class="nobifashion_home_banner_link"
                href="#">
                <picture class="nobifashion_home_banner_media">
                    <source media="(max-width: 959px)"
                        srcset="{{ asset('clients/assets/img/banners/ao-ni-nam-nu.webp') }}">
                    <img src="{{ asset('clients/assets/img/banners/ao-ni-nam-nu.webp') }}"
                        alt="WOMEN Sweatshirts &amp; Hoodies" width="1600" height="800" loading="lazy"
                        decoding="async">
                    </source>
                </picture>
                <div class="nobifashion_home_banner_copy">
                    <div class="nobifashion_home_banner_badge">
                        <img src="{{ asset('clients/assets/img/banners/ao-ni-nam-nu.webp') }}"
                            alt="TRENDING" width="90" height="30" loading="lazy">
                    </div>
                    <h2 class="nobifashion_home_banner_title" id="nobifashion_home_banner_7_title">Áo Nỉ Nam Nữ</h2>
                    <p class="nobifashion_home_banner_description">Mẫu mới trong tuần.</p>
                    <p class="nobifashion_home_banner_price">399.000 VND<del
                            class="nobifashion_home_banner_old_price">499.000
                            VND</del>
                    </p>
                </div>
            </a>
        </section>
        <div class="nobifashion_home_spacer" aria-hidden="true"></div>
        <section class="nobifashion_home_banner" data-nobifashion-tone="dark"
            aria-labelledby="nobifashion_home_banner_8_title">
            <a class="nobifashion_home_banner_link"
                href="#">
                <picture class="nobifashion_home_banner_media">
                    <source media="(max-width: 959px)"
                        srcset="{{ asset('clients/assets/img/banners/ao-cardigan-lot-long-gia-long-cuu.webp') }}">
                    <img src="{{ asset('clients/assets/img/banners/ao-cardigan-lot-long-gia-long-cuu.webp') }}"
                        alt="WOMEN New Arrivals" width="1600" height="800" loading="lazy" decoding="async">
                    </source>
                </picture>
                <div class="nobifashion_home_banner_copy">
                    <div class="nobifashion_home_banner_badge">
                        <img src="{{ asset('clients/assets/img/banners/ao-cardigan-lot-long-gia-long-cuu.webp') }}"
                            alt="LifeWear magazine (White)" width="90" height="30" loading="lazy">
                    </div>
                    <h2 class="nobifashion_home_banner_title" id="nobifashion_home_banner_8_title">Áo Khoác Cardigan Lót Lông Giả Lông Cừu</h2>
                    <p class="nobifashion_home_banner_description">Làm mới phong cách với Áo Cardigan Lót Lông Giả Lông
                        Cừu Dáng
                        Relax cùng những thiết kế mới vừa ra mắt.</p>
                    <p class="nobifashion_home_banner_price">999.000 VND<del
                            class="nobifashion_home_banner_old_price">1.299.000
                            VND</del>
                    </p>
                </div>
            </a>
        </section>
                @include('clients.templates.home_product_grid', ['products' => $womenProducts->take(4)])

        <section class="nobifashion_home_banner" data-nobifashion-tone="dark"
            aria-labelledby="nobifashion_home_banner_9_title">
            <a class="nobifashion_home_banner_link"
                href="#">
                <picture class="nobifashion_home_banner_media">
                    <source media="(max-width: 959px)"
                        srcset="{{ asset('clients/assets/img/banners/ao-len-nu.webp') }}">
                    <img src="{{ asset('clients/assets/img/banners/ao-len-nu.webp') }}"
                        alt="Sweaters &amp; Knitwear" width="1600" height="800" loading="lazy"
                        decoding="async">
                    </source>
                </picture>
                <div class="nobifashion_home_banner_copy">
                    <h2 class="nobifashion_home_banner_title" id="nobifashion_home_banner_9_title">Áo Len Nữ</h2>
                    <p class="nobifashion_home_banner_description">Khám phá đa dạng các thiết kế len chất lượng, từ áo len
                        chống
                        tia UV đến Cashmere mềm mại.</p>
                    <p class="nobifashion_home_banner_price">250.000 VND<del
                            class="nobifashion_home_banner_old_price">350.000
                            VND</del>
                    </p>
                </div>
            </a>
        </section>
                @include('clients.templates.home_product_grid', ['products' => $productsFeatured->slice(4, 4)])

        <section class="nobifashion_home_banner" data-nobifashion-tone="dark"
            aria-labelledby="nobifashion_home_banner_10_title">
            <a class="nobifashion_home_banner_link"
                href="#">
                <picture class="nobifashion_home_banner_media">
                    <source media="(max-width: 959px)"
                        srcset="{{ asset('clients/assets/img/banners/do-lot-nu.webp') }}">
                    <img src="{{ asset('clients/assets/img/banners/do-lot-nu.webp') }}"
                        alt="Đồ lót nữ" width="1600" height="800" loading="lazy"
                        decoding="async">
                    </source>
                </picture>
                <div class="nobifashion_home_banner_copy">
                    <div class="nobifashion_home_banner_badge">
                        <img src="{{ asset('clients/assets/img/banners/do-lot-nu.webp') }}"
                            alt="Đồ lót nữ" width="90" height="30" loading="lazy">
                    </div>
                    <h2 class="nobifashion_home_banner_title" id="nobifashion_home_banner_10_title">Đồ lót nữ</h2>
                </div>
            </a>
        </section>

        {{-- SECTION: BÀI VIẾT NỔI BẬT (SLIDER 2 HÀNG CHẠY VÔ TẬN) --}}
        @if (!empty($featuredBlogPosts['row1']) && count($featuredBlogPosts['row1']) > 0)
            <section class="nobifashion_home_blog_section" aria-labelledby="nobifashion_home_blog_title">
                <div class="nobifashion_home_blog_header">
                    <h2 class="nobifashion_home_blog_title" id="nobifashion_home_blog_title">Bài viết nổi bật</h2>
                </div>

                {{-- HÀNG 1: Chạy sang phải (Left to Right) --}}
                <div class="nobifashion_home_blog_marquee nobifashion_marquee_ltr">
                    <div class="nobifashion_home_blog_track">
                        @foreach ($featuredBlogPosts['row1'] as $post)
                            <a href="{{ $post['url'] }}" class="nobifashion_home_blog_card" title="{{ $post['title'] }}">
                                <div class="nobifashion_home_blog_card_media">
                                    <img src="{{ $post['image'] }}" alt="{{ $post['alt'] }}" loading="lazy" decoding="async">
                                    <div class="nobifashion_home_blog_card_overlay"></div>
                                </div>
                                <div class="nobifashion_home_blog_card_content">
                                    <h3 class="nobifashion_home_blog_card_title">{{ $post['title'] }}</h3>
                                    @if (!empty($post['excerpt']))
                                        <p class="nobifashion_home_blog_card_excerpt">{{ $post['excerpt'] }}</p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                        {{-- Duplicate để chạy vòng lặp vô tận mượt mà --}}
                        @foreach ($featuredBlogPosts['row1'] as $post)
                            <a href="{{ $post['url'] }}" class="nobifashion_home_blog_card" aria-hidden="true" tabindex="-1">
                                <div class="nobifashion_home_blog_card_media">
                                    <img src="{{ $post['image'] }}" alt="{{ $post['alt'] }}" loading="lazy" decoding="async">
                                    <div class="nobifashion_home_blog_card_overlay"></div>
                                </div>
                                <div class="nobifashion_home_blog_card_content">
                                    <h3 class="nobifashion_home_blog_card_title">{{ $post['title'] }}</h3>
                                    @if (!empty($post['excerpt']))
                                        <p class="nobifashion_home_blog_card_excerpt">{{ $post['excerpt'] }}</p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- HÀNG 2: Chạy sang trái (Right to Left) --}}
                <div class="nobifashion_home_blog_marquee nobifashion_marquee_rtl">
                    <div class="nobifashion_home_blog_track">
                        @foreach ($featuredBlogPosts['row2'] as $post)
                            <a href="{{ $post['url'] }}" class="nobifashion_home_blog_card" title="{{ $post['title'] }}">
                                <div class="nobifashion_home_blog_card_media">
                                    <img src="{{ $post['image'] }}" alt="{{ $post['alt'] }}" loading="lazy" decoding="async">
                                    <div class="nobifashion_home_blog_card_overlay"></div>
                                </div>
                                <div class="nobifashion_home_blog_card_content">
                                    <h3 class="nobifashion_home_blog_card_title">{{ $post['title'] }}</h3>
                                    @if (!empty($post['excerpt']))
                                        <p class="nobifashion_home_blog_card_excerpt">{{ $post['excerpt'] }}</p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                        {{-- Duplicate để chạy vòng lặp vô tận mượt mà --}}
                        @foreach ($featuredBlogPosts['row2'] as $post)
                            <a href="{{ $post['url'] }}" class="nobifashion_home_blog_card" aria-hidden="true" tabindex="-1">
                                <div class="nobifashion_home_blog_card_media">
                                    <img src="{{ $post['image'] }}" alt="{{ $post['alt'] }}" loading="lazy" decoding="async">
                                    <div class="nobifashion_home_blog_card_overlay"></div>
                                </div>
                                <div class="nobifashion_home_blog_card_content">
                                    <h3 class="nobifashion_home_blog_card_title">{{ $post['title'] }}</h3>
                                    @if (!empty($post['excerpt']))
                                        <p class="nobifashion_home_blog_card_excerpt">{{ $post['excerpt'] }}</p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
        <div class="nobifashion_home_spacer" aria-hidden="true"></div>
    </main>

@endsection
