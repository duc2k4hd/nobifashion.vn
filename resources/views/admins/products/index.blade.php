@extends('admins.layouts.master')

@section('title', 'Quản lý sản phẩm')
@section('page-title', 'Sản phẩm')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/products-icon.png') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('admins/vendor/slimselect/slimselect.css') }}">
@endpush

@push('styles')
    <style>
        .products-container {
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

        /* Quick Filter Tabs / Stats Bar */
        .status-tabs-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
        .filter-grid {
            display: grid;
            grid-template-columns: 2.2fr 1.2fr 1.3fr 1.1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }
        .filter-grid-secondary {
            display: grid;
            grid-template-columns: 1fr 1fr 1.1fr 1.3fr 1.1fr auto;
            gap: 12px;
            align-items: flex-end;
        }
        @media (max-width: 1280px) {
            .filter-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .filter-grid-secondary {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 768px) {
            .filter-grid,
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

        /* SlimSelect Custom Styling for Filter */
        .filter-field .ss-main {
            width: 100% !important;
            height: 38px !important;
            min-height: 38px !important;
            padding: 0 10px !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            background-color: #f8fafc !important;
            font-size: 13px !important;
            color: #1e293b !important;
            box-sizing: border-box !important;
            transition: all 0.15s ease;
        }
        .filter-field .ss-main:focus,
        .filter-field .ss-main.ss-open-below,
        .filter-field .ss-main.ss-open-above {
            background-color: #ffffff !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15) !important;
        }
        .filter-field .ss-main .ss-single {
            font-size: 13px !important;
            color: #1e293b !important;
            line-height: 36px !important;
            padding: 0 !important;
            margin: 0 !important;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .filter-field .ss-main .ss-placeholder {
            color: #64748b !important;
            font-size: 13px !important;
            line-height: 36px !important;
            padding: 0 !important;
        }
        .filter-field .ss-main .ss-deselect {
            margin-left: 6px !important;
            color: #94a3b8 !important;
        }
        .filter-field .ss-main .ss-deselect:hover {
            color: #ef4444 !important;
        }
        .filter-field .ss-main .ss-arrow path {
            stroke: #64748b !important;
            stroke-width: 2 !important;
        }
        .ss-content {
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15) !important;
            font-size: 13px !important;
            z-index: 1060 !important;
            overflow: hidden !important;
        }
        .ss-content .ss-search {
            padding: 8px !important;
            background-color: #f8fafc !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }
        .ss-content .ss-search input {
            height: 34px !important;
            font-size: 13px !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            padding: 6px 10px !important;
            box-shadow: none !important;
            background-color: #ffffff !important;
        }
        .ss-content .ss-search input:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15) !important;
        }
        .ss-content .ss-list {
            max-height: 320px !important;
        }
        .ss-content .ss-list .ss-option {
            padding: 7px 12px !important;
            font-size: 13px !important;
            transition: background 0.12s ease;
        }
        .ss-content .ss-list .ss-option.is-root-category {
            font-weight: 700 !important;
            color: #0f172a !important;
            background-color: #f8fafc;
            border-top: 1px solid #f1f5f9;
        }
        .ss-content .ss-list .ss-option.is-child-category {
            color: #334155 !important;
        }
        .ss-content .ss-list .ss-option:hover,
        .ss-content .ss-list .ss-option.ss-highlighted {
            background-color: #eff6ff !important;
            color: #1d4ed8 !important;
        }
        .ss-content .ss-list .ss-option.ss-selected {
            background-color: #dbeafe !important;
            color: #1e40af !important;
            font-weight: 600 !important;
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
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            margin-bottom: 24px;
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

        /* Product Cell */
        .product-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 360px;
        }
        .product-thumb-wrap {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            overflow: hidden;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .product-thumb-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .product-thumb-placeholder {
            color: #94a3b8;
            font-size: 16px;
        }
        .product-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }
        .product-name {
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
        .product-name:hover {
            color: #2563eb;
        }
        .product-meta-row {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .sku-badge {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11px;
            color: #475569;
            background: #f1f5f9;
            padding: 1px 6px;
            border-radius: 4px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        /* Feature Badges */
        .feature-badge {
            font-size: 10px;
            font-weight: 600;
            padding: 1px 6px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            line-height: 1.4;
        }
        .badge-flash-sale {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .badge-featured {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }
        .badge-variants {
            background: #f5f3ff;
            color: #7c3aed;
            border: 1px solid #ddd6fe;
        }

        /* Price display */
        .price-box {
            display: flex;
            flex-direction: column;
            gap: 1px;
            white-space: nowrap;
        }
        .price-current {
            font-weight: 700;
            color: #0f172a;
            font-size: 13.5px;
        }
        .price-current.has-sale {
            color: #dc2626;
        }
        .price-original {
            font-size: 11.5px;
            color: #94a3b8;
            text-decoration: line-through;
        }

        /* Stock Pill */
        .stock-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
            white-space: nowrap;
        }
        .stock-in {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .stock-low {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }
        .stock-out {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
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
            user-select: none;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .status-badge-active {
            background: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }
        .status-badge-inactive {
            background: #f8fafc;
            color: #64748b;
            border-color: #cbd5e1;
        }
        .status-badge-trash {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
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
            border: 1px solid transparent;
            cursor: pointer;
            line-height: 1;
            flex-shrink: 0;
            background: #ffffff;
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
            color: #475569;
            border-color: #cbd5e1;
        }
        .btn-action-edit:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
        }
        .btn-action-del {
            color: #dc2626;
            border-color: #fecaca;
        }
        .btn-action-del:hover {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fca5a5;
        }
        .btn-action-restore {
            color: #16a34a;
            border-color: #bbf7d0;
            background: #f0fdf4;
        }
        .btn-action-restore:hover {
            background: #16a34a;
            color: #ffffff;
            border-color: #16a34a;
        }
        .btn-action-force-del {
            color: #ffffff;
            background: #0f172a;
            border-color: #0f172a;
        }
        .btn-action-force-del:hover {
            background: #dc2626;
            border-color: #dc2626;
        }

        /* Pagination Box */
        .products-pagination-box {
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

        /* Buttons Standard */
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
        .btn-modern-green,
        .btn-modern-excel {
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
        .btn-modern-green:hover,
        .btn-modern-excel:hover {
            background: #15803d;
            color: #ffffff;
            border-color: #15803d;
        }
    </style>
@endpush

@section('content')
<div class="products-container">

    {{-- Page Header --}}
    <div class="page-header-box">
        <div>
            <h2>
                <i class="fa-solid fa-boxes-stacked text-primary"></i>
                Quản lý sản phẩm
            </h2>
            <p>Kiểm soát kho hàng, cấu hình bảng giá, thuộc tính biến thể và quản lý trạng thái kinh doanh.</p>
        </div>

        <div class="header-actions">
            <a href="{{ route('admin.products.export-excel') }}" class="btn-modern-green" title="Xuất toàn bộ danh sách sản phẩm ra file Excel">
                <i class="fa-solid fa-file-arrow-down"></i> Xuất Excel
            </a>
            <a href="{{ route('admin.products.import-excel') }}" class="btn-modern-excel" title="Nhập danh sách sản phẩm từ file Excel">
                <i class="fa-solid fa-file-excel"></i> Import Excel
            </a>
            <a href="{{ route('admin.products.create') }}" class="btn-modern-primary" title="Tạo sản phẩm mới">
                <i class="fa-solid fa-plus"></i> Thêm sản phẩm
            </a>
        </div>
    </div>

    {{-- Quick Status Filter Tabs --}}
    @php
        $currentStatus = request('status', '');
    @endphp
    <div class="status-tabs-row">
        {{-- Tất cả --}}
        <a href="{{ route('admin.products.index', request()->except(['page', 'status'])) }}"
           class="status-tab-item {{ $currentStatus === '' ? 'active' : '' }}">
            <span class="status-tab-title">
                <i class="fa-solid fa-layer-group"></i> Tất cả sản phẩm
            </span>
            <span class="status-tab-count">{{ number_format($stats->total ?? 0) }}</span>
        </a>

        {{-- Đang bán --}}
        <a href="{{ route('admin.products.index', array_merge(request()->except(['page', 'status']), ['status' => 'active'])) }}"
           class="status-tab-item {{ $currentStatus === 'active' ? 'active' : '' }}">
            <span class="status-tab-title" style="color: #059669;">
                <i class="fa-solid fa-circle-check"></i> Đang bán
            </span>
            <span class="status-tab-count">{{ number_format($stats->active ?? 0) }}</span>
        </a>

        {{-- Tạm ẩn --}}
        <a href="{{ route('admin.products.index', array_merge(request()->except(['page', 'status']), ['status' => 'inactive'])) }}"
           class="status-tab-item {{ $currentStatus === 'inactive' ? 'active' : '' }}">
            <span class="status-tab-title" style="color: #64748b;">
                <i class="fa-solid fa-eye-slash"></i> Tạm ẩn
            </span>
            <span class="status-tab-count">{{ number_format($stats->inactive ?? 0) }}</span>
        </a>

        {{-- Hết hàng --}}
        <a href="{{ route('admin.products.index', array_merge(request()->except(['page', 'status']), ['status' => 'out_of_stock'])) }}"
           class="status-tab-item {{ $currentStatus === 'out_of_stock' ? 'active' : '' }}">
            <span class="status-tab-title" style="color: #dc2626;">
                <i class="fa-solid fa-triangle-exclamation"></i> Hết hàng
            </span>
            <span class="status-tab-count">{{ number_format($stats->out_of_stock ?? 0) }}</span>
        </a>

        {{-- Thùng rác --}}
        <a href="{{ route('admin.products.index', array_merge(request()->except(['page', 'status']), ['status' => 'trash'])) }}"
           class="status-tab-item {{ $currentStatus === 'trash' ? 'active' : '' }}">
            <span class="status-tab-title" style="color: #b91c1c;">
                <i class="fa-regular fa-trash-can"></i> Thùng rác
            </span>
            <span class="status-tab-count">{{ number_format($stats->trash ?? 0) }}</span>
        </a>
    </div>

    {{-- Filter Panel --}}
    <div class="filter-panel">
        <div class="filter-panel-header">
            <span class="filter-panel-title">
                <i class="fa-solid fa-filter text-primary"></i> Bộ lọc & Tìm kiếm sản phẩm
            </span>
            <div class="filter-results-summary">
                Hiển thị <strong>{{ number_format($products->firstItem() ?? 0) }} - {{ number_format($products->lastItem() ?? 0) }}</strong> trên tổng <strong>{{ number_format($products->total()) }}</strong> kết quả
            </div>
        </div>

        <form method="GET" action="{{ route('admin.products.index') }}" id="filter-form">
            {{-- Giữ nguyên status hiện tại nếu có --}}
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            {{-- Hàng lọc chính --}}
            <div class="filter-grid">
                {{-- Từ khóa --}}
                <div class="filter-field filter-field-search">
                    <label for="filter-keyword">Tìm kiếm</label>
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input
                        id="filter-keyword"
                        type="text"
                        name="keyword"
                        value="{{ request('keyword') }}"
                        placeholder="Tên sản phẩm, mã SKU hoặc đường dẫn slug..."
                    >
                </div>

                {{-- Hãng --}}
                <div class="filter-field">
                    <label for="filter-brand">Hãng / Thương hiệu</label>
                    <select id="filter-brand" name="brand_id">
                        <option data-placeholder="true" value="">Tất cả hãng</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" {{ (string) request('brand_id') === (string) $brand->id ? 'selected' : '' }}>
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Danh mục --}}
                <div class="filter-field">
                    <label for="filter-category">Danh mục</label>
                    <select id="filter-category" name="category_id">
                        <option data-placeholder="true" value="">Tất cả danh mục</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                    class="{{ ($category->depth ?? 0) === 0 ? 'is-root-category' : 'is-child-category' }}"
                                    {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>
                                {{ $category->hierarchical_name ?? $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Kho hàng --}}
                <div class="filter-field">
                    <label for="filter-stock-status">Tồn kho</label>
                    <select id="filter-stock-status" name="stock_status">
                        <option value="">Tất cả kho hàng</option>
                        <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>Còn hàng</option>
                        <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Hết hàng</option>
                    </select>
                </div>

                {{-- Số lượng mỗi trang --}}
                <div class="filter-field">
                    <label for="filter-per-page">Hiển thị / trang</label>
                    <select id="filter-per-page" name="per_page">
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}" {{ (int) $perPage === (int) $option ? 'selected' : '' }}>
                                {{ $option }} dòng
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Hàng lọc mở rộng --}}
            <div class="filter-grid-secondary">
                {{-- Nổi bật --}}
                <div class="filter-field">
                    <label for="filter-featured">Sản phẩm nổi bật</label>
                    <select id="filter-featured" name="is_featured">
                        <option value="">Tất cả</option>
                        <option value="1" {{ request('is_featured') === '1' ? 'selected' : '' }}>Chỉ nổi bật</option>
                        <option value="0" {{ request('is_featured') === '0' ? 'selected' : '' }}>Không nổi bật</option>
                    </select>
                </div>

                {{-- Flash Sale --}}
                <div class="filter-field">
                    <label for="filter-flash-sale">Flash Sale</label>
                    <select id="filter-flash-sale" name="flash_sale_status">
                        <option value="">Tất cả</option>
                        <option value="1" {{ request('flash_sale_status') === '1' ? 'selected' : '' }}>Đang Flash Sale</option>
                        <option value="0" {{ request('flash_sale_status') === '0' ? 'selected' : '' }}>Không Flash Sale</option>
                    </select>
                </div>

                {{-- Biến thể --}}
                <div class="filter-field">
                    <label for="filter-variants">Loại sản phẩm</label>
                    <select id="filter-variants" name="has_variants">
                        <option value="">Tất cả</option>
                        <option value="1" {{ request('has_variants') === '1' ? 'selected' : '' }}>Có biến thể</option>
                        <option value="0" {{ request('has_variants') === '0' ? 'selected' : '' }}>Sản phẩm đơn</option>
                    </select>
                </div>

                {{-- Sắp xếp --}}
                <div class="filter-field">
                    <label for="filter-sort">Sắp xếp theo</label>
                    <select id="filter-sort" name="sort_by">
                        <option value="latest" {{ request('sort_by', 'latest') === 'latest' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="oldest" {{ request('sort_by') === 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                        <option value="name_asc" {{ request('sort_by') === 'name_asc' ? 'selected' : '' }}>Tên A &rarr; Z</option>
                        <option value="name_desc" {{ request('sort_by') === 'name_desc' ? 'selected' : '' }}>Tên Z &rarr; A</option>
                        <option value="price_asc" {{ request('sort_by') === 'price_asc' ? 'selected' : '' }}>Giá tăng dần</option>
                        <option value="price_desc" {{ request('sort_by') === 'price_desc' ? 'selected' : '' }}>Giá giảm dần</option>
                        <option value="stock_desc" {{ request('sort_by') === 'stock_desc' ? 'selected' : '' }}>Tồn kho cao nhất</option>
                        <option value="stock_asc" {{ request('sort_by') === 'stock_asc' ? 'selected' : '' }}>Tồn kho thấp nhất</option>
                    </select>
                </div>

                {{-- Nút bấm Lọc & Reset --}}
                <div class="filter-actions-group" style="padding-bottom: 2px;">
                    <button type="submit" class="btn-modern-primary" style="height: 38px;">
                        <i class="fa-solid fa-filter"></i> Lọc
                    </button>
                    <a href="{{ route('admin.products.index', request('status') ? ['status' => request('status')] : []) }}"
                       class="btn-modern-secondary" style="height: 38px;" title="Xóa bộ lọc">
                        <i class="fa-solid fa-arrow-rotate-left"></i> Đặt lại
                    </a>
                </div>
            </div>

            {{-- Thông báo chế độ tìm kiếm thông minh Progressive Search --}}
            @if(($searchMeta['mode'] ?? null) === 'exact_phrase')
                <div class="search-hint-box search-hint-exact">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>Đang ưu tiên các sản phẩm khớp đúng cụm từ khóa <strong>"{{ request('keyword') }}"</strong>.</span>
                </div>
            @elseif(($searchMeta['mode'] ?? null) === 'progressive')
                <div class="search-hint-box search-hint-fallback">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Không có sản phẩm khớp chính xác. Hệ thống đang tìm kiếm theo các cụm từ tương đồng:
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
                Danh sách kết quả
                @if(request('status') === 'trash')
                    <span class="badge bg-danger ms-2" style="font-size: 11px; font-weight: 500;">Thùng rác</span>
                @endif
            </h3>

            {{-- Bulk Actions Bar (Tự động hiện khi tick checkbox) --}}
            <div class="bulk-actions-bar" id="bulkActionsBar">
                <span class="d-inline-flex align-items-center gap-1">
                    <strong id="selectedCountText">0</strong> sản phẩm được chọn:
                </span>

                {{-- Nút "Những sản phẩm đã chọn" --}}
                <button type="button" class="btn btn-sm btn-outline-primary bg-white fw-semibold" id="btnOpenSelectedModal" title="Xem và quản lý danh sách sản phẩm đã chọn">
                    <i class="fa-solid fa-list-check"></i> Những sản phẩm đã chọn (<span id="btnSelectedCount">0</span>)
                </button>

                {{-- Nút "Xuất Excel đã chọn" --}}
                <button type="button" class="btn btn-sm btn-outline-success bg-white fw-semibold" id="btnExportSelectedProducts" title="Xuất file Excel cho những sản phẩm đang được chọn">
                    <i class="fa-solid fa-file-arrow-down text-success"></i> Xuất Excel đã chọn
                </button>

                <form action="{{ route('admin.products.bulk-action') }}" method="POST" id="bulk-action-form" class="d-inline-flex gap-1 m-0">
                    @csrf

                    @if(request('status') === 'trash')
                        <button type="submit" class="btn btn-sm btn-outline-success bg-white" name="bulk_action" value="restore">
                            <i class="fa-solid fa-rotate-left"></i> Khôi phục đã chọn
                        </button>
                        <button
                            type="submit"
                            class="btn btn-sm btn-danger"
                            name="bulk_action"
                            value="force_delete"
                            onclick="return confirm('Xóa vĩnh viễn các sản phẩm đã chọn? Dữ liệu này sẽ mất hoàn toàn và KHÔNG thể khôi phục!')"
                        >
                            <i class="fa-solid fa-trash-can"></i> Xóa vĩnh viễn đã chọn
                        </button>
                    @else
                        <button type="submit" class="btn btn-sm btn-outline-success bg-white" name="bulk_action" value="show" title="Hiển thị sản phẩm ra website">
                            <i class="fa-solid fa-eye"></i> Hiện
                        </button>
                        <button type="submit" class="btn btn-sm btn-outline-secondary bg-white" name="bulk_action" value="hide" title="Tạm ẩn sản phẩm khỏi website">
                            <i class="fa-solid fa-eye-slash"></i> Ẩn
                        </button>
                        <button
                            type="submit"
                            class="btn btn-sm btn-outline-danger bg-white"
                            name="bulk_action"
                            value="delete"
                            onclick="return confirm('Chuyển các sản phẩm đã chọn vào Thùng rác? Bạn vẫn có thể khôi phục sau.')"
                        >
                            <i class="fa-regular fa-trash-can"></i> Bỏ vào thùng rác
                        </button>
                    @endif
                </form>
            </div>
        </div>

        {{-- Banner khi kích hoạt chế độ chỉ xem sản phẩm đã chọn trên bảng --}}
        <div id="filterSelectedBanner" class="alert alert-primary py-2 px-3 mx-3 mt-3 mb-0 d-none align-items-center justify-content-between" style="font-size: 13px; border-radius: 6px;">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-filter text-primary"></i>
                <span>Đang chỉ hiển thị <strong><span id="filterBannerCount">0</span></strong> sản phẩm bạn đã chọn.</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary bg-white py-0 px-2" id="btnCancelTableFilter" style="font-size: 12px; font-weight: 500;">
                <i class="fa-solid fa-xmark"></i> Bỏ lọc (Hiện toàn bộ sản phẩm)
            </button>
        </div>

        <div class="table-responsive">
            <table class="clean-table" id="productsTable">
                <thead>
                    <tr>
                        <th style="width: 44px; text-align: center;">
                            <input type="checkbox" id="select-all-products" class="form-check-input mt-0" style="cursor: pointer;">
                        </th>
                        <th>Sản phẩm</th>
                        <th style="width: 140px;">Hãng</th>
                        <th style="width: 160px;">Danh mục</th>
                        <th style="width: 130px;">Giá bán</th>
                        <th style="width: 110px; text-align: center;">Tồn kho</th>
                        <th style="width: 120px; text-align: center;">Trạng thái</th>
                        <th style="width: 120px; text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $imgUrl = null;
                            if ($product->primaryImage?->url) {
                                $imgUrl = str_starts_with($product->primaryImage->url, 'http')
                                    ? $product->primaryImage->url
                                    : asset('clients/assets/img/clothes/' . $product->primaryImage->url);
                            }
                            $hasDiscount = $product->sale_price && $product->sale_price < $product->price;
                            $hasFlashSale = (bool) $product->currentFlashSaleItem;
                        @endphp
                        <tr>
                            {{-- Checkbox --}}
                            <td style="text-align: center;">
                                <input
                                    type="checkbox"
                                    name="selected[]"
                                    value="{{ $product->id }}"
                                    class="product-checkbox form-check-input mt-0"
                                    form="bulk-action-form"
                                    style="cursor: pointer;"
                                    data-id="{{ $product->id }}"
                                    data-name="{{ e($product->name) }}"
                                    data-sku="{{ e($product->sku ?? '') }}"
                                    data-brand="{{ e($product->brand?->name ?? '—') }}"
                                    data-category="{{ e($product->primaryCategory?->name ?? '—') }}"
                                    data-image="{{ $imgUrl ?? asset('clients/assets/img/clothes/no-image.webp') }}"
                                    data-price="{{ number_format($product->price, 0, ',', '.') }}đ"
                                    data-sale-price="{{ $hasDiscount ? number_format($product->sale_price, 0, ',', '.') . 'đ' : '' }}"
                                    data-stock="{{ number_format($product->stock_quantity) }}"
                                    data-status-label="{{ $product->trashed() ? 'Đã xóa' : ($product->is_active ? 'Đang bán' : 'Tạm ẩn') }}"
                                    data-status-badge="{{ $product->trashed() ? 'status-badge-trash' : ($product->is_active ? 'status-badge-active' : 'status-badge-inactive') }}"
                                    data-status-icon="{{ $product->trashed() ? 'fa-trash-can' : ($product->is_active ? 'fa-circle-check' : 'fa-eye-slash') }}"
                                >
                            </td>

                            {{-- Sản phẩm (Ảnh + Tên + SKU + Badges) --}}
                            <td>
                                <div class="product-cell">
                                    <div class="product-thumb-wrap">
                                        @if($imgUrl)
                                            <img
                                                src="{{ $imgUrl }}"
                                                alt="{{ $product->name }}"
                                                class="product-thumb-img"
                                                loading="lazy"
                                                onerror="this.onerror=null;this.parentElement.innerHTML='<span class=\'product-thumb-placeholder\'><i class=\'fa-solid fa-shirt\'></i></span>';"
                                            >
                                        @else
                                            <span class="product-thumb-placeholder">
                                                <i class="fa-solid fa-shirt"></i>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="product-info">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="product-name" title="{{ $product->name }}">
                                            {{ $product->name }}
                                        </a>
                                        <div class="product-meta-row">
                                            @if($product->sku)
                                                <span class="sku-badge" title="Mã SKU">
                                                    {{ $product->sku }}
                                                </span>
                                            @endif

                                            @if($hasFlashSale)
                                                <span class="feature-badge badge-flash-sale" title="Đang trong chương trình Flash Sale">
                                                    <i class="fa-solid fa-bolt"></i> Flash Sale
                                                </span>
                                            @endif

                                            @if($product->is_featured)
                                                <span class="feature-badge badge-featured" title="Sản phẩm nổi bật">
                                                    <i class="fa-solid fa-star"></i> Nổi bật
                                                </span>
                                            @endif

                                            @if($product->has_variants)
                                                <span class="feature-badge badge-variants" title="Có nhiều biến thể phân loại">
                                                    <i class="fa-solid fa-layer-group"></i> Biến thể
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Hãng --}}
                            <td>
                                @if($product->brand)
                                    <span style="font-weight: 500; color: #1e293b;">{{ $product->brand->name }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>

                            {{-- Danh mục --}}
                            <td>
                                @if($product->primaryCategory)
                                    <span style="color: #475569; font-size: 12.5px;">{{ $product->primaryCategory->name }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>

                            {{-- Giá bán --}}
                            <td>
                                <div class="price-box">
                                    @if($hasDiscount)
                                        <span class="price-current has-sale">{{ number_format($product->sale_price) }}đ</span>
                                        <span class="price-original">{{ number_format($product->price) }}đ</span>
                                    @else
                                        <span class="price-current">{{ number_format($product->price) }}đ</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Tồn kho --}}
                            <td style="text-align: center;">
                                @if($product->stock_quantity > 10)
                                    <span class="stock-pill stock-in" title="Số lượng: {{ number_format($product->stock_quantity) }}">
                                        {{ number_format($product->stock_quantity) }} sp
                                    </span>
                                @elseif($product->stock_quantity > 0)
                                    <span class="stock-pill stock-low" title="Sắp hết hàng!">
                                        {{ number_format($product->stock_quantity) }} sp
                                    </span>
                                @else
                                    <span class="stock-pill stock-out">
                                        Hết hàng
                                    </span>
                                @endif
                            </td>

                            {{-- Trạng thái --}}
                            <td style="text-align: center;">
                                @if($product->trashed())
                                    <span class="status-badge status-badge-trash">
                                        <i class="fa-solid fa-trash-can"></i> Đã xóa
                                    </span>
                                @elseif($product->is_active)
                                    <span class="status-badge status-badge-active">
                                        <i class="fa-solid fa-circle-check"></i> Đang bán
                                    </span>
                                @else
                                    <span class="status-badge status-badge-inactive">
                                        <i class="fa-solid fa-eye-slash"></i> Tạm ẩn
                                    </span>
                                @endif
                            </td>

                            {{-- Thao tác --}}
                            <td style="text-align: right;">
                                <div class="action-btn-group">
                                    @if($product->trashed())
                                        {{-- Nút Khôi phục --}}
                                        <form action="{{ route('admin.products.restore', $product->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-table-action btn-action-restore" title="Khôi phục sản phẩm này">
                                                <i class="fa-solid fa-rotate-left"></i>
                                            </button>
                                        </form>

                                        {{-- Nút Xóa vĩnh viễn --}}
                                        <form
                                            action="{{ route('admin.products.force-delete', $product->id) }}"
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('CẢNH BÁO: Xóa vĩnh viễn sản phẩm &quot;{{ addslashes($product->name) }}&quot;? Hành động này KHÔNG thể hoàn tác!')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-table-action btn-action-force-del" title="Xóa vĩnh viễn">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    @else
                                        {{-- Nút Xem ngoài web --}}
                                        @if(Route::has('client.products.show') && $product->slug)
                                            <a href="{{ route('client.products.show', $product->slug) }}" target="_blank" class="btn-table-action btn-action-view" title="Xem sản phẩm ngoài website">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                        @endif

                                        {{-- Nút Chỉnh sửa --}}
                                        <a href="{{ route('admin.products.edit', $product) }}" class="btn-table-action btn-action-edit" title="Chỉnh sửa chi tiết">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>

                                        {{-- Nút Xóa mềm (Bỏ vào thùng rác) --}}
                                        <form
                                            action="{{ route('admin.products.destroy', $product) }}"
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Chuyển sản phẩm &quot;{{ addslashes($product->name) }}&quot; vào Thùng rác?')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-table-action btn-action-del" title="Chuyển vào thùng rác">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 48px 16px; color: #94a3b8;">
                                <i class="fa-solid fa-box-open" style="font-size: 36px; margin-bottom: 12px; display: block; color: #cbd5e1;"></i>
                                <div style="font-weight: 600; font-size: 14px; color: #64748b;">Không tìm thấy sản phẩm nào</div>
                                <div style="font-size: 12.5px; margin-top: 4px;">Thử thay đổi từ khóa tìm kiếm hoặc điều chỉnh lại các tiêu chí lọc phía trên.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Phân trang sạch đẹp --}}
        <div class="products-pagination-box">
            <div class="pagination-count-text">
                Hiển thị <strong>{{ number_format($products->firstItem() ?? 0) }}</strong> - <strong>{{ number_format($products->lastItem() ?? 0) }}</strong> trên tổng <strong>{{ number_format($products->total()) }}</strong> sản phẩm
            </div>

            @if($products->hasPages())
                <nav aria-label="Phân trang sản phẩm">
                    <ul class="clean-pagination-list">
                        {{-- Nút Trang trước --}}
                        @if ($products->onFirstPage())
                            <li class="page-item disabled">
                                <span class="page-link" aria-label="Trang trước" title="Trang trước">
                                    <i class="fa-solid fa-angle-left"></i>
                                </span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $products->previousPageUrl() }}" rel="prev" aria-label="Trang trước" title="Trang trước">
                                    <i class="fa-solid fa-angle-left"></i>
                                </a>
                            </li>
                        @endif

                        {{-- Danh sách trang --}}
                        @php
                            $currentPage = $products->currentPage();
                            $lastPage = $products->lastPage();
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($lastPage, $currentPage + 2);
                        @endphp

                        @if($startPage > 1)
                            <li class="page-item">
                                <a class="page-link" href="{{ $products->url(1) }}">1</a>
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
                                    <a class="page-link" href="{{ $products->url($page) }}">{{ $page }}</a>
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
                                <a class="page-link" href="{{ $products->url($lastPage) }}">{{ $lastPage }}</a>
                            </li>
                        @endif

                        {{-- Nút Trang sau --}}
                        @if ($products->hasMorePages())
                            <li class="page-item">
                                <a class="page-link" href="{{ $products->nextPageUrl() }}" rel="next" aria-label="Trang sau" title="Trang sau">
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

    {{-- Modal: Những sản phẩm đã chọn --}}
    <div class="modal fade" id="selectedProductsModal" tabindex="-1" aria-labelledby="selectedProductsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light py-3">
                    <h5 class="modal-title fs-6 fw-bold text-dark m-0 d-flex align-items-center gap-2" id="selectedProductsModalLabel">
                        <i class="fa-solid fa-boxes-stacked text-primary"></i>
                        Những sản phẩm đã chọn
                        <span class="badge bg-primary rounded-pill px-2" id="modalSelectedBadge">0</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body p-3">
                    {{-- Thanh công cụ trong modal --}}
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom flex-wrap gap-2">
                        <div class="small text-muted">
                            <i class="fa-regular fa-circle-check text-success me-1"></i> Danh sách các sản phẩm đang được tích chọn.
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-danger" id="modalBtnDeselectAll">
                                <i class="fa-solid fa-xmark"></i> Bỏ chọn tất cả
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="modalBtnToggleTableFilter">
                                <i class="fa-solid fa-filter"></i> <span id="modalFilterButtonText">Chỉ hiện trên bảng chính</span>
                            </button>
                        </div>
                    </div>

                    {{-- Bảng danh sách sản phẩm trong modal --}}
                    <div class="table-responsive" style="max-height: 380px;">
                        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 40px; text-align: center;">#</th>
                                    <th>Sản phẩm</th>
                                    <th style="width: 110px;">Hãng</th>
                                    <th style="width: 120px;">Danh mục</th>
                                    <th style="width: 120px;">Giá bán</th>
                                    <th style="width: 90px; text-align: center;">Tồn kho</th>
                                    <th style="width: 110px; text-align: center;">Trạng thái</th>
                                    <th style="width: 70px; text-align: center;">Bỏ chọn</th>
                                </tr>
                            </thead>
                            <tbody id="selectedProductsTableBody">
                                {{-- Render qua JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <div class="d-inline-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-success bg-white" id="modalBtnExportExcel" title="Tải file Excel các sản phẩm đang chọn">
                            <i class="fa-solid fa-file-arrow-down text-success"></i> Xuất Excel (<span id="modalExportCount">0</span>)
                        </button>
                        @if(request('status') === 'trash')
                            <button type="button" class="btn btn-sm btn-success" onclick="triggerBulkSubmit('restore')">
                                <i class="fa-solid fa-rotate-left"></i> Khôi phục đã chọn
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="triggerBulkSubmit('force_delete')">
                                <i class="fa-solid fa-trash-can"></i> Xóa vĩnh viễn đã chọn
                            </button>
                        @else
                            <button type="button" class="btn btn-sm btn-success" onclick="triggerBulkSubmit('show')">
                                <i class="fa-solid fa-eye"></i> Hiện
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="triggerBulkSubmit('hide')">
                                <i class="fa-solid fa-eye-slash"></i> Ẩn
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="triggerBulkSubmit('delete')">
                                <i class="fa-regular fa-trash-can"></i> Bỏ vào thùng rác
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const selectAll = document.getElementById('select-all-products');
        const productCheckboxes = document.querySelectorAll('.product-checkbox');
        const bulkActionsBar = document.getElementById('bulkActionsBar');
        const selectedCountText = document.getElementById('selectedCountText');
        const btnSelectedCount = document.getElementById('btnSelectedCount');
        const btnExportSelectedProducts = document.getElementById('btnExportSelectedProducts');
        const bulkForm = document.getElementById('bulk-action-form');
        const btnOpenSelectedModal = document.getElementById('btnOpenSelectedModal');
        const modalElement = document.getElementById('selectedProductsModal');
        const modalSelectedBadge = document.getElementById('modalSelectedBadge');
        const modalExportCount = document.getElementById('modalExportCount');
        const modalBtnExportExcel = document.getElementById('modalBtnExportExcel');
        const selectedProductsTableBody = document.getElementById('selectedProductsTableBody');
        const modalBtnDeselectAll = document.getElementById('modalBtnDeselectAll');
        const modalBtnToggleTableFilter = document.getElementById('modalBtnToggleTableFilter');
        const modalFilterButtonText = document.getElementById('modalFilterButtonText');
        const filterSelectedBanner = document.getElementById('filterSelectedBanner');
        const filterBannerCount = document.getElementById('filterBannerCount');
        const btnCancelTableFilter = document.getElementById('btnCancelTableFilter');

        let isTableFiltered = false;

        function getCheckedBoxes() {
            return Array.from(productCheckboxes).filter(cb => cb.checked);
        }

        function updateBulkBar() {
            const checkedBoxes = getCheckedBoxes();
            const count = checkedBoxes.length;

            if (selectedCountText) {
                selectedCountText.textContent = count;
            }
            if (btnSelectedCount) {
                btnSelectedCount.textContent = count;
            }
            if (modalSelectedBadge) {
                modalSelectedBadge.textContent = count;
            }
            if (modalExportCount) {
                modalExportCount.textContent = count;
            }

            if (bulkActionsBar) {
                if (count > 0) {
                    bulkActionsBar.classList.add('active');
                } else {
                    bulkActionsBar.classList.remove('active');
                    if (isTableFiltered) {
                        clearTableFilter();
                    }
                }
            }

            if (selectAll) {
                selectAll.checked = (productCheckboxes.length > 0 && count === productCheckboxes.length);
                selectAll.indeterminate = (count > 0 && count < productCheckboxes.length);
            }

            // Nếu đang trong chế độ lọc bảng thì cập nhật lại visibility
            if (isTableFiltered) {
                applyTableFilter();
            }
        }

        function renderSelectedModal() {
            const checkedBoxes = getCheckedBoxes();
            if (!selectedProductsTableBody) return;

            if (checkedBoxes.length === 0) {
                selectedProductsTableBody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="fa-solid fa-inbox fs-3 mb-2 d-block text-secondary"></i>
                            Chưa có sản phẩm nào được chọn.
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            checkedBoxes.forEach((cb, index) => {
                const id = cb.value;
                const name = cb.dataset.name || 'Sản phẩm #' + id;
                const sku = cb.dataset.sku ? `<small class="text-muted d-block">SKU: ${cb.dataset.sku}</small>` : '';
                const brand = cb.dataset.brand || '—';
                const category = cb.dataset.category || '—';
                const image = cb.dataset.image || '';
                const price = cb.dataset.price || '—';
                const salePrice = cb.dataset.salePrice ? `<small class="text-danger d-block">${cb.dataset.salePrice}</small>` : '';
                const stock = cb.dataset.stock || '0';
                const statusLabel = cb.dataset.statusLabel || '—';
                const statusBadge = cb.dataset.statusBadge || 'badge bg-secondary';
                const statusIcon = cb.dataset.statusIcon || 'fa-circle-dot';

                html += `
                    <tr id="modal-row-${id}">
                        <td style="text-align: center; color: #94a3b8; font-weight: 500;">${index + 1}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="${image}" alt="${name}" style="width: 36px; height: 36px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0; flex-shrink: 0;" onerror="this.src='/clients/assets/img/clothes/no-image.webp'">
                                <div style="min-width: 0;">
                                    <div class="fw-semibold text-dark text-truncate" style="max-width: 260px;" title="${name}">${name}</div>
                                    ${sku}
                                </div>
                            </div>
                        </td>
                        <td><span class="text-secondary">${brand}</span></td>
                        <td><span class="text-secondary">${category}</span></td>
                        <td>
                            <span class="fw-bold">${price}</span>
                            ${salePrice}
                        </td>
                        <td style="text-align: center;"><span class="badge bg-light text-dark border">${stock} sp</span></td>
                        <td style="text-align: center;">
                            <span class="status-badge ${statusBadge}" style="font-size: 11px; padding: 2px 8px;">
                                <i class="fa-solid ${statusIcon}"></i> ${statusLabel}
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="btn btn-sm btn-outline-danger p-1 rounded btn-unselect-item" data-id="${id}" title="Bỏ chọn sản phẩm này" style="width: 28px; height: 28px; line-height: 1;">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            selectedProductsTableBody.innerHTML = html;

            // Gắn sự kiện bỏ chọn từng dòng trong modal
            selectedProductsTableBody.querySelectorAll('.btn-unselect-item').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    const targetCb = Array.from(productCheckboxes).find(cb => cb.value == id);
                    if (targetCb) {
                        targetCb.checked = false;
                        updateBulkBar();
                        renderSelectedModal();
                        if (getCheckedBoxes().length === 0 && modalElement) {
                            const modal = bootstrap.Modal.getInstance(modalElement);
                            if (modal) modal.hide();
                        }
                    }
                });
            });
        }

        function applyTableFilter() {
            const checkedBoxes = getCheckedBoxes();
            const checkedIds = new Set(checkedBoxes.map(cb => cb.value));

            productCheckboxes.forEach(cb => {
                const tr = cb.closest('tr');
                if (tr) {
                    if (checkedIds.has(cb.value)) {
                        tr.style.display = '';
                    } else {
                        tr.style.display = 'none';
                    }
                }
            });

            isTableFiltered = true;
            if (filterSelectedBanner) {
                filterSelectedBanner.classList.remove('d-none');
                filterSelectedBanner.classList.add('d-flex');
            }
            if (filterBannerCount) {
                filterBannerCount.textContent = checkedBoxes.length;
            }
            if (modalFilterButtonText) {
                modalFilterButtonText.textContent = 'Bỏ lọc (Hiện toàn bộ bảng)';
            }
        }

        function clearTableFilter() {
            productCheckboxes.forEach(cb => {
                const tr = cb.closest('tr');
                if (tr) {
                    tr.style.display = '';
                }
            });

            isTableFiltered = false;
            if (filterSelectedBanner) {
                filterSelectedBanner.classList.add('d-none');
                filterSelectedBanner.classList.remove('d-flex');
            }
            if (modalFilterButtonText) {
                modalFilterButtonText.textContent = 'Chỉ hiện trên bảng chính';
            }
        }

        // Mở Modal xem những sản phẩm đã chọn
        if (btnOpenSelectedModal) {
            btnOpenSelectedModal.addEventListener('click', () => {
                const checkedBoxes = getCheckedBoxes();
                if (checkedBoxes.length === 0) {
                    alert('Chưa có sản phẩm nào được chọn.');
                    return;
                }
                renderSelectedModal();
                if (modalElement) {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                    modal.show();
                }
            });
        }

        // Bỏ chọn tất cả từ Modal
        if (modalBtnDeselectAll) {
            modalBtnDeselectAll.addEventListener('click', () => {
                productCheckboxes.forEach(cb => {
                    cb.checked = false;
                });
                updateBulkBar();
                clearTableFilter();
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) modal.hide();
                }
            });
        }

        // Chuyển đổi lọc bảng từ Modal
        if (modalBtnToggleTableFilter) {
            modalBtnToggleTableFilter.addEventListener('click', () => {
                if (isTableFiltered) {
                    clearTableFilter();
                } else {
                    applyTableFilter();
                }
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) modal.hide();
                }
            });
        }

        // Nút hủy lọc từ banner trên bảng
        if (btnCancelTableFilter) {
            btnCancelTableFilter.addEventListener('click', () => {
                clearTableFilter();
            });
        }

        // Chọn tất cả
        if (selectAll) {
            selectAll.addEventListener('change', () => {
                productCheckboxes.forEach(cb => {
                    // Nếu đang lọc chỉ chọn những hàng đang hiển thị
                    const tr = cb.closest('tr');
                    if (!isTableFiltered || (tr && tr.style.display !== 'none')) {
                        cb.checked = selectAll.checked;
                    }
                });
                updateBulkBar();
            });
        }

        // Checkbox từng sản phẩm
        productCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkBar);
        });

        // Kiểm tra trước khi submit form thao tác hàng loạt
        if (bulkForm) {
            bulkForm.addEventListener('submit', (e) => {
                const checkedBoxes = getCheckedBoxes();
                if (checkedBoxes.length === 0) {
                    e.preventDefault();
                    alert('Vui lòng chọn ít nhất một sản phẩm để thực hiện thao tác.');
                }
            });
        }

        // Hàm trigger submit từ Modal
        window.triggerBulkSubmit = function (action) {
            const checkedBoxes = getCheckedBoxes();
            if (checkedBoxes.length === 0) {
                alert('Vui lòng chọn ít nhất một sản phẩm.');
                return;
            }

            if (action === 'delete') {
                if (!confirm(`Chuyển ${checkedBoxes.length} sản phẩm đã chọn vào Thùng rác? Bạn vẫn có thể khôi phục sau.`)) {
                    return;
                }
            } else if (action === 'force_delete') {
                if (!confirm(`CẢNH BÁO: Xóa VĨNH VIỄN ${checkedBoxes.length} sản phẩm đã chọn? Dữ liệu này KHÔNG thể khôi phục!`)) {
                    return;
                }
            }

            if (bulkForm) {
                // Xóa action input cũ nếu có
                const oldInput = bulkForm.querySelector('input[name="bulk_action"]');
                if (oldInput) oldInput.remove();

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'bulk_action';
                actionInput.value = action;
                bulkForm.appendChild(actionInput);

                bulkForm.submit();
            }
        };

        // Hàm thực hiện xuất file Excel cho các sản phẩm đã chọn
        function exportSelectedToExcel() {
            const checkedBoxes = getCheckedBoxes();
            if (checkedBoxes.length === 0) {
                alert('Vui lòng chọn ít nhất một sản phẩm để xuất Excel.');
                return;
            }

            const exportForm = document.createElement('form');
            exportForm.method = 'POST';
            exportForm.action = '{{ route('admin.products.export-excel') }}';
            exportForm.style.display = 'none';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            exportForm.appendChild(csrfInput);

            checkedBoxes.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                exportForm.appendChild(input);
            });

            document.body.appendChild(exportForm);
            exportForm.submit();
            setTimeout(() => exportForm.remove(), 2000);
        }

        if (btnExportSelectedProducts) {
            btnExportSelectedProducts.addEventListener('click', exportSelectedToExcel);
        }

        if (modalBtnExportExcel) {
            modalBtnExportExcel.addEventListener('click', exportSelectedToExcel);
        }
    });
</script>
<script src="{{ asset('admins/vendor/slimselect/slimselect.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof SlimSelect !== 'undefined') {
            const brandSelectEl = document.getElementById('filter-brand');
            if (brandSelectEl) {
                new SlimSelect({
                    select: brandSelectEl,
                    settings: {
                        placeholderText: 'Tất cả hãng',
                        allowDeselect: true,
                        searchPlaceholder: 'Tìm kiếm hãng/thương hiệu...',
                        searchText: 'Không tìm thấy hãng nào',
                    }
                });
            }

            const categorySelectEl = document.getElementById('filter-category');
            if (categorySelectEl) {
                new SlimSelect({
                    select: categorySelectEl,
                    settings: {
                        placeholderText: 'Tất cả danh mục',
                        allowDeselect: true,
                        searchPlaceholder: 'Tìm kiếm danh mục...',
                        searchText: 'Không tìm thấy danh mục nào',
                    }
                });
            }
        }
    });
</script>
@endpush
