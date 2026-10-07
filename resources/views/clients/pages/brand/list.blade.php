@extends('clients.layouts.master')

@section('title', 'Top Thương Hiệu Thời Trang Chính Hãng | ' . ($settings->site_name ?? 'NOBIFASHION'))

@section('head')
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="description" content="Khám phá bảng xếp hạng và danh bạ các thương hiệu thời trang chính hãng, local brand Việt Nam uy tín hàng đầu tại {{ $settings->site_name ?? 'NOBIFASHION' }}." />
    <meta name="keywords" content="thương hiệu thời trang, local brand việt nam, nobifashion, yody, thời trang nam chính hãng, shop thời trang" />
    <link rel="canonical" href="{{ route('client.brand.list') }}" />
    
    {{-- Open Graph --}}
    <meta property="og:locale" content="vi_VN" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="Top Thương Hiệu Thời Trang Chính Hãng | {{ $settings->site_name ?? 'NOBIFASHION' }}" />
    <meta property="og:description" content="Khám phá bảng xếp hạng và danh bạ các thương hiệu thời trang chính hãng, local brand Việt Nam uy tín tại {{ $settings->site_name ?? 'NOBIFASHION' }}." />
    <meta property="og:url" content="{{ route('client.brand.list') }}" />
    <meta property="og:site_name" content="{{ $settings->site_name ?? 'NOBIFASHION' }}" />

    <style>
        /* ========================================================
           PAGE SCOPED STYLES: BRANDS DIRECTORY (NOBIFASHION)
           ======================================================== */
        :root {
            --brand-primary: #2563eb;
            --brand-primary-dark: #1d4ed8;
            --brand-dark: #0f172a;
            --brand-text: #334155;
            --brand-muted: #64748b;
            --brand-border: #e2e8f0;
            --brand-card-bg: #ffffff;
            --brand-soft-bg: #f8fafc;
            --brand-gold: #f59e0b;
        }

        .nobi-brands-wrapper {
            background-color: #f8fafc;
            min-height: 100vh;
            padding-bottom: 60px;
        }

        /* 1. Breadcrumbs */
        .nobi-brands-breadcrumb {
            max-width: 1240px;
            margin: 0 auto;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            color: var(--brand-muted);
        }
        .nobi-brands-breadcrumb a {
            color: var(--brand-muted);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .nobi-brands-breadcrumb a:hover {
            color: var(--brand-primary);
        }
        .nobi-brands-breadcrumb span.current {
            color: var(--brand-dark);
            font-weight: 600;
        }

        /* 2. Hero Section */
        .nobi-brands-hero {
            max-width: 1240px;
            margin: 0 auto 32px;
            padding: 0 20px;
        }
        .nobi-brands-hero-inner {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #1e3a8a 100%);
            border-radius: 24px;
            padding: 44px 36px;
            color: #ffffff;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.25);
            position: relative;
            overflow: hidden;
        }
        .nobi-brands-hero-inner::after {
            content: '';
            position: absolute;
            right: -40px;
            top: -40px;
            width: 280px;
            height: 280px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .nobi-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #93c5fd;
            margin-bottom: 16px;
        }
        .nobi-hero-title {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 12px;
            color: #ffffff;
            letter-spacing: -0.02em;
        }
        .nobi-hero-desc {
            font-size: 15px;
            line-height: 1.6;
            color: #cbd5e1;
            max-width: 680px;
            margin-bottom: 28px;
        }
        .nobi-hero-stats {
            display: flex;
            align-items: center;
            gap: 32px;
            flex-wrap: wrap;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
        }
        .nobi-hero-stat-item {
            display: flex;
            flex-direction: column;
        }
        .nobi-hero-stat-num {
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .nobi-hero-stat-num .star {
            color: #fbbf24;
            font-size: 18px;
        }
        .nobi-hero-stat-label {
            font-size: 12.5px;
            color: #94a3b8;
            font-weight: 500;
        }

        /* 3. Filter Bar & Alphabet Index */
        .nobi-filter-section {
            max-width: 1240px;
            margin: 0 auto 32px;
            padding: 0 20px;
        }
        .nobi-filter-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 20px 24px;
            border: 1px solid var(--brand-border);
            box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.04);
        }
        .nobi-filter-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .nobi-search-input-box {
            position: relative;
            flex: 1;
            min-width: 280px;
            max-width: 480px;
        }
        .nobi-search-input-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 15px;
        }
        .nobi-search-input {
            width: 100%;
            padding: 11px 16px 11px 40px;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            font-size: 14px;
            color: var(--brand-dark);
            outline: none;
            transition: all 0.2s ease;
        }
        .nobi-search-input:focus {
            background: #ffffff;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        .nobi-tabs-group {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 12px;
        }
        .nobi-tab-btn {
            border: none;
            background: transparent;
            padding: 8px 16px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .nobi-tab-btn.active {
            background: #ffffff;
            color: var(--brand-dark);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        /* Alphabet Bar */
        .nobi-alphabet-bar {
            display: flex;
            align-items: center;
            gap: 6px;
            overflow-x: auto;
            padding-top: 10px;
            border-top: 1px dashed #e2e8f0;
            scrollbar-width: none;
        }
        .nobi-alphabet-bar::-webkit-scrollbar {
            display: none;
        }
        .nobi-letter-btn {
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #475569;
            font-size: 12.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            flex-shrink: 0;
        }
        .nobi-letter-btn:hover {
            border-color: var(--brand-primary);
            color: var(--brand-primary);
            background: #eff6ff;
        }
        .nobi-letter-btn.active {
            background: var(--brand-primary);
            color: #ffffff;
            border-color: var(--brand-primary);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }
        .nobi-letter-btn.disabled {
            opacity: 0.35;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* 4. Top Brands Showcase (Bảng xếp hạng) */
        .nobi-section-container {
            max-width: 1240px;
            margin: 0 auto 40px;
            padding: 0 20px;
        }
        .nobi-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .nobi-section-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--brand-dark);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        .nobi-section-title i {
            color: var(--brand-gold);
        }
        .nobi-section-subtitle {
            font-size: 13px;
            color: var(--brand-muted);
            margin-top: 3px;
        }

        .nobi-top-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .nobi-top-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid var(--brand-border);
            padding: 24px 20px;
            position: relative;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 16px -4px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }
        .nobi-top-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 30px -8px rgba(0, 0, 0, 0.1);
            border-color: #cbd5e1;
        }
        .nobi-rank-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 4px;
            letter-spacing: 0.02em;
        }
        .nobi-rank-1 {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .nobi-rank-2 {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .nobi-rank-3 {
            background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%);
            color: #9a3412;
            border: 1px solid #fdba74;
        }
        .nobi-rank-other {
            background: #f8fafc;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .nobi-top-logo-wrapper {
            margin: 20px auto 14px;
            width: 84px;
            height: 84px;
            border-radius: 50%;
            background: #ffffff;
            border: 2px solid #f1f5f9;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .nobi-top-card:hover .nobi-top-logo-wrapper {
            transform: scale(1.08);
            border-color: var(--brand-primary);
        }
        .nobi-top-logo-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .nobi-top-logo-placeholder {
            font-size: 20px;
            font-weight: 800;
            color: var(--brand-primary);
        }
        .nobi-top-info {
            text-align: center;
            margin-bottom: 16px;
        }
        .nobi-top-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--brand-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-bottom: 4px;
        }
        .nobi-verified-icon {
            color: #2563eb;
            font-size: 13px;
        }
        .nobi-top-campaign {
            font-size: 12px;
            color: #2563eb;
            font-weight: 600;
            background: #eff6ff;
            display: inline-block;
            padding: 2px 8px;
            border-radius: 6px;
            margin-bottom: 6px;
        }
        .nobi-top-metrics {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            padding: 12px 0;
            border-top: 1px dashed #e2e8f0;
            border-bottom: 1px dashed #e2e8f0;
            margin-bottom: 16px;
        }
        .nobi-metric-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .nobi-metric-val {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--brand-dark);
        }
        .nobi-metric-lbl {
            font-size: 11px;
            color: var(--brand-muted);
        }
        .nobi-top-btn {
            margin-top: auto;
            width: 100%;
            padding: 9px 0;
            border-radius: 10px;
            background: #f8fafc;
            color: var(--brand-primary);
            font-size: 13px;
            font-weight: 600;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .nobi-top-card:hover .nobi-top-btn {
            background: var(--brand-primary);
            color: #ffffff;
            border-color: var(--brand-primary);
        }

        /* 5. All Brands Directory Grid */
        .nobi-all-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }
        .nobi-brand-item-card {
            background: #ffffff;
            border: 1px solid var(--brand-border);
            border-radius: 16px;
            padding: 20px;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            transition: all 0.25s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
            position: relative;
        }
        .nobi-brand-item-card:hover {
            transform: translateY(-4px);
            border-color: #94a3b8;
            box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.08);
        }
        .nobi-item-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 12px;
        }
        .nobi-item-logo {
            width: 58px;
            height: 58px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }
        .nobi-item-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .nobi-item-title-group {
            flex: 1;
            min-width: 0;
        }
        .nobi-item-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--brand-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .nobi-item-meta {
            font-size: 12px;
            color: var(--brand-muted);
            margin-top: 2px;
        }
        .nobi-item-desc {
            font-size: 12.5px;
            color: #64748b;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 14px;
            flex: 1;
        }
        .nobi-item-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 12px;
            border-top: 1px solid #f1f5f9;
            font-size: 12px;
        }
        .nobi-item-badge-count {
            background: #f1f5f9;
            color: #334155;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 600;
        }
        .nobi-item-rating {
            color: #d97706;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 3px;
        }

        /* Empty State */
        .nobi-empty-brands {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: #ffffff;
            border-radius: 16px;
            border: 1px dashed #cbd5e1;
        }
        .nobi-empty-brands i {
            font-size: 40px;
            color: #cbd5e1;
            margin-bottom: 12px;
        }
        .nobi-empty-brands h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--brand-dark);
            margin-bottom: 6px;
        }
        .nobi-empty-brands p {
            font-size: 13.5px;
            color: var(--brand-muted);
            margin-bottom: 16px;
        }

        /* 6. SEO Content & FAQs Section (Chuẩn Dosi-in Style) */
        .nobi-seo-section {
            max-width: 1240px;
            margin: 40px auto 0;
            padding: 0 20px;
        }
        .nobi-seo-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid var(--brand-border);
            padding: 36px 32px;
            box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.04);
        }
        .nobi-seo-content h2 {
            font-size: 20px;
            font-weight: 800;
            color: var(--brand-dark);
            margin-bottom: 12px;
        }
        .nobi-seo-content p {
            font-size: 14px;
            line-height: 1.7;
            color: #475569;
            margin-bottom: 16px;
        }
        .nobi-faq-title {
            font-size: 18px;
            font-weight: 800;
            color: var(--brand-dark);
            margin: 28px 0 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nobi-faq-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .nobi-faq-item {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            background: #f8fafc;
            transition: all 0.2s ease;
        }
        .nobi-faq-item.active {
            background: #ffffff;
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }
        .nobi-faq-question {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            color: var(--brand-dark);
            user-select: none;
        }
        .nobi-faq-question i {
            font-size: 12px;
            color: #94a3b8;
            transition: transform 0.25s ease;
        }
        .nobi-faq-item.active .nobi-faq-question i {
            transform: rotate(180deg);
            color: var(--brand-primary);
        }
        .nobi-faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 13.5px;
            line-height: 1.6;
            color: #475569;
            padding: 0 20px;
        }
        .nobi-faq-item.active .nobi-faq-answer {
            padding: 0 20px 16px;
            max-height: 300px;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1024px) {
            .nobi-top-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .nobi-brands-hero-inner {
                padding: 28px 20px;
                border-radius: 18px;
            }
            .nobi-hero-title {
                font-size: 24px;
            }
            .nobi-hero-stats {
                gap: 18px;
            }
            .nobi-hero-stat-num {
                font-size: 20px;
            }
            .nobi-filter-top {
                flex-direction: column;
                align-items: stretch;
            }
            .nobi-search-input-box {
                max-width: 100%;
            }
            .nobi-top-grid {
                grid-template-columns: 1fr;
            }
            .nobi-all-grid {
                grid-template-columns: 1fr;
            }
            .nobi-seo-card {
                padding: 24px 18px;
            }
        }
    </style>
