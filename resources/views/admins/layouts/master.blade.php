<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') | {{ $settings->site_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('admins/css/custom.css') }}">
    @stack('styles')
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            color: #333;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: #f8f9fa;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 1px 0 3px rgba(0,0,0,0.05);
            z-index: 1000;
            border-right: 1px solid #e9ecef;
            transition: transform 0.3s ease, width 0.3s ease;
        }
        .ck-content .image>figcaption {
            min-height: 20px !important;
        }
        .sidebar.collapsed {
            transform: translateX(-100%);
        }
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 3px;
        }
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            background: white;
        }
        .sidebar-header img {
            max-width: 100%;
            height: auto;
            margin-bottom: 10px;
        }
        .sidebar-header h1 {
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 5px;
            color: #333;
        }
        .sidebar-header p {
            font-size: 12px;
            color: #6c757d;
        }
        .sidebar-menu {
            padding: 10px 0;
        }
        .menu-section {
            padding: 8px 15px;
            font-size: 11px;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }
        .menu-group {
            margin-bottom: 2px;
        }
        .menu-group-header {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: #495057;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 14px;
            font-weight: 500;
            user-select: none;
        }
        .menu-group-header:hover {
            background: #e9ecef;
        }
        .menu-group-header.active {
            background: #dee2e6;
            color: #212529;
        }
        .menu-group-header .menu-arrow {
            margin-left: auto;
            font-size: 12px;
            transition: transform 0.2s;
            color: #6c757d;
        }
        .menu-group-header.expanded .menu-arrow {
            transform: rotate(90deg);
        }
        .menu-group-items {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: white;
        }
        .menu-group-items.expanded {
            max-height: 1000px;
        }
        .menu-item {
            display: flex;
            align-items: center;
            padding: 8px 15px 8px 40px;
            color: #6c757d;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 13px;
            font-weight: 400;
        }
        .menu-item:hover {
            background: #f1f3f5;
            color: #212529;
        }
        .menu-item.active {
            background: #e7f5ff;
            color: #1971c2;
            font-weight: 500;
            border-left: 3px solid #1971c2;
        }
        .menu-item-icon {
            display: inline-block;
            width: 18px;
            margin-right: 10px;
            text-align: center;
            font-size: 14px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        .main-content.sidebar-collapsed {
            margin-left: 0;
        }
        
        /* Top Bar */
        .top-bar {
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .top-bar-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }
        .top-bar-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        /* Content Area */
        .content-area {
            background: transparent;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0 !important;
            }
            .main-content.sidebar-collapsed {
                margin-left: 0 !important;
            }
        }
        .menu-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f3f5;
            color: #495057;
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.2s;
            margin-right: 15px;
        }
        .menu-toggle:hover {
            background: #e9ecef;
            border-color: #ced4da;
        }
        
        /* Pagination Container */
        nav[role="navigation"] {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        /* Mobile pagination buttons */
        nav[role="navigation"] > div:first-child {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }
        
        nav[role="navigation"] > div:first-child a,
        nav[role="navigation"] > div:first-child span {
            padding: 10px 20px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            color: #475569;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
        }
        
        nav[role="navigation"] > div:first-child a:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #334155;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        nav[role="navigation"] > div:first-child span.cursor-default {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f8f9fa;
        }
        
        /* Desktop pagination container */
        nav[role="navigation"] > div:last-child {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        /* Showing text */
        nav[role="navigation"] p.text-sm {
            color: #64748b;
            font-size: 14px;
            font-weight: 400;
            margin: 0;
        }
        
        nav[role="navigation"] p.text-sm .font-medium {
            color: #334155;
            font-weight: 600;
        }
        
        /* Pagination buttons container - Target by multiple attributes */
        nav[role="navigation"] span.relative.z-0 {
            display: inline-flex;
            align-items: center;
            gap: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        /* All pagination buttons */
        nav[role="navigation"] span.relative.z-0 > * {
            margin: 0;
        }
        
        nav[role="navigation"] span.relative.z-0 a,
        nav[role="navigation"] span.relative.z-0 span {
            padding: 10px 16px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            border-right: none;
        }
        
        nav[role="navigation"] span.relative.z-0 > *:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }
        
        nav[role="navigation"] span.relative.z-0 > *:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            border-right: 1px solid #e2e8f0;
        }
        
        /* Hover states */
        nav[role="navigation"] span.relative.z-0 a:hover {
            background: #f1f5f9;
            color: #334155;
            z-index: 1;
            border-color: #cbd5e1;
        }
        
        /* Active page - Beautiful gradient */
        nav[role="navigation"] span[aria-current="page"] span,
        nav[role="navigation"] span.cursor-default:not([aria-disabled]) {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
            color: #fff !important;
            border-color: #6366f1 !important;
            font-weight: 600;
            z-index: 2;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.3);
        }
        
        /* Disabled buttons */
        nav[role="navigation"] span[aria-disabled="true"] span {
            opacity: 0.4;
            cursor: not-allowed;
            background: #f8f9fa !important;
            color: #94a3b8 !important;
        }
        
        /* SVG icons in buttons */
        nav[role="navigation"] svg {
            width: 20px;
            height: 20px;
        }
        
        /* Focus states for accessibility */
        nav[role="navigation"] a:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            z-index: 1;
        }

        /* CKEditor 5 Styles */
        .ck-editor__editable {
            min-height: 500px;
        }
        
        .ck-editor__editable_inline {
            min-height: 500px;
        }
        
        /* Responsive design */
        @media (max-width: 640px) {
            nav[role="navigation"] > div:last-child {
                flex-direction: column;
                align-items: stretch;
            }
            
            nav[role="navigation"] span.relative.z-0 {
                justify-content: center;
                flex-wrap: wrap;
            }
            
            nav[role="navigation"] p.text-sm {
                text-align: center;
                width: 100%;
            }
        }
    </style>
    @stack('head')
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div><img src="{{ asset('clients/assets/img/business/'. $settings->site_logo) }}" alt="{{ $settings->site_name }}" style="width: 100%; height: 100%;"></div>
            {{-- <h1>📊 Admin Panel</h1>
            <p style="color: white; text-align: center;">{{ $settings->site_name }}</p> --}}
        </div>
        <nav class="sidebar-menu">
            <div class="menu-section">Tổng Quan</div>
            <a href="{{ route('admin.dashboard') }}" class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span class="menu-item-icon">📊</span>
                Dashboard
            </a>
            
            <div class="menu-section">Sản Phẩm</div>
            <div class="menu-group">
                <div class="menu-group-header" data-group="products">
                    <span class="menu-item-icon">📦</span>
                    <span>Sản Phẩm</span>
                    <span class="menu-arrow">▶</span>
                </div>
                <div class="menu-group-items" id="products-group">
                    <a href="{{ route('admin.products.index') }}" class="menu-item {{ request()->routeIs('admin.products.index') && !request('status') ? 'active' : '' }}">
                        Tất cả sản phẩm
                    </a>
                    <a href="{{ route('admin.products.index', ['status' => 'active']) }}" class="menu-item {{ request()->routeIs('admin.products.index') && request('status') === 'active' ? 'active' : '' }}">
                        Đang bán
                    </a>
                    <a href="{{ route('admin.products.index', ['status' => 'inactive']) }}" class="menu-item {{ request()->routeIs('admin.products.index') && request('status') === 'inactive' ? 'active' : '' }}">
                        Tạm ẩn
                    </a>
                    <a href="{{ route('admin.products.import-excel') }}" class="menu-item {{ request()->routeIs('admin.products.import-excel*') ? 'active' : '' }}">
                        Nhập Excel
                    </a>
                    <a href="{{ route('admin.products.export-excel') }}" class="menu-item {{ request()->routeIs('admin.products.export-excel') ? 'active' : '' }}">
                        Xuất Excel
                    </a>
                </div>
            </div>
            <a href="{{ route('admin.categories.index') }}" class="menu-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                <span class="menu-item-icon">🏷️</span>
                Danh Mục
            </a>
            <a href="{{ route('admin.flash-sales.index') }}" class="menu-item {{ request()->routeIs('admin.flash-sales.*') ? 'active' : '' }}">
                <span class="menu-item-icon">⚡</span>
                Flash Sale
            </a>
            
            <div class="menu-section">Nội Dung</div>
            <a href="{{ route('admin.posts.index') }}" class="menu-item {{ request()->routeIs('admin.posts.*') ? 'active' : '' }}">
                <span class="menu-item-icon">📝</span>
                Bài viết
            </a>
            <a href="{{ route('admin.comments.index') }}" class="menu-item {{ request()->routeIs('admin.comments.*') ? 'active' : '' }}">
                <span class="menu-item-icon">💬</span>
                Bình luận
            </a>
            <a href="{{ route('admin.email-accounts.index') }}" class="menu-item {{ request()->routeIs('admin.email-accounts.*') ? 'active' : '' }}">
                <span class="menu-item-icon">📧</span>
                Email
            </a>
            <a href="{{ route('admin.tags.index') }}" class="menu-item {{ request()->routeIs('admin.tags.*') ? 'active' : '' }}">
                <span class="menu-item-icon">🏷️</span>
                Thẻ (Tags)
            </a>
            <a href="{{ route('admin.banners.index') }}" class="menu-item {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">
                <span class="menu-item-icon">🖼️</span>
                Banner
            </a>
            <a href="{{ route('admin.media.index') }}" class="menu-item {{ request()->routeIs('admin.media.*') ? 'active' : '' }}">
                <span class="menu-item-icon">🗂️</span>
                Media
            </a>
            <a href="{{ route('admin.sitemap.index') }}" class="menu-item {{ request()->routeIs('admin.sitemap.*') ? 'active' : '' }}">
                <span class="menu-item-icon">🗺️</span>
                Sitemap
            </a>
            
            <div class="menu-section">Đơn Hàng</div>
            <div class="menu-group">
                <div class="menu-group-header" data-group="orders">
                    <span class="menu-item-icon">📋</span>
                    <span>Đơn Hàng</span>
                    <span class="menu-arrow">▶</span>
                </div>
                <div class="menu-group-items" id="orders-group">
                    <a href="{{ route('admin.orders.index') }}" class="menu-item {{ request()->routeIs('admin.orders.index') && !request('status') && !request('delivery_status') ? 'active' : '' }}">
                        Tất cả đơn hàng
                    </a>
                    <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="menu-item {{ request()->routeIs('admin.orders.*') && request('status') === 'pending' ? 'active' : '' }}">
                        Chờ xử lý
                    </a>
                    <a href="{{ route('admin.orders.index', ['delivery_status' => 'shipped']) }}" class="menu-item {{ request()->routeIs('admin.orders.*') && request('delivery_status') === 'shipped' ? 'active' : '' }}">
                        Đang giao hàng
                    </a>
                    <a href="{{ route('admin.orders.index', ['status' => 'completed']) }}" class="menu-item {{ request()->routeIs('admin.orders.*') && request('status') === 'completed' ? 'active' : '' }}">
                        Hoàn thành
                    </a>
                </div>
            </div>
            <a href="{{ route('admin.carts.index') }}" class="menu-item {{ request()->routeIs('admin.carts.index') || (request()->routeIs('admin.carts.show') || request()->routeIs('admin.carts.edit')) ? 'active' : '' }}">
                <span class="menu-item-icon">🛒</span>
                Giỏ Hàng
            </a>
            <a href="{{ route('admin.carts.create-order.index') }}" class="menu-item {{ request()->routeIs('admin.carts.create-order.*') ? 'active' : '' }}">
                <span class="menu-item-icon">📦</span>
                Lên Đơn Hàng
            </a>
            
            <div class="menu-section">Khách Hàng</div>
            <a href="{{ route('admin.accounts.index') }}" class="menu-item {{ request()->routeIs('admin.accounts.*') ? 'active' : '' }}">
                <span class="menu-item-icon">👤</span>
                Tài khoản
            </a>
            <a href="{{ route('admin.newsletters.index') }}" class="menu-item {{ request()->routeIs('admin.newsletters.*') ? 'active' : '' }}">
                <span class="menu-item-icon">📧</span>
                <span style="flex: 1;">Newsletter</span>
                @php
                    try {
                        $pendingNewsletterCount = \App\Models\NewsletterSubscription::where('status', 'pending')->count();
                    } catch (\Exception $e) {
                        $pendingNewsletterCount = 0;
                    }
                @endphp
                @if($pendingNewsletterCount > 0)
                    <span style="background: #f59e0b; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px;">{{ $pendingNewsletterCount }}</span>
                @endif
            </a>
            <a href="{{ route('admin.contacts.index') }}" class="menu-item {{ request()->routeIs('admin.contacts.*') ? 'active' : '' }}">
                <span class="menu-item-icon">💬</span>
                <span style="flex: 1;">Liên Hệ</span>
                @php
                    try {
                        $newContactCount = \App\Models\Contact::where('status', 'new')->count();
                    } catch (\Exception $e) {
                        $newContactCount = 0;
                    }
                @endphp
                @if($newContactCount > 0)
                    <span style="background: #ef4444; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px; font-weight: 600;">{{ $newContactCount }}</span>
                @endif
            </a>
            <a href="{{ route('admin.addresses.index') }}" class="menu-item {{ request()->routeIs('admin.addresses.*') ? 'active' : '' }}">
                <span class="menu-item-icon">📍</span>
                Địa Chỉ Giao Hàng
            </a>
            
            <div class="menu-section">Khuyến Mãi</div>
            <a href="{{ route('admin.vouchers.index') }}" class="menu-item {{ request()->routeIs('admin.vouchers.*') ? 'active' : '' }}">
                <span class="menu-item-icon">🎫</span>
                Voucher
            </a>
            
            <div class="menu-section">Hệ Thống</div>
            <a href="{{ route('admin.settings.index') }}" class="menu-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <span class="menu-item-icon">⚙️</span>
                Cài Đặt
            </a>
            <div class="menu-group">
                <div class="menu-group-header" data-group="tools">
                    <span class="menu-item-icon">🛠️</span>
                    <span>Tools Cào Website</span>
                    <span class="menu-arrow">▶</span>
                </div>
                <div class="menu-group-items" id="tools-group">
                    <a href="{{ route('admin.canifa-crawler.index') }}" class="menu-item {{ request()->routeIs('admin.canifa-crawler.*') ? 'active' : '' }}">
                        canifa.com
                    </a>
                    <a href="{{ route('admin.routine-crawler.index') }}" class="menu-item {{ request()->routeIs('admin.routine-crawler.*') ? 'active' : '' }}">
                        routine.vn
                    </a>
                    <a href="{{ route('admin.onoff-crawler.index') }}" class="menu-item {{ request()->routeIs('admin.onoff-crawler.*') ? 'active' : '' }}">
                        onoff.vn
                    </a>
                    <a href="{{ route('admin.coolmate-crawler.index') }}" class="menu-item {{ request()->routeIs('admin.coolmate-crawler.*') ? 'active' : '' }}">
                        coolmate.me
                    </a>
                </div>
            </div>
            <a href="{{ route('admin.trash.index') }}" class="menu-item {{ request()->routeIs('admin.trash.*') ? 'active' : '' }}">
                <span class="menu-item-icon">🗑️</span>
                Thùng Rác
            </a>
            <a href="{{ url('/') }}" class="menu-item" target="_blank">
                <span class="menu-item-icon">🌐</span>
                Về Trang Chủ
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div style="display: flex; align-items: center;">
                <button class="menu-toggle" id="sidebarToggle" onclick="toggleSidebar()" title="Đóng/Mở menu">
                    <span id="toggleIcon">☰</span>
                </button>
                <span class="top-bar-title">@yield('page-title', 'Dashboard')</span>
            </div>
            <div class="top-bar-actions">
                <div class="user-info">
                    <div class="user-avatar">
                        @php
                            $user = auth('web')->user() ?? auth()->user();
                            $userInitial = $user ? strtoupper(substr($user->name ?? 'A', 0, 1)) : 'A';
                            $userName = $user ? ($user->name ?? 'Admin') : 'Admin';
                        @endphp
                        {{ $userInitial }}
                    </div>
                    <div>
                        <div style="font-size: 14px; font-weight: 600;">{{ $userName }}</div>
                        <div style="font-size: 12px; color: #666;">Quản trị viên</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            @yield('content')
        </div>
    </main>
    
    <div id="custom-toast-container" class="custom-toast-container"></div>

    <style>
        .custom-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            z-index: 9999;
            cursor: pointer;
        }

        .custom-toast {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 16px;
            color: #fff;
            max-width: 320px;
            opacity: 0;
            transform: translateX(100%);
            transition: transform 0.4s ease, opacity 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .custom-toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .custom-toast.success {
            background-color: #1c9a4a;
            /* xanh lá */
        }

        .custom-toast.error {
            background-color: #ef4444;
            /* đỏ */
        }

        .custom-toast.warning {
            background-color: #f59e0b;
            /* cam */
        }

        .custom-toast.info {
            background-color: #3b82f6;
            /* xanh dương */
        }

        .custom-toast-icon {
            font-size: 18px;
        }
    </style>

    <script>
        function showCustomToast(
            message = "Thông báo!",
            type = "info",
            duration = 5000
        ) {
            const container = document.getElementById("custom-toast-container");
            const toast = document.createElement("div");
            const icon = document.createElement("span");

            toast.className = `custom-toast ${type}`;
            icon.className = "custom-toast-icon";

            // Gán biểu tượng theo loại
            const icons = {
                success: "✅",
                error: "❌",
                warning: "⚠️",
                info: "💬",
            };
            icon.textContent = icons[type] || "🔔";

            toast.appendChild(icon);
            toast.appendChild(document.createTextNode(message));
            container.appendChild(toast);

            // Kích hoạt animation
            setTimeout(() => toast.classList.add("show"), 100);

            toast.addEventListener("click", () => {
                toast.classList.remove("show");
                setTimeout(() => {
                    container.removeChild(toast);
                }, 300);
                return;
            });

            // Gỡ thông báo sau duration
            setTimeout(() => {
                toast.classList.remove("show");
                setTimeout(() => {
                    container.removeChild(toast);
                }, 300);
                return;
            }, duration);
        }
        @php
            $alerts = [
                'success' => session('success'),
                'error'   => session('error'),
                'warning' => session('warning'),
                'info'    => session('info'),
            ];
        @endphp
        document.addEventListener("DOMContentLoaded", function() {
            let alerts = [];
            @foreach ($alerts as $type => $message)
                @if ($message)
                    alerts.push({type: '{{ $type }}', message: @json($message)});
                @endif
            @endforeach

            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    alerts.push({type: 'error', message: @json($error)});
                @endforeach
            @endif

            @if(request()->cookie('updated_account_success'))
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        showCustomToast(
                            "Cập nhật tài khoản thành công. Vui lòng đăng nhập lại.",
                            "success"
                        );
                    });
                </script>
            @endif

            alerts.forEach(a => showCustomToast(a.message, a.type));
        });
    </script>

    @php
        Cookie::queue(Cookie::forget('updated_account_success'));
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script>
        // Load sidebar state from localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleIcon = document.getElementById('toggleIcon');
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('sidebar-collapsed');
                toggleIcon.textContent = '☰';
            } else {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('sidebar-collapsed');
                toggleIcon.textContent = '✕';
            }
            
            // Initialize mobile sidebar state
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('open');
            }
        });

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleIcon = document.getElementById('toggleIcon');
            const isMobile = window.innerWidth <= 768;
            
            if (isMobile) {
                // Mobile: toggle open class
                sidebar.classList.toggle('open');
            } else {
                // Desktop: toggle collapsed class
                const isCollapsed = sidebar.classList.contains('collapsed');
                
                if (isCollapsed) {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('sidebar-collapsed');
                    toggleIcon.textContent = '✕';
                    localStorage.setItem('sidebarCollapsed', 'false');
                } else {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('sidebar-collapsed');
                    toggleIcon.textContent = '☰';
                    localStorage.setItem('sidebarCollapsed', 'true');
                }
            }
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
                if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });

        // Menu accordion functionality
        document.addEventListener('DOMContentLoaded', function() {
            const menuGroups = document.querySelectorAll('.menu-group-header');
            
            menuGroups.forEach(header => {
                header.addEventListener('click', function(e) {
                    e.preventDefault();
                    const groupId = this.getAttribute('data-group');
                    const items = document.getElementById(groupId + '-group');
                    const isExpanded = items.classList.contains('expanded');
                    
                    // Close all other groups
                    document.querySelectorAll('.menu-group-items').forEach(item => {
                        item.classList.remove('expanded');
                    });
                    document.querySelectorAll('.menu-group-header').forEach(h => {
                        h.classList.remove('expanded');
                    });
                    
                    // Toggle current group
                    if (!isExpanded) {
                        items.classList.add('expanded');
                        this.classList.add('expanded');
                    }
                });
            });

            // Auto-expand groups with active items
            document.querySelectorAll('.menu-item.active').forEach(activeItem => {
                const group = activeItem.closest('.menu-group-items');
                if (group) {
                    group.classList.add('expanded');
                    const header = group.previousElementSibling;
                    if (header) {
                        header.classList.add('expanded');
                    }
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <!-- CKEditor 5 -->
    <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/47.4.0/ckeditor5.css" crossorigin>
    <script src="https://cdn.ckeditor.com/ckeditor5/47.4.0/ckeditor5.umd.js" crossorigin></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/47.4.0/translations/vi.umd.js" crossorigin></script>
    <script src="{{ asset('admins/js/ckeditor-init.js?v=' . time()) }}"></script>
    @stack('scripts')
    
    <!-- Back to Top Button -->
    <button id="back-to-top" class="back-to-top-btn" title="Lên đầu trang" aria-label="Lên đầu trang">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 15l-6-6-6 6"/>
        </svg>
    </button>
    
    <style>
        .back-to-top-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
            opacity: 0;
            transform: translateY(20px);
        }
        
        .back-to-top-btn.show {
            display: flex;
            opacity: 1;
            transform: translateY(0);
        }
        
        .back-to-top-btn:hover {
            background: #0056b3;
            box-shadow: 0 6px 16px rgba(0, 123, 255, 0.4);
            transform: translateY(-2px);
        }
        
        .back-to-top-btn:active {
            transform: translateY(0);
        }
        
        .back-to-top-btn svg {
            width: 20px;
            height: 20px;
        }
        
        @media (max-width: 768px) {
            .back-to-top-btn {
                bottom: 20px;
                right: 20px;
                width: 45px;
                height: 45px;
            }
        }
    </style>
    
    <script>
        (function() {
            const backToTopBtn = document.getElementById('back-to-top');
            
            if (!backToTopBtn) return;
            
            // Hiển thị/ẩn nút khi scroll
            function toggleBackToTop() {
                if (window.pageYOffset > 300) {
                    backToTopBtn.classList.add('show');
                } else {
                    backToTopBtn.classList.remove('show');
                }
            }
            
            // Scroll smooth lên đầu trang
            backToTopBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            // Lắng nghe sự kiện scroll
            window.addEventListener('scroll', toggleBackToTop);
            
            // Kiểm tra ngay khi load trang
            toggleBackToTop();
        })();
    </script>
</body>
</html>

