<!-- Media Library Modal (WordPress Style) -->
<div id="media-library-modal" class="media-library-modal" style="display: none;">
    <div class="media-library-overlay"></div>
    <div class="media-library-container">
        <div class="media-library-header">
            <div class="media-library-tabs">
                <button class="tab-button active" data-tab="library">Thư viện</button>
                <button class="tab-button" data-tab="upload">Tải lên</button>
            </div>
            <button class="close-button" id="close-media-library">&times;</button>
        </div>

        <div class="media-library-content">
            <!-- Library Tab -->
            <div class="tab-content active" id="tab-library">
                <div class="media-library-toolbar">
                    <div class="search-box">
                        <input type="text" id="media-search" placeholder="Tìm kiếm ảnh..." class="form-control">
                    </div>
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="grid" title="Grid view">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M1 1h6v6H1V1zm8 0h6v6H9V1zM1 9h6v6H1V9zm8 0h6v6H9V9z"/>
                            </svg>
                        </button>
                        <button class="view-btn" data-view="list" title="List view">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M1 1h14v2H1V1zm0 4h14v2H1V5zm0 4h14v2H1V9zm0 4h14v2H1v-2z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="media-library-body">
                    <div class="media-library-main">
                        <div class="media-library-grid" id="media-grid">
                            <div class="loading-spinner">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Đang tải...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar Chi tiết -->
                    <div class="media-library-sidebar" id="media-detail-panel" style="display: none;">
                        <div class="sidebar-header">
                            <h6>CHI TIẾT TẬP TIN</h6>
                            <button type="button" id="close-detail-panel" class="close-detail">&times;</button>
                        </div>
                        <div class="sidebar-content">
                            <div class="detail-preview">
                                <img id="detail-preview-img" src="" alt="">
                            </div>
                            <div id="detail-meta" class="detail-meta"></div>
                            <hr>
                            <div class="form-group mb-3">
                                <label class="form-label small fw-bold">Tiêu đề (Title)</label>
                                <input type="text" id="detail-title" class="form-control form-control-sm">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label small fw-bold">Văn bản thay thế (Alt)</label>
                                <input type="text" id="detail-alt" class="form-control form-control-sm">
                            </div>
                            <div class="detail-actions">
                                <button type="button" id="save-detail" class="btn btn-primary btn-sm w-100 mb-2">
                                    <span id="save-detail-text">Lưu thay đổi</span>
                                </button>
                                <button type="button" id="delete-detail" class="btn btn-outline-danger btn-sm w-100">Xóa vĩnh viễn</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upload Tab -->
            <div class="tab-content" id="tab-upload">
                <div class="upload-area" id="upload-area">
                    <div class="upload-placeholder">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <p class="mt-3 mb-0">Kéo thả ảnh vào đây hoặc click để chọn</p>
                        <small class="text-muted">Hỗ trợ: JPG, PNG, GIF, WEBP, AVIF (tối đa 5MB)</small>
                    </div>
                    <input type="file" id="file-input" multiple accept="image/jpeg,image/png,image/gif,image/webp,image/avif" style="display: none;">
                </div>
            </div>
        </div>

        <div class="media-library-footer">
            <div class="selected-info" id="selected-info" style="display: none;">
                <span id="selected-count">0</span> ảnh đã chọn
                <button type="button" class="btn btn-outline-danger btn-sm ml-3" id="delete-bulk-media" style="margin-left: 15px; display: none;" onclick="window.mediaLibrary.deleteBulk()">
                    <i class="fas fa-trash-alt"></i> Xóa các tệp này
                </button>
            </div>
            <div id="load-more-container" class="load-more-container" style="display: none;"></div>
            <div class="footer-actions">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.mediaLibrary.loadMedia('', true)" style="margin-right: auto;">
                    <i class="fas fa-sync-alt"></i> Làm mới
                </button>
                <button class="btn btn-secondary" id="cancel-media-library">Hủy</button>
                <button class="btn btn-primary" id="insert-media" disabled>Chèn vào editor</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.media-library-modal {
    position: fixed;
    inset: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.media-library-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(4px);
}

.media-library-container {
    position: relative;
    background: #fff;
    border-radius: 8px;
    width: 90%;
    max-width: 1200px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.media-library-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
}

.media-library-tabs {
    display: flex;
    gap: 8px;
}

