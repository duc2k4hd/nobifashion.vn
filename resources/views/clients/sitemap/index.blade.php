@extends('clients.layouts.master')

@section('title', $metaTitle ?? 'Sitemap - ' . config('app.name'))

@section('head')
    {{-- Basic SEO Meta Tags --}}
    <meta name="description" content="{{ $metaDescription ?? 'Sitemap của ' . config('app.name') . ' - Tìm kiếm và khám phá tất cả các trang, sản phẩm, bài viết và danh mục trên website của chúng tôi.' }}">
    <meta name="keywords" content="{{ $metaKeywords ?? 'sitemap, ' . config('app.name') . ', bản đồ trang web, tìm kiếm nội dung' }}">
    <meta name="author" content="{{ $siteName ?? config('app.name') }}">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="googlebot" content="index, follow">
    <meta name="bingbot" content="index, follow">
    
    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ $sitemapUrl ?? route('client.sitemap.html') }}">
    
    {{-- Alternate Languages (if needed) --}}
    <link rel="alternate" hreflang="vi" href="{{ $sitemapUrl ?? route('client.sitemap.html') }}">
    
    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $sitemapUrl ?? route('client.sitemap.html') }}">
    <meta property="og:title" content="{{ $metaTitle ?? 'Sitemap - ' . config('app.name') }}">
    <meta property="og:description" content="{{ $metaDescription ?? 'Sitemap của ' . config('app.name') . ' - Tìm kiếm và khám phá tất cả các trang, sản phẩm, bài viết và danh mục trên website của chúng tôi.' }}">
    @php
        $ogImage = file_exists(public_path('clients/assets/img/og-sitemap.jpg')) 
            ? asset('clients/assets/img/og-sitemap.jpg') 
            : (file_exists(public_path('clients/assets/img/business/' . ($settings->site_logo ?? ''))) 
                ? asset('clients/assets/img/business/' . ($settings->site_logo ?? '')) 
                : asset('clients/assets/img/default-og.jpg'));
    @endphp
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Sitemap - {{ $siteName ?? config('app.name') }}">
    <meta property="og:site_name" content="{{ $siteName ?? config('app.name') }}">
    <meta property="og:locale" content="vi_VN">
    
    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $sitemapUrl ?? route('client.sitemap.html') }}">
    <meta name="twitter:title" content="{{ $metaTitle ?? 'Sitemap - ' . config('app.name') }}">
    <meta name="twitter:description" content="{{ $metaDescription ?? 'Sitemap của ' . config('app.name') . ' - Tìm kiếm và khám phá tất cả các trang, sản phẩm, bài viết và danh mục trên website của chúng tôi.' }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <meta name="twitter:image:alt" content="Sitemap - {{ $siteName ?? config('app.name') }}">
    
    {{-- Additional Meta Tags --}}
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    {{-- Sitemap XML Reference --}}
    <link rel="sitemap" type="application/xml" href="{{ $sitemapXmlUrl ?? url('/sitemap.xml') }}">
    
    {{-- Preconnect for Performance --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
@endsection

@section('schema')
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "{{ $metaTitle ?? 'Sitemap - ' . config('app.name') }}",
        "description": "{{ $metaDescription ?? 'Sitemap của ' . config('app.name') }}",
        "url": "{{ $sitemapUrl ?? route('client.sitemap.html') }}",
        "inLanguage": "vi-VN",
        "isPartOf": {
            "@type": "WebSite",
            "name": "{{ $siteName ?? config('app.name') }}",
            "url": "{{ $siteUrl ?? config('app.url') }}"
        },
        "breadcrumb": {
            "@type": "BreadcrumbList",
            "itemListElement": [
                {
                    "@type": "ListItem",
                    "position": 1,
                    "name": "Trang chủ",
                    "item": "{{ $siteUrl ?? config('app.url') }}"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "Sitemap",
                    "item": "{{ $sitemapUrl ?? route('client.sitemap.html') }}"
                }
            ]
        },
        "mainEntity": {
            "@type": "ItemList",
            "name": "Sitemap Index",
            "description": "Danh sách tất cả các sitemap XML",
            "itemListElement": [
                @foreach($sitemaps as $index => $sitemap)
                {
                    "@type": "ListItem",
                    "position": {{ $index + 1 }},
                    "name": "{{ $sitemap['name'] }}",
                    "description": "{{ $sitemap['description'] }}",
                    "url": "{{ $sitemap['url'] }}"
                }@if(!$loop->last),@endif
                @endforeach
            ]
        }
    }
    </script>
@endsection

@push('styles')
    <style>
        /* ============================================
           HERO SECTION - Premium Design
           ============================================ */
        .sitemap-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            padding: 80px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
            margin-bottom: 60px;
        }
        
        .sitemap-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 0.8; }
        }
        
        .sitemap-hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .sitemap-hero h1 {
            font-size: 48px;
            font-weight: 800;
            color: white;
            margin-bottom: 20px;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            letter-spacing: -1px;
        }
        
        .sitemap-hero p {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 0;
            font-weight: 400;
            line-height: 1.6;
        }
        
        /* ============================================
           MAIN CONTAINER
           ============================================ */
        .sitemap-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 80px;
        }
        
        /* ============================================
           SITEMAP GRID - Beautiful Cards
           ============================================ */
        .sitemap-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }
        
        .sitemap-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 
                0 10px 25px -5px rgba(0, 0, 0, 0.1),
                0 4px 6px -2px rgba(0, 0, 0, 0.05),
                0 0 0 1px rgba(0, 0, 0, 0.05);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: visible;
            border: 2px solid transparent;
        }
        
        .sitemap-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s ease;
        }
        
        .sitemap-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 
                0 20px 40px -10px rgba(102, 126, 234, 0.3),
                0 10px 20px -5px rgba(102, 126, 234, 0.2),
                0 0 0 1px rgba(102, 126, 234, 0.1);
            border-color: rgba(102, 126, 234, 0.2);
        }
        
        .sitemap-card:hover::before {
            transform: scaleX(1);
        }
        
        .sitemap-card-icon {
            font-size: 56px;
            margin-bottom: 20px;
            display: block;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .sitemap-card:nth-child(1) .sitemap-card-icon { animation-delay: 0s; }
        .sitemap-card:nth-child(2) .sitemap-card-icon { animation-delay: 0.2s; }
        .sitemap-card:nth-child(3) .sitemap-card-icon { animation-delay: 0.4s; }
        .sitemap-card:nth-child(4) .sitemap-card-icon { animation-delay: 0.6s; }
        .sitemap-card:nth-child(5) .sitemap-card-icon { animation-delay: 0.8s; }
        .sitemap-card:nth-child(6) .sitemap-card-icon { animation-delay: 1s; }
        .sitemap-card:nth-child(7) .sitemap-card-icon { animation-delay: 1.2s; }
        
        .sitemap-card-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }
        
        .sitemap-card-description {
            font-size: 15px;
            color: #64748b;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        
        .sitemap-card-url {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            padding: 12px 20px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 12px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            word-break: break-all;
            position: relative;
            z-index: 10;
            cursor: pointer;
        }
        
        .sitemap-card-url:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .sitemap-card-url::after {
            content: '→';
            font-size: 18px;
            transition: transform 0.3s ease;
        }
        
        .sitemap-card-url:hover::after {
            transform: translateX(5px);
        }
        
        .sitemap-card-url-wrapper {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .sitemap-card-url-direct {
            display: block;
            font-size: 12px;
            color: #64748b;
            padding: 8px 12px;
            background: #f1f5f9;
            border-radius: 8px;
            font-family: 'Monaco', 'Courier New', monospace;
            word-break: break-all;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
            position: relative;
            z-index: 10;
            cursor: pointer;
            text-decoration: none;
        }
        
        .sitemap-card-url-direct:hover {
            background: #e2e8f0;
            border-color: #cbd5e1;
        }
        
        /* ============================================
           INFO SECTION
           ============================================ */
        .sitemap-info {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 20px;
            padding: 40px;
            margin-top: 60px;
            border: 2px solid rgba(102, 126, 234, 0.1);
        }
        
        .sitemap-info h2 {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sitemap-info p {
            font-size: 16px;
            color: #475569;
            line-height: 1.8;
            margin-bottom: 16px;
        }
        
        .sitemap-info ul {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .sitemap-info li {
            padding: 12px 0;
            padding-left: 32px;
            position: relative;
            color: #475569;
            font-size: 15px;
            line-height: 1.6;
        }
        
        .sitemap-info li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: 700;
            font-size: 18px;
        }
        
        /* ============================================
           ANIMATIONS
           ============================================ */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .sitemap-card {
            animation: fadeInUp 0.6s ease-out backwards;
        }
        
        .sitemap-card:nth-child(1) { animation-delay: 0.1s; }
        .sitemap-card:nth-child(2) { animation-delay: 0.2s; }
        .sitemap-card:nth-child(3) { animation-delay: 0.3s; }
        .sitemap-card:nth-child(4) { animation-delay: 0.4s; }
        .sitemap-card:nth-child(5) { animation-delay: 0.5s; }
        .sitemap-card:nth-child(6) { animation-delay: 0.6s; }
        .sitemap-card:nth-child(7) { animation-delay: 0.7s; }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 768px) {
            .sitemap-hero {
                padding: 60px 20px;
            }
            
            .sitemap-hero h1 {
                font-size: 36px;
            }
            
            .sitemap-hero p {
                font-size: 18px;
            }
            
            .sitemap-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .sitemap-card {
                padding: 30px;
            }
            
            .sitemap-info {
                padding: 30px 20px;
            }
        }
        
        /* ============================================
           DECORATIVE ELEMENTS
           ============================================ */
        .sitemap-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.05) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: 1;
            pointer-events: none;
        }
        
        .sitemap-card:hover::after {
            opacity: 1;
        }
        
        .sitemap-card-url-wrapper {
            position: relative;
            z-index: 10;
        }
    </style>
