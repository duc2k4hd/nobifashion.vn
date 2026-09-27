@extends('admins.layouts.master')

@section('title', 'Dashboard Quản trị')
@section('page-title', 'Trung tâm Thống kê & Phân tích Dữ liệu')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/dashboard-icon.png') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@push('styles')
<style>
    /* ==========================================================================
       DASHBOARD ENTERPRISE THEME (Compact, Clean, Low Padding/Margin, 6-8px Radius)
       ========================================================================== */
    :root {
        --dash-bg: #f8fafc;
        --dash-card-bg: #ffffff;
        --dash-border: #e2e8f0;
        --dash-border-subtle: #f1f5f9;
        --dash-text-main: #0f172a;
        --dash-text-muted: #64748b;
        --dash-primary: #2563eb;
        --dash-success: #10b981;
        --dash-warning: #f59e0b;
        --dash-danger: #ef4444;
        --dash-info: #0284c7;
        --dash-indigo: #6366f1;
    }

    .dash-wrap {
        padding: 0 4px 20px 4px;
        color: var(--dash-text-main);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    /* Top Command Toolbar */
    .dash-toolbar {
        background: #ffffff;
        border: 1px solid var(--dash-border);
        border-radius: 6px;
        padding: 10px 14px;
        margin-bottom: 12px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .dash-toolbar-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    .pulse-dot {
        width: 8px;
        height: 8px;
        background-color: #10b981;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulse-green 2s infinite;
    }

    @keyframes pulse-green {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    .dash-btn-group {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .dash-btn {
        padding: 5px 11px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 5px;
        border: 1px solid var(--dash-border);
        background: #ffffff;
        color: #334155;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.15s ease;
        text-decoration: none;
    }

    .dash-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #cbd5e1;
    }

    .dash-btn.active {
        background: #0f172a;
        color: #ffffff;
        border-color: #0f172a;
    }

    .dash-btn-primary {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
    }

    .dash-btn-primary:hover {
        background: #1d4ed8;
        color: #ffffff;
    }

    /* KPI Metric Cards */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 12px;
        margin-bottom: 12px;
    }

    .kpi-card {
        background: #ffffff;
        border: 1px solid var(--dash-border);
        border-radius: 6px;
        padding: 12px 14px;
        position: relative;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .kpi-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    }

    .kpi-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
    }

    .kpi-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
    }

    .kpi-icon-wrap {
        width: 28px;
        height: 28px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
    }

    .kpi-value {
        font-size: 22px;
        font-weight: 800;
        line-height: 1.2;
        color: #0f172a;
        letter-spacing: -0.5px;
        margin-bottom: 4px;
    }

    .kpi-subtext {
        font-size: 11px;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .kpi-footer-link {
        margin-top: 8px;
        padding-top: 6px;
        border-top: 1px dashed var(--dash-border-subtle);
        font-size: 11px;
        font-weight: 600;
        color: #2563eb;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        cursor: pointer;
    }

    .kpi-footer-link:hover {
        color: #1d4ed8;
        text-decoration: underline;
    }

    /* Cards & Containers */
    .dash-card {
        background: #ffffff;
        border: 1px solid var(--dash-border);
        border-radius: 6px;
        margin-bottom: 12px;
        overflow: hidden;
    }

    .dash-card-header {
        padding: 10px 14px;
        border-bottom: 1px solid var(--dash-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
        background: #fafafa;
    }

    .dash-card-title {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .dash-card-body {
        padding: 12px 14px;
    }

    /* Compact Chart Containers */
    .chart-box-main {
        position: relative;
        height: 270px;
        width: 100%;
    }

    .chart-box-donut {
        position: relative;
        height: 220px;
        width: 100%;
    }

    .chart-box-bar {
        position: relative;
        height: 220px;
        width: 100%;
    }

    /* Compact Data Table */
    .dash-table-wrap {
        overflow-x: auto;
    }

    .dash-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        text-align: left;
    }

    .dash-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 8px 10px;
        border-bottom: 1px solid var(--dash-border);
        white-space: nowrap;
    }

    .dash-table td {
        padding: 8px 10px;
        border-bottom: 1px solid var(--dash-border-subtle);
        vertical-align: middle;
        color: #1e293b;
    }

    .dash-table tbody tr:hover {
        background: #f8fafc;
    }

    .dash-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 7px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
        line-height: 1.4;
    }

    .dash-badge-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .dash-badge-info { background: #f0f9ff; color: #075985; border: 1px solid #bae6fd; }
    .dash-badge-warning { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
    .dash-badge-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .dash-badge-slate { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
    .dash-badge-primary { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }

    .code-tag {
        font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
        font-size: 11px;
        background: #f1f5f9;
        padding: 2px 5px;
        border-radius: 4px;
        color: #334155;
        border: 1px solid #e2e8f0;
    }

    /* Modals Minimalist */
    .modal-content {
        border-radius: 8px !important;
        border: 1px solid var(--dash-border);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        border-bottom: 1px solid var(--dash-border);
        padding: 12px 16px;
        background: #f8fafc;
        border-top-left-radius: 7px;
        border-top-right-radius: 7px;
    }

    .modal-body {
        padding: 16px;
        font-size: 12px;
    }

    .modal-footer {
        border-top: 1px solid var(--dash-border);
        padding: 10px 16px;
        background: #fafafa;
        border-bottom-left-radius: 7px;
        border-bottom-right-radius: 7px;
    }

    .quick-meta-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px;
        margin-bottom: 10px;
    }

    .quick-meta-label {
        font-size: 10px;
        text-transform: uppercase;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 2px;
    }

    .quick-meta-val {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
    }

    .action-btn-mini {
        padding: 3px 7px;
        font-size: 11px;
        border-radius: 4px;
        border: 1px solid var(--dash-border);
        background: #ffffff;
        color: #334155;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        text-decoration: none;
    }

    .action-btn-mini:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
</style>
@endpush

@section('content')
<div class="dash-wrap">

    {{-- TOP COMMAND TOOLBAR --}}
    <div class="dash-toolbar">
        <div>
            <h1 class="dash-toolbar-title">
                <span class="pulse-dot"></span>
                Tổng quan Hoạt động & Chỉ số Doanh nghiệp
            </h1>
            <span class="text-muted" style="font-size: 11px;">
                Dữ liệu phân tích trực tiếp từ Database • Cập nhật lúc {{ \Carbon\Carbon::now()->format('H:i:s d/m/Y') }}
            </span>
        </div>

        <div class="dash-btn-group">
            <button type="button" class="dash-btn" data-bs-toggle="modal" data-bs-target="#revenueDetailModal">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Phân tích Doanh thu
            </button>
            <button type="button" class="dash-btn" data-bs-toggle="modal" data-bs-target="#inventoryModal">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7.5 4.27 9 5.15M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
                Kiểm kho hàng hóa
            </button>
            <button type="button" class="dash-btn" data-bs-toggle="modal" data-bs-target="#customerModal">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Khách hàng
            </button>
            <button type="button" class="dash-btn" data-bs-toggle="modal" data-bs-target="#seoContentModal">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10M6 10h10"/></svg>
                Nội dung & SEO
            </button>
            <button type="button" class="dash-btn dash-btn-primary" onclick="window.location.reload();">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                Làm mới
            </button>
        </div>
    </div>

    {{-- ROW 1: 4 COMPACT METRIC CARDS --}}
    <div class="kpi-grid">
        {{-- Card 1: Doanh thu --}}
        <div class="kpi-card">
            <div>
                <div class="kpi-header">
                    <span class="kpi-title">Doanh thu luỹ kế</span>
                    <div class="kpi-icon-wrap" style="background: #ecfdf5; color: #10b981;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                </div>
                <div class="kpi-value text-success">{{ number_format($kpis['total_revenue']) }}₫</div>
                <div class="kpi-subtext">
                    <span>Thực thu: <strong class="text-dark">{{ number_format($kpis['completed_revenue']) }}₫</strong></span>
                    <span>• Đang xử lý: <strong class="text-primary">{{ number_format($kpis['processing_revenue']) }}₫</strong></span>
                </div>
            </div>
            <a class="kpi-footer-link" data-bs-toggle="modal" data-bs-target="#revenueDetailModal">
                Phân tích dòng tiền & Top đơn hàng →
            </a>
        </div>

        {{-- Card 2: Đơn hàng --}}
        <div class="kpi-card">
            <div>
                <div class="kpi-header">
                    <span class="kpi-title">Đơn hàng & Tỷ lệ hoàn tất</span>
                    <div class="kpi-icon-wrap" style="background: #eff6ff; color: #2563eb;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                    </div>
                </div>
                <div class="kpi-value">{{ $kpis['total_orders'] }} <span style="font-size: 13px; font-weight: 500; color: #64748b;">đơn hàng</span></div>
                <div class="kpi-subtext">
                    <span class="dash-badge dash-badge-success py-0 px-1">Xong: {{ $kpis['completed_orders'] }}</span>
                    <span class="dash-badge dash-badge-info py-0 px-1">Đang xử lý: {{ $kpis['processing_orders'] }}</span>
                    <span class="dash-badge dash-badge-warning py-0 px-1">Chờ: {{ $kpis['pending_orders'] }}</span>
                    <span class="dash-badge dash-badge-danger py-0 px-1">Hủy: {{ $kpis['cancelled_orders'] }}</span>
                </div>
            </div>
            <a class="kpi-footer-link" href="#recentOrdersSection">
                Xem danh sách 18 đơn chi tiết bên dưới ↓
            </a>
        </div>

        {{-- Card 3: Kho hàng & Sản phẩm --}}
        <div class="kpi-card">
            <div>
                <div class="kpi-header">
                    <span class="kpi-title">Kho hàng & Giá trị tồn</span>
                    <div class="kpi-icon-wrap" style="background: #fef3c7; color: #d97706;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7.5 4.27 9 5.15M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/></svg>
                    </div>
                </div>
                <div class="kpi-value">{{ number_format($kpis['total_stock']) }} <span style="font-size: 13px; font-weight: 500; color: #64748b;">chiếc trong kho</span></div>
                <div class="kpi-subtext">
                    <span>{{ $kpis['total_products'] }} sản phẩm</span>
                    <span>• Giá trị kho: <strong class="text-dark">{{ number_format($kpis['inventory_value']) }}₫</strong></span>
                </div>
            </div>
            <a class="kpi-footer-link" data-bs-toggle="modal" data-bs-target="#inventoryModal">
                Chi tiết tồn kho từng sản phẩm →
            </a>
        </div>

        {{-- Card 4: Thành viên, Content & SEO --}}
        <div class="kpi-card">
            <div>
                <div class="kpi-header">
                    <span class="kpi-title">Hệ thống & Tương tác</span>
                    <div class="kpi-icon-wrap" style="background: #f3e8ff; color: #7e22ce;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                </div>
                <div class="kpi-value">{{ $kpis['total_customers'] }} <span style="font-size: 13px; font-weight: 500; color: #64748b;">thành viên</span></div>
                <div class="kpi-subtext">
                    <span>{{ $kpis['total_posts'] }} bài viết ({{ $kpis['total_post_views'] }} views)</span>
                    <span>• {{ $kpis['total_contacts'] }} liên hệ</span>
                </div>
            </div>
            <a class="kpi-footer-link" data-bs-toggle="modal" data-bs-target="#seoContentModal">
                Xem bài viết, SEO 301 & Liên hệ →
            </a>
        </div>
    </div>

    {{-- ROW 2: CHARTS (AREA REVENUE CHART + ORDER STATUS DONUT) --}}
    <div class="row g-2 mb-2">
        {{-- Chart 1: Main Trend (Area / Line Chart) --}}
        <div class="col-lg-8">
            <div class="dash-card mb-2">
                <div class="dash-card-header">
                    <h2 class="dash-card-title">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        Xu hướng Doanh thu & Đơn hàng theo Ngày phát sinh
                    </h2>
                    <div class="dash-btn-group">
                        <button type="button" class="dash-btn active" id="btnChartRev" onclick="switchMainChart('rev')">Doanh thu (₫)</button>
                        <button type="button" class="dash-btn" id="btnChartOrd" onclick="switchMainChart('ord')">Số đơn (Đơn)</button>
                        <button type="button" class="dash-btn" id="btnChartBoth" onclick="switchMainChart('both')">Kết hợp cả 2</button>
                    </div>
                </div>
                <div class="dash-card-body">
                    <div class="chart-box-main">
                        <canvas id="mainTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart 2: Order Status Distribution (Donut Chart) --}}
        <div class="col-lg-4">
            <div class="dash-card mb-2">
                <div class="dash-card-header">
                    <h2 class="dash-card-title">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
                        Cơ cấu Trạng thái Đơn hàng
                    </h2>
                    <span class="dash-badge dash-badge-slate">Tổng: {{ $kpis['total_orders'] }} đơn</span>
                </div>
                <div class="dash-card-body">
                    <div class="chart-box-donut">
                        <canvas id="orderStatusChart"></canvas>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top" style="font-size: 11px;">
                        <span class="text-muted">AOV (Giá trị TB/đơn):</span>
                        <strong class="text-dark">{{ number_format($kpis['aov']) }}₫</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ROW 3: 3 DIVERSE CHARTS (Top Products Bar, Payment Methods Bar, Delivery Polar/Donut) --}}
    <div class="row g-2 mb-2">
        {{-- Chart 3: Top Products (Horizontal Bar Chart) --}}
        <div class="col-lg-4">
            <div class="dash-card mb-2">
                <div class="dash-card-header">
                    <h2 class="dash-card-title">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        Top Sản phẩm Bán chạy
                    </h2>
                    <button type="button" class="action-btn-mini" data-bs-toggle="modal" data-bs-target="#inventoryModal">Tất cả</button>
                </div>
                <div class="dash-card-body">
                    <div class="chart-box-bar">
                        <canvas id="topProductsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart 4: Payment Analysis (Grouped/Stacked Bar Chart) --}}
        <div class="col-lg-4">
            <div class="dash-card mb-2">
                <div class="dash-card-header">
                    <h2 class="dash-card-title">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        Thanh toán & Phương thức
                    </h2>
                    <span class="dash-badge dash-badge-info">8 Đã thanh toán</span>
                </div>
                <div class="dash-card-body">
                    <div class="chart-box-bar">
                        <canvas id="paymentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart 5: Delivery & Shipping (Donut/Polar Chart) --}}
        <div class="col-lg-4">
            <div class="dash-card mb-2">
                <div class="dash-card-header">
                    <h2 class="dash-card-title">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#7e22ce" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        Tiến độ Vận chuyển GHN
                    </h2>
                    <span class="dash-badge dash-badge-slate">Đối soát</span>
                </div>
                <div class="dash-card-body">
                    <div class="chart-box-bar">
                        <canvas id="deliveryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ROW 4: RECENT ORDERS TABLE (WITH INSTANT POPUP ON CLICK) --}}
    <div class="dash-card mb-2" id="recentOrdersSection">
        <div class="dash-card-header">
            <h2 class="dash-card-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Danh sách Đơn hàng Gần đây (Bấm dòng bất kỳ để xem nhanh chi tiết)
            </h2>
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="orderTableSearch" class="form-control form-control-sm" style="font-size: 11px; width: 180px; height: 28px;" placeholder="Tìm mã đơn, tên, sđt...">
                <a href="{{ route('admin.orders.index') }}" class="dash-btn dash-btn-primary" style="padding: 4px 8px; font-size: 11px;">Tất cả đơn →</a>
            </div>
        </div>
        <div class="dash-table-wrap">
            <table class="dash-table" id="ordersTable">
                <thead>
                    <tr>
                        <th style="width: 140px;">Mã đơn</th>
                        <th>Khách hàng & SĐT</th>
                        <th>Thanh toán</th>
                        <th>Trạng thái đơn</th>
                        <th>Giao hàng</th>
                        <th>Tổng tiền</th>
                        <th>Ngày tạo</th>
                        <th class="text-end" style="width: 120px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $ord)
                        <tr class="order-row cursor-pointer" data-order='@json($ord)' style="cursor: pointer;" onclick="openOrderModal(this)">
                            <td>
                                <span class="code-tag fw-bold text-primary">{{ $ord['code'] }}</span>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $ord['receiver_name'] }}</div>
                                <div class="text-muted" style="font-size: 11px;">{{ $ord['receiver_phone'] }}</div>
                            </td>
                            <td>
                                @if($ord['payment_status'] === 'paid')
                                    <span class="dash-badge dash-badge-success">Đã thanh toán</span>
                                @elseif($ord['payment_status'] === 'pending')
                                    <span class="dash-badge dash-badge-warning">Chờ thanh toán</span>
                                @else
                                    <span class="dash-badge dash-badge-danger">Thất bại / Hủy</span>
                                @endif
                                <div class="text-muted" style="font-size: 10px; margin-top: 2px;">{{ strtoupper($ord['payment_method'] ?? 'COD') }}</div>
                            </td>
                            <td>
                                @if($ord['status'] === 'completed')
                                    <span class="dash-badge dash-badge-success">Hoàn thành</span>
                                @elseif($ord['status'] === 'processing')
                                    <span class="dash-badge dash-badge-info">Đang xử lý</span>
                                @elseif($ord['status'] === 'pending')
                                    <span class="dash-badge dash-badge-warning">Chờ duyệt</span>
                                @else
                                    <span class="dash-badge dash-badge-danger">Đã hủy</span>
                                @endif
                            </td>
                            <td>
                                @if($ord['delivery_status'] === 'delivered')
                                    <span class="dash-badge dash-badge-success">Đã giao</span>
                                @elseif($ord['delivery_status'] === 'shipping')
                                    <span class="dash-badge dash-badge-info">Đang giao</span>
                                @elseif($ord['delivery_status'] === 'cancelled')
                                    <span class="dash-badge dash-badge-danger">Đã hủy giao</span>
                                @else
                                    <span class="dash-badge dash-badge-slate">Chờ vận chuyển</span>
                                @endif
                            </td>
                            <td>
                                <strong class="text-dark">{{ number_format($ord['final_price']) }}₫</strong>
                                <div class="text-muted" style="font-size: 10px;">{{ $ord['items_count'] }} món</div>
                            </td>
                            <td>
                                <div>{{ $ord['created_at_formatted'] }}</div>
                                <div class="text-muted" style="font-size: 10px;">{{ $ord['created_at_relative'] }}</div>
                            </td>
                            <td class="text-end" onclick="event.stopPropagation();">
                                <button type="button" class="action-btn-mini text-primary" onclick="openOrderModalFromBtn(this)">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Xem nhanh
                                </button>
                                <a href="{{ route('admin.orders.show', $ord['id']) }}" class="action-btn-mini text-muted" title="Trang quản lý đơn">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Chưa có đơn hàng nào trong hệ thống</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ROW 5: CATALOG INVENTORY & CONTENT/SEO --}}
    <div class="row g-2">
        {{-- Product Catalog Inventory Table --}}
        <div class="col-lg-6">
            <div class="dash-card mb-2">
                <div class="dash-card-header">
                    <h2 class="dash-card-title">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        Kiểm soát Tồn kho & Sản phẩm đang bán
                    </h2>
                    <a href="{{ route('admin.products.index') }}" class="action-btn-mini">Quản lý kho →</a>
                </div>
                <div class="dash-table-wrap">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Tên sản phẩm</th>
                                <th>SKU</th>
                                <th>Danh mục</th>
                                <th>Giá bán</th>
                                <th>Tồn kho</th>
                                <th>Giá trị tồn</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($catalogProducts as $prod)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.products.edit', $prod['id']) }}" class="fw-bold text-dark text-decoration-none hover-primary">
                                            {{ $prod['name'] }}
                                        </a>
                                    </td>
                                    <td><span class="code-tag">{{ $prod['sku'] }}</span></td>
                                    <td><span class="dash-badge dash-badge-slate">{{ $prod['category_name'] }}</span></td>
                                    <td><strong>{{ number_format($prod['price']) }}₫</strong></td>
                                    <td>
                                        @if($prod['stock_quantity'] > 20)
                                            <span class="dash-badge dash-badge-success">{{ $prod['stock_quantity'] }} chiếc</span>
                                        @else
                                            <span class="dash-badge dash-badge-warning">{{ $prod['stock_quantity'] }} chiếc (sắp hết)</span>
                                        @endif
                                    </td>
                                    <td><strong class="text-primary">{{ number_format($prod['inventory_value']) }}₫</strong></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-3 text-muted">Chưa có sản phẩm nào</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Top Viewed Posts & SEO / Contacts --}}
        <div class="col-lg-6">
            <div class="dash-card mb-2">
                <div class="dash-card-header">
                    <h2 class="dash-card-title">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#7e22ce" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                        Bài viết & Hiệu suất SEO Content
                    </h2>
                    <a href="{{ route('admin.posts.index') }}" class="action-btn-mini">Quản lý bài viết →</a>
                </div>
                <div class="dash-table-wrap">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Tiêu đề bài viết</th>
                                <th>Lượt xem</th>
                                <th>Trạng thái</th>
                                <th>Ngày đăng</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topPosts as $p)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 280px;" title="{{ $p->title }}">
                                            {{ $p->title }}
                                        </div>
                                        <div class="text-muted" style="font-size: 10px;">/{{ $p->slug }}</div>
                                    </td>
                                    <td>
                                        <span class="dash-badge dash-badge-primary">
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                            {{ number_format($p->views) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="dash-badge dash-badge-success">{{ ucfirst($p->status ?? 'published') }}</span>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($p->created_at)->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center py-3 text-muted">Chưa có bài viết</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ==========================================================================
     MODALS / POPUPS (CHI TIẾT TRỰC QUAN TỨC THÌ 0MS)
     ========================================================================== --}}