.tab-button {
    padding: 8px 16px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-weight: 500;
    color: #6b7280;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
}

.tab-button.active {
    color: #2563eb;
    border-bottom-color: #2563eb;
}

.close-button {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6b7280;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: background 0.2s;
}

.close-button:hover {
    background: #f3f4f6;
}

.media-library-content {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.media-library-body {
    display: flex;
    flex: 1;
    overflow: hidden;
    height: 100%;
}

.media-library-main {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
    position: relative;
}

.media-library-sidebar {
    width: 320px;
    background: #f8f9fa;
    border-left: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    animation: sidebarSlide 0.2s ease-out;
    position: relative;
    z-index: 100;
}

@keyframes sidebarSlide {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.sidebar-header {
    padding: 12px 15px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    position: sticky;
    top: 0;
    z-index: 105;
}

.sidebar-header h6 {
    margin: 0;
    font-size: 13px;
    color: #495057;
    text-transform: uppercase;
    font-weight: 700;
}

.close-detail {
    background: none;
    border: none;
    font-size: 20px;
    line-height: 1;
    color: #adb5bd;
    cursor: pointer;
}

.sidebar-content {
    padding: 15px;
}

.detail-preview {
    margin-bottom: 15px;
    background: #f0f0f0;
    border-radius: 4px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 200px;
    border: 1px solid #e9ecef;
}

.detail-preview img {
    max-width: 100%;
    max-height: 250px;
    object-fit: contain;
}

.detail-meta {
    font-size: 11px;
    color: #6c757d;
    line-height: 1.6;
}

.detail-meta strong {
    display: block;
    color: #212529;
    margin-bottom: 4px;
    word-break: break-all;
    font-size: 13px;
}

.tab-content {
    display: none;
    flex: 1;
    overflow: hidden;
    flex-direction: column;
    padding: 0;
}

.tab-content.active {
    display: flex;
}

.media-library-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    gap: 12px;
}

.search-box {
    flex: 1;
}

.view-toggle {
    display: flex;
    gap: 4px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 4px;
}

.view-btn {
    background: transparent;
    border: none;
    padding: 6px 8px;
    cursor: pointer;
    color: #6b7280;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.view-btn.active {
    background: #2563eb;
    color: #fff;
}

.media-library-grid {
    overflow-y: auto;
    padding: 15px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 15px;
}

.media-library-grid.list-view {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.media-item {
    position: relative;
    border: 2px solid transparent;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s;
    background: #fff;
    aspect-ratio: 1;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.media-item:hover {
    border-color: #2563eb;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
}

.media-item.selected {
    border-color: #2563eb;
    background: #eff6ff;
}

.media-item.selected::after {
    content: "✓";
    position: absolute;
    top: 5px;
    right: 5px;
    background: #2563eb;
    color: #fff;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
    z-index: 5;
}

.media-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.media-item-name {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.9);
    padding: 5px 8px;
    font-size: 11px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    border-top: 1px solid #eee;
}

/* Tooltip Hint */
.media-item::before {
    content: "Click đúp để sửa";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0, 0, 0, 0.7);
    color: #fff;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 10px;
    opacity: 0;
    transition: opacity 0.2s;
    z-index: 10;
    white-space: nowrap;
    pointer-events: none;
}

.media-item:hover::before {
    opacity: 1;
}

.media-item.list-view {
    aspect-ratio: auto;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px;
}

.media-item.list-view img {
    width: 60px;
    height: 60px;
    flex-shrink: 0;
    border-radius: 4px;
}

.media-item-info {
    flex: 1;
    min-width: 0;
}

.media-item-checkbox {
    position: absolute;
    top: 8px;
    left: 8px;
    z-index: 11;
}

.upload-area {
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 20px;
}

.upload-area:hover, .upload-area.dragover {
    border-color: #2563eb;
    background: #f9fafb;
}

.upload-placeholder {
    color: #6b7280;
}

.media-library-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    border-top: 1px solid #e5e7eb;
    background: #f8f9fa;
    gap: 12px;
}

.footer-actions {
    display: flex;
    gap: 10px;
}

.load-more-container {
    display: flex;
    justify-content: center;
    padding: 15px;
}

.loading-spinner {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px;
    width: 100%;
}
</style>
@endpush
