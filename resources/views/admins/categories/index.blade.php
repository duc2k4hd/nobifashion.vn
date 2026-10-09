@extends('admins.layouts.master')

@section('title', 'Quản lý danh mục sản phẩm')
@section('page-title', 'Danh mục sản phẩm')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/category-icon.png') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.css">
@endpush

@push('styles')
    <style>
        .categories-container {
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

        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }
        .stat-card-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 2px;
        }
        .stat-card-val {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
        }
        .stat-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            font-size: 16px;
        }

        /* Filter Panel */
        .filter-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .filter-form-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
            gap: 12px;
            align-items: flex-end;
        }
        @media (max-width: 1200px) {
            .filter-form-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 768px) {
            .filter-form-grid {
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

        /* Table Card */
        .table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
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

        /* Bulk Action Bar */
        .bulk-actions-bar {
            display: none;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            font-size: 12.5px;
            color: #1e40af;
        }
        .bulk-actions-bar.active {
            display: inline-flex;
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
        .clean-table tbody tr.dragging td {
            background: #f1f5f9;
            opacity: 0.7;
        }

        /* Drag Handle */
        .drag-handle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            cursor: grab;
            color: #94a3b8;
            border-radius: 4px;
            transition: color 0.15s, background-color 0.15s;
        }
        .drag-handle:hover {
            color: #2563eb;
            background-color: #eff6ff;
        }

        /* Category Item Design */
        .cat-item-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .cat-thumb {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            flex-shrink: 0;
        }
        .cat-thumb-placeholder {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 14px;
            flex-shrink: 0;
        }
        .cat-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .cat-name {
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .cat-slug {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11.5px;
            color: #64748b;
            background: #f1f5f9;
            padding: 1px 6px;
            border-radius: 4px;
            display: inline-block;
            width: fit-content;
        }
        .tree-indent {
            display: inline-flex;
            align-items: center;
            color: #cbd5e1;
            font-weight: 700;
            margin-right: 4px;
            font-size: 14px;
        }
        .badge-root-tag {
            font-size: 10.5px;
            font-weight: 600;
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            padding: 1px 6px;
            border-radius: 4px;
        }
        .badge-child-tag {
            font-size: 10.5px;
            font-weight: 500;
            background: #f1f5f9;
            color: #475569;
            padding: 1px 6px;
            border-radius: 4px;
        }

        /* Count Pills */
        .count-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            height: 24px;
            padding: 0 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .count-pill-products {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .count-pill-children {
            background: #f5f3ff;
            color: #7c3aed;
            border: 1px solid #ddd6fe;
        }

        /* Status Toggle Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
            border: 1px solid transparent;
        }
        .status-badge-active {
            background: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }
        .status-badge-active:hover {
            background: #d1fae5;
        }
        .status-badge-inactive {
            background: #f8fafc;
            color: #64748b;
            border-color: #cbd5e1;
        }
        .status-badge-inactive:hover {
            background: #f1f5f9;
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
            gap: 5px;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            height: 30px;
            border: 1px solid transparent;
            cursor: pointer;
            white-space: nowrap !important;
            line-height: 1;
            flex-shrink: 0;
        }
        .btn-action-quick {
            background: #eff6ff;
            color: #2563eb;
            border-color: #bfdbfe;
            font-weight: 600;
        }
        .btn-action-quick:hover {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }
        .btn-action-quick i {
            font-size: 11px;
        }
        .btn-action-full {
            background: #ffffff;
            color: #475569;
            border-color: #cbd5e1;
            width: 30px;
            height: 30px;
            padding: 0;
        }
        .btn-action-full:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
        }
        .btn-action-del {
            background: #ffffff;
            color: #dc2626;
            border-color: #fecaca;
            width: 30px;
            height: 30px;
            padding: 0;
        }
        .btn-action-del:hover {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fca5a5;
        }
        .btn-action-del[disabled] {
            background: #f8fafc;
            color: #cbd5e1;
            border-color: #e2e8f0;
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* Pagination Styling */
        .categories-pagination-box {
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

        /* Quick Edit Modal */
        .quick-edit-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(2px);
            z-index: 1050;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .quick-edit-backdrop.active {
            display: flex;
        }
        .quick-edit-modal-box {
            background: #ffffff;
            border-radius: 12px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }
        .modal-header-clean {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .modal-header-clean h4 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-modal-close {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 18px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }
        .btn-modal-close:hover {
            color: #0f172a;
        }
        .modal-tabs {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 0 20px;
        }
        .modal-tab-btn {
            padding: 10px 16px;
            border: none;
            background: none;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            border-bottom: 2px solid transparent;
            cursor: pointer;
        }
        .modal-tab-btn.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
        }
        .modal-body-clean {
            padding: 20px;
            overflow-y: auto;
        }
        .modal-footer-clean {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding: 14px 20px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        /* App Alerts */
        .app-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 18px;
        }
        .app-alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .app-alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .app-alert-warning {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }
    </style>
@endpush

@section('content')
<div class="categories-container">

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="app-alert app-alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="app-alert app-alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if(session('warning'))
        <div class="app-alert app-alert-warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>{{ session('warning') }}</div>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="page-header-box">
        <div>
            <h2>
                <i class="fa-solid fa-folder-tree text-secondary"></i>
                Danh mục sản phẩm
            </h2>
            <p>Quản lý hệ thống cây danh mục phân cấp cha - con và sắp xếp thứ tự hiển thị trên website.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#exportCategoriesModal">
                <i class="fa-solid fa-file-export"></i>
                <span>Xuất dữ liệu</span>
            </button>
            <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#importCategoriesModal">
                <i class="fa-solid fa-file-import"></i>
                <span>Nhập dữ liệu</span>
            </button>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                <i class="fa-solid fa-plus"></i>
                <span>Thêm danh mục</span>
            </a>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="stats-row">
        <div class="stat-card">
            <div>
                <div class="stat-card-label">Tổng danh mục</div>
                <div class="stat-card-val">{{ $stats['total'] ?? $categories->total() }}</div>
            </div>
            <div class="stat-card-icon">
                <i class="fa-solid fa-layer-group"></i>
            </div>
        </div>

        <div class="stat-card">
            <div>
                <div class="stat-card-label">Danh mục gốc (Cha)</div>
                <div class="stat-card-val">{{ $stats['root'] ?? 0 }}</div>
            </div>
            <div class="stat-card-icon text-primary">
                <i class="fa-solid fa-folder"></i>
            </div>
        </div>

        <div class="stat-card">
            <div>
                <div class="stat-card-label">Đang hoạt động</div>
                <div class="stat-card-val text-success">{{ $stats['active'] ?? 0 }}</div>
            </div>
            <div class="stat-card-icon text-success">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <div class="stat-card">
            <div>
                <div class="stat-card-label">Đang tạm ẩn</div>
                <div class="stat-card-val text-muted">{{ $stats['inactive'] ?? 0 }}</div>
            </div>
            <div class="stat-card-icon">
                <i class="fa-solid fa-eye-slash"></i>
            </div>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="filter-panel">
        <form class="filter-form-grid" method="GET" action="{{ route('admin.categories.index') }}">
            {{-- Tìm kiếm --}}
            <div class="filter-field">
                <label for="filter-keyword">Tìm kiếm</label>
                <input type="text" id="filter-keyword" name="keyword"
                       placeholder="Nhập tên danh mục hoặc slug..."
                       value="{{ request('keyword') }}">
            </div>

            {{-- Cấp bậc --}}
            <div class="filter-field">
                <label for="filter-level">Cấp bậc</label>
                <select id="filter-level" name="level">
                    <option value="">-- Tất cả cấp --</option>
                    <option value="root" {{ request('level') === 'root' ? 'selected' : '' }}>📁 Danh mục gốc (Cha)</option>
                    <option value="child" {{ request('level') === 'child' ? 'selected' : '' }}>↳ Danh mục con</option>
                </select>
            </div>

            {{-- Trạng thái --}}
            <div class="filter-field">
                <label for="filter-status">Trạng thái</label>
                <select id="filter-status" name="status">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Tạm ẩn</option>
                </select>
            </div>

            {{-- Sắp xếp theo --}}
            <div class="filter-field">
                <label for="filter-sort">Sắp xếp theo</label>
                <select id="filter-sort" name="sort">
                    <option value="sort_order" {{ request('sort') === 'sort_order' || !request('sort') ? 'selected' : '' }}>Thứ tự hiển thị</option>
                    <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Tên danh mục A-Z</option>
                    <option value="created_at" {{ request('sort') === 'created_at' ? 'selected' : '' }}>Mới tạo</option>
                    <option value="updated_at" {{ request('sort') === 'updated_at' ? 'selected' : '' }}>Vừa cập nhật</option>
                </select>
            </div>

            {{-- Chiều sắp xếp --}}
            <div class="filter-field">
                <label for="filter-direction">Chiều sắp xếp</label>
                <select id="filter-direction" name="direction">
                    <option value="asc" {{ request('direction') === 'asc' || !request('direction') ? 'selected' : '' }}>Tăng dần</option>
                    <option value="desc" {{ request('direction') === 'desc' ? 'selected' : '' }}>Giảm dần</option>
                </select>
            </div>

            {{-- Nút thao tác --}}
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-secondary d-inline-flex align-items-center gap-1" style="height: 38px;">
                    <i class="fa-solid fa-filter"></i> Lọc
                </button>
                @if(request()->hasAny(['keyword', 'status', 'level', 'sort', 'direction']))
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center" style="height: 38px;" title="Xóa bộ lọc">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                    @if($categories->total() > 0)
                        <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-1 fw-semibold text-nowrap" id="btnExportFiltered" style="height: 38px;" title="Xuất toàn bộ {{ $categories->total() }} danh mục đang lọc trên tất cả các trang">
                            <i class="fa-solid fa-file-export text-primary"></i>
                            <span>Xuất kết quả lọc ({{ $categories->total() }})</span>
                        </button>
                    @endif
                @endif
            </div>
        </form>
    </div>

    {{-- Table Card --}}
    <div class="table-card">
        <div class="table-card-header">
            <h3>
                <i class="fa-solid fa-list text-secondary"></i>
                Danh sách danh mục
            </h3>

            {{-- Bulk Actions Bar (Hiện khi có checkbox được chọn) --}}
            <div class="bulk-actions-bar" id="bulkActionsBar">
                <span><strong id="selectedCountText">0</strong> danh mục được chọn:</span>
                <div class="d-inline-flex gap-1 m-0 align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-primary bg-white fw-semibold" id="btnExportSelected">
                        <i class="fa-solid fa-file-export me-1 text-primary"></i> Xuất đang chọn
                    </button>
                    <form action="{{ route('admin.categories.bulk-action') }}" method="POST" id="category-bulk-form" class="d-inline-flex gap-1 m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-success bg-white" name="bulk_action" value="show">
                            <i class="fa-solid fa-check"></i> Hiện
                        </button>
                        <button type="submit" class="btn btn-sm btn-outline-secondary bg-white" name="bulk_action" value="hide">
                            <i class="fa-solid fa-eye-slash"></i> Ẩn
                        </button>
                        <button type="submit" class="btn btn-sm btn-outline-danger bg-white" name="bulk_action" value="delete"
                                onclick="return confirm('Xác nhận xóa các danh mục đã chọn? Các danh mục có sản phẩm hoặc danh mục con sẽ được tự động bỏ qua để đảm bảo an toàn.');">
                            <i class="fa-regular fa-trash-can"></i> Xóa
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="clean-table" id="categoriesTable">
                <thead>
                    <tr>
                        <th style="width: 44px; text-align: center;">
                            <input type="checkbox" id="select-all-categories" class="form-check-input mt-0" style="cursor: pointer;">
                        </th>
                        <th style="width: 44px; text-align: center;" title="Kéo thả để sắp xếp thứ tự">
                            <i class="fa-solid fa-grip-vertical text-muted"></i>
                        </th>
                        <th>Tên danh mục / Đường dẫn</th>
                        <th style="width: 180px;">Danh mục cha</th>
                        <th style="width: 100px; text-align: center;">Sản phẩm</th>
                        <th style="width: 100px; text-align: center;">Danh mục con</th>
                        <th style="width: 130px; text-align: center;">Trạng thái</th>
                        <th style="width: 210px; text-align: right; white-space: nowrap;">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="categoriesList">
                    @forelse($categories as $category)
                        <tr data-category-id="{{ $category->id }}" data-sort="{{ $category->sort_order ?? 0 }}">
                            {{-- Checkbox --}}
                            <td style="text-align: center;">
                                <input type="checkbox" name="selected[]" value="{{ $category->id }}"
                                       class="form-check-input category-checkbox mt-0" form="category-bulk-form" style="cursor: pointer;">
                            </td>

                            {{-- Drag Handle --}}
                            <td style="text-align: center;">
                                <span class="drag-handle" title="Kéo thả để đổi thứ tự">
                                    <i class="fa-solid fa-grip-vertical"></i>
                                </span>
                            </td>

                            {{-- Tên danh mục --}}
                            <td>
                                <div class="cat-item-wrap">
                                    {{-- Ảnh Thumbnail --}}
                                    @if(!empty($category->image))
                                        <img src="{{ str_starts_with($category->image, 'http') ? $category->image : asset('clients/assets/img/categories/' . $category->image) }}"
                                             alt="{{ $category->name }}" class="cat-thumb"
                                             onerror="this.onerror=null; this.src='{{ asset('clients/assets/img/categories/no-image.webp') }}';">
                                    @else
                                        <div class="cat-thumb-placeholder">
                                            <i class="fa-regular fa-image"></i>
                                        </div>
                                    @endif

                                    {{-- Tên & Slug --}}
                                    <div class="cat-info">
                                        <div class="cat-name">
                                            @if($category->parent_id)
                                                <span class="tree-indent" title="Danh mục con">↳</span>
                                            @endif
                                            <span>{{ $category->name }}</span>
                                            @if(!$category->parent_id)
                                                <span class="badge-root-tag">Gốc</span>
                                            @endif
                                        </div>
                                        <div>
                                            <span class="cat-slug">{{ $category->slug }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Danh mục cha --}}
                            <td>
                                @if($category->parent)
                                    <span class="text-secondary fw-medium" title="Danh mục cha: {{ $category->parent->name }}">
                                        <i class="fa-regular fa-folder me-1 text-primary"></i>
                                        {{ $category->parent->name }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Số lượng sản phẩm --}}
                            <td style="text-align: center;">
                                <span class="count-pill count-pill-products" title="{{ $category->product_count ?? 0 }} sản phẩm liên kết">
                                    {{ $category->product_count ?? 0 }}
                                </span>
                            </td>

                            {{-- Số lượng danh mục con --}}
                            <td style="text-align: center;">
                                @if(($category->child_count ?? 0) > 0)
                                    <span class="count-pill count-pill-children" title="{{ $category->child_count }} danh mục con">
                                        {{ $category->child_count }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>

                            {{-- Trạng thái (Click đổi trạng thái tức thì) --}}
                            <td style="text-align: center;">
                                <span class="status-badge status-badge-{{ $category->is_active ? 'active' : 'inactive' }}"
                                      data-category-id="{{ $category->id }}"
                                      title="Nhấp để đổi trạng thái">
                                    <i class="fa-solid fa-{{ $category->is_active ? 'circle-check' : 'circle-xmark' }}"></i>
                                    <span>{{ $category->is_active ? 'Hoạt động' : 'Tạm ẩn' }}</span>
                                </span>
                            </td>

                            {{-- Thao tác --}}
                            <td style="text-align: right; white-space: nowrap;">
                                <div class="action-btn-group">
                                    {{-- Nút Sửa Nhanh --}}
                                    <button type="button" class="btn-table-action btn-action-quick quick-edit-btn"
                                            data-category-id="{{ $category->id }}"
                                            data-category-name="{{ $category->name }}"
                                            data-category-slug="{{ $category->slug }}"
                                            data-category-sort-order="{{ $category->sort_order ?? 0 }}"
                                            data-category-active="{{ $category->is_active ? '1' : '0' }}"
                                            data-category-meta-title="{{ $category->meta_title ?? '' }}"
                                            data-category-meta-description="{{ $category->meta_description ?? '' }}"
                                            data-category-meta-keywords="{{ $category->meta_keywords ?? '' }}"
                                            data-category-image="{{ $category->image ? (str_starts_with($category->image, 'http') ? $category->image : asset('clients/assets/img/categories/' . $category->image)) : '' }}"
                                            title="Sửa nhanh thông tin">
                                        <i class="fa-solid fa-bolt me-1"></i><span>Sửa nhanh</span>
                                    </button>

                                    {{-- Nút Sửa Chi tiết --}}
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="btn-table-action btn-action-full" title="Chỉnh sửa toàn bộ">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>

                                    {{-- Nút Xóa --}}
                                    @php
                                        $hasProducts = ($category->product_count ?? 0) > 0;
                                        $hasChildren = ($category->child_count ?? 0) > 0;
                                        $canDelete = !$hasProducts && !$hasChildren;
                                    @endphp
                                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa danh mục &quot;{{ addslashes($category->name) }}&quot; không?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-table-action btn-action-del"
                                                {{ !$canDelete ? 'disabled' : '' }}
                                                title="{{ !$canDelete ? 'Không thể xóa danh mục đang có sản phẩm hoặc danh mục con' : 'Xóa danh mục' }}">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 48px 16px; color: #94a3b8;">
                                <i class="fa-solid fa-folder-open" style="font-size: 32px; margin-bottom: 12px; display: block; color: #cbd5e1;"></i>
                                <div style="font-weight: 500; font-size: 14px; color: #64748b;">Không tìm thấy danh mục nào</div>
                                <div style="font-size: 12px; margin-top: 4px;">Thử thay đổi từ khóa hoặc bộ lọc tìm kiếm phía trên.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Phân trang sạch đẹp --}}
        <div class="categories-pagination-box">
            <div class="pagination-count-text">
                Hiển thị <strong>{{ $categories->firstItem() ?? 0 }}</strong> - <strong>{{ $categories->lastItem() ?? 0 }}</strong> trên tổng số <strong>{{ $categories->total() }}</strong> danh mục
            </div>

            @if($categories->hasPages())
                <nav aria-label="Phân trang danh mục">
                    <ul class="clean-pagination-list">
                        {{-- Nút Trang trước --}}
                        @if ($categories->onFirstPage())
                            <li class="page-item disabled">
                                <span class="page-link" aria-label="Trang trước" title="Trang trước">
                                    <i class="fa-solid fa-angle-left"></i>
                                </span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $categories->previousPageUrl() }}" rel="prev" aria-label="Trang trước" title="Trang trước">
                                    <i class="fa-solid fa-angle-left"></i>
                                </a>
                            </li>
                        @endif

                        {{-- Danh sách trang --}}
                        @php
                            $currentPage = $categories->currentPage();
                            $lastPage = $categories->lastPage();
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($lastPage, $currentPage + 2);
                        @endphp

                        @if($startPage > 1)
                            <li class="page-item">
                                <a class="page-link" href="{{ $categories->url(1) }}">1</a>
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
                                    <a class="page-link" href="{{ $categories->url($page) }}">{{ $page }}</a>
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
                                <a class="page-link" href="{{ $categories->url($lastPage) }}">{{ $lastPage }}</a>
                            </li>
                        @endif

                        {{-- Nút Trang sau --}}
                        @if ($categories->hasMorePages())
                            <li class="page-item">
                                <a class="page-link" href="{{ $categories->nextPageUrl() }}" rel="next" aria-label="Trang sau" title="Trang sau">
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

