@extends('admins.layouts.master')

@section('title', 'Quản lý hãng')
@section('page-title', 'Hãng sản phẩm')

@push('styles')
    <style>
        .brand-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .brand-table th,
        .brand-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #eef2f7;
            text-align: left;
            font-size: 13px;
            vertical-align: top;
        }

        .brand-table th {
            background: #f8fafc;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
        }

        .brand-table tr:hover td {
            background: #f8fafc;
        }

        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .filter-bar input,
        .filter-bar select {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            min-height: 42px;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        .actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .brand-logo {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
    </style>
@endpush

@section('content')
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:16px;flex-wrap:wrap;">
            <div>
                <h2 style="margin:0 0 4px;">Danh sách hãng</h2>
                <p style="margin:0;color:#64748b;">Quản lý hãng thật cho sản phẩm, tách riêng khỏi tag để dữ liệu đồng bộ và lọc chính xác hơn.</p>
            </div>
            <a href="{{ route('admin.brands.create') }}" class="btn btn-primary">Thêm hãng</a>
        </div>

        <form class="filter-bar" method="GET" action="{{ route('admin.brands.index') }}">
            <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Tìm theo tên hoặc slug...">
            <select name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Hiển thị</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Tạm ẩn</option>
            </select>
            <button type="submit" class="btn btn-primary">Lọc</button>
            <a href="{{ route('admin.brands.index') }}" class="btn btn-outline-secondary">Đặt lại</a>
        </form>

        <div class="table-responsive">
            <table class="brand-table">
                <thead>
                    <tr>
                        <th style="width:40px;">
                            <input type="checkbox" id="select-all-brands">
                        </th>
                        <th>Logo</th>
                        <th>Tên hãng</th>
                        <th>Slug</th>
                        <th>Sản phẩm</th>
                        <th>Thứ tự</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($brands as $brand)
                        <tr>
                            <td>
                                <input type="checkbox" name="selected[]" value="{{ $brand->id }}" class="brand-checkbox" form="brand-bulk-form">
                            </td>
                            <td>
                                @if($brand->logo)
                                    <img src="{{ asset(trim((string) config('media.directories.brands', 'clients/assets/img/brands'), '/') . '/' . $brand->logo) }}" alt="{{ $brand->name }}" class="brand-logo">
                                @else
                                    <div class="brand-logo" style="display:flex;align-items:center;justify-content:center;color:#94a3b8;font-weight:700;">
                                        {{ strtoupper(mb_substr($brand->name, 0, 1)) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $brand->name }}</strong>
                                @if($brand->website)
                                    <div style="margin-top:4px;">
                                        <a href="{{ $brand->website }}" target="_blank" rel="noopener noreferrer">{{ $brand->website }}</a>
                                    </div>
                                @endif
                            </td>
                            <td>{{ $brand->slug }}</td>
                            <td>
                                {{ number_format($brand->products_count) }}
                                @if($brand->products_count > 0)
                                    <div style="margin-top:4px;color:#64748b;font-size:12px;">Xóa hãng sẽ tự bỏ gán khỏi sản phẩm.</div>
                                @endif
                            </td>
                            <td>{{ number_format($brand->sort_order) }}</td>
                            <td>
                                @if($brand->is_active)
                                    <span class="badge badge-success">Hiển thị</span>
                                @else
                                    <span class="badge badge-danger">Tạm ẩn</span>
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('admin.brands.edit', $brand) }}" class="btn btn-secondary btn-sm">Sửa</a>
                                    <form action="{{ route('admin.brands.toggle', $brand) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-primary btn-sm" style="background:#0ea5e9;border:none;">
                                            {{ $brand->is_active ? 'Ẩn' : 'Hiển thị' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.brands.destroy', $brand) }}" method="POST" onsubmit="return confirm('Xóa hãng này? Các sản phẩm đang gắn hãng sẽ được tự động bỏ gán.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:32px;color:#94a3b8;">Chưa có hãng nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form id="brand-bulk-form" action="{{ route('admin.brands.bulk-action') }}" method="POST" style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm" name="bulk_action" value="hide">Ẩn các hãng đã chọn</button>
            <button type="submit" class="btn btn-primary btn-sm" name="bulk_action" value="show">Hiển thị các hãng đã chọn</button>
            <button type="submit" class="btn btn-danger btn-sm" name="bulk_action" value="delete" onclick="return confirm('Xóa các hãng đã chọn? Sản phẩm đang gắn hãng sẽ được tự động bỏ gán.')">Xóa các hãng đã chọn</button>
        </form>

        <div style="margin-top:16px;">
            {{ $brands->links() }}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('select-all-brands');
            const checkboxes = document.querySelectorAll('.brand-checkbox');
            const form = document.getElementById('brand-bulk-form');

            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = selectAll.checked;
                    });
                });
            }

            if (form) {
                form.addEventListener('submit', (event) => {
                    const hasSelected = Array.from(checkboxes).some((checkbox) => checkbox.checked);
                    if (!hasSelected) {
                        event.preventDefault();
                        alert('Vui lòng chọn ít nhất một hãng.');
                    }
                });
            }
        });
    </script>
@endpush