{{-- POPUP 1: XEM NHANH CHI TIẾT ĐƠN HÀNG (QUICK ORDER MODAL) --}}
<div class="modal fade" id="quickOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold" style="font-size: 14px;" id="modalOrderCodeTitle">
                        Chi tiết Đơn hàng: ORD-XXXXXX
                    </h5>
                    <span class="text-muted" style="font-size: 11px;" id="modalOrderDate">Đặt lúc: --</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Status & Meta Badges --}}
                <div class="d-flex flex-wrap gap-2 mb-3 pb-2 border-bottom">
                    <span id="modalOrderStatusBadge" class="dash-badge dash-badge-info">Trạng thái</span>
                    <span id="modalOrderPaymentBadge" class="dash-badge dash-badge-success">Thanh toán</span>
                    <span id="modalOrderDeliveryBadge" class="dash-badge dash-badge-slate">Giao hàng</span>
                    <span id="modalOrderPartner" class="dash-badge dash-badge-primary">Đối tác vận chuyển</span>
                </div>

                {{-- Customer & Delivery Info --}}
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="quick-meta-box h-100">
                            <div class="quick-meta-label">Thông tin Người nhận hàng</div>
                            <div class="quick-meta-val" id="modalReceiverName">Nguyễn Văn A</div>
                            <div style="font-size: 11px; margin-top: 3px;">
                                <div><i class="bi bi-telephone text-muted me-1"></i><span id="modalReceiverPhone">--</span></div>
                                <div><i class="bi bi-envelope text-muted me-1"></i><span id="modalReceiverEmail">--</span></div>
                                <div class="mt-1"><i class="bi bi-geo-alt text-muted me-1"></i><span id="modalReceiverAddress" class="fw-semibold">--</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="quick-meta-box h-100">
                            <div class="quick-meta-label">Thanh toán & Ghi chú</div>
                            <div style="font-size: 11px;">
                                <div>Phương thức: <strong id="modalPaymentMethod" class="text-dark">--</strong></div>
                                <div>Mã vận đơn: <strong id="modalTrackingCode" class="code-tag">--</strong></div>
                                <div class="mt-2 text-muted">Ghi chú khách: <span id="modalCustomerNote" class="text-dark fst-italic">--</span></div>
                                <div class="text-muted">Ghi chú Admin: <span id="modalAdminNote" class="text-dark fst-italic">--</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Items Table --}}
                <div class="dash-card mb-3">
                    <div class="dash-card-header py-1 px-2">
                        <span class="dash-card-title" style="font-size: 11px;">Danh sách sản phẩm trong đơn</span>
                    </div>
                    <div class="dash-table-wrap">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Tên sản phẩm</th>
                                    <th>SKU</th>
                                    <th class="text-center">Số lượng</th>
                                    <th class="text-end">Đơn giá</th>
                                    <th class="text-end">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody id="modalOrderItemsBody">
                                {{-- Rendered by JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Price Breakdown Summary --}}
                <div class="p-2 rounded bg-light border" style="font-size: 12px;">
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Tiền hàng (Tạm tính):</span>
                        <strong id="modalSubtotal">0₫</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Phí giao hàng:</span>
                        <span id="modalShippingFee">0₫</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Giảm giá / Voucher:</span>
                        <span id="modalDiscount" class="text-danger">0₫</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-top mt-1 pt-1 fw-bold fs-6">
                        <span>Tổng thanh toán cuối cùng:</span>
                        <span id="modalFinalPrice" class="text-success">0₫</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <a href="#" id="modalOrderLink" class="btn btn-sm btn-primary">Chuyển sang trang quản lý đơn →</a>
            </div>
        </div>
    </div>
