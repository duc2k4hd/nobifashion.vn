@extends('admins.layouts.master')

@section('title', 'Quản lý Media')
@section('page-title', 'Media Library')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/media-icon.png') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
    <link rel="stylesheet" href="{{ asset('admins/css/media-manager.css?v=' . filemtime(public_path('admins/css/media-manager.css'))) }}">
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
                <button type="button" class="media-btn media-btn-secondary" id="mediaRefreshBtn">Quét lại dữ liệu</button>
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
                                    <span>{{ $folder['label'] }}</span>
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
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        window.mediaManagerConfig = {
            csrfToken: @json(csrf_token()),
            fallbackImage: @json(asset('clients/assets/no-image.webp')),
            routes: {
                search: @json(route('admin.media.search')),
                upload: @json(route('admin.media.upload')),
                updateBase: @json(url('/admin/media/update')),
                assign: @json(route('admin.media.assign')),
                bulkDelete: @json(route('admin.media.bulk-delete')),
                targets: @json(route('admin.media.targets')),
            },
            initialState: {
                items: @json($initialMedia),
                meta: @json($initialPagination),
                stats: @json($stats),
            },
        };
    </script>
    <script src="{{ asset('admins/js/media-manager.js?v=' . filemtime(public_path('admins/js/media-manager.js'))) }}"></script>
@endpush
