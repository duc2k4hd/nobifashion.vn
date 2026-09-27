@extends('admins.layouts.master')

@section('title', 'Quản lý danh mục')
@section('page-title', 'Danh mục sản phẩm')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/category-icon.png') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.css">
@endpush

@push('styles')
    <style>
        .categories-page {
            display: grid;
            gap: 20px;
        }

        .categories-toolbar,
        .categories-filters,
        .categories-table-card,
        .categories-bulk-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        }

        .categories-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 20px 24px;
            flex-wrap: wrap;
        }

        .categories-toolbar__title {
            display: grid;
            gap: 6px;
        }

        .categories-toolbar__title h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .categories-toolbar__title p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .categories-toolbar__actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .categories-filters {
            padding: 22px 24px;
        }

        .categories-filters__head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .categories-filters__head h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .categories-filters__summary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 12px;
            background: #f8fafc;
            color: #334155;
            font-size: 14px;
            white-space: nowrap;
        }

        .categories-filter-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 14px;
            align-items: end;
        }

        .categories-filter-field {
            display: grid;
            gap: 8px;
        }

        .categories-filter-field--keyword {
            grid-column: span 2;
        }

        .categories-filter-field label {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        .categories-filter-field input,
        .categories-filter-field select {
            width: 100%;
            min-height: 44px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 14px;
            color: #0f172a;
            background: #fff;
        }

        .categories-filter-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .categories-filter-actions .btn {
            min-height: 44px;
            padding-inline: 18px;
        }

        .categories-table-card {
            overflow: hidden;
        }

        .categories-table-card__head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 18px 24px;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        .categories-table-card__head h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .categories-table-card__head p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .categories-table-wrap {
            overflow-x: auto;
        }

        .categories-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .categories-table th,
        .categories-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: top;
            text-align: left;
        }

        .categories-table th {
            background: #f8fafc;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #475569;
            white-space: nowrap;
        }

        .categories-table tbody tr {
            transition: background 0.2s;
        }

        .categories-table tbody tr:hover td {
            background: #f8fafc;
        }

        .categories-table tbody tr.dragging {
            opacity: 0.5;
            background: #f1f5f9 !important;
        }

        .categories-table__name {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .categories-table__name strong {
            color: #0f172a;
            font-size: 15px;
        }

        .categories-table__meta {
            color: #64748b;
            font-size: 13px;
        }

        .category-level-indicator {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .category-parent-row {
            background: linear-gradient(90deg, #f8fafc 0%, #ffffff 100%);
            font-weight: 600;
            border-top: 2px solid #e2e8f0;
        }

        .category-parent-row td {
            padding-top: 16px !important;
            padding-bottom: 16px !important;
        }

        .category-child-row {
            background: #ffffff;
        }

        .category-child-row td {
            padding-left: 60px !important;
        }

        .child-indent-line {
            position: relative;
            padding-left: 16px;
        }

        .child-indent-line::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(180deg, #cbd5e1 0%, rgba(203, 213, 225, 0) 100%);
        }

        .category-parent-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            background: #ede9fe;
            color: #6d28d9;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .category-child-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            background: #dbeafe;
            color: #1e40af;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .categories-status {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s;
        }

        .categories-status--active {
            background: #dcfce7;
            color: #166534;
        }

        .categories-status--active:hover {
            background: #bbf7d0;
        }

        .categories-status--inactive {
            background: #fee2e2;
            color: #b91c1c;
        }

        .categories-status--inactive:hover {
            background: #fecaca;
        }

        .categories-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
            flex-wrap: nowrap;
            align-items: center;
        }

        .categories-actions .quick-edit-btn {
            height: fit-content;
        }

        .categories-actions .quick-edit-btn-1 {
            width: fit-content;
            white-space: nowrap;
        }

        .categories-actions form {
            margin: 0;
        }

        .categories-empty {
            padding: 48px 20px;
            text-align: center;
            color: #94a3b8;
            font-size: 15px;
        }

        .categories-bulk-card {
            padding: 18px 24px;
        }

        .categories-bulk-card__inner {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .categories-bulk-card__hint {
            color: #64748b;
            font-size: 14px;
        }

        .categories-bulk-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .categories-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #2563eb;
            cursor: pointer;
        }

        .categories-pagination {
            margin-top: 8px;
        }

        .drag-handle {
            cursor: grab;
            color: #94a3b8;
            font-size: 14px;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .drag-handle:hover {
            background: #f1f5f9;
            color: #475569;
        }

        .drag-handle:active {
            cursor: grabbing;
        }

        /* Quick Edit Modal Styles */
        .quick-edit-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .quick-edit-modal.active {
            display: flex;
        }

        .quick-edit-modal-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 500px;
            padding: 0;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .quick-edit-modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .quick-edit-modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .quick-edit-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #94a3b8;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .quick-edit-modal-close:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .quick-edit-modal-body {
            padding: 24px;
        }

        .quick-edit-form-group {
            margin-bottom: 18px;
        }

        .quick-edit-form-group:last-child {
            margin-bottom: 0;
        }

        .quick-edit-form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
        }

        .quick-edit-form-group input,
        .quick-edit-form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            color: #0f172a;
            font-family: inherit;
        }

        .quick-edit-form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .quick-edit-form-group input:focus,
        .quick-edit-form-group textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .quick-edit-modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .quick-edit-modal-footer .btn {
            min-height: 40px;
            padding-inline: 20px;
        }

        /* Tab Navigation */
        .quick-edit-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #e2e8f0;
            margin: -24px -24px 24px -24px;
            padding: 0;
        }

        .quick-edit-tab-btn {
            flex: 1;
            background: none;
            border: none;
            padding: 16px 12px;
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            white-space: nowrap;
        }

        .quick-edit-tab-btn:hover {
            color: #334155;
            background: #f8fafc;
        }

        .quick-edit-tab-btn.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
        }

        .quick-edit-tab-content {
            display: none;
        }

        .quick-edit-tab-content.active {
            display: block;
        }

        /* Form layout */
        .quick-edit-form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .quick-edit-image-preview {
            margin-bottom: 12px;
            padding: 12px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quick-edit-image-preview img {
            max-width: 100%;
            max-height: 150px;
            border-radius: 6px;
        }

        @media (max-width: 1440px) {
            .categories-filter-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .categories-filter-field--keyword {
                grid-column: span 2;
            }
        }

        @media (max-width: 992px) {
            .categories-filters__head,
            .categories-bulk-card__inner {
                flex-direction: column;
                align-items: stretch;
            }

            .categories-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .categories-filter-field--keyword {
                grid-column: span 2;
            }

            .categories-actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 640px) {
            .categories-filter-grid {
                grid-template-columns: 1fr;
            }

            .categories-filter-field--keyword {
                grid-column: span 1;
            }

            .categories-toolbar,
            .categories-filters,
            .categories-table-card__head,
            .categories-bulk-card {
                padding-inline: 16px;
            }

            .categories-table th,
            .categories-table td {
                padding-inline: 12px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="categories-page">
        {{-- Toolbar --}}
        <section class="categories-toolbar">
            <div class="categories-toolbar__title">
                <h2>Danh sách danh mục</h2>
                <p>Quản lý danh mục sản phẩm, tìm kiếm, lọc, sắp xếp và thao tác hàng loạt.</p>
            </div>

            <div class="categories-toolbar__actions">
                <button type="button" id="btnOpenExportModal" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#exportCategoriesModal">
                    <i class="fas fa-file-download me-1"></i> Xuất CSV / Excel
                </button>
                <button type="button" id="btnOpenImportModal" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#importCategoriesModal">
                    <i class="fas fa-file-upload me-1"></i> Nhập CSV / Excel
                </button>
                <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">+ Thêm danh mục</a>
            </div>
        </section>

        {{-- Filters --}}
        <section class="categories-filters">
            <div class="categories-filters__head">
                <div>
                    <h3>Bộ lọc danh mục</h3>
                    <p>Lọc theo từ khóa, trạng thái và cách sắp xếp.</p>
                </div>

                <div class="categories-filters__summary">
                    <span>Hiển thị</span>
                    <strong>{{ number_format($categories->firstItem() ?? 0) }} - {{ number_format($categories->lastItem() ?? 0) }}</strong>
                    <span>trên tổng</span>
                    <strong>{{ number_format($categories->total()) }}</strong>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.categories.index') }}">
                <div class="categories-filter-grid">
                    <div class="categories-filter-field categories-filter-field--keyword">
                        <label for="filter-keyword">Từ khóa</label>
                        <input
                            id="filter-keyword"
                            type="text"
                            name="keyword"
                            value="{{ request('keyword') }}"
                            placeholder="Tìm theo tên, slug hoặc mô tả..."
                        >
                    </div>

                    <div class="categories-filter-field">
                        <label for="filter-status">Trạng thái</label>
                        <select id="filter-status" name="status">
                            <option value="">Tất cả trạng thái</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Đang hiển thị</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Tạm ẩn</option>
                        </select>
                    </div>

                    <div class="categories-filter-field">
                        <label for="filter-sort">Sắp xếp theo</label>
                        <select id="filter-sort" name="sort">
                            <option value="sort_order" {{ request('sort') === 'sort_order' || !request('sort') ? 'selected' : '' }}>Thứ tự tùy chỉnh</option>
                            <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Tên A-Z</option>
                            <option value="created_at" {{ request('sort') === 'created_at' ? 'selected' : '' }}>Mới nhất</option>
                            <option value="updated_at" {{ request('sort') === 'updated_at' ? 'selected' : '' }}>Vừa cập nhật</option>
                        </select>
                    </div>

                    <div class="categories-filter-field">
                        <label for="filter-direction">Hướng sắp xếp</label>
                        <select id="filter-direction" name="direction">
                            <option value="asc" {{ request('direction') === 'asc' || !request('direction') ? 'selected' : '' }}>Tăng dần</option>
                            <option value="desc" {{ request('direction') === 'desc' ? 'selected' : '' }}>Giảm dần</option>
                        </select>
                    </div>

                    <div class="categories-filter-field">
                        <label for="filter-per-page">Hiển thị mỗi trang</label>
                        <select id="filter-per-page" name="per_page">
                            @foreach($perPageOptions as $option)
                                <option value="{{ $option }}" {{ $perPage === $option ? 'selected' : '' }}>
                                    {{ $option }} danh mục
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="categories-filter-actions">
                        <button type="submit" class="btn btn-primary">Lọc dữ liệu</button>
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Đặt lại</a>
                    </div>
                </div>
            </form>
        </section>

        {{-- Table --}}
        <section class="categories-table-card">
            <div class="categories-table-card__head">
                <div>
                    <h3>Danh sách kết quả</h3>
                    <p>Nhấp vào biểu tượng 📋 để kéo và sắp xếp lại danh mục. Nhấp vào trạng thái để chuyển đổi nhanh.</p>
                </div>
            </div>

            <div class="categories-table-wrap">
                <table class="categories-table" id="categoriesTable">
                    <thead>
                        <tr>
                            <th style="width: 48px;">
                                <input type="checkbox" id="select-all-categories" class="categories-checkbox">
                            </th>
                            <th style="width: 40px; text-align: center;">Sắp xếp</th>
                            <th>Tên danh mục</th>
                            <th>Danh mục cha</th>
                            <th style="text-align: center;">Sản phẩm</th>
                            <th style="text-align: center;">Danh mục con</th>
                            <th>Trạng thái</th>
                            <th style="text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="categoriesList">
                        @forelse($categories as $category)
                            {{-- ===== CATEGORY PARENT ROW ===== --}}
                            <tr 
                                data-category-id="{{ $category->id }}" 
                                data-sort="{{ $category->sort_order }}"
                                class="category-{{ $category->parent_id ? 'child' : 'parent' }}-row"
                            >
                                <td>
                                    <input
                                        type="checkbox"
                                        name="selected[]"
                                        value="{{ $category->id }}"
                                        class="category-checkbox categories-checkbox"
                                        form="category-bulk-form"
                                    >
                                </td>
                                <td style="text-align: center;">
                                    <span class="drag-handle" title="Kéo để sắp xếp">📋</span>
                                </td>
                                <td>
                                    <div class="categories-table__name">
                                        @if($category->parent_id)
                                            {{-- CHILD CATEGORY --}}
                                            <div class="child-indent-line">
                                                <span style="color: #cbd5e1;">↳</span>
                                            </div>
                                        @else
                                            {{-- ROOT/PARENT CATEGORY --}}
                                            <span class="category-parent-badge">📁 Chính</span>
                                        @endif
                                        <div>
                                            <strong>{{ $category->name }}</strong>
                                            <div class="categories-table__meta" style="margin-top: 4px;">{{ $category->slug }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($category->parent)
                                        <div style="display: flex; align-items: center; gap: 8px; color: #64748b; font-size: 13px;">
                                            <span style="color: #cbd5e1;">└</span>
                                            <span>{{ $category->parent->name }}</span>
                                        </div>
                                    @else
                                        <span style="color: #cbd5e1; font-size: 13px;">-</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; 
                                                 background: #f0f9ff; border-radius: 8px; color: #0284c7; font-weight: 600; font-size: 13px;">
                                        {{ $category->product_count ?? 0 }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    @if($category->child_count > 0 && !$category->parent_id)
                                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; 
                                                     background: #f5f3ff; border-radius: 8px; color: #7c3aed; font-weight: 600; font-size: 13px;"
                                              title="Có {{ $category->child_count }} danh mục con">
                                            {{ $category->child_count }}
                                        </span>
                                    @else
                                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; 
                                                     background: #f1f5f9; border-radius: 8px; color: #cbd5e1; font-weight: 600; font-size: 13px;">
                                            -
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="categories-status categories-status--{{ $category->is_active ? 'active' : 'inactive' }}" 
                                          data-category-id="{{ $category->id }}"
                                          title="Nhấp để thay đổi"
                                          style="cursor: pointer;">
                                        {{ $category->is_active ? '✓ Hoạt động' : '✕ Tạm ẩn' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="categories-actions">
                                        <button 
                                            type="button" 
                                            class="btn btn-outline-primary btn-sm quick-edit-btn quick-edit-btn-1"
                                            data-category-id="{{ $category->id }}"
                                            data-category-name="{{ $category->name }}"
                                            data-category-slug="{{ $category->slug }}"
                                            data-category-description="{{ $category->description }}"
                                            data-category-sort-order="{{ $category->sort_order ?? 0 }}"
                                            data-category-active="{{ $category->is_active ? '1' : '0' }}"
                                            data-category-meta-title="{{ $category->meta_title ?? '' }}"
                                            data-category-meta-description="{{ $category->meta_description ?? '' }}"
                                            data-category-meta-keywords="{{ $category->meta_keywords ?? '' }}"
                                            data-category-image="{{ $category->image ? (str_starts_with($category->image, 'http') ? $category->image : asset('clients/assets/img/categories/' . $category->image)) : '' }}"
                                            title="Sửa nhanh tên, slug, thứ tự, SEO"
                                        >
                                            ✏️ Sửa nhanh
                                        </button>

                                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-outline-secondary btn-sm" title="Mở trang chỉnh sửa đầy đủ">
                                            Sửa
                                        </a>

                                        <form
                                            action="{{ route('admin.categories.destroy', $category) }}"
                                            method="POST"
                                            onsubmit="return confirm('Xóa danh mục &quot;{{ addslashes($category->name) }}&quot;? Hành động này không thể hoàn tác{{ ($category->product_count ?? 0) > 0 || ($category->child_count ?? 0) > 0 ? ' (không thể xóa nếu có sản phẩm hoặc danh mục con)' : '' }}.');"
                                            style="display: inline-block;"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit" 
                                                class="btn btn-danger btn-sm"
                                                {{ (($category->product_count ?? 0) > 0 || ($category->child_count ?? 0) > 0) ? 'disabled' : '' }}
                                                title="{{ (($category->product_count ?? 0) > 0 || ($category->child_count ?? 0) > 0) ? 'Không thể xóa nếu có sản phẩm hoặc danh mục con' : '' }}"
                                            >
                                                Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="categories-empty">
                                    Không có danh mục phù hợp với bộ lọc hiện tại.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Bulk Actions --}}
        <section class="categories-bulk-card">
            <div class="categories-bulk-card__inner">
                <div class="categories-bulk-card__hint">
                    Chọn nhiều danh mục trong bảng phía trên để thao tác hàng loạt.
                </div>

                <form
                    action="{{ route('admin.categories.bulk-action') }}"
                    method="POST"
                    id="category-bulk-form"
                    class="categories-bulk-actions"
                >
                    @csrf

                    <button type="submit" class="btn btn-outline-secondary" name="bulk_action" value="show">
                        ✓ Hiển thị đã chọn
                    </button>
                    <button type="submit" class="btn btn-outline-secondary" name="bulk_action" value="hide">
                        ✕ Ẩn đã chọn
                    </button>
                    <button
                        type="submit"
                        class="btn btn-danger"
                        name="bulk_action"
                        value="delete"
                        onclick="return confirm('Xóa các danh mục đã chọn? Hành động này không thể hoàn tác.');"
                    >
                        🗑️ Xóa đã chọn
                    </button>
                </form>
            </div>
        </section>

        {{-- Pagination --}}
        <div class="categories-pagination">
            {{ $categories->links() }}
        </div>
    </div>

    {{-- Quick Edit Modal --}}
    <div class="quick-edit-modal" id="quickEditModal">
        <div class="quick-edit-modal-content">
            <div class="quick-edit-modal-header">
                <h3>Sửa nhanh danh mục</h3>
                <button type="button" class="quick-edit-modal-close" id="quickEditModalClose">×</button>
            </div>
            <form id="quickEditForm" enctype="multipart/form-data">
                <div class="quick-edit-modal-body">
                    <input type="hidden" id="quickEditCategoryId" />
                    @csrf
                    @method('PATCH')

                    {{-- TAB NAVIGATION --}}
                    <div class="quick-edit-tabs">
                        <button type="button" class="quick-edit-tab-btn active" data-tab="basic">
                            📋 Cơ bản
                        </button>
                        <button type="button" class="quick-edit-tab-btn" data-tab="seo">
                            🔍 SEO Meta
                        </button>
                    </div>

                    {{-- TAB BASIC --}}
                    <div class="quick-edit-tab-content active" id="tab-basic">
                        <div class="quick-edit-form-group">
                            <label for="quickEditName">Tên danh mục *</label>
                            <input 
                                type="text" 
                                id="quickEditName" 
                                name="name" 
                                placeholder="Nhập tên danh mục"
                                required
                            >
                        </div>

                        <div class="quick-edit-form-group">
                            <label for="quickEditSlug">Slug *</label>
                            <input 
                                type="text" 
                                id="quickEditSlug" 
                                name="slug" 
                                placeholder="category-name"
                                required
                            >
                        </div>

                        <div class="quick-edit-form-row">
                            <div class="quick-edit-form-group" style="flex: 1;">
                                <label for="quickEditSortOrder">Thứ tự</label>
                                <input 
                                    type="number" 
                                    id="quickEditSortOrder" 
                                    name="sort_order" 
                                    min="0" 
                                    value="0"
                                >
                            </div>

                            <div class="quick-edit-form-group" style="flex: 1;">
                                <label for="quickEditActive" style="display: flex; align-items: center; margin-bottom: 0;">
                                    <input 
                                        type="checkbox" 
                                        id="quickEditActive" 
                                        name="is_active"
                                        style="width: 18px; height: 18px; margin-right: 8px; cursor: pointer;"
                                    >
                                    <span>Hoạt động</span>
                                </label>
                            </div>
                        </div>

                        <div class="quick-edit-form-group">
                            <label for="quickEditImage">Ảnh danh mục</label>
                            <div class="quick-edit-image-preview">
                                <img id="quickEditImagePreview" src="" alt="Preview" style="display: none;">
                            </div>
                            <input 
                                type="file" 
                                id="quickEditImage" 
                                name="image" 
                                accept="image/*"
                            >
                            <small style="color: #64748b;">Hỗ trợ: JPEG, PNG, GIF, WebP, AVIF. Max 5MB</small>
                        </div>
                    </div>

                    {{-- TAB SEO --}}
                    <div class="quick-edit-tab-content" id="tab-seo">
                        <div class="quick-edit-form-group">
                            <label for="quickEditMetaTitle">Meta Title</label>
                            <input 
                                type="text" 
                                id="quickEditMetaTitle" 
                                name="meta_title" 
                                placeholder="Tiêu đề cho SEO"
                                maxlength="255"
                            >
                            <small style="color: #64748b;">Tối đa 60 ký tự để hiển thị tốt trên Google</small>
                        </div>

                        <div class="quick-edit-form-group">
                            <label for="quickEditMetaDescription">Meta Description</label>
                            <textarea 
                                id="quickEditMetaDescription" 
                                name="meta_description" 
                                placeholder="Mô tả ngắn cho SEO"
                                maxlength="500"
                            ></textarea>
                            <small style="color: #64748b;">Tối đa 160 ký tự để hiển thị tốt trên Google</small>
                        </div>

                        <div class="quick-edit-form-group">
                            <label for="quickEditMetaKeywords">Meta Keywords</label>
                            <textarea 
                                id="quickEditMetaKeywords" 
                                name="meta_keywords" 
                                placeholder="Từ khóa SEO (cách nhau bằng dấu phẩy)"
                                maxlength="500"
                            ></textarea>
                            <small style="color: #64748b;">VD: thời trang, áo nam, quần dài</small>
                        </div>
                    </div>
                </div>

                <div class="quick-edit-modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="quickEditModalCancel">Hủy</button>
                    <button type="submit" class="btn btn-primary">💾 Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Xuất CSV/Excel -->
    <div class="modal fade" id="exportCategoriesModal" tabindex="-1" aria-labelledby="exportCategoriesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold" id="exportCategoriesModalLabel">
                        <i class="fas fa-file-download text-success me-2"></i> Xuất dữ liệu Danh mục ra CSV / Excel
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Phạm vi xuất -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-uppercase small text-muted">1. Phạm vi danh mục cần xuất</label>
                        <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded-3 border">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exportScope" id="scopeAll" value="all" checked>
                                <label class="form-check-label fw-semibold" for="scopeAll">
                                    <i class="fas fa-globe text-primary me-1"></i> Tất cả danh mục trong hệ thống
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exportScope" id="scopeFilter" value="filter">
                                <label class="form-check-label fw-semibold" for="scopeFilter">
                                    <i class="fas fa-filter text-info me-1"></i> Theo bộ lọc tìm kiếm hiện tại trên trang
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Định dạng xuất -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-uppercase small text-muted">2. Định dạng tệp xuất</label>
                        <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded-3 border">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exportFormat" id="formatCsv" value="csv" checked>
                                <label class="form-check-label fw-semibold" for="formatCsv">
                                    <i class="fas fa-file-csv text-success me-1"></i> CSV (Streaming siêu tốc, 0MB RAM, hỗ trợ 100k+ danh mục)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exportFormat" id="formatXlsx" value="xlsx">
                                <label class="form-check-label fw-semibold" for="formatXlsx">
                                    <i class="fas fa-file-excel text-success me-1"></i> Microsoft Excel (.xlsx)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Chọn cột xuất -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold text-uppercase small text-muted mb-0">3. Chọn các cột cần xuất</label>
                            <div class="btn-group btn-group-sm">
                                <button type="button" id="btnExportSelectAll" class="btn btn-outline-secondary py-0">Chọn tất cả</button>
                                <button type="button" id="btnExportDeselectAll" class="btn btn-outline-secondary py-0">Bỏ chọn</button>
                                <button type="button" id="btnExportDefaultCols" class="btn btn-outline-primary py-0">Mặc định</button>
                            </div>
                        </div>
                        <div class="p-3 bg-light rounded-3 border">
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
                <div class="modal-footer border-0 pt-0 pb-4 px-4 d-flex justify-content-between">
                    <a href="{{ route('admin.categories.sample') }}" class="btn btn-outline-secondary rounded-3">
                        <i class="fas fa-download me-1"></i> Tải file mẫu CSV
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" id="btnConfirmExport" class="btn btn-success rounded-3 px-4 fw-bold">
                            <i class="fas fa-file-download me-1"></i> Bắt đầu Xuất Dữ Liệu
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nhập CSV/Excel -->
    <div class="modal fade" id="importCategoriesModal" tabindex="-1" aria-labelledby="importCategoriesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold" id="importCategoriesModalLabel">
                        <i class="fas fa-file-upload text-info me-2"></i> Nhập danh mục từ CSV / Excel (Batch Ultra Fast)
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
                                        <i class="fas fa-cloud-upload-alt text-primary fa-2x mb-2"></i>
                                        <p class="mb-1 fw-semibold small text-dark">Kéo thả file CSV/Excel vào đây hoặc nhấn để chọn</p>
                                        <span class="text-muted" style="font-size: 0.75rem;">Hỗ trợ .csv, .xlsx, .xls (Không giới hạn dung lượng)</span>
                                    </div>
                                    <div id="modalImportFileInfo" class="d-none text-start p-2 bg-white rounded shadow-sm">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center overflow-hidden">
                                                <i class="fas fa-file-csv text-success fa-2x me-2"></i>
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

                            <!-- Quy tắc khớp thông minh -->
                            <div class="alert alert-info py-2 px-3 mb-3 small border-0" style="background: #f0f9ff; border-left: 4px solid #0284c7 !important;">
                                <div class="fw-bold mb-1 text-primary"><i class="fas fa-shield-alt me-1"></i> Quy tắc khớp danh mục thông minh:</div>
                                <ul class="mb-0 ps-3">
                                    <li><strong>Có ID:</strong> Bắt buộc là cập nhật danh mục theo ID. (Báo lỗi nếu ID không tồn tại).</li>
                                    <li><strong>Không có ID:</strong> Khớp theo <code>Slug</code> (hoặc sinh từ Tên). Nếu có -> Cập nhật; nếu chưa -> Tạo mới.</li>
                                    <li><strong>Danh mục cha:</strong> Tự động ánh xạ qua ID, Slug hoặc Tên. Tự tạo mới danh mục cha nếu chưa tồn tại.</li>
                                </ul>
                            </div>

                            <!-- Chọn cột nhập vào (hiển thị khi đã đọc file) -->
                            <div id="modalImportColumnsContainer" class="d-none mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-bold text-uppercase small text-muted mb-0">2. Chọn các cột cần nhập vào</label>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" id="btnModalImportSelectAll" class="btn btn-outline-secondary py-0">Chọn hết</button>
                                        <button type="button" id="btnModalImportDeselectAll" class="btn btn-outline-secondary py-0">Bỏ hết</button>
                                        <button type="button" id="btnModalImportDefaultCols" class="btn btn-outline-primary py-0">Mặc định</button>
                                    </div>
                                </div>
                                <div class="p-3 bg-light rounded-3 border" style="max-height: 180px; overflow-y: auto;">
                                    <div class="row g-2" id="modalImportColumnsList">
                                        <!-- Render dynamic columns -->
                                    </div>
                                </div>
                                <div class="text-muted small mt-1 fst-italic">* Cột bỏ chọn sẽ giữ nguyên dữ liệu cũ khi cập nhật.</div>
                            </div>

                            <!-- Cấu hình Batch -->
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-muted mb-1">Kích thước mẻ:</label>
                                    <select id="modalBatchSize" class="form-select form-select-sm">
                                        <option value="50">50 danh mục / mẻ</option>
                                        <option value="100" selected>100 danh mục / mẻ</option>
                                        <option value="200">200 danh mục / mẻ</option>
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

                        <!-- Cột phải: Tiến trình & Console Log -->
                        <div class="col-lg-6">
                            <label class="form-label fw-bold text-uppercase small text-muted">3. Trạng thái & Tiến trình xử lý</label>
                            
                            <!-- Thống kê 4 ô -->
                            <div class="row g-2 mb-3">
                                <div class="col-3">
                                    <div class="p-2 text-center rounded bg-light border">
                                        <div class="text-muted small" style="font-size: 0.72rem;">Tổng số</div>
                                        <div id="modalStatTotal" class="h6 fw-bold mb-0 text-dark">0</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 text-center rounded" style="background: #ecfdf5; border: 1px dashed #10b981;">
                                        <div class="text-success small" style="font-size: 0.72rem;">Thành công</div>
                                        <div id="modalStatSuccess" class="h6 fw-bold mb-0 text-success">0</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 text-center rounded" style="background: #eff6ff; border: 1px dashed #3b82f6;">
                                        <div class="text-primary small" style="font-size: 0.72rem;">Tạo mới</div>
                                        <div id="modalStatCreated" class="h6 fw-bold mb-0 text-primary">0</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 text-center rounded" style="background: #fef2f2; border: 1px dashed #ef4444;">
                                        <div class="text-danger small" style="font-size: 0.72rem;">Lỗi</div>
                                        <div id="modalStatError" class="h6 fw-bold mb-0 text-danger">0</div>
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

                            <!-- Activity Log Terminal -->
                            <div class="rounded-3 bg-dark overflow-hidden">
                                <div class="d-flex justify-content-between align-items-center px-3 py-1 bg-secondary text-white" style="font-size: 0.75rem;">
                                    <span class="fw-bold"><i class="fas fa-terminal me-1"></i> NHẬT KÝ TIẾN TRÌNH</span>
                                    <span id="modalCurrentStatus" class="opacity-75">Sẵn sàng...</span>
                                </div>
                                <div id="modalImportLog" class="p-2 font-monospace text-white-50 overflow-auto" style="height: 220px; font-size: 0.78rem; background: #0f172a;">
                                    <div>> Vui lòng chọn file CSV/Excel để bắt đầu...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4 d-flex justify-content-between">
                    <a href="{{ route('admin.categories.sample') }}" class="btn btn-outline-secondary rounded-3">
                        <i class="fas fa-download me-1"></i> Tải file mẫu CSV
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" id="btnStartCategoryImportBatch" class="btn btn-primary rounded-3 px-4 fw-bold" disabled>
                            <i class="fas fa-rocket me-1"></i> Bắt đầu Nhập Dữ Liệu
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <script>
        // UTF-8 Vietnamese slug generator (remove diacritics)
        function toSlugVN(text) {
            if (!text) return '';
            
            // Map Vietnamese characters to ASCII equivalents
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
            const bulkForm = document.getElementById('category-bulk-form');
            const categoriesTable = document.getElementById('categoriesTable');
            const categoryStatuses = document.querySelectorAll('.categories-status');

            // ===== QUICK EDIT MODAL FUNCTIONS =====
            const quickEditModal = document.getElementById('quickEditModal');
            const quickEditForm = document.getElementById('quickEditForm');
            const quickEditModalClose = document.getElementById('quickEditModalClose');
            const quickEditModalCancel = document.getElementById('quickEditModalCancel');
            const quickEditButtons = document.querySelectorAll('button.quick-edit-btn');
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

            // Tab switching
            const tabBtns = document.querySelectorAll('.quick-edit-tab-btn');
            tabBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const tabName = btn.dataset.tab;
                    
                    // Remove active from all tabs and contents
                    tabBtns.forEach(b => b.classList.remove('active'));
                    document.querySelectorAll('.quick-edit-tab-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    
                    // Add active to current
                    btn.classList.add('active');
                    document.getElementById('tab-' + tabName).classList.add('active');
                });
            });

            // Image preview on file select
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

            // Open quick edit modal
            quickEditButtons.forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const categoryId = btn.dataset.categoryId;
                    if (!categoryId) return;

                    // Immediately populate from button dataset for zero delay
                    quickEditCategoryId.value = categoryId;
                    quickEditName.value = btn.dataset.categoryName || '';
                    quickEditSlug.value = btn.dataset.categorySlug || '';
                    quickEditSortOrder.value = btn.dataset.categorySortOrder ?? 0;
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

                    // Reset tab to basic
                    document.querySelectorAll('.quick-edit-tab-btn').forEach(b => b.classList.remove('active'));
                    document.querySelectorAll('.quick-edit-tab-content').forEach(c => c.classList.remove('active'));
                    document.querySelector('[data-tab="basic"]').classList.add('active');
                    document.getElementById('tab-basic').classList.add('active');

                    // Reset file input
                    quickEditImage.value = '';

                    quickEditModal.classList.add('active');
                    quickEditName.focus();

                    // Fetch fresh JSON from server
                    try {
                        const response = await fetch(`/admin/categories/${categoryId}/edit`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (response.ok) {
                            const res = await response.json();
                            const data = res.data || res;
                            if (data) {
                                if (data.name !== undefined) quickEditName.value = data.name;
                                if (data.slug !== undefined) quickEditSlug.value = data.slug;
                                if (data.sort_order !== undefined) quickEditSortOrder.value = data.sort_order;
                                if (data.is_active !== undefined) quickEditActive.checked = Boolean(data.is_active);
                                if (data.meta_title !== undefined) quickEditMetaTitle.value = data.meta_title || '';
                                if (data.meta_description !== undefined) quickEditMetaDescription.value = data.meta_description || '';
                                if (data.meta_keywords !== undefined) quickEditMetaKeywords.value = data.meta_keywords || '';
                                if (data.image) {
                                    quickEditImagePreview.src = data.image;
                                    quickEditImagePreview.style.display = 'block';
                                }
                            }
                        }
                    } catch (error) {
                        console.warn('Could not fetch category JSON:', error);
                    }
                });
            });

            // Close quick edit modal
            const closeQuickEditModal = () => {
                quickEditModal.classList.remove('active');
                quickEditForm.reset();
                quickEditImagePreview.style.display = 'none';
            };

            quickEditModalClose.addEventListener('click', closeQuickEditModal);
            quickEditModalCancel.addEventListener('click', closeQuickEditModal);

            // Close on backdrop click
            quickEditModal.addEventListener('click', (e) => {
                if (e.target === quickEditModal) {
                    closeQuickEditModal();
                }
            });

            // Generate slug only when EMPTY
            quickEditName.addEventListener('input', () => {
                // Only auto-generate slug if slug field is EMPTY
                if (quickEditSlug.value.trim() === '') {
                    quickEditSlug.value = toSlugVN(quickEditName.value);
                }
            });

            // Submit quick edit form
            quickEditForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const categoryId = quickEditCategoryId.value;
                const name = quickEditName.value.trim();
                const slug = quickEditSlug.value.trim();
                const sortOrder = parseInt(quickEditSortOrder.value) || 0;
                const isActive = quickEditActive.checked;
                const metaTitle = quickEditMetaTitle.value.trim();
                const metaDescription = quickEditMetaDescription.value.trim();
                const metaKeywords = quickEditMetaKeywords.value.trim();

                if (!categoryId || !name || !slug) {
                    alert('Vui lòng điền đầy đủ thông tin (tên, slug)');
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('name', name);
                    formData.append('slug', slug);
                    formData.append('sort_order', sortOrder);
                    formData.append('is_active', isActive ? '1' : '0');
                    formData.append('meta_title', metaTitle);
                    formData.append('meta_description', metaDescription);
                    formData.append('meta_keywords', metaKeywords);

                    // Add image if selected
                    if (quickEditImage.files.length > 0) {
                        formData.append('image', quickEditImage.files[0]);
                    }

                    formData.append('_method', 'PATCH');
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

                    const response = await fetch(
                        `/admin/categories/${categoryId}/quick-update`,
                        {
                            method: 'POST',
                            body: formData
                        }
                    );

                    let data = null;
                    try {
                        data = await response.json();
                    } catch (parseError) {
                        console.error('Response parse error:', response.status, response.statusText);
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    if (response.ok && data.success) {
                        // Update table row
                        const row = categoriesTable.querySelector(`tr[data-category-id="${categoryId}"]`);
                        if (row) {
                            const nameCell = row.querySelector('.categories-table__name strong');
                            const metaCell = row.querySelector('.categories-table__meta');
                            if (nameCell) nameCell.textContent = name;
                            if (metaCell) metaCell.textContent = slug;

                            const statusBadge = row.querySelector('.categories-status');
                            if (statusBadge) {
                                if (isActive) {
                                    statusBadge.className = 'categories-status categories-status--active';
                                    statusBadge.textContent = '✓ Hoạt động';
                                } else {
                                    statusBadge.className = 'categories-status categories-status--inactive';
                                    statusBadge.textContent = '✕ Tạm ẩn';
                                }
                            }
                        }

                        // Update button dataset attributes
                        const btn = document.querySelector(`button.quick-edit-btn[data-category-id="${categoryId}"]`);
                        if (btn) {
                            btn.dataset.categoryName = name;
                            btn.dataset.categorySlug = slug;
                            btn.dataset.categorySortOrder = sortOrder;
                            btn.dataset.categoryActive = isActive ? '1' : '0';
                            btn.dataset.categoryMetaTitle = metaTitle;
                            btn.dataset.categoryMetaDescription = metaDescription;
                            btn.dataset.categoryMetaKeywords = metaKeywords;
                            if (data.category && data.category.image) {
                                btn.dataset.categoryImage = '/clients/assets/img/categories/' + data.category.image;
                            }
                        }

                        closeQuickEditModal();
                        alert('✅ Đã cập nhật danh mục thành công!');
                    } else {
                        const errorMsg = data?.message || data?.errors || 'Không thể cập nhật danh mục';
                        console.error('Server error:', errorMsg);
                        alert('❌ Lỗi: ' + (typeof errorMsg === 'object' ? JSON.stringify(errorMsg) : errorMsg));
                    }
                } catch (error) {
                    console.error('Quick edit error details:', error.message, error.stack);
                    alert('❌ Có lỗi xảy ra:\n' + error.message);
                }
            });

            // ===== SELECT ALL LOGIC =====
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', () => {
                    categoryCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
                });
            }

            // ===== BULK FORM VALIDATION =====
            if (bulkForm) {
                bulkForm.addEventListener('submit', (e) => {
                    const anyChecked = Array.from(categoryCheckboxes).some(cb => cb.checked);
                    if (!anyChecked) {
                        e.preventDefault();
                        alert('Vui lòng chọn ít nhất một danh mục.');
                    }
                });
            }

            // ===== QUICK TOGGLE STATUS =====
            categoryStatuses.forEach(status => {
                status.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const categoryId = status.dataset.categoryId;
                    const row = categoriesTable.querySelector(`tr[data-category-id="${categoryId}"]`);

                    try {
                        const response = await fetch(
                            `/admin/categories/${categoryId}/quick-toggle`,
                            {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                }
                            }
                        );

                        const data = await response.json();

                        if (data.success) {
                            // Update status badge
                            if (data.is_active) {
                                status.classList.remove('categories-status--inactive');
                                status.classList.add('categories-status--active');
                                status.textContent = '✓ Hoạt động';
                            } else {
                                status.classList.remove('categories-status--active');
                                status.classList.add('categories-status--inactive');
                                status.textContent = '✕ Tạm ẩn';
                            }
                        }
                    } catch (error) {
                        console.error('Error toggling status:', error);
                        alert('Có lỗi xảy ra khi cập nhật trạng thái.');
                    }
                });
            });

            // ===== DRAG & DROP SORTING =====
            const categorysList = document.getElementById('categoriesList');
            if (categorysList && categorysList.children.length > 0 && categorysList.children[0].dataset.categoryId) {
                Sortable.create(categorysList, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'dragging',
                    onEnd: async (evt) => {
                        // Collect new sort order
                        const items = Array.from(categorysList.querySelectorAll('tr')).map((tr, index) => ({
                            id: parseInt(tr.dataset.categoryId),
                            sort_order: index
                        }));

                        try {
                            const response = await fetch(
                                '{{ route("admin.categories.update-sort") }}',
                                {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                    },
                                    body: JSON.stringify({ items })
                                }
                            );

                            const data = await response.json();
                            if (data.success) {
                                // Optional: Show success toast
                                console.log('Đã cập nhật thứ tự danh mục.');
                            }
                        } catch (error) {
                            console.error('Error updating sort:', error);
                            alert('Có lỗi xảy ra khi cập nhật thứ tự.');
                            // Reset to original position
                            location.reload();
                        }
                    }
                });
            }

            // ==========================================
            // LOGIC XUẤT CSV / EXCEL DANH MỤC
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

                    if (scope === 'filter') {
                        const currentUrlParams = new URLSearchParams(window.location.search);
                        for (const [key, val] of currentUrlParams.entries()) {
                            if (val && key !== 'page') {
                                params.append(key, val);
                            }
                        }
                    }

                    // Đóng modal
                    if (typeof bootstrap !== 'undefined' && exportModalEl) {
                        const modalInstance = bootstrap.Modal.getInstance(exportModalEl) || new bootstrap.Modal(exportModalEl);
                        modalInstance.hide();
                    }

                    // Tải file trực tiếp dạng streaming
                    window.location.href = "{{ route('admin.categories.export') }}?" + params.toString();
                });
            }

            // ==========================================
            // LOGIC NHẬP CSV / EXCEL DANH MỤC (BATCH ULTRA FAST)
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
                    alert('Định dạng không hợp lệ. Vui lòng chọn tệp có đuôi .csv, .xlsx hoặc .xls');
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

                        // Render danh sách checkbox các cột tìm thấy
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
                        addModalLog(`Đọc tệp thành công! Tìm thấy ${categoryImportData.length} danh mục và ${detectedImportHeaders.length} cột.`, 'success');
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

                    addModalLog(`Bắt đầu nhập ${categoryImportData.length} danh mục với ${concurrency} luồng song song (mỗi mẻ ${batchSize} dòng)...`, 'info');

                    // Tạo các chunks
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
                                    addModalLog(`[Luồng ${workerId}] Mẻ ${chunk.index}/${totalBatches} thành công (+${result.success_count} danh mục).`, 'success');
                                } else {
                                    throw new Error(result.message || 'Lỗi xử lý từ máy chủ');
                                }
                            } catch (err) {
                                console.error(err);
                                addModalLog(`❌ [Luồng ${workerId}] Lỗi tại mẻ ${chunk.index}: ${err.message}`, 'error');
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
                    addModalLog(`🎉 Quá trình nhập hoàn tất! Thành công: ${successCount} (Tạo mới: ${createdCount}), Lỗi: ${errorCount}`, 'success');
                    btnStartCategoryImportBatch.innerHTML = '<i class="fas fa-check me-2"></i> HOÀN TẤT NHẬP DỮ LIỆU';
                    btnStartCategoryImportBatch.classList.remove('btn-primary');
                    btnStartCategoryImportBatch.classList.add('btn-success');

                    setTimeout(() => {
                        if (confirm(`Quá trình nhập dữ liệu danh mục đã hoàn tất!\n- Thành công: ${successCount}\n- Tạo mới: ${createdCount}\n- Lỗi: ${errorCount}\n\nBạn có muốn làm mới trang để xem danh sách cập nhật không?`)) {
                            window.location.reload();
                        }
                    }, 800);
                });
            }
        });
    </script>
@endpush
