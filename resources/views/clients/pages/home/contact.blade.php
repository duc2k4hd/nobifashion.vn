@extends('clients.layouts.master')

@section('title', 'Liên Hệ NOBI FASHION – Tư Vấn Size & Chăm Sóc Khách Hàng Tận Tâm')

@section('head')
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="keywords" content="{{ $settings->seo_keywords ?? 'liên hệ NOBI FASHION, chăm sóc khách hàng nobi fashion, shop thời trang Hải Phòng, hotline nobi fashion, tư vấn size nobi' }}">
    <meta name="description" content="Trung tâm chăm sóc khách hàng {{ ($settings->site_name ?? null) ?: (($settings->subname ?? null) ?: 'NOBI FASHION') }}. Kết nối trực tiếp để được tư vấn chọn size chuẩn dáng, hỗ trợ đơn hàng và đổi trả nhanh chóng. Hotline: {{ ($settings->contact_phone ?? null) ?: 'Đang cập nhật...' }}.">
    <link rel="canonical" href="{{ route('client.page.contact') }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="Liên Hệ NOBI FASHION – Tư Vấn Size & Chăm Sóc Khách Hàng Tận Tâm">
    <meta property="og:description" content="Trung tâm chăm sóc khách hàng {{ ($settings->site_name ?? null) ?: (($settings->subname ?? null) ?: 'NOBI FASHION') }}. Kết nối trực tiếp để được tư vấn chọn size chuẩn dáng, hỗ trợ đơn hàng và đổi trả nhanh chóng.">
    <meta property="og:url" content="{{ route('client.page.contact') }}">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ asset('clients/assets/img/business/' . (($settings->site_banner ?? null) ?: (($settings->site_logo ?? null) ?: 'banner.webp'))) }}">
    <meta property="og:site_name" content="{{ renderMeta(($settings->site_name ?? null) ?: (($settings->subname ?? null) ?: 'Đang cập nhật...')) }}">
    <meta property="og:locale" content="vi_VN">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Liên Hệ NOBI FASHION – Tư Vấn Size & Chăm Sóc Khách Hàng Tận Tâm">
    <meta name="twitter:description" content="Trung tâm chăm sóc khách hàng {{ ($settings->site_name ?? null) ?: (($settings->subname ?? null) ?: 'NOBI FASHION') }}. Kết nối trực tiếp để được tư vấn chọn size chuẩn dáng, hỗ trợ đơn hàng và đổi trả nhanh chóng.">
    <meta name="twitter:image" content="{{ asset('clients/assets/img/business/' . (($settings->site_banner ?? null) ?: (($settings->site_logo ?? null) ?: 'banner.webp'))) }}">
@endsection