@endpush

@section('content')
    <!-- Hero Section -->
    <div class="sitemap-hero">
        <div class="sitemap-hero-content">
            <h1>🗺️ Sitemap</h1>
            <p>Khám phá toàn bộ nội dung trên website của chúng tôi một cách dễ dàng và nhanh chóng</p>
        </div>
    </div>

    <!-- Main Container -->
    <div class="sitemap-container">
        <!-- Sitemap Grid -->
        <div class="sitemap-grid">
            @foreach($sitemaps as $sitemap)
                <div class="sitemap-card">
                    <span class="sitemap-card-icon">{{ $sitemap['icon'] }}</span>
                    <h3 class="sitemap-card-title">{{ $sitemap['name'] }}</h3>
                    <p class="sitemap-card-description">{{ $sitemap['description'] }}</p>
                    <div class="sitemap-card-url-wrapper">
                        <a href="{{ $sitemap['url'] }}" target="_blank" class="sitemap-card-url">
                            📄 Xem sitemap XML
                        </a>
                        <a href="{{ $sitemap['url'] }}" target="_blank" class="sitemap-card-url-direct" title="Click để mở XML">
                            {{ $sitemap['url'] }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Info Section -->
        <div class="sitemap-info">
            <h2>ℹ️ Thông tin về Sitemap</h2>
            <p>
                Sitemap giúp các công cụ tìm kiếm như Google, Bing dễ dàng thu thập và lập chỉ mục 
                tất cả các trang trên website của chúng tôi. Điều này giúp cải thiện khả năng hiển thị 
                của website trên các công cụ tìm kiếm.
            </p>
            <ul>
                <li>Sitemap được cập nhật tự động khi có nội dung mới</li>
                <li>Hỗ trợ đầy đủ các loại nội dung: bài viết, sản phẩm, danh mục, tags</li>
                <li>Tuân thủ chuẩn XML Sitemap Protocol của Google</li>
                <li>Tối ưu hóa cho SEO và công cụ tìm kiếm</li>
            </ul>
            <p style="margin-top: 24px; margin-bottom: 0;">
                <strong>Lưu ý:</strong> Các file sitemap XML được tạo tự động và có thể được submit 
                trực tiếp lên Google Search Console để tăng tốc độ lập chỉ mục.
            </p>
        </div>
    </div>
@endsection

