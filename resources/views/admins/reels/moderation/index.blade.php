@extends('admins.layouts.master')

@section('title', 'Trung tâm kiểm duyệt bình luận Reels')

@push('styles')
<link rel="stylesheet" href="{{ asset('admins/css/reels_admin.css') }}?v={{ time() }}">
<style>
    .nobifashion_mod_kpi_grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }
    @media (max-width: 1200px) {
        .nobifashion_mod_kpi_grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 640px) {
        .nobifashion_mod_kpi_grid {
            grid-template-columns: 1fr;
        }
    }
    .nobifashion_mod_table {
        table-layout: fixed;
        width: 100%;
        margin-bottom: 0;
    }
    .nobifashion_mod_table th,
    .nobifashion_mod_table td {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
        padding: 9px 10px;
    }
    .nobifashion_nav_tab_pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        font-size: 13px;
        font-weight: 500;
        border-radius: 8px;
        color: #64748b;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .nobifashion_nav_tab_pill:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .nobifashion_nav_tab_pill.active {
        background: #0f172a;
        color: #ffffff;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="nobifashion_admin_reels_container">
    <!-- Header -->
    <div class="nobifashion_admin_reels_header mb-3">
        <div>
            <h1 class="nobifashion_admin_reels_title" style="font-size: 20px;">
                <i class="fa-solid fa-shield-halved text-primary"></i> Trung tâm kiểm duyệt bình luận Reels
            </h1>
            <p class="nobifashion_admin_reels_subtitle mb-0">
                Tự động quét và xử lý ngôn từ thô tục, spam liên kết và bình luận quấy rối theo thời gian thực.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary py-1 px-3" data-bs-toggle="modal" data-bs-target="#createRuleModal">
                <i class="fa-solid fa-plus"></i> Thêm quy tắc
            </button>
            <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline py-1 px-3" data-bs-toggle="modal" data-bs-target="#createWhitelistModal">
                <i class="fa-solid fa-shield-heart text-success"></i> Thêm ngoại lệ
            </button>
            <form action="{{ route('admin.reels.moderation.seed-presets') }}" method="POST" onsubmit="return confirm('Nạp bộ quy tắc kiểm duyệt chuẩn tiếng Việt (tự động chặn chửi bậy, spam link, số điện thoại lừa đảo)?');">
                @csrf
                <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline py-1 px-3" style="border-color: #3b82f6; color: #2563eb;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Nạp bộ lọc mẫu
                </button>
            </form>
        </div>
    </div>

    <!-- Subnav Tabs -->
    @include('admins.reels.partials.subnav')

    <!-- 4 Thẻ KPI Cards Thống Kê (Vừa khít 1 màn hình) -->
    <div class="nobifashion_mod_kpi_grid">
        <div class="nobifashion_admin_reels_kpi_card p-3">
            <div class="nobifashion_admin_reels_kpi_header mb-2">
                <span class="nobifashion_admin_reels_kpi_label" style="font-size: 11px;">QUY TẮC ĐANG BẬT</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #3b82f6; background: #eff6ff; width: 30px; height: 30px;">
                    <i class="fa-solid fa-list-check" style="font-size: 13px;"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value text-primary" style="font-size: 18px;">
                {{ $counts['active_rules'] ?? 0 }} / {{ $counts['total_rules'] ?? 0 }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer mt-1" style="font-size: 11px;">
                Đang quét bình luận tự động
            </div>
        </div>

        <div class="nobifashion_admin_reels_kpi_card p-3">
            <div class="nobifashion_admin_reels_kpi_header mb-2">
                <span class="nobifashion_admin_reels_kpi_label" style="font-size: 11px;">CHẶN NGAY (BLOCK)</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #ef4444; background: #fef2f2; width: 30px; height: 30px;">
                    <i class="fa-solid fa-ban" style="font-size: 13px;"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value text-danger" style="font-size: 18px;">
                {{ $counts['block_rules'] ?? 0 }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer mt-1" style="font-size: 11px;">
                Quy tắc cấm tuyệt đối
            </div>
        </div>

        <div class="nobifashion_admin_reels_kpi_card p-3">
            <div class="nobifashion_admin_reels_kpi_header mb-2">
                <span class="nobifashion_admin_reels_kpi_label" style="font-size: 11px;">GIỮ CHỜ DUYỆT (REVIEW)</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #f59e0b; background: #fef3c7; width: 30px; height: 30px;">
                    <i class="fa-solid fa-clock" style="font-size: 13px;"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value text-warning" style="font-size: 18px;">
                {{ $counts['review_rules'] ?? 0 }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer mt-1" style="font-size: 11px;">
                Chờ admin kiểm duyệt
            </div>
        </div>

        <div class="nobifashion_admin_reels_kpi_card p-3">
            <div class="nobifashion_admin_reels_kpi_header mb-2">
                <span class="nobifashion_admin_reels_kpi_label" style="font-size: 11px;">TỪ KHÓA NGOẠI LỆ</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #10b981; background: #ecfdf5; width: 30px; height: 30px;">
                    <i class="fa-solid fa-shield-heart" style="font-size: 13px;"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value text-success" style="font-size: 18px;">
                {{ $counts['whitelists'] ?? 0 }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer mt-1" style="font-size: 11px;">
                Ngữ cảnh thời trang hợp lệ
            </div>
        </div>
    </div>

    <!-- Main Navigation Pills -->
    <div class="nobifashion_admin_reels_card p-2 mb-3">
        <div class="d-flex align-items-center gap-1 overflow-auto">
            <a href="{{ route('admin.reels.moderation.index', ['tab' => 'rules']) }}" class="nobifashion_nav_tab_pill {{ $activeTab === 'rules' ? 'active' : '' }}">
                <i class="fa-solid fa-list-check"></i> Quy tắc từ khóa ({{ $rules->count() }})
            </a>
            <a href="{{ route('admin.reels.moderation.index', ['tab' => 'whitelist']) }}" class="nobifashion_nav_tab_pill {{ $activeTab === 'whitelist' ? 'active' : '' }}">
                <i class="fa-solid fa-shield-heart"></i> Danh sách ngoại lệ ({{ $whitelists->count() }})
            </a>
            <a href="{{ route('admin.reels.moderation.index', ['tab' => 'logs']) }}" class="nobifashion_nav_tab_pill {{ $activeTab === 'logs' ? 'active' : '' }}">
                <i class="fa-solid fa-clock-rotate-left"></i> Nhật ký kiểm duyệt ({{ $counts['logs'] ?? 0 }})
            </a>
            <a href="{{ route('admin.reels.moderation.index', ['tab' => 'tester']) }}" class="nobifashion_nav_tab_pill {{ $activeTab === 'tester' ? 'active' : '' }}">
                <i class="fa-solid fa-vial-circle-check text-warning"></i> Công cụ thử nghiệm (Tester)
            </a>
        </div>
    </div>

    <!-- TAB 1: RULES -->
    @if($activeTab === 'rules')
    <div class="nobifashion_admin_reels_card overflow-hidden">
        <div class="px-3 py-2 bg-light border-bottom d-flex justify-content-between align-items-center gap-2">
            <span class="fw-semibold text-dark small">
                Danh sách bộ lọc kiểm duyệt tự động ({{ $rules->count() }} quy tắc)
            </span>
            <button type="button" class="btn btn-sm btn-primary py-1 px-3" style="border-radius: 6px; font-size: 12px;" data-bs-toggle="modal" data-bs-target="#createRuleModal">
                <i class="fa-solid fa-plus me-1"></i> Tạo quy tắc mới
            </button>
        </div>

        <div class="table-responsive m-0">
            <table class="table table-hover align-middle nobifashion_mod_table">
                <thead class="table-light text-muted small text-uppercase" style="font-size: 11.5px;">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Mẫu nhận diện (Pattern / Từ khóa)</th>
                        <th style="width: 115px;">Phân loại</th>
                        <th style="width: 80px;">Ngôn ngữ</th>
                        <th style="width: 95px;">Mức độ</th>
                        <th style="width: 105px;">Hành động</th>
                        <th style="width: 100px;" class="text-center">Trạng thái</th>
                        <th style="width: 95px;" class="text-end pe-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $rule)
                        <tr>
                            <td class="text-muted small">#{{ $rule->id }}</td>
                            <td>
                                <code class="fw-bold px-2 py-1 bg-light border rounded text-dark text-break" style="font-size: 12.5px;">
                                    {{ $rule->pattern }}
                                </code>
                            </td>
                            <td>
                                @switch($rule->rule_type)
                                    @case('profanity')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 11px;">Thô tục</span>
                                        @break
                                    @case('link')
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1" style="font-size: 11px;">Spam Link</span>
                                        @break
                                    @case('harassment')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 11px;">Đả kích</span>
                                        @break
                                    @case('spam')
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1" style="font-size: 11px;">Tin rác</span>
                                        @break
                                    @default
                                        <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 11px;">Tùy chỉnh</span>
                                @endswitch
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border">{{ strtoupper($rule->language ?? 'ALL') }}</span>
                            </td>
                            <td>
                                @if($rule->severity === 'high')
                                    <span class="badge bg-danger px-2 py-1" style="font-size: 10.5px;">Cao</span>
                                @elseif($rule->severity === 'medium')
                                    <span class="badge bg-warning text-dark px-2 py-1" style="font-size: 10.5px;">Trung bình</span>
                                @else
                                    <span class="badge bg-info-subtle text-info-emphasis px-2 py-1" style="font-size: 10.5px;">Thấp</span>
                                @endif
                            </td>
                            <td>
                                @if($rule->action === 'block')
                                    <span class="badge bg-danger text-white px-2 py-1" style="font-size: 11px;">
                                        <i class="fa-solid fa-ban me-1"></i> Chặn
                                    </span>
                                @elseif($rule->action === 'review')
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1" style="font-size: 11px;">
                                        <i class="fa-solid fa-eye me-1"></i> Giữ duyệt
                                    </span>
                                @else
                                    <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 11px;">Cảnh báo</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <form action="{{ route('admin.reels.moderation.rules.toggle', $rule->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm p-0 border-0" title="Bấm để bật/tắt">
                                        @if($rule->is_active)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="cursor: pointer;">
                                                <i class="fa-solid fa-toggle-on me-1"></i> Bật
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1" style="cursor: pointer;">
                                                <i class="fa-solid fa-toggle-off me-1"></i> Tắt
                                            </span>
                                        @endif
                                    </button>
                                </form>
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius: 6px; padding: 2px 7px; font-size: 11px;" 
                                        onclick="openEditRuleModal({{ json_encode($rule) }})" title="Sửa quy tắc">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <form action="{{ route('admin.reels.moderation.rules.destroy', $rule->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa quy tắc này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius: 6px; padding: 2px 7px; font-size: 11px;" title="Xóa">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-shield-halved fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                                <div class="fw-semibold">Chưa có quy tắc kiểm duyệt nào được thiết lập</div>
                                <div class="small text-muted mt-1">Bấm nút <strong>"Nạp bộ lọc mẫu"</strong> ở góc trên để nạp sẵn bộ từ khóa chuẩn tiếng Việt ngay lập tức.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- TAB 2: WHITELIST -->
    @if($activeTab === 'whitelist')
    <div class="nobifashion_admin_reels_card overflow-hidden">
        <div class="px-3 py-2 bg-light border-bottom d-flex justify-content-between align-items-center gap-2">
            <span class="fw-semibold text-dark small">
                Danh sách từ khóa ngoại lệ ({{ $whitelists->count() }} từ khóa)
            </span>
            <button type="button" class="btn btn-sm btn-success py-1 px-3" style="border-radius: 6px; font-size: 12px;" data-bs-toggle="modal" data-bs-target="#createWhitelistModal">
                <i class="fa-solid fa-plus me-1"></i> Thêm từ ngoại lệ
            </button>
        </div>

        <div class="table-responsive m-0">
            <table class="table table-hover align-middle nobifashion_mod_table">
                <thead class="table-light text-muted small text-uppercase" style="font-size: 11.5px;">
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th style="width: 250px;">Cụm từ ngoại lệ</th>
                        <th>Lý do / Ghi chú ngữ cảnh</th>
                        <th style="width: 140px;">Ngày thêm</th>
                        <th style="width: 90px;" class="text-end pe-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($whitelists as $item)
                        <tr>
                            <td class="text-muted small">#{{ $item->id }}</td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-6">
                                    {{ $item->word }}
                                </span>
                            </td>
                            <td class="text-muted small">{{ $item->note ?? 'Không có ghi chú' }}</td>
                            <td class="small text-muted">{{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '—' }}</td>
                            <td class="text-end pe-3">
                                <form action="{{ route('admin.reels.moderation.whitelist.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa từ ngoại lệ này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius: 6px; padding: 2px 7px; font-size: 11px;" title="Xóa">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-shield-heart fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                                <div class="fw-semibold">Chưa có từ khóa ngoại lệ nào</div>
                                <div class="small text-muted mt-1">Các từ ngữ trong danh sách này sẽ không bao giờ bị bộ lọc quét dính cấm.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- TAB 3: LOGS -->
    @if($activeTab === 'logs')
    <div class="nobifashion_admin_reels_card overflow-hidden">
        <div class="px-3 py-2 bg-light border-bottom">
            <span class="fw-semibold text-dark small">
                Nhật ký xử lý kiểm duyệt vi phạm ({{ $logs->total() }} lượt phát hiện)
            </span>
        </div>

        <div class="table-responsive m-0">
            <table class="table table-hover align-middle nobifashion_mod_table">
                <thead class="table-light text-muted small text-uppercase" style="font-size: 11.5px;">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="width: 140px;">Người gửi / IP</th>
                        <th>Nội dung bị gắn cờ</th>
                        <th style="width: 140px;">Quy tắc vi phạm</th>
                        <th style="width: 110px;">Kết quả</th>
                        <th style="width: 120px;">Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-muted small">#{{ $log->id }}</td>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="font-size: 12.5px;">
                                    {{ $log->anonymousUser ? $log->anonymousUser->nickname : 'Khách' }}
                                </div>
                                <small class="text-muted" style="font-size: 11px;">IP: {{ $log->ip_address }}</small>
                            </td>
                            <td>
                                <div class="text-truncate text-danger fw-semibold" title="{{ $log->flagged_content }}" style="font-size: 12.5px;">
                                    {{ $log->flagged_content }}
                                </div>
                            </td>
                            <td>
                                @if($log->rule)
                                    <code class="px-2 py-1 bg-light border rounded small">{{ $log->rule->pattern }}</code>
                                @else
                                    <span class="text-muted small">Mã: {{ $log->reason_code }}</span>
                                @endif
                            </td>
                            <td>
                                @if($log->moderation_result === 'blocked')
                                    <span class="badge bg-danger text-white">Đã chặn</span>
                                @elseif($log->moderation_result === 'review')
                                    <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                @else
                                    <span class="badge bg-secondary">{{ $log->moderation_result }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-muted small" style="font-size: 11.5px;">{{ $log->created_at ? $log->created_at->format('d/m/Y H:i') : '—' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-clipboard-check fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                                <div class="fw-semibold">Chưa có nhật ký kiểm duyệt nào</div>
                                <div class="small text-muted mt-1">Khi có người bình luận vi phạm từ cấm, hệ thống sẽ tự động ghi lại tại đây.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-3 py-2 bg-white border-top">
                {{ $logs->appends(['tab' => 'logs'])->links() }}
            </div>
        @endif
    </div>
    @endif

    <!-- TAB 4: RULE TESTER (Giao diện 2 cột hiện đại) -->
    @if($activeTab === 'tester')
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="nobifashion_admin_reels_card p-3">
                <h6 class="fw-bold text-dark mb-1">
                    <i class="fa-solid fa-vial-circle-check text-primary me-1"></i> Thử nghiệm kiểm tra bình luận
                </h6>
                <p class="text-muted small mb-3">Nhập văn bản mẫu để kiểm tra xem hệ thống sẽ cho phép hay chặn.</p>

                <form id="testerForm">
                    <div class="mb-3">
                        <textarea id="testerInput" class="nobifashion_admin_reels_input w-100" rows="5" style="border-radius: 8px;" placeholder="Ví dụ: Áo này nhìn chán vl, liên hệ zalo.me/0987654321 mua đồ giá rẻ..."></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" id="btnRunTest" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                            <i class="fa-solid fa-bolt me-1"></i> Chạy kiểm tra ngay
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="nobifashion_admin_reels_card p-3">
                <h6 class="fw-bold text-dark mb-1">
                    <i class="fa-solid fa-chart-simple text-success me-1"></i> Kết quả phân tích thời gian thực
                </h6>
                <p class="text-muted small mb-3">Đánh giá dựa trên toàn bộ quy tắc đang hoạt động.</p>

                <div id="testerResultPlaceholder" class="text-center py-5 text-muted">
                    <i class="fa-solid fa-magnifying-glass fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                    Nhập văn bản bên trái và bấm <strong>"Chạy kiểm tra ngay"</strong> để xem kết quả.
                </div>

                <div id="testerResultContainer" class="d-none"></div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modal: Tạo quy tắc mới -->
<div class="modal fade" id="createRuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form action="{{ route('admin.reels.moderation.rules.store') }}" method="POST" class="modal-content border-0 rounded-4 shadow">
            @csrf
            <div class="modal-header border-bottom py-2 px-3">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="fa-solid fa-plus text-primary me-2"></i>Thêm quy tắc kiểm duyệt
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2">
                    <label class="form-label fw-semibold small mb-1">Mẫu nhận diện (Pattern / Từ khóa) <span class="text-danger">*</span></label>
                    <input type="text" name="pattern" class="nobifashion_admin_reels_input w-100" style="padding: 6px 10px; font-size: 13px;" required placeholder="Ví dụ: đm, lừa đảo, hoặc regex /\b(dm|vcl)\b/i">
                    <small class="text-muted" style="font-size: 11px;">Hỗ trợ từ khóa nguyên chuỗi hoặc cú pháp Regex.</small>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label fw-semibold small mb-1">Phân loại</label>
                        <select name="rule_type" class="nobifashion_admin_reels_select w-100" style="padding: 6px 10px; font-size: 13px;">
                            <option value="profanity">Từ ngữ thô tục / Nhạy cảm</option>
                            <option value="link">Spam link / URL</option>
                            <option value="harassment">Quấy rối / Đả kích</option>
                            <option value="spam">Tin rác / Lặp lại</option>
                            <option value="custom">Tùy chỉnh</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold small mb-1">Ngôn ngữ</label>
                        <select name="language" class="nobifashion_admin_reels_select w-100" style="padding: 6px 10px; font-size: 13px;">
                            <option value="vi">Tiếng Việt (vi)</option>
                            <option value="en">Tiếng Anh (en)</option>
                            <option value="all">Tất cả</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label fw-semibold small mb-1">Mức độ</label>
                        <select name="severity" class="nobifashion_admin_reels_select w-100" style="padding: 6px 10px; font-size: 13px;">
                            <option value="high">Cao (High)</option>
                            <option value="medium" selected>Trung bình</option>
                            <option value="low">Thấp (Low)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold small mb-1">Hành động</label>
                        <select name="action" class="nobifashion_admin_reels_select w-100" style="padding: 6px 10px; font-size: 13px;">
                            <option value="block">Chặn ngay lập tức</option>
                            <option value="review" selected>Giữ lại chờ duyệt</option>
                            <option value="warn">Cảnh báo</option>
                        </select>
                    </div>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="createRuleActive" checked>
                    <label class="form-check-label fw-semibold small" for="createRuleActive">Kích hoạt quy tắc ngay sau khi lưu</label>
                </div>
            </div>
            <div class="modal-footer border-top bg-light py-2 px-3">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-sm btn-primary">Lưu quy tắc</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Sửa quy tắc -->
<div class="modal fade" id="editRuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form id="editRuleForm" method="POST" class="modal-content border-0 rounded-4 shadow">
            @csrf
            @method('PUT')
            <div class="modal-header border-bottom py-2 px-3">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="fa-solid fa-pencil text-primary me-2"></i>Chỉnh sửa quy tắc
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2">
                    <label class="form-label fw-semibold small mb-1">Mẫu nhận diện <span class="text-danger">*</span></label>
                    <input type="text" id="editRulePattern" name="pattern" class="nobifashion_admin_reels_input w-100" style="padding: 6px 10px; font-size: 13px;" required>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label fw-semibold small mb-1">Phân loại</label>
                        <select id="editRuleType" name="rule_type" class="nobifashion_admin_reels_select w-100" style="padding: 6px 10px; font-size: 13px;">
                            <option value="profanity">Từ ngữ thô tục / Nhạy cảm</option>
                            <option value="link">Spam link / URL</option>
                            <option value="harassment">Quấy rối / Đả kích</option>
                            <option value="spam">Tin rác / Lặp lại</option>
                            <option value="custom">Tùy chỉnh</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold small mb-1">Ngôn ngữ</label>
                        <select id="editRuleLang" name="language" class="nobifashion_admin_reels_select w-100" style="padding: 6px 10px; font-size: 13px;">
                            <option value="vi">Tiếng Việt (vi)</option>
                            <option value="en">Tiếng Anh (en)</option>
                            <option value="all">Tất cả</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label fw-semibold small mb-1">Mức độ</label>
                        <select id="editRuleSeverity" name="severity" class="nobifashion_admin_reels_select w-100" style="padding: 6px 10px; font-size: 13px;">
                            <option value="high">Cao (High)</option>
                            <option value="medium">Trung bình</option>
                            <option value="low">Thấp (Low)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold small mb-1">Hành động</label>
                        <select id="editRuleAction" name="action" class="nobifashion_admin_reels_select w-100" style="padding: 6px 10px; font-size: 13px;">
                            <option value="block">Chặn ngay lập tức</option>
                            <option value="review">Giữ lại chờ duyệt</option>
                            <option value="warn">Cảnh báo</option>
                        </select>
                    </div>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editRuleActive">
                    <label class="form-check-label fw-semibold small" for="editRuleActive">Kích hoạt quy tắc</label>
                </div>
            </div>
            <div class="modal-footer border-top bg-light py-2 px-3">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-sm btn-primary">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Thêm từ ngoại lệ (Whitelist) -->
<div class="modal fade" id="createWhitelistModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <form action="{{ route('admin.reels.moderation.whitelist.store') }}" method="POST" class="modal-content border-0 rounded-4 shadow">
            @csrf
            <div class="modal-header border-bottom py-2 px-3">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="fa-solid fa-shield-heart text-success me-2"></i>Thêm cụm từ ngoại lệ
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2">
                    <label class="form-label fw-semibold small mb-1">Cụm từ ngoại lệ <span class="text-danger">*</span></label>
                    <input type="text" name="word" class="nobifashion_admin_reels_input w-100" style="padding: 6px 10px; font-size: 13px;" required placeholder="Ví dụ: đầm xòe, quần què, chất lừ...">
                    <small class="text-muted" style="font-size: 11px;">Từ ngữ này sẽ không bao giờ bị bộ lọc quét nhầm cấm.</small>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold small mb-1">Ghi chú ngữ cảnh</label>
                    <textarea name="note" class="nobifashion_admin_reels_input w-100" rows="2" style="padding: 6px 10px; font-size: 13px;" placeholder="Ví dụ: Thuật ngữ thiết kế thời trang"></textarea>
                </div>
            </div>
            <div class="modal-footer border-top bg-light py-2 px-3">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-sm btn-success">Lưu ngoại lệ</button>
            </div>
        </form>
    </div>
</div>

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

// Tester AJAX
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
            body: JSON.stringify({ text: text })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-bolt me-1"></i> Chạy kiểm tra ngay';
            placeholder.classList.add('d-none');
            container.classList.remove('d-none');

            let html = '';
            if (data.is_valid) {
                html = `
                    <div class="alert alert-success d-flex align-items-center p-3 mb-0" style="border-radius: 8px;">
                        <i class="fa-solid fa-circle-check fs-3 me-3 text-success"></i>
                        <div>
                            <h6 class="alert-heading mb-1 fw-bold">Nội dung an toàn!</h6>
                            <p class="mb-0 small">${data.explanation || 'Không vi phạm bất kỳ quy tắc kiểm duyệt nào.'}</p>
                        </div>
                    </div>
                `;
            } else {
                html = `
                    <div class="alert alert-danger d-flex align-items-center mb-3 p-3" style="border-radius: 8px;">
                        <i class="fa-solid fa-triangle-exclamation fs-3 me-3 text-danger"></i>
                        <div>
                            <h6 class="alert-heading mb-1 fw-bold">Phát hiện vi phạm quy tắc!</h6>
                            <p class="mb-0 small">${data.explanation || 'Nội dung bị chặn hoặc chờ duyệt.'}</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm small mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Quy tắc khớp</th>
                                    <th>Loại</th>
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
@endsection