</div>

{{-- POPUP 2: PHÂN TÍCH TÀI CHÍNH & DOANH THU CHI TIẾT --}}
<div class="modal fade" id="revenueDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="font-size: 14px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" class="me-1"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Báo cáo Phân tích Tài chính & Dòng tiền Toàn diện
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-sm-4">
                        <div class="quick-meta-box">
                            <div class="quick-meta-label">Tổng Doanh thu (Đơn hợp lệ)</div>
                            <div class="quick-meta-val text-success">{{ number_format($kpis['total_revenue']) }}₫</div>
                            <div class="text-muted" style="font-size: 10px;">{{ $kpis['total_orders'] - $kpis['cancelled_orders'] }} đơn thành công & đang xử lý</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="quick-meta-box">
                            <div class="quick-meta-label">Đã thanh toán thực thu</div>
                            <div class="quick-meta-val text-primary">{{ number_format($kpis['completed_revenue']) }}₫</div>
                            <div class="text-muted" style="font-size: 10px;">{{ $kpis['completed_orders'] }} đơn hoàn thành 100%</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="quick-meta-box">
                            <div class="quick-meta-label">Doanh số Đã Hủy / Thất thoát</div>
                            <div class="quick-meta-val text-danger">{{ number_format($kpis['cancelled_revenue']) }}₫</div>
                            <div class="text-muted" style="font-size: 10px;">{{ $kpis['cancelled_orders'] }} đơn bị hủy</div>
                        </div>
                    </div>
                </div>

                <div class="dash-card mb-3">
                    <div class="dash-card-header py-1 px-2">
                        <span class="dash-card-title" style="font-size: 11px;">Bảng kê các đơn hàng đóng góp doanh thu lớn nhất</span>
                    </div>
                    <div class="dash-table-wrap">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Trạng thái</th>
                                    <th>Thanh toán</th>
                                    <th class="text-end">Giá trị đơn</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentOrders->sortByDesc('final_price')->take(6) as $o)
                                    <tr>
                                        <td><span class="code-tag">{{ $o['code'] }}</span></td>
                                        <td>{{ $o['receiver_name'] }}</td>
                                        <td>
                                            @if($o['status'] === 'completed')
                                                <span class="dash-badge dash-badge-success">Hoàn thành</span>
                                            @elseif($o['status'] === 'processing')
                                                <span class="dash-badge dash-badge-info">Đang xử lý</span>
                                            @else
                                                <span class="dash-badge dash-badge-danger">Đã hủy</span>
                                            @endif
                                        </td>
                                        <td>{{ strtoupper($o['payment_method']) }}</td>
                                        <td class="text-end"><strong class="text-success">{{ number_format($o['final_price']) }}₫</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

