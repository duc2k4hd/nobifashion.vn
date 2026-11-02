@extends('admins.layouts.master')

@section('title', 'Trung tâm kiểm duyệt bình luận Reels')

@section('content')
<div class="nobifashion_admin_reels_wrapper container-fluid px-3 py-3">
    <!-- Header -->
    <div class="nobifashion_admin_reels_page_header mb-3">
        <div>
            <h1 class="nobifashion_admin_reels_page_title">
                <i class="fa-solid fa-shield-halved text-primary me-2"></i>Trung tâm kiểm duyệt bình luận
            </h1>
            <div class="nobifashion_admin_reels_breadcrumb">
                <a href="{{ route('admin.reels.dashboard') }}">Reels</a>
                <span>/</span>
                <span>Kiểm duyệt tự động</span>
            </div>
        </div>
        <div class="nobifashion_admin_reels_page_actions">
            <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary" data-bs-toggle="modal" data-bs-target="#createRuleModal">
                <i class="fa-solid fa-plus"></i> Thêm quy tắc
            </button>
            <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary" data-bs-toggle="modal" data-bs-target="#createWhitelistModal">
                <i class="fa-solid fa-file-circle-check"></i> Thêm từ ngoại lệ
            </button>
        </div>
    </div>

    <!-- Subnav Tabs -->
    @include('admins.reels.partials.subnav')

    <!-- Flash message -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Moderation Tabs Navigation -->
    <div class="nobifashion_admin_reels_card mb-3">
        <ul class="nav nav-tabs border-0 px-3 pt-2" id="moderationTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'rules' ? 'active' : '' }}" href="{{ route('admin.reels.moderation.index', ['tab' => 'rules']) }}">
                    <i class="fa-solid fa-list-check me-1"></i> Quy tắc từ khóa ({{ $rules->count() }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'whitelist' ? 'active' : '' }}" href="{{ route('admin.reels.moderation.index', ['tab' => 'whitelist']) }}">
                    <i class="fa-solid fa-shield-heart me-1"></i> Danh sách ngoại lệ ({{ $whitelists->count() }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'logs' ? 'active' : '' }}" href="{{ route('admin.reels.moderation.index', ['tab' => 'logs']) }}">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Nhật ký kiểm duyệt
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'tester' ? 'active' : '' }}" href="{{ route('admin.reels.moderation.index', ['tab' => 'tester']) }}">
                    <i class="fa-solid fa-vial-circle-check me-1 text-primary"></i> Công cụ thử nghiệm Rule (Tester)
                </a>
            </li>
        </ul>
    </div>

    <!-- TAB 1: RULES -->
    @if($activeTab === 'rules')
    <div class="nobifashion_admin_reels_card">
        <div class="nobifashion_admin_reels_card_header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="nobifashion_admin_reels_card_title">Danh sách bộ lọc kiểm duyệt tự động</h3>
                <p class="text-muted small mb-0">Hệ thống sẽ tự động quét bình luận theo regex / từ khóa để chặn hoặc gắn cờ chờ duyệt.</p>
            </div>
            <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary btn-sm" data-bs-toggle="modal" data-bs-target="#createRuleModal">
                <i class="fa-solid fa-plus me-1"></i> Tạo quy tắc
            </button>
        </div>
        <div class="nobifashion_admin_reels_card_body p-0">
            <div class="table-responsive">
                <table class="nobifashion_admin_reels_table">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th>Mẫu nhận diện (Pattern / Từ khóa)</th>
                            <th>Phân loại</th>
                            <th>Ngôn ngữ</th>
                            <th>Mức độ</th>
                            <th>Hành động áp dụng</th>
                            <th>Trạng thái</th>
                            <th width="120" class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rules as $rule)
                            <tr>
                                <td class="text-muted small">#{{ $rule->id }}</td>
                                <td>
                                    <code class="px-2 py-1 bg-light rounded text-dark fw-bold">{{ $rule->pattern }}</code>
                                </td>
                                <td>
                                    @php
                                        $typeMap = [
                                            'profanity' => ['badge' => 'danger', 'name' => 'Từ ngữ thô tục'],
                                            'link' => ['badge' => 'warning', 'name' => 'Spam link'],
                                            'harassment' => ['badge' => 'danger', 'name' => 'Quấy rối / Công kích'],
                                            'spam' => ['badge' => 'warning', 'name' => 'Tin rác / Lặp lại'],
                                            'custom' => ['badge' => 'info', 'name' => 'Tùy chỉnh'],
                                        ];
                                        $typeInfo = $typeMap[$rule->rule_type] ?? ['badge' => 'secondary', 'name' => $rule->rule_type];
                                    @endphp
                                    <span class="badge bg-{{ $typeInfo['badge'] }}">{{ $typeInfo['name'] }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ strtoupper($rule->language ?? 'ALL') }}</span>
                                </td>
                                <td>
                                    @if($rule->severity === 'high')
                                        <span class="badge bg-danger">Nghiêm trọng</span>
                                    @elseif($rule->severity === 'medium')
                                        <span class="badge bg-warning text-dark">Trung bình</span>
                                    @else
                                        <span class="badge bg-info">Thấp</span>
                                    @endif
                                </td>
                                <td>
                                    @if($rule->action === 'block')
                                        <span class="badge bg-danger"><i class="fa-solid fa-ban me-1"></i> Chặn ngay (Block)</span>
                                    @elseif($rule->action === 'review')
                                        <span class="badge bg-warning text-dark"><i class="fa-solid fa-hourglass-half me-1"></i> Giữ để duyệt</span>
                                    @else
                                        <span class="badge bg-secondary"><i class="fa-solid fa-flag me-1"></i> Gắn cờ cảnh báo</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('admin.reels.moderation.rules.toggle', $rule->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $rule->is_active ? 'btn-success' : 'btn-outline-secondary' }} rounded-pill px-2 py-0" style="font-size: 11px;">
                                            <i class="fa-solid {{ $rule->is_active ? 'fa-check' : 'fa-xmark' }} me-1"></i>{{ $rule->is_active ? 'Bật' : 'Tắt' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="openEditRuleModal({{ json_encode($rule) }})" title="Chỉnh sửa">
                                        <i class="fa-solid fa-pencil"></i>
                                    </button>
                                    <form action="{{ route('admin.reels.moderation.rules.destroy', $rule->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa quy tắc này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-shield-cat fa-2x mb-2 d-block text-secondary"></i>
                                    Chưa có quy tắc kiểm duyệt nào được thiết lập. Hãy tạo quy tắc mới đầu tiên!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- TAB 2: WHITELIST -->
    @if($activeTab === 'whitelist')
    <div class="nobifashion_admin_reels_card">
        <div class="nobifashion_admin_reels_card_header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="nobifashion_admin_reels_card_title">Danh sách từ khóa ngoại lệ (Whitelist)</h3>
                <p class="text-muted small mb-0">Các cụm từ hoặc ngữ cảnh thời trang hợp lệ không bao giờ bị bộ lọc tự động gắn cờ hoặc chặn.</p>
            </div>
            <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary btn-sm" data-bs-toggle="modal" data-bs-target="#createWhitelistModal">
                <i class="fa-solid fa-plus me-1"></i> Thêm từ ngoại lệ
            </button>
        </div>
        <div class="nobifashion_admin_reels_card_body p-0">
            <div class="table-responsive">
                <table class="nobifashion_admin_reels_table">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th>Cụm từ ngoại lệ</th>
                            <th>Lý do / Ghi chú ngữ cảnh</th>
                            <th>Ngày thêm</th>
                            <th width="100" class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($whitelists as $item)
                            <tr>
                                <td class="text-muted small">#{{ $item->id }}</td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-6">
                                        {{ $item->term }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $item->reason ?? 'Không có ghi chú' }}</td>
                                <td class="small text-muted">{{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '—' }}</td>
                                <td class="text-end">
                                    <form action="{{ route('admin.reels.moderation.whitelist.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa từ ngoại lệ này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-list-check fa-2x mb-2 d-block text-secondary"></i>
                                    Chưa có từ khóa ngoại lệ nào trong danh sách.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- TAB 3: LOGS -->
    @if($activeTab === 'logs')
    <div class="nobifashion_admin_reels_card">
        <div class="nobifashion_admin_reels_card_header">
            <h3 class="nobifashion_admin_reels_card_title">Nhật ký xử lý kiểm duyệt bình luận</h3>
            <p class="text-muted small mb-0">Lịch sử mọi bình luận bị bộ lọc tự động phát hiện vi phạm và xử lý.</p>
        </div>
        <div class="nobifashion_admin_reels_card_body p-0">
            <div class="table-responsive">
                <table class="nobifashion_admin_reels_table">
                    <thead>
                        <tr>
                            <th width="60">Log ID</th>
                            <th>Thời gian</th>
                            <th>Người gửi</th>
                            <th>Nội dung bình luận bị chặn / gắn cờ</th>
                            <th>Quy tắc kích hoạt</th>
                            <th>Hành động áp dụng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="text-muted small">#{{ $log->id }}</td>
                                <td class="small text-nowrap text-muted">{{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '—' }}</td>
                                <td>
                                    @if($log->anonymousUser)
                                        <a href="{{ route('admin.reels.anonymous-users.show', $log->anonymousUser->id) }}" class="text-decoration-none">
                                            <span class="badge bg-secondary">
                                                <i class="fa-solid fa-user-secret me-1"></i>{{ $log->anonymousUser->display_name ?? 'Khách ẩn danh' }}
                                            </span>
                                        </a>
                                    @else
                                        <span class="text-muted">Không xác định</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-break bg-light p-2 rounded small" style="max-width: 400px;">
                                        {{ $log->comment ? $log->comment->content : ($log->comment_text ?? '—') }}
                                    </div>
                                </td>
                                <td>
                                    @if($log->rule)
                                        <code class="text-danger fw-bold">{{ $log->rule->pattern }}</code>
                                        <span class="badge bg-light text-dark border ms-1">{{ $log->rule->rule_type }}</span>
                                    @else
                                        <span class="text-muted">Quy tắc đã bị xóa (#{{ $log->rule_id }})</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->action_taken === 'block')
                                        <span class="badge bg-danger"><i class="fa-solid fa-ban me-1"></i> Đã chặn</span>
                                    @elseif($log->action_taken === 'review')
                                        <span class="badge bg-warning text-dark"><i class="fa-solid fa-hourglass-half me-1"></i> Giữ chờ duyệt</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $log->action_taken }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-clipboard-check fa-2x mb-2 d-block text-secondary"></i>
                                    Chưa có nhật ký kiểm duyệt nào được ghi lại.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
                <div class="p-3 border-top">
                    {{ $logs->appends(['tab' => 'logs'])->links() }}
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- TAB 4: RULE TESTER -->
    @if($activeTab === 'tester')
    <div class="row">
        <div class="col-lg-6">
            <div class="nobifashion_admin_reels_card mb-3">
                <div class="nobifashion_admin_reels_card_header">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-vial-circle-check text-primary me-2"></i>Thử nghiệm bộ lọc kiểm duyệt
                    </h3>
                    <p class="text-muted small mb-0">Nhập văn bản mẫu để kiểm tra xem hệ thống sẽ phân loại, phát hiện từ khóa cấm hoặc áp dụng hành động gì.</p>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    <form id="testerForm">
                        <div class="mb-3">
                            <label class="nobifashion_admin_reels_form_label">Nội dung văn bản cần kiểm thử</label>
                            <textarea id="testerInput" class="nobifashion_admin_reels_form_control" rows="5" placeholder="Ví dụ: Áo này xấu vl, liên hệ zalo 0987654321 mua đồ giá rẻ..."></textarea>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" id="btnRunTest" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                                <i class="fa-solid fa-bolt me-1"></i> Chạy kiểm tra ngay
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="nobifashion_admin_reels_card mb-3">
                <div class="nobifashion_admin_reels_card_header">
                    <h3 class="nobifashion_admin_reels_card_title">Kết quả phân tích</h3>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    <div id="testerResultPlaceholder" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-magnifying-glass fa-2x mb-2 d-block text-secondary"></i>
                        Nhập văn bản bên trái và bấm <strong>"Chạy kiểm tra ngay"</strong> để xem kết quả đánh giá thời gian thực.
                    </div>
                    <div id="testerResultContainer" class="d-none">
                        <!-- Loaded dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modal: Create Rule -->
<div class="modal fade" id="createRuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.reels.moderation.rules.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-plus text-primary me-2"></i>Thêm quy tắc kiểm duyệt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="nobifashion_admin_reels_form_label">Mẫu nhận diện (Pattern / Từ khóa) <span class="text-danger">*</span></label>
                    <input type="text" name="pattern" class="nobifashion_admin_reels_form_control" required placeholder="Ví dụ: đm, vl, lừa đảo, hoặc regex /\b(dm|vcl)\b/i">
                    <small class="text-muted">Hỗ trợ từ khóa nguyên chuỗi hoặc cú pháp Regular Expression.</small>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="nobifashion_admin_reels_form_label">Loại quy tắc</label>
                        <select name="rule_type" class="nobifashion_admin_reels_form_control">
                            <option value="profanity">Từ ngữ thô tục / Nhạy cảm</option>
                            <option value="link">Spam đường link / URL</option>
                            <option value="harassment">Quấy rối / Đả kích cá nhân</option>
                            <option value="spam">Nội dung tin rác / Lặp lại</option>
                            <option value="custom">Quy tắc tùy chỉnh</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="nobifashion_admin_reels_form_label">Ngôn ngữ</label>
                        <select name="language" class="nobifashion_admin_reels_form_control">
                            <option value="vi">Tiếng Việt (vi)</option>
                            <option value="en">Tiếng Anh (en)</option>
                            <option value="all">Tất cả ngôn ngữ</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="nobifashion_admin_reels_form_label">Mức độ nghiêm trọng</label>
                        <select name="severity" class="nobifashion_admin_reels_form_control">
                            <option value="high">Nghiêm trọng (High)</option>
                            <option value="medium" selected>Trung bình (Medium)</option>
                            <option value="low">Thấp (Low)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="nobifashion_admin_reels_form_label">Hành động xử lý</label>
                        <select name="action" class="nobifashion_admin_reels_form_control">
                            <option value="block">Chặn ngay lập tức (Block)</option>
                            <option value="review" selected>Giữ lại để Admin duyệt (Review)</option>
                            <option value="warn">Chỉ gắn cờ cảnh báo (Warn)</option>
                        </select>
                    </div>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="createRuleActive" checked>
                    <label class="form-check-label" for="createRuleActive">Kích hoạt quy tắc ngay sau khi lưu</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">Lưu quy tắc</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Rule -->
<div class="modal fade" id="editRuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editRuleForm" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-pencil text-primary me-2"></i>Chỉnh sửa quy tắc kiểm duyệt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="nobifashion_admin_reels_form_label">Mẫu nhận diện <span class="text-danger">*</span></label>
                    <input type="text" id="editRulePattern" name="pattern" class="nobifashion_admin_reels_form_control" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="nobifashion_admin_reels_form_label">Loại quy tắc</label>
                        <select id="editRuleType" name="rule_type" class="nobifashion_admin_reels_form_control">
                            <option value="profanity">Từ ngữ thô tục / Nhạy cảm</option>
                            <option value="link">Spam đường link / URL</option>
                            <option value="harassment">Quấy rối / Đả kích cá nhân</option>
                            <option value="spam">Nội dung tin rác / Lặp lại</option>
                            <option value="custom">Quy tắc tùy chỉnh</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="nobifashion_admin_reels_form_label">Ngôn ngữ</label>
                        <select id="editRuleLang" name="language" class="nobifashion_admin_reels_form_control">
                            <option value="vi">Tiếng Việt (vi)</option>
                            <option value="en">Tiếng Anh (en)</option>
                            <option value="all">Tất cả ngôn ngữ</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="nobifashion_admin_reels_form_label">Mức độ nghiêm trọng</label>
                        <select id="editRuleSeverity" name="severity" class="nobifashion_admin_reels_form_control">
                            <option value="high">Nghiêm trọng (High)</option>
                            <option value="medium">Trung bình (Medium)</option>
                            <option value="low">Thấp (Low)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="nobifashion_admin_reels_form_label">Hành động xử lý</label>
                        <select id="editRuleAction" name="action" class="nobifashion_admin_reels_form_control">
                            <option value="block">Chặn ngay lập tức (Block)</option>
                            <option value="review">Giữ lại để Admin duyệt (Review)</option>
                            <option value="warn">Chỉ gắn cờ cảnh báo (Warn)</option>
                        </select>
                    </div>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editRuleActive">
                    <label class="form-check-label" for="editRuleActive">Đang kích hoạt</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">Cập nhật thay đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Create Whitelist -->
<div class="modal fade" id="createWhitelistModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.reels.moderation.whitelist.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-file-circle-check text-success me-2"></i>Thêm cụm từ ngoại lệ (Whitelist)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="nobifashion_admin_reels_form_label">Cụm từ ngoại lệ <span class="text-danger">*</span></label>
                    <input type="text" name="term" class="nobifashion_admin_reels_form_control" required placeholder="Ví dụ: đầm xòe, quần què (thời trang), ...">
                    <small class="text-muted">Các từ ngữ trong danh sách này sẽ không bao giờ bị bộ lọc quét dính lỗi cấm.</small>
                </div>
                <div class="mb-3">
                    <label class="nobifashion_admin_reels_form_label">Lý do / Ngữ cảnh</label>
                    <textarea name="reason" class="nobifashion_admin_reels_form_control" rows="2" placeholder="Ví dụ: Tên phong cách thiết kế thời trang"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">Lưu ngoại lệ</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openEditRuleModal(rule) {
    const form = document.getElementById('editRuleForm');
    form.action = `/admin/reels/moderation/rules/${rule.id}`;
    document.getElementById('editRulePattern').value = rule.pattern;
    document.getElementById('editRuleType').value = rule.rule_type;
    document.getElementById('editRuleLang').value = rule.language || 'all';
    document.getElementById('editRuleSeverity').value = rule.severity || 'medium';
    document.getElementById('editRuleAction').value = rule.action || 'review';
    document.getElementById('editRuleActive').checked = !!rule.is_active;

    const modal = new bootstrap.Modal(document.getElementById('editRuleModal'));
    modal.show();
}

