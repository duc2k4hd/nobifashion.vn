@extends('admins.layouts.master')

@section('title', 'Chi tiết tài khoản')
@section('page-title', '👤 Chi tiết tài khoản')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/account-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .detail-page {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .avatar-history-list {
            display: grid;
            gap: 12px;
        }
        .avatar-history-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }
        .avatar-history-item img {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            object-fit: cover;
        }
        .detail-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            background: linear-gradient(120deg, #eef2ff, #ecfeff);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 60px rgba(79,70,229,0.15);
        }
        .header-info {
            display: flex;
            gap: 18px;
        }
        .header-avatar {
            width: 72px;
            height: 72px;
            border-radius: 22px;
            overflow: hidden;
            border: 3px solid rgba(255,255,255,0.8);
            box-shadow: 0 10px 30px rgba(15,23,42,0.2);
        }
        .header-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .header-meta h2 {
            margin: 0;
            font-size: 24px;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .header-meta p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 14px;
        }
        .header-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .tag {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(15,23,42,0.08);
            background: rgba(255,255,255,0.7);
            color: #0f172a;
        }
        .tag.success { color: #0f766e; border-color: #14b8a6; }
        .tag.danger { color: #b91c1c; border-color: #f87171; }
        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .header-actions button,
        .header-actions a {
            border: none;
            border-radius: 999px;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-light {
            background: #fff;
            color: #0f172a;
            border: 1px solid #e2e8f0;
        }
        .btn-outline-danger {
            background: transparent;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .btn-success {
            background: #10b981 !important;
            color: #fff !important;
            border-color: #10b981 !important;
        }
        .metric-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit,minmax(200px,1fr));
        }
        .metric-card {
            background: #fff;
            padding: 18px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 40px rgba(15,23,42,0.05);
        }
        .metric-card h5 {
            margin: 0;
            font-size: 12px;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 0.08em;
        }
        .metric-card strong {
            display: block;
            margin-top: 6px;
            font-size: 18px;
            color: #0f172a;
        }
        .tabs {
            display: flex;
            gap: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .tabs button {
            border: none;
            background: transparent;
            padding: 12px 18px;
            font-weight: 600;
            color: #64748b;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        .tabs button.active {
            color: #4c1d95;
            border-color: #7c3aed;
        }
        .tab-panels {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            padding: 24px;
            box-shadow: 0 15px 50px rgba(15,23,42,0.05);
        }
        .tab-panel {
            display: none;
        }
        .tab-panel.active {
            display: block;
        }
        .form-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit,minmax(220px,1fr));
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            border: 1px solid #cbd5f5;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 13px;
            background: #f8fafc;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
        }
        .timeline {
            border-left: 2px dashed #e2e8f0;
            margin-left: 10px;
            padding-left: 20px;
        }
        .timeline-item {
            margin-bottom: 18px;
            position: relative;
        }
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -31px;
            top: 4px;
            width: 12px;
            height: 12px;
            background: #a855f7;
            border-radius: 999px;
            box-shadow: 0 0 0 4px rgba(168,85,247,0.2);
        }
        .timeline-item h4 {
            margin: 0;
            font-size: 14px;
            color: #0f172a;
        }
        .timeline-item small {
            color: #94a3b8;
            font-size: 12px;
        }
        .log-payload {
            margin-top: 6px;
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 12px;
            padding: 8px 10px;
            font-family: "JetBrains Mono", monospace;
            font-size: 12px;
            overflow-x: auto;
        }
        .avatar-manager {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit,minmax(260px,1fr));
        }
        .avatar-card {
            border: 1px dashed #cbd5f5;
            border-radius: 16px;
            padding: 18px;
            text-align: center;
        }
        .avatar-preview img {
            width: 120px;
            height: 120px;
            border-radius: 24px;
            object-fit: cover;
            border: 4px solid #f8fafc;
            box-shadow: 0 10px 30px rgba(15,23,42,0.2);
        }
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 18px;
            border-radius: 12px;
            color: #fff;
            background: #0f172a;
            box-shadow: 0 10px 30px rgba(15,23,42,0.2);
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s;
            z-index: 999;
        }
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
@endpush

