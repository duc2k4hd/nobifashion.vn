@extends('admins.layouts.master')

@section('title', 'Quản lý Media')
@section('page-title', 'Media Library')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/media-icon.png') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
    <link rel="stylesheet" href="{{ asset('admins/css/media-manager.css') }}?v={{ env('APP_VERSION') }}">
@endpush

@section('content')
    <div class="media-manager-page">
        <section class="media-page-header">
            <div>
                <p class="media-page-eyebrow">Thư viện phương tiện</p>
                <h1>Quản lý ảnh tập trung</h1>
                <p class="media-page-description">
                    Theo dõi ảnh đang dùng, file mồ côi, file thiếu và thao tác gán lại ảnh trong một màn hình duy nhất.
                </p>
            </div>
            <div class="media-page-actions">
                <button type="button" class="media-btn media-btn-secondary" id="mediaSyncPostsBtn" title="Quét toàn bộ ảnh trong bài viết và tự động gán đối tượng">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: -2px;">
                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                    </svg>
                    Đồng bộ ảnh bài viết
                </button>
                <button type="button" class="media-btn media-btn-secondary" id="mediaRefreshBtn">Quét lại dữ liệu</button>
                <button type="button" class="media-btn media-btn-secondary" id="mediaCleanupBtn">Dọn file lỗi</button>
                <button type="button" class="media-btn media-btn-primary" id="mediaToggleUploadBtn">Tải ảnh mới</button>
            </div>
        </section>

        <section class="media-stats-grid" id="mediaStatsGrid">
            <article class="media-stat-card">
                <span class="media-stat-label">Mục trong thư viện</span>
                <strong class="media-stat-value" data-stat-key="library_items">{{ number_format($stats['library_items']) }}</strong>
            </article>
            <article class="media-stat-card">
                <span class="media-stat-label">Bản ghi đang theo dõi</span>
                <strong class="media-stat-value" data-stat-key="tracked_records">{{ number_format($stats['tracked_records']) }}</strong>
            </article>
            <article class="media-stat-card">
                <span class="media-stat-label">Tệp vật lý trên ổ đĩa</span>
                <strong class="media-stat-value" data-stat-key="physical_files">{{ number_format($stats['physical_files']) }}</strong>
            </article>
            <article class="media-stat-card media-stat-warning">
                <span class="media-stat-label">File mồ côi</span>
                <strong class="media-stat-value" data-stat-key="orphan_files">{{ number_format($stats['orphan_files']) }}</strong>
            </article>
            <article class="media-stat-card media-stat-danger">
                <span class="media-stat-label">File thiếu</span>
                <strong class="media-stat-value" data-stat-key="missing_files">{{ number_format($stats['missing_files']) }}</strong>
            </article>
            <article class="media-stat-card media-stat-warning">
                <span class="media-stat-label">Record chưa gắn</span>
                <strong class="media-stat-value" data-stat-key="unassigned_records">{{ number_format($stats['unassigned_records']) }}</strong>
            </article>
            <article class="media-stat-card media-stat-accent">
                <span class="media-stat-label">Dung lượng ước tính</span>
                <strong class="media-stat-value" data-stat-key="estimated_size">{{ $stats['estimated_size'] }}</strong>
            </article>
        </section>

        <section class="media-upload-panel is-collapsed" id="mediaUploadPanel">
            <div class="media-panel-head">
                <div>
                    <h2>Upload ảnh vào thư viện</h2>
                    <p>Chọn thư mục lưu ảnh rồi tải lên. Nếu cần gán vào sản phẩm, bài viết hoặc banner, hãy chọn ảnh sau khi upload và gán từ inspector bên phải.</p>
                </div>
                <button type="button" class="media-panel-close" id="mediaCollapseUploadBtn" aria-label="Đóng panel upload">×</button>
            </div>

            <form id="mediaUploadForm" class="media-upload-form">
                @csrf
                <div class="media-form-grid">
                    <div class="media-field">
                        <label for="mediaUploadFolder">Thư mục đích</label>
                        <select name="folder" id="mediaUploadFolder" required>
                            @foreach($folders->groupBy('scope') as $scope => $items)
                                <optgroup label="{{ $scope }}">
                                    @foreach($items as $folder)
                                        <option value="{{ $folder['key'] }}">
                                            {{ $folder['label'] }} ({{ $folder['path'] }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="media-dropzone" id="mediaDropzone">
                    <input type="file" name="files[]" id="mediaFileInput" accept="image/*" multiple hidden>
                    <div>
                        <strong>Kéo thả ảnh vào đây</strong>
                        <p>hoặc bấm để chọn nhiều file. Hỗ trợ JPG, PNG, GIF, WEBP, AVIF tối đa 5MB mỗi file.</p>
                        <span id="mediaSelectedFiles">Chưa chọn file nào.</span>
                    </div>
                </div>

                <div class="media-form-actions">
                    <button type="submit" class="media-btn media-btn-primary">Upload vào thư viện</button>
                </div>
            </form>
        </section>

        <section class="media-workspace">
            <aside class="media-sidebar">
                <div class="media-sidebar-section">
                    <h3>Trạng thái quản lý</h3>
                    <div class="media-status-list" id="mediaStatusList">
                        @foreach($statusFilters as $key => $label)
                            @continue($key === 'shared_file')
                            <button type="button"
                                    class="media-status-filter {{ $key === 'all' ? 'is-active' : '' }}"
                                    data-status="{{ $key }}">
                                <span>{{ $label }}</span>
                                <strong data-status-count="{{ $key }}">
                                    {{ number_format($stats['status_counts'][$key] ?? 0) }}
                                </strong>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="media-sidebar-section">
                    <h3>Thư mục đang theo dõi</h3>
                    <ul class="media-folder-list">
                        @foreach($folders as $folder)
                            <li>
                                <button type="button" class="media-folder-filter" data-folder="{{ $folder['key'] }}">
                                    <span>
                                        {{ $folder['label'] }}
                                        <small class="text-muted ms-1">({{ $folder['file_count'] ?? 0 }})</small>
                                    </span>
                                    <small>{{ $folder['path'] }}</small>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="media-sidebar-section media-sidebar-note">
                    <h3>Nguyên tắc an toàn</h3>
                    <p>
                        Nếu một file đang được nhiều bản ghi cùng dùng, hệ thống chỉ xóa record và giữ lại file vật lý để tránh làm gãy ảnh ở nơi khác.
                    </p>
                    <p>
                        Tool dọn file lỗi sẽ xem trước và xử lý record DB bị mất file, record chưa gắn đối tượng và file vật lý không còn bản ghi tham chiếu.
                    </p>
                </div>
            </aside>

            <main class="media-browser">
                <div class="media-toolbar">
                    <div class="media-toolbar-search">
                        <input type="search" id="mediaKeyword" placeholder="Tìm theo tên file, tiêu đề, alt, đường dẫn, đối tượng...">
                    </div>

                    <div class="media-toolbar-filters">
                        <select id="mediaFilterType">
                            @foreach($typeFilters as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <select id="mediaFilterFolder">
                            <option value="all">Tất cả thư mục</option>
                            @foreach($folders as $folder)
                                <option value="{{ $folder['key'] }}">{{ $folder['label'] }}</option>
                            @endforeach
                        </select>

                        <select id="mediaFilterStatus">
                            @foreach($statusFilters as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <select id="mediaSort">
                            <option value="created_at">Mới cập nhật</option>
                            <option value="file_name">Tên file</option>
                            <option value="size">Dung lượng</option>
                            <option value="entity_id">ID đối tượng</option>
                        </select>

                        <select id="mediaPerPage">
                            <option value="50" selected>50 / trang</option>
                            <option value="200">200 / trang</option>
                            <option value="500">500 / trang</option>
                            <option value="2000">2000 / trang</option>
                            <option value="5000">5000 / trang</option>
                            <option value="10000">10000 / trang</option>
                        </select>

                        <div class="media-view-switch">
                            <button type="button" class="is-active" data-view="grid" id="mediaViewGridBtn">Lưới</button>
                            <button type="button" data-view="list" id="mediaViewListBtn">Danh sách</button>
                        </div>
                    </div>
                </div>

                <div class="media-bulkbar" id="mediaBulkBar" hidden>
                    <div>
                        <strong id="mediaBulkCount">0</strong>
                        <span>mục đang được chọn</span>
                    </div>
                    <div class="media-bulkbar-actions">
                        <button type="button" class="media-btn media-btn-secondary" id="mediaSelectVisibleBtn">Chọn tất cả đang hiển thị</button>
                        <button type="button" class="media-btn media-btn-secondary" id="mediaClearSelectionBtn">Bỏ chọn</button>
                        <button type="button" class="media-btn media-btn-danger" id="mediaBulkDeleteBtn">Xóa các mục đã chọn</button>
                    </div>
                </div>

                <div class="media-results-meta">
                    <div id="mediaResultsSummary">
                        Hiển thị {{ number_format($initialPagination['from'] ?? 0) }} - {{ number_format($initialPagination['to'] ?? 0) }}
                        trên {{ number_format($initialPagination['total'] ?? 0) }} mục
                    </div>
                    <div id="mediaCurrentFilterText">Bộ lọc hiện tại: Tất cả</div>
                </div>

                <div class="media-grid is-grid" id="mediaGrid"></div>

                <div class="media-empty-state" id="mediaEmptyState" hidden>
                    <h3>Không có media phù hợp</h3>
                    <p>Thử đổi bộ lọc hoặc từ khóa để xem thêm kết quả.</p>
                </div>

                <div class="media-pagination" id="mediaPagination">
                    <button type="button" class="media-btn media-btn-secondary" id="mediaPrevBtn">Trang trước</button>
                    <span id="mediaPaginationText">
                        Trang {{ $initialPagination['current_page'] ?? 1 }} / {{ $initialPagination['last_page'] ?? 1 }}
                    </span>
                    <button type="button" class="media-btn media-btn-secondary" id="mediaNextBtn">Trang sau</button>
                </div>
            </main>

            <aside class="media-inspector" id="mediaInspector">
                <div class="media-inspector-empty" id="mediaInspectorEmpty">
                    <h3>Chọn một ảnh để xem chi tiết</h3>
                    <p>Inspector bên phải sẽ hiển thị metadata, tình trạng file, vị trí sử dụng và các thao tác gán/xóa.</p>
                </div>

                <div class="media-inspector-content" id="mediaInspectorContent" hidden>
                    <div class="media-inspector-preview">
                        <img src="" alt="" id="mediaInspectorImage">
                    </div>

                    <div class="media-inspector-head">
                        <div>
                            <h3 id="mediaInspectorTitle">-</h3>
                            <p id="mediaInspectorSubtitle">-</p>
                        </div>
                        <div class="media-inspector-badges" id="mediaInspectorBadges"></div>
                    </div>

                    <form id="mediaInspectorForm" class="media-inspector-form">
                        @csrf
                        <input type="hidden" id="mediaInspectorSource">
                        <input type="hidden" id="mediaInspectorId">

                        <div class="media-field">
                            <label for="mediaInspectorTitleInput">Tiêu đề</label>
                            <input type="text" id="mediaInspectorTitleInput" name="title">
                        </div>

                        <div class="media-field">
                            <label for="mediaInspectorAltInput">Alt text</label>
                            <input type="text" id="mediaInspectorAltInput" name="alt">
                        </div>

                        <div class="media-field">
                            <label for="mediaInspectorDescriptionInput">Ghi chú</label>
                            <textarea id="mediaInspectorDescriptionInput" name="description" rows="3"></textarea>
                        </div>

                        <label class="media-checkbox">
                            <input type="checkbox" id="mediaInspectorPrimaryInput" name="is_primary" value="1">
                            <span>Đặt làm ảnh chính nếu là ảnh sản phẩm</span>
                        </label>

                        <div class="media-inspector-actions">
                            <button type="submit" class="media-btn media-btn-primary" id="mediaSaveBtn">Lưu metadata</button>
                            <button type="button" class="media-btn media-btn-secondary" id="mediaCopyPathBtn">Chép đường dẫn</button>
                            <button type="button" class="media-btn media-btn-secondary" id="mediaOpenOriginalBtn">Mở file</button>
                            <button type="button" class="media-btn media-btn-danger" id="mediaDeleteBtn">Xóa mục này</button>
                        </div>
                    </form>

                    <div class="media-inspector-meta" id="mediaInspectorMeta"></div>

                    <div class="media-assign-box">
                        <div class="media-assign-head">
                            <h4>Gán ảnh vào đối tượng khác</h4>
                            <p>Hữu ích với file mồ côi hoặc khi tái sử dụng cùng một ảnh cho nhiều nơi.</p>
                        </div>

                        <form id="mediaAssignForm" class="media-assign-form">
                            @csrf
                            <input type="hidden" id="mediaAssignSource" name="source">
                            <input type="hidden" id="mediaAssignMediaId" name="media_id">

                            <div class="media-field">
                                <label for="mediaAssignTargetType">Loại đối tượng</label>
                                <select id="mediaAssignTargetType" name="target_type">
                                    @foreach($uploadTargets as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="media-field">
                                <label for="mediaAssignTargetId">Đối tượng cụ thể</label>
                                <select id="mediaAssignTargetId" name="target_id"></select>
                            </div>

                            <button type="submit" class="media-btn media-btn-primary" id="mediaAssignBtn">Gán ngay</button>
                        </form>
                    </div>
                </div>
            </aside>
        </section>
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
