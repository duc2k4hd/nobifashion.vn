@php
    $isEdit = $emailAccount->exists;
@endphp

@push('styles')
<style>
    .nobi_preset_btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        border-radius: 8px;
        font-size: 12.5px;
        font-weight: 600;
        color: #334155;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .nobi_preset_btn:hover {
        background: #ffffff;
        border-color: #3b82f6;
        color: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(37,99,235,0.12);
    }
    .nobi_preset_btn.active {
        background: #eff6ff;
        border-color: #2563eb;
        color: #1d4ed8;
    }
</style>
@endpush

<form id="emailAccountForm" action="{{ $isEdit ? route('admin.email-accounts.update', $emailAccount) : route('admin.email-accounts.store') }}" method="POST">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="row g-4">
        <!-- ============================================== -->
        <!-- LEFT COLUMN: THÔNG TIN CHÍNH & CẤU HÌNH SMTP -->
        <!-- ============================================== -->
        <div class="col-lg-8">
            <!-- Card 1: Thông tin hiển thị -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-id-card text-primary me-2"></i> Thông tin hiển thị & Người gửi
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Địa chỉ Email người gửi <span class="text-danger">*</span></label>
                            <input type="email" id="field_email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                   value="{{ old('email', $emailAccount->email) }}" 
                                   required 
                                   placeholder="info@nobifashion.vn">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">Địa chỉ email sẽ hiển thị ở mục người gửi (From Address).</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tên hiển thị người gửi <span class="text-danger">*</span></label>
                            <input type="text" id="field_name" name="name" class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $emailAccount->name) }}" 
                                   required 
                                   placeholder="NOBI FASHION - Chăm sóc khách hàng">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">Tên thương hiệu hoặc bộ phận (From Name).</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Mô tả công dụng</label>
                            <textarea name="description" class="form-control" rows="2" 
                                      placeholder="Ví dụ: Chuyên gửi thông báo đơn hàng và tiếp nhận phản hồi từ khách...">{{ old('description', $emailAccount->description) }}</textarea>
                            <div class="form-text small">Ghi chú nội bộ để phân biệt các hòm thư trong hệ thống.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Cấu hình máy chủ SMTP -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-server text-primary me-2"></i> Thông số kết nối máy chủ SMTP
                    </h5>
                    <span class="badge bg-light text-secondary border">Tùy chọn nâng cao</span>
                </div>
                <div class="card-body p-4">
                    <!-- Presets 1-Click -->
                    <div class="mb-4 p-3 bg-light rounded-3 border">
                        <label class="form-label fw-semibold small text-dark d-block mb-2">
                            <i class="fas fa-magic text-warning me-1"></i> Điền nhanh cấu hình mẫu (1-Click Presets):
                        </label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="nobi_preset_btn" onclick="applyPreset('gmail')">
                                <i class="fab fa-google text-danger"></i> Gmail / Google Workspace
                            </button>
                            <button type="button" class="nobi_preset_btn" onclick="applyPreset('outlook')">
                                <i class="fab fa-microsoft text-primary"></i> Microsoft 365 / Outlook
                            </button>
                            <button type="button" class="nobi_preset_btn" onclick="applyPreset('yandex')">
                                <i class="fas fa-envelope text-danger"></i> Yandex Mail
                            </button>
                            <button type="button" class="nobi_preset_btn" onclick="applyPreset('zoho')">
                                <i class="fas fa-briefcase text-info"></i> Zoho Mail
                            </button>
                            <button type="button" class="nobi_preset_btn" onclick="applyPreset('cpanel')">
                                <i class="fas fa-globe text-success"></i> Webmail riêng (Domain)
                            </button>
                            <button type="button" class="nobi_preset_btn text-muted" onclick="applyPreset('default')">
                                <i class="fas fa-undo"></i> Dùng từ .env
                            </button>
                        </div>
                        <div id="preset_note" class="small mt-2 text-primary d-none"></div>
                    </div>

                    <div class="alert alert-info py-2 px-3 small rounded-3 d-flex align-items-start gap-2 mb-3">
                        <i class="fas fa-info-circle mt-1"></i>
                        <div>
                            <strong>Quy tắc kế thừa:</strong> Nếu để trống các ô bên dưới, hệ thống sẽ tự động sử dụng cấu hình mặc định trong file môi trường <code>.env</code> (<code>MAIL_HOST</code>, <code>MAIL_PORT</code>, <code>MAIL_USERNAME</code>, <code>MAIL_PASSWORD</code>). Chỉ điền khi bạn muốn tài khoản này gửi qua máy chủ SMTP riêng.
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">SMTP Host</label>
                            <input type="text" id="field_mail_host" name="mail_host" class="form-control" 
                                   value="{{ old('mail_host', $emailAccount->mail_host) }}" 
                                   placeholder="{{ config('mail.mailers.smtp.host') ?? 'smtp.gmail.com' }}">
                            <div class="form-text small">Địa chỉ máy chủ gửi thư (VD: <code>smtp.gmail.com</code>).</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">SMTP Port</label>
                            <input type="number" id="field_mail_port" name="mail_port" class="form-control" 
                                   value="{{ old('mail_port', $emailAccount->mail_port) }}" 
                                   placeholder="{{ config('mail.mailers.smtp.port') ?? '587' }}"
                                   min="1" max="65535">
                            <div class="form-text small">Cổng kết nối (thường là <code>587</code> hoặc <code>465</code>).</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SMTP Username (Tài khoản đăng nhập)</label>
                            <input type="text" id="field_mail_username" name="mail_username" class="form-control" 
                                   value="{{ old('mail_username', $emailAccount->mail_username) }}" 
                                   placeholder="{{ config('mail.mailers.smtp.username') ?? 'user@gmail.com' }}">
                            <div class="form-text small">Tài khoản xác thực SMTP (thường là địa chỉ email đầy đủ).</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SMTP Password (Mật khẩu xác thực)</label>
                            <div class="input-group">
                                <input type="password" id="field_mail_password" name="mail_password" class="form-control" 
                                       value="" 
                                       placeholder="{{ $isEdit && $emailAccount->mail_password ? '•••••••• (Để trống nếu giữ nguyên)' : 'Nhập mật khẩu hoặc App Password' }}">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('field_mail_password', this)">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text small">
                                @if($isEdit && $emailAccount->mail_password)
                                    <span class="text-success"><i class="fas fa-check-circle"></i> Đã lưu mật khẩu mã hóa an toàn.</span> Chỉ nhập khi muốn đổi mật khẩu mới.
                                @else
                                    Để trống = Dùng mật khẩu mặc định từ <code>.env</code>.
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Giao thức mã hóa (Encryption)</label>
                            <select id="field_mail_encryption" name="mail_encryption" class="form-select">
                                <option value="">-- Mặc định theo .env ({{ config('mail.mailers.smtp.encryption') ?: 'tls' }}) --</option>
                                <option value="tls" {{ old('mail_encryption', $emailAccount->mail_encryption) === 'tls' ? 'selected' : '' }}>TLS (Khuyến nghị cho cổng 587)</option>
                                <option value="ssl" {{ old('mail_encryption', $emailAccount->mail_encryption) === 'ssl' ? 'selected' : '' }}>SSL (Khuyến nghị cho cổng 465)</option>
                            </select>
                            <div class="form-text small">Phương thức bảo mật kết nối đến máy chủ thư.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- RIGHT COLUMN: XUẤT BẢN & LIVE TEST -->
        <!-- ============================================== -->
        <div class="col-lg-4">
            <!-- Card 3: Trạng thái & Tùy chọn -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-sliders-h text-primary me-2"></i> Trạng thái & Cài đặt
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="field_is_active" name="is_active" value="1" 
                               {{ old('is_active', $emailAccount->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="field_is_active">
                            Kích hoạt hoạt động
                        </label>
                        <div class="form-text small">Cho phép hệ thống sử dụng tài khoản này để gửi thư.</div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="field_is_default" name="is_default" value="1" 
                               {{ old('is_default', $emailAccount->is_default) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="field_is_default">
                            Đặt làm email gửi mặc định
                        </label>
                        <div class="form-text small">Hệ thống sẽ ưu tiên dùng hòm thư này khi các module không chỉ định cụ thể.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Thứ tự hiển thị (Order)</label>
                        <input type="number" name="order" class="form-control" 
                               value="{{ old('order', $emailAccount->order ?? 0) }}" 
                               min="0" placeholder="0">
                        <div class="form-text small">Số nhỏ hơn sẽ được ưu tiên hiển thị trước.</div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                            <i class="fas fa-save me-1"></i> {{ $isEdit ? 'Lưu thay đổi' : 'Tạo tài khoản email' }}
                        </button>
                        <a href="{{ route('admin.email-accounts.index') }}" class="btn btn-outline-secondary py-2">
                            <i class="fas fa-arrow-left me-1"></i> Quay lại danh sách
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card 4: Kiểm tra kết nối trực tiếp (Live Test) -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-vial text-warning me-2"></i> Thử nghiệm kết nối trực tiếp
                    </h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">
                        Kiểm tra ngay tính chính xác của các thông số SMTP vừa nhập ở bên trái trước khi bấm Lưu.
                    </p>

                    <div id="live_test_alert" class="alert d-none py-2 px-3 small rounded-3 mb-3"></div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email nhận kiểm tra: <span class="text-danger">*</span></label>
                        <input type="email" id="live_test_recipient" class="form-control form-control-sm" 
                               value="{{ auth('web')->user()?->email ?? '' }}" 
                               placeholder="nhap-email-nhan@gmail.com">
                    </div>

                    <button type="button" id="btn_run_live_test" class="btn btn-outline-primary w-100 btn-sm py-2" onclick="handleLiveTestConnection()">
                        <span id="live_test_spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        <i class="fas fa-bolt me-1"></i> Bắt đầu kiểm tra kết nối
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'far fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'far fa-eye';
        }
    }

    function applyPreset(type) {
        const hostInput = document.getElementById('field_mail_host');
        const portInput = document.getElementById('field_mail_port');
        const encSelect = document.getElementById('field_mail_encryption');
        const noteBox = document.getElementById('preset_note');

        noteBox.classList.remove('d-none');

        switch (type) {
            case 'gmail':
                hostInput.value = 'smtp.gmail.com';
                portInput.value = '587';
                encSelect.value = 'tls';
                noteBox.innerHTML = '<strong>💡 Lưu ý Gmail:</strong> Tài khoản Google cần bật Xác thực 2 bước (2FA) và tạo <strong>Mật khẩu ứng dụng (App Password)</strong> gồm 16 ký tự để điền vào ô Password.';
                break;
            case 'outlook':
                hostInput.value = 'smtp.office365.com';
                portInput.value = '587';
                encSelect.value = 'tls';
                noteBox.innerHTML = '<strong>💡 Lưu ý Microsoft 365:</strong> Đảm bảo tài khoản của bạn được cấp quyền gửi SMTP Authenticated Submission.';
                break;
            case 'yandex':
                hostInput.value = 'smtp.yandex.com';
                portInput.value = '465';
                encSelect.value = 'ssl';
                noteBox.innerHTML = '<strong>💡 Lưu ý Yandex:</strong> Cần tạo App Password trong phần Security của tài khoản Yandex ID.';
                break;
            case 'zoho':
                hostInput.value = 'smtppro.zoho.com';
                portInput.value = '465';
                encSelect.value = 'ssl';
                noteBox.innerHTML = '<strong>💡 Lưu ý Zoho:</strong> Khuyên dùng Port 465 SSL với Zoho Mail Organization.';
                break;
            case 'cpanel':
                const emailVal = document.getElementById('field_email').value.trim();
                let domain = 'yourdomain.com';
                if (emailVal.includes('@')) {
                    domain = emailVal.split('@')[1];
                }
                hostInput.value = 'mail.' + domain;
                portInput.value = '465';
                encSelect.value = 'ssl';
                noteBox.innerHTML = '<strong>💡 Webmail:</strong> Đã cấu hình theo tên miền <code>mail.' + domain + '</code> (Port 465 SSL). Hãy kiểm tra lại bản ghi DNS hoặc chứng chỉ SSL của mail server.';
                break;
            case 'default':
                hostInput.value = '';
                portInput.value = '';
                encSelect.value = '';
                noteBox.innerHTML = 'Đã hoàn tác về giá trị mặc định được thiết lập trong tệp <code>.env</code>.';
                break;
        }
    }

    async function handleLiveTestConnection() {
        const recipient = document.getElementById('live_test_recipient').value.trim();
        const fromEmail = document.getElementById('field_email').value.trim();

        const alertBox = document.getElementById('live_test_alert');
        const btn = document.getElementById('btn_run_live_test');
        const spinner = document.getElementById('live_test_spinner');

        if (!fromEmail) {
            alert('Vui lòng nhập Địa chỉ email người gửi ở Card bên trái trước khi kiểm tra!');
            document.getElementById('field_email').focus();
            return;
        }

        if (!recipient) {
            alert('Vui lòng nhập địa chỉ email nhận kiểm tra!');
            document.getElementById('live_test_recipient').focus();
            return;
        }

        btn.disabled = true;
        spinner.classList.remove('d-none');
        alertBox.className = 'alert alert-info py-2 px-3 small rounded-3 mb-3';
        alertBox.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang kết nối máy chủ SMTP và gửi thư test...';

        const payload = {
            email: fromEmail,
            name: document.getElementById('field_name').value.trim(),
            recipient_email: recipient,
            mail_host: document.getElementById('field_mail_host').value.trim(),
            mail_port: document.getElementById('field_mail_port').value.trim(),
            mail_username: document.getElementById('field_mail_username').value.trim(),
            mail_password: document.getElementById('field_mail_password').value,
            mail_encryption: document.getElementById('field_mail_encryption').value,
        };

        try {
            const res = await fetch("{{ route('admin.email-accounts.test-connection') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (res.ok && data.success) {
                alertBox.className = 'alert alert-success py-2 px-3 small rounded-3 mb-3';
                alertBox.innerHTML = '<i class="fas fa-check-circle me-1"></i> <strong>Thành công!</strong> ' + data.message;
            } else {
                alertBox.className = 'alert alert-danger py-2 px-3 small rounded-3 mb-3';
                alertBox.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> <strong>Thất bại:</strong> ' + (data.message || 'Lỗi cấu hình SMTP.');
            }
        } catch (err) {
            alertBox.className = 'alert alert-danger py-2 px-3 small rounded-3 mb-3';
            alertBox.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> <strong>Lỗi:</strong> ' + err.message;
        } finally {
            btn.disabled = false;
            spinner.classList.add('d-none');
        }
    }
</script>
