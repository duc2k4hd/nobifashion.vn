@extends('admins.layouts.master')

@section('title', 'Quản lý sản phẩm Flash Sale')
@section('page-title', '📦 Sản phẩm Flash Sale: ' . $flashSale->title)

@push('head')
<link rel="shortcut icon" href="{{ asset('admins/img/icons/flash-sale-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
<style>
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 20px;
    }

    .selected-product-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #e0f2fe;
        color: #0369a1;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        margin: 4px;
    }

    .selected-product-chip button {
        border: none;
        background: transparent;
        color: #0f172a;
        cursor: pointer;
        font-size: 14px;
        line-height: 1;
        padding: 0;
    }

    .items-table {
        width: 100%;
        min-width: 1200px;
        border-collapse: collapse;
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .items-table th,
    .items-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #eef2f7;
        text-align: left;
    }

    .items-table th {
        background: #f8fafc;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #475569;
    }

    .items-table tr:hover td {
        background: #f1f5f9;
    }

    .product-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 6px;
    }

    .badge {
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-success {
        background: #dcfce7;
        color: #15803d;
    }

    .badge-danger {
        background: #fee2e2;
        color: #b91c1c;
    }

    .badge-warning {
        background: #fef3c7;
        color: #92400e;
    }

    .progress-bar {
        width: 100%;
        height: 8px;
        background: #e2e8f0;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 4px;
    }

    .progress-fill {
        height: 100%;
        background: #10b981;
        transition: width 0.3s;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        position: relative;
        background: #fff;
        margin: auto;
        padding: 20px;
        top: 50%;
        transform: translateY(-50%);
        border-radius: 12px;
        width: 95vw;
        max-height: 95vh;
        overflow-y: auto;
    }

    .search-results {
        max-height: 400px;
        overflow-y: auto;
        margin-top: 10px;
    }

    .search-result-item {
        padding: 10px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        margin-bottom: 8px;
        cursor: pointer;
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .search-result-item:hover {
        background: #f1f5f9;
    }

    .inline-edit {
        display: inline-block;
        min-width: 80px;
        padding: 4px 8px;
        border: 1px solid transparent;
        border-radius: 4px;
    }

    .inline-edit:hover {
        border-color: #cbd5f5;
        background: #f8fafc;
    }

    .inline-edit.editing {
        border-color: #6366f1;
        background: #fff;
    }

    .price-input-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .price-suggest {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #64748b;
    }

    .price-suggest .suggest-price-btn {
        border: 1px solid #d4d4d8;
        background: #f4f4f5;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .price-suggest .suggest-price-btn:hover {
        background: #e0e7ff;
        border-color: #a5b4fc;
        color: #4338ca;
    }

    .price-actions {
        display: flex;
        justify-content: flex-start;
        margin-top: 4px;
    }

    .price-actions .btn {
        font-size: 12px;
        padding: 4px 10px;
    }

    .suggested-products-trigger {
        display: flex;
        gap: 12px;
        align-items: center;
        margin-top: 12px;
        flex-wrap: wrap;
    }

    .suggested-products-trigger span {
        color: #94a3b8;
        font-size: 13px;
    }

    .suggested-products-wrapper {
        margin-top: 14px;
        border: 1px dashed #cbd5f5;
        border-radius: 12px;
        padding: 16px;
        background: #f8fafc;
        display: none;
        flex-direction: column;
        gap: 12px;
    }

    .suggested-products-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    .suggested-products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
    }

    .suggested-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .suggested-card img {
        width: 100%;
        height: 110px;
        object-fit: cover;
        border-radius: 10px;
    }

    .suggested-card h5 {
        margin: 0;
        font-size: 15px;
        color: #0f172a;
    }

    .suggested-card .meta {
        font-size: 13px;
        color: #64748b;
    }

    .suggested-card .meta strong {
        color: #0ea5e9;
    }

    .suggested-card .actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        margin-top: 6px;
    }

    .suggested-card .badge {
        background: #e0f2fe;
        color: #0369a1;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
    }

    .price-log-modal {
        max-width: 640px;
    }

    .price-logs-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        max-height: 60vh;
        overflow-y: auto;
    }

    .price-log-item {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px;
        background: #fff;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.05);
    }

    .price-log-item h5 {
        margin: 0 0 6px;
        font-size: 14px;
        color: #0f172a;
    }

    .price-log-meta {
        font-size: 12px;
        color: #94a3b8;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
    }

    .suggested-products-loading {
        text-align: center;
        color: #64748b;
        font-size: 14px;
    }

    /* Tab Styles */
    .tab-container {
        margin-bottom: 20px;
    }

    .tabs {
        display: flex;
        gap: 0;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 20px;
    }

    .tab {
        padding: 12px 24px;
        background: #f8f9fa;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: #64748b;
        transition: all 0.2s;
        flex: 1;
        text-align: center;
    }

    .tab:hover {
        background: #f1f5f9;
        color: #334155;
    }

    .tab.active {
        background: #fff;
        color: #6366f1;
        border-bottom-color: #6366f1;
        font-weight: 600;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    /* Pagination Wrapper */
    .pagination-wrapper {
        margin: 30px 0;
    }

    /* Product picker modal */
    .product-picker-modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(2px);
        padding: 20px;
    }

    .product-picker-content {
        background: #fff;
        border-radius: 16px;
        width: 95vw;
        margin: 0 auto;
        height: 95vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 60px rgba(15, 23, 42, 0.2);
    }

    .product-picker-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .product-picker-body {
        flex: 1;
        display: grid;
        grid-template-columns: 260px 1fr;
        overflow: hidden;
    }

    .picker-sidebar {
        border-right: 1px solid #e2e8f0;
        overflow-y: auto;
        padding: 16px;
        background: #f8fafc;
    }

    .picker-sidebar h4 {
        font-size: 14px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 12px;
    }

    .category-btn {
        width: 100%;
        text-align: left;
        padding: 10px 12px;
        border: none;
        border-radius: 8px;
        background: transparent;
        margin-bottom: 6px;
        font-size: 14px;
        color: #475569;
        cursor: pointer;
        transition: background 0.15s, color 0.15s;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .category-btn span {
        font-size: 12px;
        color: #94a3b8;
    }

    .category-btn:hover,
    .category-btn.active {
        background: #e0e7ff;
        color: #4338ca;
    }

    .picker-main {
        padding: 16px 24px;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .picker-filters {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .picker-search input {
        padding: 10px 14px;
        border-radius: 10px;
        border: 1px solid #cbd5f5;
        width: 280px;
    }

    .products-grid-picker {
        flex: 1;
        overflow-y: auto;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 16px;
        padding-right: 4px;
    }

    .picker-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
        position: relative;
        cursor: pointer;
        transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
        height: max-content;
        padding: 10px 0;
    }

    .picker-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
    }

    .picker-card.selected {
        border-color: #6366f1;
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
    }

    .picker-card img {
        width: 100%;
        object-fit: cover;
        background: #e2e8f0;
    }

    .picker-card-body {
        padding: 10px 12px 14px;
    }

    .picker-card-name {
        font-size: 13px;
        font-weight: 600;
        color: #0f172a;
        height: 34px;
        overflow: hidden;
    }

    .picker-card-info {
        font-size: 12px;
        color: #64748b;
        margin: 6px 0;
    }

    .picker-card-price {
        font-size: 14px;
        font-weight: 600;
        color: #10b981;
    }

    .picker-overlay-check {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 2px solid #e2e8f0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #10b981;
    }

    .picker-card.selected .picker-overlay-check {
        border-color: #10b981;
        background: #10b981;
        color: #fff;
    }

    .picker-footer {
        border-top: 1px solid #e2e8f0;
        padding: 16px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
        border-bottom-left-radius: 16px;
        border-bottom-right-radius: 16px;
    }

    .picker-pagination {
        display: flex;
        gap: 8px;
    }

    .picker-pagination button {
        border: 1px solid #e2e8f0;
        background: #fff;
        border-radius: 8px;
        padding: 8px 14px;
        cursor: pointer;
    }

    .picker-selected-counter {
        font-weight: 600;
        color: #0f172a;
    }

    @media (max-width: 1024px) {
        .product-picker-body {
            grid-template-columns: 220px 1fr;
        }

        .picker-search input {
            width: 100%;
        }
    }

    @media (max-width: 768px) {
        .product-picker-body {
            grid-template-columns: 1fr;
        }

        .picker-sidebar {
            display: none;
        }
    }

</style>
@endpush

@section('content')
@php
$manualCategories = \App\Models\Category::where('is_active', true)
->orderBy('name')
->get();
@endphp
<div style="width:100%;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
        <div>
            <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-secondary">← Quay lại</a>
            <a href="{{ route('admin.flash-sales.edit', $flashSale) }}" class="btn btn-secondary">✏️ Sửa Flash Sale</a>
        </div>
        <div style="display:flex;gap:10px;">
            @if($flashSale->items()->count() > 0 && !$flashSale->isActive())
            <form action="{{ route('admin.flash-sales.items.delete-all', $flashSale) }}" method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa TOÀN BỘ {{ $flashSale->items()->count() }} sản phẩm? Sau khi xóa, bạn có thể chọn lại chế độ thêm sản phẩm (thủ công hoặc tự động từ danh mục).');">
                @csrf
                @method('DELETE')
                <input type="hidden" name="confirm" value="1">
                <button type="submit" class="btn btn-danger" title="Xóa toàn bộ sản phẩm để chuyển đổi chế độ">
                    🗑️ Xóa toàn bộ sản phẩm
                </button>
            </form>
            @endif
            @if($flashSale->product_add_mode === 'auto_by_category')
            <button onclick="openAddProductModal()" class="btn btn-primary">➕ Thêm sản phẩm từ danh mục</button>
            @else
            <button onclick="openAddProductModal()" class="btn btn-primary">➕ Thêm sản phẩm</button>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:20px;">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:20px;">
        {{ session('error') }}
    </div>
    @endif

    @if(session('import_errors'))
    <div class="alert alert-warning" style="margin-bottom:20px;">
        <strong>Có {{ count(session('import_errors')) }} dòng import gặp lỗi:</strong>
        <ul style="margin:8px 0 0 18px;">
            @foreach(session('import_errors') as $importError)
                <li>Hàng {{ $importError['row'] ?? '?' }}: {{ $importError['message'] ?? 'Lỗi không xác định' }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($flashSale->isLocked())
    <div class="alert alert-warning" style="margin-bottom:20px;">
        🔒 Flash Sale đang chạy - Không thể chỉnh sửa giá và số lượng. Chỉ có thể bật/tắt sản phẩm.
        <a href="{{ route('admin.flash-sales.preview', $flashSale) }}" class="btn btn-sm btn-secondary" style="margin-left:10px;" target="_blank">👁️ Xem Flash Sale</a>
        <a href="{{ route('admin.flash-sales.stats', $flashSale) }}" class="btn btn-sm btn-info" style="margin-left:10px;">📊 Xem thống kê</a>
    </div>
    @endif

    <!-- Filters -->
    <form class="filter-bar" method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
        <select name="filter" style="padding:8px 12px;border:1px solid #cbd5f5;border-radius:6px;">
            <option value="">-- Tất cả --</option>
            <option value="available" {{ request('filter') === 'available' ? 'selected' : '' }}>Còn hàng</option>
            <option value="sold_out" {{ request('filter') === 'sold_out' ? 'selected' : '' }}>Hết hàng</option>
            <option value="inactive" {{ request('filter') === 'inactive' ? 'selected' : '' }}>Đã tắt</option>
        </select>
        <input type="text" name="search" placeholder="Tìm theo tên hoặc SKU..." value="{{ request('search') }}" style="padding:8px 12px;border:1px solid #cbd5f5;border-radius:6px;flex:1;min-width:200px;">
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="{{ route('admin.flash-sales.items', $flashSale) }}" class="btn btn-secondary">Xóa bộ lọc</a>
    </form>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:20px;">
        <div style="background:#fff;padding:16px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.05);">
            <div style="font-size:12px;color:#64748b;">Tổng sản phẩm</div>
            <div style="font-size:24px;font-weight:bold;color:#0f172a;">{{ $pageStats['total_items'] ?? $items->total() }}</div>
        </div>
        <div style="background:#fff;padding:16px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.05);">
            <div style="font-size:12px;color:#64748b;">Tổng đã bán</div>
            <div style="font-size:24px;font-weight:bold;color:#0f172a;">{{ $pageStats['total_sold'] ?? 0 }}</div>
        </div>
        <div style="background:#fff;padding:16px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.05);">
            <div style="font-size:12px;color:#64748b;">Tổng còn lại</div>
            <div style="font-size:24px;font-weight:bold;color:#0f172a;">{{ $pageStats['total_remaining'] ?? 0 }}</div>
        </div>
    </div>

    <form id="bulkActionForm" method="POST" action="{{ route('admin.flash-sales.items.bulk-action', $flashSale) }}" data-running="{{ $flashSale->isActive() ? 'true' : 'false' }}">
        @csrf
        <input type="hidden" name="action" id="bulk_action_input">
    </form>

    <div class="bulk-actions" style="display:flex;gap:10px;align-items:center;margin-bottom:12px;flex-wrap:wrap;">
        <div style="font-weight:600;color:#475569;">Hành động hàng loạt:</div>
        <button type="button" class="btn btn-outline-success" onclick="submitBulkAction('activate')">Kích hoạt</button>
        <button type="button" class="btn btn-outline-warning" onclick="submitBulkAction('deactivate')">Tạm tắt</button>
        <button type="button" class="btn btn-outline-danger" onclick="submitBulkAction('delete')" {{ $flashSale->isActive() ? 'disabled' : '' }}>Xóa</button>
            <a href="{{ route('admin.flash-sales.stats', $flashSale) }}" class="btn btn-light">📊 Thống kê</a>
    </div>

    <!-- Items Table -->
    <div class="table-responsive">
        <table class="items-table">
                <thead>
                    <tr>
                        <th style="width:40px;">
                            <input type="checkbox" id="select_all_items">
                        </th>
                        <th style="width:80px;">Ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>SKU</th>
                        <th>Giá gốc</th>
                        <th>Giá Flash Sale</th>
                        <th>% Giảm</th>
                        <th>Stock</th>
                        <th>Đã bán</th>
                        <th>Còn lại</th>
                        <th>Max/User</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    <tr>
                        @php
                            $soldValue = $item->display_sold ?? $item->sold ?? 0;
                            $remainingValue = $item->display_remaining ?? max(0, ($item->stock ?? 0) - ($item->sold ?? 0));
                            $soldPercent = $item->display_sold_percentage ?? $item->sold_percentage ?? 0;
                        @endphp
                        <td>
                            <input type="checkbox" class="bulk-checkbox" name="item_ids[]" value="{{ $item->id }}" form="bulkActionForm">
                        </td>
                        <td>
                        @if($item->product && $item->product->primaryImage)
                        <img src="{{ asset('clients/assets/img/clothes/' . $item->product->primaryImage->url) }}" alt="{{ $item->product->name ?? 'N/A' }}" class="product-image">
                        @else
                        <div style="width:60px;height:60px;background:#e2e8f0;border-radius:6px;display:flex;align-items:center;justify-content:center;">
                            <small>No Image</small>
                        </div>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $item->product->name ?? 'Sản phẩm đã bị xóa' }}</strong>
                    </td>
                    <td>{{ $item->product->sku ?? 'N/A' }}</td>
                    <td>{{ number_format($item->original_price ?? ($item->product->price ?? 0)) }}₫</td>
                    <td>
                        @if($flashSale->isLocked())
                        <strong>{{ number_format($item->sale_price ?? 0, 0, ',', '.') }}₫</strong>
                        @else
                        <div class="price-input-group">
                            <input type="text"
                                   class="inline-edit"
                                   data-item-id="{{ $item->id }}"
                                   data-field="sale_price"
                                   data-format="price"
                                   data-original="{{ $item->original_price ?? 0 }}"
                                   value="{{ number_format($item->sale_price ?? 0, 0, '', '') }}"
                                   inputmode="decimal"
                                   pattern="[0-9,.]*"
                                   style="width:120px;">
                            <div class="price-suggest">
                                <small>Gợi ý:</small>
                                <button type="button" class="suggest-price-btn" data-item-id="{{ $item->id }}" data-percent="10">-10%</button>
                                <button type="button" class="suggest-price-btn" data-item-id="{{ $item->id }}" data-percent="20">-20%</button>
                                <button type="button" class="suggest-price-btn" data-item-id="{{ $item->id }}" data-percent="30">-30%</button>
                            </div>
                            <div class="price-actions">
                                <button type="button" class="btn btn-light btn-sm" onclick="openPriceLogModal({{ $item->id }})">
                                    🕒 Lịch sử giá
                                </button>
                            </div>
                        </div>
                        @endif
                    </td>
                    <td>
                        @php
                        $discount = $item->original_price > 0
                        ? round((($item->original_price - $item->sale_price) / $item->original_price) * 100, 1)
                        : 0;
                        @endphp
                        <strong style="color:#ef4444;">-{{ $discount }}%</strong>
                    </td>
                    <td>
                        @if($flashSale->isLocked())
                        {{ $item->stock }}
                        @else
                        <input type="number" class="inline-edit" data-item-id="{{ $item->id }}" data-field="stock" value="{{ $item->stock }}" min="0" style="width:80px;">
                        @endif
                    </td>
                    <td>
                        <strong>{{ $soldValue }}</strong>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width:{{ $soldPercent }}%"></div>
                        </div>
                    </td>
                    <td>
                        <strong style="color:{{ $remainingValue > 0 ? '#10b981' : '#ef4444' }};">
                            {{ $remainingValue }}
                        </strong>
                    </td>
                    <td>
                        @if($flashSale->isLocked())
                        {{ $item->max_per_user ?? '-' }}
                        @else
                        <input type="number" class="inline-edit" data-item-id="{{ $item->id }}" data-field="max_per_user" value="{{ $item->max_per_user }}" min="1" style="width:60px;">
                        @endif
                    </td>
                    <td>
                        @if($item->is_active)
                        <span class="badge badge-success">Hoạt động</span>
                        @else
                        <span class="badge badge-danger">Không hoạt động</span>
                        @endif
                        @if($remainingValue <= 0) <br><span class="badge badge-warning" style="margin-top:4px;">Hết hàng</span>
                            @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            @if(!$flashSale->isLocked())
                            <form action="{{ route('admin.flash-sales.items.update', [$flashSale, $item]) }}" method="POST" style="display:inline;" class="toggle-active-form">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="is_active" value="{{ $item->is_active ? 0 : 1 }}">
                                <button type="submit" class="btn btn-sm {{ $item->is_active ? 'btn-warning' : 'btn-success' }}" title="{{ $item->is_active ? 'Tắt' : 'Bật' }}">
                                    {{ $item->is_active ? '⏸️' : '▶️' }}
                                </button>
                            </form>
                            <form action="{{ route('admin.flash-sales.items.destroy', [$flashSale, $item]) }}" method="POST" style="display:inline;" onsubmit="return confirm('Xóa sản phẩm này khỏi Flash Sale?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Xóa">🗑️</button>
                            </form>
                            @else
                            <form action="{{ route('admin.flash-sales.items.update', [$flashSale, $item]) }}" method="POST" style="display:inline;" class="toggle-active-form">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="is_active" value="{{ $item->is_active ? 0 : 1 }}">
                                <button type="submit" class="btn btn-sm {{ $item->is_active ? 'btn-warning' : 'btn-success' }}" title="{{ $item->is_active ? 'Tắt' : 'Bật' }}">
                                    {{ $item->is_active ? '⏸️' : '▶️' }}
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                    @empty
                    <tr>
                        <td colspan="13" style="text-align:center;padding:40px;color:#94a3b8;">
                            Chưa có sản phẩm nào trong Flash Sale này
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <!-- Pagination -->
    <div class="pagination-wrapper">
        {{ $items->links() }}
    </div>
