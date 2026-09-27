@extends('clients.layouts.master')

@section('title', '404 - Không Tìm Thấy Trang | ' . ($settings_site_name ?? 'NOBI FASHION VIỆT NAM'))

@section('head')
    <meta name="theme-color" content="#fffbf5">
    <meta name="robots" content="nofollow, noindex"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap');

        /* ==========================================================================
           NOBI FASHION 404 - SOOTHING, CURVED, ORGANIC, WARM & ENGAGING
           ========================================================================== */
        :root {
            --curve-bg-warm: #fdfbf7;
            --curve-card: #ffffff;
            --curve-text-dark: #1c1917;
            --curve-text-muted: #78716c;
            --curve-border: #f1ece4;
            --curve-coral: #ea580c;
            --curve-coral-light: #fff7ed;
            --curve-coral-hover: #c2410c;
            --curve-amber: #d97706;
            --curve-amber-light: #fffbeb;
            --curve-shadow: 0 12px 36px -8px rgba(28, 25, 23, 0.06), 0 4px 12px -2px rgba(28, 25, 23, 0.03);
            --curve-radius-card: 28px;
            --curve-radius-pill: 50px;
        }

        .nobi-404-page {
            font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(180deg, #fffbf5 0%, #fdfbf7 50%, #f7f4ed 100%);
            padding: 30px 16px 60px 16px;
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--curve-text-dark);
            position: relative;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .nobi-404-page h1,
        .nobi-404-page h2,
        .nobi-404-page h3,
        .nobi-404-page p,
        .nobi-404-page a,
        .nobi-404-page button,
        .nobi-404-page input,
        .nobi-404-page span {
            font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        /* Vòng tròn decor nền mềm mại */
        .decor-circle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            filter: blur(60px);
            opacity: 0.45;
            z-index: 1;
        }
        .decor-circle-1 {
            width: 380px;
            height: 380px;
            background: #fed7aa;
            top: -60px;
            left: -80px;
        }
        .decor-circle-2 {
            width: 420px;
            height: 420px;
            background: #fecdd3;
            bottom: -80px;
            right: -60px;
        }

        .nobi-404-shell {
            max-width: 960px;
            width: 100%;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        /* Thẻ chính nguyên khối bo tròn mềm mại */
        .curved-main-card {
            background: var(--curve-card);
            border: 1px solid var(--curve-border);
            border-radius: var(--curve-radius-card);
            box-shadow: var(--curve-shadow);
            padding: 36px 28px;
            text-align: center;
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
        }

        /* ==========================================================================
           CẢNH VẬT THỜI TRANG VÀ CON SỐ 404 BO TRÒN DỄ THƯƠNG
           ========================================================================== */
        .curved-scene-bubble {
            width: 160px;
            height: 160px;
            margin: 0 auto 18px auto;
            border-radius: 50%;
            background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 40%, #fecdd3 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: inset 0 2px 6px rgba(255, 255, 255, 0.8), 0 8px 24px rgba(234, 88, 12, 0.12);
            animation: bubbleFloat 4s ease-in-out infinite alternate;
        }

        @keyframes bubbleFloat {
            0% { transform: translateY(0px); }
            100% { transform: translateY(-8px); }
        }

        /* Khinh khí cầu bay lượn */
        .floating-balloon {
            width: 80px;
            height: auto;
            animation: swayBalloon 3s ease-in-out infinite alternate;
        }

        @keyframes swayBalloon {
            0% { transform: rotate(-4deg); }
            100% { transform: rotate(4deg); }
        }

        /* Ngôi sao lấp lánh decor */
        .mini-sparkle {
            position: absolute;
            color: #d97706;
            animation: sparkleGlow 2s infinite alternate;
        }
        .sparkle-1 { top: 18px; left: 16px; font-size: 14px; animation-delay: 0.2s; }
        .sparkle-2 { bottom: 24px; right: 18px; font-size: 12px; animation-delay: 0.7s; }
        .sparkle-3 { top: 20px; right: 26px; font-size: 10px; animation-delay: 1.1s; }

        @keyframes sparkleGlow {
            0% { opacity: 0.3; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1.2); }
        }

        /* Con số 404 nghệ thuật bo tròn */
        .curved-number-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -2px;
            background: linear-gradient(135deg, #ea580c 0%, #f43f5e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
            user-select: none;
        }

        .curved-tag-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: var(--curve-radius-pill);
            background: var(--curve-coral-light);
            border: 1px solid #fed7aa;
            color: var(--curve-coral);
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 12px;
            letter-spacing: 0.3px;
        }

        .curved-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--curve-text-dark);
            margin-bottom: 8px;
            letter-spacing: -0.3px;
        }

        .curved-desc {
            font-size: 14px;
            color: var(--curve-text-muted);
            max-width: 580px;
            margin: 0 auto 20px auto;
            line-height: 1.6;
        }

        /* ==========================================================================
           THANH TÌM KIẾM BO TRÒN PILL THÂN THIỆN
           ========================================================================== */
        .curved-search-form {
            max-width: 500px;
            margin: 0 auto 16px auto;
            position: relative;
        }

        .curved-search-input {
            width: 100%;
            padding: 12px 50px 12px 20px;
            font-size: 13px;
            border: 1.5px solid var(--curve-border);
            border-radius: var(--curve-radius-pill);
            background: #fafaf9;
            color: var(--curve-text-dark);
            outline: none;
            transition: all 0.2s ease;
        }
        .curved-search-input:focus {
            background: #ffffff;
            border-color: var(--curve-coral);
            box-shadow: 0 0 0 4px rgba(234, 88, 12, 0.12);
        }

        .curved-search-btn {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: var(--curve-coral);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.15s, transform 0.15s;
        }
        .curved-search-btn:hover {
            background: var(--curve-coral-hover);
            transform: translateY(-50%) scale(1.05);
        }

        /* Gợi ý tags bo tròn */
        .curved-tag-list {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 22px;
        }

        .curved-pill-chip {
            padding: 4px 12px;
            border-radius: var(--curve-radius-pill);
            background: #f5f5f4;
            color: #44403c;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid #e7e5e4;
            transition: all 0.15s ease;
        }
        .curved-pill-chip:hover {
            background: var(--curve-text-dark);
            color: #ffffff;
            border-color: var(--curve-text-dark);
            transform: translateY(-1px);
        }

        /* ==========================================================================
           CÁC NÚT BẤM CTA BO TRÒN (PILL BUTTONS)
           ========================================================================== */
        .curved-cta-row {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .curved-btn {
            padding: 10px 22px;
            border-radius: var(--curve-radius-pill);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.18s ease;
            cursor: pointer;
            border: 1.5px solid transparent;
            line-height: 1.4;
        }

        .curved-btn-dark {
            background: var(--curve-text-dark);
            color: #ffffff;
            border-color: var(--curve-text-dark);
        }
        .curved-btn-dark:hover {
            background: #292524;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(28, 25, 23, 0.15);
        }

        .curved-btn-coral {
            background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
            color: #ffffff;
            border-color: #ea580c;
        }
        .curved-btn-coral:hover {
            background: linear-gradient(135deg, #c2410c 0%, #ea580c 100%);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(234, 88, 12, 0.25);
        }

        .curved-btn-light {
            background: #ffffff;
            color: #44403c;
            border-color: #e7e5e4;
        }
        .curved-btn-light:hover {
            background: #f5f5f4;
            color: #1c1917;
            border-color: #d6d3d1;
            transform: translateY(-2px);
        }

        /* ==========================================================================
           VOUCHER AN ỦI GỌN GÀNG (PILL VOUCHER RIBBON)
           ========================================================================== */
        .curved-voucher-ribbon {
            background: var(--curve-amber-light);
            border: 1.5px dashed #fcd34d;
            border-radius: var(--curve-radius-pill);
            padding: 8px 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin: 0 auto;
            max-width: 580px;
            font-size: 12px;
        }

        .curved-code-tag {
            background: #ffffff;
            color: var(--curve-amber);
            font-weight: 800;
            padding: 3px 10px;
            border-radius: var(--curve-radius-pill);
            border: 1px solid #fde68a;
            letter-spacing: 1px;
            font-family: Consolas, monospace;
        }

        .curved-copy-btn {
            background: var(--curve-amber);
            color: #ffffff;
            border: none;
            border-radius: var(--curve-radius-pill);
            padding: 4px 12px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.15s, transform 0.15s;
        }
        .curved-copy-btn:hover {
            background: #b45309;
            transform: scale(1.04);
        }

        /* ==========================================================================
           SẢN PHẨM GỢI Ý BO TRÒN MỀM MẠI (CURVED PRODUCT CARDS)
           ========================================================================== */
        .curved-suggest-wrap {
            margin-top: 10px;
        }

        .curved-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            padding: 0 4px;
        }

        .curved-section-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--curve-text-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .curved-grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
        }

        .curved-product-card {
            background: #ffffff;
            border: 1px solid var(--curve-border);
            border-radius: 20px;
            padding: 12px;
            box-shadow: 0 4px 16px -2px rgba(28, 25, 23, 0.04);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .curved-product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -4px rgba(28, 25, 23, 0.08);
            border-color: #cbd5e1;
        }

        .curved-img-container {
            width: 100%;
            height: 200px;
            border-radius: 14px;
            overflow: hidden;
            background: #f5f5f4;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .curved-prod-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.35s ease;
        }
        .curved-product-card:hover .curved-prod-img {
            transform: scale(1.05);
        }

        .curved-prod-details {
            padding: 10px 4px 4px 4px;
            text-align: left;
        }

        .curved-prod-cat {
            font-size: 11px;
            font-weight: 700;
            color: var(--curve-coral);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .curved-prod-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--curve-text-dark);
            line-height: 1.4;
            height: 36px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            margin-bottom: 8px;
        }

        .curved-price-row {
            display: flex;
            align-items: baseline;
            gap: 8px;
        }

        .curved-final-price {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
        }

        .curved-orig-price {
            font-size: 12px;
            color: #a8a29e;
            text-decoration: line-through;
        }

        /* ==========================================================================
           THANH QUẢN TRỊ VIÊN & LIÊN HỆ {Nguyễn Minh Đức} nguyenminhduc.id.vn BO TRÒN PILL
           ========================================================================== */
        .curved-admin-bar {
            background: #ffffff;
            border: 1px solid var(--curve-border);
            border-radius: var(--curve-radius-pill);
            box-shadow: 0 4px 12px rgba(28, 25, 23, 0.03);
            padding: 10px 22px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 12px;
            color: #57534e;
        }

        .admin-pill-tag {
            background: #1c1917;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: var(--curve-radius-pill);
            margin-right: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .curved-admin-link {
            color: var(--curve-coral);
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-bottom: 1.5px dotted var(--curve-coral);
            transition: color 0.15s;
        }
        .curved-admin-link:hover {
            color: var(--curve-coral-hover);
            border-bottom-style: solid;
        }

        @media (max-width: 640px) {
            .curved-main-card { padding: 24px 16px; border-radius: 20px; }
            .curved-scene-bubble { width: 130px; height: 130px; }
            .curved-number-badge { font-size: 48px; }
            .curved-title { font-size: 20px; }
            .curved-cta-row { flex-direction: column; width: 100%; }
            .curved-btn { width: 100%; justify-content: center; }
            .curved-voucher-ribbon { border-radius: 16px; flex-direction: column; text-align: center; }
            .curved-admin-bar { border-radius: 16px; flex-direction: column; text-align: center; }
        }
    </style>
@endsection

@section('content')
@php
    // Lấy 3 sản phẩm thực tế từ Database
    $suggestedProducts = \App\Models\Product::where('is_active', true)
        ->with(['primaryCategory', 'primaryImage'])
        ->orderBy('id', 'desc')
        ->limit(3)
        ->get();

    // Mảng fallback ảnh thời trang có sẵn trong assets để đảm bảo 100% không bao giờ bị trắng ảnh
    $fallbackImages = [
        asset('clients/assets/img/clothes/ao-denim-nam-light-weight-002.avif'),
        asset('clients/assets/img/clothes/ao-denim-nam-light-weight-003.avif'),
        asset('clients/assets/img/clothes/ao-denim-nam-light-weight-004.avif'),
    ];
@endphp

<div class="nobi-404-page">
    {{-- Vòng tròn decor nền mờ ảo --}}
    <div class="decor-circle decor-circle-1"></div>
    <div class="decor-circle decor-circle-2"></div>

    <div class="nobi-404-shell">

        {{-- KHỐI CHÍNH NGUYÊN KHỐI BO TRÒN MỀM MẠI --}}
        <div class="curved-main-card">
            
            {{-- CẢNH VẬT: BONG BÓNG THỜI TRANG & KHINH KHÍ CẦU VỚI CÁC ĐƯỜNG CONG --}}
            <div class="curved-scene-bubble">
                <span class="mini-sparkle sparkle-1">✦</span>
                <span class="mini-sparkle sparkle-2">✦</span>
                <span class="mini-sparkle sparkle-3">★</span>
                
                {{-- SVG Khinh khí cầu thời trang bo tròn đáng yêu --}}
                <svg class="floating-balloon" viewBox="0 0 80 100" fill="none">
                    <ellipse cx="40" cy="38" rx="32" ry="36" fill="url(#balloonGrad)"/>
                    <path d="M16 34 C16 16, 64 16, 64 34 C64 58, 46 68, 40 74 C34 68, 16 58, 16 34 Z" fill="url(#balloonGrad2)"/>
                    <path d="M30 8 C33 22, 33 54, 40 74 C47 54, 47 22, 50 8 Z" fill="#ffffff" opacity="0.35"/>
                    <line x1="30" y1="74" x2="34" y2="86" stroke="#78716c" stroke-width="1.8"/>
                    <line x1="50" y1="74" x2="46" y2="86" stroke="#78716c" stroke-width="1.8"/>
                    <rect x="31" y="86" width="18" height="12" rx="4" fill="#ea580c"/>
                    <defs>
                        <linearGradient id="balloonGrad" x1="8" y1="8" x2="72" y2="72" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#ea580c"/>
                            <stop offset="1" stop-color="#f43f5e"/>
                        </linearGradient>
                        <linearGradient id="balloonGrad2" x1="16" y1="20" x2="64" y2="70" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#f97316"/>
                            <stop offset="1" stop-color="#fb7185"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>

            {{-- Con số 404 nghệ thuật --}}
            <div class="curved-number-badge">404</div>

            <div>
                <span class="curved-tag-pill">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>
                    TRANG KHÔNG TỒN TẠI HOẶC ĐÃ THAY ĐỔI
                </span>
            </div>

            <h1 class="curved-title">Ối, Bạn Đang Lạc Bước Khỏi Bản Đồ Thời Trang!</h1>
            
            <p class="curved-desc">
                Liên kết bạn vừa truy cập có thể đã được cập nhật hoặc chuyển sang bộ sưu tập mới.
                Đừng lo lắng, hãy để <strong>NOBI FASHION</strong> gợi ý cho bạn những mẫu trang phục hot trend nhất nhé!
            </p>

            {{-- THANH TÌM KIẾM BO TRÒN PILL THÂN THIỆN --}}
            <form action="{{ url('/shop') }}" method="GET" class="curved-search-form">
                <input type="text" name="keyword" class="curved-search-input" placeholder="Tìm kiếm áo khoác, áo phao, sơ mi, gile...">
                <button type="submit" class="curved-search-btn" title="Tìm kiếm">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
            </form>

            {{-- CÁC GỢI Ý CHIP PILL BO TRÒN --}}
            <div class="curved-tag-list">
                <span style="font-size: 12px; color: #a8a29e;">Gợi ý hot:</span>
                <a href="{{ url('/shop?keyword=Áo+phao') }}" class="curved-pill-chip">Áo phao nam</a>
                <a href="{{ url('/shop?keyword=Áo+khoác') }}" class="curved-pill-chip">Áo khoác nam</a>
                <a href="{{ url('/shop?keyword=Gile') }}" class="curved-pill-chip">Phao gile VIP</a>
                <a href="{{ route('client.blog.index') }}" class="curved-pill-chip">Mẹo phối đồ</a>
            </div>

            {{-- HỆ THỐNG NÚT BẤM CTA BO TRÒN ĐA DẠNG (INLINE SVGs KHÔNG BAO GIỜ LỖI ICON) --}}
            <div class="curved-cta-row">
                <a href="{{ route('client.home.index') }}" class="curved-btn curved-btn-dark">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Về Trang Chủ
                </a>
                <a href="{{ route('client.product.shop.index') }}" class="curved-btn curved-btn-coral">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    Mua Sắm Hàng Mới
                </a>
                <a href="{{ route('client.blog.index') }}" class="curved-btn curved-btn-light">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg>
                    Blog Xu Hướng
                </a>
                <a href="{{ route('client.page.contact') }}" class="curved-btn curved-btn-light">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
                    Hỗ Trợ 24/7
                </a>
                <button type="button" class="curved-btn curved-btn-light" onclick="window.history.length > 1 ? window.history.back() : window.location.href='/'">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Quay Lại
                </button>
            </div>

            {{-- HOẠT ĐỘNG TƯƠNG TÁC: VOUCHER AN ỦI BO TRÒN GỌN GÀNG --}}
            <div class="curved-voucher-ribbon">
                <span>🎁 <strong>Quà an ủi khi lạc lối:</strong> Nhận ngay ưu đãi giảm 10%</span>
                <span class="curved-code-tag" id="voucherCodeLabel">NOBI404</span>
                <button type="button" class="curved-copy-btn" id="btnCopyVoucher" onclick="copyVoucher()">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    <span id="copyVoucherText">Sao chép mã</span>
                </button>
            </div>

        </div>

        {{-- SẢN PHẨM GỢI Ý TỪ DATABASE THẬT VỚI HÌNH ẢNH RÕ ĐẸP (KHÔNG BAO GIỜ BỊ TRẮNG ẢNH) --}}
        @if(isset($suggestedProducts) && $suggestedProducts->isNotEmpty())
            <div class="curved-suggest-wrap">
                <div class="curved-section-header">
                    <h3 class="curved-section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="#d97706" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        Bộ Sưu Tập Đang Được Yêu Thích Nhất
                    </h3>
                    <a href="{{ route('client.product.shop.index') }}" class="curved-pill-chip" style="font-weight: 700; color: var(--curve-coral);">
                        Xem tất cả sản phẩm →
                    </a>
                </div>

                <div class="curved-grid-3">
                    @foreach($suggestedProducts as $index => $product)
                        @php
                            // Tìm ảnh sản phẩm: ưu tiên primaryImage, fallback description regex, fallback ảnh có sẵn trong assets
                            $imgSrc = null;
                            if ($product->primaryImage && $product->primaryImage->url) {
                                $imgSrc = str_starts_with($product->primaryImage->url, 'http')
                                    ? $product->primaryImage->url
                                    : asset('clients/assets/img/clothes/' . $product->primaryImage->url);
                            }
                            if (!$imgSrc && !empty($product->description)) {
                                preg_match('/src=[\"\']([^\"\']+)[\"\']/', $product->description, $matchImg);
                                if (!empty($matchImg[1])) {
                                    $imgSrc = $matchImg[1];
                                }
                            }
                            // Nếu vẫn chưa có ảnh, dùng ảnh trang phục thật từ assets
                            if (!$imgSrc) {
                                $imgSrc = $fallbackImages[$index % count($fallbackImages)];
                            }

                            $finalPrice = (float) ($product->sale_price ?? $product->price);
                        @endphp
                        <a href="{{ url('/san-pham/' . $product->slug) }}" class="curved-product-card">
                            <div class="curved-img-container">
                                <img src="{{ $imgSrc }}" alt="{{ $product->name }}" class="curved-prod-img" loading="lazy">
                            </div>
                            <div class="curved-prod-details">
                                <div class="curved-prod-cat">{{ $product->primaryCategory->name ?? 'NOBI FASHION' }}</div>
                                <div class="curved-prod-name" title="{{ $product->name }}">{{ $product->name }}</div>
                                <div class="curved-price-row">
                                    <span class="curved-final-price">{{ number_format($finalPrice, 0, ',', '.') }}₫</span>
                                    @if($product->sale_price && $product->sale_price < $product->price)
                                        <span class="curved-orig-price">{{ number_format($product->price, 0, ',', '.') }}₫</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- THANH QUẢN TRỊ VIÊN & LIÊN HỆ {Nguyễn Minh Đức} nguyenminhduc.id.vn BO TRÒN PILL --}}
        <div class="curved-admin-bar">
            <div>
                <span class="admin-pill-tag">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    Quản trị viên
                </span>
                <span>Hệ thống phát triển bởi: </span>
                <strong class="text-dark">Nguyễn Minh Đức</strong>
                <span>(Website: </span>
                <a href="https://nguyenminhduc.id.vn/" target="_blank" rel="noopener noreferrer" class="curved-admin-link" title="Website cá nhân của Nguyễn Minh Đức">
                    nguyenminhduc.id.vn 
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
                <span>)</span>
            </div>
            <div>
                <a href="https://nguyenminhduc.id.vn/" target="_blank" rel="noopener noreferrer" class="curved-pill-chip" style="font-weight: 700; color: #1c1917; display: inline-flex; align-items: center; gap: 5px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    Báo lỗi liên kết cho Admin
                </a>
            </div>
        </div>

    </div>
</div>
@endsection

@section('foot')
<script>
    // 1-Click sao chép mã Voucher với hiệu ứng phản hồi mượt mà
    function copyVoucher() {
        const code = document.getElementById('voucherCodeLabel').innerText;
        const textSpan = document.getElementById('copyVoucherText');
        const btn = document.getElementById('btnCopyVoucher');
        navigator.clipboard.writeText(code).then(() => {
            textSpan.innerText = 'Đã sao chép!';
            btn.style.background = '#10b981';
            setTimeout(() => {
                textSpan.innerText = 'Sao chép mã';
                btn.style.background = 'var(--curve-amber)';
            }, 3000);
        }).catch(() => {
            alert('Mã ưu đãi của bạn: ' + code);
        });
    }
</script>
@endsection
