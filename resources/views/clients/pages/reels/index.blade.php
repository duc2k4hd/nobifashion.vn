@extends('clients.layouts.master')

@section('body_class', 'nobifashion_reels_page')

@section('title', $seoTitle ?? 'Nobi Reels — Video Thời Trang & Xu Hướng Phối Đồ | Nobi Fashion')

@section('head')
    <link rel="stylesheet"
        href="{{ asset('clients/assets/css/reels.css') }}?v={{ filemtime(public_path('clients/assets/css/reels.css')) }}">
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="description"
        content="{{ $seoDescription ?? 'Xem video ngắn thời trang Nobi Reels, cập nhật xu hướng phối đồ và mua sắm thời trang chính hãng.' }}" />
    <link rel="canonical" href="{{ url('/reels') }}">
    <style>
        .nobifashion_reels_page,
        .nobifashion_reels_page body,
        .nobifashion_reels_page html {
            overflow: hidden !important;
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .nobifashion_reels_page #main-content {
            padding: 0 !important;
            margin: 0 !important;
            height: 100vh !important;
            height: 100dvh !important;
            max-width: 100% !important;
        }
    </style>
@endsection

@section('content')
    <div id="nobifashion_reels_app" class="nobifashion_reels_app" data-theme="light">
        <!-- A. FIXED HEADER -->
        <header class="nobifashion_reels_header" role="banner">
            <div class="nobifashion_reels_header_left">
                <a href="{{ url('/') }}" class="nobifashion_reels_brand"
                    aria-label="{{ $settings->site_name ?? 'Nobi Fashion' }} Trang chủ">
                    <img class="nobifashion_reels_brand_img"
                        src="{{ asset('clients/assets/img/business/' . ($settings->site_logo ?? 'nobifashion-logo.png')) }}"
                        alt="{{ $settings->site_name ?? 'Nobi Fashion' }}">
                </a>

                <nav class="nobifashion_reels_nav_tabs" aria-label="Điều hướng danh mục Reels">
                    <button type="button" class="nobifashion_reels_nav_tab nobifashion_reels_is_active" data-filter="all">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18" />
                            <line x1="7" y1="2" x2="7" y2="22" />
                            <line x1="17" y1="2" x2="17" y2="22" />
                            <line x1="2" y1="12" x2="22" y2="12" />
                        </svg>
                        <span>Tất cả</span>
                    </button>
                    <button type="button" class="nobifashion_reels_nav_tab" data-filter="trending">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                            <polyline points="17 6 23 6 23 12" />
                        </svg>
                        <span>Xu hướng</span>
                    </button>
                    <button type="button" class="nobifashion_reels_nav_tab" data-filter="new">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <polyline points="12 6 12 12 16 14" />
                        </svg>
                        <span>Mới ra mắt</span>
                    </button>
                    <button type="button" class="nobifashion_reels_nav_tab" data-filter="liked">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path
                                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                        </svg>
                        <span>Đã thích</span>
                    </button>
                    <button type="button" class="nobifashion_reels_nav_tab" data-filter="saved">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                        </svg>
                        <span>Đã lưu</span>
                    </button>
                </nav>
            </div>

            <!-- Search Bar -->
            <div class="nobifashion_reels_header_center">
                <div class="nobifashion_reels_search_box">
                    <div class="nobifashion_reels_search_input_wrap">
                        <svg class="nobifashion_reels_search_icon" width="15" height="15" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                        <input type="search" id="nobifashion_reels_search_input" class="nobifashion_reels_search_input"
                            placeholder="Tìm video, bộ sưu tập, #hashtag hoặc NTK..." autocomplete="off"
                            aria-label="Tìm kiếm trên Nobi Reels" />
                        <button type="button" id="nobifashion_reels_search_clear" class="nobifashion_reels_search_clear"
                            aria-label="Xóa từ khóa">✕</button>
                    </div>
                    <div id="nobifashion_reels_search_dropdown"
                        class="nobifashion_reels_search_dropdown nobifashion_reels_scrollable"></div>
                </div>
            </div>

            <!-- Header Right Controls -->
            <div class="nobifashion_reels_header_right">
                <button type="button" class="nobifashion_reels_icon_btn" data-action="open-cart"
                    aria-label="Giỏ hàng Nobi Fashion" title="Giỏ hàng">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.9">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <path d="M16 10a4 4 0 0 1-8 0" />
                    </svg>
                    <span id="nobifashion_reels_cart_badge" class="nobifashion_reels_badge"
                        style="display:none;">0</span>
                </button>

                <button type="button" class="nobifashion_reels_icon_btn" data-action="open-notifications"
                    aria-label="Thông báo" title="Thông báo">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.9">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                    </svg>
                    <span class="nobifashion_reels_badge">3</span>
                </button>

                <button type="button" id="nobifashion_reels_theme_btn" class="nobifashion_reels_icon_btn"
                    data-action="toggle-theme" aria-label="Chuyển đổi giao diện Sáng hoặc Tối"
                    title="Đổi giao diện Sáng / Tối"></button>

                <button type="button" class="nobifashion_reels_icon_btn" data-action="toggle-comments-panel"
                    aria-label="Ẩn hoặc hiện bảng bình luận" title="Đóng/Mở khung bình luận">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.9">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                        <line x1="15" y1="3" x2="15" y2="21" />
                    </svg>
                </button>

                <button type="button" class="nobifashion_reels_avatar_btn" data-action="open-profile"
                    aria-label="Tài khoản cá nhân" title="Menu cá nhân">
                    NB
                </button>
            </div>

            <!-- Popover for Notifications / Profile -->
            <div id="nobifashion_reels_popover" class="nobifashion_reels_popover"></div>
        </header>

        <!-- MAIN WORKSPACE (CENTER VIDEO STAGE + RIGHT COMMENTS SIDEBAR) -->
        <main id="nobifashion_reels_workspace" class="nobifashion_reels_workspace">
            <!-- B. CENTER VIDEO STAGE -->
            <section id="nobifashion_reels_stage" class="nobifashion_reels_stage_area"
                aria-label="Trình phát video dọc Nobi Reels">
                <!-- 9:16 Video Frame + Adjacent Action Rail -->
                <div class="nobifashion_reels_stage_center_group">
                    <div id="nobifashion_reels_viewport" class="nobifashion_reels_viewport_frame">
                        <div id="nobifashion_reels_track" class="nobifashion_reels_track"></div>
                    </div>

                    <aside id="nobifashion_reels_action_rail" class="nobifashion_reels_action_rail"
                        aria-label="Tương tác nhanh với video"></aside>
                </div>

                <!-- Desktop Up/Down Navigation Arrows -->
                <div class="nobifashion_reels_nav_arrows">
                    <button type="button" id="nobifashion_reels_prev_btn" class="nobifashion_reels_nav_arrow_btn"
                        data-action="nav-prev" aria-label="Video trước đó (ArrowUp)" title="Video trước (Phím lên)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <polyline points="18 15 12 9 6 15" />
                        </svg>
                    </button>
                    <button type="button" id="nobifashion_reels_next_btn" class="nobifashion_reels_nav_arrow_btn"
                        data-action="nav-next" aria-label="Video tiếp theo (ArrowDown)"
                        title="Video tiếp theo (Phím xuống)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <polyline points="6 9 12 15 18 9" />
                        </svg>
                    </button>
                </div>

                <!-- Empty State -->
                <div id="nobifashion_reels_empty_state" class="nobifashion_reels_empty_state">
                    <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.5">
                        <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18" />
                        <line x1="7" y1="2" x2="7" y2="22" />
                        <line x1="17" y1="2" x2="17" y2="22" />
                        <line x1="2" y1="12" x2="22" y2="12" />
                    </svg>
                    <h2 style="font-size:16px;font-weight:700;">Không tìm thấy video phù hợp</h2>
                    <p style="font-size:13px;color:var(--nobifashion_reels_text_secondary);max-width:320px;">
                        Chưa có video nào khớp với bộ lọc hoặc từ khóa tìm kiếm hiện tại của bạn.
                    </p>
                    <button type="button" class="nobifashion_reels_btn_primary" data-action="reset-home">
                        Xem toàn bộ Nobi Reels
                    </button>
                </div>
            </section>

            <!-- C. RIGHT SIDEBAR (CREATOR + PRODUCT PREVIEW + COMMENTS) -->
            <aside id="nobifashion_reels_sidebar" class="nobifashion_reels_sidebar"
                aria-label="Thông tin bài đăng và bình luận"></aside>
        </main>

        <!-- DRAWERS & MODALS -->
        <div id="nobifashion_reels_backdrop" class="nobifashion_reels_backdrop"></div>
        <aside id="nobifashion_reels_product_drawer" class="nobifashion_reels_drawer"
            aria-label="Chi tiết sản phẩm trong video"></aside>
        <aside id="nobifashion_reels_cart_drawer" class="nobifashion_reels_drawer" aria-label="Giỏ hàng Nobi Fashion">
        </aside>
        <div id="nobifashion_reels_modal" class="nobifashion_reels_modal" role="dialog" aria-modal="true"
            aria-label="Hộp thoại tùy chọn"></div>
        <div id="nobifashion_reels_toast" class="nobifashion_reels_toast" role="status" aria-live="polite"></div>
    </div>
@endsection

@section('foot')
    <script>
        window.__INITIAL_REELS__ = {!! json_encode($initialReels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};
        window.__REELS_NEXT_CURSOR__ = {!! json_encode($nextCursor) !!};
        window.__REELS_HAS_MORE__ = {{ $hasMore ? 'true' : 'false' }};
    </script>
    <script defer
        src="{{ asset('clients/assets/js/reels.js') }}?v={{ filemtime(public_path('clients/assets/js/reels.js')) }}">
    </script>
@endsection