{{-- POPUP 3: TỒN KHO & SẢN PHẨM CHI TIẾT --}}
<div class="modal fade" id="inventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="font-size: 14px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" class="me-1"><path d="m7.5 4.27 9 5.15M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/></svg>
                    Bảng Quản lý Tồn kho & Định giá Sản phẩm Hệ thống
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-sm-3">
                        <div class="quick-meta-box">
                            <div class="quick-meta-label">Tổng tồn kho</div>
                            <div class="quick-meta-val text-primary">{{ number_format($kpis['total_stock']) }} chiếc</div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="quick-meta-box">
                            <div class="quick-meta-label">Tổng giá trị tồn kho</div>
                            <div class="quick-meta-val text-success">{{ number_format($kpis['inventory_value']) }}₫</div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="quick-meta-box">
                            <div class="quick-meta-label">Mã hàng hoạt động</div>
                            <div class="quick-meta-val text-dark">{{ $kpis['active_products'] }} / {{ $kpis['total_products'] }} sp</div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="quick-meta-box">
                            <div class="quick-meta-label">Danh mục phân loại</div>
                            <div class="quick-meta-val text-info">{{ $kpis['total_categories'] }} danh mục</div>
                        </div>
                    </div>
                </div>

                <div class="dash-table-wrap">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Mã SKU</th>
                                <th>Tên sản phẩm</th>
                                <th>Danh mục chính</th>
                                <th class="text-end">Đơn giá bán</th>
                                <th class="text-center">Tồn kho hiện tại</th>
                                <th class="text-end">Tổng giá trị tồn</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($catalogProducts as $prod)
                                <tr>
                                    <td><span class="code-tag">{{ $prod['sku'] }}</span></td>
                                    <td><strong>{{ $prod['name'] }}</strong></td>
                                    <td><span class="dash-badge dash-badge-slate">{{ $prod['category_name'] }}</span></td>
                                    <td class="text-end">{{ number_format($prod['price']) }}₫</td>
                                    <td class="text-center">
                                        <span class="dash-badge dash-badge-success fs-6 py-1 px-2">{{ $prod['stock_quantity'] }}</span>
                                    </td>
                                    <td class="text-end text-primary fw-bold">{{ number_format($prod['inventory_value']) }}₫</td>
                                    <td class="text-center">
                                        @if($prod['is_active'])
                                            <span class="dash-badge dash-badge-success">Đang bán</span>
                                        @else
                                            <span class="dash-badge dash-badge-slate">Tạm ẩn</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.products.edit', $prod['id']) }}" class="action-btn-mini text-primary">Sửa kho</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <a href="{{ route('admin.products.create') }}" class="btn btn-sm btn-primary">+ Thêm sản phẩm mới</a>
            </div>
        </div>
    </div>