@endsection

@section('schema')
    {{-- Schema BreadcrumbList & ItemList & FAQPage --}}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": "BreadcrumbList",
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
                    }
                ]
            },
            {
                "@type": "ItemList",
                "name": "Danh mục các thương hiệu thời trang chính hãng",
                "description": "Bảng xếp hạng và thư mục thương hiệu thời trang chính hãng tại {{ $settings->site_name ?? 'NOBIFASHION' }}",
                "numberOfItems": {{ $totalBrandsCount }},
                "itemListElement": [
                    @foreach($brands->take(20) as $index => $b)
                    {
                        "@type": "ListItem",
                        "position": {{ $index + 1 }},
                        "name": "{{ $b->name }}",
                        "url": "{{ route('client.brand.show', $b->slug) }}"
                    }@if(!$loop->last),@endif
                    @endforeach
                ]
            },
            {
                "@type": "FAQPage",
                "mainEntity": [
                    {
                        "@type": "Question",
                        "name": "Các thương hiệu tại NOBIFASHION có chính hãng không?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "100% các sản phẩm và thương hiệu được phân phối tại NOBIFASHION đều là hàng chính hãng được ký hợp đồng bảo chứng chất lượng hoặc độc quyền thương hiệu."
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "Chính sách đổi trả sản phẩm khi mua từ các thương hiệu như thế nào?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "Khách hàng được hỗ trợ đổi size, đổi mẫu trong vòng 15 đến 30 ngày trên toàn quốc với thủ tục nhanh gọn và hỗ trợ tận nhà."
                        }
                    }
                ]
            }
        ]
    }
    </script>
