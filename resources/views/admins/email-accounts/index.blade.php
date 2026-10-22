@extends('admins.layouts.master')

@section('title', 'Quản lý Tài khoản Email & SMTP')
@section('page-title', '📧 Quản lý Tài khoản Email')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/email-icon.webp') }}" type="image/x-icon">
@endpush

@push('styles')
<style>
    .nobi_email_stat_card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 100%;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .nobi_email_stat_card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.06);
    }
    .nobi_stat_icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .nobi_stat_info h3 {
        font-size: 22px;
        font-weight: 700;
        margin: 0;
        line-height: 1.2;
    }
    .nobi_stat_info span {
        font-size: 13px;
        color: #64748b;
        font-weight: 500;
    }

    /* Email Card List */
    .nobi_email_item {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px 24px;
        margin-bottom: 16px;
        box-shadow: 0 2px 6px rgba(15,23,42,0.03);
        transition: all 0.25s ease;
        position: relative;
    }
    .nobi_email_item:hover {
        box-shadow: 0 8px 22px rgba(15,23,42,0.08);
        border-color: #cbd5e1;
    }
    .nobi_email_item.is-default {
        border-color: #f59e0b;
        background: linear-gradient(180deg, #ffffff 0%, #fffdfa 100%);
    }
    .nobi_email_item.is-default::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        width: 5px;
        background: #f59e0b;
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .nobi_email_avatar {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        background: #eff6ff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .nobi_email_avatar.is-default {
        background: #fef3c7;
        color: #d97706;
    }

    .nobi_email_title {
        font-size: 16.5px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 2px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .nobi_email_sender {
        font-size: 13.5px;
        color: #475569;
        font-weight: 500;
    }
    .nobi_email_desc {
        font-size: 12.5px;
        color: #64748b;
        margin-top: 4px;
        line-height: 1.4;
    }

    .nobi_smtp_pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 12.5px;
        color: #334155;
    }
    .nobi_smtp_pill strong {
        color: #0f172a;
    }

    .nobi_badge_status {
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .nobi_badge_default {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    .nobi_badge_active {
        background: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    .nobi_badge_inactive {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    .btn-copy-email {
        background: transparent;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 2px 6px;
        font-size: 12px;
        border-radius: 4px;
        transition: all 0.15s ease;
    }
    .btn-copy-email:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Tài khoản Email & Cấu hình SMTP</h1>
            <p class="text-muted mb-0">Quản lý các hòm thư gửi thông báo đơn hàng, chăm sóc khách hàng và thử nghiệm kết nối SMTP.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.email-accounts.create') }}" class="btn btn-primary px-3 shadow-sm">
                <i class="fas fa-plus-circle me-1"></i> Thêm tài khoản email
            </a>
        </div>
    </div>

    <!-- Metric Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="nobi_email_stat_card">
                <div class="nobi_stat_info">
                    <span>Tổng tài khoản</span>
                    <h3 class="text-dark">{{ $stats['total'] ?? 0 }}</h3>
                </div>
                <div class="nobi_stat_icon bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-mail-bulk"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="nobi_email_stat_card">
                <div class="nobi_stat_info">
                    <span>Đang hoạt động</span>
                    <h3 class="text-success">{{ $stats['active'] ?? 0 }}</h3>
                </div>
                <div class="nobi_stat_icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="nobi_email_stat_card">
                <div class="nobi_stat_info">
                    <span>Tạm dừng</span>
                    <h3 class="text-secondary">{{ $stats['inactive'] ?? 0 }}</h3>
                </div>
                <div class="nobi_stat_icon bg-secondary bg-opacity-10 text-secondary">
                    <i class="fas fa-pause-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="nobi_email_stat_card">
                <div class="nobi_stat_info">
                    <span>Email mặc định</span>
                    <h3 class="text-warning text-truncate" style="max-width: 170px; font-size: 16px;" title="{{ $stats['default']->email ?? 'Chưa thiết lập' }}">
                        {{ $stats['default']->email ?? 'Chưa có' }}
                    </h3>
                </div>
                <div class="nobi_stat_icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-star"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.email-accounts.index') }}" class="row g-2 align-items-center">
                <div class="col-md-5 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" name="keyword" value="{{ request('keyword') }}" 
                               class="form-control border-start-0 ps-0" 
                               placeholder="Tìm theo email, tên người gửi...">
                    </div>
                </div>
                <div class="col-md-4 col-lg-3">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>🟢 Đang hoạt động</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>⚪ Tạm dừng</option>
                    </select>
                </div>
                <div class="col-md-3 col-lg-5 d-flex gap-2 justify-content-md-end">
                    <button type="submit" class="btn btn-primary px-3">
                        <i class="fas fa-filter me-1"></i> Lọc
                    </button>
                    @if(request()->hasAny(['keyword', 'status']))
                        <a href="{{ route('admin.email-accounts.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-1"></i> Đặt lại
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="fas fa-check-circle fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="fas fa-exclamation-triangle fs-5"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Accounts List -->
    @if($emailAccounts->isEmpty())
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <div class="py-4">
                <div class="mb-3">
                    <i class="fas fa-envelope-open-text text-muted" style="font-size: 56px;"></i>
                </div>
                <h4 class="fw-bold text-dark">Chưa tìm thấy tài khoản email nào</h4>
                <p class="text-muted mb-4">Hãy thêm tài khoản email đầu tiên để phục vụ gửi thông báo và tương tác khách hàng.</p>
                <a href="{{ route('admin.email-accounts.create') }}" class="btn btn-primary px-4 py-2">
                    <i class="fas fa-plus me-1"></i> Tạo tài khoản email ngay
                </a>
            </div>
        </div>
    @else
        <div class="email-list-wrapper">
            @foreach($emailAccounts as $account)
                <div class="nobi_email_item {{ $account->is_default ? 'is-default' : '' }}">
                    <div class="row align-items-center g-3">
                        <!-- Left Info -->
                        <div class="col-lg-5 col-md-6">
                            <div class="d-flex align-items-start gap-3">
                                <div class="nobi_email_avatar {{ $account->is_default ? 'is-default' : '' }}">
                                    <i class="fas {{ $account->is_default ? 'fa-star' : 'fa-envelope' }}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="nobi_email_title">
                                        <span id="email_text_{{ $account->id }}">{{ $account->email }}</span>
                                        <button type="button" class="btn-copy-email" onclick="copyToClipboard('{{ $account->email }}', this)" title="Sao chép email">
                                            <i class="far fa-copy"></i>
                                        </button>
                                    </div>
                                    <div class="nobi_email_sender">
                                        <i class="fas fa-user-tag text-muted me-1"></i> Tên người gửi: <strong>{{ $account->name }}</strong>
                                    </div>
                                    @if($account->description)
                                        <div class="nobi_email_desc">
                                            <i class="far fa-comment-alt text-muted me-1"></i> {{ $account->description }}
                                        </div>
                                    @endif
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        @if($account->is_default)
                                            <span class="nobi_badge_status nobi_badge_default">
                                                <i class="fas fa-star text-warning"></i> Mặc định hệ thống
                                            </span>
                                        @endif
                                        @if($account->is_active)
                                            <span class="nobi_badge_status nobi_badge_active">
                                                <i class="fas fa-circle text-success" style="font-size: 7px;"></i> Đang hoạt động
                                            </span>
                                        @else
                                            <span class="nobi_badge_status nobi_badge_inactive">
                                                <i class="fas fa-circle text-secondary" style="font-size: 7px;"></i> Tạm dừng
                                            </span>
                                        @endif
                                        <span class="badge bg-light text-secondary border">Thứ tự: {{ $account->order ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Middle SMTP Config Details -->
                        <div class="col-lg-4 col-md-6">
                            <div class="d-flex flex-column gap-2">
                                <div class="nobi_smtp_pill">
                                    <i class="fas fa-server text-primary"></i>
                                    <span>Máy chủ SMTP: 
                                        <strong>{{ $account->mail_host ?: (config('mail.mailers.smtp.host') ?: 'Theo .env') }}</strong>
                                    </span>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <div class="nobi_smtp_pill">
                                        <i class="fas fa-network-wired text-info"></i>
                                        <span>Port: <strong>{{ $account->mail_port ?: (config('mail.mailers.smtp.port') ?: '587') }}</strong></span>
                                    </div>
                                    <div class="nobi_smtp_pill">
                                        <i class="fas fa-shield-alt text-warning"></i>
                                        <span>Mã hóa: <strong class="text-uppercase">{{ $account->mail_encryption ?: (config('mail.mailers.smtp.encryption') ?: 'TLS') }}</strong></span>
                                    </div>
                                    @if($account->mail_host)
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 align-self-center">SMTP Riêng</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border align-self-center">Dùng .env</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Right Actions -->
                        <div class="col-lg-3 col-md-12 text-lg-end">
                            <div class="d-flex flex-wrap gap-2 justify-content-lg-end align-items-center">
                                <!-- Send Test Mail Button -->
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="openTestMailModal({{ $account->id }}, '{{ $account->email }}', '{{ $account->name }}')" 
                                        title="Gửi thư kiểm tra kết nối SMTP">
                                    <i class="fas fa-paper-plane me-1"></i> Test
                                </button>

                                <!-- Toggle Active Status -->
                                <form action="{{ route('admin.email-accounts.toggle-active', $account) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $account->is_active ? 'btn-outline-success' : 'btn-outline-secondary' }}" 
                                            title="{{ $account->is_active ? 'Bấm để tạm dừng tài khoản này' : 'Bấm để kích hoạt hoạt động' }}">
                                        <i class="fas {{ $account->is_active ? 'fa-toggle-on text-success' : 'fa-toggle-off' }}"></i>
                                        <span>{{ $account->is_active ? 'Bật' : 'Tắt' }}</span>
                                    </button>
                                </form>

                                <!-- Set Default -->
                                @if(!$account->is_default)
                                    <form action="{{ route('admin.email-accounts.set-default', $account) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Đặt làm tài khoản mặc định gửi thư">
                                            <i class="far fa-star"></i>
                                        </button>
                                    </form>
                                @endif

                                <!-- Edit -->
                                <a href="{{ route('admin.email-accounts.edit', $account) }}" class="btn btn-sm btn-outline-dark" title="Chỉnh sửa cấu hình">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <!-- Delete -->
                                @if(!$account->is_default)
                                    <form action="{{ route('admin.email-accounts.destroy', $account) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa tài khoản email [{{ $account->email }}]? Thao tác này không thể hoàn tác.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa tài khoản">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 d-flex justify-content-end">
            {{ $emailAccounts->links() }}
        </div>
    @endif
</div>

<!-- ============================================== -->
<!-- MODAL: GỬI EMAIL THỬ NGHIỆM (TEST MAIL) -->
<!-- ============================================== -->
<div class="modal fade" id="modalTestEmail" tabindex="-1" aria-labelledby="modalTestEmailLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTestEmailLabel">
                    <i class="fas fa-paper-plane text-primary me-2"></i> Gửi thử nghiệm kết nối SMTP
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formSendTestEmail" onsubmit="handleSendTestEmail(event)">
                <div class="modal-body py-3">
                    <p class="text-muted small mb-3">
                        Hệ thống sẽ sử dụng cấu hình SMTP của tài khoản <strong id="modal_sender_email" class="text-primary"></strong> để gửi 1 thư kiểm tra.
                    </p>

                    <div id="test_mail_alert" class="d-none alert mb-3 py-2 px-3 small rounded-3"></div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Gửi từ tài khoản:</label>
                        <input type="text" id="modal_sender_name" class="form-control bg-light" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Địa chỉ email người nhận thử nghiệm: <span class="text-danger">*</span></label>
                        <input type="email" id="test_recipient_email" class="form-control" 
                               value="{{ auth('web')->user()?->email ?? '' }}" 
                               placeholder="nhap-email-nhan-test@gmail.com" required>
                        <div class="form-text small">Mặc định gửi về email tài khoản quản trị của bạn hoặc email cá nhân để kiểm tra hộp thư đến (Inbox / Spam).</div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" id="btn_submit_test_email" class="btn btn-primary rounded-3 px-4">
                        <span id="btn_test_spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        <i class="fas fa-paper-plane me-1"></i> Bắt đầu gửi thử
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let currentTestAccountId = null;

    function openTestMailModal(accountId, email, name) {
        currentTestAccountId = accountId;
        document.getElementById('modal_sender_email').textContent = email;
        document.getElementById('modal_sender_name').value = name + ' <' + email + '>';
        
        const alertBox = document.getElementById('test_mail_alert');
        alertBox.className = 'd-none alert mb-3 py-2 px-3 small rounded-3';
        alertBox.innerHTML = '';

        const modal = new bootstrap.Modal(document.getElementById('modalTestEmail'));
        modal.show();
    }

    async function handleSendTestEmail(e) {
        e.preventDefault();
        if (!currentTestAccountId) return;

        const recipient = document.getElementById('test_recipient_email').value.trim();
        if (!recipient) return;

        const btnSubmit = document.getElementById('btn_submit_test_email');
        const spinner = document.getElementById('btn_test_spinner');
        const alertBox = document.getElementById('test_mail_alert');

        btnSubmit.disabled = true;
        spinner.classList.remove('d-none');
        alertBox.className = 'alert alert-info mb-3 py-2 px-3 small rounded-3';
        alertBox.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Đang kết nối tới máy chủ SMTP và gửi thư thử nghiệm... Vui lòng chờ!';

        try {
            const url = "{{ url('admin/email-accounts') }}/" + currentTestAccountId + "/send-test";
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ recipient_email: recipient })
            });

            const data = await res.json();

            if (res.ok && data.success) {
                alertBox.className = 'alert alert-success mb-3 py-2 px-3 small rounded-3';
                alertBox.innerHTML = '<i class="fas fa-check-circle me-1"></i> <strong>Thành công!</strong> ' + data.message;
            } else {
                alertBox.className = 'alert alert-danger mb-3 py-2 px-3 small rounded-3';
                alertBox.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> <strong>Thất bại:</strong> ' + (data.message || 'Không thể gửi email kiểm tra.');
            }
        } catch (err) {
            alertBox.className = 'alert alert-danger mb-3 py-2 px-3 small rounded-3';
            alertBox.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> <strong>Lỗi kết nối:</strong> ' + err.message;
        } finally {
            btnSubmit.disabled = false;
            spinner.classList.add('d-none');
        }
    }

    function copyToClipboard(text, btnElement) {
        navigator.clipboard.writeText(text).then(() => {
            const originalHtml = btnElement.innerHTML;
            btnElement.innerHTML = '<i class="fas fa-check text-success"></i>';
            setTimeout(() => {
                btnElement.innerHTML = originalHtml;
            }, 1500);
        });
    }
</script>
@endsection