</div>

{{-- Quick Edit Modal --}}
<div class="quick-edit-backdrop" id="quickEditModal">
    <div class="quick-edit-modal-box">
        <div class="modal-header-clean">
            <h4>
                <i class="fa-solid fa-bolt text-primary"></i>
                Sửa nhanh danh mục
            </h4>
            <button type="button" class="btn-modal-close" id="quickEditModalClose">✕</button>
        </div>

        <div class="modal-tabs">
            <button type="button" class="modal-tab-btn active" data-tab="basic">
                <i class="fa-solid fa-pen-to-square me-1"></i> Thông tin cơ bản
            </button>
            <button type="button" class="modal-tab-btn" data-tab="seo">
                <i class="fa-solid fa-magnifying-glass me-1"></i> Cấu hình SEO
            </button>
        </div>

        <form id="quickEditForm" enctype="multipart/form-data">
            <input type="hidden" id="quickEditCategoryId" />
            @csrf
            @method('PATCH')

            <div class="modal-body-clean">
                {{-- Tab Basic --}}
                <div class="quick-edit-tab-content active" id="tab-basic">
                    <div class="mb-3">
                        <label for="quickEditName" class="form-label small fw-bold">Tên danh mục <span class="text-danger">*</span></label>
                        <input type="text" id="quickEditName" name="name" class="form-control form-control-sm" required>
                    </div>

                    <div class="mb-3">
                        <label for="quickEditSlug" class="form-label small fw-bold">Slug (Đường dẫn) <span class="text-danger">*</span></label>
                        <input type="text" id="quickEditSlug" name="slug" class="form-control form-control-sm" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="quickEditSortOrder" class="form-label small fw-bold">Thứ tự hiển thị</label>
                            <input type="number" id="quickEditSortOrder" name="sort_order" class="form-control form-control-sm" min="0" value="0">
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check mb-1">
                                <input type="checkbox" id="quickEditActive" name="is_active" class="form-check-input" style="cursor: pointer;">
                                <label for="quickEditActive" class="form-check-label small fw-semibold" style="cursor: pointer;">
                                    Kích hoạt hoạt động
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="quickEditImage" class="form-label small fw-bold">Ảnh danh mục</label>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <img id="quickEditImagePreview" src="" alt="Preview"
                                 style="width: 50px; height: 50px; border-radius: 6px; object-fit: cover; border: 1px solid #cbd5e1; display: none;">
                            <input type="file" id="quickEditImage" name="image" class="form-control form-control-sm" accept="image/*">
                        </div>
                        <small class="text-muted" style="font-size: 11px;">Hỗ trợ: JPEG, PNG, WebP, AVIF. Tối đa 5MB</small>
                    </div>
                </div>

                {{-- Tab SEO --}}
                <div class="quick-edit-tab-content" id="tab-seo" style="display: none;">
                    <div class="mb-3">
                        <label for="quickEditMetaTitle" class="form-label small fw-bold">Meta Title</label>
                        <input type="text" id="quickEditMetaTitle" name="meta_title" class="form-control form-control-sm" maxlength="255">
                        <small class="text-muted" style="font-size: 11px;">Tối đa 60-70 ký tự để hiển thị tốt trên Google</small>
                    </div>

                    <div class="mb-3">
                        <label for="quickEditMetaDescription" class="form-label small fw-bold">Meta Description</label>
                        <textarea id="quickEditMetaDescription" name="meta_description" class="form-control form-control-sm" rows="3" maxlength="500"></textarea>
                        <small class="text-muted" style="font-size: 11px;">Mô tả ngắn hiển thị trên kết quả tìm kiếm Google</small>
                    </div>

                    <div class="mb-2">
                        <label for="quickEditMetaKeywords" class="form-label small fw-bold">Meta Keywords</label>
                        <input type="text" id="quickEditMetaKeywords" name="meta_keywords" class="form-control form-control-sm" placeholder="thời trang, áo polo...">
                    </div>
                </div>
            </div>

            <div class="modal-footer-clean">
                <button type="button" class="btn btn-sm btn-light border" id="quickEditModalCancel">Hủy</button>
                <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1" id="quickEditSubmitBtn">
                    <i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Xuất CSV/Excel --}}
