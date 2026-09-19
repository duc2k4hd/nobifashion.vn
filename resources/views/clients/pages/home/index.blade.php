@extends('clients.layouts.master')

@section('body_class', 'nobifashion_home_page')

@section('title', renderMeta(optional($settings)->site_title ?? (optional($settings)->site_name ?? 'NOBI FASHION - Shop
    quần áo & phụ kiện thời trang')))

@section('head')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/home.css') }}?v={{ env('APP_VERSION') }}">
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="keywords" content="{{ $settings->seo_keywords ?? 'NOBI FASHION, quần áo, phụ kiện, thời trang' }}">
    <meta name="description"
        content="{{ renderMeta($settings->site_description) ?? 'NOBI FASHION - Shop quần áo & phụ kiện thời trang' }}">
    <link rel="canonical" href="{{ $settings->site_url ?? '/' }}">
@endsection

@section('foot')
    <script defer src="{{ asset('clients/assets/js/home.js') }}?v={{ env('APP_VERSION') }}"></script>
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
        <nav class="nobifashion_home_categories" id="nobifashion_home_categories" data-nobifashion-tone="light"
            aria-labelledby="nobifashion_home_categories_title">
            <div class="nobifashion_home_container">
                <h2 class="nobifashion_home_section_title" id="nobifashion_home_categories_title">Tìm theo danh mục</h2>
                <div class="nobifashion_home_category_grid">
                    @foreach ($categories as $rootCat)
                        @foreach ($rootCat->children as $category)
                            <a class="nobifashion_home_category" href="{{ url('/' . $category->slug) }}">
                                <img class="nobifashion_home_category_image"
                                    src="{{ $category->image_url }}"
                                    alt="{{ $category->name }}"
                                    width="80"
                                    height="80"
                                    loading="lazy"
                                    decoding="async">
                                <span class="nobifashion_home_category_text">{{ $category->name }}</span>
                            </a>
                        @endforeach
                    @endforeach
                </div>
                <button class="nobifashion_home_pill nobifashion_home_all_categories" type="button"
                    data-nobifashion-open="menu" aria-controls="nobifashion_home_menu" aria-expanded="false">Xem tất cả
                    danh mục sản phẩm</button>
            </div>
        </nav>

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
        <div class="nobifashion_home_spacer" aria-hidden="true"></div>
    </main>

@endsection