</div>

{{-- POPUP 4: KHÁCH HÀNG & THÀNH VIÊN CHI TIẾT --}}
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="font-size: 14px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" class="me-1"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    Bảng Thống kê Khách hàng & Giá trị Trọn đời (LTV)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="dash-table-wrap">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Họ và Tên</th>
                                <th>Số điện thoại</th>
                                <th>Email</th>
                                <th class="text-center">Số đơn đã đặt</th>
                                <th class="text-end">Tổng tiền chi tiêu</th>
                                <th>Lần mua gần nhất</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topCustomers as $cust)
                                <tr>
                                    <td><strong class="text-dark">{{ $cust['name'] }}</strong></td>
                                    <td><span class="code-tag">{{ $cust['phone'] }}</span></td>
                                    <td>{{ $cust['email'] }}</td>
                                    <td class="text-center">
                                        <span class="dash-badge dash-badge-primary">{{ $cust['order_count'] }} đơn</span>
                                    </td>
                                    <td class="text-end"><strong class="text-success">{{ number_format($cust['total_spent']) }}₫</strong></td>
                                    <td>{{ $cust['last_order'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-3 text-muted">Chưa có khách hàng</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

{{-- POPUP 5: BÀI VIẾT, CONTENT & SEO 301 --}}
<div class="modal fade" id="seoContentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="font-size: 14px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#7e22ce" stroke-width="2" class="me-1"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/></svg>
                    Hiệu suất Nội dung, Blog & Hệ thống SEO 301
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-sm-4">
                        <div class="quick-meta-box">
                            <div class="quick-meta-label">Tổng số bài viết</div>
                            <div class="quick-meta-val text-primary">{{ $kpis['total_posts'] }} bài</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="quick-meta-box">
                            <div class="quick-meta-label">Tổng lượt đọc views</div>
                            <div class="quick-meta-val text-success">{{ number_format($kpis['total_post_views']) }} lượt</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="quick-meta-box">
                            <div class="quick-meta-label">Chuyển hướng 301 SEO</div>
                            <div class="quick-meta-val text-dark">{{ $kpis['total_redirects'] }} rules ({{ $kpis['total_redirect_hits'] }} hits)</div>
                        </div>
                    </div>
                </div>

                <div class="dash-card mb-2">
                    <div class="dash-card-header py-1 px-2">
                        <span class="dash-card-title" style="font-size: 11px;">Toàn bộ bài viết tin tức & Lượt đọc</span>
                    </div>
                    <div class="dash-table-wrap">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Tiêu đề bài viết</th>
                                    <th>Đường dẫn slug</th>
                                    <th>Lượt xem</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topPosts as $p)
                                    <tr>
                                        <td><strong>{{ $p->title }}</strong></td>
                                        <td><code>/{{ $p->slug }}</code></td>
                                        <td><span class="dash-badge dash-badge-info">{{ $p->views }}</span></td>
                                        <td><span class="dash-badge dash-badge-success">{{ $p->status }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <a href="{{ route('admin.redirects.index') }}" class="btn btn-sm btn-outline-primary">Quản lý Redirect 301</a>
                <a href="{{ route('admin.posts.index') }}" class="btn btn-sm btn-primary">Quản lý Bài viết</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // =========================================================================
    // CHART.JS 4.4 INITIALIZATION & DYNAMIC SWITCHERS
    // =========================================================================
    const dailyLabels = @json($trendDailyLabels);
    const dailyRevenue = @json($trendDailyRevenue);
    const dailyOrders = @json($trendDailyOrders);

    // Chart 1: Main Area Line Chart
    let mainChartInstance = null;
    const mainCtx = document.getElementById('mainTrendChart');
    if (mainCtx) {
        const ctx2d = mainCtx.getContext('2d');
        const gradientBlue = ctx2d.createLinearGradient(0, 0, 0, 250);
        gradientBlue.addColorStop(0, 'rgba(37, 99, 235, 0.25)');
        gradientBlue.addColorStop(1, 'rgba(37, 99, 235, 0.00)');

        mainChartInstance = new Chart(ctx2d, {
            type: 'line',
            data: {
                labels: dailyLabels,
                datasets: [{
                    label: 'Doanh thu (₫)',
                    data: dailyRevenue,
                    borderColor: '#2563eb',
                    backgroundColor: gradientBlue,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#2563eb',
                    pointBorderWidth: 2,
                    yAxisID: 'y'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed.y !== null) {
                                    if (context.dataset.yAxisID === 'y2' || context.dataset.label.includes('đơn')) {
                                        label += context.parsed.y + ' đơn';
                                    } else {
                                        label += new Intl.NumberFormat('vi-VN').format(context.parsed.y) + '₫';
                                    }
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            font: { size: 11 },
                            callback: function(val) {
                                if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M₫';
                                if (val >= 1000) return (val / 1000).toFixed(0) + 'K₫';
                                return val + '₫';
                            }
                        }
                    }
                }
            }
        });
    }

    function switchMainChart(mode) {
        if (!mainChartInstance) return;

        document.getElementById('btnChartRev').classList.remove('active');
        document.getElementById('btnChartOrd').classList.remove('active');
        document.getElementById('btnChartBoth').classList.remove('active');

        if (mode === 'rev') {
            document.getElementById('btnChartRev').classList.add('active');
            mainChartInstance.data.datasets = [{
                label: 'Doanh thu (₫)',
                data: dailyRevenue,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                yAxisID: 'y'
            }];
            delete mainChartInstance.options.scales.y2;
            mainChartInstance.options.scales.y.display = true;
        } else if (mode === 'ord') {
            document.getElementById('btnChartOrd').classList.add('active');
            mainChartInstance.data.datasets = [{
                label: 'Số lượng đơn hàng',
                data: dailyOrders,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.15)',
                borderWidth: 2,
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                yAxisID: 'y'
            }];
            delete mainChartInstance.options.scales.y2;
            mainChartInstance.options.scales.y.ticks.callback = function(val) { return val + ' đơn'; };
        } else if (mode === 'both') {
            document.getElementById('btnChartBoth').classList.add('active');
            mainChartInstance.data.datasets = [
                {
                    label: 'Doanh thu (₫)',
                    data: dailyRevenue,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.05)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.35,
                    yAxisID: 'y'
                },
                {
                    label: 'Số đơn',
                    data: dailyOrders,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.35,
                    yAxisID: 'y2'
                }
            ];
            mainChartInstance.options.scales.y = {
                type: 'linear',
                display: true,
                position: 'left',
                grid: { color: '#f1f5f9' },
                ticks: {
                    callback: function(val) { return (val / 1000000).toFixed(1) + 'M₫'; }
                }
            };
            mainChartInstance.options.scales.y2 = {
                type: 'linear',
                display: true,
                position: 'right',
                grid: { drawOnChartArea: false },
                ticks: {
                    callback: function(val) { return val + ' đơn'; }
                }
            };
        }
        mainChartInstance.update();
    }

    // Chart 2: Order Status Donut Chart
    const statusCtx = document.getElementById('orderStatusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: @json($orderStatusDist['labels']),
                datasets: [{
                    data: @json($orderStatusDist['data']),
                    backgroundColor: @json($orderStatusDist['colors']),
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            font: { size: 11 },
                            padding: 8
                        }
                    }
                }
            }
        });
    }

    // Chart 3: Top Products Horizontal Bar Chart
    const topProdCtx = document.getElementById('topProductsChart');
    if (topProdCtx) {
        new Chart(topProdCtx, {
            type: 'bar',
            data: {
                labels: @json($chartProductLabels),
                datasets: [{
                    label: 'Đã bán / Số lượng',
                    data: @json($chartProductSold),
                    backgroundColor: '#d97706',
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { size: 10 } }
                    },
                    y: {
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }

    // Chart 4: Payment Methods & Status Bar Chart
    const paymentCtx = document.getElementById('paymentChart');
    if (paymentCtx) {
        new Chart(paymentCtx, {
            type: 'bar',
            data: {
                labels: ['Đã thanh toán', 'Chờ xử lý', 'Thất bại'],
                datasets: [{
                    label: 'Số đơn',
                    data: [
                        {{ $paymentStatusCounts['paid'] ?? 0 }},
                        {{ $paymentStatusCounts['pending'] ?? 0 }},
                        {{ $paymentStatusCounts['failed'] ?? 0 }}
                    ],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 2, font: { size: 10 } },
                        grid: { color: '#f1f5f9' }
                    },
                    x: { ticks: { font: { size: 10 } } }
                }
            }
        });
    }

    // Chart 5: Delivery Status
    const deliveryCtx = document.getElementById('deliveryChart');
    if (deliveryCtx) {
        new Chart(deliveryCtx, {
            type: 'doughnut',
            data: {
                labels: ['Đã giao thành công', 'Đang vận chuyển', 'Chờ chuyển hàng', 'Đã hủy giao'],
                datasets: [{
                    data: [
                        {{ $deliveryCounts['delivered'] ?? 0 }},
                        {{ $deliveryCounts['shipping'] ?? 0 }},
                        {{ $deliveryCounts['pending'] ?? 0 }},
                        {{ $deliveryCounts['cancelled'] ?? 0 }}
                    ],
                    backgroundColor: ['#10b981', '#0284c7', '#64748b', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 10 }, padding: 6 }
                    }
                }
            }
        });
    }

    // =========================================================================
    // INSTANT QUICK VIEW MODAL LOGIC (0ms Latency)
    // =========================================================================
    let orderModalInstance = null;
    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('quickOrderModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            orderModalInstance = new bootstrap.Modal(modalEl);
        }

        // Live Table Search
        const searchInput = document.getElementById('orderTableSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const term = e.target.value.toLowerCase().trim();
                const rows = document.querySelectorAll('#ordersTable tbody tr.order-row');
                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(term) ? '' : 'none';
                });
            });
        }
    });

    function openOrderModalFromBtn(btn) {
        const tr = btn.closest('tr.order-row');
        if (tr) openOrderModal(tr);
    }

    function openOrderModal(row) {
        const raw = row.getAttribute('data-order');
        if (!raw) return;
        const ord = JSON.parse(raw);

        // Header
        document.getElementById('modalOrderCodeTitle').innerText = 'Chi tiết Đơn hàng: ' + ord.code;
        document.getElementById('modalOrderDate').innerText = 'Ngày tạo: ' + ord.created_at_formatted + ' (' + ord.created_at_relative + ')';

        // Badges
        const statusBadge = document.getElementById('modalOrderStatusBadge');
        if (ord.status === 'completed') {
            statusBadge.className = 'dash-badge dash-badge-success';
            statusBadge.innerText = 'Trạng thái: Hoàn thành';
        } else if (ord.status === 'processing') {
            statusBadge.className = 'dash-badge dash-badge-info';
            statusBadge.innerText = 'Trạng thái: Đang xử lý';
        } else if (ord.status === 'pending') {
            statusBadge.className = 'dash-badge dash-badge-warning';
            statusBadge.innerText = 'Trạng thái: Chờ duyệt';
        } else {
            statusBadge.className = 'dash-badge dash-badge-danger';
            statusBadge.innerText = 'Trạng thái: Đã hủy';
        }

        const payBadge = document.getElementById('modalOrderPaymentBadge');
        payBadge.className = (ord.payment_status === 'paid') ? 'dash-badge dash-badge-success' : 'dash-badge dash-badge-warning';
        payBadge.innerText = (ord.payment_status === 'paid') ? 'Đã thanh toán (' + (ord.payment_method || 'COD').toUpperCase() + ')' : 'Chưa thanh toán (' + (ord.payment_method || 'COD').toUpperCase() + ')';

        const delBadge = document.getElementById('modalOrderDeliveryBadge');
        delBadge.className = (ord.delivery_status === 'delivered') ? 'dash-badge dash-badge-success' : 'dash-badge dash-badge-slate';
        delBadge.innerText = 'Giao hàng: ' + (ord.delivery_status || 'Chờ giao');

        document.getElementById('modalOrderPartner').innerText = 'Vận chuyển: ' + (ord.shipping_partner || 'GHN').toUpperCase();

        // Customer
        document.getElementById('modalReceiverName').innerText = ord.receiver_name || 'Khách vãng lai';
        document.getElementById('modalReceiverPhone').innerText = ord.receiver_phone || '---';
        document.getElementById('modalReceiverEmail').innerText = ord.receiver_email || '---';
        document.getElementById('modalReceiverAddress').innerText = ord.shipping_address || 'Tại cửa hàng';

        // Meta
        document.getElementById('modalPaymentMethod').innerText = (ord.payment_method || 'Chuyển khoản').toUpperCase();
        document.getElementById('modalTrackingCode').innerText = ord.shipping_tracking_code || 'Chưa phát hành';
        document.getElementById('modalCustomerNote').innerText = ord.customer_note || 'Không có';
        document.getElementById('modalAdminNote').innerText = ord.admin_note || '---';

        // Items
        const tbody = document.getElementById('modalOrderItemsBody');
        tbody.innerHTML = '';
        if (ord.items && ord.items.length > 0) {
            ord.items.forEach((item, idx) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${idx + 1}</td>
                    <td><strong class="text-dark">${item.name}</strong></td>
                    <td><span class="code-tag">${item.sku}</span></td>
                    <td class="text-center"><span class="dash-badge dash-badge-primary">${item.quantity}</span></td>
                    <td class="text-end">${new Intl.NumberFormat('vi-VN').format(item.price)}₫</td>
                    <td class="text-end"><strong>${new Intl.NumberFormat('vi-VN').format(item.total_price)}₫</strong></td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-2 text-muted">Không có thông tin chi tiết sản phẩm</td></tr>';
        }

        // Pricing
        document.getElementById('modalSubtotal').innerText = new Intl.NumberFormat('vi-VN').format(ord.total_price) + '₫';
        document.getElementById('modalShippingFee').innerText = new Intl.NumberFormat('vi-VN').format(ord.shipping_fee) + '₫';
        document.getElementById('modalDiscount').innerText = '-' + new Intl.NumberFormat('vi-VN').format(ord.discount || ord.voucher_discount || 0) + '₫';
        document.getElementById('modalFinalPrice').innerText = new Intl.NumberFormat('vi-VN').format(ord.final_price) + '₫';

        // Link
        document.getElementById('modalOrderLink').href = '/admin/orders/' + ord.id;

        // Show modal
        if (orderModalInstance) {
            orderModalInstance.show();
        } else {
            const fallbackModal = new bootstrap.Modal(document.getElementById('quickOrderModal'));
            fallbackModal.show();
        }
    }
</script>
@endpush
@endsection
