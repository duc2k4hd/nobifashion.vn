@extends('admins.layouts.master')

@section('title', 'Quản lý sản phẩm')
@section('page-title', '📦 Sản phẩm')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/products-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .product-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .product-table th, .product-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #eef2f7;
            text-align: left;
        }
        .product-table th {
            background: #f8fafc;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
        }
        .product-table tr:hover td {
            background: #f1f5f9;
        }
        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .filter-bar input,
        .filter-bar select {
            padding: 8px 12px;
            border: 1px solid #cbd5f5;
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
        .actions {
            display: flex;
            gap: 8px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('select-all-products');
            const checkboxes = document.querySelectorAll('.product-checkbox');
            const form = document.getElementById('bulk-action-form');

            if (!selectAll || !form) {
                return;
            }

            selectAll.addEventListener('change', () => {
                checkboxes.forEach(cb => {
                    cb.checked = selectAll.checked;
                });
            });

            form.addEventListener('submit', (e) => {
                const hasSelected = Array.from(checkboxes).some(cb => cb.checked);
                if (!hasSelected) {
                    e.preventDefault();
                    alert('Vui lòng chọn ít nhất một sản phẩm trước khi thực hiện hành động.');
                }
            });
        });
    </script>
@endpush

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/products-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h2 style="margin:0;">Danh sách sản phẩm</h2>
            <div style="display:flex;gap:10px;">
                <a href="{{ route('admin.products.import-excel') }}" class="btn btn-secondary">📥 Import Excel</a>
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">➕ Thêm sản phẩm</a>
            </div>
        </div>

        <form class="filter-bar" method="GET">
            <input type="text" name="keyword" placeholder="Tìm SKU hoặc tên..."
                   value="{{ request('keyword') }}">
            <select name="status">
                <option value="">-- Trạng thái --</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Đang bán</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Tạm ẩn</option>
            </select>
            <button type="submit" class="btn btn-primary">Lọc</button>
        </form>

        <div class="table-responsive">
            <table class="product-table">
                <thead>
                <tr>
                    <th style="width:40px;">
                        <input type="checkbox" id="select-all-products">
                    </th>
                    <th>SKU</th>
                    <th>Tên</th>
                    <th>Danh mục</th>
                    <th>Giá</th>
                    <th>Stock</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            <input type="checkbox" name="selected[]" value="{{ $product->id }}" class="product-checkbox" form="bulk-action-form">
                        </td>
                        <td>{{ $product->sku }}</td>
                        <td>
                            <strong>{{ $product->name }}</strong><br>
                            <small>Slug: {{ $product->slug }}</small>
                        </td>
                        <td>{{ $product->primaryCategory->name ?? '-' }}</td>
                        <td>{{ number_format($product->price) }}₫</td>
                        <td>{{ $product->stock_quantity }}</td>
                        <td>
                            @if($product->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-secondary">✏️</a>
                                @if($product->is_active)
                                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                                           onsubmit="return confirm('Chuyển sản phẩm này sang trạng thái TẠM ẨN?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-primary" style="background:#ef4444;border:none;">Ẩn</button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.products.restore', $product) }}" method="POST"
                                           onsubmit="return confirm('Khôi phục sản phẩm này về trạng thái tạm ẩn?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-secondary">Khôi phục</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;">Chưa có sản phẩm nào</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <form action="{{ route('admin.products.bulk-action') }}" method="POST" id="bulk-action-form" style="margin-top:10px; display:flex; gap:10px;">
            @csrf
            <button type="submit" class="btn btn-secondary" name="bulk_action" value="hide">Ẩn các sản phẩm đã chọn</button>
            <button type="submit" class="btn btn-danger" name="bulk_action" value="delete">Xóa mềm các sản phẩm đã chọn</button>
        </form>

        <div style="margin-top:20px;">
            {{ $products->links() }}
        </div>
    </div>
@endsection

