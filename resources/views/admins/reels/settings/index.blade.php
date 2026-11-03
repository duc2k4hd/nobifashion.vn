@extends('admins.layouts.master')

@section('title', 'Cấu hình hệ thống Nobi Reels (Settings)')

@push('styles')
<link rel="stylesheet" href="{{ asset('admins/css/reels_admin.css') }}?v={{ time() }}">
<style>
    .settings_kpi_grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }
    @media (max-width: 1200px) {
        .settings_kpi_grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 640px) {
        .settings_kpi_grid {
            grid-template-columns: 1fr;
        }
    }
    .settings_group_card {
        border-radius: 12px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .settings_group_header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .settings_group_title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
    }
    .settings_group_body {
        padding: 20px;
    }
    .settings_switch_item {
        padding: 12px 14px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        margin-bottom: 12px;
        transition: background-color 0.15s ease;
    }
    .settings_switch_item:hover {
        background: #f1f5f9;
    }
</style>
@endpush

@section('content')
<div class="nobifashion_admin_reels_container">
    <!-- Header -->
    <div class="nobifashion_admin_reels_header mb-3">
        <div>
            <h1 class="nobifashion_admin_reels_title" style="font-size: 20px;">
                <i class="fa-solid fa-sliders text-primary me-2"></i> Cài đặt hệ thống Nobi Reels
            </h1>
            <p class="nobifashion_admin_reels_subtitle mb-0">
                Quản lý các tham số vận hành, trải nghiệm tương tác, thuật toán phân phối và chính sách bảo mật cho Reels.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('admin.reels.settings.reset') }}" method="POST" onsubmit="return confirm('Khôi phục toàn bộ cài đặt Reels về mặc định ban đầu của hệ thống?');">
                @csrf
                <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline">
                    <i class="fa-solid fa-rotate-left me-1"></i> Khôi phục mặc định
                </button>
            </form>
            <button type="submit" form="settingsForm" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                <i class="fa-solid fa-floppy-disk me-1"></i> Lưu cấu hình
            </button>
        </div>
    </div>

    <!-- Subnav Tabs -->
    @include('admins.reels.partials.subnav')

    <!-- 4 THẺ KPI TÓM TẮT TRẠNG THÁI CÀI ĐẶT -->
    <div class="settings_kpi_grid">
        <!-- 1. Trạng thái Reels -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">TRANG REELS CÔNG KHAI</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #3b82f6; background: #eff6ff;">
                    <i class="fa-solid fa-film"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value">
                @if(!empty($reelSettings['reels.enabled']))
                    <span class="text-success" style="font-size: 20px;"><i class="fa-solid fa-circle-check me-1"></i>Đang hoạt động</span>
                @else
                    <span class="text-danger" style="font-size: 20px;"><i class="fa-solid fa-circle-pause me-1"></i>Đang tạm tắt</span>
                @endif
            </div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span class="text-muted small">Đường dẫn: /reels</span>
            </div>
        </div>

        <!-- 2. Kiểm duyệt tự động -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">BỘ LỌC KIỂM DUYỆT</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #10b981; background: #ecfdf5;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value">
                @if(!empty($reelSettings['reels.moderation_enabled']))
                    <span class="text-success" style="font-size: 20px;"><i class="fa-solid fa-lock me-1"></i>Bảo vệ bật</span>
                @else
                    <span class="text-warning" style="font-size: 20px;">Chưa kích hoạt</span>
                @endif
            </div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span class="text-muted small">AI & Regex quét từ khóa cấm</span>
            </div>
        </div>

        <!-- 3. Tỷ lệ hòa trộn -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">HÒA TRỘN DUAL-ENGINE</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #f59e0b; background: #fffbeb;">
                    <i class="fa-solid fa-shuffle"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value" style="font-size: 22px;">
                <span class="text-danger">{{ $reelSettings['reels.trending_weight'] ?? 60 }}%</span> <span class="text-muted fw-normal" style="font-size: 16px;">/</span> <span class="text-primary">{{ $reelSettings['reels.newest_weight'] ?? 40 }}%</span>
            </div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span class="text-muted small">Trending vs Khám phá mới</span>
            </div>
        </div>

        <!-- 4. Quyền riêng tư IP -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">AN TOÀN QUYỀN RIÊNG TƯ</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #8b5cf6; background: #f5f3ff;">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value">
                @if(!empty($reelSettings['reels.mask_ips']))
                    <span class="text-success" style="font-size: 20px;"><i class="fa-solid fa-mask me-1"></i>IP Masking Bật</span>
                @else
                    <span class="text-secondary" style="font-size: 20px;">Lưu đầy đủ IP</span>
                @endif
            </div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span class="text-muted small">Ẩn 2 octet cuối của IP</span>
            </div>
        </div>
    </div>

    <!-- FORM CÀI ĐẶT CHÍNH -->
    <form id="settingsForm" action="{{ route('admin.reels.settings.update') }}" method="POST">
        @csrf
        
        <div class="row g-3">
            <!-- 1. GENERAL SETTINGS -->
            <div class="col-lg-6">
                <div class="settings_group_card h-100">
                    <div class="settings_group_header">
                        <h3 class="settings_group_title">
                            <i class="fa-solid fa-gear text-primary me-2"></i> 1. Cấu hình chung & Trải nghiệm lướt xem
                        </h3>
                    </div>
                    <div class="settings_group_body">
                        <!-- Switch Kích Hoạt -->
                        <div class="settings_switch_item form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="reels_enabled" value="1" id="reels_enabled" {{ !empty($reelSettings['reels.enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold text-dark" for="reels_enabled">Kích hoạt trang Reels công khai (/reels)</label>
                            <div class="text-muted small">Tạm thời tắt nếu đang nâng cấp hạ tầng hoặc bảo trì hệ thống.</div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-muted mb-1">Số video tải mỗi batch</label>
                                <input type="number" name="reels_batch_limit" class="form-control form-control-sm" value="{{ $reelSettings['reels.batch_limit'] ?? 10 }}" min="5" max="30">
                                <small class="text-muted" style="font-size: 11px;">Mặc định: 10 video / lượt</small>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-muted mb-1">Thời lượng tối đa (giây)</label>
                                <input type="number" name="reels_max_duration" class="form-control form-control-sm" value="{{ $reelSettings['reels.max_duration'] ?? 180 }}" min="10" max="600">
                                <small class="text-muted" style="font-size: 11px;">Mặc định: 180s (3 phút)</small>
                            </div>
                        </div>

                        <h6 class="fw-bold text-dark small mb-2 mt-3"><i class="fa-solid fa-hand-pointer text-muted me-1"></i> Quyền tương tác của người xem:</h6>
                        <div class="row g-2">
                            <div class="col-4">
                                <div class="form-check form-switch p-2 bg-light rounded border">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="reels_likes_enabled" value="1" id="reels_likes_enabled" {{ !empty($reelSettings['reels.likes_enabled']) ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-semibold" for="reels_likes_enabled">Thả tim</label>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check form-switch p-2 bg-light rounded border">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="reels_share_enabled" value="1" id="reels_share_enabled" {{ !empty($reelSettings['reels.share_enabled']) ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-semibold" for="reels_share_enabled">Chia sẻ</label>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check form-switch p-2 bg-light rounded border">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="reels_save_enabled" value="1" id="reels_save_enabled" {{ !empty($reelSettings['reels.save_enabled']) ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-semibold" for="reels_save_enabled">Lưu video</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. COMMENTS & MODERATION -->
            <div class="col-lg-6">
                <div class="settings_group_card h-100">
                    <div class="settings_group_header">
                        <h3 class="settings_group_title">
                            <i class="fa-solid fa-shield-halved text-success me-2"></i> 2. Bình luận & Kiểm duyệt tự động
                        </h3>
                    </div>
                    <div class="settings_group_body">
                        <div class="settings_switch_item form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="reels_comments_enabled" value="1" id="reels_comments_enabled" {{ !empty($reelSettings['reels.comments_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold text-dark" for="reels_comments_enabled">Cho phép bình luận trên Reels</label>
                            <div class="text-muted small">Nếu tắt, khay bình luận sẽ bị khóa ở toàn bộ các video.</div>
                        </div>

                        <div class="settings_switch_item form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="reels_moderation_enabled" value="1" id="reels_moderation_enabled" {{ !empty($reelSettings['reels.moderation_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold text-dark" for="reels_moderation_enabled">Bật kiểm duyệt Regex & Từ khóa cấm</label>
                            <div class="text-muted small">Tự động gắn cờ hoặc chặn bình luận thô tục, công kích, phản cảm.</div>
                        </div>

                        <div class="settings_switch_item form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="reels_block_links" value="1" id="reels_block_links" {{ !empty($reelSettings['reels.block_links']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold text-dark" for="reels_block_links">Chặn bình luận chứa liên kết ngoài (URL)</label>
                            <div class="text-muted small">Ngăn chặn spam lừa đảo, cờ bạc, link tiếp thị ngoài.</div>
                        </div>

                        <div class="row g-2 align-items-center">
                            <div class="col-6">
                                <div class="form-check form-switch p-2 bg-light rounded border">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="reels_stickers_enabled" value="1" id="reels_stickers_enabled" {{ !empty($reelSettings['reels.stickers_enabled']) ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-semibold" for="reels_stickers_enabled">Bật khay Sticker</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-muted mb-1">Độ dài tối đa ký tự</label>
                                <input type="number" name="reels_max_comment_length" class="form-control form-control-sm" value="{{ $reelSettings['reels.max_comment_length'] ?? 500 }}" min="50" max="2000">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. FEED DISTRIBUTION ALGORITHM -->
            <div class="col-lg-6">
                <div class="settings_group_card h-100">
                    <div class="settings_group_header">
                        <h3 class="settings_group_title">
                            <i class="fa-solid fa-satellite-dish text-warning me-2"></i> 3. Thuật toán phân phối Dual-Engine
                        </h3>
                    </div>
                    <div class="settings_group_body">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-muted mb-1">Tỷ lệ Engine Trending (%)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="reels_trending_weight" class="form-control" value="{{ $reelSettings['reels.trending_weight'] ?? 60 }}" min="10" max="90">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted" style="font-size: 11px;">Ưu tiên video có tương tác cao</small>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-muted mb-1">Tỷ lệ Engine Mới (Newest %)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="reels_newest_weight" class="form-control" value="{{ $reelSettings['reels.newest_weight'] ?? 40 }}" min="10" max="90">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted" style="font-size: 11px;">Cơ hội khám phá video BST mới</small>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-muted mb-1">Thời gian sống Snapshot (Cache TTL phút)</label>
                            <input type="number" name="reels_snapshot_ttl_minutes" class="form-control form-control-sm" value="{{ $reelSettings['reels.snapshot_ttl_minutes'] ?? 60 }}" min="5" max="1440">
                            <small class="text-muted" style="font-size: 11px;">Sau khoảng thời gian này, hệ thống sẽ tự động cập nhật lại bảng xếp hạng video.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. ANALYTICS & PRIVACY -->
            <div class="col-lg-6">
                <div class="settings_group_card h-100">
                    <div class="settings_group_header">
                        <h3 class="settings_group_title">
                            <i class="fa-solid fa-user-shield text-info me-2"></i> 4. Phân tích & Quyền riêng tư người xem
                        </h3>
                    </div>
                    <div class="settings_group_body">
                        <div class="settings_switch_item form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="reels_analytics_enabled" value="1" id="reels_analytics_enabled" {{ !empty($reelSettings['reels.analytics_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold text-dark" for="reels_analytics_enabled">Thu thập dữ liệu phân tích Analytics</label>
                            <div class="text-muted small">Ghi nhận thời lượng xem, tỷ lệ xem hết, loop, lượt click sản phẩm.</div>
                        </div>

                        <div class="settings_switch_item form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="reels_mask_ips" value="1" id="reels_mask_ips" {{ !empty($reelSettings['reels.mask_ips']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold text-dark" for="reels_mask_ips">Bật IP Masking (Ẩn 2 octet cuối của IP)</label>
                            <div class="text-muted small">Tuân thủ tiêu chuẩn bảo vệ quyền riêng tư và an toàn dữ liệu khách hàng.</div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-muted mb-1">Thời hạn lưu trữ nhật ký xem (Retention Days)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="reels_retention_days" class="form-control" value="{{ $reelSettings['reels.retention_days'] ?? 90 }}" min="7" max="365">
                                <span class="input-group-text">ngày</span>
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Nhật ký cũ hơn mốc này sẽ được tự động dọn dẹp để tối ưu dung lượng DB.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. DEFAULT SEO CONFIGURATION -->
            <div class="col-12">
                <div class="settings_group_card mb-3">
                    <div class="settings_group_header">
                        <h3 class="settings_group_title">
                            <i class="fa-brands fa-google text-danger me-2"></i> 5. Cấu hình SEO Meta Tags mặc định
                        </h3>
                    </div>
                    <div class="settings_group_body">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold text-muted mb-1">Hậu tố tiêu đề SEO (Title Suffix)</label>
                                <input type="text" name="reels_default_seo_title_suffix" class="form-control form-control-sm" value="{{ $reelSettings['reels.default_seo_title_suffix'] ?? ' | Nobi Fashion Reels' }}">
                                <small class="text-muted" style="font-size: 11px;">Ví dụ: Tiêu đề video + " | Nobi Fashion Reels"</small>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold text-muted mb-1">Mô tả Meta Description mặc định</label>
                                <textarea name="reels_default_seo_description" class="form-control form-control-sm" rows="2">{{ $reelSettings['reels.default_seo_description'] ?? 'Khám phá video thời trang xu hướng, phong cách trẻ trung và BST mới nhất tại Nobi Fashion Reels.' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary px-4 py-2">
                <i class="fa-solid fa-floppy-disk me-1"></i> Lưu toàn bộ cấu hình Reels
            </button>
        </div>
    </form>
</div>
@endsection
