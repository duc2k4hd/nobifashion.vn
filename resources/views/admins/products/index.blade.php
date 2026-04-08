@extends('admins.layouts.master')

@section('title', 'Quản lý sản phẩm')
@section('page-title', 'Sản phẩm')

@push('head')
    @php
        $slimSelectCssPath = public_path('admins/vendor/slimselect/slimselect.css');
        $slimSelectCssVersion = file_exists($slimSelectCssPath) ? filemtime($slimSelectCssPath) : null;
        $slimSelectCssAsset = asset('admins/vendor/slimselect/slimselect.css') . ($slimSelectCssVersion ? '?v=' . $slimSelectCssVersion : '');
        $slimSelectJsPath = public_path('admins/vendor/slimselect/slimselect.min.js');
        $slimSelectJsVersion = file_exists($slimSelectJsPath) ? filemtime($slimSelectJsPath) : null;
        $slimSelectJsAsset = asset('admins/vendor/slimselect/slimselect.min.js') . ($slimSelectJsVersion ? '?v=' . $slimSelectJsVersion : '');
    @endphp
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/products-icon.png') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ $slimSelectCssAsset }}">
@endpush

@push('styles')
    <style>
        .products-page {
            display: grid;
            gap: 20px;
        }

        .products-toolbar,
        .products-import-card,
        .products-filters,
        .products-table-card,
        .products-bulk-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        }

        .products-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 20px 24px;
            flex-wrap: wrap;
        }

        .products-toolbar__title {
            display: grid;
            gap: 6px;
        }

        .products-toolbar__title h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .products-toolbar__title p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .products-toolbar__actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .products-filters {
            padding: 22px 24px;
        }

        .products-import-card {
            padding: 22px 24px;
        }

        .products-import-card__head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .products-import-card__head h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .products-import-card__head p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .products-import-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
            align-items: end;
        }

        .products-import-field {
            display: grid;
            gap: 8px;
        }

        .products-import-field label {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        .products-import-field input[type="file"] {
            width: 100%;
            min-height: 52px;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 14px;
            color: #0f172a;
            background: #f8fafc;
        }

        .products-import-hint {
            color: #64748b;
            font-size: 13px;
        }

        .products-filters__head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .products-filters__head h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .products-filters__head p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .products-filters__summary {
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

        .products-filter-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 14px;
            align-items: end;
        }

        .products-filter-field {
            display: grid;
            gap: 8px;
        }

        .products-filter-field--keyword {
            grid-column: span 2;
        }

        .products-filter-field label {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        .products-filter-field input,
        .products-filter-field select {
            width: 100%;
            min-height: 44px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 14px;
            color: #0f172a;
            background: #fff;
        }

        .products-filter-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .products-filter-actions .btn {
            min-height: 44px;
            padding-inline: 18px;
        }

        .products-table-card {
            overflow: hidden;
        }

        .products-table-card__head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 18px 24px;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        .products-table-card__head h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .products-table-card__head p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .products-table-wrap {
            overflow-x: auto;
        }

        .products-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .products-table th,
        .products-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: top;
            text-align: left;
        }

        .products-table th {
            background: #f8fafc;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #475569;
            white-space: nowrap;
        }

        .products-table tbody tr:hover td {
            background: #f8fafc;
        }

        .products-table__name {
            display: grid;
            gap: 4px;
        }

        .products-table__name strong {
            color: #0f172a;
            font-size: 15px;
        }

        .products-table__meta {
            color: #64748b;
            font-size: 13px;
        }

        .products-table__price {
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
        }

        .products-status {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .products-status--active {
            background: #dcfce7;
            color: #166534;
        }

        .products-status--inactive {
            background: #fee2e2;
            color: #b91c1c;
        }

        .products-status--trash {
            background: #e2e8f0;
            color: #334155;
        }

        .products-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .products-actions form {
            margin: 0;
        }

        .products-empty {
            padding: 48px 20px;
            text-align: center;
            color: #94a3b8;
            font-size: 15px;
        }

        .products-bulk-card {
            padding: 18px 24px;
        }

        .products-bulk-card__inner {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .products-bulk-card__hint {
            color: #64748b;
            font-size: 14px;
        }

        .products-bulk-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .products-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #2563eb;
            cursor: pointer;
        }

        .products-pagination {
            margin-top: 8px;
        }

        .products-filters .ss-main,
        .products-filters .ss-content {
            border-radius: 12px;
        }

        .products-filters .ss-main {
            min-height: 44px;
            border-color: #cbd5e1;
            padding-inline: 10px;
        }

        .products-filters .ss-main:focus {
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
            border-color: #2563eb;
        }

        .products-filters .ss-values {
            font-size: 14px;
            color: #0f172a;
        }

        .products-search-hint {
            font-size: 12px;
            line-height: 1.5;
        }

        @media (max-width: 1440px) {
            .products-filter-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .products-filter-field--keyword {
                grid-column: span 2;
            }
        }

        @media (max-width: 992px) {
            .products-import-form,
            .products-filters__head,
            .products-bulk-card__inner {
                flex-direction: column;
                align-items: stretch;
            }

            .products-import-form {
                grid-template-columns: 1fr;
            }

            .products-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .products-filter-field--keyword {
                grid-column: span 2;
            }

            .products-actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 640px) {
            .products-filter-grid {
                grid-template-columns: 1fr;
            }

            .products-filter-field--keyword {
                grid-column: span 1;
            }

            .products-toolbar,
            .products-filters,
            .products-table-card__head,
            .products-bulk-card {
                padding-inline: 16px;
            }

            .products-table th,
            .products-table td {
                padding-inline: 12px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ $slimSelectJsAsset }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('select-all-products');
            const productCheckboxes = document.querySelectorAll('.product-checkbox');
            const bulkForm = document.getElementById('bulk-action-form');
            const importForm = document.getElementById('products-inline-import-form');
            const importFileInput = document.getElementById('products-inline-import-file');

            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    productCheckboxes.forEach((checkbox) => {
                        checkbox.checked = selectAll.checked;
                    });
                });
            }

            if (bulkForm) {
                bulkForm.addEventListener('submit', (event) => {
                    const hasSelected = Array.from(productCheckboxes).some((checkbox) => checkbox.checked);
                    if (!hasSelected) {
                        event.preventDefault();
                        alert('Vui lòng chọn ít nhất một sản phẩm trước khi thực hiện hành động.');
                    }
                });
            }

            if (importForm && importFileInput) {
                const maxSizeMb = Number(importFileInput.dataset.maxSizeMb || 50);
                const maxBytes = maxSizeMb * 1024 * 1024;

                const validateImportFile = () => {
                    const file = importFileInput.files && importFileInput.files[0] ? importFileInput.files[0] : null;
                    if (!file) {
                        return true;
                    }

                    if (file.size > maxBytes) {
                        window.alert(`File quá lớn. Vui lòng chọn file nhỏ hơn ${maxSizeMb}MB.`);
                        importFileInput.value = '';
                        return false;
                    }

                    return true;
                };

                importFileInput.addEventListener('change', validateImportFile);
                importForm.addEventListener('submit', (event) => {
                    if (!validateImportFile()) {
                        event.preventDefault();
                    }
                });
            }

            if (typeof SlimSelect !== 'undefined') {
                document.querySelectorAll('select[data-slim-select]').forEach((select) => {
                    new SlimSelect({
                        select,
                        settings: {
                            allowDeselect: select.dataset.allowDeselect === 'true',
                            searchPlaceholder: 'Tìm kiếm...',
                            searchText: 'Không tìm thấy dữ liệu phù hợp',
                            placeholderText: select.dataset.placeholder || '',
                            closeOnSelect: true,
                        }
                    });
                });
            }
        });
    </script>
