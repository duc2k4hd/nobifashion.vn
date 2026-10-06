@extends('admins.layouts.master')

@section('title', 'Quản lý Banners')
@section('page-title', 'Quản lý Banners')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/banners-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
<style>
    :root {
        --nobi-primary: #2563eb;
        --nobi-primary-hover: #1d4ed8;
        --nobi-card-bg: #ffffff;
        --nobi-border: #e2e8f0;
    }

    /* Page Header */
    .nobi-banner-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 24px;
    }
    .nobi-banner-title-box h2 {
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .nobi-banner-title-box p {
        margin: 4px 0 0;
        font-size: 13.5px;
        color: #64748b;
    }

    /* Stat KPI Cards */
    .nobi-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .nobi-stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .nobi-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
    }
    .nobi-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .nobi-stat-info {
        flex: 1;
    }
    .nobi-stat-num {
        font-size: 22px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }
    .nobi-stat-label {
        font-size: 12.5px;
        color: #64748b;
        font-weight: 500;
    }

    /* Control Toolbar */
    .nobi-toolbar {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 14px 18px;
        margin-bottom: 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }
    .nobi-filter-form {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        flex: 1;
    }
    .nobi-input-group {
        position: relative;
        min-width: 220px;
    }
    .nobi-input-group i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 13px;
        pointer-events: none;
    }
    .nobi-input-group input {
        width: 100%;
        padding: 8px 12px 8px 34px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 13px;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .nobi-input-group input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        outline: none;
    }
    .nobi-select {
        padding: 8px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 13px;
        background-color: #ffffff;
        color: #334155;
        cursor: pointer;
        transition: border-color 0.15s ease;
    }
    .nobi-select:focus {
        border-color: #2563eb;
        outline: none;
    }

    /* View Switcher */
    .nobi-view-switcher {
        display: flex;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 8px;
        gap: 2px;
    }
    .nobi-view-btn {
        border: none;
        background: transparent;
        color: #64748b;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
    }
    .nobi-view-btn.active {
        background: #ffffff;
        color: #0f172a;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    /* Drag & Drop Sortable Styles */
    .nobi-drag-handle {
        cursor: grab;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        background: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(8px);
        color: #ffffff;
        font-size: 13px;
        transition: all 0.2s ease;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .nobi-drag-handle:hover {
        background: #2563eb;
        color: #ffffff;
        transform: scale(1.08);
    }
    .nobi-drag-handle:active {
        cursor: grabbing;
    }
    .nobi-table-drag-handle {
        cursor: grab;
        padding: 4px;
        transition: color 0.15s ease;
    }
    .nobi-table-drag-handle:hover {
        color: #2563eb !important;
    }
    .nobi-sortable-ghost {
        opacity: 0.35 !important;
        border: 2px dashed #2563eb !important;
        background: #eff6ff !important;
        box-shadow: none !important;
    }
    .nobi-sortable-chosen {
        box-shadow: 0 16px 36px rgba(37, 99, 235, 0.22) !important;
        transform: scale(1.02);
        z-index: 100;
    }
    .nobi-sortable-drag {
        opacity: 0.95 !important;
        cursor: grabbing !important;
    }

    /* Grid Layout */
    .nobi-banner-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 20px;
    }
    @media (max-width: 640px) {
        .nobi-banner-grid {
            grid-template-columns: 1fr;
        }
    }

    .nobi-card-item {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.25s ease;
        position: relative;
    }
    .nobi-card-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        border-color: #cbd5e1;
    }

    /* Thumbnail Area */
    .nobi-card-media {
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 9;
        background: #0f172a;
        overflow: hidden;
    }
    .nobi-card-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        transition: transform 0.4s ease;
        display: block;
    }
    .nobi-card-item:hover .nobi-card-img {
        transform: scale(1.03);
    }
    .nobi-card-media-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(0,0,0,0.4) 0%, transparent 40%, rgba(0,0,0,0.65) 100%);
        opacity: 0.85;
        transition: opacity 0.2s ease;
    }
    .nobi-card-media-top {
        position: absolute;
        top: 12px;
        left: 12px;
        right: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 2;
    }
    .nobi-card-order-badge {
        background: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(8px);
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
        letter-spacing: 0.5px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .nobi-card-media-actions {
        position: absolute;
        bottom: 12px;
        right: 12px;
        display: flex;
        gap: 6px;
        z-index: 2;
        opacity: 0;
        transform: translateY(6px);
        transition: all 0.2s ease;
    }
    .nobi-card-item:hover .nobi-card-media-actions {
        opacity: 1;
        transform: translateY(0);
    }
    .nobi-media-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.92);
        color: #0f172a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        transition: transform 0.15s ease, background-color 0.15s ease;
        text-decoration: none;
    }
    .nobi-media-btn:hover {
        background: #ffffff;
        color: #2563eb;
        transform: scale(1.08);
    }

    /* Card Body */
    .nobi-card-body {
        padding: 16px 18px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }
    .nobi-card-title-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 8px;
        margin-bottom: 8px;
    }
    .nobi-card-title {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        line-height: 1.35;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
    }
    .nobi-card-desc {
        font-size: 13px;
        color: #64748b;
        margin: 0 0 12px 0;
        line-height: 1.5;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        min-height: 39px;
    }
    .nobi-card-meta {
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 12px;
        color: #64748b;
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 14px;
    }
    .nobi-meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .nobi-meta-item i {
        width: 14px;
        color: #94a3b8;
    }
    .nobi-meta-link {
        color: #2563eb;
        text-decoration: none;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .nobi-meta-link:hover {
        text-decoration: underline;
    }

    /* Card Footer */
    .nobi-card-footer {
        padding-top: 12px;
        border-top: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: auto;
    }
    .nobi-card-actions {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Modern Toggle Switch */
    .nobi-toggle {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        user-select: none;
        font-size: 12.5px;
        font-weight: 600;
    }
    .nobi-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }
    .nobi-toggle-slider {
        position: relative;
        width: 38px;
        height: 22px;
        background-color: #cbd5e1;
        border-radius: 20px;
        transition: background-color 0.25s ease;
    }
    .nobi-toggle-slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        border-radius: 50%;
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }
    .nobi-toggle input:checked + .nobi-toggle-slider {
        background-color: #10b981;
    }
    .nobi-toggle input:checked + .nobi-toggle-slider:before {
        transform: translateX(16px);
    }
    .nobi-toggle-text {
        font-size: 12px;
        color: #475569;
    }

    /* Badges */
    .nobi-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11.5px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    /* Buttons */
    .nobi-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .nobi-btn-primary {
        background: #2563eb;
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
    }
    .nobi-btn-primary:hover {
        background: #1d4ed8;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
    }
    .nobi-btn-outline {
        background: #ffffff;
        color: #334155;
        border-color: #cbd5e1;
    }
    .nobi-btn-outline:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
    }
    .nobi-btn-icon {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #475569;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .nobi-btn-icon:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .nobi-btn-icon-danger:hover {
        background: #fee2e2;
        color: #dc2626;
        border-color: #fca5a5;
    }

    /* Table View Styles */
    .nobi-table-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
    }
    .nobi-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }
    .nobi-table th {
        background: #f8fafc;
        padding: 12px 16px;
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
    }
    .nobi-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13.5px;
        color: #1e293b;
        vertical-align: middle;
    }
    .nobi-table tr:hover td {
        background: #f8fafc;
    }
    .nobi-table-thumb {
        width: 110px;
        height: 62px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        display: block;
    }

    /* Live Preview Modal */
    .nobi-preview-modal-body {
        padding: 24px;
        background: #0f172a;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }
    .nobi-preview-img-box {
        max-width: 100%;
        border-radius: 12px;
        overflow: hidden;
        border: 2px solid rgba(255, 255, 255, 0.15);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
    }
    .nobi-preview-img-box img {
        display: block;
        max-width: 100%;
        height: auto;
    }

    /* Toast notification */
    .nobi-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: #0f172a;
        color: #ffffff;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 13.5px;
        font-weight: 500;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 9999;
        transform: translateY(100px);
        opacity: 0;
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease;
    }
    .nobi-toast.show {
        transform: translateY(0);
        opacity: 1;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    {{-- Header --}}
    <div class="nobi-banner-header">
        <div class="nobi-banner-title-box">
            <h2>
                <i class="fa-solid fa-images text-primary"></i>
                Quản lý Banners & Hero Slider
            </h2>
            <p>Tùy biến hình ảnh quảng bá thương hiệu, Coverflow 3D trang chủ và banner khuyến mãi</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.banners.create') }}" class="nobi-btn nobi-btn-primary">
                <i class="fa-solid fa-plus"></i>
                Thêm banner mới
            </a>
        </div>
    </div>

    {{-- KPI Stat Cards --}}
    <div class="nobi-stats-grid">
        <div class="nobi-stat-card">
            <div class="nobi-stat-icon" style="background:#eff6ff;color:#2563eb;">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div class="nobi-stat-info">
                <div class="nobi-stat-num">{{ $stats['total'] ?? $banners->total() }}</div>
                <div class="nobi-stat-label">Tổng số banner</div>
            </div>
        </div>

        <div class="nobi-stat-card">
            <div class="nobi-stat-icon" style="background:#f5f3ff;color:#7c3aed;">
                <i class="fa-solid fa-cube"></i>
            </div>
            <div class="nobi-stat-info">
                <div class="nobi-stat-num">{{ $stats['home'] ?? 0 }}</div>
                <div class="nobi-stat-label">Hero 3D Trang chủ</div>
            </div>
        </div>

        <div class="nobi-stat-card">
            <div class="nobi-stat-icon" style="background:#ecfdf5;color:#059669;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="nobi-stat-info">
                <div class="nobi-stat-num">{{ $stats['active'] ?? 0 }}</div>
                <div class="nobi-stat-label">Đang hiển thị</div>
            </div>
        </div>

        <div class="nobi-stat-card">
            <div class="nobi-stat-icon" style="background:#fef2f2;color:#dc2626;">
                <i class="fa-solid fa-eye-slash"></i>
            </div>
            <div class="nobi-stat-info">
                <div class="nobi-stat-num">{{ $stats['inactive'] ?? 0 }}</div>
                <div class="nobi-stat-label">Đang tạm ẩn</div>
            </div>
        </div>
    </div>

    {{-- Filter Toolbar & View Switcher --}}
    <div class="nobi-toolbar">
        <form method="GET" action="{{ route('admin.banners.index') }}" class="nobi-filter-form" id="bannerFilterForm">
            <div class="nobi-input-group">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="keyword" placeholder="Tìm theo tên banner..." value="{{ request('keyword') }}">
            </div>

            <select name="position" class="nobi-select" onchange="document.getElementById('bannerFilterForm').submit()">
                <option value="">-- Tất cả vị trí --</option>
                @foreach($positions ?? config('banners.positions', []) as $key => $label)
                    <option value="{{ $key }}" {{ request('position') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="nobi-select" onchange="document.getElementById('bannerFilterForm').submit()">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Đang hiển thị</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Đang tạm ẩn</option>
            </select>

            <button type="submit" class="nobi-btn nobi-btn-outline">
                <i class="fa-solid fa-filter"></i> Lọc
            </button>

            @if(request()->hasAny(['keyword', 'position', 'status']))
                <a href="{{ route('admin.banners.index') }}" class="nobi-btn nobi-btn-outline text-danger">
                    <i class="fa-solid fa-rotate-left"></i> Xóa lọc
                </a>
            @endif

            {{-- Drag Hint --}}
            <span class="badge bg-light text-primary border px-3 py-2 d-none d-md-inline-flex align-items-center gap-1">
                <i class="fa-solid fa-arrows-up-down-left-right"></i> Kéo thả box để đổi thứ tự
            </span>
        </form>

        {{-- Switch View: Grid / Table --}}
        <div class="nobi-view-switcher">
            <button type="button" class="nobi-view-btn active" id="btnViewGrid" title="Chế độ xem dạng lưới">
                <i class="fa-solid fa-grip"></i> Lưới
            </button>
            <button type="button" class="nobi-view-btn" id="btnViewTable" title="Chế độ xem dạng bảng">
                <i class="fa-solid fa-list"></i> Bảng
            </button>
        </div>
    </div>

    {{-- Content View Container --}}
    <div id="bannerContainer">
        {{-- 1. GRID VIEW (Default) --}}
        <div class="nobi-banner-grid" id="bannerGridView">
            @forelse($banners as $banner)
                @php
                    $allPositions = $positions ?? config('banners.positions', []);
                    $allBadges = $positionBadges ?? config('banners.position_badges', []);
                    $positionText = $allPositions[$banner->position] ?? $banner->position;
                    $badgeConfig = $allBadges[$banner->position] ?? ['bg' => '#e2e8f0', 'text' => '#475569'];

                    $desktopImg = $banner->desktop_url;
                    $mobileImg = $banner->mobile_url;
                @endphp

                <div class="nobi-card-item" id="banner-card-{{ $banner->id }}" data-id="{{ $banner->id }}">
                    {{-- Media Frame --}}
                    <div class="nobi-card-media">
                        <img src="{{ $desktopImg }}" alt="{{ $banner->title }}" class="nobi-card-img" loading="lazy">
                        <div class="nobi-card-media-overlay"></div>

                        <div class="nobi-card-media-top">
                            <div class="d-flex align-items-center gap-2">
                                <span class="nobi-drag-handle" title="Kéo thả để sắp xếp thứ tự hiển thị">
                                    <i class="fa-solid fa-grip-vertical"></i>
                                </span>
                                <span class="nobi-badge" style="background: {{ $badgeConfig['bg'] }}; color: {{ $badgeConfig['text'] }}; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                                    <i class="fa-solid fa-tag"></i> {{ $positionText }}
                                </span>
                            </div>
                            <span class="nobi-card-order-badge banner-order-number-{{ $banner->id }}">
                                Thứ tự: #{{ $banner->order ?? 0 }}
                            </span>
                        </div>

                        {{-- Hover Quick Actions --}}
                        <div class="nobi-card-media-actions">
                            <button type="button" class="nobi-media-btn" title="Xem ảnh lớn" 
                                    onclick="openPreviewModal('{{ addslashes($banner->title) }}', '{{ $desktopImg }}', '{{ $mobileImg }}', '{{ $banner->link }}')">
                                <i class="fa-solid fa-magnifying-glass-plus"></i>
                            </button>
                            @if($banner->link)
                                <a href="{{ $banner->link }}" target="{{ $banner->taget ?? '_blank' }}" class="nobi-media-btn" title="Mở liên kết">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Card Content --}}
                    <div class="nobi-card-body">
                        <div class="nobi-card-title-row">
                            <h3 class="nobi-card-title" title="{{ $banner->title }}">{{ $banner->title }}</h3>
                        </div>

                        <p class="nobi-card-desc" title="{{ $banner->description }}">
                            {{ $banner->description ?: 'Không có mô tả chi tiết.' }}
                        </p>

                        {{-- Metadata Info --}}
                        <div class="nobi-card-meta">
                            <div class="nobi-meta-item">
                                <i class="fa-solid fa-link"></i>
                                @if($banner->link)
                                    <a href="{{ $banner->link }}" target="{{ $banner->taget ?? '_blank' }}" class="nobi-meta-link" title="{{ $banner->link }}">
                                        {{ Str::limit($banner->link, 38) }}
                                    </a>
                                @else
                                    <span class="text-muted">Chưa gắn liên kết</span>
                                @endif
                            </div>

                            <div class="nobi-meta-item">
                                <i class="fa-regular fa-clock"></i>
                                <span>
                                    Hiển thị: 
                                    @if($banner->start_at || $banner->end_at)
                                        {{ $banner->start_at ? $banner->start_at->format('d/m/Y') : 'Bắt đầu' }} - 
                                        {{ $banner->end_at ? $banner->end_at->format('d/m/Y') : 'Vô thời hạn' }}
                                    @else
                                        <strong class="text-success">Vô thời hạn</strong>
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- Footer & Actions --}}
                        <div class="nobi-card-footer">
                            {{-- Interactive Toggle Switch --}}
                            <label class="nobi-toggle" title="Nhấn để bật / tắt hiển thị">
                                <input type="checkbox" class="banner-toggle-switch" data-id="{{ $banner->id }}" {{ $banner->is_active ? 'checked' : '' }}>
                                <span class="nobi-toggle-slider"></span>
                                <span class="nobi-toggle-text text-status-{{ $banner->id }}">
                                    {{ $banner->is_active ? 'Đang bật' : 'Đã ẩn' }}
                                </span>
                            </label>

                            {{-- Action Buttons --}}
                            <div class="nobi-card-actions">
                                <a href="{{ route('admin.banners.edit', $banner) }}" class="nobi-btn-icon" title="Chỉnh sửa banner">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>

                                <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Bạn có chắc chắn muốn xoá banner \'{{ addslashes($banner->title) }}\' không?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="nobi-btn-icon nobi-btn-icon-danger" title="Xoá banner">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5" style="grid-column: 1 / -1; background:#fff; border-radius:16px; border:1px dashed #cbd5e1;">
                    <i class="fa-solid fa-images fa-3x text-muted mb-3 d-block"></i>
                    <h5 class="text-secondary fw-semibold">Không tìm thấy banner nào</h5>
                    <p class="text-muted small">Hãy thử điều chỉnh lại bộ lọc hoặc tạo thêm banner mới.</p>
                    <a href="{{ route('admin.banners.create') }}" class="nobi-btn nobi-btn-primary mt-2">
                        <i class="fa-solid fa-plus"></i> Thêm banner ngay
                    </a>
                </div>
            @endforelse
        </div>

        {{-- 2. TABLE VIEW (Alternative) --}}
        <div class="nobi-table-card d-none" id="bannerTableView">
            <table class="nobi-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Thứ tự</th>
                        <th style="width: 140px;">Ảnh xem trước</th>
                        <th>Tiêu đề & Mô tả</th>
                        <th>Vị trí</th>
                        <th>Liên kết</th>
                        <th>Thời gian</th>
                        <th style="width: 130px;">Trạng thái</th>
                        <th style="width: 110px; text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="bannerTableBody">
                    @forelse($banners as $banner)
                        @php
                            $allPositions = $positions ?? config('banners.positions', []);
                            $allBadges = $positionBadges ?? config('banners.position_badges', []);
                            $positionText = $allPositions[$banner->position] ?? $banner->position;
                            $badgeConfig = $allBadges[$banner->position] ?? ['bg' => '#e2e8f0', 'text' => '#475569'];

                            $desktopImg = $banner->desktop_url;
                            $mobileImg = $banner->mobile_url;
                        @endphp
                        <tr data-id="{{ $banner->id }}">
                            <td class="nobi-table-drag-cell">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-grip-vertical text-muted nobi-table-drag-handle" title="Kéo để đổi thứ tự"></i>
                                    <span class="badge bg-light text-dark border banner-order-number-{{ $banner->id }}">#{{ $banner->order ?? 0 }}</span>
                                </div>
                            </td>
                            <td>
                                <img src="{{ $desktopImg }}" alt="{{ $banner->title }}" class="nobi-table-thumb"
                                     onclick="openPreviewModal('{{ addslashes($banner->title) }}', '{{ $desktopImg }}', '{{ $mobileImg }}', '{{ $banner->link }}')"
                                     style="cursor: pointer;" title="Bấm để phóng to">
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $banner->title }}</div>
                                @if($banner->description)
                                    <div class="text-muted small text-truncate" style="max-width: 320px;">{{ $banner->description }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="nobi-badge" style="background: {{ $badgeConfig['bg'] }}; color: {{ $badgeConfig['text'] }};">
                                    {{ $positionText }}
                                </span>
                            </td>
                            <td>
                                @if($banner->link)
                                    <a href="{{ $banner->link }}" target="{{ $banner->taget ?? '_blank' }}" class="text-primary text-decoration-none small text-truncate d-block" style="max-width: 180px;">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i> {{ $banner->link }}
                                    </a>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-secondary d-block">
                                    {{ $banner->start_at ? $banner->start_at->format('d/m/Y') : 'Không giới hạn' }}
                                </small>
                            </td>
                            <td>
                                <label class="nobi-toggle">
                                    <input type="checkbox" class="banner-toggle-switch" data-id="{{ $banner->id }}" {{ $banner->is_active ? 'checked' : '' }}>
                                    <span class="nobi-toggle-slider"></span>
                                    <span class="nobi-toggle-text text-status-{{ $banner->id }}">
                                        {{ $banner->is_active ? 'Bật' : 'Ẩn' }}
                                    </span>
                                </label>
                            </td>
                            <td style="text-align: center;">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.banners.edit', $banner) }}" class="nobi-btn-icon" title="Chỉnh sửa">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xoá banner này không?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="nobi-btn-icon nobi-btn-icon-danger" title="Xóa">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                Chưa có banner nào phù hợp.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($banners->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-muted small">
                Hiển thị {{ $banners->firstItem() }} - {{ $banners->lastItem() }} trên tổng số {{ $banners->total() }} banner
            </div>
            <div>
                {{ $banners->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

{{-- Live Preview Modal --}}
<div class="modal fade" id="bannerPreviewModal" tabindex="-1" aria-labelledby="bannerPreviewTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
            <div class="modal-header border-bottom bg-white py-3 px-4">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="bannerPreviewTitle">
                    <i class="fa-solid fa-image text-primary"></i> Xem trước banner
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="nobi-preview-modal-body">
                    {{-- Device Selector Tabs --}}
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-dark active px-4 py-2" id="tabPreviewDesktop" onclick="switchPreviewDevice('desktop')">
                            <i class="fa-solid fa-desktop me-1"></i> Desktop
                        </button>
                        <button type="button" class="btn btn-dark px-4 py-2" id="tabPreviewMobile" onclick="switchPreviewDevice('mobile')">
                            <i class="fa-solid fa-mobile-screen me-1"></i> Mobile
                        </button>
                    </div>

                    {{-- Image Views --}}
                    <div class="nobi-preview-img-box" id="boxPreviewDesktop">
                        <img id="modalImgDesktop" src="" alt="Desktop Preview">
                    </div>
                    <div class="nobi-preview-img-box d-none" id="boxPreviewMobile" style="max-width: 360px;">
                        <img id="modalImgMobile" src="" alt="Mobile Preview">
                    </div>

                    <div id="modalLinkContainer" class="text-center">
                        <a id="modalLinkBtn" href="#" target="_blank" class="btn btn-outline-light btn-sm px-3 rounded-pill">
                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Thử mở liên kết
                        </a>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4 border-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

{{-- Notification Toast --}}
<div class="nobi-toast" id="nobiToast">
    <i class="fa-solid fa-circle-check text-success fa-lg"></i>
    <span id="nobiToastMsg">Cập nhật thành công!</span>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // 1. View Switcher (Grid vs Table)
    const btnGrid = document.getElementById('btnViewGrid');
    const btnTable = document.getElementById('btnViewTable');
    const gridView = document.getElementById('bannerGridView');
    const tableView = document.getElementById('bannerTableView');

    const savedView = localStorage.getItem('nobi_banner_view_mode') || 'grid';
    applyView(savedView);

    btnGrid.addEventListener('click', function () {
        applyView('grid');
    });

    btnTable.addEventListener('click', function () {
        applyView('table');
    });

    function applyView(mode) {
        if (mode === 'table') {
            btnTable.classList.add('active');
            btnGrid.classList.remove('active');
            tableView.classList.remove('d-none');
            gridView.classList.add('d-none');
            localStorage.setItem('nobi_banner_view_mode', 'table');
        } else {
            btnGrid.classList.add('active');
            btnTable.classList.remove('active');
            gridView.classList.remove('d-none');
            tableView.classList.add('d-none');
            localStorage.setItem('nobi_banner_view_mode', 'grid');
        }
    }

    // 2. AJAX Status Toggle Switches
    const toggleSwitches = document.querySelectorAll('.banner-toggle-switch');

    toggleSwitches.forEach(sw => {
        sw.addEventListener('change', function () {
            const bannerId = this.getAttribute('data-id');
            const isChecked = this.checked;
            const statusLabels = document.querySelectorAll(`.text-status-${bannerId}`);

            // Optimistic UI update
            statusLabels.forEach(lbl => {
                lbl.textContent = isChecked ? 'Đang bật' : 'Đã ẩn';
            });

            // Sync other inputs for same id (if in both views)
            document.querySelectorAll(`.banner-toggle-switch[data-id="${bannerId}"]`).forEach(input => {
                input.checked = isChecked;
            });

            fetch(`/admin/banners/${bannerId}/toggle`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                showToast(data.message || (isChecked ? 'Đã bật hiển thị banner.' : 'Đã tạm tắt banner.'));
            })
            .catch(err => {
                console.error('Toggle error:', err);
                // Revert
                this.checked = !isChecked;
                statusLabels.forEach(lbl => {
                    lbl.textContent = !isChecked ? 'Đang bật' : 'Đã ẩn';
                });
                showToast('Không thể cập nhật trạng thái banner. Vui lòng thử lại!', true);
            });
        });
    });

    // 3. Drag & Drop Sorting (SortableJS)
    const gridContainer = document.getElementById('bannerGridView');
    const tableContainer = document.getElementById('bannerTableBody');

    function initSortable(el, handleClass) {
        if (!el || typeof Sortable === 'undefined') return;

        Sortable.create(el, {
            handle: handleClass,
            animation: 250,
            ghostClass: 'nobi-sortable-ghost',
            chosenClass: 'nobi-sortable-chosen',
            dragClass: 'nobi-sortable-drag',
            onEnd: function () {
                const items = Array.from(el.querySelectorAll('[data-id]'));
                const orders = [];
                const ids = [];

                items.forEach((item, index) => {
                    const id = item.getAttribute('data-id');
                    const newOrder = index + 1;
                    orders.push({ id: parseInt(id), order: newOrder });
                    ids.push(parseInt(id));

                    // Update UI order badges instantly across grid & table views
                    document.querySelectorAll(`.banner-order-number-${id}`).forEach(badge => {
                        if (badge.textContent.includes('Thứ tự:')) {
                            badge.textContent = `Thứ tự: #${newOrder}`;
                        } else {
                            badge.textContent = `#${newOrder}`;
                        }
                    });
                });

                // Send AJAX to save order
                fetch('{{ route('admin.banners.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ orders, ids })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Đã cập nhật thứ tự banner thành công!');
                    } else {
                        showToast('Có lỗi khi lưu thứ tự: ' + (data.message || ''), true);
                    }
                })
                .catch(err => {
                    console.error('Reorder error:', err);
                    showToast('Lỗi mạng khi lưu thứ tự banner!', true);
                });
            }
        });
    }

    if (gridContainer) {
        initSortable(gridContainer, '.nobi-drag-handle');
    }
    if (tableContainer) {
        initSortable(tableContainer, '.nobi-table-drag-cell');
    }

    // Toast helper
    function showToast(msg, isError = false) {
        const toast = document.getElementById('nobiToast');
        const toastMsg = document.getElementById('nobiToastMsg');
        const icon = toast.querySelector('i');

        toastMsg.textContent = msg;
        if (isError) {
            icon.className = 'fa-solid fa-circle-exclamation text-danger fa-lg';
        } else {
            icon.className = 'fa-solid fa-circle-check text-success fa-lg';
        }

        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3200);
    }
});

