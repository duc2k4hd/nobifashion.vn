@extends('admins.layouts.master')

@section('title', 'Cấu hình hệ thống Nobi Reels (Settings)')

@section('content')
<div class="nobifashion_admin_reels_wrapper container-fluid px-3 py-3">
    <!-- Header -->
    <div class="nobifashion_admin_reels_page_header mb-3">
        <div>
            <h1 class="nobifashion_admin_reels_page_title">
                <i class="fa-solid fa-sliders text-primary me-2"></i>Cài đặt hệ thống Nobi Reels
            </h1>
            <div class="nobifashion_admin_reels_breadcrumb">
                <a href="{{ route('admin.reels.dashboard') }}">Reels</a>
                <span>/</span>
                <span>Cấu hình vận hành</span>
            </div>
        </div>
        <div class="nobifashion_admin_reels_page_actions">
            <form action="{{ route('admin.reels.settings.reset') }}" method="POST" class="d-inline" onsubmit="return confirm('Khôi phục toàn bộ cài đặt Reels về mặc định của hệ thống?');">
                @csrf
                <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary">
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

    <form id="settingsForm" action="{{ route('admin.reels.settings.update') }}" method="POST">
        @csrf
        
        <div class="row g-3">
            <!-- 1. GENERAL SETTINGS -->
            <div class="col-lg-6">
                <div class="nobifashion_admin_reels_card h-100 mb-3">
                    <div class="nobifashion_admin_reels_card_header">
                        <h3 class="nobifashion_admin_reels_card_title">
                            <i class="fa-solid fa-gear text-primary me-2"></i>1. Cấu hình chung & Trải nghiệm xem
                        </h3>
                    </div>
                    <div class="nobifashion_admin_reels_card_body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="reels_enabled" value="1" id="reels_enabled" {{ !empty($settings['reels.enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="reels_enabled">Kích hoạt trang Reels công khai (/reels)</label>
                            <div class="text-muted small">Tạm thời tắt nếu đang bảo trì hoặc cập nhật hạ tầng video.</div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="nobifashion_admin_reels_form_label">Số video tải mỗi batch</label>
                                <input type="number" name="reels_batch_limit" class="nobifashion_admin_reels_form_control" value="{{ $settings['reels.batch_limit'] ?? 10 }}" min="5" max="30">
                                <small class="text-muted">Mặc định: 10 video</small>
                            </div>
                            <div class="col-6">
                                <label class="nobifashion_admin_reels_form_label">Thời lượng tối đa (giây)</label>
                                <input type="number" name="reels_max_duration" class="nobifashion_admin_reels_form_control" value="{{ $settings['reels.max_duration'] ?? 180 }}" min="10" max="600">
                                <small class="text-muted">Mặc định: 180s (3 phút)</small>
                            </div>
                        </div>

                        <hr class="my-3">

                        <h6 class="fw-bold mb-2">Quyền tương tác người xem:</h6>
                        <div class="row g-2">
                            <div class="col-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="reels_likes_enabled" value="1" id="reels_likes_enabled" {{ !empty($settings['reels.likes_enabled']) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="reels_likes_enabled">Thả tim</label>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="reels_share_enabled" value="1" id="reels_share_enabled" {{ !empty($settings['reels.share_enabled']) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="reels_share_enabled">Chia sẻ</label>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="reels_save_enabled" value="1" id="reels_save_enabled" {{ !empty($settings['reels.save_enabled']) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="reels_save_enabled">Lưu video</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. COMMENTS & MODERATION -->
            <div class="col-lg-6">
                <div class="nobifashion_admin_reels_card h-100 mb-3">
                    <div class="nobifashion_admin_reels_card_header">
                        <h3 class="nobifashion_admin_reels_card_title">
                            <i class="fa-solid fa-shield-halved text-success me-2"></i>2. Bình luận & Kiểm duyệt tự động
                        </h3>
                    </div>
                    <div class="nobifashion_admin_reels_card_body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="reels_comments_enabled" value="1" id="reels_comments_enabled" {{ !empty($settings['reels.comments_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="reels_comments_enabled">Cho phép bình luận trên toàn bộ Reels</label>
                            <div class="text-muted small">Nếu tắt, khay bình luận sẽ bị khóa ở mọi video.</div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="reels_moderation_enabled" value="1" id="reels_moderation_enabled" {{ !empty($settings['reels.moderation_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="reels_moderation_enabled">Bật AI & Regex kiểm duyệt từ khóa cấm</label>
                            <div class="text-muted small">Tự động gắn cờ hoặc chặn bình luận phản cảm, thô tục, công kích.</div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="reels_block_links" value="1" id="reels_block_links" {{ !empty($settings['reels.block_links']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="reels_block_links">Chặn tất cả bình luận chứa đường link (URL/Domain)</label>
                            <div class="text-muted small">Ngăn chặn spam lừa đảo, cờ bạc, link tiếp thị liên kết ngoài.</div>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <div class="form-check form-switch pt-2">
                                    <input class="form-check-input" type="checkbox" name="reels_stickers_enabled" value="1" id="reels_stickers_enabled" {{ !empty($settings['reels.stickers_enabled']) ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-semibold" for="reels_stickers_enabled">Bật gửi Sticker</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="nobifashion_admin_reels_form_label">Độ dài tối đa ký tự</label>
                                <input type="number" name="reels_max_comment_length" class="nobifashion_admin_reels_form_control" value="{{ $settings['reels.max_comment_length'] ?? 500 }}" min="50" max="2000">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. FEED DISTRIBUTION ALGORITHM -->
            <div class="col-lg-6">
                <div class="nobifashion_admin_reels_card h-100 mb-3">
                    <div class="nobifashion_admin_reels_card_header">
                        <h3 class="nobifashion_admin_reels_card_title">
                            <i class="fa-solid fa-satellite-dish text-warning me-2"></i>3. Thuật toán phân phối Dual-Engine
                        </h3>
                    </div>
                    <div class="nobifashion_admin_reels_card_body">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="nobifashion_admin_reels_form_label">Tỷ lệ Engine Trending (%)</label>
                                <div class="input-group">
                                    <input type="number" name="reels_trending_weight" class="nobifashion_admin_reels_form_control" value="{{ $settings['reels.trending_weight'] ?? 60 }}" min="10" max="90">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Ưu tiên video tương tác cao</small>
                            </div>
                            <div class="col-6">
                                <label class="nobifashion_admin_reels_form_label">Tỷ lệ Engine Mới (Newest %)</label>
                                <div class="input-group">
                                    <input type="number" name="reels_newest_weight" class="nobifashion_admin_reels_form_control" value="{{ $settings['reels.newest_weight'] ?? 40 }}" min="10" max="90">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Tạo cơ hội khám phá BST mới</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="nobifashion_admin_reels_form_label">Thời gian sống Snapshot (Cache TTL phút)</label>
                            <input type="number" name="reels_snapshot_ttl_minutes" class="nobifashion_admin_reels_form_control" value="{{ $settings['reels.snapshot_ttl_minutes'] ?? 60 }}" min="5" max="1440">
                            <small class="text-muted">Sau thời gian này, hệ thống sẽ tự động cập nhật lại bảng xếp hạng video xu hướng.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. ANALYTICS & PRIVACY -->
            <div class="col-lg-6">
                <div class="nobifashion_admin_reels_card h-100 mb-3">
                    <div class="nobifashion_admin_reels_card_header">
                        <h3 class="nobifashion_admin_reels_card_title">
                            <i class="fa-solid fa-user-shield text-info me-2"></i>4. Phân tích & Quyền riêng tư người xem
                        </h3>
                    </div>
                    <div class="nobifashion_admin_reels_card_body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="reels_analytics_enabled" value="1" id="reels_analytics_enabled" {{ !empty($settings['reels.analytics_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="reels_analytics_enabled">Thu thập dữ liệu phân tích xem video (Analytics)</label>
                            <div class="text-muted small">Ghi nhận thời lượng xem, tỷ lệ xem hết, loop, lượt click sản phẩm.</div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="reels_mask_ips" value="1" id="reels_mask_ips" {{ !empty($settings['reels.mask_ips']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="reels_mask_ips">Bật IP Masking (Ẩn 2 octet cuối của IP)</label>
                            <div class="text-muted small">Tuân thủ tiêu chuẩn an toàn bảo mật và bảo vệ dữ liệu khách truy cập.</div>
                        </div>

                        <div class="mb-3">
                            <label class="nobifashion_admin_reels_form_label">Thời hạn lưu trữ nhật ký xem (Retention Days)</label>
                            <div class="input-group">
                                <input type="number" name="reels_retention_days" class="nobifashion_admin_reels_form_control" value="{{ $settings['reels.retention_days'] ?? 90 }}" min="7" max="365">
                                <span class="input-group-text">ngày</span>
                            </div>
                            <small class="text-muted">Các bản ghi xem video cũ hơn sẽ được dọn dẹp để tối ưu dung lượng DB.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. DEFAULT SEO CONFIGURATION -->
            <div class="col-12">
                <div class="nobifashion_admin_reels_card mb-3">
                    <div class="nobifashion_admin_reels_card_header">
                        <h3 class="nobifashion_admin_reels_card_title">
                            <i class="fa-brands fa-google text-danger me-2"></i>5. Cấu hình SEO mặc định (Reels Meta Tags)
                        </h3>
                    </div>
                    <div class="nobifashion_admin_reels_card_body">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="nobifashion_admin_reels_form_label">Hậu tố tiêu đề SEO (Title Suffix)</label>
                                <input type="text" name="reels_default_seo_title_suffix" class="nobifashion_admin_reels_form_control" value="{{ $settings['reels.default_seo_title_suffix'] ?? ' | Nobi Fashion Reels' }}">
                                <small class="text-muted">Ví dụ: Tên video + " | Nobi Fashion Reels"</small>
                            </div>
                            <div class="col-md-7">
                                <label class="nobifashion_admin_reels_form_label">Mô tả Meta Description mặc định</label>
                                <textarea name="reels_default_seo_description" class="nobifashion_admin_reels_form_control" rows="2">{{ $settings['reels.default_seo_description'] ?? 'Khám phá video thời trang xu hướng, phong cách trẻ trung và BST mới nhất tại Nobi Fashion Reels.' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary px-4">
                <i class="fa-solid fa-floppy-disk me-1"></i> Lưu toàn bộ cấu hình Reels
            </button>
        </div>
    </form>
</div>
@endsection