@endpush

@section('content')
    <div class="products-page">
        <section class="products-toolbar">
            <div class="products-toolbar__title">
                <h2>Danh sách sản phẩm</h2>
                <p>Quản lý dữ liệu sản phẩm, bộ lọc, trạng thái bán và thao tác hàng loạt.</p>
            </div>

            <div class="products-toolbar__actions">
                <a href="{{ route('admin.products.import-excel') }}" class="btn btn-outline-secondary">Import Excel</a>
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Thêm sản phẩm</a>
            </div>
        </section>

        <section class="products-import-card">
            <div class="products-import-card__head">
                <div>
                    <h3>Nhập sản phẩm từ Excel</h3>
                    <p>Upload nhanh file `.xlsx` hoặc `.xls` ngay tại trang danh sách sản phẩm.</p>
                </div>
            </div>

            <form id="products-inline-import-form" method="POST" action="{{ route('admin.products.import-excel.process') }}" enctype="multipart/form-data" class="products-import-form">
                @csrf

                <div class="products-import-field">
                    <label for="products-inline-import-file">Chọn file Excel</label>
                    <input type="file" name="excel_file" id="products-inline-import-file" accept=".xlsx,.xls" required data-max-size-mb="50">
                    <div class="products-import-hint">Chỉ chấp nhận file `.xlsx` hoặc `.xls` (tối đa 50MB).</div>
                </div>

                <button type="submit" class="btn btn-primary">Nhập Excel</button>
            </form>
        </section>

        <section class="products-filters">
            <div class="products-filters__head">
                <div>
                    <h3>Bộ lọc sản phẩm</h3>
                    <p>Lọc theo từ khóa, hãng, danh mục, trạng thái, tồn kho và số lượng hiển thị.</p>
                </div>

                <div class="products-filters__summary">
                    <span>Hiển thị</span>
                    <strong>{{ number_format($products->firstItem() ?? 0) }} - {{ number_format($products->lastItem() ?? 0) }}</strong>
                    <span>trên tổng</span>
                    <strong>{{ number_format($products->total()) }}</strong>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.products.index') }}">
                <div class="products-filter-grid">
                    <div class="products-filter-field products-filter-field--keyword">
                        <label for="filter-keyword">Từ khóa</label>
                        <input
                            id="filter-keyword"
                            type="text"
                            name="keyword"
                            value="{{ request('keyword') }}"
                            placeholder="Tìm theo tên sản phẩm hoặc SKU..."
                        >
                        @if(($searchMeta['mode'] ?? null) === 'exact_phrase')
                            <div class="products-search-hint text-success">
                                Đang ưu tiên kết quả khớp đúng cụm từ trên tên sản phẩm.
                            </div>
                        @elseif(($searchMeta['mode'] ?? null) === 'progressive')
                            <div class="products-search-hint text-warning">
                                Không có bản ghi khớp đúng cụm từ. Hệ thống đang fallback theo các cụm gần đúng:
                                {{ collect($searchMeta['segments'] ?? [])->take(5)->implode(', ') }}.
                            </div>
                        @endif
                    </div>

                    <div class="products-filter-field">
                        <label for="filter-brand">Hãng</label>
                        <select
                            id="filter-brand"
                            name="brand_id"
                            data-slim-select
                            data-allow-deselect="true"
                            data-placeholder="Chọn hãng"
                        >
                            <option value="">Tất cả hãng</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ (string) request('brand_id') === (string) $brand->id ? 'selected' : '' }}>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="products-filter-field">
                        <label for="filter-category">Danh mục</label>
                        <select
                            id="filter-category"
                            name="category_id"
                            data-slim-select
                            data-allow-deselect="true"
                            data-placeholder="Chọn danh mục"
                        >
                            <option value="">Tất cả danh mục</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="products-filter-field">
                        <label for="filter-status">Trạng thái</label>
                        <select
                            id="filter-status"
                            name="status"
                            data-slim-select
                            data-allow-deselect="true"
                            data-placeholder="Chọn trạng thái"
                        >
                            <option value="">Tất cả trạng thái</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Đang bán</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Tạm ẩn</option>
                            <option value="trash" {{ request('status') === 'trash' ? 'selected' : '' }}>Thùng rác</option>
                        </select>
                    </div>

                    <div class="products-filter-field">
                        <label for="filter-stock-status">Kho hàng</label>
                        <select
                            id="filter-stock-status"
                            name="stock_status"
                            data-slim-select
                            data-allow-deselect="true"
                            data-placeholder="Chọn kho hàng"
                        >
                            <option value="">Tất cả tồn kho</option>
                            <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>Còn hàng</option>
                            <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Hết hàng</option>
                        </select>
                    </div>

                    <div class="products-filter-field">
                        <label for="filter-per-page">Hiển thị mỗi trang</label>
                        <select
                            id="filter-per-page"
                            name="per_page"
                            data-slim-select
                            data-placeholder="Chọn số lượng"
                        >
                            @foreach($perPageOptions as $option)
                                <option value="{{ $option }}" {{ (int) $perPage === (int) $option ? 'selected' : '' }}>
                                    {{ $option }} sản phẩm
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="products-filter-grid mt-3">
                    <div class="products-filter-field">
                        <label for="filter-featured">Nổi bật</label>
                        <select
                            id="filter-featured"
                            name="is_featured"
                            data-slim-select
                            data-allow-deselect="true"
                            data-placeholder="Chọn loại"
                        >
                            <option value="">Tất cả</option>
                            <option value="1" {{ request('is_featured') === '1' ? 'selected' : '' }}>Chỉ nổi bật</option>
                            <option value="0" {{ request('is_featured') === '0' ? 'selected' : '' }}>Không nổi bật</option>
                        </select>
                    </div>

                    <div class="products-filter-field">
                        <label for="filter-variants">Biến thể</label>
                        <select
                            id="filter-variants"
                            name="has_variants"
                            data-slim-select
                            data-allow-deselect="true"
                            data-placeholder="Chọn kiểu"
                        >
                            <option value="">Tất cả</option>
                            <option value="1" {{ request('has_variants') === '1' ? 'selected' : '' }}>Có biến thể</option>
                            <option value="0" {{ request('has_variants') === '0' ? 'selected' : '' }}>Không biến thể</option>
                        </select>
                    </div>

                    <div class="products-filter-field">
                        <label for="filter-flash-sale">Flash sale</label>
                        <select
                            id="filter-flash-sale"
                            name="flash_sale_status"
                            data-slim-select
                            data-allow-deselect="true"
                            data-placeholder="Chọn trạng thái"
                        >
                            <option value="">Tất cả</option>
                            <option value="1" {{ request('flash_sale_status') === '1' ? 'selected' : '' }}>Đang flash sale</option>
                            <option value="0" {{ request('flash_sale_status') === '0' ? 'selected' : '' }}>Không flash sale</option>
                        </select>
                    </div>

                    <div class="products-filter-field">
                        <label for="filter-sort">Sắp xếp</label>
                        <select
                            id="filter-sort"
                            name="sort_by"
                            data-slim-select
                            data-allow-deselect="true"
                            data-placeholder="Chọn cách sắp xếp"
                        >
                            <option value="latest" {{ request('sort_by', 'latest') === 'latest' ? 'selected' : '' }}>Mới nhất</option>
                            <option value="oldest" {{ request('sort_by') === 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                            <option value="name_asc" {{ request('sort_by') === 'name_asc' ? 'selected' : '' }}>Tên A-Z</option>
                            <option value="name_desc" {{ request('sort_by') === 'name_desc' ? 'selected' : '' }}>Tên Z-A</option>
                            <option value="price_asc" {{ request('sort_by') === 'price_asc' ? 'selected' : '' }}>Giá tăng dần</option>
                            <option value="price_desc" {{ request('sort_by') === 'price_desc' ? 'selected' : '' }}>Giá giảm dần</option>
                            <option value="stock_desc" {{ request('sort_by') === 'stock_desc' ? 'selected' : '' }}>Tồn kho giảm dần</option>
                            <option value="stock_asc" {{ request('sort_by') === 'stock_asc' ? 'selected' : '' }}>Tồn kho tăng dần</option>
                        </select>
                    </div>
                </div>

                <div class="products-filter-actions mt-3">
                    <button type="submit" class="btn btn-primary">Lọc dữ liệu</button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Đặt lại</a>
                </div>
            </form>
        </section>

        <section class="products-table-card">
            <div class="products-table-card__head">
                <div>
                    <h3>Danh sách kết quả</h3>
                    <p>Giữ nguyên bộ lọc khi phân trang và thao tác hàng loạt.</p>
                </div>
            </div>

            <div class="products-table-wrap">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th style="width: 48px;">
                                <input type="checkbox" id="select-all-products" class="products-checkbox">
                            </th>
                            <th>SKU</th>
                            <th>Tên sản phẩm</th>
                            <th>Hãng</th>
                            <th>Danh mục</th>
                            <th>Giá</th>
                            <th>Tồn kho</th>
                            <th>Trạng thái</th>
                            <th style="text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>
                                    <input
                                        type="checkbox"
                                        name="selected[]"
                                        value="{{ $product->id }}"
                                        class="product-checkbox products-checkbox"
                                        form="bulk-action-form"
                                    >
                                </td>
                                <td>{{ $product->sku }}</td>
                                <td>
                                    <div class="products-table__name">
                                        <strong>{{ $product->name }}</strong>
                                        <span class="products-table__meta">Slug: {{ $product->slug }}</span>
                                    </div>
                                </td>
                                <td>{{ $product->brand?->name ?? '-' }}</td>
                                <td>{{ $product->primaryCategory->name ?? '-' }}</td>
                                <td class="products-table__price">{{ number_format($product->price) }}đ</td>
                                <td>{{ number_format($product->stock_quantity) }}</td>
                                <td>
                                    @if($product->trashed())
                                        <span class="products-status products-status--trash">Đã xóa</span>
                                    @elseif($product->is_active)
                                        <span class="products-status products-status--active">Active</span>
                                    @else
                                        <span class="products-status products-status--inactive">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="products-actions">
                                        @if($product->trashed())
                                            <form action="{{ route('admin.products.restore', $product->id) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-secondary btn-sm" title="Khôi phục">
                                                    Khôi phục
                                                </button>
                                            </form>

                                            <form
                                                action="{{ route('admin.products.force-delete', $product->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Xóa vĩnh viễn sản phẩm này? Hành động này không thể hoàn tác.')"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-dark btn-sm" title="Xóa vĩnh viễn">
                                                    Xóa vĩnh viễn
                                                </button>
                                            </form>
                                        @else
                                            <div style="display: flex; gap: 10px;">
                                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-secondary btn-sm" title="Chỉnh sửa">
                                                    Sửa
                                                </a>

                                                <form
                                                    action="{{ route('admin.products.destroy', $product) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Ẩn sản phẩm này? Bạn có thể khôi phục trong Thùng rác.')"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Ẩn">
                                                        Ẩn
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="products-empty">
                                    Không có sản phẩm phù hợp với bộ lọc hiện tại.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="products-bulk-card">
            <div class="products-bulk-card__inner">
                <div class="products-bulk-card__hint">
                    Chọn nhiều sản phẩm trong bảng phía trên để thao tác hàng loạt.
                </div>

                <form
                    action="{{ route('admin.products.bulk-action') }}"
                    method="POST"
                    id="bulk-action-form"
                    class="products-bulk-actions"
                >
                    @csrf

                    @if(request('status') === 'trash')
                        <button type="submit" class="btn btn-outline-secondary" name="bulk_action" value="restore">
                            Khôi phục đã chọn
                        </button>
                        <button
                            type="submit"
                            class="btn btn-dark"
                            name="bulk_action"
                            value="force_delete"
                            onclick="return confirm('Xóa vĩnh viễn các sản phẩm đã chọn? Hành động này không thể hoàn tác.')"
                        >
                            Xóa vĩnh viễn đã chọn
                        </button>
                    @else
                        <button type="submit" class="btn btn-outline-secondary" name="bulk_action" value="show">
                            Hiện các sản phẩm đã chọn
                        </button>
                        <button type="submit" class="btn btn-outline-secondary" name="bulk_action" value="hide">
                            Ẩn các sản phẩm đã chọn
                        </button>
                        <button
                            type="submit"
                            class="btn btn-danger"
                            name="bulk_action"
                            value="delete"
                            onclick="return confirm('Bỏ vào thùng rác các sản phẩm đã chọn?')"
                        >
                            Bỏ vào thùng rác
                        </button>
                    @endif
                </form>
            </div>
        </section>

        <div class="products-pagination">
            {{ $products->links() }}
        </div>
    </div>
@endsection