// 4. Live Preview Modal Logic
function openPreviewModal(title, desktopUrl, mobileUrl, link) {
    document.getElementById('bannerPreviewTitle').innerHTML = `<i class="fa-solid fa-image text-primary"></i> ${title}`;
    document.getElementById('modalImgDesktop').src = desktopUrl;
    document.getElementById('modalImgMobile').src = mobileUrl;

    const linkContainer = document.getElementById('modalLinkContainer');
    const linkBtn = document.getElementById('modalLinkBtn');
    if (link) {
        linkBtn.href = link;
        linkContainer.classList.remove('d-none');
    } else {
        linkContainer.classList.add('d-none');
    }

    switchPreviewDevice('desktop');

    const modalEl = document.getElementById('bannerPreviewModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function switchPreviewDevice(device) {
    const btnDesk = document.getElementById('tabPreviewDesktop');
    const btnMob = document.getElementById('tabPreviewMobile');
    const boxDesk = document.getElementById('boxPreviewDesktop');
    const boxMob = document.getElementById('boxPreviewMobile');

    if (device === 'mobile') {
        btnMob.classList.add('active');
        btnDesk.classList.remove('active');
        boxMob.classList.remove('d-none');
        boxDesk.classList.add('d-none');
    } else {
        btnDesk.classList.add('active');
        btnMob.classList.remove('active');
        boxDesk.classList.remove('d-none');
        boxMob.classList.add('d-none');
    }
}
</script>
@endpush
