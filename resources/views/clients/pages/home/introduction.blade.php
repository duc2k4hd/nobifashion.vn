@extends('clients.layouts.master')

@section('title', 'Giới Thiệu NOBI FASHION – Thời Trang Nam Hiện Đại, Chuẩn Phom Dáng Việt')

@section('head')
    <meta name="robots" content="index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
    <meta name="bingbot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
    <meta name="keywords" content="{{ $settings->seo_keywords ?? 'giới thiệu nobi fashion, thương hiệu nobi fashion, thời trang nam hải phòng, shop quần áo nam đẹp, nobi fashion việt nam' }}">
    <meta name="description" content="Khám phá câu chuyện thương hiệu {{ ($settings->site_name ?? null) ?: (($settings->subname ?? null) ?: 'NOBI FASHION') }} – Thời trang nam thiết kế trẻ trung, phom dáng chuẩn người Việt, chất liệu thoáng mát bền đẹp và dịch vụ tận tâm.">
    <meta name="author" content="{{ ($settings->seo_author ?? null) ?: (($settings->site_name ?? null) ?: 'NOBI FASHION') }}">
    <meta name="generator" content="NOBI FASHION Platform">
    <meta name="revisit-after" content="1 days">
    <meta name="rating" content="general">
    <link rel="canonical" href="{{ route('client.page.introduction') }}">
    <link rel="alternate" hreflang="vi" href="{{ route('client.page.introduction') }}">
    <link rel="alternate" hreflang="x-default" href="{{ route('client.page.introduction') }}">

    {{-- Geo Meta Tags (Local SEO Hải Phòng) --}}
    <meta name="geo.region" content="VN-HP">
    <meta name="geo.placename" content="Hải Phòng">
    <meta name="geo.position" content="20.82989;106.67608">
    <meta name="ICBM" content="20.82989, 106.67608">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ renderMeta(($settings->site_name ?? null) ?: (($settings->subname ?? null) ?: 'NOBI FASHION')) }}">
    <meta property="og:url" content="{{ route('client.page.introduction') }}">
    <meta property="og:title" content="Giới Thiệu NOBI FASHION – Thời Trang Nam Hiện Đại, Chuẩn Phom Dáng Việt">
    <meta property="og:description" content="Khám phá câu chuyện thương hiệu {{ ($settings->site_name ?? null) ?: (($settings->subname ?? null) ?: 'NOBI FASHION') }} – Thời trang nam thiết kế trẻ trung, phom dáng chuẩn người Việt, chất liệu thoáng mát bền đẹp và dịch vụ tận tâm.">
    <meta property="og:image" content="{{ asset('clients/assets/img/business/' . (($settings->site_banner ?? null) ?: (($settings->site_logo ?? null) ?: 'banner.webp'))) }}">
    <meta property="og:image:secure_url" content="{{ asset('clients/assets/img/business/' . (($settings->site_banner ?? null) ?: (($settings->site_logo ?? null) ?: 'banner.webp'))) }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Giới thiệu thương hiệu thời trang NOBI FASHION">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:locale" content="vi_VN">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@nobifashion">
    <meta name="twitter:creator" content="@nobifashion">
    <meta name="twitter:title" content="Giới Thiệu NOBI FASHION – Thời Trang Nam Hiện Đại, Chuẩn Phom Dáng Việt">
    <meta name="twitter:description" content="Khám phá câu chuyện thương hiệu {{ ($settings->site_name ?? null) ?: (($settings->subname ?? null) ?: 'NOBI FASHION') }} – Thời trang nam thiết kế trẻ trung, phom dáng chuẩn người Việt, chất liệu thoáng mát bền đẹp và dịch vụ tận tâm.">
    <meta name="twitter:image" content="{{ asset('clients/assets/img/business/' . (($settings->site_banner ?? null) ?: (($settings->site_logo ?? null) ?: 'banner.webp'))) }}">
    <meta name="twitter:image:alt" content="Giới thiệu thương hiệu thời trang NOBI FASHION">
@endsection

