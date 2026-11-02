@extends('admins.layouts.master')

@section('title', 'Chỉnh sửa Video Reel: ' . $reel->title)

@push('styles')
<link rel="stylesheet" href="{{ asset('admins/css/reels_admin.css') }}?v={{ time() }}">
<style>
.tab-badge {
    background: #e2e8f0;
    color: #475569;
    border-radius: 999px;
    padding: 2px 7px;
    font-size: 11px;
    margin-left: 4px;
}
.nobifashion_admin_reels_form_tab_btn.active .tab-badge {
    background: #ffffff;
    color: #0f172a;
}
</style>
@endpush

@section('content')
<div class="nobifashion_admin_reels_container">
    <div class="nobifashion_admin_reels_header">
        <div>
            <h1 class="nobifashion_admin_reels_title">
                <i class="fa-solid fa-pen-to-square text-primary"></i> Chỉnh sửa Video #{{ $reel->id }}
            </h1>
            <p class="nobifashion_admin_reels_subtitle">
                {{ $reel->title }} ({{ $reel->slug }})
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.reels.videos.index') }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline">
                <i class="fa-solid fa-arrow-left"></i> Danh sách
            </a>
            <a href="{{ route('admin.reels.videos.show', $reel->id) }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline">
                <i class="fa-solid fa-eye"></i> Xem Chi Tiết
            </a>
            <button type="submit" form="reelEditForm" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                <i class="fa-solid fa-save"></i> Lưu Thay Đổi
            </button>
        </div>
    </div>

    @include('admins.reels.partials.subnav')

    <form id="reelEditForm" action="{{ route('admin.reels.videos.update', $reel->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <!-- Left Column: 7 Tabs Form (8 cols) -->
            <div class="col-lg-8">
                <div class="nobifashion_admin_reels_card">
                    <div class="nobifashion_admin_reels_form_tabs">
                        <button type="button" class="nobifashion_admin_reels_form_tab_btn active" data-tab="tab_basic">
                            1. Cơ bản
                        </button>
                        <button type="button" class="nobifashion_admin_reels_form_tab_btn" data-tab="tab_media">
                            2. Video & Media
                        </button>
                        <button type="button" class="nobifashion_admin_reels_form_tab_btn" data-tab="tab_author">
                            3. Tác giả
                        </button>
                        <button type="button" class="nobifashion_admin_reels_form_tab_btn" data-tab="tab_brand">
                            4. Thương hiệu
                        </button>
                        <button type="button" class="nobifashion_admin_reels_form_tab_btn" data-tab="tab_products">
                            5. Sản phẩm <span id="attached_count_badge" class="tab-badge">{{ $reel->products->count() }}</span>
                        </button>
                        <button type="button" class="nobifashion_admin_reels_form_tab_btn" data-tab="tab_seo">
                            6. SEO & Social
                        </button>
                        <button type="button" class="nobifashion_admin_reels_form_tab_btn" data-tab="tab_stats">
                            7. Thống kê
                        </button>
                    </div>

                    <div class="nobifashion_admin_reels_card_body">
                        <!-- TAB 1: THÔNG TIN CƠ BẢN -->
                        <div class="nobifashion_admin_reels_form_tab_pane active" id="tab_basic">
                            <div class="nobifashion_admin_reels_form_group">
                                <label class="nobifashion_admin_reels_label">Tiêu đề Video <span class="text-danger">*</span></label>
                                <input type="text" id="input_title" name="title" class="nobifashion_admin_reels_input w-100" value="{{ old('title', $reel->title) }}" required>
                            </div>

                            <div class="nobifashion_admin_reels_form_group">
                                <label class="nobifashion_admin_reels_label">Đường dẫn URL tĩnh (Slug)</label>
                                <input type="text" id="input_slug" name="slug" class="nobifashion_admin_reels_input w-100" value="{{ old('slug', $reel->slug) }}">
                                <div class="nobifashion_admin_reels_help">Nếu thay đổi đường dẫn của video đã xuất bản, hệ thống sẽ tự động tạo Redirect 301.</div>
                            </div>

                            <div class="nobifashion_admin_reels_form_group">
                                <label class="nobifashion_admin_reels_label">Mô tả / Caption Video</label>
                                <textarea id="input_caption" name="caption" class="form-control" rows="4">{{ old('caption', $reel->caption) }}</textarea>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="nobifashion_admin_reels_form_group">
                                        <label class="nobifashion_admin_reels_label">Danh mục Video <span class="text-danger">*</span></label>
                                        <select name="category" class="nobifashion_admin_reels_select w-100" required>
                                            <option value="trending" {{ old('category', $reel->category) === 'trending' ? 'selected' : '' }}>Trending (Xu hướng)</option>
                                            <option value="new" {{ old('category', $reel->category) === 'new' ? 'selected' : '' }}>New Arrivals (Hàng mới về)</option>
                                            <option value="style_tips" {{ old('category', $reel->category) === 'style_tips' ? 'selected' : '' }}>Phối đồ / Tips thời trang</option>
                                            <option value="lookbook" {{ old('category', $reel->category) === 'lookbook' ? 'selected' : '' }}>Lookbook BST</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="nobifashion_admin_reels_form_group">
                                        <label class="nobifashion_admin_reels_label">Trạng thái phát hành <span class="text-danger">*</span></label>
                                        <select name="status" class="nobifashion_admin_reels_select w-100" required>
                                            <option value="published" {{ old('status', $reel->status) === 'published' ? 'selected' : '' }}>Xuất bản (Published)</option>
                                            <option value="draft" {{ old('status', $reel->status) === 'draft' ? 'selected' : '' }}>Lưu nháp (Draft)</option>
                                            <option value="hidden" {{ old('status', $reel->status) === 'hidden' ? 'selected' : '' }}>Tạm ẩn (Hidden)</option>
                                            <option value="archived" {{ old('status', $reel->status) === 'archived' ? 'selected' : '' }}>Lưu trữ (Archived)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="nobifashion_admin_reels_form_group">
                                <label class="nobifashion_admin_reels_label">Hashtags</label>
                                <input type="text" id="input_hashtags" name="hashtags" class="nobifashion_admin_reels_input w-100" value="{{ is_array($reel->hashtags) ? implode(', ', $reel->hashtags) : $reel->hashtags }}">
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured_check" {{ old('is_featured', $reel->is_featured) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold" for="is_featured_check">Đánh dấu Video Nổi bật</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="nobifashion_admin_reels_form_group mb-0">
                                        <label class="nobifashion_admin_reels_label">Mức độ ưu tiên hiển thị (Order Priority)</label>
                                        <input type="number" name="order_priority" class="nobifashion_admin_reels_input w-100" value="{{ old('order_priority', $reel->order_priority) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: VIDEO & MEDIA -->
                        <div class="nobifashion_admin_reels_form_tab_pane" id="tab_media">
                            <div class="nobifashion_admin_reels_form_group">
                                <label class="nobifashion_admin_reels_label">Đường dẫn Video URL <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="url" id="input_video_url" name="video_url" class="nobifashion_admin_reels_input form-control" value="{{ old('video_url', $reel->video_url) }}" required>
                                    <button type="button" id="btn_check_video_url" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline">
                                        <i class="fa-solid fa-link"></i> Kiểm tra URL
                                    </button>
                                </div>
                                <div id="video_url_check_result" class="mt-2" style="font-size: 12.5px; display: none;"></div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="nobifashion_admin_reels_form_group">
                                        <label class="nobifashion_admin_reels_label">Ảnh bìa Poster URL (9:16)</label>
                                        <input type="url" id="input_poster_url" name="poster_url" class="nobifashion_admin_reels_input w-100" value="{{ old('poster_url', $reel->poster_url) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="nobifashion_admin_reels_form_group">
                                        <label class="nobifashion_admin_reels_label">Ảnh Thumbnail nhỏ</label>
                                        <input type="url" name="thumbnail_url" class="nobifashion_admin_reels_input w-100" value="{{ old('thumbnail_url', $reel->thumbnail_url) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="nobifashion_admin_reels_form_group">
                                        <label class="nobifashion_admin_reels_label">External ID</label>
                                        <input type="text" name="external_id" class="nobifashion_admin_reels_input w-100" value="{{ old('external_id', $reel->external_id) }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="nobifashion_admin_reels_form_group">
                                        <label class="nobifashion_admin_reels_label">Thời lượng (Giây)</label>
                                        <input type="number" id="input_duration" name="duration" class="nobifashion_admin_reels_input w-100" value="{{ old('duration', $reel->duration) }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="nobifashion_admin_reels_form_group">
                                        <label class="nobifashion_admin_reels_label">Tỷ lệ khung hình</label>
                                        <input type="text" name="aspect_ratio" class="nobifashion_admin_reels_input w-100" value="{{ old('aspect_ratio', $reel->aspect_ratio) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="nobifashion_admin_reels_form_group">
                                        <label class="nobifashion_admin_reels_label">Nhạc nền / Audio Title</label>
                                        <input type="text" name="audio_title" class="nobifashion_admin_reels_input w-100" value="{{ old('audio_title', $reel->audio_title) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="nobifashion_admin_reels_form_group">
                                        <label class="nobifashion_admin_reels_label">Nhà cung cấp video</label>
                                        <input type="text" name="video_provider" class="nobifashion_admin_reels_input w-100" value="{{ old('video_provider', $reel->video_provider) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: TÁC GIẢ -->
                        <div class="nobifashion_admin_reels_form_tab_pane" id="tab_author">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="nobifashion_admin_reels_form_group">
                                        <label class="nobifashion_admin_reels_label">Tên tác giả / Kênh</label>
                                        <input type="text" id="input_author_name" name="author_name" class="nobifashion_admin_reels_input w-100" value="{{ old('author_name', $reel->author_name) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="nobifashion_admin_reels_form_group">
                                        <label class="nobifashion_admin_reels_label">Handle (@kenh)</label>
                                        <input type="text" id="input_author_handle" name="author_handle" class="nobifashion_admin_reels_input w-100" value="{{ old('author_handle', $reel->author_handle) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="nobifashion_admin_reels_form_group">
                                <label class="nobifashion_admin_reels_label">Avatar Tác giả (URL)</label>
                                <input type="url" name="author_avatar" class="nobifashion_admin_reels_input w-100" value="{{ old('author_avatar', $reel->author_avatar) }}">
                            </div>

                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="author_verified" value="1" id="author_verified_check" {{ old('author_verified', $reel->author_verified) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="author_verified_check">Hiển thị tích xanh xác minh (Verified Badge)</label>
                            </div>
                        </div>

                        <!-- TAB 4: THƯƠNG HIỆU -->
                        <div class="nobifashion_admin_reels_form_tab_pane" id="tab_brand">
                            <div class="nobifashion_admin_reels_form_group">
                                <label class="nobifashion_admin_reels_label">Thương hiệu / Hãng liên kết</label>
                                <select name="brand_id" id="select_brand" class="nobifashion_admin_reels_select w-100">
                                    <option value="">-- Mặc định (Nobi Fashion) --</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ old('brand_id', $reel->brand_id) == $brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }} ({{ $brand->slug }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- TAB 5: SẢN PHẨM LIÊN QUAN -->
                        <div class="nobifashion_admin_reels_form_tab_pane" id="tab_products">
                            <div class="nobifashion_admin_reels_form_group">
                                <label class="nobifashion_admin_reels_label">Tìm và gắn sản phẩm</label>
                                <div class="position-relative">
                                    <input type="text" id="search_product_input" class="nobifashion_admin_reels_input w-100" placeholder="Nhập tên sản phẩm hoặc SKU...">
                                    <div id="product_search_dropdown" style="display:none; position:absolute; top:42px; left:0; right:0; background:#fff; border:1px solid #e2e8f0; border-radius:8px; max-height:220px; overflow-y:auto; z-index:100; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);"></div>
                                </div>
                            </div>

                            <label class="nobifashion_admin_reels_label mt-3">Sản phẩm hiện được gắn trong Reel:</label>
                            <div id="attached_products_list" class="list-group">
                                @forelse($reel->products as $p)
                                    @php $img = $p->primaryImage ? $p->primaryImage->file_path : null; @endphp
                                    <div class="list-group-item d-flex align-items-center justify-content-between p-2 mb-2 border rounded" id="attached_prod_{{ $p->id }}">
                                        <input type="hidden" name="products[]" value="{{ $p->id }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <div style="width:36px; height:36px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                                                @if($img)
                                                    <img src="{{ str_starts_with($img, 'http') ? $img : '/' . ltrim($img, '/') }}" style="width:100%; height:100%; object-fit:cover;">
                                                @endif
                                            </div>
                                            <div>
                                                <div style="font-size:13px; font-weight:600;">{{ $p->name }}</div>
                                                <div class="text-muted" style="font-size:11.5px;">SKU: {{ $p->sku ?: 'N/A' }} - {{ number_format($p->price) }}đ</div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 btn-remove-prod" data-id="{{ $p->id }}">✕ Gỡ</button>
                                    </div>
                                @empty
                                    <div id="no_products_hint" class="text-muted p-3 text-center border rounded">
                                        Chưa có sản phẩm nào được gắn. Hãy tìm kiếm ở trên để thêm sản phẩm.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- TAB 6: SEO & SOCIAL -->
                        <div class="nobifashion_admin_reels_form_tab_pane" id="tab_seo">
                            <div class="nobifashion_admin_reels_form_group">
                                <label class="nobifashion_admin_reels_label">SEO Meta Title</label>
                                <input type="text" id="seo_title" name="seo_title" class="nobifashion_admin_reels_input w-100" value="{{ old('seo_title', $reel->seo_title) }}">
                            </div>

                            <div class="nobifashion_admin_reels_form_group">
                                <label class="nobifashion_admin_reels_label">SEO Meta Description</label>
                                <textarea id="seo_description" name="seo_description" class="form-control" rows="3">{{ old('seo_description', $reel->seo_description) }}</textarea>
                            </div>

                            <div class="nobifashion_admin_reels_form_group">
                                <label class="nobifashion_admin_reels_label">Canonical URL</label>
                                <input type="url" name="canonical_url" class="nobifashion_admin_reels_input w-100" value="{{ old('canonical_url', $reel->canonical_url) }}">
                            </div>
                        </div>

                        <!-- TAB 7: THỐNG KÊ (CHỈ ĐỌC) -->
                        <div class="nobifashion_admin_reels_form_tab_pane" id="tab_stats">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="p-3 bg-light rounded text-center border">
                                        <div class="text-muted" style="font-size:11px; text-transform:uppercase;">Lượt xem hiển thị</div>
                                        <div class="fw-bold mt-1" style="font-size:22px;">{{ number_format($reel->view_count) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-3 bg-light rounded text-center border">
                                        <div class="text-muted" style="font-size:11px; text-transform:uppercase;">Lượt thả tim</div>
                                        <div class="fw-bold text-danger mt-1" style="font-size:22px;">{{ number_format($reel->like_count) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-3 bg-light rounded text-center border">
                                        <div class="text-muted" style="font-size:11px; text-transform:uppercase;">Bình luận</div>
                                        <div class="fw-bold text-info mt-1" style="font-size:22px;">{{ number_format($reel->comment_count) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-3 bg-light rounded text-center border">
                                        <div class="text-muted" style="font-size:11px; text-transform:uppercase;">Lượt lưu</div>
                                        <div class="fw-bold text-warning mt-1" style="font-size:22px;">{{ number_format($reel->save_count) }}</div>
                                    </div>
                                </div>
                            </div>

                            @if($watchStats)
                            <div class="mt-4 p-3 border rounded bg-white">
                                <h6 class="fw-bold mb-3"><i class="fa-solid fa-chart-simple text-primary"></i> Nhật ký Xem Thực Tế (Watch Logs)</h6>
                                <div class="row g-3" style="font-size:13px;">
                                    <div class="col-md-4">
                                        <span class="text-muted">Tổng phiên xem:</span>
                                        <strong>{{ number_format($watchStats->total_watches ?? 0) }}</strong>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="text-muted">Xem hoàn thành:</span>
                                        <strong>{{ number_format($watchStats->completed_watches ?? 0) }}</strong>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="text-muted">Số lần lặp lại (Loops):</span>
                                        <strong>{{ number_format($watchStats->total_loops ?? 0) }}</strong>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="text-muted">Thời gian xem TB:</span>
                                        <strong>{{ $watchStats->avg_watch_time ?? 0 }} giây</strong>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="text-muted">Tỷ lệ xem TB:</span>
                                        <strong>{{ $watchStats->avg_completion_rate ?? 0 }}%</strong>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Live 9:16 Mobile Mockup Preview (4 cols) -->
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 85px;">
                    <div class="text-center mb-2 fw-bold text-muted" style="font-size: 13px;">
                        <i class="fa-solid fa-mobile-screen"></i> LIVE 9:16 REELS PREVIEW
                    </div>

                    <div class="nobifashion_admin_reels_preview_frame">
                        <video id="preview_video_el" src="{{ $reel->video_url }}" class="nobifashion_admin_reels_preview_video" loop muted playsinline></video>

                        <div class="nobifashion_admin_reels_preview_overlay">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: #fff; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                    <i class="fa-solid fa-user text-dark" style="font-size: 12px;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size: 12px;" id="preview_author_text">{{ $reel->author_name }}</div>
                                    <div style="font-size: 10px; opacity: 0.8;" id="preview_handle_text">{{ $reel->author_handle }}</div>
                                </div>
                            </div>
                            <div class="fw-bold mb-1" style="font-size: 13px;" id="preview_title_text">{{ $reel->title }}</div>
                            <div style="font-size: 11px; opacity: 0.9;" id="preview_caption_text">{{ $reel->caption }}</div>
                            <div class="mt-1" style="font-size: 11px; color: #fde047;" id="preview_hashtags_text">
                                {{ is_array($reel->hashtags) ? implode(' ', $reel->hashtags) : $reel->hashtags }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Tab Switching
    const tabBtns = document.querySelectorAll('.nobifashion_admin_reels_form_tab_btn');
    const tabPanes = document.querySelectorAll('.nobifashion_admin_reels_form_tab_pane');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-tab');
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            const targetPane = document.getElementById(targetId);
            if (targetPane) targetPane.classList.add('active');
        });
    });

    // 2. Live Preview Sync
    const titleInput = document.getElementById('input_title');
    const captionInput = document.getElementById('input_caption');
    const hashtagsInput = document.getElementById('input_hashtags');
    const authorNameInput = document.getElementById('input_author_name');
    const authorHandleInput = document.getElementById('input_author_handle');
    const videoUrlInput = document.getElementById('input_video_url');
    const videoEl = document.getElementById('preview_video_el');

    const previewTitle = document.getElementById('preview_title_text');
    const previewCaption = document.getElementById('preview_caption_text');
    const previewHashtags = document.getElementById('preview_hashtags_text');
    const previewAuthor = document.getElementById('preview_author_text');
    const previewHandle = document.getElementById('preview_handle_text');

    function syncPreview() {
        if (titleInput && previewTitle) previewTitle.textContent = titleInput.value.trim() || 'Tiêu đề video';
        if (captionInput && previewCaption) previewCaption.textContent = captionInput.value.trim() || '';
        if (hashtagsInput && previewHashtags) previewHashtags.textContent = hashtagsInput.value.trim() || '';
        if (authorNameInput && previewAuthor) previewAuthor.textContent = authorNameInput.value.trim() || 'Nobi Fashion';
        if (authorHandleInput && previewHandle) previewHandle.textContent = authorHandleInput.value.trim() || '@nobifashion';
    }

    [titleInput, captionInput, hashtagsInput, authorNameInput, authorHandleInput].forEach(el => {
        if (el) el.addEventListener('input', syncPreview);
    });

    if (videoUrlInput && videoEl) {
        videoUrlInput.addEventListener('change', function () {
            const url = this.value.trim();
            if (url) {
                videoEl.src = url;
                videoEl.play().catch(() => {});
            }
        });
        if (videoEl.src) videoEl.play().catch(() => {});
    }

    // 3. Check Video URL button
    const btnCheck = document.getElementById('btn_check_video_url');
    const checkResult = document.getElementById('video_url_check_result');
    if (btnCheck && videoUrlInput && checkResult) {
        btnCheck.addEventListener('click', function () {
            const url = videoUrlInput.value.trim();
            if (!url) {
                alert('Vui lòng nhập URL video trước khi kiểm tra.');
                return;
            }
            checkResult.style.display = 'block';
            checkResult.innerHTML = '<span class="text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Đang gửi HEAD request kiểm tra URL...</span>';

            fetch('{{ route("admin.reels.videos.check-video-url") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ url: url })
            })
            .then(res => res.json())
            .then(data => {
                if (data.accessible) {
                    checkResult.innerHTML = `
                        <div class="alert alert-success py-2 px-3 m-0">
                            <strong><i class="fa-solid fa-circle-check"></i> Hợp lệ:</strong> ${data.message} | HTTP ${data.status_code} | MIME: ${data.content_type} ${data.content_length_mb ? `(${data.content_length_mb} MB)` : ''}
                        </div>
                    `;
                } else {
                    checkResult.innerHTML = `
                        <div class="alert alert-danger py-2 px-3 m-0">
                            <strong><i class="fa-solid fa-triangle-exclamation"></i> Không thể truy cập:</strong> ${data.message}
                        </div>
                    `;
                }
            })
            .catch(err => {
                checkResult.innerHTML = `<div class="alert alert-danger py-2 px-3 m-0">Lỗi kết nối kiểm tra: ${err}</div>`;
            });
        });
    }

    // 4. Product Search & Attach
    const searchProdInput = document.getElementById('search_product_input');
    const prodDropdown = document.getElementById('product_search_dropdown');
    const attachedList = document.getElementById('attached_products_list');
    const noProdHint = document.getElementById('no_products_hint');
    const attachedBadge = document.getElementById('attached_count_badge');
    const attachedIds = new Set();

    // Khởi tạo các ID sản phẩm đã gắn sẵn
    document.querySelectorAll('#attached_products_list input[name="products[]"]').forEach(input => {
        attachedIds.add(parseInt(input.value, 10));
    });

    document.querySelectorAll('.btn-remove-prod').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = parseInt(this.getAttribute('data-id'), 10);
            attachedIds.delete(id);
            const row = document.getElementById(`attached_prod_${id}`);
            if (row) row.remove();
            if (attachedBadge) attachedBadge.textContent = attachedIds.size;
            if (attachedIds.size === 0 && noProdHint) noProdHint.style.display = 'block';
        });
    });

    let searchTimer = null;
    if (searchProdInput && prodDropdown) {
        searchProdInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            const q = this.value.trim();
            if (q.length < 2) {
                prodDropdown.style.display = 'none';
                return;
            }

            searchTimer = setTimeout(() => {
                fetch(`{{ route("admin.reels.videos.search-products") }}?q=${encodeURIComponent(q)}`)
                    .then(res => res.json())
                    .then(res => {
                        if (!res.success || !res.data.length) {
                            prodDropdown.innerHTML = '<div class="p-3 text-muted text-center" style="font-size:12.5px;">Không tìm thấy sản phẩm phù hợp.</div>';
                            prodDropdown.style.display = 'block';
                            return;
                        }

                        let html = '';
                        res.data.forEach(p => {
                            if (!attachedIds.has(p.id)) {
                                html += `
                                    <div class="p-2 border-bottom d-flex align-items-center justify-content-between cursor-pointer prod-item" data-id="${p.id}" data-name="${p.name}" data-price="${p.price}" data-sku="${p.sku}" data-img="${p.image || ''}" style="cursor:pointer;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div style="width:32px; height:32px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                                                ${p.image ? `<img src="${p.image}" style="width:100%; height:100%; object-fit:cover;">` : ''}
                                            </div>
                                            <div>
                                                <div style="font-size:12.5px; font-weight:600;">${p.name}</div>
                                                <div class="text-muted" style="font-size:11px;">SKU: ${p.sku || 'N/A'} - ${new Intl.NumberFormat('vi-VN', {style:'currency', currency:'VND'}).format(p.price)}</div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0" style="font-size:11px;">+ Gắn</button>
                                    </div>
                                `;
                            }
                        });

                        prodDropdown.innerHTML = html || '<div class="p-3 text-muted text-center" style="font-size:12.5px;">Tất cả sản phẩm tìm thấy đã được gắn.</div>';
                        prodDropdown.style.display = 'block';
                    });
            }, 250);
        });

        document.addEventListener('click', function (e) {
            if (!searchProdInput.contains(e.target) && !prodDropdown.contains(e.target)) {
                prodDropdown.style.display = 'none';
            }
        });

        prodDropdown.addEventListener('click', function (e) {
            const item = e.target.closest('.prod-item');
            if (!item) return;

            const id = parseInt(item.getAttribute('data-id'), 10);
            const name = item.getAttribute('data-name');
            const sku = item.getAttribute('data-sku');
            const price = parseFloat(item.getAttribute('data-price')) || 0;
            const img = item.getAttribute('data-img');

            attachProduct(id, name, sku, price, img);
            prodDropdown.style.display = 'none';
            searchProdInput.value = '';
        });
    }

    function attachProduct(id, name, sku, price, img) {
        if (attachedIds.has(id)) return;
        attachedIds.add(id);

        if (noProdHint) noProdHint.style.display = 'none';
        if (attachedBadge) attachedBadge.textContent = attachedIds.size;

        const row = document.createElement('div');
        row.className = 'list-group-item d-flex align-items-center justify-content-between p-2 mb-2 border rounded';
        row.id = `attached_prod_${id}`;
        row.innerHTML = `
            <input type="hidden" name="products[]" value="${id}">
            <div class="d-flex align-items-center gap-2">
                <div style="width:36px; height:36px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                    ${img ? `<img src="${img}" style="width:100%; height:100%; object-fit:cover;">` : ''}
                </div>
                <div>
                    <div style="font-size:13px; font-weight:600;">${name}</div>
                    <div class="text-muted" style="font-size:11.5px;">SKU: ${sku || 'N/A'} - ${new Intl.NumberFormat('vi-VN', {style:'currency', currency:'VND'}).format(price)}</div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 btn-remove-prod" data-id="${id}">✕ Gỡ</button>
        `;

        row.querySelector('.btn-remove-prod').addEventListener('click', function () {
            attachedIds.delete(id);
            row.remove();
            if (attachedBadge) attachedBadge.textContent = attachedIds.size;
            if (attachedIds.size === 0 && noProdHint) noProdHint.style.display = 'block';
        });

        attachedList.appendChild(row);
    }
});
</script>
@endpush

