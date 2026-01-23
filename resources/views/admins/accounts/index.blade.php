@extends('admins.layouts.master')

@section('title', 'Quản lý tài khoản')
@section('page-title', '👤 Tài khoản')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/account-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .accounts-page {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }
        .page-header h2 {
            font-size: 24px;
            margin: 0;
            color: #0f172a;
        }
        .page-subtitle {
            color: #64748b;
            font-size: 13px;
            margin-top: 4px;
        }
        .header-actions {
            display: flex;
            gap: 10px;
        }
        .stat-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit,minmax(160px,1fr));
        }
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 10px 40px rgba(15,23,42,0.08);
            border: 1px solid #e2e8f0;
        }
        .stat-card h6 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin: 0 0 6px;
        }
        .stat-card strong {
            font-size: 24px;
            color: #0f172a;
        }
        .stat-card span {
            font-size: 12px;
            color: #22c55e;
            margin-left: 6px;
        }
        .filter-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 16px;
            box-shadow: 0 12px 30px rgba(15,23,42,0.05);
        }
        .filter-basic {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit,minmax(200px,1fr));
        }
        .filter-advanced {
            display: none;
            gap: 12px;
            margin-top: 12px;
            grid-template-columns: repeat(auto-fit,minmax(180px,1fr));
        }
        .filter-card label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 4px;
            display: block;
        }
        .filter-card input,
        .filter-card select {
            width: 100%;
            border: 1px solid #cbd5f5;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 13px;
            background: #f8fafc;
        }
        .filter-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 16px;
        }
        .table-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 15px 50px rgba(15,23,42,0.06);
            overflow: hidden;
        }
        .account-table {
            width: 100%;
            border-collapse: collapse;
        }
        .account-table thead th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            background: #f8fafc;
            padding: 12px 18px;
        }
        .account-table tbody td {
            padding: 16px 18px;
            border-top: 1px solid #f1f5f9;
            vertical-align: top;
        }
        .account-table tbody tr:hover {
            background: #f8fafc;
        }
        .user-cell {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .user-cell img {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
        }
        .user-name {
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .verified-badge {
            font-size: 11px;
            color: #0f766e;
            background: #ccfbf1;
            border-radius: 999px;
            padding: 2px 8px;
        }
        .user-contact,
        .user-meta {
            font-size: 12px;
            color: #475569;
        }
        .role-pill {
            display: inline-flex;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .role-admin { background: #fee2e2; color: #b91c1c; }
        .role-staff { background: #dbeafe; color: #1d4ed8; }
        .role-writer { background: #fef3c7; color: #b45309; }
        .role-user { background: #e2e8f0; color: #475569; }
        .status-stack {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-active { background: #dcfce7; color: #16a34a; }
        .status-inactive { background: #fee2e2; color: #b91c1c; }
        .status-chip {
            display: inline-flex;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid #e2e8f0;
            text-transform: capitalize;
            color: #475569;
        }
        .status-chip.status-locked { border-color: #f87171; color: #b91c1c; }
        .status-chip.status-suspended { border-color: #fb923c; color: #c2410c; }
        .status-chip.status-banned { border-color: #f43f5e; color: #be123c; }
        .last-login strong {
            display: block;
            font-size: 13px;
            color: #0f172a;
        }
        .last-login small {
            color: #94a3b8;
            font-size: 11px;
        }
        .table-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .quick-btn {
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #0f172a;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 12px;
            transition: all 0.2s;
        }
        .quick-btn:hover {
            border-color: #6366f1;
            color: #4c1d95;
        }
        .bulk-bar {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 12px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px dashed #cbd5f5;
        }
        .bulk-bar select {
            border: 1px solid #cbd5f5;
            border-radius: 8px;
            padding: 8px 10px;
            background: #fff;
        }
        @media (max-width: 768px) {
            .page-header { flex-direction: column; }
            .user-cell { flex-direction: column; align-items: center; text-align: center; }
            .table-actions { flex-direction: column; }
        }
    </style>
@endpush

@section('content')
    @php
        $statusOptions = [
            '' => '-- Trạng thái --',
            'active' => 'Đang hoạt động',
            'inactive' => 'Tạm khóa',
        ];
        $emailVerifiedOptions = [
            '' => '-- Email xác minh --',
            'yes' => 'Đã xác minh',
            'no' => 'Chưa xác minh',
        ];
        $genderOptions = [
            '' => '-- Giới tính --',
            'male' => 'Nam',
            'female' => 'Nữ',
            'other' => 'Khác',
        ];
        $accountStatusLabels = [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            'locked' => 'Locked',
            'review' => 'Review',
            'banned' => 'Banned',
        ];
        $advancedOpen = collect($filters ?? [])
            ->only(['account_status','email_verified','gender','location','last_login_from','last_login_to'])
            ->filter(fn($value) => $value !== null && $value !== '')
            ->isNotEmpty();
        $statCards = [
            ['label' => 'Tổng tài khoản', 'key' => 'total'],
            ['label' => 'Đang hoạt động', 'key' => 'active'],
            ['label' => 'Tạm khóa', 'key' => 'inactive'],
            ['label' => 'Đã xác minh', 'key' => 'verified'],
            ['label' => 'Bị khóa', 'key' => 'locked'],
            ['label' => 'Bị treo', 'key' => 'suspended'],
        ];
    @endphp

    <div class="accounts-page">
        <div class="page-header">
            <div>
                <h2>Danh sách tài khoản</h2>
                <p class="page-subtitle">
                    {{ $accounts->total() }} tài khoản • cập nhật {{ now()->diffForHumans() }}
                </p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn btn-light" id="toggleAdvancedFilters">
                    🔎 Bộ lọc nâng cao
                </button>
                <a href="{{ route('admin.accounts.create') }}" class="btn btn-primary">
                    ➕ Thêm tài khoản
                </a>
            </div>
        </div>

        <div class="stat-grid">
            @foreach($statCards as $card)
                <div class="stat-card">
                    <h6>{{ $card['label'] }}</h6>
                    <strong>{{ number_format($stats[$card['key']] ?? 0) }}</strong>
                </div>
            @endforeach
        </div>

        <form class="filter-card" method="GET">
            <div class="filter-basic">
                <div>
                    <label>Từ khóa</label>
                    <input type="text" name="keyword" placeholder="Tìm tên, email hoặc số điện thoại..."
                           value="{{ $filters['keyword'] ?? '' }}">
                </div>
                <div>
                    <label>Vai trò</label>
                    <select name="role">
                        <option value="">-- Vai trò --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role }}" {{ ($filters['role'] ?? '') === $role ? 'selected' : '' }}>
                                {{ ucfirst($role) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Trạng thái hoạt động</label>
                    <select name="status">
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="filter-advanced" data-open="{{ $advancedOpen ? 'true' : 'false' }}">
                <div>
                    <label>Account status</label>
                    <select name="account_status">
                        <option value="">-- Tất cả --</option>
                        @foreach($accountStatuses as $status)
                            <option value="{{ $status }}" {{ ($filters['account_status'] ?? '') === $status ? 'selected' : '' }}>
                                {{ $accountStatusLabels[$status] ?? ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Email xác minh</label>
                    <select name="email_verified">
                        @foreach($emailVerifiedOptions as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['email_verified'] ?? '') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Giới tính</label>
                    <select name="gender">
                        @foreach($genderOptions as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['gender'] ?? '') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Khu vực</label>
                    <input type="text" name="location" placeholder="VD: Hà Nội"
                           value="{{ $filters['location'] ?? '' }}">
                </div>
                <div>
                    <label>Đăng nhập từ</label>
                    <input type="date" name="last_login_from" value="{{ $filters['last_login_from'] ?? '' }}">
                </div>
                <div>
                    <label>Đăng nhập đến</label>
                    <input type="date" name="last_login_to" value="{{ $filters['last_login_to'] ?? '' }}">
                </div>
            </div>

            <div class="filter-actions">
                <a href="{{ route('admin.accounts.index') }}" class="btn btn-light">Đặt lại</a>
                <button type="submit" class="btn btn-primary">Áp dụng</button>
            </div>
        </form>

        <div class="table-card">
            <table class="account-table">
                <thead>
                    <tr>
                        <th style="width:40px;">
                            <input type="checkbox" id="select-all-accounts">
                        </th>
                        <th>Người dùng</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Lần đăng nhập gần nhất</th>
                        <th style="width:220px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($accounts as $account)
                    @php
                        $profile = $account->profile;
                        $displayName = $profile?->full_name ?? $account->name ?? '—';
                        $avatar = $profile?->avatar
                            ? asset('admins/img/accounts/' . $profile->avatar)
                            : 'https://ui-avatars.com/api/?name=' . urlencode($displayName) . '&background=F3F4F6&color=0F172A&bold=true';
                        $roleClass = 'role-' . ($account->role ?? 'user');
                        $statusLabel = $account->is_active ? 'Đang hoạt động' : 'Đã khóa';
                        $loginHuman = optional($account->login_history)?->diffForHumans();
                        $loginExact = optional($account->login_history)?->format('d/m/Y H:i');
                    @endphp
                    <tr>
                        <td>
                            <input type="checkbox"
                                   class="account-checkbox"
                                   name="selected[]"
                                   value="{{ $account->id }}"
                                   form="account-bulk-form">
                        </td>
                        <td>
                            <div class="user-cell">
                                <img src="{{ $avatar }}" alt="{{ $displayName }}">
                                <div>
                                    <div class="user-name">
                                        {{ $displayName }}
                                        @if($account->email_verified_at)
                                            <span class="verified-badge">Đã xác minh</span>
                                        @endif
                                    </div>
                                    <div class="user-contact">{{ $account->email }}</div>
                                    @if($profile?->phone)
                                        <div class="user-meta">📞 {{ $profile->phone }}</div>
                                    @endif
                                    @if($profile?->location)
                                        <div class="user-meta">📍 {{ $profile->location }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="role-pill {{ $roleClass }}">
                                {{ ucfirst($account->role) }}
                            </span>
                        </td>
                        <td>
                            <div class="status-stack">
                                <span class="status-pill {{ $account->is_active ? 'status-active' : 'status-inactive' }}">
                                    {{ $statusLabel }}
                                </span>
                                <span class="status-chip status-{{ $account->account_status }}">
                                    {{ $accountStatusLabels[$account->account_status] ?? ucfirst($account->account_status ?? '—') }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="last-login">
                                <strong>{{ $loginHuman ?? 'Chưa có' }}</strong>
                                @if($loginExact)
                                    <small>{{ $loginExact }}</small>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="{{ route('admin.accounts.edit', $account) }}" class="quick-btn">
                                    ✏️ Chi tiết
                                </a>
                                <form action="{{ route('admin.accounts.toggle', $account) }}" method="POST"
                                      onsubmit="return confirm('Xác nhận thay đổi trạng thái tài khoản này?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="quick-btn">
                                        {{ $account->is_active ? '🔒 Khóa' : '🔓 Mở khóa' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:40px;color:#94a3b8;">
                            Không tìm thấy tài khoản phù hợp với bộ lọc hiện tại.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <form id="account-bulk-form" action="{{ route('admin.accounts.bulk-action') }}" method="POST" class="bulk-bar">
            @csrf
            <strong>Hành động hàng loạt:</strong>
            <select name="bulk_action" required>
                <option value="">-- Chọn hành động --</option>
                <option value="activate">Mở khóa</option>
                <option value="deactivate">Khóa tạm thời</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Thực thi</button>
        </form>

        <div>
            {{ $accounts->links() }}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('select-all-accounts');
            const checkboxes = document.querySelectorAll('.account-checkbox');
            const bulkForm = document.getElementById('account-bulk-form');
            const advancedPanel = document.querySelector('.filter-advanced');
            const toggleAdvanced = document.getElementById('toggleAdvancedFilters');

            if (toggleAdvanced && advancedPanel) {
                let isOpen = advancedPanel.dataset.open === 'true';

                const syncAdvanced = () => {
                    advancedPanel.style.display = isOpen ? 'grid' : 'none';
                    toggleAdvanced.classList.toggle('btn-primary', isOpen);
                };

                toggleAdvanced.addEventListener('click', () => {
                    isOpen = !isOpen;
                    syncAdvanced();
                });

                syncAdvanced();
            }

            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    checkboxes.forEach(cb => cb.checked = selectAll.checked);
                });
            }

            if (bulkForm) {
                bulkForm.addEventListener('submit', (event) => {
                    const hasSelected = Array.from(checkboxes).some(cb => cb.checked);
                    if (!hasSelected) {
                        event.preventDefault();
                        alert('Vui lòng chọn ít nhất một tài khoản để thực hiện.');
                    }
                });
            }
        });
    </script>
@endpush
