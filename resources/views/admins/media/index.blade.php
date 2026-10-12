@extends('admins.layouts.master')

@section('title', 'Quản lý Media')
@section('page-title', 'Media Library')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/media-icon.png') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
    <link rel="stylesheet" href="{{ asset('admins/css/media-manager.css') }}?v={{ time() }}">
@endpush

@section('content')
    <div class="media-manager-page">
        {{-- Header tối giản --}}
        <header class="media-header">
            <div class="media-header-left">
                <div class="media-header-title-wrap">
                    <h1 class="media-title">Thư viện Media</h1>
                    <span class="media-badge-count" id="mediaTotalCountBadge">
                        {{ number_format($initialPagination['total'] ?? 0) }} tệp • {{ $stats['estimated_size'] ?? '0 B' }}
                    </span>
                </div>
                <p class="media-subtitle">Quản lý, tải lên và sao chép liên kết hình ảnh nhanh chóng</p>
            </div>
            <div class="media-header-actions">
                <button type="button" class="media-btn media-btn-outline" id="mediaSyncPostsBtn" title="Quét toàn bộ ảnh trong bài viết và tự động gán đối tượng">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                    </svg>
                    Đồng bộ bài viết
                </button>
                <button type="button" class="media-btn media-btn-outline" id="mediaCleanupBtn" title="Dọn dẹp file rác và lỗi">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                    Dọn file rác
                </button>
                <button type="button" class="media-btn media-btn-primary" id="mediaToggleUploadBtn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Tải ảnh lên
                </button>
            </div>
        </header>

        {{-- Panel Upload kéo thả (Ẩn/Hiện nhẹ nhàng khi bấm Tải ảnh lên) --}}
        <section class="media-upload-panel is-collapsed" id="mediaUploadPanel">
            <div class="media-upload-header">
                <div>
                    <h3 class="media-upload-title">Tải ảnh lên hệ thống</h3>
                    <p class="media-upload-desc">Hỗ trợ định dạng JPG, PNG, WEBP, AVIF, GIF (tối đa 5MB mỗi ảnh)</p>
                </div>
                <button type="button" class="media-panel-close" id="mediaCollapseUploadBtn" aria-label="Đóng">×</button>
            </div>

            <form id="mediaUploadForm" class="media-upload-form">
                @csrf
                <div class="media-upload-row">
                    <div class="media-field media-field-folder">
                        <label for="mediaUploadFolder">Lưu vào thư mục:</label>
                        <select name="folder" id="mediaUploadFolder" class="media-select" required>
                            @foreach($folders->groupBy('scope') as $scope => $items)
                                <optgroup label="{{ $scope }}">
                                    @foreach($items as $folder)
                                        <option value="{{ $folder['key'] }}" {{ $folder['key'] === 'clothes' ? 'selected' : '' }}>
                                            {{ $folder['label'] }} ({{ $folder['path'] }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div class="media-dropzone" id="mediaDropzone">
                        <input type="file" name="files[]" id="mediaFileInput" accept="image/*" multiple hidden>
                        <div class="media-dropzone-content">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="media-dropzone-icon">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            <p class="media-dropzone-text"><strong>Kéo thả ảnh vào đây</strong> hoặc <span class="media-dropzone-link">chọn file từ máy tính</span></p>
                            <span class="media-dropzone-hint" id="mediaSelectedFiles">Chưa có file nào được chọn</span>
                        </div>
                    </div>
                </div>

                <div class="media-upload-actions">
                    <button type="submit" class="media-btn media-btn-primary" id="mediaSubmitUploadBtn">Bắt đầu tải lên</button>
                </div>
            </form>
        </section>

        {{-- Thanh công cụ lọc & tìm kiếm (Toolbar) --}}
        <section class="media-toolbar-card">
            <div class="media-toolbar">
                <div class="media-search-box">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="media-search-icon">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="search" id="mediaKeyword" class="media-search-input" placeholder="Tìm theo tên file, tiêu đề, đường dẫn...">
                </div>

                <div class="media-filters">
                    <div class="media-filter-item">
                        <select id="mediaFilterFolder" class="media-select">
                            <option value="all">Tất cả thư mục</option>
                            @foreach($folders as $folder)
                                <option value="{{ $folder['key'] }}">{{ $folder['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="media-filter-item">
                        <select id="mediaFilterType" class="media-select">
                            @foreach($typeFilters as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="media-filter-item" style="display: none;">
                        <select id="mediaFilterStatus" class="media-select">
                            @foreach($statusFilters as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="media-filter-item">
                        <select id="mediaSort" class="media-select">
                            <option value="created_at">Mới cập nhật</option>
                            <option value="file_name">Tên file A-Z</option>
                            <option value="size">Dung lượng lớn nhất</option>
                        </select>
                    </div>

                    <div class="media-filter-item">
                        <select id="mediaPerPage" class="media-select">
                            <option value="48" selected>48 / trang</option>
                            <option value="96">96 / trang</option>
                            <option value="192">192 / trang</option>
                        </select>
                    </div>

                    <div class="media-view-switch">
                        <button type="button" class="media-view-btn is-active" data-view="grid" id="mediaViewGridBtn" title="Xem lưới">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </button>
                        <button type="button" class="media-view-btn" data-view="list" id="mediaViewListBtn" title="Xem danh sách">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                        </button>
                    </div>

                    <button type="button" class="media-btn-icon" id="mediaRefreshBtn" title="Làm mới danh sách">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <polyline points="1 20 1 14 7 14"></polyline>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Thanh thao tác chọn nhiều (Bulk Action Bar) --}}
            <div class="media-bulkbar" id="mediaBulkBar" hidden>
                <div class="media-bulkbar-info">
                    <span class="media-bulkbar-badge" id="mediaBulkCount">0</span>
                    <span>ảnh đã được chọn</span>
                </div>
                <div class="media-bulkbar-actions">
                    <button type="button" class="media-btn media-btn-sm media-btn-outline" id="mediaSelectVisibleBtn">Chọn tất cả trang này</button>
                    <button type="button" class="media-btn media-btn-sm media-btn-outline" id="mediaClearSelectionBtn">Bỏ chọn</button>
                    <button type="button" class="media-btn media-btn-sm media-btn-danger" id="mediaBulkDeleteBtn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px; vertical-align: -2px;">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                        Xóa các ảnh đã chọn
                    </button>
                </div>
            </div>
        </section>

        {{-- Lưới hiển thị hình ảnh chính (Toàn màn hình) --}}
        <main class="media-canvas">
            <div class="media-meta-bar">
                <div class="media-meta-count" id="mediaResultsSummary">
                    Hiển thị {{ number_format($initialPagination['from'] ?? 0) }} - {{ number_format($initialPagination['to'] ?? 0) }} trên {{ number_format($initialPagination['total'] ?? 0) }} mục
                </div>
                <div class="media-meta-filter" id="mediaCurrentFilterText">Tất cả thư mục</div>
            </div>

            {{-- Lưới ảnh --}}
            <div class="media-grid is-grid" id="mediaGrid"></div>

            {{-- Trạng thái rỗng --}}
            <div class="media-empty-state" id="mediaEmptyState" hidden>
                <div class="media-empty-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                </div>
                <h3>Không tìm thấy hình ảnh nào</h3>
                <p>Thử tìm kiếm với từ khóa khác hoặc chuyển sang thư mục khác.</p>
            </div>

            {{-- Phân trang --}}
            <div class="media-pagination" id="mediaPagination">
                <button type="button" class="media-btn media-btn-outline" id="mediaPrevBtn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    Trang trước
                </button>
                <span class="media-pagination-text" id="mediaPaginationText">
                    Trang {{ $initialPagination['current_page'] ?? 1 }} / {{ $initialPagination['last_page'] ?? 1 }}
                </span>
                <button type="button" class="media-btn media-btn-outline" id="mediaNextBtn">
                    Trang sau
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left: 4px;">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
        </main>

        {{-- Backdrop khi mở Drawer xem chi tiết --}}
        <div class="media-drawer-backdrop" id="mediaDrawerBackdrop"></div>

        {{-- Drawer xem chi tiết ảnh trượt từ bên phải (Slide-over Drawer) --}}
        <aside class="media-drawer" id="mediaInspector">
            <div class="media-drawer-header">
                <h3 class="media-drawer-title">Chi tiết hình ảnh</h3>
                <button type="button" class="media-drawer-close" id="mediaCloseInspectorBtn" aria-label="Đóng chi tiết">×</button>
            </div>

            <div class="media-inspector-empty" id="mediaInspectorEmpty">
                <p>Chọn một ảnh để xem chi tiết</p>
            </div>

            <div class="media-drawer-body" id="mediaInspectorContent" hidden>
                {{-- Ảnh xem trước lớn --}}
                <div class="media-drawer-preview">
                    <img src="" alt="" id="mediaInspectorImage">
                </div>

                {{-- Thông tin tệp --}}
                <div class="media-drawer-meta-list" id="mediaInspectorMeta">
                    <div class="media-drawer-meta-item">
                        <span class="media-meta-label">Tên file:</span>
                        <strong class="media-meta-val" id="mediaInspectorTitle">-</strong>
                    </div>
                    <div class="media-drawer-meta-item">
                        <span class="media-meta-label">Dung lượng & Kích thước:</span>
                        <span class="media-meta-val" id="mediaInspectorSubtitle">-</span>
                    </div>
                    <div class="media-inspector-badges" id="mediaInspectorBadges"></div>
                </div>

                {{-- Nút sao chép link siêu tiện lợi --}}
                <div class="media-drawer-copy-box">
                    <button type="button" class="media-btn media-btn-primary media-btn-block" id="mediaCopyPathBtn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        Sao chép đường dẫn (Copy URL)
                    </button>
                    <button type="button" class="media-btn media-btn-outline media-btn-block" id="mediaOpenOriginalBtn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        Mở ảnh trong tab mới
                    </button>
                </div>

                {{-- Form sửa thông tin nhanh --}}
                <form id="mediaInspectorForm" class="media-drawer-form">
                    @csrf
                    <input type="hidden" id="mediaInspectorSource">
                    <input type="hidden" id="mediaInspectorId">

                    <div class="media-field">
                        <label for="mediaInspectorTitleInput">Tiêu đề (Title)</label>
                        <input type="text" id="mediaInspectorTitleInput" name="title" class="media-input" placeholder="Tiêu đề hiển thị...">
                    </div>

                    <div class="media-field">
                        <label for="mediaInspectorAltInput">Văn bản thay thế (Alt text cho SEO)</label>
                        <input type="text" id="mediaInspectorAltInput" name="alt" class="media-input" placeholder="Mô tả ảnh cho công cụ tìm kiếm...">
                    </div>

                    <div class="media-field" style="display: none;">
                        <textarea id="mediaInspectorDescriptionInput" name="description"></textarea>
                        <input type="checkbox" id="mediaInspectorPrimaryInput" name="is_primary" value="1">
                    </div>

                    <div class="media-drawer-form-actions">
                        <button type="submit" class="media-btn media-btn-outline" id="mediaSaveBtn">Lưu thông tin</button>
                        <button type="button" class="media-btn media-btn-danger-outline" id="mediaDeleteBtn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                            Xóa ảnh này
                        </button>
                    </div>
                </form>

                {{-- Gán ảnh vào đối tượng khác (Thu gọn mặc định) --}}
                <details class="media-assign-details">
                    <summary class="media-assign-summary">Gán ảnh vào Sản phẩm / Bài viết</summary>
                    <form id="mediaAssignForm" class="media-assign-form" style="margin-top: 12px;">
                        @csrf
                        <input type="hidden" id="mediaAssignSource" name="source">
                        <input type="hidden" id="mediaAssignMediaId" name="media_id">

                        <div class="media-field">
                            <label for="mediaAssignTargetType">Loại đối tượng</label>
                            <select id="mediaAssignTargetType" name="target_type" class="media-select">
                                @foreach($uploadTargets as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="media-field">
                            <label for="mediaAssignTargetId">Chọn đối tượng</label>
                            <select id="mediaAssignTargetId" name="target_id" class="media-select"></select>
                        </div>

                        <button type="submit" class="media-btn media-btn-primary media-btn-block" id="mediaAssignBtn" style="margin-top: 10px;">Xác nhận gán</button>
                    </form>
                </details>
            </div>
        </aside>
    </div>

    <div class="media-loading-overlay" id="mediaLoadingOverlay" hidden aria-live="assertive" aria-busy="true">
        <div class="media-loading-dialog" role="status" aria-label="Đang xử lý thư viện ảnh">
            <span class="media-loading-spinner" aria-hidden="true"></span>
            <div class="media-loading-copy">
                <strong>Đang xử lý thư viện ảnh</strong>
                <p id="mediaLoadingMessage">Vui lòng chờ đến khi thao tác hiện tại hoàn tất.</p>
                <div class="media-loading-progress" id="mediaLoadingProgress" hidden>
                    <div class="media-loading-progress-meta">
                        <span id="mediaLoadingProgressText">0 B / 0 B</span>
                        <strong id="mediaLoadingProgressPercent">0%</strong>
                    </div>
                    <div class="media-loading-progress-track" aria-hidden="true">
                        <span class="media-loading-progress-bar" id="mediaLoadingProgressBar"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="media-toast" id="mediaToast" hidden></div>

    <!-- Modal Đồng bộ & Gán ảnh bài viết -->
    <div class="media-modal" id="mediaSyncPostsModal" hidden>
        <div class="media-modal__backdrop" id="mediaSyncPostsBackdrop"></div>
        <div class="media-modal__dialog" style="max-width: 720px; height: auto; max-height: 90vh; overflow-y: auto;">
            <div class="media-modal__header">
                <div>
                    <p class="media-modal__eyebrow">Tối ưu & Tự động hóa</p>
                    <h2 style="font-size: 20px;">Đồng bộ & Gán ảnh bài viết</h2>
                    <p class="media-modal__description" style="font-size: 13px;">
                        Quét toàn bộ ảnh trong nội dung HTML và thumbnail bài viết, tự động đối soát theo tên file / link ảnh và gán vào bài viết tương ứng với tốc độ cao.
                    </p>
                </div>
                <button type="button" class="media-panel-close" id="mediaCloseSyncPostsXBtn" aria-label="Đóng">×</button>
            </div>

            <div class="media-modal__body" style="display: flex; flex-direction: column; gap: 16px; padding: 10px 0;">
                <!-- Thống kê trước khi chạy -->
                <div class="media-stats-grid" style="grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 0;">
                    <div class="media-stat-card" style="padding: 10px 14px;">
                        <span class="media-stat-label" style="font-size: 11px;">Tổng bài viết</span>
                        <strong class="media-stat-value" id="syncStatTotalPosts" style="font-size: 18px;">-</strong>
                    </div>
                    <div class="media-stat-card" style="padding: 10px 14px;">
                        <span class="media-stat-label" style="font-size: 11px;">File vật lý (posts)</span>
                        <strong class="media-stat-value" id="syncStatPhysicalFiles" style="font-size: 18px;">-</strong>
                    </div>
                    <div class="media-stat-card media-stat-success" style="padding: 10px 14px;">
                        <span class="media-stat-label" style="font-size: 11px;">Ảnh đã gán bài</span>
                        <strong class="media-stat-value" id="syncStatAssignedImages" style="font-size: 18px;">-</strong>
                    </div>
                    <div class="media-stat-card media-stat-warning" style="padding: 10px 14px;">
                        <span class="media-stat-label" style="font-size: 11px;">Ảnh chưa gán</span>
                        <strong class="media-stat-value" id="syncStatUnassignedImages" style="font-size: 18px;">-</strong>
                    </div>
                </div>

                <!-- Thiết lập đồng bộ -->
                <div style="background: #ffffff; border: 1px solid var(--media-border); border-radius: 12px; padding: 16px;">
                    <h4 style="margin: 0 0 12px; font-size: 14px; font-weight: 600;">Cấu hình đồng bộ</h4>
                    <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
                        <div style="padding: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; color: #166534; font-size: 12px; line-height: 1.5;">
                            ✓ <strong>Quy tắc an toàn:</strong> Chỉ quét bài viết và gán đối tượng cho <strong>ảnh thật đã có sẵn trong bảng Media</strong>. Ảnh trong bài viết nếu chưa có trong Media sẽ <strong>bỏ qua hoàn toàn</strong>, tuyệt đối không tự tạo thêm hàng mới.
                        </div>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="syncOptionUpdateMeta" checked>
                            <span>Cập nhật <strong>Alt text</strong> và <strong>Tiêu đề</strong> từ thẻ &lt;img&gt; nếu ảnh đang để trống</span>
                        </label>
                        <div style="display: flex; align-items: center; gap: 12px; margin-top: 6px;">
                            <label for="syncOptionBatchSize" style="font-weight: 500;">Batch size (Số bài viết / lượt):</label>
                            <select id="syncOptionBatchSize" style="padding: 4px 8px; border-radius: 6px; border: 1px solid var(--media-border);">
                                <option value="50">50 bài / lượt</option>
                                <option value="100" selected>100 bài / lượt (Khuyến nghị)</option>
                                <option value="200">200 bài / lượt</option>
                                <option value="500">500 bài / lượt (Siêu tốc)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Khu vực tiến trình chạy -->
                <div id="syncProgressContainer" style="display: none; background: #ffffff; border: 1px solid var(--media-border); border-radius: 12px; padding: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <strong id="syncProgressStatusText" style="font-size: 13px; color: var(--media-primary);">Đang chuẩn bị...</strong>
                        <span id="syncProgressPercent" style="font-size: 14px; font-weight: 700;">0%</span>
                    </div>
                    <div style="height: 10px; background: #e2e8f0; border-radius: 999px; overflow: hidden; margin-bottom: 12px;">
                        <div id="syncProgressBar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #2271b1, #00a32a); border-radius: 999px; transition: width 0.3s ease;"></div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; font-size: 12px; text-align: center; color: var(--media-text-soft);">
                        <div style="background: var(--media-panel-soft); padding: 8px; border-radius: 8px;">
                            <span>Bài viết đã quét:</span><br>
                            <strong id="syncProgressProcessedPosts" style="font-size: 14px; color: var(--media-text);">0</strong>
                        </div>
                        <div style="background: var(--media-panel-soft); padding: 8px; border-radius: 8px;">
                            <span>Ảnh media đã gán bài:</span><br>
                            <strong id="syncProgressAssignedImages" style="font-size: 14px; color: var(--media-success);">0</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="media-modal__actions" style="border-top: 1px solid var(--media-border); padding-top: 14px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <button type="button" class="media-btn media-btn-danger" id="mediaCleanupGhostsBtn" title="Xóa các bản ghi ảnh rác thiếu file vật lý">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px; vertical-align: -2px;">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                        Xóa sạch ảnh thiếu file
                    </button>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="media-btn media-btn-secondary" id="mediaCloseSyncPostsBtn">Đóng</button>
                    <button type="button" class="media-btn media-btn-danger" id="mediaStopSyncPostsBtn" style="display: none;">Dừng lại</button>
                    <button type="button" class="media-btn media-btn-primary" id="mediaStartSyncPostsBtn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px; vertical-align: -2px;">
                            <polygon points="5 3 19 12 5 21 5 3"/>
                        </svg>
                        Bắt đầu đồng bộ
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    @php
        $mediaRequestLimits = [
            'upload' => [
                'maxFilesPerRequest' => max(1, (int) ini_get('max_file_uploads')),
                'maxBatchBytes' => ini_get('post_max_size') ?: '8M',
                'appMaxSingleFileKb' => max(1, (int) config('media.request_limits.upload_file_max_kb', 5120)),
                'batchSafetyRatio' => (float) config('media.request_limits.upload_batch_safety_ratio', 0.9),
            ],
            'delete' => [
                'maxItemsPerRequest' => max(1, (int) config('media.request_limits.delete_items_per_request', 200)),
            ],
        ];
    @endphp
    <script>
        window.mediaManagerConfig = {
            csrfToken: @json(csrf_token()),
            fallbackImage: @json(asset('clients/assets/no-image.webp')),
            limits: @json($mediaRequestLimits),
            routes: {
                search: @json(route('admin.media.search')),
                upload: @json(route('admin.media.upload')),
                updateBase: @json(url('/admin/media/update')),
                assign: @json(route('admin.media.assign')),
                bulkDelete: @json(route('admin.media.bulk-delete')),
                fastDeleteScope: @json(route('admin.media.fast-delete-scope')),
                cleanup: @json(route('admin.media.cleanup')),
                targets: @json(route('admin.media.targets')),
                syncPostsOverview: @json(route('admin.media.sync-posts.overview')),
                syncPostsIndexFiles: @json(route('admin.media.sync-posts.index-files')),
                syncPostsProcessChunk: @json(route('admin.media.sync-posts.process-chunk')),
                syncPostsCleanupGhosts: @json(route('admin.media.sync-posts.cleanup-ghosts')),
            },
            initialState: {
                items: @json($initialMedia),
                meta: @json($initialPagination),
                stats: @json($stats),
            },
        };
    </script>
    <script src="{{ asset('admins/js/media-manager.js') }}?v={{ env('APP_VERSION') }}.{{ @filemtime(public_path('admins/js/media-manager.js')) }}"></script>
@endpush