@endsection

@section('content')
    <div class="nobi-brands-wrapper">
        {{-- 1. Breadcrumbs --}}
        <div class="nobi-brands-breadcrumb">
            <a href="{{ url('/') }}"><i class="fa-solid fa-house"></i> Trang chủ</a>
            <i class="fa-solid fa-chevron-right" style="font-size: 10px;"></i>
            <span class="current">Thương hiệu chính hãng</span>
        </div>

        {{-- 2. Hero Banner --}}
        <div class="nobi-brands-hero">
            <div class="nobi-brands-hero-inner">
                <div class="nobi-hero-badge">
                    <i class="fa-solid fa-award"></i> THƯƠNG HIỆU VIỆT & CHÍNH HÃNG
                </div>
                <h1 class="nobi-hero-title">
                    Thư Mục Thương Hiệu Thời Trang Hàng Đầu
                </h1>
                <p class="nobi-hero-desc">
                    Khám phá bảng xếp hạng và hệ sinh thái các local brand, thương hiệu thời trang uy tín chuẩn chất lượng Việt Nam được kiểm duyệt và phân phối chính thức tại {{ $settings->site_name ?? 'NOBIFASHION' }}.
                </p>

                <div class="nobi-hero-stats">
                    <div class="nobi-hero-stat-item">
                        <div class="nobi-hero-stat-num">{{ $totalBrandsCount }}</div>
                        <div class="nobi-hero-stat-label">Thương hiệu đối tác</div>
                    </div>
                    <div class="nobi-hero-stat-item">
                        <div class="nobi-hero-stat-num">{{ number_format($totalProductsCount) }}+</div>
                        <div class="nobi-hero-stat-label">Sản phẩm chính hãng</div>
                    </div>
                    <div class="nobi-hero-stat-item">
                        <div class="nobi-hero-stat-num">
                            {{ number_format((float) $avgRating, 1) }} <i class="fa-solid fa-star star"></i>
                        </div>
                        <div class="nobi-hero-stat-label">Điểm đánh giá uy tín</div>
                    </div>
                    <div class="nobi-hero-stat-item">
                        <div class="nobi-hero-stat-num">100%</div>
                        <div class="nobi-hero-stat-label">Cam kết chính hãng</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Filter Bar & Alphabet Index --}}
        <div class="nobi-filter-section">
            <div class="nobi-filter-card">
                <div class="nobi-filter-top">
                    {{-- Ô tìm kiếm tức thì --}}
                    <div class="nobi-search-input-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="brand-live-search" class="nobi-search-input" placeholder="Tìm kiếm nhanh thương hiệu (VD: Yody, Nobifashion...)" autocomplete="off">
                    </div>

                    {{-- Tabs phân loại --}}
                    <div class="nobi-tabs-group">
                        <button type="button" class="nobi-tab-btn active" data-tab="all">Tất cả ({{ $totalBrandsCount }})</button>
                        <button type="button" class="nobi-tab-btn" data-tab="top">Nổi bật</button>
                        <button type="button" class="nobi-tab-btn" data-tab="most-products">Nhiều sản phẩm</button>
                    </div>
                </div>

                {{-- Thanh chữ cái Alphabet (A - Z) --}}
                <div class="nobi-alphabet-bar">
                    <button type="button" class="nobi-letter-btn active" data-letter="ALL">TẤT CẢ</button>
                    @foreach(['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','#'] as $letter)
                        @php
                            $hasLetter = $availableLetters->contains($letter);
                        @endphp
                        <button type="button" class="nobi-letter-btn {{ $hasLetter ? '' : 'disabled' }}" data-letter="{{ $letter }}">
                            {{ $letter }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 4. Bảng Xếp Hạng Top Thương Hiệu Nổi Bật (Top Brands Showcase) --}}
        @if(isset($topBrands) && $topBrands->isNotEmpty())
            <div class="nobi-section-container" id="top-brands-showcase">
                <div class="nobi-section-header">
                    <div>
                        <h2 class="nobi-section-title">
                            <i class="fa-solid fa-ranking-star"></i> Bảng Xếp Hạng Thương Hiệu Nổi Bật
                        </h2>
                        <div class="nobi-section-subtitle">Dựa trên số lượng sản phẩm, đánh giá và mức độ quan tâm của cộng đồng</div>
                    </div>
                </div>

                <div class="nobi-top-grid">
                    @foreach($topBrands as $idx => $top)
                        @php
                            $rankClass = match($idx) {
                                0 => 'nobi-rank-1',
                                1 => 'nobi-rank-2',
                                2 => 'nobi-rank-3',
                                default => 'nobi-rank-other'
                            };
                            $rankIcon = match($idx) {
                                0 => '👑 TOP 1',
                                1 => '🥈 TOP 2',
                                2 => '🥉 TOP 3',
                                default => '⭐ TOP ' . ($idx + 1)
                            };
                        @endphp
                        <a href="{{ route('client.brand.show', $top->slug) }}" class="nobi-top-card">
                            <div class="nobi-rank-badge {{ $rankClass }}">
                                {{ $rankIcon }}
                            </div>

                            <div class="nobi-top-logo-wrapper">
                                @if(!empty($top->logo))
                                    <img src="{{ asset('clients/assets/img/brands/' . $top->logo) }}" alt="{{ $top->name }}">
                                @else
                                    <span class="nobi-top-logo-placeholder">{{ strtoupper(substr($top->name, 0, 2)) }}</span>
                                @endif
                            </div>

                            <div class="nobi-top-info">
                                <div class="nobi-top-name">
                                    {{ $top->name }}
                                    <i class="fa-solid fa-circle-check nobi-verified-icon" title="Chứng nhận chính hãng"></i>
                                </div>
                                @if(!empty($top->campaign))
                                    <span class="nobi-top-campaign">{{ $top->campaign }}</span>
                                @endif
                            </div>

                            <div class="nobi-top-metrics">
                                <div class="nobi-metric-item">
                                    <span class="nobi-metric-val">{{ number_format($top->products_count ?? 0) }}</span>
                                    <span class="nobi-metric-lbl">Sản phẩm</span>
                                </div>
                                <div class="nobi-metric-item">
                                    <span class="nobi-metric-val">{{ number_format((float) ($top->rating_score ?? 4.9), 1) }} ⭐</span>
                                    <span class="nobi-metric-lbl">Đánh giá</span>
                                </div>
                            </div>

                            <div class="nobi-top-btn">
                                Khám phá gian hàng <i class="fa-solid fa-arrow-right"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- 5. Danh Bạ Toàn Bộ Thương Hiệu (All Brands Directory Grid) --}}
        <div class="nobi-section-container">
            <div class="nobi-section-header">
                <div>
                    <h2 class="nobi-section-title" id="directory-heading">
                        <i class="fa-solid fa-boxes-stacked" style="color: var(--brand-primary);"></i> Danh Sách Tất Cả Thương Hiệu
                    </h2>
                    <div class="nobi-section-subtitle">
                        Tìm thấy <strong id="visible-brands-count" style="color: var(--brand-primary);">{{ $totalBrandsCount }}</strong> thương hiệu chính hãng
                    </div>
                </div>
            </div>

            <div class="nobi-all-grid" id="brands-grid-container">
                @forelse($brands as $item)
                    @php
                        $firstLetter = mb_strtoupper(mb_substr(trim($item->name), 0, 1, 'UTF-8'), 'UTF-8');
                        if (!preg_match('/^[A-Z]$/u', $firstLetter)) {
                            $firstLetter = '#';
                        }
                    @endphp
                    <a href="{{ route('client.brand.show', $item->slug) }}" 
                       class="nobi-brand-item-card" 
                       data-name="{{ mb_strtolower($item->name, 'UTF-8') }}" 
                       data-letter="{{ $firstLetter }}"
                       data-products="{{ $item->products_count ?? 0 }}"
                       data-rating="{{ $item->rating_score ?? 4.9 }}">
                        <div class="nobi-item-header">
                            <div class="nobi-item-logo">
                                @if(!empty($item->logo))
                                    <img src="{{ asset('clients/assets/img/brands/' . $item->logo) }}" alt="{{ $item->name }}" loading="lazy">
                                @else
                                    <span style="font-weight: 800; font-size: 16px; color: var(--brand-primary);">
                                        {{ strtoupper(substr($item->name, 0, 2)) }}
                                    </span>
                                @endif
                            </div>
                            <div class="nobi-item-title-group">
                                <div class="nobi-item-name">
                                    {{ $item->name }}
                                    <i class="fa-solid fa-circle-check nobi-verified-icon"></i>
                                </div>
                                <div class="nobi-item-meta">
                                    @if(!empty($item->campaign))
                                        <span style="color: #2563eb; font-weight: 600;">{{ $item->campaign }}</span>
                                    @else
                                        <span>Đối tác chính hãng</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="nobi-item-desc">
                            {{ $item->description ? Str::limit($item->description, 95) : 'Thương hiệu thời trang cao cấp chính hãng với nhiều thiết kế nổi bật tại NOBIFASHION.' }}
                        </div>

                        <div class="nobi-item-footer">
                            <span class="nobi-item-badge-count">
                                <i class="fa-solid fa-shirt"></i> {{ number_format($item->products_count ?? 0) }} sản phẩm
                            </span>
                            <span class="nobi-item-rating">
                                <i class="fa-solid fa-star"></i> {{ number_format((float) ($item->rating_score ?? 4.9), 1) }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="nobi-empty-brands">
                        <i class="fa-solid fa-box-open"></i>
                        <h4>Chưa có thương hiệu nào</h4>
                        <p>Danh sách thương hiệu đang được cập nhật. Vui lòng quay lại sau.</p>
                    </div>
                @endforelse

                {{-- Empty state khi tìm kiếm không ra kết quả --}}
                <div class="nobi-empty-brands" id="search-empty-state" style="display: none;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <h4>Không tìm thấy thương hiệu phù hợp</h4>
                    <p>Hãy thử tìm bằng từ khóa khác hoặc bấm xem tất cả thương hiệu.</p>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-reset-filter" style="border-radius: 8px; padding: 6px 16px;">
                        Xem tất cả thương hiệu
                    </button>
                </div>
            </div>
        </div>

        {{-- 6. Khối Nội dung Giới thiệu & FAQs Chuẩn SEO (Dosi-in Style) --}}
        <div class="nobi-seo-section">
            <div class="nobi-seo-card">
                <div class="nobi-seo-content">
                    <h2>Về Hệ Sinh Thái Thương Hiệu Thời Trang Tại NOBIFASHION</h2>
                    <p>
                        <strong>NOBIFASHION</strong> tự hào là điểm đến mua sắm uy tín quy tụ hàng loạt thương hiệu thời trang nam, local brand Việt Nam và các nhãn hàng quốc tế được chọn lọc kỹ lưỡng. Chúng tôi hướng tới việc cung cấp giải pháp ăn mặc toàn diện: từ phong cách công sở chỉn chu, thanh lịch đến streetwear năng động, hiện đại và thời trang thường ngày đề cao sự thoải mái.
                    </p>
                    <p>
                        Mỗi thương hiệu xuất hiện trên hệ thống đều phải trải qua quy trình kiểm duyệt khắt khe về nguồn gốc xuất xứ, độ bền chất liệu và sự chuẩn mực trong đường may. Người tiêu dùng hoàn toàn yên tâm về chính sách giá niêm yết đồng bộ, các chương trình ưu đãi độc quyền cùng chế độ chăm sóc khách hàng chuyên nghiệp.
                    </p>
                </div>

                <div class="nobi-faq-title">
                    <i class="fa-solid fa-circle-question" style="color: var(--brand-primary);"></i> Câu Hỏi Thường Gặp Về Thương Hiệu
                </div>
                <div class="nobi-faq-list">
                    <div class="nobi-faq-item active">
                        <div class="nobi-faq-question">
                            <span>Sản phẩm từ các thương hiệu trên NOBIFASHION có chính hãng 100% không?</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="nobi-faq-answer">
                            Có. Tất cả các sản phẩm mang nhãn hiệu đối tác được bán tại NOBIFASHION đều được nhập trực tiếp từ nhà sản xuất hoặc đại diện phân phối chính thức tại Việt Nam. Chúng tôi cam kết đền bù 200% nếu phát hiện hàng giả, hàng nhái.
                        </div>
                    </div>

                    <div class="nobi-faq-item">
                        <div class="nobi-faq-question">
                            <span>Chính sách bảo hành và đổi size áp dụng như thế nào?</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="nobi-faq-answer">
                            Quý khách được hỗ trợ đổi size hoặc đổi mẫu trong vòng 15 đến 30 ngày kể từ ngày nhận hàng. Sản phẩm phải còn nguyên tem mác và chưa qua giặt tẩy. Đội ngũ giao vận sẽ hỗ trợ đổi trả tận nơi cực kỳ tiện lợi.
                        </div>
                    </div>

                    <div class="nobi-faq-item">
                        <div class="nobi-faq-question">
                            <span>Làm thế nào để thương hiệu thời trang có thể hợp tác cùng NOBIFASHION?</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="nobi-faq-answer">
                            Các nhà sản xuất và local brand có nhu cầu mở rộng kênh phân phối có thể liên hệ trực tiếp qua trang Liên hệ hoặc gửi thông tin hồ sơ nhãn hàng tới email đối tác: <code>contact@nobifashion.vn</code>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('foot')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('brand-live-search');
            const letterButtons = document.querySelectorAll('.nobi-letter-btn');
            const tabButtons = document.querySelectorAll('.nobi-tab-btn');
            const brandCards = document.querySelectorAll('.nobi-brand-item-card');
            const visibleCountSpan = document.getElementById('visible-brands-count');
            const emptyState = document.getElementById('search-empty-state');
            const btnReset = document.getElementById('btn-reset-filter');
            const showcaseSection = document.getElementById('top-brands-showcase');

            let currentLetter = 'ALL';
            let currentTab = 'all';

            function filterBrands() {
                const keyword = (searchInput?.value || '').trim().toLowerCase();
                let visibleCount = 0;

                brandCards.forEach(card => {
                    const name = card.getAttribute('data-name') || '';
                    const letter = card.getAttribute('data-letter') || '';
                    const products = parseInt(card.getAttribute('data-products') || '0', 10);

                    // 1. Kiểm tra từ khóa
                    const matchKeyword = !keyword || name.includes(keyword);

                    // 2. Kiểm tra chữ cái
                    const matchLetter = currentLetter === 'ALL' || letter === currentLetter;

                    // 3. Kiểm tra tab
                    let matchTab = true;
                    if (currentTab === 'most-products') {
                        matchTab = products > 0;
                    }

                    if (matchKeyword && matchLetter && matchTab) {
                        card.style.display = 'flex';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Cập nhật số đếm
                if (visibleCountSpan) {
                    visibleCountSpan.textContent = visibleCount;
                }

                // Hiện empty state nếu không tìm thấy
                if (emptyState) {
                    emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
                }

                // Ẩn showcase top brands khi người dùng đang search cụ thể
                if (showcaseSection) {
                    if (keyword !== '' || currentLetter !== 'ALL') {
                        showcaseSection.style.display = 'none';
                    } else {
                        showcaseSection.style.display = 'block';
                    }
                }
            }

            // Lắng nghe sự kiện gõ tìm kiếm (Realtime live search)
            if (searchInput) {
                searchInput.addEventListener('input', filterBrands);
            }

            // Lắng nghe sự kiện click chọn chữ cái A-Z
            letterButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    if (btn.classList.contains('disabled')) return;

                    letterButtons.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentLetter = btn.getAttribute('data-letter') || 'ALL';

                    filterBrands();
                });
            });

            // Lắng nghe sự kiện click Tabs
            tabButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    tabButtons.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentTab = btn.getAttribute('data-tab') || 'all';

                    // Nếu chọn tab top, cuộn mượt đến bảng xếp hạng
                    if (currentTab === 'top' && showcaseSection) {
                        showcaseSection.scrollIntoView({ behavior: 'smooth' });
                    }

                    filterBrands();
                });
            });

            // Nút reset bộ lọc
            if (btnReset) {
                btnReset.addEventListener('click', () => {
                    if (searchInput) searchInput.value = '';
                    currentLetter = 'ALL';
                    currentTab = 'all';

                    letterButtons.forEach(b => b.classList.remove('active'));
                    document.querySelector('.nobi-letter-btn[data-letter="ALL"]')?.classList.add('active');

                    tabButtons.forEach(b => b.classList.remove('active'));
                    document.querySelector('.nobi-tab-btn[data-tab="all"]')?.classList.add('active');

                    filterBrands();
                });
            }

            // FAQ Accordion click toggle
            const faqItems = document.querySelectorAll('.nobi-faq-item');
            faqItems.forEach(item => {
                const question = item.querySelector('.nobi-faq-question');
                if (question) {
                    question.addEventListener('click', () => {
                        const isActive = item.classList.contains('active');
                        faqItems.forEach(i => i.classList.remove('active'));
                        if (!isActive) {
                            item.classList.add('active');
                        }
                    });
                }
            });
        });
    </script>
@endsection