@section('schema')
    @php
        $siteUrl = config('app.url') ?? url('/');
        $logoUrl = asset('clients/assets/img/business/' . ($settings->site_logo ?? 'nobifashion-logo.png'));
        $bannerUrl = asset('clients/assets/img/business/' . ($settings->site_banner ?? 'banner.webp'));
        $socialLinks = array_values(array_filter([
            $settings->facebook_link ?? null,
            $settings->instagram_link ?? null,
            $settings->tiktok_link ?? null,
        ]));

        $schemaGraph = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => $siteUrl . '#website',
                    'url' => $siteUrl,
                    'name' => ($settings->site_name ?? null) ?: (($settings->subname ?? null) ?: 'NOBI FASHION'),
                    'description' => ($settings->site_description ?? null) ?: 'Thương hiệu thời trang nam hiện đại chuẩn phom dáng người Việt',
                    'publisher' => [
                        '@id' => $siteUrl . '#organization'
                    ],
                    'inLanguage' => 'vi-VN'
                ],
                [
                    '@type' => 'AboutPage',
                    '@id' => route('client.page.introduction') . '#webpage',
                    'url' => route('client.page.introduction'),
                    'name' => 'Giới Thiệu NOBI FASHION – Thời Trang Nam Hiện Đại, Chuẩn Phom Dáng Việt',
                    'description' => 'Khám phá câu chuyện thương hiệu NOBI FASHION – Thời trang nam thiết kế trẻ trung, phom dáng chuẩn người Việt, chất liệu thoáng mát bền đẹp và dịch vụ tận tâm.',
                    'isPartOf' => [
                        '@id' => $siteUrl . '#website'
                    ],
                    'breadcrumb' => [
                        '@id' => route('client.page.introduction') . '#breadcrumb'
                    ],
                    'about' => [
                        '@id' => $siteUrl . '#organization'
                    ],
                    'inLanguage' => 'vi-VN'
                ],
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => route('client.page.introduction') . '#breadcrumb',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Trang chủ',
                            'item' => route('client.home.index')
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'Giới thiệu',
                            'item' => route('client.page.introduction')
                        ]
                    ]
                ],
                [
                    '@type' => ['ClothingStore', 'Organization'],
                    '@id' => $siteUrl . '#organization',
                    'name' => ($settings->site_name ?? null) ?: (($settings->subname ?? null) ?: 'NOBI FASHION'),
                    'alternateName' => ($settings->subname ?? null) ?: 'NOBI FASHION',
                    'url' => $siteUrl,
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => $logoUrl,
                        'caption' => 'Logo NOBI FASHION'
                    ],
                    'image' => $bannerUrl,
                    'telephone' => ($settings->contact_phone ?? null) ?: 'Đang cập nhật...',
                    'email' => ($settings->contact_email ?? null) ?: 'Đang cập nhật...',
                    'taxID' => ($settings->site_tax_code ?? null) ?: 'Đang cập nhật...',
                    'priceRange' => '$$',
                    'currenciesAccepted' => 'VND',
                    'paymentAccepted' => 'Cash, Credit Card, COD, Banking',
                    'founder' => [
                        '@type' => 'Person',
                        'name' => 'Nguyễn Minh Đức (Đức Nobi)',
                        'jobTitle' => 'Founder',
                        'sameAs' => 'https://www.facebook.com/ducnobi2004'
                    ],
                    'address' => [
                        '@type' => 'PostalAddress',
                        'streetAddress' => ($settings->contact_address ?? null) ?: 'Đang cập nhật...',
                        'addressLocality' => ($settings->city ?? null) ?: 'Hải Phòng',
                        'addressRegion' => ($settings->city ?? null) ?: 'Hải Phòng',
                        'postalCode' => ($settings->postalCode ?? null) ?: '180000',
                        'addressCountry' => 'VN'
                    ],
                    'geo' => [
                        '@type' => 'GeoCoordinates',
                        'latitude' => (float) (($settings->latitude ?? null) ?: 20.82989),
                        'longitude' => (float) (($settings->longitude ?? null) ?: 106.67608)
                    ],
                    'openingHoursSpecification' => [
                        [
                            '@type' => 'OpeningHoursSpecification',
                            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                            'opens' => '08:00',
                            'closes' => '17:30'
                        ]
                    ],
                    'contactPoint' => [
                        [
                            '@type' => 'ContactPoint',
                            'telephone' => ($settings->contact_phone ?? null) ?: 'Đang cập nhật...',
                            'contactType' => 'customer service',
                            'areaServed' => 'VN',
                            'availableLanguage' => ['Vietnamese']
                        ]
                    ],
                    'sameAs' => $socialLinks
                ],
                [
                    '@type' => 'FAQPage',
                    '@id' => route('client.page.introduction') . '#faq',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => 'NOBI FASHION là thương hiệu thời trang định hướng như thế nào?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'NOBI FASHION là thương hiệu thời trang nam phong cách Smart-Casual hiện đại, tập trung vào các thiết kế tối giản, dễ phối đồ, chuẩn phom dáng người Việt và chất liệu thoáng mát bền đẹp.'
                            ]
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Mua sắm tại NOBI FASHION có được kiểm tra hàng trước khi nhận không?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Có. NOBI FASHION áp dụng chính sách đồng kiểm 100% cho mọi đơn hàng. Quý khách hoàn toàn được quyền mở hộp kiểm tra chất vải, đường may và mẫu mã trước khi thanh toán.'
                            ]
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Chính sách đổi size sản phẩm của NOBI FASHION ra sao?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'NOBI FASHION hỗ trợ đổi size thuận tiện trong 7 ngày nếu trang phục chưa vừa vặn với vóc dáng, đảm bảo khách hàng luôn có sản phẩm ưng ý nhất.'
                            ]
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Địa chỉ showroom trải nghiệm trực tiếp của NOBI FASHION ở đâu?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Quý khách có thể ghé thăm cửa hàng tại Ngõ 512 Thiên Lôi, Phường Vĩnh Niệm, Quận Lê Chân, Thành phố Hải Phòng để trực tiếp trải nghiệm và thử phom dáng.'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    @endphp
    <script type="application/ld+json">
        {!! json_encode($schemaGraph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endsection

@push('styles')
    <style>
        :root {
            --nb-bg: #f8fafc;
            --nb-card: #ffffff;
            --nb-text: #1e293b;
            --nb-muted: #64748b;
            --nb-border: #e2e8f0;
            --nb-dark: #0f172a;
            --nb-accent: #e11d48;
        }

        .intro-page {
            max-width: 1200px;
            margin: 20px auto 60px;
            padding: 0 16px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--nb-text);
        }

        /* Breadcrumb */
        .intro-breadcrumb {
            margin-bottom: 24px;
        }

        .intro-breadcrumb ol {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            list-style: none !important;
            padding: 0;
            margin: 0;
            font-size: 13px;
        }

        .intro-breadcrumb li {
            display: inline-flex !important;
            align-items: center;
            gap: 8px;
            color: var(--nb-muted);
        }

        .intro-breadcrumb a {
            color: var(--nb-muted);
            text-decoration: none !important;
            transition: color 0.2s ease;
        }

        .intro-breadcrumb a:hover {
            color: var(--nb-dark);
        }

        .intro-breadcrumb li.active {
            color: var(--nb-dark);
            font-weight: 600;
        }

        .intro-breadcrumb .separator {
            font-size: 10px;
            color: #cbd5e1;
        }

        /* Hero Banner */
        .intro-hero-clean {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 40px;
            align-items: center;
            background: var(--nb-card);
            border: 1px solid var(--nb-border);
            border-radius: 20px;
            padding: 44px;
            margin-bottom: 36px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
            position: relative;
            overflow: hidden;
        }

        .intro-hero-clean::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--nb-dark), var(--nb-accent), var(--nb-dark));
        }

        .intro-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--nb-dark);
            background: #f1f5f9;
            padding: 6px 14px;
            border-radius: 999px;
            margin-bottom: 16px;
            border: 1px solid var(--nb-border);
        }

        .intro-hero-title {
            font-size: clamp(26px, 3.5vw, 38px);
            font-weight: 800;
            color: var(--nb-dark);
            line-height: 1.25;
            margin: 0 0 16px;
            letter-spacing: -0.02em;
        }

        .intro-hero-lead {
            font-size: 15.5px;
            line-height: 1.75;
            color: var(--nb-muted);
            margin: 0 0 28px;
        }

        .intro-hero-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
        }

        .btn-intro-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--nb-dark);
            color: #ffffff !important;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none !important;
            transition: all 0.25s ease;
        }

        .btn-intro-primary:hover {
            background: #1e293b;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.15);
        }

        .btn-intro-outline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            color: var(--nb-dark) !important;
            border: 1px solid var(--nb-border);
            padding: 12px 22px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none !important;
            transition: all 0.25s ease;
        }

        .btn-intro-outline:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-2px);
        }

        .intro-hero-media {
            position: relative;
            display: flex;
            justify-content: center;
        }

        .intro-media-wrapper {
            position: relative;
            width: 100%;
            max-width: 440px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            border: 1px solid var(--nb-border);
            background: #f1f5f9;
        }

        .intro-media-wrapper img {
            width: 100%;
            height: 380px;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }

        .intro-media-wrapper:hover img {
            transform: scale(1.03);
        }

        .intro-media-badge {
            position: absolute;
            bottom: 16px;
            left: 16px;
            right: 16px;
            background: rgba(15, 23, 42, 0.88);
            backdrop-filter: blur(8px);
            color: #ffffff;
            padding: 14px 18px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .intro-media-badge span {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #fda4af;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .intro-media-badge strong {
            display: block;
            font-size: 13.5px;
            line-height: 1.4;
            color: #ffffff;
            font-weight: 600;
        }

        /* 4 Thẻ Cam Kết Thực Tế */
        .intro-pillars-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 48px;
        }

        .pillar-card {
            background: var(--nb-card);
            border: 1px solid var(--nb-border);
            border-radius: 14px;
            padding: 22px 20px;
            transition: all 0.25s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
        }

        .pillar-card:hover {
            border-color: #cbd5e1;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
        }

        .pillar-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--nb-dark);
            margin-bottom: 14px;
        }

        .pillar-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--nb-dark);
            margin: 0 0 8px;
        }

        .pillar-desc {
            font-size: 13.5px;
            line-height: 1.6;
            color: var(--nb-muted);
            margin: 0;
        }

        /* Section: Câu chuyện thương hiệu */
        .intro-story-section {
            background: var(--nb-card);
            border: 1px solid var(--nb-border);
            border-radius: 20px;
            padding: 44px;
            margin-bottom: 48px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
        }

        .story-header {
            max-width: 720px;
            margin-bottom: 32px;
        }

        .story-header h2 {
            font-size: clamp(22px, 3vw, 30px);
            font-weight: 800;
            color: var(--nb-dark);
            line-height: 1.3;
            margin: 8px 0 14px;
            letter-spacing: -0.01em;
        }

        .story-header p {
            font-size: 15px;
            line-height: 1.7;
            color: var(--nb-muted);
            margin: 0;
        }

        .story-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--nb-border);
        }

        .story-col-item h3 {
            font-size: 17px;
            font-weight: 700;
            color: var(--nb-dark);
            margin: 0 0 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .story-col-item p {
            font-size: 14px;
            line-height: 1.7;
            color: var(--nb-muted);
            margin: 0;
        }

        /* Section: 3 Trụ Cột Giá Trị Cốt Lõi */
        .intro-values-section {
            margin-bottom: 48px;
        }

        .section-header-center {
            text-align: center;
            max-width: 680px;
            margin: 0 auto 36px;
        }

        .section-header-center h2 {
            font-size: clamp(22px, 3vw, 30px);
            font-weight: 800;
            color: var(--nb-dark);
            margin: 8px 0 12px;
            letter-spacing: -0.01em;
        }

        .section-header-center p {
            font-size: 14.5px;
            line-height: 1.65;
            color: var(--nb-muted);
            margin: 0;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .value-card {
            background: var(--nb-card);
            border: 1px solid var(--nb-border);
            border-radius: 16px;
            padding: 30px 24px;
            transition: all 0.25s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
            position: relative;
        }

        .value-card:hover {
            border-color: #cbd5e1;
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .value-number {
            font-size: 12px;
            font-weight: 800;
            color: var(--nb-accent);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 12px;
        }

        .value-card h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--nb-dark);
            margin: 0 0 12px;
        }

        .value-card p {
            font-size: 14px;
            line-height: 1.7;
            color: var(--nb-muted);
            margin: 0;
        }

        /* Section: 4 Bước Kiểm Soát Chất Lượng */
        .intro-process-section {
            background: var(--nb-card);
            border: 1px solid var(--nb-border);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 48px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
        }

        .process-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 32px;
            position: relative;
        }

        .process-step {
            position: relative;
            background: #f8fafc;
            border: 1px solid var(--nb-border);
            border-radius: 14px;
            padding: 24px 20px;
            transition: all 0.2s ease;
        }

        .process-step:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.04);
        }

        .step-index {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--nb-dark);
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
        }

        .process-step h4 {
            font-size: 15px;
            font-weight: 700;
            color: var(--nb-dark);
            margin: 0 0 8px;
        }

        .process-step p {
            font-size: 13px;
            line-height: 1.6;
            color: var(--nb-muted);
            margin: 0;
        }

        /* Section: Showroom & Map */
        .intro-showroom-section {
            display: grid;
            grid-template-columns: 0.95fr 1.05fr;
            gap: 24px;
            margin-bottom: 48px;
        }

        .showroom-info-box {
            background: var(--nb-card);
            border: 1px solid var(--nb-border);
            border-radius: 18px;
            padding: 32px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .showroom-header h3 {
            font-size: 22px;
            font-weight: 800;
            color: var(--nb-dark);
            margin: 8px 0 10px;
        }

        .showroom-header p {
            font-size: 14px;
            line-height: 1.6;
            color: var(--nb-muted);
            margin: 0 0 20px;
        }

        .showroom-details {
            list-style: none;
            padding: 0;
            margin: 0 0 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .showroom-details li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 14px;
            line-height: 1.6;
        }

        .detail-label {
            font-weight: 600;
            color: var(--nb-dark);
            min-width: 90px;
            flex-shrink: 0;
        }

        .detail-value {
            color: var(--nb-muted);
        }

        .detail-value a {
            color: var(--nb-dark);
            text-decoration: none;
            font-weight: 600;
        }

        .detail-value a:hover {
            color: var(--nb-accent);
        }

        .showroom-map-box {
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid var(--nb-border);
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
            min-height: 360px;
            background: #e2e8f0;
        }

        .showroom-map-box iframe {
            width: 100%;
            height: 100%;
            min-height: 360px;
            border: 0;
            display: block;
        }

        /* Section: CTA Banner */
        .intro-cta-banner {
            background: var(--nb-dark);
            border-radius: 20px;
            padding: 44px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 28px;
            color: #ffffff;
            margin-bottom: 48px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.15);
        }

        .cta-content h3 {
            font-size: clamp(20px, 2.5vw, 28px);
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 8px;
            line-height: 1.3;
        }

        .cta-content p {
            font-size: 14.5px;
            line-height: 1.6;
            color: #94a3b8;
            margin: 0;
        }

        .cta-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .btn-cta-white {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
            color: var(--nb-dark) !important;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none !important;
            transition: all 0.25s ease;
        }

        .btn-cta-white:hover {
            background: #f1f5f9;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 255, 255, 0.2);
        }

        .btn-cta-transparent {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 12px 22px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none !important;
            transition: all 0.25s ease;
        }

        .btn-cta-transparent:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Responsive Breakpoints */
        @media (max-width: 992px) {
            .intro-hero-clean {
                grid-template-columns: 1fr;
                padding: 32px 24px;
            }

            .intro-media-wrapper {
                max-width: 100%;
            }

            .intro-media-wrapper img {
                height: 320px;
            }

            .story-columns {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .values-grid {
                grid-template-columns: 1fr;
            }

            .process-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .intro-showroom-section {
                grid-template-columns: 1fr;
            }

            .intro-cta-banner {
                flex-direction: column;
                align-items: flex-start;
                padding: 32px 24px;
            }

            .cta-actions {
                width: 100%;
                flex-direction: column;
            }

            .btn-cta-white,
            .btn-cta-transparent {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 640px) {
            .intro-hero-actions {
                flex-direction: column;
                width: 100%;
            }

            .btn-intro-primary,
            .btn-intro-outline {
                width: 100%;
                justify-content: center;
            }

            .intro-pillars-grid {
                grid-template-columns: 1fr;
            }

            .process-grid {
                grid-template-columns: 1fr;
            }

            .intro-story-section,
            .intro-process-section,
            .showroom-info-box {
                padding: 24px 20px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="intro-page">

        {{-- Breadcrumb Điều Hướng --}}
        <nav aria-label="breadcrumb" class="intro-breadcrumb">
            <ol>
                <li>
                    <a href="{{ route('client.home.index') }}">Trang chủ</a>
                    <span class="separator">/</span>
                </li>
                <li class="active" aria-current="page">Giới thiệu</li>
            </ol>
        </nav>

        {{-- 1. Hero Section: Định Vị Thương Hiệu --}}
        <section class="intro-hero-clean">
            <div class="intro-hero-content">
                <span class="intro-eyebrow">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    Câu Chuyện Thương Hiệu
                </span>
                <h1 class="intro-hero-title">
                    Thời Trang Nam Hiện Đại & Lịch Lãm Cho Phái Mạnh Việt
                </h1>
                <p class="intro-hero-lead">
                    Khởi nguồn từ thành phố cảng Hải Phòng, <strong>{{ ($settings->site_name ?? null) ?: (($settings->subname ?? null) ?: 'NOBI FASHION') }}</strong> ra đời với sứ mệnh mang đến những thiết kế nam tối giản, chỉn chu và tôn vinh vóc dáng. Chúng tôi tin rằng trang phục đẹp không chỉ nằm ở vẻ bề ngoài, mà còn đến từ sự vừa vặn, chất liệu thoải mái và niềm tự tin bạn mang theo mỗi ngày.
                </p>
                <div class="intro-hero-actions">
                    <a href="{{ route('client.product.shop.index') }}" class="btn-intro-primary">
                        <span>Khám Phá Bộ Sưu Tập</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                    <a href="{{ route('client.page.contact') }}" class="btn-intro-outline">
                        <span>Liên Hệ Tư Vấn Size</span>
                    </a>
                </div>
            </div>

            <div class="intro-hero-media">
                <div class="intro-media-wrapper">
                    <img src="{{ asset('clients/assets/img/banners/' . (($settings->site_banner ?? null) ?: 'phoi-do-mua-dong-1.webp')) }}" alt="Bộ sưu tập thời trang NOBI FASHION" loading="lazy">
                    <div class="intro-media-badge">
                        <span>Thiết Kế Tinh Gọn</span>
                        <strong>Tỉ mỉ từ đường kim, chỉn chu trong từng phom dáng</strong>
                    </div>
                </div>
            </div>
        </section>

        {{-- 2. Bốn Điểm Nhấn Cam Kết Thực Tế --}}
        <section class="intro-pillars-grid">
            <div class="pillar-card">
                <div class="pillar-icon-box">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.38 3.46L16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"></path></svg>
                </div>
                <h3 class="pillar-title">Phom Dáng Chuẩn Việt</h3>
                <p class="pillar-desc">Nghiên cứu kỹ lưỡng số đo và thể trạng nam giới Việt để tạo nên những trang phục vừa vặn, tôn dáng, che khuyết điểm hiệu quả.</p>
            </div>

            <div class="pillar-card">
                <div class="pillar-icon-box">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m4.93 4.93 4.24 4.24"></path><path d="m14.83 9.17 4.24-4.24"></path><path d="m14.83 14.83 4.24 4.24"></path><path d="m9.17 14.83-4.24 4.24"></path><circle cx="12" cy="12" r="4"></circle></svg>
                </div>
                <h3 class="pillar-title">Chất Liệu Tuyển Chọn</h3>
                <p class="pillar-desc">Ưu tiên các dòng vải tự nhiên Cotton Compact, Spandex co giãn 4 chiều, sợi Bamboo thấm hút tốt, bền màu và thoáng mát suốt ngày dài.</p>
            </div>

            <div class="pillar-card">
                <div class="pillar-icon-box">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                </div>
                <h3 class="pillar-title">Đồng Kiểm Khi Nhận</h3>
                <p class="pillar-desc">Khách hàng hoàn toàn được quyền mở hộp kiểm tra kỹ đường may, chất vải và đúng mẫu mã trước khi thanh toán nhận hàng.</p>
            </div>

            <div class="pillar-card">
                <div class="pillar-icon-box">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 2v6h6"></path><path d="M21.5 22v-6h-6"></path><path d="M22 11.5A10 10 0 0 0 3.2 7.2L2.5 8"></path><path d="M2 12.5a10 10 0 0 0 18.8 4.2l.7-.7"></path></svg>
                </div>
                <h3 class="pillar-title">Hỗ Trợ Đổi Size Linh Hoạt</h3>
                <p class="pillar-desc">Nếu chưa vừa vặn với vóc dáng, đội ngũ hỗ trợ nhanh chóng đổi size thuận tiện trong 7 ngày, đảm bảo bạn luôn có bộ đồ ưng ý nhất.</p>
            </div>
        </section>

        {{-- 3. Câu Chuyện & Triết Lý Sản Phẩm --}}
        <section class="intro-story-section">
            <div class="story-header">
                <span class="intro-eyebrow">Hành Trình Kiến Tạo</span>
                <h2>Từ Trăn Trở Đến Từng Thiết Kế Đời Thường</h2>
                <p>
                    Thị trường thời trang nam hiện đại không thiếu những mẫu mã hào nhoáng, nhưng tìm được một thương hiệu thực sự thấu hiểu phom người Việt, may kỹ càng và có mức giá hợp lý lại không hề dễ dàng. Đó chính là lý do NOBI FASHION ra đời.
                </p>
            </div>

            <div class="story-columns">
                <div class="story-col-item">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                        Tối Giản, Dễ Phối & Bền Bỉ
                    </h3>
                    <p>
                        Chúng tôi tập trung vào phong cách Smart-Casual và Daily Wear: các mẫu áo thun cổ tròn, áo polo thanh lịch, sơ mi dạo phố công sở và quần dài vừa vặn. Màu sắc trung tính, thanh thoát giúp bạn dễ dàng kết hợp tủ đồ mỗi sáng mà không mất quá nhiều thời gian suy nghĩ.
                    </p>
                </div>

                <div class="story-col-item">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        Minh Bạch & Tôn Trọng Khách Hàng
                    </h3>
                    <p>
                        Hình ảnh sản phẩm trên website và các kênh bán hàng đều được chụp thực tế với độ chân thật cao nhất. Chúng tôi cam kết mô tả đúng chất liệu, thông số kích thước chi tiết và tư vấn tận tâm để mỗi lần nhận hàng là một trải nghiệm trọn vẹn.
                    </p>
                </div>
            </div>
        </section>

        {{-- 4. Ba Trụ Cột Giá Trị Cốt Lõi --}}
        <section class="intro-values-section">
            <div class="section-header-center">
                <span class="intro-eyebrow">Kim Chỉ Nam Hoạt Động</span>
                <h2>Ba Giá Trị Cốt Lõi Làm Nên NOBI FASHION</h2>
                <p>Những nguyên tắc vững chắc dẫn dắt mọi quyết định từ khâu chọn cuộn vải, hoàn thiện đường may đến dịch vụ khách hàng.</p>
            </div>

            <div class="values-grid">
                <div class="value-card">
                    <span class="value-number">Giá Trị 01</span>
                    <h3>Chất Lượng Vượt Trội</h3>
                    <p>Không thỏa hiệp với những chất liệu rẻ tiền nhanh bai nhão. Từng thước vải đều được kiểm tra độ co giãn, độ bền màu sau giặt và độ mềm mại trên làn da.</p>
                </div>

                <div class="value-card">
                    <span class="value-number">Giá Trị 02</span>
                    <h3>Vừa Vặn Tôn Dáng</h3>
                    <p>Rập thiết kế được nghiên cứu đo đạc theo số đo thực tế của nam giới Á Đông: độ rộng vai vừa tầm, độ dài áo chuẩn mực, vòng nách êm ái tạo sự tự tin tuyệt đối.</p>
                </div>

                <div class="value-card">
                    <span class="value-number">Giá Trị 03</span>
                    <h3>Tận Tâm Phục Vụ</h3>
                    <p>Bán hàng không dừng lại ở lúc chốt đơn. Chúng tôi luôn đồng hành lắng nghe phản hồi, hỗ trợ đổi size và giải quyết mọi băn khoăn của khách hàng chu đáo nhất.</p>
                </div>
            </div>
        </section>

        {{-- 5. Quy Trình Kiểm Soát Sản Phẩm Tinh Gọn --}}
        <section class="intro-process-section">
            <div class="story-header" style="margin-bottom: 0;">
                <span class="intro-eyebrow">Quy Trình Chuẩn Mực</span>
                <h2>4 Bước Kiểm Soát Từng Mẫu Trang Phục</h2>
                <p>Để một sản phẩm đến tay bạn trong tình trạng hoàn hảo nhất, đội ngũ NOBI FASHION tuân thủ quy trình kiểm soát nghiêm ngặt.</p>
            </div>

            <div class="process-grid">
                <div class="process-step">
                    <div class="step-index">01</div>
                    <h4>Chọn Lọc Chất Vải</h4>
                    <p>Thử nghiệm độ co giãn, bề mặt mềm mịn và khả năng thấm hút mồ hôi trước khi đưa vào sản xuất số lượng lớn.</p>
                </div>

                <div class="process-step">
                    <div class="step-index">02</div>
                    <h4>May Mẫu & Căn Phom</h4>
                    <p>Mặc thử mẫu thực tế trên người thật ở nhiều dáng người khác nhau để tinh chỉnh độ cử động thoải mái nhất.</p>
                </div>

                <div class="process-step">
                    <div class="step-index">03</div>
                    <h4>Kiểm Tra KCS Chi Tiết</h4>
                    <p>Soi xét từng đường kim mũi chỉ, cúc áo, khóa kéo và cắt tỉa chỉ thừa trước khi cho vào túi bảo quản.</p>
                </div>

                <div class="process-step">
                    <div class="step-index">04</div>
                    <h4>Đóng Gói & Giao Nhanh</h4>
                    <p>Đóng gói cẩn thận, bảo quản trang phục phẳng phiu và chuyển phát an toàn đến tận tay khách hàng trên toàn quốc.</p>
                </div>
            </div>
        </section>

        {{-- 6. Showroom Thực Tế & Bản Đồ Vị Trí --}}
        <section class="intro-showroom-section">
            <div class="showroom-info-box">
                <div class="showroom-header">
                    <span class="intro-eyebrow">Địa Chỉ & Trụ Sở</span>
                    <h3>Trải Nghiệm Trực Tiếp Tại Showroom</h3>
                    <p>Quý khách có thể ghé thăm cửa hàng để trực tiếp cảm nhận chất vải, ướm thử phom dáng và nhận sự tư vấn chu đáo từ đội ngũ của chúng tôi.</p>
                </div>

                <ul class="showroom-details">
                    <li>
                        <span class="detail-label">Cửa hàng:</span>
                        <span class="detail-value"><strong>{{ ($settings->site_name ?? null) ?: (($settings->subname ?? null) ?: 'NOBI FASHION') }}</strong></span>
                    </li>
                    <li>
                        <span class="detail-label">Địa chỉ:</span>
                        <span class="detail-value">{{ ($settings->contact_address ?? null) ?: 'Đang cập nhật...' }}</span>
                    </li>
                    <li>
                        <span class="detail-label">Hotline:</span>
                        <span class="detail-value">
                            @if(!empty($settings->contact_phone))
                                <a href="tel:{{ $settings->contact_phone }}">{{ $settings->contact_phone }}</a>
                            @else
                                Đang cập nhật...
                            @endif
                        </span>
                    </li>
                    <li>
                        <span class="detail-label">Email:</span>
                        <span class="detail-value">
                            @if(!empty($settings->contact_email))
                                <a href="mailto:{{ $settings->contact_email }}">{{ $settings->contact_email }}</a>
                            @else
                                Đang cập nhật...
                            @endif
                        </span>
                    </li>
                    <li>
                        <span class="detail-label">Giờ mở cửa:</span>
                        <span class="detail-value">{{ ($settings->business_hours ?? null) ?: 'Đang cập nhật...' }}</span>
                    </li>
                </ul>

                <div>
                    @if(!empty($settings->contact_address))
                        <a href="https://maps.google.com/?q={{ urlencode($settings->contact_address) }}" target="_blank" rel="noopener noreferrer" class="btn-intro-outline" style="width: 100%; justify-content: center;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg>
                            <span>Mở Chỉ Đường Trên Google Maps</span>
                        </a>
                    @endif
                </div>
            </div>

            <div class="showroom-map-box">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4010.6654359851395!2d106.67608087555321!3d20.829894880770134!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x314a707e662ec17b%3A0x6a8ab8c6fc7ccd75!2zTmfDtSA1MTIgRHVvbmcgVGhpw6puIEzDtGksIEFuIEJpw6puLCBI4bqjaSBQaMOybmcsIFZp4buHdCBOYW0!5e1!3m2!1svi!2s!4v1790407588827!5m2!1svi!2s"
                    loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade" title="Bản đồ chỉ dẫn địa chỉ NOBI FASHION Hải Phòng"></iframe>
            </div>
        </section>

        {{-- 7. Kêu Gọi Hành Động (CTA Banner) --}}
        <section class="intro-cta-banner">
            <div class="cta-content">
                <h3>Sẵn Sàng Nâng Tầm Tủ Đồ Của Bạn?</h3>
                <p>Khám phá ngay các bộ sưu tập mới nhất với ưu đãi hấp dẫn và chính sách đổi trả thuận tiện.</p>
            </div>
            <div class="cta-actions">
                <a href="{{ route('client.product.shop.index') }}" class="btn-cta-white">
                    <span>Xem Tất Cả Sản Phẩm</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
                <a href="{{ route('client.page.contact') }}" class="btn-cta-transparent">
                    <span>Trung Tâm Hỗ Trợ</span>
                </a>
            </div>
        </section>

        {{-- 8. Gợi Ý Sản Phẩm Mới --}}
        @if (isset($productNew) && count($productNew) > 0)
            <div class="intro-products" style="margin-top: 40px;">
                @include('clients.templates.product_new')
            </div>
        @endif

    </div>
@endsection