@section('content')
    @php
        $apiRoutes = [
            'account' => route('admin.api.accounts.show', $account_admin),
            'updateAccount' => route('admin.api.accounts.update', $account_admin),
            'toggle' => route('admin.api.accounts.toggle', $account_admin),
            'changeRole' => route('admin.api.accounts.change-role', $account_admin),
            'resetPassword' => route('admin.api.accounts.reset-password', $account_admin),
            'forceLogout' => route('admin.api.accounts.force-logout', $account_admin),
            'verifyEmail' => route('admin.api.accounts.verify-email', $account_admin),
            'profile' => route('admin.api.accounts.profile.show', $account_admin),
            'profileUpdate' => route('admin.api.accounts.profile.update', $account_admin),
            'profileVisibility' => route('admin.api.accounts.profile.visibility', $account_admin),
            'avatarUpload' => route('admin.api.accounts.profile.avatar', $account_admin),
            'logs' => route('admin.api.accounts.logs.index', $account_admin),
            'logsExport' => route('admin.api.accounts.logs.export', $account_admin),
        ];
    @endphp

    <div class="detail-page" id="accountDetailRoot"
         data-account-id="{{ $account_admin->id }}"
         data-endpoints='@json($apiRoutes)'
         data-roles='@json($roles)'
         data-statuses='@json($accountStatuses)'>

        <div class="detail-header">
            <div class="header-info">
                <div class="header-avatar">
                    <img data-bind="avatar" src="https://via.placeholder.com/120" alt="avatar">
                </div>
                <div class="header-meta">
                    <h2>
                        <span data-bind="displayName">Đang tải...</span>
                        <span class="tag success" data-bind="roleBadge">Role</span>
                    </h2>
                    <p data-bind="email">—</p>
                    <div class="header-tags" data-bind="statusTags">
                        <span class="tag">Loading</span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a href="{{ route('admin.accounts.index') }}" class="btn btn-light">↩️ Quay lại</a>
                <button type="button" class="btn btn-light" data-action="verifyEmail">📨 Gửi email xác minh</button>
                <button type="button" class="btn btn-light" data-action="toggleActive" disabled>⏳ Đang tải...</button>
                <button type="button" class="btn btn-outline-danger" data-action="forceLogout">🛑 Force logout</button>
            </div>
        </div>

        <div class="metric-grid">
            <div class="metric-card">
                <h5>Last login</h5>
                <strong data-bind="lastLoginHuman">—</strong>
                <small data-bind="lastLoginExact"></small>
            </div>
            <div class="metric-card">
                <h5>Last password change</h5>
                <strong data-bind="lastPasswordChangeHuman">—</strong>
                <small data-bind="lastPasswordChangeExact"></small>
            </div>
            <div class="metric-card">
                <h5>Login attempts</h5>
                <strong data-bind="loginAttempts">0</strong>
            </div>
            <div class="metric-card">
                <h5>Security flags</h5>
                <strong data-bind="securityFlags">Không</strong>
            </div>
        </div>

        <div class="tabs" id="accountTabs">
            <button type="button" class="active" data-tab="account">Account</button>
            <button type="button" data-tab="profile">Profile</button>
            <button type="button" data-tab="logs">Activity Logs</button>
            <button type="button" data-tab="security">Security</button>
            <button type="button" data-tab="avatar">Avatar Manager</button>
        </div>

        <div class="tab-panels">
            <div class="tab-panel active" data-panel="account">
                <form id="accountInfoForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Họ tên</label>
                            <input type="text" name="name" placeholder="Nhập họ tên">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" required placeholder="example@email.com">
                        </div>
                        <div class="form-group">
                            <label>Vai trò</label>
                            <select name="role"></select>
                        </div>
                        <div class="form-group">
                            <label>Trạng thái hoạt động</label>
                            <select name="is_active">
                                <option value="1">Đang hoạt động</option>
                                <option value="0">Tạm khóa</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Account status</label>
                            <select name="account_status"></select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
                    </div>
                </form>
            </div>

            <div class="tab-panel" data-panel="profile">
                <form id="profileForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Họ tên đầy đủ</label>
                            <input type="text" name="full_name" placeholder="Full name">
                        </div>
                        <div class="form-group">
                            <label>Nickname</label>
                            <input type="text" name="nickname" placeholder="Nickname">
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <input type="text" name="phone" placeholder="0978...">
                        </div>
                        <div class="form-group">
                            <label>Giới tính</label>
                            <select name="gender">
                                <option value="">--</option>
                                <option value="male">Nam</option>
                                <option value="female">Nữ</option>
                                <option value="other">Khác</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ngày sinh</label>
                            <input type="date" name="birthday">
                        </div>
                        <div class="form-group">
                            <label>Khu vực</label>
                            <input type="text" name="location" placeholder="Hà Nội ...">
                        </div>
                        <div class="form-group" style="grid-column:1/-1;">
                            <label>Giới thiệu</label>
                            <textarea name="bio" rows="3" placeholder="Mô tả ngắn..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Công khai profile</label>
                            <select name="is_public">
                                <option value="1">Có</option>
                                <option value="0">Không</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Lưu profile</button>
                    </div>
                </form>
            </div>

            <div class="tab-panel" data-panel="logs">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;">
                    <div>
                        <h4 style="margin:0;color:#0f172a;">Activity timeline</h4>
                        <small style="color:#94a3b8;">Theo dõi mọi thay đổi quan trọng</small>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <select id="logTypeFilter" class="form-control" style="width:200px;">
                            <option value="">All types</option>
                            <option value="account.created">Account created</option>
                            <option value="account.updated">Account updated</option>
                            <option value="profile.updated">Profile updated</option>
                            <option value="account.role_changed">Role changed</option>
                            <option value="account.password_reset">Password reset</option>
                            <option value="account.force_logout">Force logout</option>
                        </select>
                        <a href="{{ $apiRoutes['logsExport'] }}" class="btn btn-light" target="_blank">⬇️ Export CSV</a>
                    </div>
                </div>
                <div class="timeline" id="logsTimeline">
                    <p style="color:#94a3b8;">Đang tải logs...</p>
                </div>
                <div class="form-actions" id="logsPagination"></div>
            </div>

            <div class="tab-panel" data-panel="security">
                <form id="passwordForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Mật khẩu mới</label>
                            <input type="password" name="password" placeholder="********" required>
                        </div>
                        <div class="form-group">
                            <label>Xác nhận mật khẩu</label>
                            <input type="password" name="password_confirmation" placeholder="********" required>
                        </div>
                        <div class="form-group">
                            <label>Force logout tất cả thiết bị?</label>
                            <select name="force_logout">
                                <option value="1">Có</option>
                                <option value="0" selected>Không</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">🔐 Reset password</button>
                    </div>
                </form>
                <hr style="margin:24px 0;">
                <div class="metric-grid">
                    <div class="metric-card">
                        <h5>Login attempts</h5>
                        <strong data-bind="loginAttempts">0</strong>
                    </div>
                    <div class="metric-card">
                        <h5>Security flags</h5>
                        <strong data-bind="securityFlags">Không</strong>
                    </div>
                </div>
            </div>

            <div class="tab-panel" data-panel="avatar">
                <div class="avatar-manager">
                    <div class="avatar-card">
                        <div class="avatar-preview">
                            <img data-bind="avatar" src="https://via.placeholder.com/120" alt="avatar">
                        </div>
                        <h4>Avatar chính</h4>
                        <form id="avatarForm" enctype="multipart/form-data">
                            <div class="form-group">
                                <input type="file" name="avatar" accept="image/*">
                            </div>
                            <div class="form-group">
                                <input type="file" name="sub_avatar" accept="image/*">
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="remove_avatar" value="1">
                                    Xóa avatar hiện tại
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="remove_sub_avatar" value="1">
                                    Xóa sub avatar
                                </label>
                            </div>
                            <input type="hidden" name="history_restore" value="">
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">⬆️ Cập nhật avatar</button>
                            </div>
                        </form>
                    </div>
                    <div class="avatar-card">
                        <h4>Avatar lịch sử</h4>
                        <div class="avatar-history" id="avatarHistory">
                            <p style="color:#94a3b8;">Chưa có lịch sử.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/plugin/relativeTime.js"></script>
    <script>
        dayjs.extend(dayjs_plugin_relativeTime);
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.getElementById('accountDetailRoot');
            if (!root) return;

            const endpoints = JSON.parse(root.dataset.endpoints || '{}');
            const roles = JSON.parse(root.dataset.roles || '[]');
            const statuses = JSON.parse(root.dataset.statuses || '[]');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '{{ csrf_token() }}';
            const accountImageBase = @json(rtrim(asset('admins/img/accounts'), '/'));

            const state = {
                account: null,
                profile: null,
                logs: [],
                logsMeta: null,
            };

            const toastEl = document.getElementById('toast');
            const notify = (message, type = 'success') => {
                if (!toastEl) return;
                toastEl.textContent = message;
                toastEl.style.background = type === 'error' ? '#b91c1c' : '#0f172a';
                toastEl.classList.add('show');
                setTimeout(() => toastEl.classList.remove('show'), 3000);
            };

            const fetchJson = async (url, options = {}) => {
                const headers = options.headers || {};
                headers['X-CSRF-TOKEN'] = csrfToken;
                if (!(options.body instanceof FormData)) {
                    headers['Content-Type'] = headers['Content-Type'] || 'application/json';
                }
                options.headers = headers;
                const res = await fetch(url, options);
                const data = res.status === 204 ? null : await res.json().catch(() => null);
                if (!res.ok) {
                    throw data?.message || 'Có lỗi xảy ra';
                }
                return data;
            };

            const populateSelect = (select, values) => {
                if (!select) return;
                select.innerHTML = '';
                values.forEach(value => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = value.charAt(0).toUpperCase() + value.slice(1);
                    select.appendChild(option);
                });
            };

            const renderAccount = () => {
                const account = state.account;
                if (!account) return;
                const displayName = account.profile?.full_name || account.name || account.email;
                const avatar = account.profile?.avatar || account.profile?.sub_avatar;
                const avatarUrl = avatar 
                    ? (avatar.startsWith('http') ? avatar : `${accountImageBase}/${avatar}`) 
                    : `https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&background=F3F4F6&color=0F172A&bold=true`;
                root.querySelectorAll('[data-bind="avatar"]').forEach(img => img.src = avatarUrl);
                root.querySelector('[data-bind="displayName"]').textContent = displayName;
                root.querySelector('[data-bind="email"]').textContent = account.email;
                root.querySelector('[data-bind="roleBadge"]').textContent = account.role;
                const tags = root.querySelector('[data-bind="statusTags"]');
                if (tags) {
                    tags.innerHTML = '';
                    const tagActive = document.createElement('span');
                    tagActive.className = `tag ${account.is_active ? 'success' : 'danger'}`;
                    tagActive.textContent = account.is_active ? 'Đang hoạt động' : 'Đã khóa';
                    tags.appendChild(tagActive);
                    const tagStatus = document.createElement('span');
                    tagStatus.className = 'tag';
                    tagStatus.textContent = account.account_status ?? '—';
                    tags.appendChild(tagStatus);
                }

                const verifyButton = document.querySelector('[data-action="verifyEmail"]');
                if (verifyButton) {
                    if (account.email_verified_at) {
                        verifyButton.disabled = true;
                        verifyButton.textContent = '✅ Đã xác minh';
                        verifyButton.classList.add('btn-success');
                    } else {
                        verifyButton.disabled = false;
                        verifyButton.textContent = '📨 Gửi email xác minh';
                        verifyButton.classList.remove('btn-success');
                    }
                }

                const toggleButton = document.querySelector('[data-action="toggleActive"]');
                if (toggleButton) {
                    toggleButton.disabled = false;
                    if (account.is_active) {
                        toggleButton.textContent = '🔒 Khóa tài khoản';
                        toggleButton.dataset.nextState = 'deactivate';
                    } else {
                        toggleButton.textContent = '🔓 Mở khóa tài khoản';
                        toggleButton.dataset.nextState = 'activate';
                    }
                }
                const setText = (selector, text) => {
                    const el = root.querySelector(`[data-bind="${selector}"]`);
                    if (el) el.textContent = text;
                };
                setText('lastLoginHuman', account.login_history ? dayjs(account.login_history).fromNow() : 'Chưa đăng nhập');
                setText('lastLoginExact', account.login_history ? dayjs(account.login_history).format('DD/MM/YYYY HH:mm') : '');
                setText('lastPasswordChangeHuman', account.last_password_changed_at ? dayjs(account.last_password_changed_at).fromNow() : 'Chưa từng đổi');
                setText('lastPasswordChangeExact', account.last_password_changed_at ? dayjs(account.last_password_changed_at).format('DD/MM/YYYY HH:mm') : '');
                setText('loginAttempts', account.login_attempts ?? 0);
                setText('securityFlags', (account.security_flags || []).join(', ') || 'Không');

                const accountForm = document.getElementById('accountInfoForm');
                if (accountForm) {
                    accountForm.name.value = account.name ?? '';
                    accountForm.email.value = account.email ?? '';
                    accountForm.role.value = account.role;
                    accountForm.is_active.value = account.is_active ? '1' : '0';
                    accountForm.account_status.value = account.account_status ?? '';
                }

                const historyContainer = document.getElementById('avatarHistory');
                if (historyContainer) {
                    const history = [];
                    if (account.profile?.avatar_history?.length) {
                        account.profile.avatar_history.forEach(file => {
                            history.push({ type: 'avatar', file, label: 'Avatar chính' });
                        });
                    }
                    if (account.profile?.sub_avatar_history?.length) {
                        account.profile.sub_avatar_history.forEach(file => {
                            history.push({ type: 'sub_avatar', file, label: 'Ảnh phụ' });
                        });
                    }

                    if (!history.length) {
                        historyContainer.innerHTML = '<p style="color:#94a3b8;">Chưa có lịch sử.</p>';
                    } else {
                        historyContainer.innerHTML = `
                            <div class="avatar-history-list">
                                ${history.map(item => `
                                    <div class="avatar-history-item">
                                        <img src="${accountImageBase}/${item.file}" alt="${item.label}">
                                        <div style="flex:1">
                                            <strong>${item.label}</strong>
                                            <div style="font-size:12px;color:#94a3b8;">${item.file}</div>
                                        </div>
                                        <button type="button" class="btn btn-light" data-history-restore="${item.type}">
                                            🔄 Khôi phục
                                        </button>
                                    </div>
                                `).join('')}
                            </div>
                        `;
                        historyContainer.querySelectorAll('[data-history-restore]').forEach(btn => {
                            btn.addEventListener('click', () => {
                                const field = btn.dataset.historyRestore;
                                const form = document.getElementById('avatarForm');
                                if (!form) return;
                                const input = form.querySelector('input[name="history_restore"]');
                                input.value = field;
                                form.requestSubmit();
                            });
                        });
                    }
                }
            };

            const renderProfile = () => {
                const profile = state.profile || {};
                const form = document.getElementById('profileForm');
                if (!form) return;
                form.full_name.value = profile.full_name ?? '';
                form.nickname.value = profile.nickname ?? '';
                form.phone.value = profile.phone ?? '';
                form.gender.value = profile.gender ?? '';
                form.birthday.value = profile.birthday ?? '';
                form.location.value = profile.location ?? '';
                form.bio.value = profile.bio ?? '';
                form.is_public.value = profile.is_public ? '1' : '0';
            };

            const renderLogs = () => {
                const container = document.getElementById('logsTimeline');
                if (!container) return;
                if (!state.logs.length) {
                    container.innerHTML = '<p style="color:#94a3b8;">Chưa có log nào.</p>';
                    return;
                }
                container.innerHTML = state.logs.map(log => `
                    <div class="timeline-item">
                        <h4>${log.type}</h4>
                        <small>${dayjs(log.created_at).format('DD/MM/YYYY HH:mm')} • ${log.admin_name ?? 'System'}</small>
                        ${log.payload ? `<pre class="log-payload">${JSON.stringify(log.payload, null, 2)}</pre>` : ''}
                    </div>
                `).join('');
                const pagination = document.getElementById('logsPagination');
                if (pagination && state.logsMeta) {
                    pagination.innerHTML = '';
                    if (state.logsMeta.prev_page_url) {
                        const btnPrev = document.createElement('button');
                        btnPrev.type = 'button';
                        btnPrev.className = 'btn btn-light';
                        btnPrev.textContent = '← Trước';
                        btnPrev.addEventListener('click', () => loadLogs(state.logsMeta.prev_page_url));
                        pagination.appendChild(btnPrev);
                    }
                    if (state.logsMeta.next_page_url) {
                        const btnNext = document.createElement('button');
                        btnNext.type = 'button';
                        btnNext.className = 'btn btn-light';
                        btnNext.textContent = 'Sau →';
                        btnNext.addEventListener('click', () => loadLogs(state.logsMeta.next_page_url));
                        pagination.appendChild(btnNext);
                    }
                }
            };

            const loadAccount = async () => {
                try {
                    const response = await fetchJson(endpoints.account);
                    state.account = response.data ?? response;
                    renderAccount();
                } catch (error) {
                    notify(error, 'error');
                }
            };

            const loadProfile = async () => {
                try {
                    const response = await fetchJson(endpoints.profile);
                    state.profile = response.data ?? response;
                    if (state.account) {
                        state.account.profile = state.profile;
                        renderAccount();
                    }
                    renderProfile();
                } catch (error) {
                    notify(error, 'error');
                }
            };

            const loadLogs = async (url = null) => {
                try {
                    const endpoint = url ?? endpoints.logs;
                    const type = document.getElementById('logTypeFilter')?.value;
                    const query = type ? `${endpoint}?type=${encodeURIComponent(type)}` : endpoint;
                    const response = await fetchJson(query);
                    state.logs = response.data ?? [];
                    state.logsMeta = response.meta ?? null;
                    renderLogs();
                } catch (error) {
                    notify(error, 'error');
                }
            };

            const handleForm = (formId, endpoint, method = 'POST', after = () => {}) => {
                const form = document.getElementById(formId);
                if (!form) return;
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    try {
                        let body;
                        if (form.enctype === 'multipart/form-data') {
                            body = new FormData(form);
                        } else {
                            const formData = new FormData(form);
                            body = JSON.stringify(Object.fromEntries(formData.entries()));
                        }
                        await fetchJson(endpoint, { method, body });
                        notify('Đã lưu thành công');
                        after();
                    } catch (error) {
                        notify(error, 'error');
                    }
                });
            };

            const switchTab = (target) => {
                document.querySelectorAll('#accountTabs button').forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.tab === target);
                });
                document.querySelectorAll('.tab-panel').forEach(panel => {
                    panel.classList.toggle('active', panel.dataset.panel === target);
                });
            };

            document.querySelectorAll('#accountTabs button').forEach(btn => {
                btn.addEventListener('click', () => switchTab(btn.dataset.tab));
            });

            populateSelect(document.querySelector('#accountInfoForm select[name="role"]'), roles);
            populateSelect(document.querySelector('#accountInfoForm select[name="account_status"]'), statuses);

            handleForm('accountInfoForm', endpoints.updateAccount, 'PUT', () => loadAccount());
            handleForm('profileForm', endpoints.profileUpdate, 'PUT', () => loadProfile());
            handleForm('passwordForm', endpoints.resetPassword, 'PATCH');

            const avatarForm = document.getElementById('avatarForm');
            if (avatarForm) {
                avatarForm.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    try {
                        await fetchJson(endpoints.avatarUpload, {
                            method: 'POST',
                            body: new FormData(avatarForm),
                        });
                        notify('Đã cập nhật avatar');
                        avatarForm.reset();
                        const restoreInput = avatarForm.querySelector('input[name="history_restore"]');
                        if (restoreInput) {
                            restoreInput.value = '';
                        }
                        loadAccount();
                    } catch (error) {
                        notify(error, 'error');
                    }
                });
            }

            document.getElementById('logTypeFilter')?.addEventListener('change', () => loadLogs());

            document.querySelector('[data-action="verifyEmail"]')?.addEventListener('click', async () => {
                try {
                    await fetchJson(endpoints.verifyEmail, { method: 'POST' });
                    notify('Đã gửi email xác minh tới người dùng');
                } catch (error) {
                    notify(error, 'error');
                }
            });

            document.querySelector('[data-action="toggleActive"]')?.addEventListener('click', async (event) => {
                const button = event.currentTarget;
                const action = button.dataset.nextState || 'deactivate';
                const confirmMessage = action === 'deactivate'
                    ? 'Bạn chắc chắn muốn KHÓA tài khoản này?'
                    : 'Bạn chắc chắn muốn MỞ KHÓA tài khoản này?';
                if (!confirm(confirmMessage)) {
                    return;
                }
                button.disabled = true;
                try {
                    await fetchJson(endpoints.toggle, { method: 'PATCH' });
                    notify('Đã cập nhật trạng thái tài khoản');
                    loadAccount();
                } catch (error) {
                    notify(error, 'error');
                } finally {
                    button.disabled = false;
                }
            });

            document.querySelector('[data-action="forceLogout"]')?.addEventListener('click', async () => {
                if (!confirm('Xác nhận force logout tất cả phiên?')) return;
                try {
                    await fetchJson(endpoints.forceLogout, { method: 'POST' });
                    notify('Đã force logout');
                } catch (error) {
                    notify(error, 'error');
                }
            });

            loadAccount();
            loadProfile();
            loadLogs();
        });
    </script>
@endpush
