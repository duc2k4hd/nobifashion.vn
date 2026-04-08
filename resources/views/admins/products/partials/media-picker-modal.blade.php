@php
    $productMediaFolderGroups = $productMediaFolders->groupBy('scope');
@endphp

<div class="media-modal" id="productMediaPickerModal" hidden>
    <div class="media-modal__backdrop" data-product-media-close></div>

    <div class="media-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="productMediaPickerTitle">
        <div class="media-modal__header">
            <div>
                <p class="media-modal__eyebrow">Media picker cho sản phẩm</p>
                <h2 id="productMediaPickerTitle">Chọn ảnh cho gallery sản phẩm</h2>
                <p class="media-modal__description">
                    Modal này dùng chung toàn bộ backend và tính năng của quản lý media:
                    tìm kiếm, lọc, upload, sửa metadata, xóa, gán lại đối tượng và chọn ảnh để đưa vào gallery hoặc làm ảnh chính.
                </p>
            </div>

            <div class="media-modal__actions">
                <button type="button" class="media-btn media-btn-secondary" id="productMediaPickerCloseBtn">Đóng</button>
                <button type="button" class="media-btn media-btn-primary" id="productMediaPickerChooseBtn" disabled>Chọn ảnh đang active</button>
            </div>
        </div>

        <div class="media-modal__body">
            <div class="media-manager-page is-embedded">
                <section class="media-upload-panel is-collapsed" id="mediaUploadPanel">
                    <div class="media-panel-head">
                        <div>
                            <h2>Upload ảnh vào thư viện</h2>
                            <p>Upload xong có thể chọn ngay file vừa tải lên cho dòng ảnh hiện tại.</p>
                        </div>
                        <button type="button" class="media-panel-close" id="mediaCollapseUploadBtn" aria-label="Đóng panel upload">×</button>
                    </div>

                    <form id="mediaUploadForm" class="media-upload-form">
                        @csrf

                        <div class="media-form-grid">
                            <div class="media-field">
                                <label for="mediaUploadFolder">Thư mục đích</label>
                                <select name="folder" id="mediaUploadFolder" required>
                                    @foreach($productMediaFolderGroups as $scope => $items)
                                        <optgroup label="{{ $scope }}">
                                            @foreach($items as $folder)
                                                <option value="{{ $folder['key'] }}" @selected($folder['key'] === 'clothes')>
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
                                <p>Hoặc bấm để chọn nhiều file. Sau khi upload, danh sách trong modal sẽ được quét lại ngay.</p>
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
                                @foreach($productMediaStatusFilters as $key => $label)
                                    <button
                                        type="button"
                                        class="media-status-filter {{ $key === 'all' ? 'is-active' : '' }}"
                                        data-status="{{ $key }}"
                                    >
                                        <span>{{ $label }}</span>
                                        <strong data-status-count="{{ $key }}">0</strong>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="media-sidebar-section">
                            <h3>Thư mục đang theo dõi</h3>
                            <ul class="media-folder-list">
                                @foreach($productMediaFolders as $folder)
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
                            <h3>Nguyên tắc gán ảnh</h3>
                            <p>
                                Bạn có thể chọn ảnh từ bất kỳ thư mục media nào.
                                Khi lưu sản phẩm, hệ thống sẽ chuẩn hóa đường dẫn ảnh về đúng vùng ảnh sản phẩm để frontend không bị lệch logic.
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
                                    @foreach($productMediaTypeFilters as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>

                                <select id="mediaFilterFolder">
                                    <option value="all">Tất cả thư mục</option>
                                    @foreach($productMediaFolders as $folder)
                                        <option value="{{ $folder['key'] }}">{{ $folder['label'] }}</option>
                                    @endforeach
                                </select>

                                <select id="mediaFilterStatus">
                                    @foreach($productMediaStatusFilters as $key => $label)
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

                        <div class="media-browser-actions">
                            <button type="button" class="media-btn media-btn-secondary" id="mediaRefreshBtn">Quét lại</button>
                            <button type="button" class="media-btn media-btn-primary" id="mediaToggleUploadBtn">Tải ảnh mới</button>
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
                            <div id="mediaResultsSummary">Hiển thị 0 - 0 trên 0 mục</div>
                            <div id="mediaCurrentFilterText">Bộ lọc hiện tại: Tất cả</div>
                        </div>

                        <div class="media-grid is-grid" id="mediaGrid"></div>

                        <div class="media-empty-state" id="mediaEmptyState" hidden>
                            <h3>Không có media phù hợp</h3>
                            <p>Thử đổi bộ lọc hoặc từ khóa để xem thêm kết quả.</p>
                        </div>

                        <div class="media-pagination" id="mediaPagination">
                            <button type="button" class="media-btn media-btn-secondary" id="mediaPrevBtn">Trang trước</button>
                            <span id="mediaPaginationText">Trang 1 / 1</span>
                            <button type="button" class="media-btn media-btn-secondary" id="mediaNextBtn">Trang sau</button>
                        </div>
                    </main>

                    <aside class="media-inspector" id="mediaInspector">
                        <div class="media-inspector-empty" id="mediaInspectorEmpty">
                            <h3>Chọn một ảnh để xem chi tiết</h3>
                            <p>Inspector bên phải cho phép xem metadata, trạng thái file, gán lại đối tượng và chọn ảnh đó cho dòng gallery hiện tại.</p>
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
                                    <span>Đặt làm ảnh chính nếu đây là ảnh sản phẩm</span>
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
                                    <p>Dùng khi cần tái sử dụng một ảnh cho nhiều module.</p>
                                </div>

                                <form id="mediaAssignForm" class="media-assign-form">
                                    @csrf
                                    <input type="hidden" id="mediaAssignSource" name="source">
                                    <input type="hidden" id="mediaAssignMediaId" name="media_id">

                                    <div class="media-field">
                                        <label for="mediaAssignTargetType">Loại đối tượng</label>
                                        <select id="mediaAssignTargetType" name="target_type">
                                            @foreach($productMediaUploadTargets as $key => $label)
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
        </div>
    </div>

    <div class="media-loading-overlay" id="mediaLoadingOverlay" hidden aria-live="assertive" aria-busy="true">
        <div class="media-loading-dialog" role="status" aria-label="Đang xử lý thư viện ảnh">
            <span class="media-loading-spinner" aria-hidden="true"></span>
            <div class="media-loading-copy">
                <strong>Đang xử lý thư viện ảnh</strong>
                <p id="mediaLoadingMessage">Vui lòng chờ đến khi thao tác hiện tại hoàn tất.</p>
            </div>
        </div>
    </div>

    <div class="media-toast" id="mediaToast" hidden></div>
</div>