@section('schema')
    @php
        $siteUrl = config('app.url') ?? url('/');
        $logoUrl = asset('clients/assets/img/business/' . ($settings->site_logo ?? 'nobifashion-logo.png'));
        $socialLinks = array_values(array_filter([
            $settings->facebook_link ?? null,
            $settings->instagram_link ?? null,
            $settings->tiktok_link ?? null,
        ]));

        $schemaContactPage = [
            '@context' => 'https://schema.org',
            '@type' => 'ContactPage',
            '@id' => route('client.page.contact') . '#webpage',
            'url' => route('client.page.contact'),
            'name' => 'Liên Hệ NOBI FASHION – Tư Vấn Size & Chăm Sóc Khách Hàng Tận Tâm',
            'description' => 'Trung tâm chăm sóc khách hàng NOBI FASHION. Tư vấn chọn size, chính sách mua sắm và hỗ trợ đơn hàng.',
            'inLanguage' => 'vi-VN',
            'breadcrumb' => [
                '@type' => 'BreadcrumbList',
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
                        'name' => 'Liên hệ',
                        'item' => route('client.page.contact')
                    ]
                ]
            ],
            'mainEntity' => [
                '@type' => ['ClothingStore', 'Organization'],
                '@id' => $siteUrl . '#organization',
                'name' => ($settings->site_name ?? null) ?: (($settings->subname ?? null) ?: 'Đang cập nhật...'),
                'alternateName' => ($settings->subname ?? null) ?: (($settings->site_name ?? null) ?: 'Đang cập nhật...'),
                'url' => $siteUrl,
                'logo' => $logoUrl,
                'image' => asset('clients/assets/img/business/' . ($settings->site_banner ?? 'banner.webp')),
                'telephone' => ($settings->contact_phone ?? null) ?: 'Đang cập nhật...',
                'email' => ($settings->contact_email ?? null) ?: 'Đang cập nhật...',
                'taxID' => ($settings->site_tax_code ?? null) ?: 'Đang cập nhật...',
                'priceRange' => '$$',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => ($settings->contact_address ?? null) ?: 'Đang cập nhật...',
                    'addressLocality' => ($settings->city ?? null) ?: 'Đang cập nhật...',
                    'addressRegion' => ($settings->city ?? null) ?: 'Đang cập nhật...',
                    'postalCode' => ($settings->postalCode ?? null) ?: 'Đang cập nhật...',
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
                        'opens' => '08:30',
                        'closes' => '21:30'
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
            ]
        ];
    @endphp
    <script type="application/ld+json">
        {!! json_encode($schemaContactPage, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endsection

@push('styles')
    <style>
        :root {
            --ct-bg-page: #f8fafc;
            --ct-card-bg: #ffffff;
            --ct-text-main: #1e293b;
            --ct-text-muted: #64748b;
            --ct-border: #e2e8f0;
            --ct-dark: #0f172a;
            --ct-accent: #e11d48;
        }

        .contact-page {
            max-width: 1200px;
            margin: 20px auto 60px;
            padding: 0 16px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--ct-text-main);
        }

        /* Breadcrumb */
        .contact-breadcrumb {
            margin-bottom: 24px;
        }

        .contact-breadcrumb ol {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            list-style: none !important;
            padding: 0;
            margin: 0;
            font-size: 13px;
        }

        .contact-breadcrumb li {
            display: inline-flex !important;
            align-items: center;
            gap: 8px;
            color: var(--ct-text-muted);
        }

        .contact-breadcrumb a {
            color: var(--ct-text-muted);
            text-decoration: none !important;
            transition: color 0.2s ease;
        }

        .contact-breadcrumb a:hover {
            color: var(--ct-dark);
        }

        .contact-breadcrumb li.active {
            color: var(--ct-dark);
            font-weight: 600;
        }

        .contact-breadcrumb .separator {
            font-size: 10px;
            color: #cbd5e1;
        }

        /* Header Hero */
        .contact-header {
            text-align: center;
            max-width: 760px;
            margin: 0 auto 36px;
        }

        .contact-eyebrow {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--ct-dark);
            background: #f1f5f9;
            padding: 6px 14px;
            border-radius: 999px;
            margin-bottom: 12px;
            border: 1px solid var(--ct-border);
        }

        .contact-title {
            font-size: clamp(26px, 4vw, 36px);
            font-weight: 800;
            color: var(--ct-dark);
            margin: 0 0 14px;
            letter-spacing: -0.02em;
            line-height: 1.3;
        }

        .contact-desc {
            font-size: 15.5px;
            line-height: 1.7;
            color: var(--ct-text-muted);
            margin: 0;
        }

        /* Quick Contact Cards */
        .quick-contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 36px;
        }

        .quick-card {
            background: var(--ct-card-bg);
            border: 1px solid var(--ct-border);
            border-radius: 14px;
            padding: 22px 20px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            text-decoration: none !important;
            color: inherit !important;
            transition: all 0.25s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
        }

        .quick-card:hover {
            border-color: #cbd5e1;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        }

        .quick-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ct-dark);
            flex-shrink: 0;
        }

        .quick-info span {
            display: block;
            font-size: 12.5px;
            color: var(--ct-text-muted);
            margin-bottom: 4px;
            font-weight: 500;
        }

        .quick-info strong {
            display: block;
            font-size: 15px;
            font-weight: 700;
            color: var(--ct-dark);
            line-height: 1.4;
        }

        .quick-info small {
            display: block;
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }

        /* Main Section: 2 Columns */
        .contact-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 5fr) minmax(0, 7fr);
            gap: 32px;
            margin-bottom: 48px;
        }

        /* Left Side: Business Info & Commitments */
        .contact-info-panel {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .info-box {
            background: var(--ct-card-bg);
            border: 1px solid var(--ct-border);
            border-radius: 16px;
            padding: 26px 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }

        .info-box-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--ct-dark);
            margin: 0 0 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-detail-list {
            list-style: none !important;
            padding: 0;
            margin: 0;
        }

        .info-detail-list li {
            display: flex !important;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px dashed #f1f5f9;
            font-size: 14.5px;
            line-height: 1.6;
        }

        .info-detail-list li:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-detail-list li:first-child {
            padding-top: 0;
        }

        .info-label {
            font-weight: 600;
            color: #334155;
            min-width: 110px;
            flex-shrink: 0;
        }

        .info-text {
            color: #475569;
        }

        .info-text a {
            color: var(--ct-dark);
            text-decoration: underline !important;
            font-weight: 600;
        }

        /* Commitments Grid */
        .commitments-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .commitment-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
        }

        .commitment-card strong {
            display: block;
            font-size: 13.5px;
            font-weight: 700;
            color: var(--ct-dark);
            margin-bottom: 4px;
        }

        .commitment-card p {
            font-size: 12px;
            color: var(--ct-text-muted);
            line-height: 1.5;
            margin: 0;
        }

        /* Social Channels */
        .social-channel-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 6px;
        }

        .social-channel-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--ct-dark) !important;
            background: #f1f5f9;
            border: 1px solid var(--ct-border);
            text-decoration: none !important;
            transition: all 0.2s;
        }

        .social-channel-btn:hover {
            background: #e2e8f0;
            border-color: #cbd5e1;
        }

        /* Right Side: Contact Form */
        .contact-form-panel {
            background: var(--ct-card-bg);
            border: 1px solid var(--ct-border);
            border-radius: 20px;
            padding: 34px 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        }

        .form-header-box {
            margin-bottom: 24px;
        }

        .form-header-box h2 {
            font-size: 22px;
            font-weight: 800;
            color: var(--ct-dark);
            margin: 0 0 8px;
        }

        .form-header-box p {
            font-size: 14.5px;
            color: var(--ct-text-muted);
            margin: 0;
            line-height: 1.6;
        }

        .contact-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 13.5px;
            font-weight: 600;
            color: #334155;
        }

        .form-group label .required {
            color: var(--ct-accent);
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 11px 14px;
            font-size: 14.5px;
            font-family: inherit;
            color: #1e293b;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: var(--ct-dark);
            box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        .file-upload-box {
            position: relative;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 13px;
            color: var(--ct-text-muted);
        }

        .file-upload-box input[type="file"] {
            font-size: 13px;
            color: #475569;
        }

        .form-alert {
            padding: 14px 16px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 16px;
        }

        .submit-btn {
            background: var(--ct-dark);
            color: #ffffff;
            font-size: 15px;
            font-weight: 600;
            padding: 14px 28px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
        }

        .submit-btn:hover {
            background: #1e293b;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.15);
        }

        .submit-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
        }

        /* Map Section */
        .contact-map-section {
            background: var(--ct-card-bg);
            border: 1px solid var(--ct-border);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            margin-bottom: 48px;
        }

        .map-header {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .map-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--ct-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .map-header p {
            font-size: 14px;
            color: var(--ct-text-muted);
            margin: 0;
        }

        .map-container {
            width: 100%;
            height: 400px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--ct-border);
            background: #e2e8f0;
        }

        .map-container iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }

        @media (max-width: 991px) {
            .contact-main-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .contact-form-panel {
                padding: 26px 20px;
            }

            .commitments-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .contact-map-section {
                padding: 20px 16px;
            }

            .map-container {
                height: 300px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="contact-page">

        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="contact-breadcrumb">
            <ol>
                <li>
                    <a href="{{ route('client.home.index') }}">Trang chủ</a>
                    <span class="separator">/</span>
                </li>
                <li class="active" aria-current="page">Liên hệ</li>
            </ol>
        </nav>

        {{-- Header Hero --}}
        <header class="contact-header">
            <span class="contact-eyebrow">Dịch Vụ Khách Hàng • NOBI FASHION</span>
            <h1 class="contact-title">Liên hệ & Hỗ trợ</h1>
            <p class="contact-desc">
                Đội ngũ NOBI FASHION luôn sẵn lòng đồng hành cùng bạn. Dù là tư vấn lựa chọn size số, giải đáp thắc mắc về đơn hàng hay trao đổi hợp tác kinh doanh, chúng tôi sẽ phản hồi nhanh chóng và tận tâm nhất.
            </p>
        </header>

        {{-- 4 Thẻ Liên Hệ Nhanh --}}
        <div class="quick-contact-grid">
            <a href="{{ !empty($settings->contact_phone) ? 'tel:' . $settings->contact_phone : 'javascript:void(0);' }}" class="quick-card">
                <div class="quick-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                </div>
                <div class="quick-info">
                    <span>Hotline tư vấn</span>
                    <strong>{{ ($settings->contact_phone ?? null) ?: 'Đang cập nhật...' }}</strong>
                    <small>{{ ($settings->business_hours ?? null) ?: 'Đang cập nhật...' }}</small>
                </div>
            </a>

            @php
                $zaloNum = ($settings->contact_zalo ?? null) ?: ($settings->contact_phone ?? null);
            @endphp
            <a href="{{ !empty($zaloNum) ? 'https://zalo.me/' . preg_replace('/[^0-9]/', '', $zaloNum) : 'javascript:void(0);' }}" target="_blank" rel="noopener noreferrer" class="quick-card">
                <div class="quick-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                </div>
                <div class="quick-info">
                    <span>Chat Zalo</span>
                    <strong>{{ $zaloNum ?: 'Đang cập nhật...' }}</strong>
                    <small>Hỗ trợ trực tiếp online</small>
                </div>
            </a>

            <a href="{{ !empty($settings->contact_email) ? 'mailto:' . $settings->contact_email : 'javascript:void(0);' }}" class="quick-card">
                <div class="quick-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                </div>
                <div class="quick-info">
                    <span>Email hỗ trợ</span>
                    <strong>{{ ($settings->contact_email ?? null) ?: 'Đang cập nhật...' }}</strong>
                    <small>Phản hồi trong 24 giờ</small>
                </div>
            </a>

            <div class="quick-card">
                <div class="quick-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                </div>
                <div class="quick-info">
                    <span>Địa chỉ cửa hàng</span>
                    <strong>{{ ($settings->city ?? null) ?: (($settings->district ?? null) ?: 'Đang cập nhật...') }}</strong>
                    <small>{{ ($settings->contact_address ?? null) ?: 'Đang cập nhật...' }}</small>
                </div>
            </div>
        </div>

        {{-- Main Section: 2 Columns --}}
        <div class="contact-main-grid">

            {{-- Cột Trái: Thông tin chính thức & Cam kết dịch vụ --}}
            <div class="contact-info-panel">

                {{-- Box Thông tin doanh nghiệp --}}
                <div class="info-box">
                    <h3 class="info-box-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                        Thông Tin Doanh Nghiệp
                    </h3>

                    <ul class="info-detail-list">
                        <li>
                            <span class="info-label">Đơn vị:</span>
                            <span class="info-text"><strong>{{ ($settings->site_name ?? null) ?: (($settings->subname ?? null) ?: 'Đang cập nhật...') }}</strong></span>
                        </li>
                        <li>
                            <span class="info-label">Mã số thuế:</span>
                            <span class="info-text">{{ ($settings->site_tax_code ?? null) ?: 'Đang cập nhật...' }}</span>
                        </li>
                        <li>
                            <span class="info-label">Trụ sở chính:</span>
                            <span class="info-text">{{ ($settings->contact_address ?? null) ?: 'Đang cập nhật...' }}</span>
                        </li>
                        <li>
                            <span class="info-label">Hotline:</span>
                            <span class="info-text">
                                @if(!empty($settings->contact_phone))
                                    <a href="tel:{{ $settings->contact_phone }}">{{ $settings->contact_phone }}</a>
                                @else
                                    Đang cập nhật...
                                @endif
                            </span>
                        </li>
                        <li>
                            <span class="info-label">Email CSKH:</span>
                            <span class="info-text">
                                @if(!empty($settings->contact_email))
                                    <a href="mailto:{{ $settings->contact_email }}">{{ $settings->contact_email }}</a>
                                @else
                                    Đang cập nhật...
                                @endif
                            </span>
                        </li>
                        <li>
                            <span class="info-label">Giờ phục vụ:</span>
                            <span class="info-text">{{ ($settings->business_hours ?? null) ?: 'Đang cập nhật...' }}</span>
                        </li>
                    </ul>
                </div>

                {{-- Box Cam kết dịch vụ thực tế --}}
                <div class="info-box">
                    <h3 class="info-box-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        Cam Kết Dịch Vụ
                    </h3>

                    <div class="commitments-grid">
                        <div class="commitment-card">
                            <strong>👕 Tư vấn size chuẩn</strong>
                            <p>Tư vấn số đo, form dáng chuẩn xác trước khi chốt đơn.</p>
                        </div>
                        <div class="commitment-card">
                            <strong>📦 Đồng kiểm khi nhận</strong>
                            <p>Kiểm tra chất lượng sản phẩm thực tế trước khi thanh toán.</p>
                        </div>
                        <div class="commitment-card">
                            <strong>🔄 Đổi size thuận tiện</strong>
                            <p>Hỗ trợ đổi size linh hoạt nếu chưa vừa vặn với vóc dáng.</p>
                        </div>
                        <div class="commitment-card">
                            <strong>🤝 May đo & Đơn sỉ</strong>
                            <p>Ưu đãi may đo đồng phục và chiết khấu hấp dẫn cho đối tác.</p>
                        </div>
                    </div>
                </div>

                {{-- Kênh kết nối mạng xã hội --}}
                <div class="info-box">
                    <h3 class="info-box-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                        Kênh Kết Nối Chính Thức
                    </h3>

                    <div class="social-channel-group">
                        @if(!empty($settings->facebook_link))
                            <a href="{{ $settings->facebook_link }}" target="_blank" rel="noopener noreferrer" class="social-channel-btn">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                                Facebook
                            </a>
                        @endif

                        @if(!empty($zaloNum))
                            <a href="https://zalo.me/{{ preg_replace('/[^0-9]/', '', $zaloNum) }}" target="_blank" rel="noopener noreferrer" class="social-channel-btn">
                                Zalo Official
                            </a>
                        @endif

                        @if(!empty($settings->instagram_link))
                            <a href="{{ $settings->instagram_link }}" target="_blank" rel="noopener noreferrer" class="social-channel-btn">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                                Instagram
                            </a>
                        @endif

                        @if(!empty($settings->tiktok_link))
                            <a href="{{ $settings->tiktok_link }}" target="_blank" rel="noopener noreferrer" class="social-channel-btn">
                                TikTok
                            </a>
                        @endif
                    </div>
                </div>

            </div>

            {{-- Cột Phải: Form Gửi Thông Điệp --}}
            <div class="contact-form-panel">
                <div class="form-header-box">
                    <h2>Gửi Thông Điệp Cho Chúng Tôi</h2>
                    <p>Hãy chia sẻ nhu cầu, thắc mắc hoặc yêu cầu hỗ trợ của bạn. Đội ngũ CSKH Nobi Fashion sẽ phản hồi trong thời gian sớm nhất.</p>
                </div>

                <form id="contact-form" action="{{ route('client.contact.store') }}" method="POST" enctype="multipart/form-data" class="contact-form">
                    @csrf

                    <div id="contact-form-message" class="form-alert" style="display:none;"></div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Họ và tên <span class="required">*</span></label>
                            <input type="text" id="name" name="name" class="form-input" placeholder="Ví dụ: Nguyễn Văn A" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Số điện thoại <span class="required">*</span></label>
                            <input type="tel" id="phone" name="phone" class="form-input" placeholder="Ví dụ: 0987 654 321" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Địa chỉ Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" class="form-input" placeholder="email@example.com" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Chủ đề cần hỗ trợ <span class="required">*</span></label>
                            <input type="text" id="subject" name="subject" class="form-input" placeholder="Tư vấn size, đơn hàng, bảo hành..." required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="message">Nội dung chi tiết <span class="required">*</span></label>
                        <textarea id="message" name="message" class="form-textarea" placeholder="Vui lòng mô tả chi tiết yêu cầu của bạn (mã đơn hàng, số đo chiều cao cân nặng, hoặc nội dung cần giải đáp)..." minlength="10" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="attachment">Tệp đính kèm (Ảnh sản phẩm / hóa đơn nếu có)</label>
                        <div class="file-upload-box">
                            <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx">
                            <div style="font-size: 11.5px; color: #94a3b8; margin-top: 4px;">Hỗ trợ ảnh định dạng JPG, PNG, WEBP, PDF (Tối đa 10MB)</div>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn" id="contact-submit-btn">
                        <span id="contact-submit-text">Gửi Yêu Cầu Liên Hệ</span>
                        <span id="contact-submit-loading" style="display:none;">Đang gửi thông tin...</span>
                    </button>
                </form>
            </div>

        </div>

        {{-- Khu Vực Bản Đồ Google Maps --}}
        <section class="contact-map-section">
            <div class="map-header">
                <div>
                    <h3>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        Vị Trí Cửa Hàng & Trụ Sở {{ ($settings->site_name ?? null) ?: (($settings->subname ?? null) ?: 'Đang cập nhật...') }}
                    </h3>
                    <p>{{ ($settings->contact_address ?? null) ?: 'Đang cập nhật...' }}</p>
                </div>
                @if(!empty($settings->contact_address))
                    <a href="https://maps.google.com/?q={{ urlencode($settings->contact_address) }}" target="_blank" rel="noopener noreferrer" class="social-channel-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg>
                        Mở trong Google Maps
                    </a>
                @endif
            </div>

            <div class="map-container">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4010.6654359851395!2d106.67608087555321!3d20.829894880770134!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x314a707e662ec17b%3A0x6a8ab8c6fc7ccd75!2zTmfDtSA1MTIgRHVvbmcgVGhpw6puIEzDtGksIEFuIEJpw6puLCBI4bqjaSBQaMOybmcsIFZp4buHdCBOYW0!5e1!3m2!1svi!2s!4v1790407588827!5m2!1svi!2s"
                    allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Bản đồ chỉ dẫn địa chỉ NOBI FASHION"></iframe>
            </div>
        </section>

        {{-- Gợi Ý Sản Phẩm Mới --}}
        @if (isset($productNew) && count($productNew) > 0)
            <div class="contact-product" style="margin-top: 40px;">
                @include('clients.templates.product_new')
            </div>
        @endif

    </div>

    {{-- Script AJAX xử lý Contact Form an toàn & mượt mà --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const contactForm = document.getElementById('contact-form');
            if (contactForm) {
                contactForm.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    const submitBtn = document.getElementById('contact-submit-btn');
                    const submitText = document.getElementById('contact-submit-text');
                    const submitLoading = document.getElementById('contact-submit-loading');
                    const messageDiv = document.getElementById('contact-form-message');

                    // Disable button & show spinner
                    submitBtn.disabled = true;
                    submitText.style.display = 'none';
                    submitLoading.style.display = 'inline';
                    messageDiv.style.display = 'none';

                    const formData = new FormData(contactForm);

                    try {
                        const response = await fetch(contactForm.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            }
                        });

                        const data = await response.json();
                        messageDiv.style.display = 'block';

                        if (data.success) {
                            messageDiv.style.background = '#ecfdf5';
                            messageDiv.style.color = '#065f46';
                            messageDiv.style.border = '1px solid #a7f3d0';
                            messageDiv.innerHTML = '<strong>✓ Gửi thành công!</strong> ' + (data.message || 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong thời gian sớm nhất.');
                            contactForm.reset();
                        } else {
                            messageDiv.style.background = '#fef2f2';
                            messageDiv.style.color = '#991b1b';
                            messageDiv.style.border = '1px solid #fecaca';

                            let errorMessage = data.message || 'Có lỗi xảy ra. Vui lòng kiểm tra lại thông tin.';
                            if (data.errors) {
                                const errorList = Object.values(data.errors).flat().join('<br>');
                                errorMessage = '<strong>✗ Vui lòng kiểm tra các mục sau:</strong><br>' + errorList;
                            }
                            messageDiv.innerHTML = errorMessage;
                        }
                    } catch (error) {
                        messageDiv.style.display = 'block';
                        messageDiv.style.background = '#fef2f2';
                        messageDiv.style.color = '#991b1b';
                        messageDiv.style.border = '1px solid #fecaca';
                        messageDiv.textContent = 'Không thể gửi biểu mẫu vào lúc này. Vui lòng liên hệ trực tiếp hotline ' + '{{ ($settings->contact_phone ?? null) ?: "Đang cập nhật..." }}' + '.';
                        console.error('Contact form error:', error);
                    } finally {
                        submitBtn.disabled = false;
                        submitText.style.display = 'inline';
                        submitLoading.style.display = 'none';
                        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
            }
        });
    </script>
@endsection