// Interactive Rule Tester AJAX
const testerForm = document.getElementById('testerForm');
if (testerForm) {
    testerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const text = document.getElementById('testerInput').value.trim();
        if (!text) {
            alert('Vui lòng nhập nội dung để kiểm tra!');
            return;
        }

        const btn = document.getElementById('btnRunTest');
        const placeholder = document.getElementById('testerResultPlaceholder');
        const container = document.getElementById('testerResultContainer');

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang phân tích...';

        fetch("{{ route('admin.reels.moderation.test-rule') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ content: text })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-bolt me-1"></i> Chạy kiểm tra ngay';
            placeholder.classList.add('d-none');
            container.classList.remove('d-none');

            let html = '';
            if (data.is_clean) {
                html = `
                    <div class="alert alert-success d-flex align-items-center">
                        <i class="fa-solid fa-circle-check fs-3 me-3 text-success"></i>
                        <div>
                            <h6 class="alert-heading mb-1 fw-bold">Nội dung an toàn!</h6>
                            <p class="mb-0 small">Không vi phạm bất kỳ quy tắc kiểm duyệt nào. Bình luận sẽ được đăng công khai ngay lập tức.</p>
                        </div>
                    </div>
                `;
            } else {
                html = `
                    <div class="alert alert-danger d-flex align-items-center mb-3">
                        <i class="fa-solid fa-triangle-exclamation fs-3 me-3 text-danger"></i>
                        <div>
                            <h6 class="alert-heading mb-1 fw-bold">Phát hiện vi phạm quy tắc!</h6>
                            <p class="mb-0 small">Hành động dự kiến: <strong class="badge bg-danger">${data.action ? data.action.toUpperCase() : 'BLOCK'}</strong></p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm small">
                            <thead class="table-light">
                                <tr>
                                    <th>Quy tắc vi phạm</th>
                                    <th>Phân loại</th>
                                    <th>Mức độ</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                (data.matched_rules || []).forEach(r => {
                    html += `
                        <tr>
                            <td><code>${r.pattern}</code></td>
                            <td><span class="badge bg-secondary">${r.rule_type}</span></td>
                            <td><span class="badge bg-warning text-dark">${r.severity}</span></td>
                            <td><span class="badge bg-danger">${r.action}</span></td>
                        </tr>
                    `;
                });
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
            }
            container.innerHTML = html;
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-bolt me-1"></i> Chạy kiểm tra ngay';
            alert('Có lỗi xảy ra trong quá trình kiểm thử.');
            console.error(err);
        });
    });
}
</script>
@endpush