</div>

<!-- Modal thêm sản phẩm -->
<div id="addProductModal" class="modal">
    <div class="modal-content">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3>Thêm sản phẩm vào Flash Sale</h3>
            <button onclick="closeAddProductModal()" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
        </div>

        @include('admins.flash-sales.partials.import-form', ['flashSale' => $flashSale])

        @if($flashSale->items()->count() === 0)
        <!-- Chưa có sản phẩm - Hiển thị 2 tab để chọn -->
        <div class="tab-container">
            <div class="tabs">
                <button class="tab active" onclick="switchTab('manual')" id="tab-manual">
                    ✋ Thêm thủ công
                </button>
                <button class="tab" onclick="switchTab('auto')" id="tab-auto">
                    🎯 Tự động từ danh mục
                </button>
            </div>

            <!-- Tab 1: Thêm thủ công -->
            <div id="tab-content-manual" class="tab-content active">
                <form id="addProductForm" action="{{ route('admin.flash-sales.items.store', $flashSale) }}" method="POST">
                    @csrf
                    <input type="hidden" name="set_mode" value="manual">

                    <div class="form-group" style="margin-bottom:16px;">
                        <label>Chọn sản phẩm <span style="color:red;">*</span></label>
                        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                            <button type="button" class="btn btn-primary" onclick="openProductPickerModal()" style="flex:0 0 auto;">
                                📦 Chọn sản phẩm
                            </button>
                            <div id="selectedProductsSummary" style="flex:1;min-height:40px;background:#f8fafc;border:1px dashed #cbd5f5;border-radius:8px;padding:10px;color:#64748b;">
                                Chưa chọn sản phẩm nào
                            </div>
                        </div>
                        <div id="selectedProductInputs"></div>
                        <small style="color:#64748b;display:block;margin-top:4px;">
                            💡 Nhấn "Chọn sản phẩm" để mở popup, có thể lọc theo danh mục và chọn nhiều sản phẩm nhanh chóng.
                        </small>
                        <div class="suggested-products-trigger">
                            <button type="button" class="btn btn-light" onclick="loadSuggestedProducts()">
                                ⭐ Gợi ý sản phẩm bán chạy
                            </button>
                            <span>Tự động gợi ý tối đa 20 sản phẩm có doanh thu cao, chưa nằm trong Flash Sale.</span>
                        </div>
                        <div id="suggestedProductsWrapper" class="suggested-products-wrapper"></div>
                    </div>

                    <div class="form-group" style="margin-bottom:16px;">
                        <label for="default_sale_price_percent">% Giảm giá mặc định (áp dụng cho tất cả sản phẩm)</label>
                        <input type="number" id="default_sale_price_percent" name="default_sale_price_percent" class="form-control" min="0" max="90" step="1" placeholder="Ví dụ: 20 (giảm 20%)">
                        <small style="color:#64748b;display:block;margin-top:4px;">
                            Nếu không nhập, sẽ dùng giá gốc của sản phẩm (giảm 20% mặc định).
                        </small>
                    </div>

                    <div class="grid-2" style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:16px;">
                        <div class="form-group">
                            <label for="default_stock">Số lượng Flash Sale mặc định</label>
                            <input type="number" id="default_stock" name="default_stock" class="form-control" min="1" placeholder="Mặc định: min(stock, 100)">
                            <small style="color:#64748b;display:block;margin-top:4px;">
                                Áp dụng cho tất cả sản phẩm
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="default_max_per_user">Giới hạn mua mỗi khách mặc định</label>
                            <input type="number" id="default_max_per_user" name="default_max_per_user" class="form-control" min="1" value="1" placeholder="Mặc định: 1">
                            <small style="color:#64748b;display:block;margin-top:4px;">
                                Áp dụng cho tất cả sản phẩm
                            </small>
                        </div>
                    </div>

                    <div style="display:flex;gap:10px;margin-top:20px;">
                        <button type="submit" class="btn btn-primary">Thêm sản phẩm</button>
                        <button type="button" onclick="closeAddProductModal()" class="btn btn-secondary">Hủy</button>
                    </div>
                </form>
            </div>

            <!-- Tab 2: Thêm tự động từ danh mục -->
            <div id="tab-content-auto" class="tab-content">
                <form id="addProductByCategoriesForm" action="{{ route('admin.flash-sales.items.by-categories', $flashSale) }}" method="POST">
                    @csrf

                    <div class="form-group" style="margin-bottom:16px;">
                        <label>Chọn danh mục <span style="color:red;">*</span></label>
                            <select name="category_ids[]" id="categorySelect" multiple class="form-control" required>
                                @foreach($manualCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        <small style="color:#64748b;display:block;margin-top:4px;">
                            💡 Tìm kiếm và chọn nhiều danh mục. Mỗi danh mục sẽ tự động lấy 20 sản phẩm nổi bật.
                        </small>
                    </div>

                    <div class="form-group" style="margin-bottom:16px;">
                        <label for="default_sale_price_percent">% Giảm giá mặc định (áp dụng cho tất cả sản phẩm)</label>
                        <input type="number" id="default_sale_price_percent" name="default_sale_price_percent" class="form-control" min="0" max="90" step="1" placeholder="Ví dụ: 20 (giảm 20%)">
                        <small style="color:#64748b;display:block;margin-top:4px;">
                            Nếu không nhập, sẽ dùng giá gốc của sản phẩm.
                        </small>
                    </div>

                    <div style="display:flex;gap:10px;margin-top:20px;">
                        <button type="submit" class="btn btn-primary">Thêm sản phẩm từ danh mục</button>
                        <button type="button" onclick="closeAddProductModal()" class="btn btn-secondary">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
        @else
        <!-- Đã có sản phẩm - Chỉ hiển thị form theo mode hiện tại -->
        @if($flashSale->product_add_mode === 'auto_by_category')
        <!-- Form thêm tự động từ danh mục -->
        <form id="addProductByCategoriesForm" action="{{ route('admin.flash-sales.items.by-categories', $flashSale) }}" method="POST">
            @csrf

            <div class="form-group" style="margin-bottom:16px;">
                <label>Chọn danh mục <span style="color:red;">*</span></label>
                <select name="category_ids[]" id="categorySelectExisting" multiple class="form-control" required>
                    @foreach(($categories ?? $manualCategories) as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <small style="color:#64748b;display:block;margin-top:4px;">
                    💡 Tìm kiếm và chọn nhiều danh mục. Mỗi danh mục sẽ tự động lấy 20 sản phẩm nổi bật.
                </small>
            </div>

            <div class="form-group" style="margin-bottom:16px;">
                <label for="default_sale_price_percent">% Giảm giá mặc định (áp dụng cho tất cả sản phẩm)</label>
                <input type="number" id="default_sale_price_percent" name="default_sale_price_percent" class="form-control" min="0" max="90" step="1" placeholder="Ví dụ: 20 (giảm 20%)">
                <small style="color:#64748b;display:block;margin-top:4px;">
                    Nếu không nhập, sẽ dùng giá gốc của sản phẩm.
                </small>
            </div>

            <div style="display:flex;gap:10px;margin-top:20px;">
                <button type="submit" class="btn btn-primary">Thêm sản phẩm từ danh mục</button>
                <button type="button" onclick="closeAddProductModal()" class="btn btn-secondary">Hủy</button>
            </div>
        </form>
        @else
        <!-- Form thêm thủ công -->
        <form id="addProductForm" action="{{ route('admin.flash-sales.items.store', $flashSale) }}" method="POST">
            @csrf

            <div class="form-group" style="margin-bottom:16px;">
                <label>Chọn sản phẩm <span style="color:red;">*</span></label>
                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <button type="button" class="btn btn-primary" onclick="openProductPickerModal()" style="flex:0 0 auto;">
                        📦 Chọn sản phẩm
                    </button>
                    <div id="selectedProductsSummary" style="flex:1;min-height:40px;background:#f8fafc;border:1px dashed #cbd5f5;border-radius:8px;padding:10px;color:#64748b;">
                        Chưa chọn sản phẩm nào
                    </div>
                </div>
                <div id="selectedProductInputs"></div>
                <small style="color:#64748b;display:block;margin-top:4px;">
                    💡 Nhấn "Chọn sản phẩm" để mở popup, có thể lọc theo danh mục và chọn nhiều sản phẩm nhanh chóng.
                </small>
                <div class="suggested-products-trigger">
                    <button type="button" class="btn btn-light" onclick="loadSuggestedProducts()">
                        ⭐ Gợi ý sản phẩm bán chạy
                    </button>
                    <span>Tự động gợi ý tối đa 20 sản phẩm có doanh thu cao, chưa nằm trong Flash Sale.</span>
                </div>
                <div id="suggestedProductsWrapper" class="suggested-products-wrapper"></div>
            </div>

            <div class="form-group" style="margin-bottom:16px;">
                <label for="default_sale_price_percent">% Giảm giá mặc định (áp dụng cho tất cả sản phẩm)</label>
                <input type="number" id="default_sale_price_percent" name="default_sale_price_percent" class="form-control" min="0" max="90" step="1" placeholder="Ví dụ: 20 (giảm 20%)">
                <small style="color:#64748b;display:block;margin-top:4px;">
                    Nếu không nhập, sẽ dùng giá gốc của sản phẩm (giảm 20% mặc định).
                </small>
            </div>

            <div class="grid-2" style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:16px;">
                <div class="form-group">
                    <label for="default_stock">Số lượng Flash Sale mặc định</label>
                    <input type="number" id="default_stock" name="default_stock" class="form-control" min="1" placeholder="Mặc định: min(stock, 100)">
                    <small style="color:#64748b;display:block;margin-top:4px;">
                        Áp dụng cho tất cả sản phẩm
                    </small>
                </div>

                <div class="form-group">
                    <label for="default_max_per_user">Giới hạn mua mỗi khách mặc định</label>
                    <input type="number" id="default_max_per_user" name="default_max_per_user" class="form-control" min="1" value="1" placeholder="Mặc định: 1">
                    <small style="color:#64748b;display:block;margin-top:4px;">
                        Áp dụng cho tất cả sản phẩm
                    </small>
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:20px;">
                <button type="submit" class="btn btn-primary">Thêm sản phẩm</button>
                <button type="button" onclick="closeAddProductModal()" class="btn btn-secondary">Hủy</button>
            </div>
        </form>
        @endif
        @endif
    </div>
</div>

<!-- Popup chọn sản phẩm nhanh -->
<div id="productPickerModal" class="product-picker-modal">
    <div class="product-picker-content">
        <div class="product-picker-header">
            <div>
                <h3 style="margin:0;">📦 Chọn sản phẩm cho Flash Sale</h3>
                <p style="margin:4px 0 0;color:#64748b;font-size:13px;">Lọc theo danh mục, tìm kiếm và chọn nhiều sản phẩm cùng lúc</p>
            </div>
            <button onclick="closeProductPickerModal()" style="border:none;background:none;font-size:28px;cursor:pointer;color:#475569;">&times;</button>
        </div>
        <div class="product-picker-body">
            <div class="picker-sidebar">
                <h4>Danh mục</h4>
                <button class="category-btn active" data-category="all" onclick="changePickerCategory('all', this)">
                    Tất cả
                </button>
                @foreach($manualCategories as $category)
                <button class="category-btn" data-category="{{ $category->id }}" onclick="changePickerCategory('{{ $category->id }}', this)">
                    <span>{{ $category->name }}</span>
                </button>
                @endforeach
            </div>
            <div class="picker-main">
                <div class="picker-filters">
                    <div class="picker-search">
                        <input type="text" id="productPickerSearch" placeholder="Tìm theo tên hoặc SKU..." onkeypress="if(event.key==='Enter'){ loadPickerProducts(1); }">
                    </div>
                    <button class="btn btn-secondary" onclick="loadPickerProducts(1)">🔍 Tìm kiếm</button>
                    <button class="btn btn-light" onclick="clearPickerSearch()">✖️ Xóa tìm</button>
                </div>
                <div id="productPickerGrid" class="products-grid-picker">
                    <div style="grid-column:1/-1;text-align:center;color:#94a3b8;padding:40px;">
                        Chọn danh mục hoặc tìm kiếm để hiển thị sản phẩm
                    </div>
                </div>
            </div>
        </div>
        <div class="picker-footer">
            <div class="picker-selected-counter">
                Đã chọn: <span id="pickerSelectedCount">0</span> sản phẩm
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <div class="picker-pagination" id="pickerPagination"></div>
                <button class="btn btn-secondary" onclick="closeProductPickerModal()">Hủy</button>
                <button class="btn btn-primary" onclick="applySelectedProducts()">✓ Thêm sản phẩm đã chọn</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal lịch sử giá -->
<div id="priceLogModal" class="modal">
    <div class="modal-content price-log-modal">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <h3 style="margin:0;font-size:18px;">🕒 Lịch sử thay đổi giá</h3>
            <button onclick="closePriceLogModal()" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
        </div>
        <div id="priceLogsContent" class="price-logs-list">
            <div class="suggested-products-loading">Đang tải dữ liệu...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    // TomSelect instances
    let categoryTomSelect = null;
    let categoryTomSelectExisting = null;

    // Bulk action helpers
    const bulkForm = document.getElementById('bulkActionForm');
    const bulkActionInput = document.getElementById('bulk_action_input');
    const selectAllCheckbox = document.getElementById('select_all_items');

    function getBulkCheckboxes() {
        return Array.from(document.querySelectorAll('.bulk-checkbox'));
    }

    function updateSelectAllState() {
        if (!selectAllCheckbox) return;
        const checkboxes = getBulkCheckboxes();
        const allChecked = checkboxes.length > 0 && checkboxes.every(cb => cb.checked);
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = !allChecked && checkboxes.some(cb => cb.checked);
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            getBulkCheckboxes().forEach(cb => cb.checked = selectAllCheckbox.checked);
        });
    }

    document.addEventListener('change', function (event) {
        if (event.target.classList && event.target.classList.contains('bulk-checkbox')) {
            updateSelectAllState();
        }
    });

    updateSelectAllState();

    window.submitBulkAction = function (action) {
        if (!bulkForm || !bulkActionInput) {
            alert('Không tìm thấy form hành động.');
            return;
        }

        const checked = getBulkCheckboxes().filter(cb => cb.checked);
        if (checked.length === 0) {
            alert('Vui lòng chọn ít nhất 1 sản phẩm để thực hiện.');
            return;
        }

        if (action === 'delete') {
            if (bulkForm.dataset.running === 'true') {
                alert('Flash Sale đang chạy, không thể xóa sản phẩm.');
                return;
            }
            if (!confirm('Xóa các sản phẩm đã chọn khỏi Flash Sale?')) {
                return;
            }
        }

        bulkActionInput.value = action;
        bulkForm.submit();
    };

    // Price suggestion buttons
    document.querySelectorAll('.suggest-price-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const percent = parseFloat(this.dataset.percent || '0');
            const itemId = this.dataset.itemId;
            const input = document.querySelector(`input.inline-edit[data-field="sale_price"][data-item-id="${itemId}"]`);

            if (!input) {
                return;
            }

            const original = parseFloat(input.dataset.original || '0');
            if (!original || original <= 0) {
                alert('Không xác định được giá gốc.');
                return;
            }

            let suggested = original * (100 - percent) / 100;
            suggested = Math.max(0, Math.round(suggested / 1000) * 1000);

            input.value = suggested.toString();
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    // Initialize TomSelect for category selection
    function initCategoryTomSelect() {
        const categorySelect = document.getElementById('categorySelect');
        if (!categorySelect || categoryTomSelect) return;

        categoryTomSelect = new TomSelect('#categorySelect', {
            plugins: ['remove_button'],
            maxItems: null,
            placeholder: 'Tìm kiếm và chọn danh mục...',
            create: false,
        });
    }

    // Initialize TomSelect for category selection (existing form)
    function initCategoryTomSelectExisting() {
        const categorySelect = document.getElementById('categorySelectExisting');
        if (!categorySelect || categoryTomSelectExisting) return;

        categoryTomSelectExisting = new TomSelect('#categorySelectExisting', {
            plugins: ['remove_button'],
            maxItems: null,
            placeholder: 'Tìm kiếm và chọn danh mục...',
            create: false,
        });
    }

    // Destroy category TomSelect
    function destroyCategoryTomSelect() {
        if (categoryTomSelect) {
            categoryTomSelect.destroy();
            categoryTomSelect = null;
        }
        if (categoryTomSelectExisting) {
            categoryTomSelectExisting.destroy();
            categoryTomSelectExisting = null;
        }
    }

    function normalizePriceValue(raw) {
        if (!raw) return '';
        let value = raw.toString().trim();
        value = value.replace(/\s+/g, '');
        value = value.replace(/[^\d,\.]/g, '');
        if (value.indexOf(',') > -1 && value.indexOf('.') > -1) {
            if (value.lastIndexOf(',') > value.lastIndexOf('.')) {
                value = value.replace(/\./g, '').replace(',', '.');
            } else {
                value = value.replace(/,/g, '');
            }
        } else if (value.indexOf(',') > -1) {
            value = value.replace(',', '.');
        }
        value = value.replace(/(?!^)\./g, '');
        const parsed = parseFloat(value);
        if (isNaN(parsed)) {
            return '';
        }
        return parsed.toFixed(2);
    }

    // Inline edit
    document.querySelectorAll('.inline-edit').forEach(input => {
        let timeout;
        // Chặn Enter, chỉ dùng change để cập nhật
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur();
            }
        });

        input.addEventListener('change', function() {
            clearTimeout(timeout);
            const itemId = this.dataset.itemId;
            const field = this.dataset.field;
            let value = this.value;
            const format = this.dataset.format;

            if (format === 'price') {
                value = normalizePriceValue(value);
                if (!value) {
                    alert('Giá không hợp lệ, vui lòng nhập số.');
                    this.focus();
                    return;
                }
            }

            timeout = setTimeout(() => {
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                formData.append('_method', 'PUT');
                formData.append(field, value);

                fetch(`{{ route('admin.flash-sales.items.update', [$flashSale, 'ITEM_ID']) }}`.replace('ITEM_ID', itemId), {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            const alert = document.createElement('div');
                            alert.className = 'alert alert-success';
                            alert.textContent = data.message || 'Cập nhật thành công';
                            alert.style.position = 'fixed';
                            alert.style.top = '20px';
                            alert.style.right = '20px';
                            alert.style.zIndex = '9999';
                            alert.style.padding = '12px 16px';
                            alert.style.borderRadius = '6px';
                            document.body.appendChild(alert);
                            setTimeout(() => alert.remove(), 3000);
                        } else {
                            alert('Lỗi: ' + (data.message || 'Không thể cập nhật'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Có lỗi xảy ra khi cập nhật');
                    });
            }, 1000);
        });
    });

    // Modal functions
    window.openAddProductModal = function () {
        document.getElementById('addProductModal').style.display = 'block';
        setTimeout(() => {
            const autoTab = document.getElementById('tab-content-auto');
            if (autoTab && autoTab.classList.contains('active')) {
                initCategoryTomSelect();
            }
        }, 100);
    };

    window.closeAddProductModal = function () {
        document.getElementById('addProductModal').style.display = 'none';
        const addProductForm = document.getElementById('addProductForm');
        if (addProductForm) {
            addProductForm.reset();
        }
        destroyCategoryTomSelect();
    };

    // Switch tab function
    window.switchTab = function (tab) {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

        const tabButton = document.getElementById('tab-' + tab);
        const tabContent = document.getElementById('tab-content-' + tab);
        if (tabButton) tabButton.classList.add('active');
        if (tabContent) tabContent.classList.add('active');

        setTimeout(() => {
            if (tab === 'auto') {
                destroyCategoryTomSelect();
                initCategoryTomSelect();
            } else {
                destroyCategoryTomSelect();
            }
        }, 100);
    };

    window.onclick = function(event) {
        const modal = document.getElementById('addProductModal');
        const pickerModal = document.getElementById('productPickerModal');
        if (event.target === modal) {
            closeAddProductModal();
        }
        if (event.target === pickerModal) {
            closeProductPickerModal();
        }
        if (priceLogModal && event.target === priceLogModal) {
            closePriceLogModal();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const categorySelectExisting = document.getElementById('categorySelectExisting');
        if (categorySelectExisting) {
            setTimeout(() => {
                initCategoryTomSelectExisting();
            }, 200);
        }
    });

    /**
     * Product picker logic
     */
    let pickerCurrentCategory = 'all';
    let pickerCurrentPage = 1;
    let pickerLastPage = 1;
    const selectedProductsMap = new Map();
    const pickerGrid = document.getElementById('productPickerGrid');
    const pickerPagination = document.getElementById('pickerPagination');
    const pickerSelectedCount = document.getElementById('pickerSelectedCount');
    const suggestedWrapper = document.getElementById('suggestedProductsWrapper');
    const priceLogModal = document.getElementById('priceLogModal');
    const priceLogsContent = document.getElementById('priceLogsContent');

    const suggestedProductsCache = new Map();
    let suggestedProductsData = [];
    let suggestedLoading = false;

    window.openProductPickerModal = function () {
        document.getElementById('productPickerModal').style.display = 'block';
        pickerSelectedCount.textContent = selectedProductsMap.size;
        pickerGrid.innerHTML = `
                <div style="grid-column:1/-1;text-align:center;color:#94a3b8;padding:40px;">
                    Đang tải sản phẩm...
                </div>
            `;
        loadPickerProducts(1);
    };

    window.closeProductPickerModal = function () {
        document.getElementById('productPickerModal').style.display = 'none';
    };

    window.changePickerCategory = function (categoryId, button) {
        pickerCurrentCategory = categoryId;
        document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
        if (button) {
            button.classList.add('active');
        }
        loadPickerProducts(1);
    };

    window.clearPickerSearch = function () {
        const searchInput = document.getElementById('productPickerSearch');
        if (searchInput) {
            searchInput.value = '';
        }
        loadPickerProducts(1);
    };

    function loadPickerProducts(page = 1) {
        pickerCurrentPage = page;
        const searchInput = document.getElementById('productPickerSearch');
        const searchQuery = searchInput ? searchInput.value.trim() : '';

        const params = new URLSearchParams();
        params.append('flash_sale_id', '{{ $flashSale->id }}');
        params.append('page', page);
        params.append('per_page', 20);
        if (pickerCurrentCategory !== 'all') {
            params.append('category_id', pickerCurrentCategory);
        }
        if (searchQuery.length > 0) {
            params.append('q', searchQuery);
        }

        fetch(`{{ route('admin.flash-sales.products.by-category') }}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Không thể tải sản phẩm');
                }
                return response.json();
            })
            .then(data => {
                renderPickerProducts(data.data || []);
                renderPickerPagination(data.meta || {});
            })
            .catch(error => {
                console.error(error);
                pickerGrid.innerHTML = `
                        <div style="grid-column:1/-1;text-align:center;color:#b91c1c;padding:40px;">
                            Không thể tải sản phẩm. Vui lòng thử lại.
                        </div>
                    `;
            });
    }

    const fallbackProductImage = @json(asset('clients/assets/img/clothes/no-image.webp'));

    function renderPickerProducts(products) {
        if (!products.length) {
            pickerGrid.innerHTML = `
                    <div style="grid-column:1/-1;text-align:center;color:#94a3b8;padding:40px;">
                        Không có sản phẩm nào phù hợp
                    </div>
                `;
            return;
        }

        pickerGrid.innerHTML = products.map(product => {
            const isSelected = selectedProductsMap.has(String(product.id));
            const img = product.image || fallbackProductImage;
            return `
                    <div class="picker-card ${isSelected ? 'selected' : ''}" onclick="toggleProductSelection(${product.id})" id="picker-card-${product.id}">
                        <div class="picker-overlay-check">${isSelected ? '✓' : ''}</div>
                        <img src="${img}" alt="${product.name}">
                        <div class="picker-card-body">
                            <div class="picker-card-name">${product.name}</div>
                            <div class="picker-card-info">SKU: ${product.sku || 'N/A'}</div>
                            <div class="picker-card-info">Tồn kho: ${product.stock_quantity ?? 0}</div>
                            <div class="picker-card-price">${new Intl.NumberFormat('vi-VN').format(product.price ?? 0)}₫</div>
                        </div>
                    </div>
                `;
        }).join('');

        // cache products for access when toggle
        products.forEach(product => {
            productCache.set(String(product.id), product);
        });
    }

    function renderPickerPagination(meta) {
        pickerLastPage = meta.last_page || 1;
        const currentPage = meta.current_page || 1;

        if (pickerLastPage <= 1) {
            pickerPagination.innerHTML = '';
            return;
        }

        let html = '';
        html += `<button ${currentPage <= 1 ? 'disabled' : ''} onclick="loadPickerProducts(${currentPage - 1})">←</button>`;
        html += `<span style="padding:8px 12px;font-weight:600;">${currentPage}/${pickerLastPage}</span>`;
        html += `<button ${currentPage >= pickerLastPage ? 'disabled' : ''} onclick="loadPickerProducts(${currentPage + 1})">→</button>`;
        pickerPagination.innerHTML = html;
    }

    function toggleProductSelection(productId) {
        const id = String(productId);
        const card = document.getElementById(`picker-card-${id}`);
        if (selectedProductsMap.has(id) && selectedProductsMap.get(id)) {
            selectedProductsMap.delete(id);
            if (card) {
                card.classList.remove('selected');
                const overlay = card.querySelector('.picker-overlay-check');
                if (overlay) overlay.textContent = '';
            }
        } else {
            const productData = productCache.get(id);
            if (!productData) return;
            selectedProductsMap.set(id, productData);
            if (card) {
                card.classList.add('selected');
                const overlay = card.querySelector('.picker-overlay-check');
                if (overlay) overlay.textContent = '✓';
            }
        }
        pickerSelectedCount.textContent = selectedProductsMap.size;
    }

    const productCache = new Map();

    function syncSelectedProductsToForm() {
        const inputsContainer = document.getElementById('selectedProductInputs');
        const summary = document.getElementById('selectedProductsSummary');

        if (!inputsContainer || !summary) {
            return;
        }

        inputsContainer.innerHTML = '';
        const fragments = [];
        selectedProductsMap.forEach((product, id) => {
            if (!product) return;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'product_ids[]';
            input.value = id;
            inputsContainer.appendChild(input);
            fragments.push(`<span class="selected-product-chip">${product.name}<button type="button" onclick="removeSelectedProduct('${id}')">&times;</button></span>`);
        });

        summary.innerHTML = fragments.join('') || 'Chưa chọn sản phẩm nào';
        pickerSelectedCount.textContent = selectedProductsMap.size;
    }

    window.removeSelectedProduct = function (productId) {
        const id = String(productId);
        if (selectedProductsMap.has(id)) {
            selectedProductsMap.delete(id);
            syncSelectedProductsToForm();
            renderSuggestedProducts();
        }
    };

    window.applySelectedProducts = function () {
        if (selectedProductsMap.size === 0) {
            alert('Vui lòng chọn ít nhất 1 sản phẩm');
            return;
        }
        syncSelectedProductsToForm();
        closeProductPickerModal();
        renderSuggestedProducts();
    };

    window.openPriceLogModal = function (itemId) {
        if (!priceLogModal || !priceLogsContent) {
            alert('Không thể mở lịch sử giá.');
            return;
        }

        priceLogModal.style.display = 'block';
        priceLogsContent.innerHTML = `<div class="suggested-products-loading">Đang tải dữ liệu...</div>`;

        fetch(`{{ route('admin.flash-sales.items.price-logs', [$flashSale, 'ITEM_ID']) }}`.replace('ITEM_ID', itemId), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Không thể tải lịch sử giá');
                }
                return response.json();
            })
            .then(data => renderPriceLogs(data.data || []))
            .catch(error => {
                console.error(error);
                priceLogsContent.innerHTML = `<div class="suggested-products-loading" style="color:#b91c1c;">Không thể tải dữ liệu. Vui lòng thử lại.</div>`;
            });
    };

    window.closePriceLogModal = function () {
        if (priceLogModal) {
            priceLogModal.style.display = 'none';
        }
    };

    function renderPriceLogs(logs) {
        if (!priceLogsContent) {
            return;
        }

        if (!logs.length) {
            priceLogsContent.innerHTML = `<div class="suggested-products-loading">Chưa có lịch sử thay đổi giá nào.</div>`;
            return;
        }

        priceLogsContent.innerHTML = logs.map(log => {
            const oldPrice = new Intl.NumberFormat('vi-VN').format(log.old_price ?? 0);
            const newPrice = new Intl.NumberFormat('vi-VN').format(log.new_price ?? 0);
            return `
                <div class="price-log-item">
                    <h5>${oldPrice}₫ → <span style="color:#16a34a;">${newPrice}₫</span></h5>
                    <div class="price-log-meta">
                        <span>Người sửa: ${log.changed_by || 'Không rõ'}</span>
                        <span>Thời gian: ${log.changed_at || '-'}</span>
                    </div>
                    ${log.reason ? `<div style="margin-top:6px;font-size:13px;color:#475569;">Lý do: ${log.reason}</div>` : ''}
                </div>
            `;
        }).join('');
    }

    window.loadSuggestedProducts = function (forceReload = false) {
        if (!suggestedWrapper) {
            alert('Vui lòng mở tab "Thêm thủ công" để sử dụng gợi ý.');
            return;
        }

        suggestedWrapper.style.display = 'flex';

        if (!forceReload && suggestedProductsData.length > 0 && !suggestedLoading) {
            renderSuggestedProducts();
            return;
        }

        suggestedLoading = true;
        suggestedWrapper.innerHTML = `<div class="suggested-products-loading">Đang tải gợi ý...</div>`;

        fetch(`{{ route('admin.flash-sales.suggest-products', $flashSale) }}?limit=20`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Không thể tải gợi ý sản phẩm');
                }
                return response.json();
            })
            .then(data => {
                suggestedProductsData = data.data || [];
                suggestedProductsCache.clear();
                suggestedProductsData.forEach(product => {
                    const id = String(product.id);
                    suggestedProductsCache.set(id, product);
                    productCache.set(id, product);
                });
                renderSuggestedProducts();
            })
            .catch(error => {
                console.error(error);
                suggestedWrapper.innerHTML = `<div class="suggested-products-loading" style="color:#b91c1c;">Không thể tải gợi ý. Vui lòng thử lại.</div>`;
            })
            .finally(() => {
                suggestedLoading = false;
            });
    };

    window.refreshSuggestedProducts = function () {
        loadSuggestedProducts(true);
    };

    window.addSuggestedProduct = function (productId) {
        const id = String(productId);
        const productData = suggestedProductsCache.get(id) || productCache.get(id);
        if (!productData) {
            alert('Không tìm thấy dữ liệu sản phẩm để thêm.');
            return;
        }
        selectedProductsMap.set(id, productData);
        syncSelectedProductsToForm();
        renderSuggestedProducts();
    };

    function renderSuggestedProducts() {
        if (!suggestedWrapper) {
            return;
        }

        if (!suggestedProductsData.length) {
            suggestedWrapper.innerHTML = `<div class="suggested-products-loading">Hiện chưa có dữ liệu để gợi ý. Hãy thử lại sau khi có đơn hàng.</div>`;
            return;
        }

        const cards = suggestedProductsData.map(product => {
            const id = String(product.id);
            const isSelected = selectedProductsMap.has(id);
            const price = new Intl.NumberFormat('vi-VN').format(product.price ?? 0);

            return `
                <div class="suggested-card">
                    <img src="${product.image || fallbackProductImage}" alt="${product.name}">
                    <h5>${product.name}</h5>
                    <div class="meta">SKU: ${product.sku || 'N/A'}</div>
                    <div class="meta">Danh mục: ${product.category || 'Chưa phân loại'}</div>
                    <div class="meta">Đã bán: <strong>${product.total_sold ?? 0}</strong> | Tồn: ${product.stock_quantity ?? 0}</div>
                    <div class="meta">Giá hiện tại: ${price}₫</div>
                    <div class="actions">
                        <span class="badge">${product.category || 'N/A'}</span>
                        <button type="button" class="btn ${isSelected ? 'btn-success' : 'btn-primary'} btn-sm" ${isSelected ? 'disabled' : ''} onclick="addSuggestedProduct(${product.id})">
                            ${isSelected ? '✓ Đã chọn' : '➕ Thêm'}
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        suggestedWrapper.innerHTML = `
            <div class="suggested-products-header">
                <strong>Top sản phẩm bán chạy</strong>
                <div style="display:flex;gap:8px;">
                    <button type="button" class="btn btn-light btn-sm" onclick="refreshSuggestedProducts()">↻ Làm mới</button>
                </div>
            </div>
            <div class="suggested-products-grid">
                ${cards}
            </div>
        `;
    }
</script>
@endpush
