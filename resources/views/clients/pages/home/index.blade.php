@extends('clients.layouts.master')

@section('body_class', 'nobifashion_home_page')

@section('title', renderMeta(optional($settings)->site_title ?? (optional($settings)->site_name ?? 'Nobi Fashion - Thời trang hiện đại & Tinh tế')))

@section('head')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/home.min.css') }}?v={{ filemtime(public_path('clients/assets/css/home.min.css')) }}">
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="keywords" content="{{ $settings->seo_keywords ?? 'Nobi Fashion, quần áo, phụ kiện, thời trang nam nữ, áo khoác, quần jean' }}">
    <meta name="description"
        content="{{ renderMeta($settings->site_description) ?? 'Nobi Fashion - Thương hiệu thời trang nam nữ đương đại. Phom dáng chuẩn mực, chất liệu chọn lọc, tối ưu cho vóc dáng người Việt.' }}">
    <link rel="canonical" href="{{ $settings->site_url ?? '/' }}">
@endsection

@section('foot')
    <script defer src="{{ asset('clients/assets/js/home.min.js') }}?v={{ filemtime(public_path('clients/assets/js/home.min.js')) }}"></script>
@endsection

@section('schema')
    @include('clients.templates.schema_home')
@endsection

@section('content')
    <main id="nobifashion_home_main" tabindex="-1">
        <h1 class="nobifashion_home_sr_only">{{ $settings->site_name ?? 'Nobi Fashion' }} - Thời Trang Đương Đại &amp; Phụ Kiện Chính Hãng</h1>

        {{-- ====================================================================
             1. HERO EDITORIAL SHOWCASE (Full-Bleed Campaign Slider)
             ==================================================================== --}}
        <section class="nobifashion_home_hero_section" aria-label="Chiến dịch thời trang tiêu điểm">
            <div class="nobifashion_home_hero_slider" id="nobifashion_home_hero_slider">
                {{-- Slide 1: Winter Editorial Campaign với Video Runway hoặc Ảnh Chiến Dịch --}}
                <div class="nobifashion_home_hero_slide nobifashion_home_hero_slide_active" data-nobifashion-slide="0">
                    <div class="nobifashion_home_hero_media">
                        <img class="nobifashion_home_hero_img"
                             src="{{ asset('clients/assets/img/banners/phoi-do-mua-dong.webp') }}"
                             alt="Bộ Sưu Tập Thu Đông - Nobi Fashion"
                             width="1920" height="960" fetchpriority="high" decoding="async">
                        <video class="nobifashion_home_hero_video" id="nobifashion_home_hero_video" muted loop playsinline preload="none"
                               data-src="{{ asset('clients/assets/img/banners/Trinh-dien-thoi-trang.mp4') }}"
                               aria-label="Video trình diễn thời trang"></video>
                        <div class="nobifashion_home_hero_overlay"></div>
                    </div>
                    <div class="nobifashion_home_container nobifashion_home_hero_content_wrap">
                        <div class="nobifashion_home_hero_content">
                            <span class="nobifashion_home_hero_eyebrow">WINTER CAMPAIGN 2026</span>
                            <h2 class="nobifashion_home_hero_title">NGHỆ THUẬT PHỐI ĐỒ<br>THỜI THƯỢNG</h2>
                            <p class="nobifashion_home_hero_desc">
                                Tinh thần tối giản đương đại kết hợp cùng chất liệu giữ nhiệt cao cấp, mang lại phom dáng chuẩn mực cho từng khoảnh khắc đời thường.
                            </p>
                            <div class="nobifashion_home_hero_cta_group">
                                <a href="{{ url('/thoi-trang-nam') }}" class="nobifashion_home_btn nobifashion_home_btn_primary">
                                    <span>Khám Phá Bộ Sưu Tập</span>
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                        <polyline points="12 5 19 12 12 19"></polyline>
                                    </svg>
                                </a>
                                <button type="button" class="nobifashion_home_btn nobifashion_home_btn_outline nobifashion_home_video_trigger"
                                        data-video-target="nobifashion_home_hero_video" aria-label="Xem video trình diễn">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true">
                                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                    </svg>
                                    <span>Xem Trình Diễn</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Slide 2: Minimal Jeans Collection --}}
                <div class="nobifashion_home_hero_slide" data-nobifashion-slide="1">
                    <div class="nobifashion_home_hero_media">
                        <img class="nobifashion_home_hero_img"
                             src="{{ asset('clients/assets/img/banners/bo-suu-tap-quan-jeans.webp') }}"
                             alt="Bộ sưu tập quần Jeans - Nobi Fashion"
                             width="1920" height="960" loading="lazy" decoding="async">
                        <div class="nobifashion_home_hero_overlay"></div>
                    </div>
                    <div class="nobifashion_home_container nobifashion_home_hero_content_wrap">
                        <div class="nobifashion_home_hero_content">
                            <span class="nobifashion_home_hero_eyebrow">SIGNATURE DENIM</span>
                            <h2 class="nobifashion_home_hero_title">ĐẲNG CẤP PHOM DÁNG<br>DENIM VIỆT</h2>
                            <p class="nobifashion_home_hero_desc">
                                Xử lý đường may tỉ mỉ, phom suông kinh điển tôn trọn chiều cao, bền bỉ cùng năm tháng.
                            </p>
                            <div class="nobifashion_home_hero_cta_group">
                                <a href="{{ url('/thoi-trang-nam') }}" class="nobifashion_home_btn nobifashion_home_btn_primary">
                                    <span>Xem Quần Jeans</span>
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                        <polyline points="12 5 19 12 12 19"></polyline>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Slide 3: Active & Streetwear Lifestyle --}}
                <div class="nobifashion_home_hero_slide" data-nobifashion-slide="2">
                    <div class="nobifashion_home_hero_media">
                        <img class="nobifashion_home_hero_img"
                             src="{{ asset('clients/assets/img/banners/bo-suu-tap-ao-khoac-gio.webp') }}"
                             alt="Áo khoác gió thời thượng - Nobi Fashion"
                             width="1920" height="960" loading="lazy" decoding="async">
                        <div class="nobifashion_home_hero_overlay"></div>
                    </div>
                    <div class="nobifashion_home_container nobifashion_home_hero_content_wrap">
                        <div class="nobifashion_home_hero_content">
                            <span class="nobifashion_home_hero_eyebrow">URBAN ACTIVEWEAR</span>
                            <h2 class="nobifashion_home_hero_title">LINH HOẠT TRONG MỌI<br>CHUYỂN ĐỘNG</h2>
                            <p class="nobifashion_home_hero_desc">
                                Áo khoác cản gió, trượt nước nhẹ nhàng và thoáng khí tối đa cho nhịp sống hiện đại năng động.
                            </p>
                            <div class="nobifashion_home_hero_cta_group">
                                <a href="{{ url('/thoi-trang-nu') }}" class="nobifashion_home_btn nobifashion_home_btn_primary">
                                    <span>Mua Ngay</span>
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                        <polyline points="12 5 19 12 12 19"></polyline>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Điều hướng Slider: Chỉ số trang tinh tế & Thanh tiến độ mỏng nhẹ --}}
            <div class="nobifashion_home_hero_nav">
                <div class="nobifashion_home_container nobifashion_home_hero_nav_inner">
                    <div class="nobifashion_home_hero_pagination">
                        <span class="nobifashion_home_hero_counter_current" id="nobifashion_home_hero_current">01</span>
                        <div class="nobifashion_home_hero_progress">
                            <div class="nobifashion_home_hero_progress_bar" id="nobifashion_home_hero_bar"></div>
                        </div>
                        <span class="nobifashion_home_hero_counter_total">03</span>
                    </div>

                    <div class="nobifashion_home_hero_arrows">
                        <button type="button" class="nobifashion_home_hero_arrow nobifashion_home_hero_arrow_prev" id="nobifashion_home_hero_prev" aria-label="Slide trước">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>
                        <button type="button" class="nobifashion_home_hero_arrow nobifashion_home_hero_arrow_next" id="nobifashion_home_hero_next" aria-label="Slide tiếp theo">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- ====================================================================
             2. BRAND VALUES & ASSURANCE STRIP (Bảo chứng phong cách tối giản)
             ==================================================================== --}}
        <section class="nobifashion_home_assurance_section" aria-label="Cam kết dịch vụ">
            <div class="nobifashion_home_container">
                <div class="nobifashion_home_assurance_grid">
                    <div class="nobifashion_home_assurance_item">
                        <div class="nobifashion_home_assurance_index">01</div>
                        <div class="nobifashion_home_assurance_text">
                            <h3 class="nobifashion_home_assurance_title">Miễn Phí Vận Chuyển</h3>
                            <p class="nobifashion_home_assurance_desc">Áp dụng đơn hàng toàn quốc từ 499.000₫</p>
                        </div>
                    </div>
                    <div class="nobifashion_home_assurance_item">
                        <div class="nobifashion_home_assurance_index">02</div>
                        <div class="nobifashion_home_assurance_text">
                            <h3 class="nobifashion_home_assurance_title">Đổi Hàng 15 Ngày</h3>
                            <p class="nobifashion_home_assurance_desc">Hỗ trợ đổi size &amp; mẫu tận nơi linh hoạt</p>
                        </div>
                    </div>
                    <div class="nobifashion_home_assurance_item">
                        <div class="nobifashion_home_assurance_index">03</div>
                        <div class="nobifashion_home_assurance_text">
                            <h3 class="nobifashion_home_assurance_title">Đồng Kiểm 100%</h3>
                            <p class="nobifashion_home_assurance_desc">Kiểm tra đường may &amp; chất vải trước thanh toán</p>
                        </div>
                    </div>
                    <div class="nobifashion_home_assurance_item">
                        <div class="nobifashion_home_assurance_index">04</div>
                        <div class="nobifashion_home_assurance_text">
                            <h3 class="nobifashion_home_assurance_title">Bảo Hành 30 Ngày</h3>
                            <p class="nobifashion_home_assurance_desc">Chính hãng đường chỉ, khuy khóa &amp; phụ kiện</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ====================================================================
             FLASH DEALS / GIÁ TỐT HÔM NAY (Phong cách Dosi-in năng động)
             ==================================================================== --}}
        @if(isset($productsFeatured) && $productsFeatured->isNotEmpty())
        <section class="nobifashion_home_flash_section" aria-label="Khuyến mãi chớp nhoáng">
            <div class="nobifashion_home_container">
                <div class="nobifashion_home_flash_box">
                    <div class="nobifashion_home_flash_header">
                        <div class="nobifashion_home_flash_title_wrap">
                            <span class="nobifashion_home_flash_icon">⚡</span>
                            <h2 class="nobifashion_home_flash_title">FLASH DEALS</h2>
                            <div class="nobifashion_home_flash_timer" id="nobifashion_flash_countdown" data-end-time="{{ now()->addHours(6)->timestamp * 1000 }}">
                                <span class="nobifashion_timer_label">Kết thúc trong</span>
                                <span class="nobifashion_timer_box" id="nobifashion_timer_hours">06</span>
                                <span class="nobifashion_timer_colon">:</span>
                                <span class="nobifashion_timer_box" id="nobifashion_timer_minutes">34</span>
                                <span class="nobifashion_timer_colon">:</span>
                                <span class="nobifashion_timer_box" id="nobifashion_timer_seconds">29</span>
                            </div>
                        </div>
                        <a href="{{ url('/thoi-trang-nam') }}" class="nobifashion_home_flash_all_link">
                            <span>Xem tất cả Deal</span>
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </a>
                    </div>
                    <div class="nobifashion_home_flash_products_wrapper">
                        @include('clients.templates.home_product_grid', ['products' => $productsFeatured->take(4)])
                    </div>
                </div>
            </div>
        </section>
        @endif

        {{-- ====================================================================
             3. CURATED DEPARTMENTS (Lối vào danh mục nghệ thuật Asymmetric Grid)
             ==================================================================== --}}
        <section class="nobifashion_home_departments_section" aria-labelledby="nobifashion_home_dept_title">
            <div class="nobifashion_home_container">
                <div class="nobifashion_home_section_header nobifashion_home_header_between">
                    <div>
                        <span class="nobifashion_home_section_eyebrow">DANH MỤC TUYỂN CHỌN</span>
                        <h2 class="nobifashion_home_section_title" id="nobifashion_home_dept_title">Khám Phá Theo Phong Cách</h2>
                    </div>
                    <a href="{{ url('/thoi-trang-nam') }}" class="nobifashion_home_link_editorial">
                        <span>Xem Tất Cả Danh Mục</span>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                </div>

                {{-- Bố cục bất đối xứng 3 khối phong cách --}}
                <div class="nobifashion_home_dept_grid">
                    {{-- Khối 1: Nam --}}
                    <article class="nobifashion_home_dept_card nobifashion_home_dept_card_large">
                        <a href="{{ url('/thoi-trang-nam') }}" class="nobifashion_home_dept_card_inner" title="Thời Trang Nam">
                            <div class="nobifashion_home_dept_media">
                                <img src="{{ asset('clients/assets/img/banners/bo-suu-tap-ao-khoac-gio.webp') }}"
                                     alt="Thời trang Nam - Nobi Fashion"
                                     width="800" height="450" loading="lazy" decoding="async">
                                <div class="nobifashion_home_dept_overlay"></div>
                            </div>
                            <div class="nobifashion_home_dept_info">
                                <span class="nobifashion_home_dept_tag">MEN'S COLLECTION</span>
                                <h3 class="nobifashion_home_dept_name">Thời Trang Nam</h3>
                                <p class="nobifashion_home_dept_sub">Áo khoác gió, Sơ mi chỉn chu, Quần âu &amp; Jeans hiện đại</p>
                                <span class="nobifashion_home_dept_btn">
                                    <span>Khám Phá Ngay</span>
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                        <polyline points="12 5 19 12 12 19"></polyline>
                                    </svg>
                                </span>
                            </div>
                        </a>
                    </article>

                    {{-- Khối 2: Nữ --}}
                    <article class="nobifashion_home_dept_card nobifashion_home_dept_card_medium">
                        <a href="{{ url('/thoi-trang-nu') }}" class="nobifashion_home_dept_card_inner" title="Thời Trang Nữ">
                            <div class="nobifashion_home_dept_media">
                                <img src="{{ asset('clients/assets/img/banners/ao-len-nu.webp') }}"
                                     alt="Thời trang Nữ - Nobi Fashion"
                                     width="800" height="450" loading="lazy" decoding="async">
                                <div class="nobifashion_home_dept_overlay"></div>
                            </div>
                            <div class="nobifashion_home_dept_info">
                                <span class="nobifashion_home_dept_tag">WOMEN'S COLLECTION</span>
                                <h3 class="nobifashion_home_dept_name">Thời Trang Nữ</h3>
                                <p class="nobifashion_home_dept_sub">Dệt kim, Áo len mềm mại, Váy đầm &amp; Phom dáng thanh lịch</p>
                                <span class="nobifashion_home_dept_btn">
                                    <span>Khám Phá Ngay</span>
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                        <polyline points="12 5 19 12 12 19"></polyline>
                                    </svg>
                                </span>
                            </div>
                        </a>
                    </article>

                    {{-- Khối 3: Thể thao & Đồ mặc nhà --}}
                    <article class="nobifashion_home_dept_card nobifashion_home_dept_card_small">
                        <a href="{{ url('/thoi-trang-nam') }}" class="nobifashion_home_dept_card_inner" title="Thể Thao & Tiện Ích">
                            <div class="nobifashion_home_dept_media">
                                <img src="{{ asset('clients/assets/img/banners/do-chay-bo.webp') }}"
                                     alt="Thể thao & Phụ kiện - Nobi Fashion"
                                     width="800" height="450" loading="lazy" decoding="async">
                                <div class="nobifashion_home_dept_overlay"></div>
                            </div>
                            <div class="nobifashion_home_dept_info">
                                <span class="nobifashion_home_dept_tag">ACTIVE &amp; LIFESTYLE</span>
                                <h3 class="nobifashion_home_dept_name">Thể Thao &amp; Mặc Nhà</h3>
                                <p class="nobifashion_home_dept_sub">Đồ chạy bộ thoáng khí, Quần lót cao cấp &amp; Phụ kiện</p>
                                <span class="nobifashion_home_dept_btn">
                                    <span>Khám Phá Ngay</span>
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                        <polyline points="12 5 19 12 12 19"></polyline>
                                    </svg>
                                </span>
                            </div>
                        </a>
                    </article>
                </div>

                {{-- Dải Category Chips phân loại nhanh --}}
                @if(isset($categories) && $categories->isNotEmpty())
                <div class="nobifashion_home_category_chips_wrap">
                    <span class="nobifashion_home_chips_label">Tìm nhanh:</span>
                    <div class="nobifashion_home_category_chips">
                        @foreach($categories->flatMap(fn($c) => $c->children)->take(10) as $childCat)
                            <a href="{{ url('/' . $childCat->slug) }}" class="nobifashion_home_chip">
                                <span>{{ $childCat->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </section>

        {{-- ====================================================================
             4. THE CURATED DROPS (Bộ sưu tập nổi bật với Instant Tab Switcher)
             ==================================================================== --}}
        <section class="nobifashion_home_curated_section" id="nobifashion_home_curated" aria-labelledby="nobifashion_home_curated_title">
            <div class="nobifashion_home_container">
                <div class="nobifashion_home_section_header nobifashion_home_header_center">
                    <span class="nobifashion_home_section_eyebrow">THE CURATED EDIT</span>
                    <h2 class="nobifashion_home_section_title" id="nobifashion_home_curated_title">Bộ Sưu Tập Nổi Bật</h2>
                    <p class="nobifashion_home_section_subtitle">
                        Những thiết kế được săn đón nhất mùa này với chất vải cao cấp và đường may chuẩn mực
                    </p>

                    {{-- Tabs chuyển đổi sản phẩm tức thì không tải lại trang --}}
                    <div class="nobifashion_home_curated_tabs" role="tablist" aria-label="Lọc nhóm sản phẩm">
                        <button type="button" class="nobifashion_home_tab_pill nobifashion_home_tab_pill_active"
                                data-nobifashion-filter="all" role="tab" aria-selected="true">
                            Tất Cả Sản Phẩm
                        </button>
                        <button type="button" class="nobifashion_home_tab_pill"
                                data-nobifashion-filter="men" role="tab" aria-selected="false">
                            Thời Trang Nam
                        </button>
                        <button type="button" class="nobifashion_home_tab_pill"
                                data-nobifashion-filter="women" role="tab" aria-selected="false">
                            Thời Trang Nữ
                        </button>
                        <button type="button" class="nobifashion_home_tab_pill"
                                data-nobifashion-filter="active" role="tab" aria-selected="false">
                            Thể Thao &amp; Mặc Nhà
                        </button>
                    </div>
                </div>

                {{-- Khung hiển thị các Tab sản phẩm --}}
                <div class="nobifashion_home_curated_content">
                    {{-- Panel: Tất Cả (Lấy từ Featured) --}}
                    <div class="nobifashion_home_tab_panel nobifashion_home_tab_panel_active" id="curated-panel-all">
                        @include('clients.templates.home_product_grid', ['products' => $productsFeatured->take(8)])
                    </div>

                    {{-- Panel: Thời Trang Nam --}}
                    <div class="nobifashion_home_tab_panel" id="curated-panel-men" style="display: none;">
                        @include('clients.templates.home_product_grid', ['products' => $menProducts->take(8)])
                    </div>

                    {{-- Panel: Thời Trang Nữ --}}
                    <div class="nobifashion_home_tab_panel" id="curated-panel-women" style="display: none;">
                        @include('clients.templates.home_product_grid', ['products' => $womenProducts->take(8)])
                    </div>

                    {{-- Panel: Thể thao / Mặc nhà --}}
                    <div class="nobifashion_home_tab_panel" id="curated-panel-active" style="display: none;">
                        @include('clients.templates.home_product_grid', ['products' => $productClothing->slice(0, 8)])
                    </div>
                </div>

                <div class="nobifashion_home_curated_footer">
                    <a href="{{ url('/thoi-trang-nam') }}" class="nobifashion_home_btn nobifashion_home_btn_outline_dark">
                        <span>Xem Toàn Bộ {{ $productsFeatured->count() > 0 ? $productsFeatured->count() . '+' : '' }} Sản Phẩm</span>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        {{-- ====================================================================
             5. ASYMMETRIC LOOKBOOK STORY ("Shop The Look" Editorial)
             ==================================================================== --}}
        <section class="nobifashion_home_lookbook_section" aria-label="Gợi ý phối đồ">
            <div class="nobifashion_home_container">
                <div class="nobifashion_home_lookbook_layout">
                    {{-- Cột trái (Lookbook Image khổ lớn) --}}
                    <div class="nobifashion_home_lookbook_feature">
                        <div class="nobifashion_home_lookbook_visual">
                            <img src="{{ asset('clients/assets/img/banners/ky-niem-mua-dong-2026.webp') }}"
                                 alt="Lookbook Thu Đông 2026 - Nobi Fashion"
                                 width="800" height="450" loading="lazy" decoding="async">
                            <div class="nobifashion_home_lookbook_tagline">
                                <span>LOOKBOOK #04</span>
                                <h4>THE WINTER NARRATIVE</h4>
                            </div>
                        </div>
                    </div>

                    {{-- Cột phải (Câu chuyện phong cách + 2 Sản phẩm phối đồ) --}}
                    <div class="nobifashion_home_lookbook_content">
                        <div class="nobifashion_home_lookbook_header">
                            <span class="nobifashion_home_section_eyebrow">STYLING INSPIRATION</span>
                            <h2 class="nobifashion_home_lookbook_title">Bản Phối Thu Đông Tối Giản</h2>
                            <p class="nobifashion_home_lookbook_text">
                                Đơn giản nhưng không đơn điệu. Sự kết hợp giữa phom áo khoác thanh lịch và quần suông mang đến vẻ ngoài phóng khoáng, giữ ấm tối ưu mà vẫn tôn lên tỷ lệ cơ thể tự nhiên.
                            </p>
                        </div>

                        {{-- Gợi ý 2 sản phẩm phối hợp --}}
                        <div class="nobifashion_home_lookbook_products">
                            @if(isset($womenProducts) && $womenProducts->count() >= 2)
                                @include('clients.templates.home_product_grid', ['products' => $womenProducts->take(2)])
                            @elseif(isset($productsFeatured) && $productsFeatured->count() >= 2)
                                @include('clients.templates.home_product_grid', ['products' => $productsFeatured->slice(8, 2)])
                            @endif
                        </div>

                        <div class="nobifashion_home_lookbook_cta">
                            <a href="{{ url('/thoi-trang-nu') }}" class="nobifashion_home_link_editorial">
                                <span>Xem Hướng Dẫn Phối Đồ Trọn Bộ</span>
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ====================================================================
             6. SEASONAL DUAL CAPSULE (Khối song hành thời trang 50/50)
             ==================================================================== --}}
        <section class="nobifashion_home_capsule_section" aria-label="Tiêu điểm chất liệu">
            <div class="nobifashion_home_container_fluid">
                <div class="nobifashion_home_capsule_grid">
                    {{-- Khối A: Áo Khoác Gió --}}
                    <div class="nobifashion_home_capsule_card">
                        <div class="nobifashion_home_capsule_media">
                            <img src="{{ asset('clients/assets/img/banners/bo-suu-tap-ao-khoac-gio.webp') }}"
                                 alt="Áo khoác gió thời thượng" width="960" height="720" loading="lazy" decoding="async">
                            <div class="nobifashion_home_capsule_overlay"></div>
                        </div>
                        <div class="nobifashion_home_capsule_content">
                            <span class="nobifashion_home_capsule_eyebrow">ESSENTIAL OUTERWEAR</span>
                            <h3 class="nobifashion_home_capsule_title">ÁO KHOÁC GIÓ THỜI TRANG</h3>
                            <p class="nobifashion_home_capsule_desc">Chất liệu trượt nước nhẹ, cản gió vượt trội, phom dáng đa ứng dụng từ đi làm đến du lịch.</p>
                            <a href="{{ url('/thoi-trang-nam') }}" class="nobifashion_home_btn nobifashion_home_btn_primary">
                                <span>Khám Phá Dòng Áo Gió</span>
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </a>
                        </div>
                    </div>

                    {{-- Khối B: Cardigan & Dệt Kim --}}
                    <div class="nobifashion_home_capsule_card">
                        <div class="nobifashion_home_capsule_media">
                            <img src="{{ asset('clients/assets/img/banners/ao-cardigan-lot-long-gia-long-cuu.webp') }}"
                                 alt="Cardigan lót lông giả lông cừu" width="960" height="720" loading="lazy" decoding="async">
                            <div class="nobifashion_home_capsule_overlay"></div>
                        </div>
                        <div class="nobifashion_home_capsule_content">
                            <span class="nobifashion_home_capsule_eyebrow">COZY KNITWEAR</span>
                            <h3 class="nobifashion_home_capsule_title">CARDIGAN &amp; DỆT KIM RELAX</h3>
                            <p class="nobifashion_home_capsule_desc">Mềm mịn êm ái trên da, thiết kế vai rơi phóng khoáng cho những ngày chuyển mùa se lạnh.</p>
                            <a href="{{ url('/thoi-trang-nu') }}" class="nobifashion_home_btn nobifashion_home_btn_primary">
                                <span>Khám Phá Dòng Dệt Kim</span>
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ====================================================================
             7. MORE TO EXPLORE (Gợi ý trang phục theo nhóm sản phẩm tiếp theo)
             ==================================================================== --}}
        @if(isset($menProducts) && $menProducts->isNotEmpty())
        <section class="nobifashion_home_secondary_section" aria-labelledby="nobifashion_home_men_title">
            <div class="nobifashion_home_container">
                <div class="nobifashion_home_section_header nobifashion_home_header_between">
                    <div>
                        <span class="nobifashion_home_section_eyebrow">DÀNH CHO PHÁI MẠNH</span>
                        <h2 class="nobifashion_home_section_title" id="nobifashion_home_men_title">Trang Phục Nam Chuẩn Gu</h2>
                    </div>
                    <a href="{{ url('/thoi-trang-nam') }}" class="nobifashion_home_link_editorial">
                        <span>Xem Tất Cả Đồ Nam</span>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                </div>

                @include('clients.templates.home_product_grid', ['products' => $menProducts->slice(0, 4)])
            </div>
        </section>
        @endif

        {{-- ====================================================================
             8. THE NOBI JOURNAL (Tạp chí phong cách & Bài viết nổi bật)
             ==================================================================== --}}
        @if (!empty($featuredBlogPosts['row1']) && count($featuredBlogPosts['row1']) > 0)
        <section class="nobifashion_home_journal_section" aria-labelledby="nobifashion_home_journal_title">
            <div class="nobifashion_home_container">
                <div class="nobifashion_home_section_header nobifashion_home_header_between">
                    <div>
                        <span class="nobifashion_home_section_eyebrow">NOBI EDITORIAL</span>
                        <h2 class="nobifashion_home_section_title" id="nobifashion_home_journal_title">Cảm Hứng Thời Trang &amp; Phong Cách</h2>
                    </div>
                    <a href="{{ url('/tin-tuc') }}" class="nobifashion_home_link_editorial">
                        <span>Xem Tất Cả Bài Viết</span>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                </div>

                {{-- Tạp chí 4 bài viết phong cách tinh tế --}}
                <div class="nobifashion_home_journal_grid">
                    @php
                        $leadPost = $featuredBlogPosts['row1'][0] ?? null;
                        $sidePosts = array_slice($featuredBlogPosts['row1'], 1, 4);
                    @endphp

                    @if($leadPost)
                    <article class="nobifashion_home_journal_lead">
                        <a href="{{ $leadPost['url'] }}" class="nobifashion_home_journal_lead_link" title="{{ $leadPost['title'] }}">
                            <div class="nobifashion_home_journal_lead_media">
                                <img src="{{ $leadPost['image'] }}" alt="{{ $leadPost['alt'] }}" loading="lazy" decoding="async">
                                <span class="nobifashion_home_journal_badge">TIÊU ĐIỂM</span>
                            </div>
                            <div class="nobifashion_home_journal_lead_content">
                                <h3 class="nobifashion_home_journal_lead_title">{{ $leadPost['title'] }}</h3>
                                @if(!empty($leadPost['excerpt']))
                                    <p class="nobifashion_home_journal_lead_excerpt">{{ Str::limit($leadPost['excerpt'], 140) }}</p>
                                @endif
                                <span class="nobifashion_home_journal_read_btn">
                                    <span>Đọc Bài Viết</span>
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                        <polyline points="12 5 19 12 12 19"></polyline>
                                    </svg>
                                </span>
                            </div>
                        </a>
                    </article>
                    @endif

                    @if(!empty($sidePosts))
                    <div class="nobifashion_home_journal_side_list">
                        @foreach($sidePosts as $sideItem)
                        <article class="nobifashion_home_journal_side_item">
                            <a href="{{ $sideItem['url'] }}" class="nobifashion_home_journal_side_link" title="{{ $sideItem['title'] }}">
                                <div class="nobifashion_home_journal_side_media">
                                    <img src="{{ $sideItem['image'] }}" alt="{{ $sideItem['alt'] }}" loading="lazy" decoding="async">
                                </div>
                                <div class="nobifashion_home_journal_side_content">
                                    <span class="nobifashion_home_journal_side_tag">XU HƯỚNG</span>
                                    <h4 class="nobifashion_home_journal_side_title">{{ $sideItem['title'] }}</h4>
                                    @if(!empty($sideItem['excerpt']))
                                        <p class="nobifashion_home_journal_side_excerpt">{{ Str::limit($sideItem['excerpt'], 75) }}</p>
                                    @endif
                                </div>
                            </a>
                        </article>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </section>
        @endif

        {{-- ====================================================================
             9. NEWSLETTER & VIP CLUB (Đăng ký thành viên tối giản)
             ==================================================================== --}}
        <section class="nobifashion_home_newsletter_section" aria-label="Đăng ký nhận tin">
            <div class="nobifashion_home_container">
                <div class="nobifashion_home_newsletter_box">
                    <span class="nobifashion_home_newsletter_eyebrow">Nobi Fashion CLUB</span>
                    <h2 class="nobifashion_home_newsletter_title">Tham Gia Cùng Chúng Tôi</h2>
                    <p class="nobifashion_home_newsletter_desc">
                        Đăng ký nhận thông tin về các bộ sưu tập giới hạn, hướng dẫn phối đồ theo mùa và nhận đặc quyền giảm 10% cho đơn hàng đầu tiên.
                    </p>
                    <form class="nobifashion_home_newsletter_form" action="#" method="POST" onsubmit="event.preventDefault(); alert('Cảm ơn bạn đã đăng ký nhận tin từ Nobi Fashion!');">
                        <div class="nobifashion_home_newsletter_input_wrap">
                            <input type="email" class="nobifashion_home_newsletter_input" placeholder="Nhập địa chỉ email của bạn..." required aria-label="Địa chỉ email">
                            <button type="submit" class="nobifashion_home_newsletter_submit" aria-label="Đăng ký nhận tin">
                                <span>Đăng Ký</span>
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </button>
                        </div>
                    </form>
                    <span class="nobifashion_home_newsletter_note">Bằng cách đăng ký, bạn đồng ý với Chính sách bảo mật của Nobi Fashion.</span>
                </div>
            </div>
        </section>

        <div class="nobifashion_home_spacer" aria-hidden="true"></div>
    </main>
@endsection

