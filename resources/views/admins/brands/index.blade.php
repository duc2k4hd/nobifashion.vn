@extends('admins.layouts.master')

@section('title', 'Quản lý thương hiệu')
@section('page-title', 'Thương hiệu sản phẩm')

@push('styles')
    <style>
        .brand-stat-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(15, 23, 42, 0.03);
            border: 1px solid #f1f5f9;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .brand-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        }

        .stat-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .brand-main-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #edf2f7;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 6px 16px rgba(15, 23, 42, 0.03);
            overflow: hidden;
            margin-top: 20px;
        }

        .brand-card-header {
            padding: 18px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            min-width: 260px;
        }

        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
        }

        .search-box input {
            padding: 9px 14px 9px 38px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            font-size: 13.5px;
            width: 100%;
            background: #f8fafc;
            transition: all 0.2s;
        }

        .search-box input:focus {
            background: #fff;
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
        }

        .custom-select {
            padding: 9px 14px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            font-size: 13.5px;
            background-color: #f8fafc;
            color: #334155;
            min-width: 150px;
            cursor: pointer;
            outline: none;
        }

        .custom-select:focus {
            background-color: #fff;
            border-color: #3b82f6;
        }

        .btn-modern {
            padding: 9px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            transition: all 0.2s ease;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-add-brand {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .btn-add-brand:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            color: #ffffff;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
            transform: translateY(-1px);
        }

        .brand-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
        }

        .brand-table th {
            background: #f8fafc;
            padding: 13px 18px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .brand-table td {
            padding: 14px 18px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13.5px;
            color: #334155;
            background: #ffffff;
        }

        .brand-table tbody tr:hover td {
            background-color: #f8fafc;
        }

        .brand-logo-wrap {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
            overflow: hidden;
            flex-shrink: 0;
        }

        .brand-logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 8px;
        }

        .brand-logo-text {
            width: 100%;
            height: 100%;
            border-radius: 8px;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }

        .brand-title-box {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .brand-name {
            font-weight: 600;
            color: #0f172a;
            font-size: 14px;
        }

        .brand-site-link {
            font-size: 12px;
            color: #3b82f6;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .brand-site-link:hover {
            text-decoration: underline;
        }

        .brand-slug-pill {
            display: inline-block;
            padding: 3px 8px;
            background: #f1f5f9;
            color: #475569;
            border-radius: 6px;
            font-family: monospace;
            font-size: 12px;
        }

        .badge-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-status-active {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .badge-status-active::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #10b981;
        }

        .badge-status-inactive {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .badge-status-inactive::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #94a3b8;
        }

        .product-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
        }

        .action-btn-group {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-table-action {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #475569;
            text-decoration: none;
            transition: all 0.15s ease;
            cursor: pointer;
        }

        .btn-table-action:hover {
            color: #0f172a;
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        .btn-action-view:hover {
            color: #0284c7;
            border-color: #bae6fd;
            background: #f0f9ff;
        }

        .btn-action-edit:hover {
            color: #2563eb;
            border-color: #bfdbfe;
            background: #eff6ff;
        }

        .btn-action-toggle:hover {
            color: #16a34a;
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .btn-action-delete:hover {
            color: #dc2626;
            border-color: #fecaca;
            background: #fef2f2;
        }

        .bulk-bar {
            background: #1e293b;
            color: #ffffff;
            border-radius: 12px;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 14px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
            transition: all 0.25s ease;
        }

        .bulk-actions-btns {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-bulk-sub {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-bulk-sub:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .btn-bulk-delete {
            background: #dc2626;
            border-color: #dc2626;
        }

        .btn-bulk-delete:hover {
            background: #b91c1c;
        }

        .campaign-badge {
            display: inline-block;
            font-size: 11.5px;
            font-weight: 600;
            padding: 2px 7px;
            background: #fef3c7;
            color: #92400e;
            border-radius: 4px;
            margin-top: 3px;
        }
    </style>
@endpush

@section('content')
    <div>
        {{-- Thẻ thống kê tổng quan --}}
        <div class="row g-3">
            <div class="col-md-4">
                <div class="brand-stat-card">
                    <div>
                        <div style="font-size: 13px; color: #64748b; font-weight: 500; margin-bottom: 4px;">Tổng thương hiệu</div>
                        <div style="font-size: 26px; font-weight: 700; color: #0f172a;">{{ number_format($stats['total'] ?? $brands->total()) }}</div>
                    </div>
                    <div class="stat-icon-wrapper" style="background: #eff6ff; color: #2563eb;">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="brand-stat-card">
                    <div>
                        <div style="font-size: 13px; color: #64748b; font-weight: 500; margin-bottom: 4px;">Đang hoạt động</div>
                        <div style="font-size: 26px; font-weight: 700; color: #059669;">{{ number_format($stats['active'] ?? 0) }}</div>
                    </div>
                    <div class="stat-icon-wrapper" style="background: #ecfdf5; color: #059669;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="brand-stat-card">
                    <div>
                        <div style="font-size: 13px; color: #64748b; font-weight: 500; margin-bottom: 4px;">Tạm ẩn</div>
                        <div style="font-size: 26px; font-weight: 700; color: #d97706;">{{ number_format($stats['inactive'] ?? 0) }}</div>
                    </div>
                    <div class="stat-icon-wrapper" style="background: #fffbeb; color: #d97706;">
                        <i class="fa-solid fa-eye-slash"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Khối danh sách chính --}}
        <div class="brand-main-card">
            {{-- Toolbar: Lọc, tìm kiếm và nút Thêm mới --}}
            <div class="brand-card-header">
                <form class="filter-group" method="GET" action="{{ route('admin.brands.index') }}" style="flex: 1;">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Tìm theo tên hoặc slug...">
                    </div>
                    <select name="status" class="custom-select" onchange="this.form.submit()">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Đang hiển thị</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Tạm ẩn</option>
                    </select>
                    <button type="submit" class="btn-modern" style="background:#f1f5f9;color:#334155;">
                        <i class="fa-solid fa-filter"></i> Lọc
                    </button>
                    @if(request('keyword') || request('status'))
                        <a href="{{ route('admin.brands.index') }}" class="btn-modern" style="background:transparent;color:#64748b;border:1px solid #e2e8f0;">
                            <i class="fa-solid fa-rotate-left"></i> Đặt lại
                        </a>
                    @endif
                </form>

                <div style="display:flex;align-items:center;gap:10px;">
                    <a href="{{ route('admin.brands.create') }}" class="btn-modern btn-add-brand">
                        <i class="fa-solid fa-plus"></i> Thêm thương hiệu
                    </a>
                </div>
            </div>

            {{-- Bảng dữ liệu thương hiệu --}}
            <div class="table-responsive">
                <table class="brand-table">
                    <thead>
                        <tr>
                            <th style="width: 44px; text-align: center;">
                                <input type="checkbox" id="select-all-brands" style="cursor: pointer; width: 16px; height: 16px;">
                            </th>
                            <th style="width: 60px;">Logo</th>
                            <th>Thương hiệu</th>
                            <th>Slug</th>
                            <th>Chỉ số & Chiến dịch</th>
                            <th>Sản phẩm</th>
                            <th style="text-align: center;">Thứ tự</th>
                            <th style="text-align: center;">Trạng thái</th>
                            <th style="text-align: right; width: 150px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($brands as $brand)
                            <tr>
                                <td style="text-align: center;">
                                    <input type="checkbox" name="selected[]" value="{{ $brand->id }}" class="brand-checkbox" form="brand-bulk-form" style="cursor: pointer; width: 16px; height: 16px;">
                                </td>
                                <td>
                                    <div class="brand-logo-wrap">
                                        @if($brand->logo)
                                            <img src="{{ asset(trim((string) config('media.directories.brands', 'clients/assets/img/brands'), '/') . '/' . $brand->logo) }}" alt="{{ $brand->name }}">
                                        @else
                                            <div class="brand-logo-text">
                                                {{ strtoupper(mb_substr($brand->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="brand-title-box">
                                        <div class="brand-name">{{ $brand->name }}</div>
                                        @if($brand->website)
                                            <a href="{{ $brand->website }}" target="_blank" rel="noopener noreferrer" class="brand-site-link">
                                                {{ $brand->website }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 10px;"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="brand-slug-pill">{{ $brand->slug }}</span>
                                </td>
                                <td>
                                    @if($brand->campaign)
                                        <div><span class="campaign-badge">{{ $brand->campaign }}</span></div>
                                    @endif
                                    <div style="font-size: 12px; color: #64748b; margin-top: 3px; display: flex; align-items: center; gap: 8px;">
                                        @if($brand->rating_score)
                                            <span><i class="fa-solid fa-star text-warning"></i> {{ number_format($brand->rating_score, 2) }}</span>
                                        @endif
                                        @if($brand->followers_count)
                                            <span><i class="fa-solid fa-users text-primary"></i> {{ number_format($brand->followers_count) }}</span>
                                        @endif
                                        @if(!$brand->campaign && !$brand->rating_score && !$brand->followers_count)
                                            <span style="color: #cbd5e1;">--</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="product-count-badge">
                                        <i class="fa-solid fa-shirt"></i> {{ number_format($brand->products_count) }} sp
                                    </span>
                                </td>
                                <td style="text-align: center; font-weight: 600; color: #64748b;">
                                    {{ $brand->sort_order }}
                                </td>
                                <td style="text-align: center;">
                                    @if($brand->is_active)
                                        <span class="badge-status-pill badge-status-active">Hoạt động</span>
                                    @else
                                        <span class="badge-status-pill badge-status-inactive">Tạm ẩn</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    <div class="action-btn-group">
                                        {{-- Nút xem gian hàng thực tế ngoài client --}}
                                        <a href="{{ route('client.brand.show', $brand->slug) }}" target="_blank" class="btn-table-action btn-action-view" title="Xem gian hàng trên website">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>

                                        {{-- Nút sửa --}}
                                        <a href="{{ route('admin.brands.edit', $brand) }}" class="btn-table-action btn-action-edit" title="Chỉnh sửa thương hiệu">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>

                                        {{-- Nút toggle ẩn / hiện --}}
                                        <form action="{{ route('admin.brands.toggle', $brand) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-table-action btn-action-toggle" title="{{ $brand->is_active ? 'Bấm để tạm ẩn' : 'Bấm để hiển thị' }}">
                                                <i class="fa-solid {{ $brand->is_active ? 'fa-toggle-on text-success' : 'fa-toggle-off text-muted' }}" style="font-size: 15px;"></i>
                                            </button>
                                        </form>

                                        {{-- Nút xóa --}}
                                        <form action="{{ route('admin.brands.destroy', $brand) }}" method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa thương hiệu [{{ $brand->name }}]? Các sản phẩm đang thuộc hãng sẽ tự động bỏ gán.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-table-action btn-action-delete" title="Xóa thương hiệu">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 48px 16px;">
                                    <div style="font-size: 42px; color: #cbd5e1; margin-bottom: 12px;">
                                        <i class="fa-solid fa-inbox"></i>
                                    </div>
                                    <div style="font-size: 15px; font-weight: 600; color: #475569; margin-bottom: 4px;">Chưa tìm thấy thương hiệu nào</div>
                                    <div style="font-size: 13px; color: #94a3b8; margin-bottom: 16px;">Hãy thử thay đổi từ khóa tìm kiếm hoặc tạo thương hiệu mới.</div>
                                    <a href="{{ route('admin.brands.create') }}" class="btn-modern btn-add-brand">
                                        <i class="fa-solid fa-plus"></i> Thêm thương hiệu ngay
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Phân trang --}}
            @if($brands->hasPages())
                <div style="padding: 16px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end;">
                    {{ $brands->links() }}
                </div>
            @endif
        </div>

        {{-- Thanh thao tác hàng loạt (Bulk actions) --}}
        <div id="bulk-action-container" style="display: none;">
            <form id="brand-bulk-form" action="{{ route('admin.brands.bulk-action') }}" method="POST" class="bulk-bar">
                @csrf
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-check-double text-primary" style="font-size: 16px;"></i>
                    <span style="font-size: 13.5px;">Đã chọn <strong id="selected-count">0</strong> thương hiệu</span>
                </div>
                <div class="bulk-actions-btns">
                    <button type="submit" class="btn-bulk-sub" name="bulk_action" value="show">
                        <i class="fa-solid fa-eye"></i> Hiển thị tất cả
                    </button>
                    <button type="submit" class="btn-bulk-sub" name="bulk_action" value="hide">
                        <i class="fa-solid fa-eye-slash"></i> Ẩn tất cả
                    </button>
                    <button type="submit" class="btn-bulk-sub btn-bulk-delete" name="bulk_action" value="delete" onclick="return confirm('CẢNH BÁO: Xóa tất cả các thương hiệu đã chọn? Các sản phẩm liên quan sẽ tự động bỏ gán hãng.')">
                        <i class="fa-solid fa-trash-can"></i> Xóa vĩnh viễn
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('select-all-brands');
            const checkboxes = document.querySelectorAll('.brand-checkbox');
            const bulkContainer = document.getElementById('bulk-action-container');
            const selectedCountEl = document.getElementById('selected-count');

            function updateBulkBar() {
                const checkedBoxes = Array.from(checkboxes).filter(cb => cb.checked);
                const count = checkedBoxes.length;

                if (selectedCountEl) {
                    selectedCountEl.textContent = count;
                }

                if (bulkContainer) {
                    bulkContainer.style.display = count > 0 ? 'block' : 'none';
                }

                if (selectAll) {
                    selectAll.checked = count > 0 && count === checkboxes.length;
                    selectAll.indeterminate = count > 0 && count < checkboxes.length;
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    checkboxes.forEach((cb) => {
                        cb.checked = selectAll.checked;
                    });
                    updateBulkBar();
                });
            }

            checkboxes.forEach((cb) => {
                cb.addEventListener('change', updateBulkBar);
            });

            const bulkForm = document.getElementById('brand-bulk-form');
            if (bulkForm) {
                bulkForm.addEventListener('submit', (e) => {
                    const hasSelected = Array.from(checkboxes).some(cb => cb.checked);
                    if (!hasSelected) {
                        e.preventDefault();
                        alert('Vui lòng chọn ít nhất một thương hiệu để thao tác.');
                    }
                });
            }
        });
    </script>
@endpush