<div class="modal fade" id="exportCategoriesModal" tabindex="-1" aria-labelledby="exportCategoriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-bottom pb-3 pt-3 px-4">
                <h5 class="modal-title fw-bold fs-6" id="exportCategoriesModalLabel">
                    <i class="fa-solid fa-file-export text-primary me-2"></i> Xuất dữ liệu danh mục ra CSV / Excel
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                {{-- Phạm vi --}}
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">1. Phạm vi danh mục</label>
                    <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded border">
                        <div class="form-check" id="scopeSelectedWrapper">
                            <input class="form-check-input" type="radio" name="exportScope" id="scopeSelected" value="selected">
                            <label class="form-check-label fw-semibold small text-primary" for="scopeSelected">
                                <i class="fa-solid fa-check-double me-1"></i> Chỉ các danh mục đang chọn (<span id="exportSelectedCount">0</span> danh mục)
                            </label>
                        </div>
                        @php
                            $hasActiveFilter = request()->hasAny(['keyword', 'status', 'level', 'sort', 'direction']) && (request('keyword') || request('status') || request('level') || request('sort') || request('direction'));
                        @endphp
                        <div class="form-check" id="scopeFilterWrapper">
                            <input class="form-check-input" type="radio" name="exportScope" id="scopeFilter" value="filter" {{ $hasActiveFilter ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold small text-success" for="scopeFilter">
                                <i class="fa-solid fa-filter me-1"></i> Toàn bộ kết quả lọc hiện tại (<strong id="exportFilteredTotalCount">{{ $categories->total() }}</strong> danh mục trên tất cả các trang)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportScope" id="scopeAll" value="all" {{ !$hasActiveFilter ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold small text-secondary" for="scopeAll">
                                <i class="fa-solid fa-database me-1"></i> Tất cả danh mục trong hệ thống ({{ $stats['total'] ?? \App\Models\Category::count() }} danh mục)
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Định dạng --}}
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">2. Định dạng tệp xuất</label>
                    <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded border">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportFormat" id="formatCsv" value="csv" checked>
                            <label class="form-check-label fw-semibold small" for="formatCsv">
                                CSV (Streaming siêu tốc, nhẹ, chuẩn UTF-8)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportFormat" id="formatXlsx" value="xlsx">
                            <label class="form-check-label fw-semibold small" for="formatXlsx">
                                Microsoft Excel (.xlsx)
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Chọn cột xuất --}}
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-0">3. Chọn các cột cần xuất</label>
                        <div class="btn-group btn-group-sm">
                            <button type="button" id="btnExportSelectAll" class="btn btn-outline-secondary py-0">Chọn tất cả</button>
                            <button type="button" id="btnExportDeselectAll" class="btn btn-outline-secondary py-0">Bỏ chọn</button>
                            <button type="button" id="btnExportDefaultCols" class="btn btn-outline-primary py-0">Mặc định</button>
                        </div>
                    </div>
                    <div class="p-3 bg-light rounded border" style="max-height: 180px; overflow-y: auto;">
                        <div class="row g-2" id="exportColumnsGrid">
                            @php
                                $allExportCols = \App\Http\Controllers\Admins\CategoryImportExportController::supportedColumns();
                            @endphp
                            @foreach ($allExportCols as $col)
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-col-check" type="checkbox" value="{{ $col['key'] }}" id="expCol_{{ $loop->index }}" {{ $col['default_export'] ? 'checked' : '' }} data-default="{{ $col['default_export'] ? '1' : '0' }}">
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
            <div class="modal-footer border-top pt-3 pb-3 px-4 d-flex justify-content-between">
                <a href="{{ route('admin.categories.sample') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-download me-1"></i> Tải file mẫu CSV
                </a>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" id="btnConfirmExport" class="btn btn-sm btn-primary px-3 fw-semibold">
                        <i class="fa-solid fa-file-export me-1"></i> Bắt đầu xuất file
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Nhập CSV/Excel --}}
<div class="modal fade" id="importCategoriesModal" tabindex="-1" aria-labelledby="importCategoriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-bottom pb-3 pt-3 px-4">
                <h5 class="modal-title fw-bold fs-6" id="importCategoriesModalLabel">
                    <i class="fa-solid fa-file-import text-primary me-2"></i> Nhập danh mục từ CSV / Excel (Batch Ultra Fast)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    {{-- Cột trái --}}
                    <div class="col-lg-6">
                        {{-- Dropzone --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold text-uppercase small text-muted">1. Chọn file dữ liệu</label>
                            <div id="modalImportDropZone" class="border-2 border-dashed rounded p-4 text-center" style="background: #f8fafc; border: 2px dashed #cbd5e1; cursor: pointer;">
                                <input type="file" id="modalImportFileInput" class="d-none" accept=".csv, .xlsx, .xls">
                                <div id="modalImportDropContent">
                                    <i class="fa-solid fa-cloud-arrow-up text-primary fa-2x mb-2"></i>
                                    <p class="mb-1 fw-semibold small text-dark">Kéo thả file CSV/Excel vào đây hoặc nhấn để chọn</p>
                                    <span class="text-muted" style="font-size: 0.75rem;">Hỗ trợ định dạng .csv, .xlsx, .xls</span>
                                </div>
                                <div id="modalImportFileInfo" class="d-none text-start p-2 bg-white rounded border">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center overflow-hidden">
                                            <i class="fa-solid fa-file-csv text-success fa-2x me-2"></i>
                                            <div class="text-truncate">
                                                <div id="modalImportFileName" class="fw-bold small text-truncate">file.csv</div>
                                                <div id="modalImportFileSize" class="text-muted" style="font-size: 0.75rem;">0 KB</div>
                                            </div>
                                        </div>
                                        <button type="button" id="btnChangeModalImportFile" class="btn btn-sm btn-outline-secondary py-0">Đổi file</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Quy tắc --}}
                        <div class="alert alert-light border py-2 px-3 mb-3 small" style="border-left: 4px solid #3b82f6 !important;">
                            <div class="fw-bold mb-1 text-primary"><i class="fa-solid fa-circle-info me-1"></i> Quy tắc khớp danh mục thông minh:</div>
                            <ul class="mb-0 ps-3">
                                <li><strong>Có ID:</strong> Cập nhật đúng danh mục theo ID đó.</li>
                                <li><strong>Không có ID:</strong> Khớp theo <code>Slug</code> hoặc sinh từ Tên (nếu có -> Cập nhật; chưa có -> Tạo mới).</li>
                                <li><strong>Danh mục cha:</strong> Tự động liên kết qua ID, Slug hoặc Tên cha.</li>
                            </ul>
                        </div>

                        {{-- Cột cần nhập --}}
                        <div id="modalImportColumnsContainer" class="d-none mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold text-uppercase small text-muted mb-0">2. Chọn các cột cần nhập</label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" id="btnModalImportSelectAll" class="btn btn-outline-secondary py-0">Chọn hết</button>
                                    <button type="button" id="btnModalImportDeselectAll" class="btn btn-outline-secondary py-0">Bỏ hết</button>
                                    <button type="button" id="btnModalImportDefaultCols" class="btn btn-outline-primary py-0">Mặc định</button>
                                </div>
                            </div>
                            <div class="p-3 bg-light rounded border" style="max-height: 160px; overflow-y: auto;">
                                <div class="row g-2" id="modalImportColumnsList"></div>
                            </div>
                        </div>

                        {{-- Cấu hình batch --}}
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted mb-1">Mỗi mẻ (Batch):</label>
                                <select id="modalBatchSize" class="form-select form-select-sm">
                                    <option value="50">50 dòng / mẻ</option>
                                    <option value="100" selected>100 dòng / mẻ</option>
                                    <option value="200">200 dòng / mẻ</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted mb-1">Luồng song song:</label>
                                <select id="modalConcurrency" class="form-select form-select-sm">
                                    <option value="1">1 luồng (Tuần tự)</option>
                                    <option value="3" selected>3 luồng song song (Nhanh)</option>
                                    <option value="5">5 luồng song song (Cực nhanh)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Cột phải: Tiến trình & Log --}}
                    <div class="col-lg-6">
                        <label class="form-label fw-bold text-uppercase small text-muted">3. Trạng thái & Tiến trình xử lý</label>
                        
                        {{-- Thống kê --}}
                        <div class="row g-2 mb-3">
                            <div class="col-3">
                                <div class="p-2 text-center rounded bg-light border">
                                    <div class="text-muted small" style="font-size: 0.72rem;">Tổng số</div>
                                    <div id="modalStatTotal" class="h6 fw-bold mb-0 text-dark">0</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 text-center rounded bg-success-subtle border border-success-subtle">
                                    <div class="text-success small" style="font-size: 0.72rem;">Thành công</div>
                                    <div id="modalStatSuccess" class="h6 fw-bold mb-0 text-success">0</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 text-center rounded bg-primary-subtle border border-primary-subtle">
                                    <div class="text-primary small" style="font-size: 0.72rem;">Tạo mới</div>
                                    <div id="modalStatCreated" class="h6 fw-bold mb-0 text-primary">0</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 text-center rounded bg-danger-subtle border border-danger-subtle">
                                    <div class="text-danger small" style="font-size: 0.72rem;">Lỗi</div>
                                    <div id="modalStatError" class="h6 fw-bold mb-0 text-danger">0</div>
                                </div>
                            </div>
                        </div>

                        {{-- Progress Bar --}}
                        <div class="mb-3 d-none" id="modalProgressArea">
                            <div class="d-flex justify-content-between mb-1 small fw-bold">
                                <span id="modalProgressText">Đang xử lý: 0/0</span>
                                <span id="modalPercentText">0%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div id="modalProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>

                        {{-- Terminal Log --}}
                        <div class="rounded border overflow-hidden">
                            <div class="d-flex justify-content-between align-items-center px-3 py-1 bg-dark text-white" style="font-size: 0.75rem;">
                                <span class="fw-bold"><i class="fa-solid fa-terminal me-1"></i> NHẬT KÝ TIẾN TRÌNH</span>
                                <span id="modalCurrentStatus" class="opacity-75">Sẵn sàng...</span>
                            </div>
                            <div id="modalImportLog" class="p-2 font-monospace text-white-50 overflow-auto" style="height: 200px; font-size: 0.78rem; background: #0f172a;">
                                <div>> Vui lòng chọn file CSV/Excel để bắt đầu...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top pt-3 pb-3 px-4 d-flex justify-content-between">
                <a href="{{ route('admin.categories.sample') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-download me-1"></i> Tải file mẫu CSV
                </a>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" id="btnStartCategoryImportBatch" class="btn btn-sm btn-primary px-3 fw-bold" disabled>
                        <i class="fa-solid fa-rocket me-1"></i> Bắt đầu nhập dữ liệu
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <script>
        function toSlugVN(text) {
            if (!text) return '';
            const vietnameseMap = {
                'à': 'a', 'á': 'a', 'ả': 'a', 'ã': 'a', 'ạ': 'a',
                'ă': 'a', 'ằ': 'a', 'ắ': 'a', 'ẳ': 'a', 'ẵ': 'a', 'ặ': 'a',
                'â': 'a', 'ầ': 'a', 'ấ': 'a', 'ẩ': 'a', 'ẫ': 'a', 'ậ': 'a',
                'đ': 'd',
                'è': 'e', 'é': 'e', 'ẻ': 'e', 'ẽ': 'e', 'ẹ': 'e',
                'ê': 'e', 'ề': 'e', 'ế': 'e', 'ể': 'e', 'ễ': 'e', 'ệ': 'e',
                'ì': 'i', 'í': 'i', 'ỉ': 'i', 'ĩ': 'i', 'ị': 'i',
                'ò': 'o', 'ó': 'o', 'ỏ': 'o', 'õ': 'o', 'ọ': 'o',
                'ô': 'o', 'ồ': 'o', 'ố': 'o', 'ổ': 'o', 'ỗ': 'o', 'ộ': 'o',
                'ơ': 'o', 'ờ': 'o', 'ớ': 'o', 'ở': 'o', 'ỡ': 'o', 'ợ': 'o',
                'ù': 'u', 'ú': 'u', 'ủ': 'u', 'ũ': 'u', 'ụ': 'u',
                'ư': 'u', 'ừ': 'u', 'ứ': 'u', 'ử': 'u', 'ữ': 'u', 'ự': 'u',
                'ỳ': 'y', 'ý': 'y', 'ỷ': 'y', 'ỹ': 'y', 'ỵ': 'y',
            };

            return text
                .toLowerCase()
                .split('')
                .map(char => vietnameseMap[char] || char)
                .join('')
                .trim()
                .replace(/\s+/g, '-')
                .replace(/[^\w\-]/g, '')
                .replace(/\-+/g, '-')
                .replace(/^\-|\-$/g, '');
        }

        document.addEventListener('DOMContentLoaded', () => {
            const selectAllCheckbox = document.getElementById('select-all-categories');
            const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
            const bulkActionsBar = document.getElementById('bulkActionsBar');
            const selectedCountText = document.getElementById('selectedCountText');
            const categoriesTable = document.getElementById('categoriesTable');

            // ===== CHECKBOX & BULK ACTIONS BAR =====
            function updateBulkBar() {
                const checked = Array.from(categoryCheckboxes).filter(cb => cb.checked);
                const scopeSelectedRadio = document.getElementById('scopeSelected');
                const exportSelectedCountEl = document.getElementById('exportSelectedCount');

                if (exportSelectedCountEl) {
                    exportSelectedCountEl.textContent = checked.length;
                }

                if (checked.length > 0) {
                    bulkActionsBar.classList.add('active');
                    if (selectedCountText) selectedCountText.textContent = checked.length;
                    if (scopeSelectedRadio) {
                        scopeSelectedRadio.disabled = false;
                    }
                } else {
                    bulkActionsBar.classList.remove('active');
                    if (scopeSelectedRadio) {
                        scopeSelectedRadio.disabled = true;
                        if (scopeSelectedRadio.checked) {
                            const scopeAllRadio = document.getElementById('scopeAll');
                            if (scopeAllRadio) scopeAllRadio.checked = true;
                        }
                    }
                }
            }

            // Nút Xuất đang chọn trên thanh bulk actions bar
            const btnExportSelected = document.getElementById('btnExportSelected');
            if (btnExportSelected) {
                btnExportSelected.addEventListener('click', () => {
                    const checked = Array.from(categoryCheckboxes).filter(cb => cb.checked);
                    if (checked.length === 0) {
                        alert('Vui lòng chọn ít nhất một danh mục để xuất.');
                        return;
                    }
                    const scopeSelectedRadio = document.getElementById('scopeSelected');
                    if (scopeSelectedRadio) {
                        scopeSelectedRadio.disabled = false;
                        scopeSelectedRadio.checked = true;
                    }
                    if (typeof bootstrap !== 'undefined' && exportModalEl) {
                        const modalInstance = bootstrap.Modal.getInstance(exportModalEl) || new bootstrap.Modal(exportModalEl);
                        modalInstance.show();
                    }
                });
            }

            // Nút Xuất kết quả lọc trên thanh bộ lọc
            const btnExportFiltered = document.getElementById('btnExportFiltered');
            if (btnExportFiltered) {
                btnExportFiltered.addEventListener('click', () => {
                    const scopeFilterRadio = document.getElementById('scopeFilter');
                    if (scopeFilterRadio) {
                        scopeFilterRadio.checked = true;
                    }
                    if (typeof bootstrap !== 'undefined' && exportModalEl) {
                        const modalInstance = bootstrap.Modal.getInstance(exportModalEl) || new bootstrap.Modal(exportModalEl);
                        modalInstance.show();
                    }
                });
            }

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', () => {
                    categoryCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
                    updateBulkBar();
                });
            }

            categoryCheckboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    if (selectAllCheckbox) {
                        const allChecked = Array.from(categoryCheckboxes).every(c => c.checked);
                        selectAllCheckbox.checked = allChecked;
                    }
                    updateBulkBar();
                });
            });

            // ===== QUICK TOGGLE STATUS VIA AJAX =====
            document.querySelectorAll('.status-badge').forEach(badge => {
                badge.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const categoryId = badge.dataset.categoryId;
                    if (!categoryId) return;

                    const originalHTML = badge.innerHTML;
                    badge.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>...';

                    try {
                        const response = await fetch(`/admin/categories/${categoryId}/quick-toggle`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            }
                        });

                        const data = await response.json();
                        if (data.success) {
                            if (data.is_active) {
                                badge.className = 'status-badge status-badge-active';
                                badge.innerHTML = '<i class="fa-solid fa-circle-check"></i> <span>Hoạt động</span>';
                            } else {
                                badge.className = 'status-badge status-badge-inactive';
                                badge.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> <span>Tạm ẩn</span>';
                            }
                        } else {
                            badge.innerHTML = originalHTML;
                            alert('Không thể đổi trạng thái.');
                        }
                    } catch (err) {
                        badge.innerHTML = originalHTML;
                        console.error('Error toggling status:', err);
                        alert('Có lỗi xảy ra khi đổi trạng thái.');
                    }
                });
            });

            // ===== DRAG & DROP SORTING =====
            const categoriesList = document.getElementById('categoriesList');
            if (categoriesList && typeof Sortable !== 'undefined') {
                Sortable.create(categoriesList, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'dragging',
                    onEnd: async () => {
                        const items = Array.from(categoriesList.querySelectorAll('tr[data-category-id]')).map((tr, idx) => ({
                            id: parseInt(tr.dataset.categoryId),
                            sort_order: idx
                        }));

                        try {
                            const res = await fetch('{{ route("admin.categories.update-sort") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                },
                                body: JSON.stringify({ items })
                            });
                            const result = await res.json();
                            if (!result.success) {
                                alert('Không thể lưu thứ tự danh mục.');
                            }
                        } catch (err) {
                            console.error('Error updating sort order:', err);
                        }
                    }
                });
            }

            // ===== QUICK EDIT MODAL =====
            const quickEditModal = document.getElementById('quickEditModal');
            const quickEditForm = document.getElementById('quickEditForm');
            const quickEditModalClose = document.getElementById('quickEditModalClose');
            const quickEditModalCancel = document.getElementById('quickEditModalCancel');
            const quickEditButtons = document.querySelectorAll('.quick-edit-btn');
            const quickEditCategoryId = document.getElementById('quickEditCategoryId');
            const quickEditName = document.getElementById('quickEditName');
            const quickEditSlug = document.getElementById('quickEditSlug');
            const quickEditSortOrder = document.getElementById('quickEditSortOrder');
            const quickEditActive = document.getElementById('quickEditActive');
            const quickEditImage = document.getElementById('quickEditImage');
            const quickEditImagePreview = document.getElementById('quickEditImagePreview');
            const quickEditMetaTitle = document.getElementById('quickEditMetaTitle');
            const quickEditMetaDescription = document.getElementById('quickEditMetaDescription');
            const quickEditMetaKeywords = document.getElementById('quickEditMetaKeywords');
            const quickEditSubmitBtn = document.getElementById('quickEditSubmitBtn');

            // Tabs inside modal
            const tabButtons = document.querySelectorAll('.modal-tab-btn');
            tabButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    tabButtons.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    const tabKey = btn.dataset.tab;
                    document.getElementById('tab-basic').style.display = tabKey === 'basic' ? 'block' : 'none';
                    document.getElementById('tab-seo').style.display = tabKey === 'seo' ? 'block' : 'none';
                });
            });

            // Preview image on file select
            if (quickEditImage) {
                quickEditImage.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (event) => {
                            quickEditImagePreview.src = event.target.result;
                            quickEditImagePreview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // Open Modal
            quickEditButtons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const categoryId = btn.dataset.categoryId;
                    if (!categoryId) return;

                    quickEditCategoryId.value = categoryId;
                    quickEditName.value = btn.dataset.categoryName || '';
                    quickEditSlug.value = btn.dataset.categorySlug || '';
                    quickEditSortOrder.value = btn.dataset.categorySortOrder || 0;
                    quickEditActive.checked = btn.dataset.categoryActive === '1';
                    quickEditMetaTitle.value = btn.dataset.categoryMetaTitle || '';
                    quickEditMetaDescription.value = btn.dataset.categoryMetaDescription || '';
                    quickEditMetaKeywords.value = btn.dataset.categoryMetaKeywords || '';

                    if (btn.dataset.categoryImage) {
                        quickEditImagePreview.src = btn.dataset.categoryImage;
                        quickEditImagePreview.style.display = 'block';
                    } else {
                        quickEditImagePreview.src = '';
                        quickEditImagePreview.style.display = 'none';
                    }

                    // Reset to first tab
                    tabButtons[0].click();
                    quickEditImage.value = '';
                    quickEditModal.classList.add('active');
                    quickEditName.focus();
                });
            });

            const closeQuickEdit = () => {
                quickEditModal.classList.remove('active');
                quickEditForm.reset();
                quickEditImagePreview.style.display = 'none';
            };

            if (quickEditModalClose) quickEditModalClose.addEventListener('click', closeQuickEdit);
            if (quickEditModalCancel) quickEditModalCancel.addEventListener('click', closeQuickEdit);
            quickEditModal.addEventListener('click', (e) => {
                if (e.target === quickEditModal) closeQuickEdit();
            });

            // Auto-generate slug when name changes if slug is empty
            if (quickEditName && quickEditSlug) {
                quickEditName.addEventListener('input', () => {
                    if (quickEditSlug.dataset.manual !== 'true') {
                        quickEditSlug.value = toSlugVN(quickEditName.value);
                    }
                });
                quickEditSlug.addEventListener('input', () => {
                    quickEditSlug.dataset.manual = quickEditSlug.value.trim().length > 0 ? 'true' : 'false';
                });
            }

            // Submit quick edit
            if (quickEditForm) {
                quickEditForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const categoryId = quickEditCategoryId.value;
                    const name = quickEditName.value.trim();
                    const slug = quickEditSlug.value.trim();

                    if (!categoryId || !name || !slug) {
                        alert('Vui lòng nhập tên danh mục và slug.');
                        return;
                    }

                    quickEditSubmitBtn.disabled = true;
                    quickEditSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang lưu...';

                    try {
                        const formData = new FormData();
                        formData.append('name', name);
                        formData.append('slug', slug);
                        formData.append('sort_order', quickEditSortOrder.value);
                        formData.append('is_active', quickEditActive.checked ? '1' : '0');
                        formData.append('meta_title', quickEditMetaTitle.value.trim());
                        formData.append('meta_description', quickEditMetaDescription.value.trim());
                        formData.append('meta_keywords', quickEditMetaKeywords.value.trim());

                        if (quickEditImage.files.length > 0) {
                            formData.append('image', quickEditImage.files[0]);
                        }

                        formData.append('_method', 'PATCH');
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

                        const res = await fetch(`/admin/categories/${categoryId}/quick-update`, {
                            method: 'POST',
                            body: formData
                        });

                        const data = await res.json();

                        if (res.ok && data.success) {
                            // Update UI row
                            const row = categoriesTable.querySelector(`tr[data-category-id="${categoryId}"]`);
                            if (row) {
                                const nameSpan = row.querySelector('.cat-name span:not(.tree-indent):not(.badge-root-tag)');
                                const slugSpan = row.querySelector('.cat-slug');
                                if (nameSpan) nameSpan.textContent = name;
                                if (slugSpan) slugSpan.textContent = slug;

                                const statusBadge = row.querySelector('.status-badge');
                                if (statusBadge) {
                                    if (quickEditActive.checked) {
                                        statusBadge.className = 'status-badge status-badge-active';
                                        statusBadge.innerHTML = '<i class="fa-solid fa-circle-check"></i> <span>Hoạt động</span>';
                                    } else {
                                        statusBadge.className = 'status-badge status-badge-inactive';
                                        statusBadge.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> <span>Tạm ẩn</span>';
                                    }
                                }
                            }

                            // Update button dataset
                            const btn = document.querySelector(`.quick-edit-btn[data-category-id="${categoryId}"]`);
                            if (btn) {
                                btn.dataset.categoryName = name;
                                btn.dataset.categorySlug = slug;
                                btn.dataset.categorySortOrder = quickEditSortOrder.value;
                                btn.dataset.categoryActive = quickEditActive.checked ? '1' : '0';
                                btn.dataset.categoryMetaTitle = quickEditMetaTitle.value;
                                btn.dataset.categoryMetaDescription = quickEditMetaDescription.value;
                                btn.dataset.categoryMetaKeywords = quickEditMetaKeywords.value;
                            }

                            closeQuickEdit();
                        } else {
                            alert('Lỗi: ' + (data.message || 'Không thể cập nhật'));
                        }
                    } catch (err) {
                        console.error('Error in quick edit:', err);
                        alert('Có lỗi xảy ra khi cập nhật.');
                    } finally {
                        quickEditSubmitBtn.disabled = false;
                        quickEditSubmitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi';
                    }
                });
            }

            // ==========================================
            // LOGIC XUẤT CSV / EXCEL
            // ==========================================
            const exportModalEl = document.getElementById('exportCategoriesModal');
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

            if (btnConfirmExport) {
                btnConfirmExport.addEventListener('click', function () {
                    const selectedCols = Array.from(document.querySelectorAll('.export-col-check:checked')).map(cb => cb.value);
                    if (selectedCols.length === 0) {
                        alert('Vui lòng chọn ít nhất một cột để xuất dữ liệu.');
                        return;
                    }

                    const scope = document.querySelector('input[name="exportScope"]:checked')?.value || 'all';
                    const format = document.querySelector('input[name="exportFormat"]:checked')?.value || 'csv';
                    const params = new URLSearchParams();

                    selectedCols.forEach(col => params.append('columns[]', col));
                    params.append('format', format);

                    if (scope === 'selected') {
                        const checkedIds = Array.from(document.querySelectorAll('.category-checkbox:checked')).map(cb => cb.value);
                        if (checkedIds.length === 0) {
                            alert('Vui lòng chọn ít nhất một danh mục để xuất.');
                            return;
                        }
                        checkedIds.forEach(id => params.append('ids[]', id));
                    } else if (scope === 'filter') {
                        // Thu thập toàn bộ giá trị bộ lọc từ form lọc trên trang
                        const filterForm = document.querySelector('.filter-form-grid');
                        if (filterForm) {
                            const formData = new FormData(filterForm);
                            for (const [key, val] of formData.entries()) {
                                if (val && key !== 'page' && key !== '_token') {
                                    params.set(key, val);
                                }
                            }
                        }
                        // Bổ sung các tham số lọc từ URL nếu có (loại trừ page)
                        const currentUrlParams = new URLSearchParams(window.location.search);
                        for (const [key, val] of currentUrlParams.entries()) {
                            if (val && key !== 'page' && !params.has(key)) {
                                params.set(key, val);
                            }
                        }
                    }

                    if (typeof bootstrap !== 'undefined' && exportModalEl) {
                        const modalInstance = bootstrap.Modal.getInstance(exportModalEl) || new bootstrap.Modal(exportModalEl);
                        modalInstance.hide();
                    }

                    // Tải tệp thông qua form POST để không bị giới hạn độ dài URL
                    const exportForm = document.createElement('form');
                    exportForm.method = 'POST';
                    exportForm.action = "{{ route('admin.categories.export') }}";
                    exportForm.style.display = 'none';

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = csrfToken;
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
                    setTimeout(() => exportForm.remove(), 2500);
                });
            }

            // ==========================================
            // LOGIC NHẬP CSV / EXCEL (BATCH ULTRA FAST)
            // ==========================================
            const modalImportDropZone = document.getElementById('modalImportDropZone');
            const modalImportFileInput = document.getElementById('modalImportFileInput');
            const modalImportDropContent = document.getElementById('modalImportDropContent');
            const modalImportFileInfo = document.getElementById('modalImportFileInfo');
            const modalImportFileName = document.getElementById('modalImportFileName');
            const modalImportFileSize = document.getElementById('modalImportFileSize');
            const btnChangeModalImportFile = document.getElementById('btnChangeModalImportFile');

            const modalImportColumnsContainer = document.getElementById('modalImportColumnsContainer');
            const modalImportColumnsList = document.getElementById('modalImportColumnsList');
            const btnModalImportSelectAll = document.getElementById('btnModalImportSelectAll');
            const btnModalImportDeselectAll = document.getElementById('btnModalImportDeselectAll');
            const btnModalImportDefaultCols = document.getElementById('btnModalImportDefaultCols');

            const modalBatchSize = document.getElementById('modalBatchSize');
            const modalConcurrency = document.getElementById('modalConcurrency');
            const btnStartCategoryImportBatch = document.getElementById('btnStartCategoryImportBatch');

            const modalProgressArea = document.getElementById('modalProgressArea');
            const modalProgressBar = document.getElementById('modalProgressBar');
            const modalProgressText = document.getElementById('modalProgressText');
            const modalPercentText = document.getElementById('modalPercentText');
            const modalCurrentStatus = document.getElementById('modalCurrentStatus');

            const modalStatTotal = document.getElementById('modalStatTotal');
            const modalStatSuccess = document.getElementById('modalStatSuccess');
            const modalStatCreated = document.getElementById('modalStatCreated');
            const modalStatError = document.getElementById('modalStatError');
            const modalImportLog = document.getElementById('modalImportLog');

            let categoryImportData = [];
            let detectedImportHeaders = [];

            const addModalLog = (msg, type = 'info') => {
                if (!modalImportLog) return;
                const colorClass = type === 'success' ? 'text-success' : (type === 'error' ? 'text-danger' : (type === 'warn' ? 'text-warning' : 'text-white-50'));
                const div = document.createElement('div');
                div.className = `mb-1 ${colorClass}`;
                div.innerHTML = `<span class="opacity-50">></span> [${new Date().toLocaleTimeString()}] ${msg}`;
                modalImportLog.appendChild(div);
                modalImportLog.scrollTop = modalImportLog.scrollHeight;
            };

            const updateModalProgress = (current, total) => {
                if (!modalProgressBar) return;
                const percent = total > 0 ? Math.round((current / total) * 100) : 0;
                modalProgressBar.style.width = `${percent}%`;
                if (modalProgressText) modalProgressText.innerText = `Đang xử lý: ${current}/${total}`;
                if (modalPercentText) modalPercentText.innerText = `${percent}%`;
            };

            if (modalImportDropZone && modalImportFileInput) {
                modalImportDropZone.addEventListener('click', (e) => {
                    if (e.target !== btnChangeModalImportFile) {
                        modalImportFileInput.click();
                    }
                });

                if (btnChangeModalImportFile) {
                    btnChangeModalImportFile.addEventListener('click', (e) => {
                        e.stopPropagation();
                        modalImportFileInput.click();
                    });
                }

                modalImportDropZone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    modalImportDropZone.style.borderColor = '#3b82f6';
                    modalImportDropZone.style.background = '#eff6ff';
                });

                modalImportDropZone.addEventListener('dragleave', () => {
                    modalImportDropZone.style.borderColor = '#cbd5e1';
                    modalImportDropZone.style.background = '#f8fafc';
                });

                modalImportDropZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    modalImportDropZone.style.borderColor = '#cbd5e1';
                    modalImportDropZone.style.background = '#f8fafc';
                    if (e.dataTransfer.files.length) {
                        handleSelectedModalImportFile(e.dataTransfer.files[0]);
                    }
                });

                modalImportFileInput.addEventListener('change', (e) => {
                    if (e.target.files.length) {
                        handleSelectedModalImportFile(e.target.files[0]);
                    }
                });
            }

            function handleSelectedModalImportFile(file) {
                if (!file.name.match(/\.(csv|xlsx|xls)$/i)) {
                    alert('Định dạng không hợp lệ. Vui lòng chọn tệp .csv, .xlsx hoặc .xls');
                    return;
                }

                if (modalImportFileName) modalImportFileName.innerText = file.name;
                if (modalImportFileSize) modalImportFileSize.innerText = `${Math.round(file.size / 1024)} KB`;
                if (modalImportFileInfo) modalImportFileInfo.classList.remove('d-none');
                if (modalImportDropContent) modalImportDropContent.classList.add('d-none');

                if (modalCurrentStatus) modalCurrentStatus.innerText = 'Đang đọc tệp...';
                addModalLog(`Đang đọc tệp: ${file.name} (${Math.round(file.size / 1024)} KB)...`, 'info');

                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];

                        categoryImportData = XLSX.utils.sheet_to_json(firstSheet);
                        const rawHeaderRows = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });
                        detectedImportHeaders = (rawHeaderRows && rawHeaderRows.length > 0) ? rawHeaderRows[0] : [];

                        if (modalStatTotal) modalStatTotal.innerText = categoryImportData.length;
                        if (modalStatSuccess) modalStatSuccess.innerText = '0';
                        if (modalStatCreated) modalStatCreated.innerText = '0';
                        if (modalStatError) modalStatError.innerText = '0';

                        if (categoryImportData.length === 0) {
                            addModalLog('Tệp không có dữ liệu danh mục hợp lệ.', 'error');
                            if (btnStartCategoryImportBatch) btnStartCategoryImportBatch.disabled = true;
                            if (modalImportColumnsContainer) modalImportColumnsContainer.classList.add('d-none');
                            return;
                        }

                        if (modalImportColumnsList) {
                            modalImportColumnsList.innerHTML = '';
                            detectedImportHeaders.forEach((colName, idx) => {
                                if (!colName || String(colName).trim() === '') return;
                                const trimmed = String(colName).trim();
                                const lower = trimmed.toLowerCase();
                                const isDefault = (
                                    lower === 'id' ||
                                    lower.includes('tên') || lower.includes('name') ||
                                    lower === 'slug' ||
                                    lower.includes('cha') || lower.includes('parent') ||
                                    lower.includes('thứ tự') || lower.includes('sort') ||
                                    lower.includes('trạng thái') || lower.includes('status')
                                );

                                const colDiv = document.createElement('div');
                                colDiv.className = 'col-sm-6';
                                colDiv.innerHTML = `
                                    <div class="form-check">
                                        <input class="form-check-input modal-col-check" type="checkbox" value="${trimmed}" id="modCol_${idx}" ${isDefault ? 'checked' : ''} data-default="${isDefault ? '1' : '0'}">
                                        <label class="form-check-label small text-truncate" for="modCol_${idx}" title="${trimmed}">
                                            ${trimmed}
                                        </label>
                                    </div>
                                `;
                                modalImportColumnsList.appendChild(colDiv);
                            });
                        }

                        if (modalImportColumnsContainer) modalImportColumnsContainer.classList.remove('d-none');
                        if (btnStartCategoryImportBatch) btnStartCategoryImportBatch.disabled = false;
                        if (modalCurrentStatus) modalCurrentStatus.innerText = `Sẵn sàng (${categoryImportData.length} danh mục)`;
                        addModalLog(`Đọc tệp thành công! Tìm thấy ${categoryImportData.length} danh mục.`, 'success');
                    } catch (err) {
                        console.error(err);
                        addModalLog('Lỗi khi đọc file: ' + err.message, 'error');
                        if (modalCurrentStatus) modalCurrentStatus.innerText = 'Lỗi đọc tệp';
                        if (btnStartCategoryImportBatch) btnStartCategoryImportBatch.disabled = true;
                    }
                };
                reader.readAsArrayBuffer(file);
            }

            if (btnModalImportSelectAll) {
                btnModalImportSelectAll.addEventListener('click', () => {
                    document.querySelectorAll('.modal-col-check').forEach(cb => cb.checked = true);
                });
            }
            if (btnModalImportDeselectAll) {
                btnModalImportDeselectAll.addEventListener('click', () => {
                    document.querySelectorAll('.modal-col-check').forEach(cb => cb.checked = false);
                });
            }
            if (btnModalImportDefaultCols) {
                btnModalImportDefaultCols.addEventListener('click', () => {
                    document.querySelectorAll('.modal-col-check').forEach(cb => {
                        cb.checked = cb.dataset.default === '1';
                    });
                });
            }

            if (btnStartCategoryImportBatch) {
                btnStartCategoryImportBatch.addEventListener('click', async () => {
                    if (!categoryImportData.length) return;

                    const selectedCols = Array.from(document.querySelectorAll('.modal-col-check:checked')).map(cb => cb.value);
                    if (selectedCols.length === 0) {
                        alert('Vui lòng chọn ít nhất một cột cần nhập vào hệ thống.');
                        return;
                    }

                    const batchSize = parseInt(modalBatchSize?.value) || 100;
                    const concurrency = parseInt(modalConcurrency?.value) || 3;

                    btnStartCategoryImportBatch.disabled = true;
                    btnStartCategoryImportBatch.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> ĐANG NHẬP DỮ LIỆU...';
                    if (modalProgressArea) modalProgressArea.classList.remove('d-none');
                    if (modalCurrentStatus) modalCurrentStatus.innerText = 'Đang xử lý các mẻ batch...';

                    let processedCount = 0;
                    let successCount = 0;
                    let createdCount = 0;
                    let errorCount = 0;

                    addModalLog(`Bắt đầu nhập ${categoryImportData.length} danh mục...`, 'info');

                    const chunks = [];
                    for (let i = 0; i < categoryImportData.length; i += batchSize) {
                        chunks.push({
                            index: Math.floor(i / batchSize) + 1,
                            items: categoryImportData.slice(i, i + batchSize)
                        });
                    }

                    const totalBatches = chunks.length;
                    let chunkCursor = 0;

                    async function runCategoryWorker(workerId) {
                        while (chunkCursor < totalBatches) {
                            const chunk = chunks[chunkCursor++];
                            if (!chunk) break;

                            addModalLog(`[Luồng ${workerId}] Đang gửi mẻ ${chunk.index}/${totalBatches} (${chunk.items.length} danh mục)...`, 'info');

                            try {
                                const response = await fetch("{{ route('admin.categories.import-batch') }}", {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        items: chunk.items,
                                        selected_columns: selectedCols
                                    })
                                });

                                const result = await response.json();

                                if (result.success) {
                                    processedCount += chunk.items.length;
                                    successCount += (result.success_count || 0);
                                    createdCount += (result.created_count || 0);

                                    if (result.errors && result.errors.length) {
                                        errorCount += result.errors.length;
                                        result.errors.forEach(err => addModalLog(`⚠️ [Mẻ ${chunk.index}]: ${err}`, 'error'));
                                    }

                                    if (modalStatSuccess) modalStatSuccess.innerText = successCount;
                                    if (modalStatCreated) modalStatCreated.innerText = createdCount;
                                    if (modalStatError) modalStatError.innerText = errorCount;
                                    updateModalProgress(processedCount, categoryImportData.length);
                                    addModalLog(`[Luồng ${workerId}] Mẻ ${chunk.index}/${totalBatches} thành công.`, 'success');
                                } else {
                                    throw new Error(result.message || 'Lỗi xử lý');
                                }
                            } catch (err) {
                                console.error(err);
                                addModalLog(`❌ [Luồng ${workerId}] Lỗi mẻ ${chunk.index}: ${err.message}`, 'error');
                                errorCount += chunk.items.length;
                                if (modalStatError) modalStatError.innerText = errorCount;
                            }
                        }
                    }

                    const workers = [];
                    const activeWorkers = Math.min(concurrency, totalBatches);
                    for (let w = 1; w <= activeWorkers; w++) {
                        workers.push(runCategoryWorker(w));
                    }

                    await Promise.all(workers);

                    if (modalCurrentStatus) modalCurrentStatus.innerText = 'Hoàn tất!';
                    addModalLog(`🎉 Hoàn tất! Thành công: ${successCount} (Mới: ${createdCount}), Lỗi: ${errorCount}`, 'success');
                    btnStartCategoryImportBatch.innerHTML = '<i class="fa-solid fa-check me-2"></i> HOÀN TẤT NHẬP DỮ LIỆU';
                    btnStartCategoryImportBatch.classList.remove('btn-primary');
                    btnStartCategoryImportBatch.classList.add('btn-success');

                    setTimeout(() => {
                        if (confirm(`Quá trình nhập dữ liệu danh mục hoàn tất!\n- Thành công: ${successCount}\n- Tạo mới: ${createdCount}\n- Lỗi: ${errorCount}\n\nBạn có muốn tải lại trang không?`)) {
                            window.location.reload();
                        }
                    }, 800);
                });
            }
        });
    </script>
@endpush
