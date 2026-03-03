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

                <div class="media-library-grid" id="media-grid">
                    <div class="loading-spinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang tải...</span>
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
            </div>
            <div id="load-more-container" class="load-more-container" style="display: none;"></div>
            <div class="footer-actions">
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

.tab-content {
    display: none;
    flex: 1;
    overflow: hidden;
    flex-direction: column;
    padding: 20px;
}

.tab-content.active {
    display: flex;
}

.media-library-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
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
    min-height: 400px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
}

.media-library-grid.grid-view {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
}

.media-library-grid.list-view {
    display: flex;
    flex-direction: column;
    gap: 8px;
}


.media-item {
    position: relative;
    border: 2px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    background: #f9fafb;
}

.media-item:hover {
    border-color: #2563eb;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.media-item.selected {
    border-color: #2563eb;
    background: #eff6ff;
}

.media-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    display: block;
}

.media-item.list-view {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px;
    max-width: 100%;
}

.media-item.list-view img {
    width: 80px;
    height: 80px;
    flex-shrink: 0;
}

.media-item-info {
    flex: 1;
    min-width: 0;
}

.media-item-name {
    font-weight: 500;
    font-size: 14px;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.media-item-meta {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.media-item-checkbox {
    position: absolute;
    top: 8px;
    left: 8px;
    width: 20px;
    height: 20px;
    z-index: 10;
}

.upload-area {
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.upload-area:hover {
    border-color: #2563eb;
    background: #f9fafb;
}

.upload-area.dragover {
    border-color: #2563eb;
    background: #eff6ff;
}

.upload-placeholder {
    color: #6b7280;
}

.media-library-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
    flex-wrap: wrap;
    gap: 12px;
}

.footer-actions {
    display: flex;
    gap: 8px;
}

.load-more-container {
    flex: 1;
    display: flex !important;
    justify-content: center;
    align-items: center;
    min-width: 150px;
    visibility: visible !important;
    order: 2;
    z-index: 10;
}

.load-more-container[style*="display: none"] {
    display: none !important;
}

.load-more-container .btn {
    min-width: 150px;
    padding: 10px 24px;
    font-weight: 500;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    position: relative;
    z-index: 11;
}

.loading-spinner {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px;
}
</style>
@endpush
