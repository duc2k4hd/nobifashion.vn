@extends('admins.layouts.master')

@section('page-title', 'Quản lý bài viết')
@section('title', 'Quản lý bài viết')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/posts-icon.png') }}" type="image/png">
@endpush

@push('styles')
    <style>
        .posts-container {
            margin: 0 auto;
        }

        /* Header Box */
        .page-header-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        .page-header-box h2 {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-header-box p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 13px;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Quick Status Filter Tabs */
        .status-tabs-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .status-tab-item {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            color: #334155;
            transition: all 0.15s ease;
            position: relative;
            overflow: hidden;
        }
        .status-tab-item:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
            color: #0f172a;
        }
        .status-tab-item.active {
            border-color: #3b82f6;
            background: #ffffff;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.12), 0 4px 12px rgba(59, 130, 246, 0.06);
        }
        .status-tab-item.active::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #3b82f6;
        }
        .status-tab-title {
            font-size: 12.5px;
            font-weight: 600;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .status-tab-item.active .status-tab-title {
            color: #1e40af;
        }
        .status-tab-count {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }
        .status-tab-item.active .status-tab-count {
            color: #2563eb;
        }

        /* Filter Panel */
        .filter-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        }
        .filter-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #e2e8f0;
        }
        .filter-panel-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .filter-results-summary {
            font-size: 12.5px;
            color: #64748b;
        }
        .filter-results-summary strong {
            color: #0f172a;
        }
        .filter-grid-primary {
            display: grid;
            grid-template-columns: 2.2fr 1.3fr 1.1fr 1.2fr 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }
        .filter-grid-secondary {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1.1fr 1.3fr auto;
            gap: 12px;
            align-items: flex-end;
        }
        @media (max-width: 1280px) {
            .filter-grid-primary,
            .filter-grid-secondary {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 768px) {
            .filter-grid-primary,
            .filter-grid-secondary {
                grid-template-columns: 1fr;
            }
        }
        .filter-field label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 5px;
            display: block;
        }
        .filter-field input,
        .filter-field select {
            width: 100%;
            height: 38px;
            padding: 7px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 13px;
            color: #1e293b;
            background-color: #f8fafc;
            transition: all 0.15s ease;
        }
        .filter-field input:focus,
        .filter-field select:focus {
            background-color: #ffffff;
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
        }
        .filter-field-search {
            position: relative;
        }
        .filter-field-search input {
            padding-left: 34px;
        }
        .filter-field-search .search-icon {
            position: absolute;
            left: 12px;
            top: 31px;
            color: #94a3b8;
            font-size: 13px;
            pointer-events: none;
        }
        .filter-actions-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Search Hint */
        .search-hint-box {
            font-size: 12px;
            padding: 6px 12px;
            border-radius: 6px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .search-hint-exact {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .search-hint-fallback {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        /* Table Card */
        .table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: visible;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            margin-bottom: 24px;
        }
        @media (min-width: 992px) {
            .table-card .table-responsive {
                overflow: visible;
            }
        }
        .table-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding: 14px 18px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .table-card-header h3 {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Bulk Actions Bar */
        .bulk-actions-bar {
            display: none;
            align-items: center;
            gap: 10px;
            padding: 6px 12px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            font-size: 12.5px;
            color: #1e40af;
            animation: fadeIn 0.2s ease;
        }
        .bulk-actions-bar.active {
            display: inline-flex;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Clean Table */
        .clean-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin: 0;
        }
        .clean-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        .clean-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #334155;
        }
        .clean-table tbody tr:hover td {
            background: #f8fafc;
        }

        /* Post Cell */
        .post-cell {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            max-width: 440px;
        }
        .post-thumb-wrap {
            width: 68px;
            height: 44px;
            border-radius: 6px;
            overflow: hidden;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .post-thumb-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .post-thumb-placeholder {
            color: #94a3b8;
            font-size: 16px;
        }
        .post-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }
        .post-title {
            font-weight: 600;
            color: #0f172a;
            font-size: 13px;
            line-height: 1.35;
            text-decoration: none;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .post-title:hover {
            color: #2563eb;
        }
        .post-slug {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11px;
            color: #64748b;
            text-decoration: none;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 320px;
        }
        .post-tags-row {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-wrap: wrap;
            margin-top: 2px;
        }
        .post-tag-pill {
            font-size: 10.5px;
            color: #475569;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 1px 6px;
            line-height: 1.3;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
            white-space: nowrap;
            border: 1px solid transparent;
        }
        .status-badge-published {
            background: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }
        .status-badge-draft {
            background: #f8fafc;
            color: #64748b;
            border-color: #cbd5e1;
        }
        .status-badge-pending {
            background: #fffbeb;
            color: #d97706;
            border-color: #fde68a;
        }
        .status-badge-archived {
            background: #f1f5f9;
            color: #334155;
            border-color: #cbd5e1;
        }
        .status-badge-trashed {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }

        /* Featured Star */
        .star-featured {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 6px;
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #fde68a;
            font-size: 12px;
        }

        /* Views Badge */
        .views-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }

        /* Table Action Buttons */
        .action-btn-group {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
            white-space: nowrap;
        }
        .btn-table-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            font-size: 12.5px;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            border: 1px solid #cbd5e1;
            cursor: pointer;
            line-height: 1;
            flex-shrink: 0;
            background: #ffffff;
            color: #475569;
        }
        .btn-table-action:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
        }
        .btn-action-view {
            color: #0284c7;
            border-color: #bae6fd;
            background: #f0f9ff;
        }
        .btn-action-view:hover {
            background: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
        }
        .btn-action-edit {
            color: #2563eb;
            border-color: #bfdbfe;
            background: #eff6ff;
        }
        .btn-action-edit:hover {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }

        /* Post Row Action Dropdown */
        .post-action-dropdown {
            position: relative;
            display: inline-block;
        }
        .post-action-dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 4px);
            min-width: 185px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15), 0 8px 10px -6px rgba(15, 23, 42, 0.1);
            padding: 6px;
            z-index: 1050;
            text-align: left;
        }
        .post-action-dropdown-menu.show {
            display: block !important;
            animation: postDropdownAnim 0.15s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes postDropdownAnim {
            from {
                opacity: 0;
                transform: translateY(-4px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .post-dropdown-item {
            display: flex;
            align-items: center;
            gap: 9px;
            width: 100%;
            padding: 7px 10px;
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            background: transparent;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            text-align: left;
            transition: background 0.12s ease, color 0.12s ease;
            white-space: nowrap;
        }
        .post-dropdown-item:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .post-dropdown-item.text-danger {
            color: #dc2626 !important;
        }
        .post-dropdown-item.text-danger:hover {
            background: #fef2f2 !important;
            color: #b91c1c !important;
        }
        .post-dropdown-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 4px 0;
        }

        /* Modern Action Buttons */
        .btn-modern-primary {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            background: #2563eb;
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            border: 1px solid #2563eb;
            transition: all 0.15s ease;
        }
        .btn-modern-primary:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
            color: #ffffff;
        }
        .btn-modern-secondary {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            background: #ffffff;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            transition: all 0.15s ease;
        }
        .btn-modern-secondary:hover {
            background: #f8fafc;
            color: #0f172a;
            border-color: #94a3b8;
        }
        .btn-modern-green {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            background: #f0fdf4;
            color: #15803d;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            border: 1px solid #bbf7d0;
            transition: all 0.15s ease;
        }
        .btn-modern-green:hover {
            background: #15803d;
            color: #ffffff;
            border-color: #15803d;
        }
        .btn-modern-cyan {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            background: #ecfeff;
            color: #0e7490;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            border: 1px solid #a5f3fc;
            transition: all 0.15s ease;
        }
        .btn-modern-cyan:hover {
            background: #0e7490;
            color: #ffffff;
            border-color: #0e7490;
        }
        .btn-modern-danger {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            border: 1px solid #fecaca;
            transition: all 0.15s ease;
        }
        .btn-modern-danger:hover {
            background: #b91c1c;
            color: #ffffff;
            border-color: #b91c1c;
        }

        /* Pagination Box */
        .posts-pagination-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            padding: 14px 20px;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
        }
        .pagination-count-text {
            font-size: 13px;
            color: #64748b;
        }
        .pagination-count-text strong {
            color: #0f172a;
            font-weight: 600;
        }
        .clean-pagination-list {
            display: flex !important;
            align-items: center !important;
            gap: 4px !important;
            list-style: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .clean-pagination-list .page-item {
            margin: 0 !important;
            list-style: none !important;
        }
        .clean-pagination-list .page-link {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 34px !important;
            height: 34px !important;
            padding: 0 10px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            color: #334155 !important;
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            text-decoration: none !important;
            transition: all 0.15s ease-in-out !important;
            cursor: pointer !important;
            box-shadow: none !important;
            line-height: 1 !important;
        }
        .clean-pagination-list .page-link:hover {
            background: #f8fafc !important;
            border-color: #94a3b8 !important;
            color: #0f172a !important;
        }
        .clean-pagination-list .page-item.active .page-link {
            background: #2563eb !important;
            border-color: #2563eb !important;
            color: #ffffff !important;
            font-weight: 600 !important;
        }
        .clean-pagination-list .page-item.disabled .page-link {
            background: #f8fafc !important;
            border-color: #e2e8f0 !important;
            color: #cbd5e1 !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
        }
        .clean-pagination-list .page-link.dots {
            border-color: transparent !important;
            background: transparent !important;
            color: #94a3b8 !important;
            cursor: default !important;
        }
    </style>
@endpush

@section('content')
<div class="posts-container">

    {{-- Page Header --}}
    <div class="page-header-box">
        <div>
            <h2>
                <i class="fa-solid fa-newspaper text-primary"></i>
                Quản lý bài viết
            </h2>
            <p>Theo dõi, biên tập, xuất bản nội dung và quản lý kho bài viết chuẩn SEO.</p>
        </div>

        <div class="header-actions">
            <button type="button" id="btnOpenExportModal" class="btn-modern-green" data-bs-toggle="modal" data-bs-target="#exportCsvModal">
                <i class="fa-solid fa-file-arrow-down"></i> Xuất CSV
            </button>
            <button type="button" id="btnOpenImportModal" class="btn-modern-cyan" data-bs-toggle="modal" data-bs-target="#importCsvModal">
                <i class="fa-solid fa-file-arrow-up"></i> Nhập CSV/Excel
            </button>
            <button type="button" class="btn-modern-danger" id="btnOpenDeleteFromTxt" data-bs-toggle="modal" data-bs-target="#deleteTxtModal" title="Xóa bài viết hàng loạt theo danh sách ID từ file .txt, kèm xóa ảnh">
                <i class="fa-solid fa-trash-can"></i> Xóa từ TXT
            </button>
            <a href="{{ route('admin.posts.create') }}" class="btn-modern-primary">
                <i class="fa-solid fa-plus"></i> Viết bài mới
            </a>
        </div>
    </div>

    {{-- Quick Status Filter Tabs --}}
    @php
        $currentStatus = $filters['status'] ?? '';
    @endphp
    <div class="status-tabs-row">
        {{-- Tất cả --}}
        <a href="{{ route('admin.posts.index', request()->except(['page', 'status'])) }}"
           class="status-tab-item {{ $currentStatus === '' ? 'active' : '' }}">
            <span class="status-tab-title">
                <i class="fa-solid fa-layer-group"></i> Tất cả bài viết
            </span>
            <span class="status-tab-count">{{ number_format($stats->total ?? 0) }}</span>
        </a>

        {{-- Đã xuất bản --}}
        <a href="{{ route('admin.posts.index', array_merge(request()->except(['page', 'status']), ['status' => 'published'])) }}"
           class="status-tab-item {{ $currentStatus === 'published' ? 'active' : '' }}">
            <span class="status-tab-title" style="color: #059669;">
                <i class="fa-solid fa-circle-check"></i> Đã xuất bản
            </span>
            <span class="status-tab-count">{{ number_format($stats->published ?? 0) }}</span>
        </a>

        {{-- Bản nháp --}}
        <a href="{{ route('admin.posts.index', array_merge(request()->except(['page', 'status']), ['status' => 'draft'])) }}"
           class="status-tab-item {{ $currentStatus === 'draft' ? 'active' : '' }}">
            <span class="status-tab-title" style="color: #64748b;">
                <i class="fa-regular fa-file-lines"></i> Bản nháp
            </span>
            <span class="status-tab-count">{{ number_format($stats->draft ?? 0) }}</span>
        </a>

        {{-- Chờ duyệt --}}
        <a href="{{ route('admin.posts.index', array_merge(request()->except(['page', 'status']), ['status' => 'pending'])) }}"
           class="status-tab-item {{ $currentStatus === 'pending' ? 'active' : '' }}">
            <span class="status-tab-title" style="color: #d97706;">
                <i class="fa-regular fa-clock"></i> Chờ duyệt
            </span>
            <span class="status-tab-count">{{ number_format($stats->pending ?? 0) }}</span>
        </a>

        {{-- Lưu trữ --}}
        <a href="{{ route('admin.posts.index', array_merge(request()->except(['page', 'status']), ['status' => 'archived'])) }}"
           class="status-tab-item {{ $currentStatus === 'archived' ? 'active' : '' }}">
            <span class="status-tab-title" style="color: #475569;">
                <i class="fa-solid fa-box-archive"></i> Lưu trữ
            </span>
            <span class="status-tab-count">{{ number_format($stats->archived ?? 0) }}</span>
        </a>

        {{-- Thùng rác --}}
        <a href="{{ route('admin.posts.index', array_merge(request()->except(['page', 'status']), ['status' => 'trashed'])) }}"
           class="status-tab-item {{ $currentStatus === 'trashed' ? 'active' : '' }}">
            <span class="status-tab-title" style="color: #dc2626;">
                <i class="fa-regular fa-trash-can"></i> Thùng rác
            </span>
            <span class="status-tab-count">{{ number_format($stats->trashed ?? 0) }}</span>
        </a>
    </div>

    {{-- Filter Panel --}}
    <div class="filter-panel">
        <div class="filter-panel-header">
            <span class="filter-panel-title">
                <i class="fa-solid fa-filter text-primary"></i> Bộ lọc & Tìm kiếm bài viết
            </span>
            <div class="filter-results-summary">
                Hiển thị <strong>{{ number_format($posts->firstItem() ?? 0) }} - {{ number_format($posts->lastItem() ?? 0) }}</strong> trên tổng <strong>{{ number_format($posts->total()) }}</strong> kết quả
            </div>
        </div>

        <form action="{{ route('admin.posts.index') }}" method="GET" id="filter-form">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            {{-- Hàng lọc chính --}}
            <div class="filter-grid-primary">
                {{-- Từ khóa --}}
                <div class="filter-field filter-field-search">
                    <label for="filter-search">Tìm kiếm</label>
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input
                        id="filter-search"
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Tiêu đề bài viết, slug..."
                    >
                </div>

                {{-- Danh mục --}}
                <div class="filter-field">
                    <label for="filter-category">Danh mục</label>
                    <select id="filter-category" name="category_id">
                        <option value="">Tất cả danh mục</option>
                        <option value="none" @selected(($filters['category_id'] ?? '') === 'none')>⚠️ Chưa có danh mục</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tag --}}
                <div class="filter-field">
                    <label for="filter-tag">Thẻ Tag</label>
                    <select id="filter-tag" name="tag_id">
                        <option value="">Tất cả thẻ</option>
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}" @selected(($filters['tag_id'] ?? '') == $tag->id)>
                                {{ $tag->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tác giả --}}
                <div class="filter-field">
                    <label for="filter-author">Tác giả</label>
                    <select id="filter-author" name="author_id">
                        <option value="">Tất cả tác giả</option>
                        @foreach($authors as $author)
                            <option value="{{ $author->id }}" @selected(($filters['author_id'] ?? '') == $author->id)>
                                {{ $author->name ?? $author->email }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Trạng thái --}}
                <div class="filter-field">
                    <label for="filter-status">Trạng thái</label>
                    <select id="filter-status" name="status">
                        <option value="">Tất cả trạng thái</option>
                        @foreach($statusOptions as $val => $lbl)
                            <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ $lbl }}</option>
                        @endforeach
                        <option value="trashed" @selected(($filters['status'] ?? '') === 'trashed')>Đã xóa mềm</option>
                    </select>
                </div>

                {{-- Hiển thị / trang --}}
                <div class="filter-field">
                    <label for="filter-limit">Hiển thị / trang</label>
                    <select id="filter-limit" name="limit">
                        <option value="50" @selected(($filters['limit'] ?? 50) == 50)>50 bài</option>
                        <option value="100" @selected(($filters['limit'] ?? 50) == 100)>100 bài</option>
                        <option value="300" @selected(($filters['limit'] ?? 50) == 300)>300 bài</option>
                        <option value="1000" @selected(($filters['limit'] ?? 50) == 1000)>1000 bài</option>
                    </select>
                </div>
            </div>

            {{-- Hàng lọc mở rộng --}}
            <div class="filter-grid-secondary">
                {{-- Ngày từ --}}
                <div class="filter-field">
                    <label for="filter-date-from">Ngày xuất bản từ</label>
                    <input type="date" id="filter-date-from" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                </div>

                {{-- Ngày đến --}}
                <div class="filter-field">
                    <label for="filter-date-to">Ngày xuất bản đến</label>
                    <input type="date" id="filter-date-to" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                </div>

                {{-- Nổi bật --}}
                <div class="filter-field">
                    <label for="filter-featured">Nổi bật</label>
                    <select id="filter-featured" name="is_featured">
                        <option value="">Tất cả</option>
                        <option value="1" @selected(($filters['is_featured'] ?? '') === '1')>Chỉ nổi bật</option>
                        <option value="0" @selected(($filters['is_featured'] ?? '') === '0')>Không nổi bật</option>
                    </select>
                </div>

                {{-- Thiếu thumbnail --}}
                <div class="filter-field">
                    <label for="filter-without-thumb">Thiếu thumbnail</label>
                    <select id="filter-without-thumb" name="without_thumbnail">
                        <option value="">Tất cả</option>
                        <option value="1" @selected(($filters['without_thumbnail'] ?? '') === '1')>Chưa có thumbnail</option>
                    </select>
                </div>

                {{-- Sắp xếp theo Lượt xem / Thời gian --}}
                <div class="filter-field">
                    <label for="filter-sort">Sắp xếp</label>
                    <select id="filter-sort" name="sort">
                        <option value="">Mới nhất</option>
                        <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Cũ nhất</option>
                        <option value="view_desc" @selected(($filters['sort'] ?? '') === 'view_desc')>Lượt xem cao nhất</option>
                        <option value="view_asc" @selected(($filters['sort'] ?? '') === 'view_asc')>Lượt xem thấp nhất</option>
                    </select>
                </div>

                {{-- Nút bấm Lọc & Reset --}}
                <div class="filter-actions-group" style="padding-bottom: 2px;">
                    <button type="submit" class="btn-modern-primary" style="height: 38px;">
                        <i class="fa-solid fa-filter"></i> Lọc
                    </button>
                    <a href="{{ route('admin.posts.index', request('status') ? ['status' => request('status')] : []) }}"
                       class="btn-modern-secondary" style="height: 38px;" title="Xóa bộ lọc">
                        <i class="fa-solid fa-arrow-rotate-left"></i> Đặt lại
                    </a>
                </div>
            </div>

            {{-- Thông báo Progressive Search --}}
            @if(($searchMeta['mode'] ?? null) === 'exact_phrase')
                <div class="search-hint-box search-hint-exact">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>Đang ưu tiên các bài viết khớp đúng cụm từ khóa <strong>"{{ $filters['search'] ?? '' }}"</strong>.</span>
                </div>
            @elseif(($searchMeta['mode'] ?? null) === 'progressive')
                <div class="search-hint-box search-hint-fallback">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Không có bài viết khớp chính xác. Đang tìm kiếm theo các cụm từ tương đồng:
                        <strong>{{ collect($searchMeta['segments'] ?? [])->take(5)->implode(', ') }}</strong>.
                    </span>
                </div>
            @endif
        </form>
    </div>

    {{-- Main Table Card --}}
    <div class="table-card">
        <div class="table-card-header">
            <h3>
                <i class="fa-solid fa-list-check text-primary"></i>
                Danh sách bài viết
                @if(($filters['status'] ?? '') === 'trashed')
                    <span class="badge bg-danger ms-2" style="font-size: 11px; font-weight: 500;">Thùng rác</span>
                @endif
            </h3>

            {{-- Bulk Actions Bar --}}
            <div class="bulk-actions-bar" id="bulkActionsBar">
                <span><strong id="bulkSelectedBadge">0</strong> bài viết được chọn:</span>
                <form action="{{ route('admin.posts.bulk-destroy') }}" method="POST" id="bulkDeleteForm" class="d-inline-flex gap-1 m-0">
                    @csrf
                    <input type="hidden" name="is_trashed" value="{{ ($filters['status'] ?? '') === 'trashed' ? '1' : '0' }}">

                    @if(($filters['status'] ?? '') === 'trashed')
                        <button type="submit" class="btn btn-sm btn-danger" id="btnBulkDelete" onclick="return confirm('Xóa VĨNH VIỄN các bài đã chọn? Hành động này KHÔNG thể hoàn tác!');">
                            <i class="fa-solid fa-trash-can"></i> Xóa vĩnh viễn đã chọn
                        </button>
                    @else
                        <button type="submit" class="btn btn-sm btn-outline-danger bg-white" id="btnBulkDelete" onclick="return confirm('Bạn có chắc chắn muốn bỏ vào thùng rác các bài viết đã chọn?');">
                            <i class="fa-regular fa-trash-can"></i> Xóa các mục đã chọn
                        </button>
                    @endif

                    <button type="button" class="btn btn-sm btn-outline-success bg-white" id="btnExportSelectedItems">
                        <i class="fa-solid fa-file-arrow-down"></i> Xuất CSV đã chọn
                    </button>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="clean-table" id="postsTable">
                <thead>
                    <tr>
                        <th style="width: 44px; text-align: center;">
                            <input class="form-check-input mt-0" type="checkbox" id="checkAll" style="cursor: pointer;">
                        </th>
                        <th style="width: 50px; text-align: center;">ID</th>
                        <th>Bài viết</th>
                        <th style="width: 140px;">Danh mục</th>
                        <th style="width: 120px; text-align: center;">Trạng thái</th>
                        <th style="width: 80px; text-align: center;">Nổi bật</th>
                        <th style="width: 100px; text-align: center;">
                            @php
                                $currentSort = $filters['sort'] ?? '';
                                $nextSort = ($currentSort === 'view_desc') ? 'view_asc' : 'view_desc';
                                $sortIcon = '';
                                if ($currentSort === 'view_desc') {
                                    $sortIcon = '<i class="fa-solid fa-arrow-down-short-wide text-primary ms-1" title="Giảm dần"></i>';
                                } elseif ($currentSort === 'view_asc') {
                                    $sortIcon = '<i class="fa-solid fa-arrow-up-wide-short text-primary ms-1" title="Tăng dần"></i>';
                                } else {
                                    $sortIcon = '<i class="fa-solid fa-sort text-muted opacity-50 ms-1"></i>';
                                }
                                $sortUrl = request()->fullUrlWithQuery(['sort' => $nextSort]);
                            @endphp
                            <a href="{{ $sortUrl }}" class="text-dark text-decoration-none d-inline-flex align-items-center" title="Bấm để sắp xếp theo lượt xem">
                                Lượt xem {!! $sortIcon !!}
                            </a>
                        </th>
                        <th style="width: 130px;">Tác giả</th>
                        <th style="width: 120px;">Xuất bản</th>
                        <th style="width: 110px; text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                        @php
                            $thumbSrc = null;
                            if (!empty($post->thumbnail)) {
                                $thumbSrc = asset('clients/assets/img/posts/' . $post->thumbnail);
                            }
                            $tagNames = !empty($post->tag_ids) 
                                ? $tags->whereIn('id', $post->tag_ids)->pluck('name')
                                : ($post->relationLoaded('tags') ? $post->tags->pluck('name') : collect());
                        @endphp
                        <tr>
                            {{-- Checkbox --}}
                            <td style="text-align: center;">
                                <input form="bulkDeleteForm" class="form-check-input item-check mt-0" type="checkbox" name="ids[]" value="{{ $post->id }}" style="cursor: pointer;">
                            </td>

                            {{-- ID --}}
                            <td style="text-align: center; font-weight: 600; color: #64748b; font-size: 12px;">
                                #{{ $post->id }}
                            </td>

                            {{-- Bài viết (Ảnh + Tiêu đề + Slug + Tags) --}}
                            <td>
                                <div class="post-cell">
                                    <div class="post-thumb-wrap">
                                        @if($thumbSrc)
                                            <img
                                                src="{{ $thumbSrc }}"
                                                alt="{{ $post->title }}"
                                                class="post-thumb-img"
                                                loading="lazy"
                                                onerror="this.onerror=null;this.parentElement.innerHTML='<span class=\'post-thumb-placeholder\'><i class=\'fa-regular fa-image\'></i></span>';"
                                            >
                                        @else
                                            <span class="post-thumb-placeholder">
                                                <i class="fa-regular fa-image"></i>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="post-info">
                                        <a href="{{ route('admin.posts.edit', $post) }}" class="post-title" title="{{ $post->title }}">
                                            {{ renderMeta($post->title) }}
                                        </a>
                                        <span class="post-slug" title="{{ $post->slug }}">
                                            {{ $post->slug }}
                                        </span>
                                        @if($tagNames->isNotEmpty())
                                            <div class="post-tags-row">
                                                @foreach($tagNames->take(3) as $tName)
                                                    <span class="post-tag-pill">#{{ $tName }}</span>
                                                @endforeach
                                                @if($tagNames->count() > 3)
                                                    <span class="post-tag-pill">+{{ $tagNames->count() - 3 }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Danh mục --}}
                            <td>
                                @if($post->category)
                                    <a href="{{ route('admin.posts.index', array_merge(request()->except(['page']), ['category_id' => $post->category->id])) }}"
                                       class="badge bg-light text-primary border text-decoration-none" style="font-weight: 500; font-size: 11.5px;">
                                        {{ $post->category->name }}
                                    </a>
                                @else
                                    <a href="{{ route('admin.posts.index', array_merge(request()->except(['page']), ['category_id' => 'none'])) }}"
                                       class="badge bg-warning text-dark border text-decoration-none" style="font-size: 11px;" title="Chưa gắn danh mục">
                                        Chưa có
                                    </a>
                                @endif
                            </td>

                            {{-- Trạng thái --}}
                            <td style="text-align: center;">
                                @if($post->trashed())
                                    <span class="status-badge status-badge-trashed">
                                        <i class="fa-regular fa-trash-can"></i> Đã xóa
                                    </span>
                                @elseif($post->status === 'published')
                                    <span class="status-badge status-badge-published">
                                        <i class="fa-solid fa-circle-check"></i> Xuất bản
                                    </span>
                                @elseif($post->status === 'draft')
                                    <span class="status-badge status-badge-draft">
                                        <i class="fa-regular fa-file-lines"></i> Bản nháp
                                    </span>
                                @elseif($post->status === 'pending')
                                    <span class="status-badge status-badge-pending">
                                        <i class="fa-regular fa-clock"></i> Chờ duyệt
                                    </span>
                                @else
                                    <span class="status-badge status-badge-archived">
                                        <i class="fa-solid fa-box-archive"></i> Lưu trữ
                                    </span>
                                @endif
                            </td>

                            {{-- Nổi bật --}}
                            <td style="text-align: center;">
                                @if($post->is_featured)
                                    <span class="star-featured" title="Bài viết nổi bật">★</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>

                            {{-- Lượt xem --}}
                            <td style="text-align: center;">
                                <span class="views-badge" title="{{ number_format($post->views) }} lượt xem">
                                    <i class="fa-regular fa-eye text-muted"></i>
                                    {{ number_format($post->views) }}
                                </span>
                            </td>

                            {{-- Tác giả --}}
                            <td>
                                <span style="font-weight: 500; font-size: 12.5px; color: #334155;">
                                    {{ $post->author?->displayName() ?? '—' }}
                                </span>
                            </td>

                            {{-- Ngày xuất bản --}}
                            <td>
                                <span style="font-size: 12px; color: #64748b;">
                                    @if($post->published_at)
                                        {{ $post->published_at->translatedFormat('d/m/Y') }}
                                        <br><small class="text-muted">{{ $post->published_at->format('H:i') }}</small>
                                    @else
                                        —
                                    @endif
                                </span>
                            </td>

                            {{-- Thao tác --}}
                            <td style="text-align: right;">
                                <div class="action-btn-group">
                                    @if($post->trashed())
                                        {{-- Nút Khôi phục --}}
                                        <form action="{{ route('admin.posts.restore', $post->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-table-action" style="color: #16a34a; border-color: #bbf7d0; background: #f0fdf4;" title="Khôi phục bài viết">
                                                <i class="fa-solid fa-rotate-left"></i>
                                            </button>
                                        </form>

                                        {{-- Nút Xóa vĩnh viễn --}}
                                        <form
                                            action="{{ route('admin.posts.bulk-destroy') }}"
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('CẢNH BÁO: Xóa vĩnh viễn bài viết &quot;{{ addslashes($post->title) }}&quot;? Hành động này KHÔNG thể hoàn tác!')"
                                        >
                                            @csrf
                                            <input type="hidden" name="ids[]" value="{{ $post->id }}">
                                            <input type="hidden" name="is_trashed" value="1">
                                            <button type="submit" class="btn-table-action" style="color: #ffffff; background: #dc2626; border-color: #dc2626;" title="Xóa vĩnh viễn">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    @else
                                        {{-- Nút Xem ngoài web --}}
                                        @if(Route::has('client.blog.show') && $post->slug)
                                            <a href="{{ route('client.blog.show', $post->slug) }}" target="_blank" class="btn-table-action btn-action-view" title="Xem ngoài website">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                        @endif

                                        {{-- Nút Sửa --}}
                                        <a href="{{ route('admin.posts.edit', $post) }}" class="btn-table-action btn-action-edit" title="Chỉnh sửa bài viết">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>

                                        {{-- Menu mở rộng tùy chọn --}}
                                        <div class="post-action-dropdown">
                                            <button class="btn-table-action post-action-more-btn" type="button" title="Tùy chọn khác">
                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                            </button>
                                            <div class="post-action-dropdown-menu">
                                                <form action="{{ route('admin.posts.duplicate', $post) }}" method="POST" class="m-0">
                                                    @csrf
                                                    <button class="post-dropdown-item" type="submit">
                                                        <i class="fa-regular fa-copy text-muted" style="width: 16px;"></i> Nhân bản bài viết
                                                    </button>
                                                </form>
                                                @if(!$post->is_featured)
                                                    <form action="{{ route('admin.posts.feature', $post) }}" method="POST" class="m-0">
                                                        @csrf
                                                        <button class="post-dropdown-item" type="submit">
                                                            <i class="fa-regular fa-star text-warning" style="width: 16px;"></i> Bật nổi bật
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('admin.posts.unfeature', $post) }}" method="POST" class="m-0">
                                                        @csrf
                                                        <button class="post-dropdown-item" type="submit">
                                                            <i class="fa-solid fa-star-half-stroke text-muted" style="width: 16px;"></i> Bỏ nổi bật
                                                        </button>
                                                    </form>
                                                @endif
                                                <div class="post-dropdown-divider"></div>
                                                <form
                                                    action="{{ route('admin.posts.destroy', $post) }}"
                                                    method="POST"
                                                    class="m-0"
                                                    onsubmit="return confirm('Chuyển bài viết &quot;{{ addslashes($post->title) }}&quot; vào Thùng rác?')"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="post-dropdown-item text-danger" type="submit">
                                                        <i class="fa-regular fa-trash-can text-danger" style="width: 16px;"></i> Bỏ vào thùng rác
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 48px 16px; color: #94a3b8;">
                                <i class="fa-regular fa-newspaper" style="font-size: 36px; margin-bottom: 12px; display: block; color: #cbd5e1;"></i>
                                <div style="font-weight: 600; font-size: 14px; color: #64748b;">Không tìm thấy bài viết nào</div>
                                <div style="font-size: 12.5px; margin-top: 4px;">Thử thay đổi từ khóa tìm kiếm hoặc điều chỉnh lại các tiêu chí lọc phía trên.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Phân trang sạch đẹp --}}
        <div class="posts-pagination-box">
            <div class="pagination-count-text">
                Hiển thị <strong>{{ number_format($posts->firstItem() ?? 0) }}</strong> - <strong>{{ number_format($posts->lastItem() ?? 0) }}</strong> trên tổng <strong>{{ number_format($posts->total()) }}</strong> bài viết
            </div>

            @if($posts->hasPages())
                <nav aria-label="Phân trang bài viết">
                    <ul class="clean-pagination-list">
                        {{-- Nút Trang trước --}}
                        @if ($posts->onFirstPage())
                            <li class="page-item disabled">
                                <span class="page-link" aria-label="Trang trước" title="Trang trước">
                                    <i class="fa-solid fa-angle-left"></i>
                                </span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $posts->previousPageUrl() }}" rel="prev" aria-label="Trang trước" title="Trang trước">
                                    <i class="fa-solid fa-angle-left"></i>
                                </a>
                            </li>
                        @endif

                        {{-- Danh sách trang --}}
                        @php
                            $currentPage = $posts->currentPage();
                            $lastPage = $posts->lastPage();
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($lastPage, $currentPage + 2);
                        @endphp

                        @if($startPage > 1)
                            <li class="page-item">
                                <a class="page-link" href="{{ $posts->url(1) }}">1</a>
                            </li>
                            @if($startPage > 2)
                                <li class="page-item disabled">
                                    <span class="page-link dots">…</span>
                                </li>
                            @endif
                        @endif

                        @for ($page = $startPage; $page <= $endPage; $page++)
                            @if ($page == $currentPage)
                                <li class="page-item active" aria-current="page">
                                    <span class="page-link">{{ $page }}</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link" href="{{ $posts->url($page) }}">{{ $page }}</a>
                                </li>
                            @endif
                        @endfor

                        @if($endPage < $lastPage)
                            @if($endPage < $lastPage - 1)
                                <li class="page-item disabled">
                                    <span class="page-link dots">…</span>
                                </li>
                            @endif
                            <li class="page-item">
                                <a class="page-link" href="{{ $posts->url($lastPage) }}">{{ $lastPage }}</a>
                            </li>
                        @endif

                        {{-- Nút Trang sau --}}
                        @if ($posts->hasMorePages())
                            <li class="page-item">
                                <a class="page-link" href="{{ $posts->nextPageUrl() }}" rel="next" aria-label="Trang sau" title="Trang sau">
                                    <i class="fa-solid fa-angle-right"></i>
                                </a>
                            </li>
                        @else
                            <li class="page-item disabled">
                                <span class="page-link" aria-label="Trang sau" title="Trang sau">
                                    <i class="fa-solid fa-angle-right"></i>
                                </span>
                            </li>
                        @endif
                    </ul>
                </nav>
            @endif
        </div>
    </div>

    <!-- Modal Xuất CSV -->
    <div class="modal fade" id="exportCsvModal" tabindex="-1" aria-labelledby="exportCsvModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold" id="exportCsvModalLabel">
                        <i class="fa-solid fa-file-arrow-down text-success me-2"></i> Tùy chọn Xuất dữ liệu Bài viết ra CSV
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Phạm vi xuất -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-uppercase small text-muted">1. Phạm vi bài viết cần xuất</label>
                        <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded-3 border">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exportScope" id="scopeAll" value="all" checked>
                                <label class="form-check-label fw-semibold" for="scopeAll">
                                    <i class="fa-solid fa-globe text-primary me-1"></i> Tất cả bài viết trong hệ thống
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exportScope" id="scopeFilter" value="filter">
                                <label class="form-check-label fw-semibold" for="scopeFilter">
                                    <i class="fa-solid fa-filter text-info me-1"></i> Theo bộ lọc tìm kiếm hiện tại trên trang
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exportScope" id="scopeSelected" value="selected" disabled>
                                <label class="form-check-label fw-semibold" for="scopeSelected" id="labelScopeSelected">
                                    <i class="fa-solid fa-square-check text-success me-1"></i> Chỉ các bài viết đang chọn (<span id="modalSelectedCount">0</span> bài)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Chọn cột xuất -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold text-uppercase small text-muted mb-0">2. Chọn các cột cần xuất</label>
                            <div class="btn-group btn-group-sm">
                                <button type="button" id="btnExportSelectAll" class="btn btn-outline-secondary py-0">Chọn tất cả</button>
                                <button type="button" id="btnExportDeselectAll" class="btn btn-outline-secondary py-0">Bỏ chọn</button>
                                <button type="button" id="btnExportDefaultCols" class="btn btn-outline-primary py-0">Mặc định</button>
                            </div>
                        </div>
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="row g-2" id="exportColumnsGrid">
                                @php
                                    $allExportCols = [
                                        ['key' => 'ID', 'label' => 'ID bài viết', 'default' => true],
                                        ['key' => 'Tiêu đề', 'label' => 'Tiêu đề', 'default' => true],
                                        ['key' => 'Slug', 'label' => 'Slug (Đường dẫn)', 'default' => true],
                                        ['key' => 'Danh mục (Tên)', 'label' => 'Danh mục (Tên)', 'default' => true],
                                        ['key' => 'Danh mục (Slug)', 'label' => 'Danh mục (Slug)', 'default' => false],
                                        ['key' => 'Nội dung', 'label' => 'Nội dung HTML (Content)', 'default' => true],
                                        ['key' => 'Tóm tắt', 'label' => 'Tóm tắt / Excerpt', 'default' => false],
                                        ['key' => 'Thumbnail URL', 'label' => 'Ảnh Thumbnail', 'default' => false],
                                        ['key' => 'Alt ảnh', 'label' => 'Alt Text ảnh', 'default' => false],
                                        ['key' => 'Trạng thái', 'label' => 'Trạng thái', 'default' => false],
                                        ['key' => 'Nổi bật', 'label' => 'Nổi bật (1/0)', 'default' => false],
                                        ['key' => 'Tags (phẩy)', 'label' => 'Tags (ngăn cách phẩy)', 'default' => false],
                                        ['key' => 'Meta Title', 'label' => 'SEO Meta Title', 'default' => false],
                                        ['key' => 'Meta Description', 'label' => 'SEO Meta Description', 'default' => false],
                                        ['key' => 'Meta Keywords', 'label' => 'SEO Meta Keywords', 'default' => false],
                                        ['key' => 'Meta Canonical', 'label' => 'SEO Canonical URL', 'default' => false],
                                        ['key' => 'Tác giả (Email)', 'label' => 'Tác giả (Email)', 'default' => false],
                                        ['key' => 'Ngày xuất bản', 'label' => 'Ngày xuất bản', 'default' => false],
                                    ];
                                @endphp
                                @foreach ($allExportCols as $col)
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-check">
                                            <input class="form-check-input export-col-check" type="checkbox" value="{{ $col['key'] }}" id="expCol_{{ $loop->index }}" {{ $col['default'] ? 'checked' : '' }} data-default="{{ $col['default'] ? '1' : '0' }}">
                                            <label class="form-check-label small" for="expCol_{{ $loop->index }}">
                                                {{ $col['label'] }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" id="btnConfirmExport" class="btn btn-success rounded-3 px-4 fw-bold">
                        <i class="fa-solid fa-file-arrow-down me-1"></i> Tải File CSV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nhập CSV/Excel -->
    <div class="modal fade" id="importCsvModal" tabindex="-1" aria-labelledby="importCsvModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold" id="importCsvModalLabel">
                        <i class="fa-solid fa-file-arrow-up text-info me-2"></i> Nhập bài viết từ CSV/Excel (Batch Ultra Fast)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <!-- Cột trái: Chọn file & chọn cột -->
                        <div class="col-lg-6">
                            <!-- Dropzone chọn file -->
                            <div class="mb-3">
                                <label class="form-label fw-bold text-uppercase small text-muted">1. Chọn file CSV / Excel</label>
                                <div id="modalImportDropZone" class="border-2 border-dashed rounded-3 p-4 text-center transition-all" style="background: #f8fafc; border-color: #cbd5e1; cursor: pointer;">
                                    <input type="file" id="modalImportFileInput" class="d-none" accept=".csv, .xlsx, .xls">
                                    <div id="modalImportDropContent">
                                        <i class="fa-solid fa-cloud-arrow-up text-primary fa-2x mb-2"></i>
                                        <p class="mb-1 fw-semibold small text-dark">Kéo thả file CSV/Excel vào đây hoặc nhấn để chọn</p>
                                        <span class="text-muted" style="font-size: 0.75rem;">Hỗ trợ .csv, .xlsx, .xls</span>
                                    </div>
                                    <div id="modalImportFileInfo" class="d-none text-start p-2 bg-white rounded shadow-sm">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center overflow-hidden">
                                                <i class="fa-solid fa-file-csv text-success fa-2x me-2"></i>
                                                <div class="text-truncate">
                                                    <div id="modalImportFileName" class="fw-bold small text-truncate">file.csv</div>
                                                    <div id="modalImportFileSize" class="text-muted" style="font-size: 0.75rem;">0 KB</div>
                                                </div>
                                            </div>
                                            <button type="button" id="btnChangeImportFile" class="btn btn-sm btn-outline-secondary py-0">Đổi file</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quy tắc khớp bài viết -->
                            <div class="alert alert-info py-2 px-3 mb-3 small border-0" style="background: #f0f9ff; border-left: 4px solid #0284c7 !important;">
                                <div class="fw-bold mb-1 text-primary"><i class="fa-solid fa-shield-halved me-1"></i> Quy tắc khớp bài viết thông minh:</div>
                                <ul class="mb-0 ps-3">
                                    <li><strong>Có ID:</strong> Bắt buộc là cập nhật bài viết theo ID. (Báo lỗi nếu ID không tồn tại trên hệ thống).</li>
                                    <li><strong>Không có ID:</strong> Khớp theo <code>Slug</code> (hoặc tự sinh Slug từ Tiêu đề). Nếu Slug đã có -> Cập nhật; nếu chưa có -> Tạo bài mới.</li>
                                </ul>
                            </div>

                            <!-- Chọn cột nhập vào (hiển thị khi đã load file) -->
                            <div id="importColumnsContainer" class="d-none">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-bold text-uppercase small text-muted mb-0">2. Chọn các cột cần nhập vào</label>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" id="btnImportSelectAllCols" class="btn btn-outline-secondary py-0">Chọn hết</button>
                                        <button type="button" id="btnImportDeselectAllCols" class="btn btn-outline-secondary py-0">Bỏ hết</button>
                                        <button type="button" id="btnImportDefaultCols" class="btn btn-outline-primary py-0">Mặc định</button>
                                    </div>
                                </div>
                                <div class="p-3 bg-light rounded-3 border" style="max-height: 200px; overflow-y: auto;">
                                    <div class="row g-2" id="importColumnsList">
                                        <!-- Render dynamic columns here -->
                                    </div>
                                </div>
                                <div class="text-muted small mt-1 fst-italic">* Những cột bạn bỏ chọn sẽ được giữ nguyên dữ liệu cũ đối với bài viết cập nhật.</div>
                            </div>
                        </div>

                        <!-- Cột phải: Tiến trình & Log -->
                        <div class="col-lg-6">
                            <label class="form-label fw-bold text-uppercase small text-muted">3. Trạng thái & Tiến trình xử lý</label>
                            
                            <!-- Thống kê 3 ô -->
                            <div class="row g-2 mb-3">
                                <div class="col-4">
                                    <div class="p-2 text-center rounded bg-light border">
                                        <div class="text-muted small" style="font-size: 0.75rem;">Tổng số</div>
                                        <div id="modalStatTotal" class="h5 fw-bold mb-0 text-dark">0</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 text-center rounded" style="background: #ecfdf5; border: 1px dashed #10b981;">
                                        <div class="text-success small" style="font-size: 0.75rem;">Thành công</div>
                                        <div id="modalStatSuccess" class="h5 fw-bold mb-0 text-success">0</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 text-center rounded" style="background: #fef2f2; border: 1px dashed #ef4444;">
                                        <div class="text-danger small" style="font-size: 0.75rem;">Lỗi</div>
                                        <div id="modalStatError" class="h5 fw-bold mb-0 text-danger">0</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mb-3 d-none" id="modalProgressArea">
                                <div class="d-flex justify-content-between mb-1 small fw-bold">
                                    <span id="modalProgressText">Đang xử lý: 0/0</span>
                                    <span id="modalPercentText">0%</span>
                                </div>
                                <div class="progress rounded-pill" style="height: 10px;">
                                    <div id="modalProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>

                            <!-- Activity Log -->
                            <div class="rounded-3 bg-dark overflow-hidden">
                                <div class="d-flex justify-content-between align-items-center px-3 py-1 bg-secondary text-white" style="font-size: 0.75rem;">
                                    <span class="fw-bold">NHẬT KÝ TIẾN TRÌNH</span>
                                    <span id="modalCurrentStatus" class="opacity-75">Sẵn sàng...</span>
                                </div>
                                <div id="modalImportLog" class="p-2 font-monospace text-white-50 small overflow-auto" style="height: 200px; font-size: 0.8rem; background: #0f172a;">
                                    <div>> Vui lòng chọn file CSV/Excel để bắt đầu...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" id="btnStartImportBatch" class="btn btn-primary rounded-3 px-4 fw-bold" disabled>
                        <i class="fa-solid fa-rocket me-1"></i> Bắt đầu Nhập Dữ Liệu
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: XÓA BÀI VIẾT TỪ FILE TXT --}}
    <div class="modal fade" id="deleteTxtModal" tabindex="-1" aria-labelledby="deleteTxtModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header" style="background: linear-gradient(135deg,#dc3545,#b02a37); color:#fff;">
                    <h5 class="modal-title fw-bold" id="deleteTxtModalLabel">
                        <i class="fa-solid fa-trash-can me-2"></i> Xóa bài viết hàng loạt từ file .TXT
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body p-4">

                    {{-- Hướng dẫn --}}
                    <div class="alert alert-warning border-0 rounded-3 mb-4">
                        <div class="fw-bold mb-2"><i class="fa-solid fa-circle-info me-1"></i> Hướng dẫn sử dụng</div>
                        <ul class="mb-0 small">
                            <li>Tạo file <code>.txt</code> với mỗi dòng là <strong>1 ID bài viết</strong> cần xóa. Ví dụ: <code>123</code>, <code>456</code>, ...</li>
                            <li>Hệ thống sẽ tự động xóa bài viết, <strong>ảnh đại diện</strong> và <strong>ảnh trong nội dung</strong>.</li>
                            <li class="text-success fw-semibold">Ảnh đang được bài viết khác sử dụng sẽ được giữ lại an toàn.</li>
                            <li class="text-danger fw-semibold">Hành động này <u>KHÔNG THỂ hoàn tác</u>. Hãy chắc chắn trước khi tiến hành!</li>
                        </ul>
                    </div>

                    {{-- Chọn file --}}
                    <div id="deleteTxtStepUpload">
                        <label class="form-label fw-semibold">Chọn file .TXT chứa danh sách ID:</label>
                        <input type="file" class="form-control" id="deleteTxtFileInput" accept=".txt">
                        <div class="form-text text-muted">Mỗi dòng 1 số ID. Dòng trống và ký tự không phải số sẽ bị bỏ qua tự động.</div>

                        {{-- Preview --}}
                        <div id="deleteTxtPreview" class="mt-3" style="display:none;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-danger fs-6" id="deleteTxtCountBadge">0 ID</span>
                                <span class="text-muted small">sẽ bị xóa</span>
                            </div>
                            <div class="border rounded-2 p-2 bg-light" style="max-height: 120px; overflow-y:auto; font-family:monospace; font-size:0.8rem;" id="deleteTxtIdList"></div>
                        </div>
                    </div>

                    {{-- Tiến trình xử lý --}}
                    <div id="deleteTxtStepProgress" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold small">Tiến trình xử lý:</span>
                            <span class="small text-muted" id="deleteTxtProgressText">0 / 0</span>
                        </div>
                        <div class="progress mb-3" style="height:12px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" id="deleteTxtProgressBar" style="width:0%"></div>
                        </div>
                        <div class="border rounded-2 bg-dark text-light p-3" style="max-height:220px; overflow-y:auto; font-size:0.78rem; font-family:monospace;" id="deleteTxtLog"></div>
                        <div class="row g-2 mt-2" id="deleteTxtStats">
                            <div class="col-4">
                                <div class="text-center p-2 rounded-2" style="background:#198754;color:#fff;">
                                    <div class="fw-bold fs-5" id="statDeletedPosts">0</div>
                                    <div class="small">Bài viết đã xóa</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center p-2 rounded-2" style="background:#0d6efd;color:#fff;">
                                    <div class="fw-bold fs-5" id="statDeletedFiles">0</div>
                                    <div class="small">Ảnh đã xóa</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center p-2 rounded-2" style="background:#6c757d;color:#fff;">
                                    <div class="fw-bold fs-5" id="statSkippedFiles">0</div>
                                    <div class="small">Ảnh giữ lại (dùng chung)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4 gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" id="deleteTxtBtnClose">Hủy / Đóng</button>
                    <button type="button" class="btn btn-danger fw-bold px-4" id="deleteTxtBtnStart" disabled>
                        <i class="fa-solid fa-trash-can me-1"></i> Bắt đầu xóa
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ==========================================
            // LOGIC XUẤT CSV VỚI POPUP CHỌN CỘT
            // ==========================================
            const exportModalEl = document.getElementById('exportCsvModal');
            const btnConfirmExport = document.getElementById('btnConfirmExport');
            const btnExportSelectAll = document.getElementById('btnExportSelectAll');
            const btnExportDeselectAll = document.getElementById('btnExportDeselectAll');
            const btnExportDefaultCols = document.getElementById('btnExportDefaultCols');
            const exportColChecks = document.querySelectorAll('.export-col-check');

            if (btnExportSelectAll) {
                btnExportSelectAll.addEventListener('click', () => {
                    exportColChecks.forEach(cb => cb.checked = true);
                });
            }
            if (btnExportDeselectAll) {
                btnExportDeselectAll.addEventListener('click', () => {
                    exportColChecks.forEach(cb => cb.checked = false);
                });
            }
            if (btnExportDefaultCols) {
                btnExportDefaultCols.addEventListener('click', () => {
                    exportColChecks.forEach(cb => cb.checked = cb.dataset.default === '1');
                });
            }

            if (exportModalEl) {
                exportModalEl.addEventListener('show.bs.modal', function () {
                    const checkedCount = document.querySelectorAll('.item-check:checked').length;
                    const radioSelected = document.getElementById('scopeSelected');
                    const modalSelectedCount = document.getElementById('modalSelectedCount');
                    if (modalSelectedCount) modalSelectedCount.textContent = checkedCount;

                    if (checkedCount > 0) {
                        if (radioSelected) {
                            radioSelected.disabled = false;
                            radioSelected.checked = true;
                        }
                    } else {
                        if (radioSelected) {
                            radioSelected.disabled = true;
                            if (radioSelected.checked) {
                                const scopeAll = document.getElementById('scopeAll');
                                if (scopeAll) scopeAll.checked = true;
                            }
                        }
                    }
                });
            }

            if (btnConfirmExport) {
                btnConfirmExport.addEventListener('click', function () {
                    const selectedCols = Array.from(document.querySelectorAll('.export-col-check:checked')).map(cb => cb.value);
                    if (selectedCols.length === 0) {
                        alert('Vui lòng chọn ít nhất một cột để xuất CSV.');
                        return;
                    }

                    const originalContent = this.innerHTML;
                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang tải xuống...';

                    try {
                        const scope = document.querySelector('input[name="exportScope"]:checked')?.value || 'all';
                        const params = new URLSearchParams();

                        selectedCols.forEach(col => params.append('columns[]', col));

                        if (scope === 'selected') {
                            const checkedItems = document.querySelectorAll('.item-check:checked');
                            if (checkedItems.length === 0) {
                                alert('Bạn chưa chọn bài viết nào từ danh sách!');
                                this.disabled = false;
                                this.innerHTML = originalContent;
                                return;
                            }
                            checkedItems.forEach(cb => params.append('ids[]', cb.value));
                        } else if (scope === 'filter') {
                            const currentUrlParams = new URLSearchParams(window.location.search);
                            for (const [key, val] of currentUrlParams.entries()) {
                                if (val && key !== 'page' && key !== 'columns[]') {
                                    params.append(key, val);
                                }
                            }

                            const filterForm = document.getElementById('filter-form');
                            if (filterForm) {
                                const formData = new FormData(filterForm);
                                for (const [key, val] of formData.entries()) {
                                    if (val && key !== 'page' && !params.has(key)) {
                                        params.append(key, val);
                                    }
                                }
                            }
                        }

                        const exportForm = document.createElement('form');
                        exportForm.method = 'POST';
                        exportForm.action = "{{ route('admin.posts.export-csv') }}";
                        exportForm.style.display = 'none';

                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = "{{ csrf_token() }}";
                        exportForm.appendChild(csrfInput);

                        for (const [key, val] of params.entries()) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = key;
                            input.value = val;
                            exportForm.appendChild(input);
                        }

                        document.body.appendChild(exportForm);
                        exportForm.submit();
                        document.body.removeChild(exportForm);

                        setTimeout(() => {
                            const modalInstance = bootstrap.Modal.getInstance(exportModalEl);
                            if (modalInstance) {
                                modalInstance.hide();
                            }

                            if (window.Toast) {
                                Toast.fire({ icon: 'success', title: 'File CSV đang được tải xuống máy tính của bạn!' });
                            }
                        }, 800);
                    } catch (error) {
                        console.error(error);
                        alert('Lỗi khi kích hoạt tải CSV: ' + error.message);
                    } finally {
                        setTimeout(() => {
                            this.disabled = false;
                            this.innerHTML = originalContent;
                        }, 1200);
                    }
                });
            }

            // ==========================================
            // LOGIC NHẬP CSV VỚI POPUP CHỌN CỘT & BATCH
            // ==========================================
            const importDropZone = document.getElementById('modalImportDropZone');
            const importFileInput = document.getElementById('modalImportFileInput');
            const importDropContent = document.getElementById('modalImportDropContent');
            const importFileInfo = document.getElementById('modalImportFileInfo');
            const importFileName = document.getElementById('modalImportFileName');
            const importFileSize = document.getElementById('modalImportFileSize');
            const btnChangeImportFile = document.getElementById('btnChangeImportFile');

            const importColumnsContainer = document.getElementById('importColumnsContainer');
            const importColumnsList = document.getElementById('importColumnsList');
            const btnImportSelectAllCols = document.getElementById('btnImportSelectAllCols');
            const btnImportDeselectAllCols = document.getElementById('btnImportDeselectAllCols');
            const btnImportDefaultCols = document.getElementById('btnImportDefaultCols');

            const modalStatTotal = document.getElementById('modalStatTotal');
            const modalStatSuccess = document.getElementById('modalStatSuccess');
            const modalStatError = document.getElementById('modalStatError');
            const modalProgressArea = document.getElementById('modalProgressArea');
            const modalProgressBar = document.getElementById('modalProgressBar');
            const modalProgressText = document.getElementById('modalProgressText');
            const modalPercentText = document.getElementById('modalPercentText');
            const modalCurrentStatus = document.getElementById('modalCurrentStatus');
            const modalImportLog = document.getElementById('modalImportLog');
            const btnStartImportBatch = document.getElementById('btnStartImportBatch');

            let importJsonData = [];
            const IMPORT_BATCH_SIZE = 50;

            const addImportLog = (msg, type = 'info') => {
                const colorClass = type === 'success' ? 'text-success' : (type === 'error' ? 'text-danger' : (type === 'warn' ? 'text-warning' : 'text-white-50'));
                const div = document.createElement('div');
                div.className = `mb-1 ${colorClass}`;
                div.innerHTML = `<span class="opacity-50">></span> [${new Date().toLocaleTimeString()}] ${msg}`;
                modalImportLog.appendChild(div);
                modalImportLog.scrollTop = modalImportLog.scrollHeight;
            };

            const updateImportProgress = (current, total) => {
                const percent = total > 0 ? Math.round((current / total) * 100) : 0;
                modalProgressBar.style.width = `${percent}%`;
                modalProgressText.innerText = `Đang xử lý: ${current}/${total}`;
                modalPercentText.innerText = `${percent}%`;
            };

            if (importDropZone && importFileInput) {
                importDropZone.addEventListener('click', (e) => {
                    if (e.target !== btnChangeImportFile) {
                        importFileInput.click();
                    }
                });

                if (btnChangeImportFile) {
                    btnChangeImportFile.addEventListener('click', (e) => {
                        e.stopPropagation();
                        importFileInput.click();
                    });
                }

                importDropZone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    importDropZone.style.borderColor = '#3b82f6';
                    importDropZone.style.background = '#eff6ff';
                });

                importDropZone.addEventListener('dragleave', () => {
                    importDropZone.style.borderColor = '#cbd5e1';
                    importDropZone.style.background = '#f8fafc';
                });

                importDropZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    importDropZone.style.borderColor = '#cbd5e1';
                    importDropZone.style.background = '#f8fafc';
                    if (e.dataTransfer.files.length) {
                        handleSelectedImportFile(e.dataTransfer.files[0]);
                    }
                });

                importFileInput.addEventListener('change', (e) => {
                    if (e.target.files.length) {
                        handleSelectedImportFile(e.target.files[0]);
                    }
                });
            }

            function handleSelectedImportFile(file) {
                if (!file.name.match(/\.(csv|xlsx|xls)$/i)) {
                    alert('Định dạng tệp không hợp lệ. Vui lòng chọn tệp có đuôi .csv, .xlsx hoặc .xls');
                    return;
                }

                importFileName.innerText = file.name;
                importFileSize.innerText = `${Math.round(file.size / 1024)} KB`;
                importFileInfo.classList.remove('d-none');
                importDropContent.classList.add('d-none');

                modalCurrentStatus.innerText = 'Đang phân tích tệp...';
                addImportLog(`Đang đọc tệp: ${file.name} (${Math.round(file.size / 1024)} KB)...`, 'info');

                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[firstSheetName];

                        const rawRows = XLSX.utils.sheet_to_json(worksheet, { defval: '' });
                        importJsonData = [];
                        let skippedEmptyRows = 0;

                        rawRows.forEach((row, idx) => {
                            let hasData = false;
                            for (const key in row) {
                                if (!key.startsWith('_') && String(row[key]).trim() !== '') {
                                    hasData = true;
                                    break;
                                }
                            }
                            if (hasData) {
                                row._excel_row = idx + 2;
                                importJsonData.push(row);
                            } else {
                                skippedEmptyRows++;
                            }
                        });

                        const rawHeaderRows = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
                        const detectedHeaders = (rawHeaderRows && rawHeaderRows.length > 0) ? rawHeaderRows[0] : [];

                        modalStatTotal.innerText = importJsonData.length;
                        modalStatSuccess.innerText = '0';
                        modalStatError.innerText = '0';

                        if (importJsonData.length === 0) {
                            addImportLog('Tệp không có dữ liệu bài viết hợp lệ.', 'error');
                            btnStartImportBatch.disabled = true;
                            importColumnsContainer.classList.add('d-none');
                            return;
                        }

                        if (skippedEmptyRows > 0) {
                            addImportLog(`Đã tự động lọc bỏ ${skippedEmptyRows} dòng trống ở cuối tệp.`, 'info');
                        }

                        importColumnsList.innerHTML = '';
                        detectedHeaders.forEach((colName, idx) => {
                            if (!colName || String(colName).trim() === '') return;
                            const trimmedName = String(colName).trim();
                            const lowerName = trimmedName.toLowerCase();
                            const isDefaultCol = (
                                lowerName === 'id' ||
                                lowerName === 'tiêu đề' || lowerName === 'tieu de' || lowerName === 'title' ||
                                lowerName === 'slug' ||
                                lowerName.startsWith('nội dung') || lowerName.startsWith('noi dung') || lowerName.startsWith('content')
                            );

                            const colDiv = document.createElement('div');
                            colDiv.className = 'col-sm-6';
                            colDiv.innerHTML = `
                                <div class="form-check">
                                    <input class="form-check-input import-col-check" type="checkbox" value="${trimmedName}" id="impCol_${idx}" ${isDefaultCol ? 'checked' : ''} data-default="${isDefaultCol ? '1' : '0'}">
                                    <label class="form-check-label small text-truncate" for="impCol_${idx}" title="${trimmedName}">
                                        ${trimmedName}
                                    </label>
                                </div>
                            `;
                            importColumnsList.appendChild(colDiv);
                        });

                        importColumnsContainer.classList.remove('d-none');
                        btnStartImportBatch.disabled = false;
                        modalCurrentStatus.innerText = `Đã sẵn sàng (${importJsonData.length} bài)`;
                        addImportLog(`Đọc tệp thành công! Tìm thấy ${importJsonData.length} dòng và ${detectedHeaders.length} cột.`, 'success');
                    } catch (err) {
                        console.error(err);
                        addImportLog('Lỗi khi đọc file: ' + err.message, 'error');
                        modalCurrentStatus.innerText = 'Lỗi đọc tệp';
                        btnStartImportBatch.disabled = true;
                    }
                };
                reader.readAsArrayBuffer(file);
            }

            if (btnImportSelectAllCols) {
                btnImportSelectAllCols.addEventListener('click', () => {
                    document.querySelectorAll('.import-col-check').forEach(cb => cb.checked = true);
                });
            }
            if (btnImportDeselectAllCols) {
                btnImportDeselectAllCols.addEventListener('click', () => {
                    document.querySelectorAll('.import-col-check').forEach(cb => cb.checked = false);
                });
            }
            if (btnImportDefaultCols) {
                btnImportDefaultCols.addEventListener('click', () => {
                    document.querySelectorAll('.import-col-check').forEach(cb => {
                        cb.checked = cb.dataset.default === '1';
                    });
                });
            }

            if (btnStartImportBatch) {
                btnStartImportBatch.addEventListener('click', async () => {
                    if (!importJsonData.length) return;

                    const selectedCols = Array.from(document.querySelectorAll('.import-col-check:checked')).map(cb => cb.value);
                    if (selectedCols.length === 0) {
                        alert('Vui lòng chọn ít nhất một cột cần nhập vào hệ thống.');
                        return;
                    }

                    btnStartImportBatch.disabled = true;
                    btnStartImportBatch.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> ĐANG NHẬP DỮ LIỆU...';
                    modalProgressArea.classList.remove('d-none');
                    modalCurrentStatus.innerText = 'Đang xử lý các mẻ batch...';

                    let processedCount = 0;
                    let successCount = 0;
                    let errorCount = 0;

                    addImportLog(`Bắt đầu nhập ${importJsonData.length} bài viết (mỗi mẻ ${IMPORT_BATCH_SIZE} bài)...`, 'info');

                    for (let i = 0; i < importJsonData.length; i += IMPORT_BATCH_SIZE) {
                        const chunk = importJsonData.slice(i, i + IMPORT_BATCH_SIZE);
                        const batchIndex = Math.floor(i / IMPORT_BATCH_SIZE) + 1;
                        const totalBatches = Math.ceil(importJsonData.length / IMPORT_BATCH_SIZE);

                        addImportLog(`Đang gửi mẻ ${batchIndex}/${totalBatches} (${chunk.length} bài)...`, 'info');

                        try {
                            const response = await fetch("{{ route('admin.posts.import-batch') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    items: chunk,
                                    selected_columns: selectedCols
                                })
                            });

                            const result = await response.json();

                            if (result.success) {
                                processedCount += chunk.length;
                                successCount += (result.success_count || 0);

                                if (result.errors && result.errors.length) {
                                    errorCount += result.errors.length;
                                    result.errors.forEach(err => addImportLog(`⚠️ ${err}`, 'error'));
                                }

                                modalStatSuccess.innerText = successCount;
                                modalStatError.innerText = errorCount;
                                updateImportProgress(processedCount, importJsonData.length);
                            } else {
                                throw new Error(result.message || 'Lỗi xử lý từ máy chủ');
                            }
                        } catch (err) {
                            console.error(err);
                            addImportLog(`Lỗi tại mẻ ${batchIndex}: ${err.message}`, 'error');
                            errorCount += chunk.length;
                            modalStatError.innerText = errorCount;
                        }
                    }

                    modalCurrentStatus.innerText = 'Hoàn tất!';
                    addImportLog(`🎉 Quá trình nhập hoàn tất! Thành công: ${successCount}, Lỗi: ${errorCount}`, 'success');
                    btnStartImportBatch.innerHTML = '<i class="fa-solid fa-check me-2"></i> HOÀN TẤT NHẬP DỮ LIỆU';
                    btnStartImportBatch.classList.remove('btn-primary');
                    btnStartImportBatch.classList.add('btn-success');

                    if (window.Toast) {
                        Toast.fire({
                            icon: errorCount === 0 ? 'success' : 'warning',
                            title: `Đã nhập xong: ${successCount} thành công, ${errorCount} lỗi.`
                        });
                    }

                    setTimeout(() => {
                        if (confirm('Quá trình nhập dữ liệu đã hoàn tất! Bạn có muốn làm mới trang để xem danh sách bài viết cập nhật không?')) {
                            window.location.reload();
                        }
                    }, 1000);
                });
            }

            // ================================================
            // Checkboxes: Chọn tất cả, Bulk Action Bar
            // ================================================
            const checkAll = document.getElementById('checkAll');
            const itemChecks = document.querySelectorAll('.item-check');
            const bulkActionsBar = document.getElementById('bulkActionsBar');
            const bulkSelectedBadge = document.getElementById('bulkSelectedBadge');
            const btnBulkDelete = document.getElementById('btnBulkDelete');

            function updateBulkBar() {
                const checkedBoxes = Array.from(itemChecks).filter(cb => cb.checked);
                const count = checkedBoxes.length;

                if (bulkSelectedBadge) {
                    bulkSelectedBadge.textContent = count;
                }

                if (bulkActionsBar) {
                    if (count > 0) {
                        bulkActionsBar.classList.add('active');
                    } else {
                        bulkActionsBar.classList.remove('active');
                    }
                }

                if (btnBulkDelete) {
                    btnBulkDelete.disabled = (count === 0);
                }

                if (checkAll) {
                    checkAll.checked = (itemChecks.length > 0 && count === itemChecks.length);
                    checkAll.indeterminate = (count > 0 && count < itemChecks.length);
                }
            }

            if (checkAll && itemChecks.length > 0) {
                checkAll.addEventListener('change', function () {
                    itemChecks.forEach(cb => cb.checked = this.checked);
                    updateBulkBar();
                });

                itemChecks.forEach(cb => {
                    cb.addEventListener('change', updateBulkBar);
                });
            }

            const btnExportSelectedItems = document.getElementById('btnExportSelectedItems');
            if (btnExportSelectedItems && exportModalEl) {
                btnExportSelectedItems.addEventListener('click', function () {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        bootstrap.Modal.getOrCreateInstance(exportModalEl).show();
                    }
                });
            }

            // ================================================
            // MODAL: XÓA TỪ TXT
            // ================================================
            const deleteTxtModalEl = document.getElementById('deleteTxtModal');
            let deleteTxtModal = null;
            if (deleteTxtModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                deleteTxtModal = bootstrap.Modal.getOrCreateInstance(deleteTxtModalEl);
            }

            const deleteTxtFileInput = document.getElementById('deleteTxtFileInput');
            const deleteTxtPreview = document.getElementById('deleteTxtPreview');
            const deleteTxtCountBadge = document.getElementById('deleteTxtCountBadge');
            const deleteTxtIdList = document.getElementById('deleteTxtIdList');
            const deleteTxtBtnStart = document.getElementById('deleteTxtBtnStart');
            const deleteTxtBtnClose = document.getElementById('deleteTxtBtnClose');
            const deleteTxtStepUpload = document.getElementById('deleteTxtStepUpload');
            const deleteTxtStepProgress = document.getElementById('deleteTxtStepProgress');
            const deleteTxtProgressBar = document.getElementById('deleteTxtProgressBar');
            const deleteTxtProgressText = document.getElementById('deleteTxtProgressText');
            const deleteTxtLog = document.getElementById('deleteTxtLog');
            const statDeletedPosts = document.getElementById('statDeletedPosts');
            const statDeletedFiles = document.getElementById('statDeletedFiles');
            const statSkippedFiles = document.getElementById('statSkippedFiles');

            let parsedIds = [];
            let isRunning = false;

            function resetDeleteTxtModal() {
                parsedIds = [];
                isRunning = false;
                if (deleteTxtFileInput) deleteTxtFileInput.value = '';
                if (deleteTxtPreview) deleteTxtPreview.style.display = 'none';
                if (deleteTxtBtnStart) {
                    deleteTxtBtnStart.disabled = true;
                    deleteTxtBtnStart.innerHTML = '<i class="fa-solid fa-trash-can me-1"></i> Bắt đầu xóa';
                    deleteTxtBtnStart.classList.remove('btn-success');
                    deleteTxtBtnStart.classList.add('btn-danger');
                }
                if (deleteTxtStepUpload) deleteTxtStepUpload.style.display = '';
                if (deleteTxtStepProgress) deleteTxtStepProgress.style.display = 'none';
                if (deleteTxtBtnClose) {
                    deleteTxtBtnClose.disabled = false;
                    deleteTxtBtnClose.textContent = 'Hủy / Đóng';
                    deleteTxtBtnClose.onclick = null;
                }
                if (deleteTxtProgressBar) {
                    deleteTxtProgressBar.style.width = '0%';
                    deleteTxtProgressBar.classList.add('progress-bar-animated');
                }
                if (deleteTxtProgressText) deleteTxtProgressText.textContent = '0 / 0';
                if (deleteTxtLog) deleteTxtLog.innerHTML = '';
            }

            if (deleteTxtModalEl) {
                deleteTxtModalEl.addEventListener('show.bs.modal', function () {
                    if (!isRunning) {
                        resetDeleteTxtModal();
                    }
                });
            }

            // Parse file TXT khi chọn
            if (deleteTxtFileInput) {
                deleteTxtFileInput.addEventListener('change', function (e) {
                    const file = e.target.files[0];
                    if (!file) {
                        parsedIds = [];
                        if (deleteTxtPreview) deleteTxtPreview.style.display = 'none';
                        if (deleteTxtBtnStart) deleteTxtBtnStart.disabled = true;
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        const lines = ev.target.result.split(/\r?\n/);
                        parsedIds = lines
                            .map(l => parseInt(l.trim(), 10))
                            .filter(n => !isNaN(n) && n > 0);

                        parsedIds = [...new Set(parsedIds)];

                        if (deleteTxtCountBadge) deleteTxtCountBadge.textContent = parsedIds.length + ' ID';
                        if (deleteTxtIdList) {
                            deleteTxtIdList.textContent = parsedIds.slice(0, 100).join(', ') + (parsedIds.length > 100 ? ' ... (và ' + (parsedIds.length - 100) + ' ID nữa)' : '');
                        }
                        if (deleteTxtPreview) deleteTxtPreview.style.display = parsedIds.length > 0 ? '' : 'none';
                        if (deleteTxtBtnStart) deleteTxtBtnStart.disabled = parsedIds.length === 0;
                    };
                    reader.readAsText(file, 'UTF-8');
                });
            }

            // Logic xóa
            function addDeleteLog(msg, type = 'info') {
                if (!deleteTxtLog) return;
                const colors = { info: '#a8d8ea', success: '#b7e4c7', error: '#f8b4b4', warn: '#ffe69c' };
                const line = document.createElement('div');
                line.style.color = colors[type] || '#fff';
                line.textContent = '[' + new Date().toLocaleTimeString() + '] ' + msg;
                deleteTxtLog.appendChild(line);
                deleteTxtLog.scrollTop = deleteTxtLog.scrollHeight;
            }

            if (deleteTxtBtnStart) {
                deleteTxtBtnStart.addEventListener('click', async function () {
                    if (isRunning || parsedIds.length === 0) return;

                    if (!confirm('Bạn có chắc chắn muốn xóa VĨNH VIỄN ' + parsedIds.length + ' bài viết và tất cả ảnh của chúng?\nHành động này KHÔNG THỂ hoàn tác!')) {
                        return;
                    }

                    isRunning = true;
                    deleteTxtBtnStart.disabled = true;
                    deleteTxtBtnStart.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang xóa...';
                    if (deleteTxtBtnClose) deleteTxtBtnClose.disabled = true;
                    if (deleteTxtStepUpload) deleteTxtStepUpload.style.display = 'none';
                    if (deleteTxtStepProgress) deleteTxtStepProgress.style.display = '';
                    if (deleteTxtLog) deleteTxtLog.innerHTML = '';
                    if (statDeletedPosts) statDeletedPosts.textContent = '0';
                    if (statDeletedFiles) statDeletedFiles.textContent = '0';
                    if (statSkippedFiles) statSkippedFiles.textContent = '0';

                    const batchSize = 200;
                    const totalIds = parsedIds.length;
                    let processedIds = 0;
                    let totalDeletedPosts = 0;
                    let totalDeletedFiles = 0;
                    let totalSkippedFiles = 0;

                    addDeleteLog('Bắt đầu xóa ' + totalIds + ' bài viết...', 'info');

                    for (let i = 0; i < parsedIds.length; i += batchSize) {
                        const batch = parsedIds.slice(i, i + batchSize);
                        const batchNum = Math.floor(i / batchSize) + 1;

                        addDeleteLog('Mẻ ' + batchNum + ': đang gửi ' + batch.length + ' ID...', 'info');

                        try {
                            const response = await fetch('{{ route('admin.posts.destroy-from-txt') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ ids: batch }),
                            });

                            const result = await response.json();

                            if (result.success) {
                                processedIds += batch.length;
                                totalDeletedPosts += result.count || 0;
                                totalDeletedFiles += result.deleted_files || 0;
                                totalSkippedFiles += result.skipped_files || 0;

                                if (statDeletedPosts) statDeletedPosts.textContent = totalDeletedPosts;
                                if (statDeletedFiles) statDeletedFiles.textContent = totalDeletedFiles;
                                if (statSkippedFiles) statSkippedFiles.textContent = totalSkippedFiles;

                                const pct = Math.min(100, Math.round(processedIds / totalIds * 100));
                                if (deleteTxtProgressBar) deleteTxtProgressBar.style.width = pct + '%';
                                if (deleteTxtProgressText) deleteTxtProgressText.textContent = Math.min(processedIds, totalIds) + ' / ' + totalIds;

                                addDeleteLog(
                                    'Mẻ ' + batchNum + ': ✅ xóa ' + result.count + ' bài, xóa ' + result.deleted_files + ' ảnh, giữ lại ' + result.skipped_files + ' ảnh',
                                    'success'
                                );
                            } else {
                                addDeleteLog('Mẻ ' + batchNum + ': ⚠️ ' + (result.message || 'Lỗi không xác định'), 'error');
                            }
                        } catch (err) {
                            addDeleteLog('Mẻ ' + batchNum + ': ❌ Lỗi mạng: ' + err.message, 'error');
                        }
                    }

                    addDeleteLog('Hoàn tất! Tổng: ' + totalDeletedPosts + ' bài đã xóa, ' + totalDeletedFiles + ' ảnh đã xóa, ' + totalSkippedFiles + ' ảnh giữ lại.', 'success');

                    if (deleteTxtProgressBar) deleteTxtProgressBar.classList.remove('progress-bar-animated');
                    deleteTxtBtnStart.innerHTML = '<i class="fa-solid fa-check me-1"></i> Hoàn tất!';
                    deleteTxtBtnStart.classList.remove('btn-danger');
                    deleteTxtBtnStart.classList.add('btn-success');
                    if (deleteTxtBtnClose) {
                        deleteTxtBtnClose.disabled = false;
                        deleteTxtBtnClose.textContent = 'Đóng và làm mới trang';
                        deleteTxtBtnClose.onclick = function () { window.location.reload(); };
                    }
                    isRunning = false;
                });
            }

            // Xử lý đóng/mở Dropdown 3 chấm tùy chọn bài viết
            document.addEventListener('click', function (e) {
                const moreBtn = e.target.closest('.post-action-more-btn');
                const allMenus = document.querySelectorAll('.post-action-dropdown-menu');

                if (moreBtn) {
                    e.preventDefault();
                    e.stopPropagation();

                    const parent = moreBtn.closest('.post-action-dropdown');
                    const menu = parent ? parent.querySelector('.post-action-dropdown-menu') : null;
                    const isOpen = menu && menu.classList.contains('show');

                    // Đóng tất cả menu đang mở
                    allMenus.forEach(m => m.classList.remove('show'));

                    if (menu && !isOpen) {
                        // Tự động lật ngược lên trên nếu gần đáy màn hình
                        const rect = moreBtn.getBoundingClientRect();
                        const spaceBelow = window.innerHeight - rect.bottom;
                        if (spaceBelow < 180) {
                            menu.style.top = 'auto';
                            menu.style.bottom = 'calc(100% + 4px)';
                        } else {
                            menu.style.top = 'calc(100% + 4px)';
                            menu.style.bottom = 'auto';
                        }
                        menu.classList.add('show');
                    }
                } else if (!e.target.closest('.post-action-dropdown-menu')) {
                    allMenus.forEach(m => m.classList.remove('show'));
                }
            });

            // Đóng menu khi nhấn Escape
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.post-action-dropdown-menu.show').forEach(m => m.classList.remove('show'));
                }
            });
        });
    </script>
@endpush
