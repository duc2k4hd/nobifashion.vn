@extends('clients.layouts.master')

@section('title', 'Giới thiệu thương hiệu NOBI FASHION - Thời trang Việt Nam | ' . renderMeta($settings->site_name ??
    $settings->subname ?? 'NOBI FASHION VIỆT NAM'))

@section('head')
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large"/>

    <meta name="keywords"
        content="{{ $settings->seo_keywords ?? 'NOBI FASHION, quần áo, phụ kiện, thời trang nam, thời trang nữ, áo phông, sơ mi, quần jean, váy, mũ nón, thắt lưng' }}">

    <meta name="description"
        content="{{ renderMeta($settings->site_description) ?? 'Khám phá NOBI FASHION – thương hiệu thời trang nam hiện đại tại Việt Nam. Mang đến phong cách năng động, lịch lãm với áo polo, sơ mi, quần kaki, áo thun và nhiều sản phẩm chất lượng.' }}">

    <meta http-equiv="date" content="{{ \Carbon\Carbon::parse('2025-06-11 13:10:59')->format('d/m/y') }}" />

    {{-- ✅ Open Graph (Facebook, Zalo, v.v.) --}}
    <meta property="og:title" content="{{ renderMeta('NOBI FASHION - Thời trang nam Việt Nam hiện đại') }}">
    <meta property="og:description" content="{{ renderMeta('NOBI FASHION – Thương hiệu thời trang nam trẻ trung tại Việt Nam. Mang đến phong cách năng động, lịch lãm và tự tin cho phái mạnh Việt.') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('clients/assets/img/business/' . ($settings->site_banner ?: ($settings->site_logo ?? 'logo-nobi-fashion.png'))) }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ renderMeta('NOBI FASHION - Thời trang nam Việt Nam') }}">
    <meta property="og:image:type" content="image/webp">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ renderMeta('NOBI FASHION') }}">
    <meta property="og:locale" content="vi_VN">

    {{-- ✅ Twitter Card (X / Twitter) --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@{{ Str::slug('NOBI FASHION', '') }}">
    <meta name="twitter:title" content="{{ renderMeta('NOBI FASHION - Thời trang nam Việt Nam hiện đại') }}">
    <meta name="twitter:description" content="{{ renderMeta('Thời trang nam cao cấp, form chuẩn Việt, dễ phối đồ. Giao hàng toàn quốc, đổi trả 7 ngày, dịch vụ tận tâm.') }}">
    <meta name="twitter:image" content="{{ asset('clients/assets/img/business/' . ($settings->site_banner ?: ($settings->site_logo ?? 'logo-nobi-fashion.png'))) }}">
    <meta name="twitter:creator" content="{{ renderMeta('NOBI FASHION') }}">

    {{-- ✅ Canonical & Hreflang --}}
    <link rel="canonical" href="{{ route('client.page.introduction') }}">
    <link rel="alternate" hreflang="vi" href="{{ route('client.page.introduction') }}">
    <link rel="alternate" hreflang="x-default" href="{{ route('client.page.introduction') }}">
@endsection

@section('content')
    <section class="intro-hero">
        <div class="intro-hero__content">
            <p class="eyebrow">NOBI FASHION HERITAGE</p>
            <h1>Tinh hoa thời trang Việt, nâng tầm trải nghiệm hiện đại</h1>
            <p>
                Khởi nguồn từ Hải Phòng, NOBI FASHION định hình phong cách smart-casual sang trọng với chất liệu cao cấp,
                form dáng tinh chỉnh cho người Việt và dịch vụ cá nhân hoá chuẩn boutique.
            </p>
            <div class="hero-actions">
                <a class="btn primary" href="{{ route('client.product.shop.index') ?? '#' }}">Khám phá BST mới</a>
                <a class="btn ghost" href="{{ route('client.page.contact') ?? '#' }}">Đặt lịch stylist riêng</a>
            </div>
            <div class="hero-stats">
                <div>
                    <span>Khách hàng thân thiết</span>
                    <strong>120.000+</strong>
                </div>
                <div>
                    <span>Showroom toàn quốc</span>
                    <strong>12</strong>
                </div>
                <div>
                    <span>Đơn vị đồng hành doanh nghiệp</span>
                    <strong>80+</strong>
                </div>
            </div>
        </div>
        <div class="intro-hero__media">
            <img src="{{ asset('clients/assets/img/banners/thuong-hieu-NOBI-FASHION-VIET-NAM.jpg') }}" alt="Studio NOBI FASHION" loading="lazy">
            <div class="badge-card">
                <p>SIGNATURE ATELIER</p>
                <h4>Form chuẩn Việt, hoàn thiện bằng tay</h4>
                <span>12 bước kiểm định – bảo hành phom trọn đời</span>
            </div>
        </div>
    </section>

    <section class="intro-panels">
        <article class="panel highlight">
            <p class="eyebrow">Sứ mệnh</p>
            <h3>Trao quyền tự tin cho phái mạnh Việt</h3>
            <p>Tạo nên trang phục dễ ứng dụng, chỉn chu trong mọi bối cảnh nhưng vẫn giữ cá tính riêng của chủ nhân.</p>
        </article>
        <article class="panel">
            <p class="eyebrow">Tầm nhìn</p>
            <h3>Thương hiệu thời trang nam dẫn đầu khu vực</h3>
            <p>Ứng dụng công nghệ đo phom 3D, chất liệu bền vững và dịch vụ concierge 24/7.</p>
        </article>
        <article class="panel">
            <p class="eyebrow">Giá trị cốt lõi</p>
            <ul>
                <li>Chân thành & tận tâm với từng khách hàng.</li>
                <li>Tiên phong xu hướng, tối ưu trải nghiệm.</li>
                <li>Phát triển bền vững, trách nhiệm với môi trường.</li>
            </ul>
        </article>
    </section>

    <section class="intro-journey">
        <div class="journey-content">
            <h2>Hành trình nâng tầm phong cách</h2>
            <div class="timeline">
                <div class="timeline-item">
                    <span class="year">2018</span>
                    <p>Mở atelier đầu tiên tại Hải Phòng, phục vụ 300 khách hàng thân thiết.</p>
                </div>
                <div class="timeline-item">
                    <span class="year">2020</span>
                    <p>Ra mắt thương mại điện tử với công nghệ AI Fit và dịch vụ tư vấn cá nhân.</p>
                </div>
                <div class="timeline-item">
                    <span class="year">2023</span>
                    <p>Phủ sóng 12 showroom, triển khai dịch vụ chỉnh sửa miễn phí 60 ngày.</p>
                </div>
                <div class="timeline-item">
                    <span class="year">2025</span>
                    <p>Đối tác trang phục của các tập đoàn, nghệ sĩ, KOLs hàng đầu Việt Nam.</p>
                </div>
            </div>
        </div>
        <div class="journey-media">
            <img src="{{ asset('clients/assets/img/banners/thuong-hieu-NOBI-FASHION-VIET-NAM.jpg') }}" alt="Hành trình studio NOBI" loading="lazy">
            <div class="media-caption">
                <strong>Craftsmanship</strong>
                <span>Đội ngũ pattern-maker nội bộ, hoàn thiện thủ công từng chi tiết.</span>
            </div>
        </div>
    </section>

    <section class="intro-grid">
        <article>
            <h3>Thiết kế tinh gọn</h3>
            <p>Đường cắt sắc nét, gam màu trung tính, dễ phối đồ nhưng vẫn nổi bật bản sắc.</p>
        </article>
        <article>
            <h3>Chất liệu độc quyền</h3>
            <p>Supima cotton, bamboo lạnh, denim co giãn 4 chiều đạt chuẩn OEKO-TEX.</p>
        </article>
        <article>
            <h3>Dịch vụ bespoke</h3>
            <p>Chỉnh phom miễn phí, concierge tại nhà, bảo hành suit signature trọn đời.</p>
        </article>
        <article>
            <h3>Trải nghiệm 360°</h3>
            <p>Stylist cá nhân, khu lounge đón tiếp, hệ thống CRM lưu giữ sở thích khách hàng.</p>
        </article>
    </section>

    <section class="intro-network">
        <div class="network-card">
            <p class="eyebrow">Hệ sinh thái NOBI</p>
            <h2>Showroom & dịch vụ cao cấp</h2>
            <ul>
                <li>Không gian boutique tại Hà Nội, Hải Phòng, Đà Nẵng, TP.HCM.</li>
                <li>Khu thử đồ riêng tư, stylist kèm 1:1 theo lịch hẹn.</li>
                <li>Quầy cà phê và lounge dành cho khách VIP.</li>
            </ul>
        </div>
        <div class="network-card gradient">
            <h3>Kết nối đa kênh</h3>
            <div class="channel">
                <span>Website</span>
                <a href="{{ $settings->site_url ?? '#' }}" target="_blank">{{ $settings->site_name ?? 'nobifashion.vn' }}</a>
            </div>
            <div class="channel">
                <span>Marketplace</span>
                <p>Shopee Mall • Lazada Flagship • TikTok Shop</p>
            </div>
            <div class="channel">
                <span>Giải pháp doanh nghiệp</span>
                <p>Đồng phục cao cấp, quà tặng đối tác, cá nhân hoá nhận diện thương hiệu.</p>
            </div>
        </div>
    </section>

    <section class="intro-map">
        <div class="map-info">
            <p class="eyebrow">Flagship Studio</p>
            <h2>595/1 Thiên Lôi, Hải Phòng</h2>
            <p>Đặt lịch trước để được chuẩn bị phòng thử riêng, valet parking và dịch vụ chăm sóc trang phục cao cấp.</p>
            <div class="contact">
                <span>Hotline</span>
                <a href="tel:{{ $settings->contact_phone ?? '' }}">{{ $settings->contact_phone ?? '' }}</a>
            </div>
            <div class="contact">
                <span>Email</span>
                <a href="mailto:{{ $settings->contact_email ?? '' }}">{{ $settings->contact_email ?? '' }}</a>
            </div>
        </div>
        <div class="map-frame">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4010.734289061049!2d106.68005187555318!3d20.82730938077215!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x314a707bf0c0c6b3%3A0x270e1b278f753cae!2zNTk1LzEgUC4gVGhpw6puIEzDtGksIFThu5UgRHAgU-G7kSAzMCwgTMOqIENow6JuLCBI4bqjaSBQaMOybmcsIFZp4buHdCBOYW0!5e1!3m2!1svi!2s!4v1762164701486!5m2!1svi!2s"
                loading="lazy" allowfullscreen></iframe>
        </div>
    </section>

    <section class="intro-cta">
        <div class="cta-card">
            <div>
                <p class="eyebrow">Kết nối cùng NOBI</p>
                <h3>Nhận lookbook mới, tham dự sự kiện private và ưu đãi thành viên</h3>
            </div>
            <a class="btn secondary" href="{{ route('client.page.contact') ?? '#' }}">Đăng ký ngay</a>
        </div>
    </section>

    <div class="intro-products">
        @include('clients.templates.product_new')
    </div>

    <style>
        :root {
            --intro-dark: #05070f;
            --intro-accent: #00c2b2;
            --intro-border: rgba(15, 23, 42, 0.08);
        }

        .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.25em;
            font-size: 11px;
            color: var(--intro-accent);
            margin-bottom: 14px;
        }

        .intro-hero {
            width: 92%;
            margin: 40px auto;
            padding: 48px;
            background: radial-gradient(circle at top right, rgba(0, 194, 178, 0.35), transparent 45%), linear-gradient(135deg, #ff3366, #a14f64 60%, #7d656b);
            border-radius: 36px;
            color: #f8ffff;
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
            gap: 32px;
        }

        .intro-hero__content h1 {
            font-size: clamp(36px, 4vw, 56px);
            margin-bottom: 18px;
            line-height: 1.15;
        }

        .intro-hero__content p {
            color: rgba(255, 255, 255, 0.78);
            line-height: 1.7;
        }

        .hero-actions {
            margin: 28px 0 22px;
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .btn {
            border-radius: 999px;
            padding: 14px 26px;
            font-weight: 600;
            border: 1px solid transparent;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s ease;
        }

        .btn.primary {
            background: linear-gradient(90deg, #00d5c0, #00a19b);
            color: #00151d;
        }

        .btn.ghost {
            border-color: rgba(255, 255, 255, 0.35);
            color: #f8ffff;
        }

        .btn.secondary {
            background: #fff;
            color: #061424;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 30px rgba(0, 0, 0, 0.25);
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
        }

        .hero-stats div {
            padding: 16px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(255, 255, 255, 0.05);
        }

        .hero-stats span {
            font-size: 13px;
            opacity: 0.7;
        }

        .hero-stats strong {
            display: block;
            margin-top: 8px;
            font-size: 26px;
        }

        .intro-hero__media {
            position: relative;
        }

        .intro-hero__media img {
            width: 100%;
            height: 100%;
            max-height: 420px;
            border-radius: 30px;
            object-fit: cover;
        }

        .badge-card {
            position: absolute;
            bottom: 20px;
            right: 20px;
            padding: 18px 22px;
            border-radius: 20px;
            backdrop-filter: blur(12px);
            background: rgba(5, 9, 18, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 80%;
        }

        .badge-card p {
            font-size: 12px;
            letter-spacing: 0.25em;
            color: var(--intro-accent);
            margin-bottom: 6px;
        }

        .badge-card h4 {
            margin-bottom: 4px;
        }

        .intro-panels,
        .intro-grid,
        .intro-network,
        .intro-map,
        .intro-products {
            width: 92%;
            margin: 35px auto;
        }

        .intro-panels {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
        }

        .panel {
            padding: 28px;
            border-radius: 24px;
            border: 1px solid var(--intro-border);
            background: #fff;
            box-shadow: 0 18px 40px rgba(5, 10, 20, 0.08);
        }

        .panel.highlight {
            background: linear-gradient(135deg, #06152a, #0c223b);
            color: #e7f6ff;
            border-color: rgba(255, 255, 255, 0.12);
        }

        .panel ul {
            padding-left: 18px;
            line-height: 1.7;
            color: #4d5566;
        }

        .panel.highlight ul {
            color: #cfe9ff;
        }

        .intro-journey {
            width: 92%;
            margin: 40px auto;
            border-radius: 34px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr);
            overflow: hidden;
            background: #fff;
            box-shadow: 0 30px 60px rgba(5, 10, 20, 0.15);
        }

        .journey-content {
            padding: 42px;
        }

        .timeline {
            margin-top: 28px;
            border-left: 2px solid rgba(5, 10, 20, 0.08);
            padding-left: 26px;
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .timeline-item {
            position: relative;
        }

        .timeline-item::before {
            content: "";
            position: absolute;
            left: -34px;
            top: 6px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00d5c0, #009ea2);
            box-shadow: 0 0 0 6px rgba(0, 165, 155, 0.18);
        }

        .timeline-item .year {
            font-weight: 700;
            color: #0d1b2a;
        }

        .journey-media {
            position: relative;
        }

        .journey-media img {
            width: 100%;
            height: 100%;
            min-height: 320px;
            object-fit: cover;
            border-radius: 0;
        }

        .media-caption {
            position: absolute;
            bottom: 20px;
            left: 20px;
            border-radius: 18px;
            padding: 18px 22px;
            background: rgba(4, 7, 16, 0.75);
            color: #f7feff;
            max-width: 70%;
            backdrop-filter: blur(12px);
        }

        .intro-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        }

        .intro-grid article {
            padding: 24px;
            border-radius: 22px;
            border: 1px dashed rgba(0, 194, 178, 0.4);
            background: rgba(0, 194, 178, 0.04);
        }

        .intro-network {
            display: grid;
            grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
            gap: 20px;
        }

        .network-card {
            border-radius: 28px;
            padding: 32px;
            background: #fff;
            border: 1px solid var(--intro-border);
            box-shadow: 0 25px 55px rgba(5, 10, 20, 0.1);
        }

        .network-card.gradient {
            background: linear-gradient(140deg, #06152a, #0f2440);
            color: #dff8ff;
            border-color: rgba(255, 255, 255, 0.1);
        }

        .network-card ul {
            margin-top: 18px;
            padding-left: 18px;
            line-height: 1.7;
        }

        .channel {
            margin-top: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .channel:last-child {
            border-bottom: 0;
        }

        .channel span {
            font-size: 13px;
            opacity: 0.7;
        }

        .channel a {
            color: inherit;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            margin-top: 6px;
        }

        .intro-map {
            display: grid;
            grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
            gap: 24px;
        }

        .map-info {
            background: #050f1f;
            color: #dff8ff;
            border-radius: 28px;
            padding: 32px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .map-info a {
            color: #79f7ff;
            text-decoration: none;
            font-weight: 600;
        }

        .map-frame {
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 25px 55px rgba(5, 10, 20, 0.18);
        }

        .map-frame iframe {
            width: 100%;
            height: 100%;
            min-height: 320px;
            border: 0;
        }

        .intro-cta {
            width: 92%;
            margin: 35px auto 20px;
        }

        .cta-card {
            border-radius: 30px;
            padding: 34px;
            background: linear-gradient(120deg, #07112a, #1b3461);
            color: #f3fbff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
        }

        .intro-products {
            width: 92%;
            margin: 20px auto 60px;
        }

        @media (max-width: 1100px) {
            .intro-hero,
            .intro-journey,
            .intro-network,
            .intro-map {
                grid-template-columns: 1fr;
            }

            .hero-actions {
                flex-direction: column;
            }

            .journey-media img {
                min-height: 260px;
            }
        }

        @media (max-width: 768px) {
            .intro-hero,
            .intro-panels,
            .intro-grid,
            .intro-network,
            .intro-map,
            .intro-products,
            .intro-cta {
                width: 95%;
            }

            .intro-hero {
                padding: 32px 22px;
            }

            .badge-card {
                position: relative;
                inset: auto;
                margin-top: 16px;
                max-width: 100%;
            }

            .panel,
            .network-card,
            .cta-card,
            .map-info,
            .intro-grid article {
                padding: 24px;
            }

            .cta-card {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endsection
