@extends('admins.layouts.master')

@section('title', 'Quản lý cài đặt hệ thống')
@section('page-title', 'Cài đặt hệ thống')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/settings-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .settings-container {
            margin: 0 auto;
        }

        /* Header section */
        .page-header-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
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
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }
        .stat-pill {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .stat-pill-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 2px;
        }
        .stat-pill-val {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
        }
        .stat-pill-icon {
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

        /* Filter Box */
        .filter-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .filter-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 12px;
            align-items: flex-end;
        }
        @media (max-width: 992px) {
            .filter-form {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 576px) {
            .filter-form {
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
        .filter-actions {
            display: flex;
            gap: 8px;
        }

        /* Table Card */
        .table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }
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
            padding: 13px 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #334155;
        }
        .clean-table tbody tr:hover td {
            background: #f8fafc;
        }
        .clean-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Key and code badges */
        .setting-key-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11.5px;
            background: #f1f5f9;
            color: #334155;
            padding: 2px 7px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            margin-top: 3px;
        }
        .btn-copy-key {
            background: none;
            border: none;
            color: #94a3b8;
            padding: 0;
            cursor: pointer;
            font-size: 11px;
            transition: color 0.15s;
        }
        .btn-copy-key:hover {
            color: #2563eb;
        }

        /* Badges */
        .badge-type {
            display: inline-block;
            font-size: 11px;
            font-weight: 500;
            padding: 2px 8px;
            border-radius: 4px;
            background: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .badge-group {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
            background: #f1f5f9;
            color: #1e293b;
        }
        .badge-scope-public {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 4px;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .badge-scope-private {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 500;
            padding: 2px 7px;
            border-radius: 4px;
            background: #f8fafc;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }
        .badge-system {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 4px;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }
        .badge-custom {
            display: inline-block;
            font-size: 11px;
            color: #94a3b8;
        }

        /* Action buttons */
        .table-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s ease;
            height: 30px;
        }
        .btn-action-edit {
            background: #ffffff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }
        .btn-action-edit:hover {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #93c5fd;
        }
        .btn-action-delete {
            background: #ffffff;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .btn-action-delete:hover {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fca5a5;
        }
        .btn-action-locked {
            background: #f8fafc;
            color: #94a3b8;
            border: 1px solid #e2e8f0;
            cursor: not-allowed;
        }

        /* Alert styling */
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

        /* Settings Pagination Box */
        .settings-pagination-box {
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
<div class="settings-container">

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

    {{-- Page Header --}}
    <div class="page-header-box">
        <div>
            <h2>
                <i class="fa-solid fa-sliders text-secondary"></i>
                Cài đặt hệ thống
            </h2>
            <p>Quản lý các thông số cấu hình hoạt động, nhận diện thương hiệu và tích hợp của website.</p>
        </div>
        <div>
            <a href="{{ route('admin.settings.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                <i class="fa-solid fa-plus"></i>
                <span>Thêm setting</span>
            </a>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="stats-row">
        <div class="stat-pill">
            <div>
                <div class="stat-pill-label">Tổng cài đặt</div>
                <div class="stat-pill-val">{{ $stats['total'] ?? $settings_all->total() }}</div>
            </div>
            <div class="stat-pill-icon">
                <i class="fa-solid fa-layer-group"></i>
            </div>
        </div>

        <div class="stat-pill">
            <div>
                <div class="stat-pill-label">Công khai (Public)</div>
                <div class="stat-pill-val">{{ $stats['public'] ?? 0 }}</div>
            </div>
            <div class="stat-pill-icon text-success">
                <i class="fa-solid fa-globe"></i>
            </div>
        </div>

        <div class="stat-pill">
            <div>
                <div class="stat-pill-label">Hệ thống bảo vệ</div>
                <div class="stat-pill-val">{{ $stats['system'] ?? count($protectedKeys) }}</div>
            </div>
            <div class="stat-pill-icon text-primary">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="filter-panel">
        <form class="filter-form" method="GET" action="{{ route('admin.settings.index') }}">
            <div class="filter-field">
                <label for="filter-keyword">Tìm kiếm</label>
                <input type="text" id="filter-keyword" name="keyword"
                       placeholder="Nhập mã key, nhãn hiển thị hoặc giá trị..."
                       value="{{ request('keyword') }}">
            </div>

            <div class="filter-field">
                <label for="filter-group">Nhóm</label>
                <select id="filter-group" name="group">
                    <option value="">-- Tất cả nhóm --</option>
                    @foreach($groups as $group)
                        <option value="{{ $group }}" {{ request('group') === $group ? 'selected' : '' }}>
                            {{ ucfirst($group) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="filter-type">Kiểu dữ liệu</label>
                <select id="filter-type" name="type">
                    <option value="">-- Tất cả kiểu --</option>
                    @foreach($types as $type)
                        <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>
                            {{ ucfirst($type) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="filter-public">Phạm vi</label>
                <select id="filter-public" name="is_public">
                    <option value="">-- Tất cả --</option>
                    <option value="1" {{ request('is_public') === '1' ? 'selected' : '' }}>Public</option>
                    <option value="0" {{ request('is_public') === '0' ? 'selected' : '' }}>Nội bộ</option>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-secondary d-inline-flex align-items-center gap-1" style="height: 38px;">
                    <i class="fa-solid fa-filter"></i> Lọc
                </button>
                @if(request()->hasAny(['keyword', 'group', 'type', 'is_public']))
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center" style="height: 38px;" title="Xóa bộ lọc">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Settings Table --}}
    <div class="table-card">
        <div class="table-responsive">
            <table class="clean-table">
                <thead>
                    <tr>
                        <th style="width: 280px;">Cấu hình / Mã Key</th>
                        <th style="width: 120px;">Nhóm</th>
                        <th style="width: 100px;">Kiểu</th>
                        <th>Giá trị hiện tại</th>
                        <th style="width: 110px; text-align: center;">Phạm vi</th>
                        <th style="width: 120px; text-align: center;">Bảo vệ</th>
                        <th style="width: 130px; text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($settings_all as $setting)
                        @php
                            $isProtected = in_array($setting->key, $protectedKeys, true);
                            $canDelete = !$isProtected || $isSuperAdmin;
                        @endphp
                        <tr>
                            {{-- Cấu hình / Key --}}
                            <td>
                                <div style="font-weight: 600; color: #0f172a;">
                                    {{ $setting->label ?: $setting->key }}
                                </div>
                                <div class="setting-key-tag">
                                    <span>{{ $setting->key }}</span>
                                    <button type="button" class="btn-copy-key" title="Sao chép key" onclick="copySettingKey('{{ $setting->key }}', this)">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                </div>
                                @if($setting->description)
                                    <div style="font-size: 11.5px; color: #94a3b8; margin-top: 2px;" title="{{ $setting->description }}">
                                        {{ \Illuminate\Support\Str::limit($setting->description, 50) }}
                                    </div>
                                @endif
                            </td>

                            {{-- Nhóm --}}
                            <td>
                                <span class="badge-group">{{ $setting->group ?: 'general' }}</span>
                            </td>

                            {{-- Kiểu dữ liệu --}}
                            <td>
                                <span class="badge-type">{{ ucfirst($setting->type) }}</span>
                            </td>

                            {{-- Giá trị --}}
                            <td style="max-width: 320px;">
                                @if($setting->key === 'product_recommen')
                                    @php
                                        $catName = !empty($setting->value) ? \App\Models\Category::where('id', $setting->value)->value('name') : null;
                                    @endphp
                                    @if($catName)
                                        <span class="badge bg-light text-primary border" style="font-weight: 600; font-size: 12px;">
                                            <i class="fa-solid fa-folder me-1"></i> {{ $catName }}
                                            <span class="text-muted font-monospace">(ID: {{ $setting->value }})</span>
                                        </span>
                                    @else
                                        <span class="badge bg-light text-secondary border" style="font-style: italic; font-size: 11.5px;">
                                            <i class="fa-solid fa-shuffle me-1"></i> 50% Nam & 50% Nữ (Mặc định)
                                        </span>
                                    @endif
                                @elseif($setting->type === 'boolean')
                                    @if((bool) $setting->value)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            <i class="fa-solid fa-check me-1"></i> Bật
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                            <i class="fa-solid fa-xmark me-1"></i> Tắt
                                        </span>
                                    @endif
                                @elseif($setting->type === 'image' && !empty($setting->value))
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ asset($setting->value) }}" alt="Preview"
                                             style="width: 28px; height: 28px; border-radius: 4px; object-fit: contain; background: #f8fafc; border: 1px solid #e2e8f0;"
                                             onerror="this.style.display='none'">
                                        <span class="text-truncate font-monospace" style="max-width: 220px; font-size: 12px;" title="{{ $setting->value }}">
                                            {{ $setting->value }}
                                        </span>
                                    </div>
                                @else
                                    <div class="text-truncate" style="max-width: 320px;" title="{{ $setting->value }}">
                                        {{ $setting->value !== null && $setting->value !== '' ? \Illuminate\Support\Str::limit(strip_tags($setting->value), 70) : '—' }}
                                    </div>
                                @endif
                            </td>

                            {{-- Phạm vi (Public / Private) --}}
                            <td style="text-align: center;">
                                @if($setting->is_public)
                                    <span class="badge-scope-public">
                                        <i class="fa-solid fa-eye"></i> Public
                                    </span>
                                @else
                                    <span class="badge-scope-private">
                                        <i class="fa-solid fa-eye-slash"></i> Nội bộ
                                    </span>
                                @endif
                            </td>

                            {{-- Bảo vệ hệ thống --}}
                            <td style="text-align: center;">
                                @if($isProtected)
                                    <span class="badge-system" title="Setting hệ thống - Không được xóa (chỉ admin@gmail.com được xoá)">
                                        <i class="fa-solid fa-shield-halved"></i> Hệ thống
                                    </span>
                                @else
                                    <span class="badge-custom">Tùy biến</span>
                                @endif
                            </td>

                            {{-- Thao tác --}}
                            <td style="text-align: right;">
                                <div class="table-actions justify-content-end">
                                    <a href="{{ route('admin.settings.edit', $setting) }}" class="btn-action btn-action-edit" title="Chỉnh sửa cấu hình">
                                        <i class="fa-solid fa-pen-to-square"></i> Sửa
                                    </a>

                                    @if($canDelete)
                                        <form action="{{ route('admin.settings.destroy', $setting) }}" method="POST" class="d-inline"
                                              onsubmit="return confirmDeleteSetting('{{ $setting->key }}', {{ $isProtected ? 'true' : 'false' }})">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-action btn-action-delete" title="Xoá cài đặt">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn-action btn-action-locked"
                                                title="Setting hệ thống được bảo vệ - Chỉ tài khoản admin@gmail.com mới có quyền xoá"
                                                disabled>
                                            <i class="fa-solid fa-lock"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 48px 16px; color: #94a3b8;">
                                <i class="fa-solid fa-sliders" style="font-size: 32px; margin-bottom: 12px; display: block; color: #cbd5e1;"></i>
                                <div style="font-weight: 500; font-size: 14px; color: #64748b;">Không tìm thấy cài đặt nào</div>
                                <div style="font-size: 12px; margin-top: 4px;">Thử thay đổi từ khóa hoặc bộ lọc tìm kiếm phía trên.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Phân trang sạch đẹp --}}
        <div class="settings-pagination-box">
            <div class="pagination-count-text">
                Hiển thị <strong>{{ $settings_all->firstItem() ?? 0 }}</strong> - <strong>{{ $settings_all->lastItem() ?? 0 }}</strong> trên tổng số <strong>{{ $settings_all->total() }}</strong> cài đặt
            </div>

            @if($settings_all->hasPages())
                <nav aria-label="Phân trang cài đặt">
                    <ul class="clean-pagination-list">
                        {{-- Nút Trang trước --}}
                        @if ($settings_all->onFirstPage())
                            <li class="page-item disabled">
                                <span class="page-link" aria-label="Trang trước" title="Trang trước">
                                    <i class="fa-solid fa-angle-left"></i>
                                </span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $settings_all->previousPageUrl() }}" rel="prev" aria-label="Trang trước" title="Trang trước">
                                    <i class="fa-solid fa-angle-left"></i>
                                </a>
                            </li>
                        @endif

                        {{-- Danh sách trang --}}
                        @php
                            $currentPage = $settings_all->currentPage();
                            $lastPage = $settings_all->lastPage();
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($lastPage, $currentPage + 2);
                        @endphp

                        @if($startPage > 1)
                            <li class="page-item">
                                <a class="page-link" href="{{ $settings_all->url(1) }}">1</a>
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
                                    <a class="page-link" href="{{ $settings_all->url($page) }}">{{ $page }}</a>
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
                                <a class="page-link" href="{{ $settings_all->url($lastPage) }}">{{ $lastPage }}</a>
                            </li>
                        @endif

                        {{-- Nút Trang sau --}}
                        @if ($settings_all->hasMorePages())
                            <li class="page-item">
                                <a class="page-link" href="{{ $settings_all->nextPageUrl() }}" rel="next" aria-label="Trang sau" title="Trang sau">
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

<script>
    function copySettingKey(key, btn) {
        navigator.clipboard.writeText(key).then(() => {
            const icon = btn.querySelector('i');
            icon.className = 'fa-solid fa-check text-success';
            setTimeout(() => {
                icon.className = 'fa-regular fa-copy';
            }, 1500);
        });
    }

    function confirmDeleteSetting(key, isProtected) {
        if (isProtected) {
            return confirm("⚠️ CẢNH BÁO: '" + key + "' là cài đặt hệ thống quan trọng!\n\nBạn đang thực hiện thao tác với tài khoản admin@gmail.com.\nBạn có chắc chắn muốn xóa không?");
        }
        return confirm("Bạn có chắc chắn muốn xóa cài đặt '" + key + "' không?");
    }
</script>
@endsection
