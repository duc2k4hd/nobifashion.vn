@extends('admins.layouts.master')

@section('title', 'Quản lý danh mục')
@section('page-title', '🏷️ Danh mục sản phẩm')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/category-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .category-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .category-table th, .category-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #eef2f7;
            text-align: left;
            font-size: 13px;
        }
        .category-table th {
            background: #f8fafc;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
        }
        .category-table tr:hover td {
            background: #f1f5f9;
        }
        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .filter-bar input,
        .filter-bar select {
            padding: 6px 10px;
            border: 1px solid #cbd5f5;
            border-radius: 6px;
            font-size: 13px;
        }
        .badge {
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 11px;
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
            gap: 6px;
        }
    </style>
@endpush

@section('content')
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="margin:0;">Danh sách danh mục</h2>
            <div style="display:flex;gap:10px;">
                <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">➕ Thêm danh mục</a>
            </div>
        </div>

        <form class="filter-bar" method="GET">
            <input type="text" name="keyword" placeholder="Tìm tên hoặc slug..."
                   value="{{ request('keyword') }}">
            <select name="status">
                <option value="">-- Trạng thái --</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Đang hiển thị</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Tạm ẩn</option>
            </select>
            <button type="submit" class="btn btn-primary">Lọc</button>
        </form>

        <div class="table-responsive">
            <table class="category-table">
                <thead>
                <tr>
                    <th style="width:40px;">
                        <input type="checkbox" id="select-all-categories">
                    </th>
                    <th>Tên</th>
                    <th>Slug</th>
                    <th>Danh mục cha</th>
                    <th>Thứ tự</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td>
                            <input type="checkbox" name="selected[]" value="{{ $category->id }}" class="category-checkbox" form="category-bulk-form">
                        </td>
                        <td>
                            <strong>{{ $category->name }}</strong>
                        </td>
                        <td>{{ $category->slug }}</td>
                        <td>{{ $category->parent?->name ?? '-' }}</td>
                        <td>{{ $category->sort_order }}</td>
                        <td>
                            @if($category->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-secondary btn-sm">Sửa</a>
                                <form action="{{ route('admin.categories.toggle', $category) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-primary btn-sm" style="background:#0ea5e9;border:none;">
                                        {{ $category->is_active ? 'Ẩn' : 'Hiển thị' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:30px;color:#94a3b8;">Chưa có danh mục nào</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <form id="category-bulk-form" action="{{ route('admin.categories.bulk-action') }}" method="POST" style="margin-top:10px;display:flex;gap:10px;">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm" name="bulk_action" value="hide">Ẩn các danh mục đã chọn</button>
            <button type="submit" class="btn btn-primary btn-sm" name="bulk_action" value="show">Hiển thị các danh mục đã chọn</button>
        </form>

        <div style="margin-top:16px;">
            {{ $categories->links() }}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('select-all-categories');
            const checkboxes = document.querySelectorAll('.category-checkbox');
            const form = document.getElementById('category-bulk-form');

            if (!selectAll || !form) return;

            selectAll.addEventListener('change', () => {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
            });

            form.addEventListener('submit', (e) => {
                const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                if (!anyChecked) {
                    e.preventDefault();
                    alert('Vui lòng chọn ít nhất một danh mục.');
                }
            });
        });
    </script>
@endpush


