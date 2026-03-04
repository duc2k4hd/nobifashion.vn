/**
 * WordPress-style Media Library (Synced, Optimized & High Performance)
 * Version: 3.0
 */
class MediaLibrary {
    constructor(options = {}) {
        this.modal = document.getElementById('media-library-modal');
        this.mediaItems = [];
        this.selectedItems = [];
        this.currentView = 'grid';
        this.currentTab = 'library';
        this.onInsert = options.onInsert || null;
        this.insertMode = options.insertMode || 'single'; // single, multiple
        this.apiUrl = options.apiUrl || '/admin/media/library';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.context = options.context || 'product'; // product hoặc post
        this.currentPage = 1;
        this.perPage = 60; // Tăng lên 60 để trải nghiệm cuộn tốt hơn
        this.hasMore = false;
        this.isLoading = false;
        this.currentSearch = '';
        this.cacheValid = false; // Cờ kiểm soát cache
        
        console.log('MediaLibrary V3.0 Constructor called.');
        this.init();
    }

    init() {
        this.modal = document.getElementById('media-library-modal');
        if (!this.modal) return;

        const grid = document.getElementById('media-grid');
        if (grid) grid.className = `media-library-grid ${this.currentView}-view`;

        this.setupEventListeners();
    }

    setupEventListeners() {
        // Tab switching
        this.modal.querySelectorAll('.tab-button').forEach(btn => {
            btn.addEventListener('click', (e) => this.switchTab(e.target.dataset.tab));
        });

        // Close modal
        document.getElementById('close-media-library')?.addEventListener('click', () => this.close());
        document.getElementById('cancel-media-library')?.addEventListener('click', () => this.close());
        this.modal.querySelector('.media-library-overlay')?.addEventListener('click', () => this.close());

        // Search với Debounce để giảm query server
        let searchTimeout;
        document.getElementById('media-search')?.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => this.searchMedia(e.target.value), 400);
        });

        // View toggle
        this.modal.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const view = e.target.closest('.view-btn').dataset.view;
                this.switchView(view);
            });
        });

        const uploadArea = document.getElementById('upload-area');
        const fileInput = document.getElementById('file-input');
        uploadArea?.addEventListener('click', () => fileInput?.click());
        fileInput?.addEventListener('change', (e) => this.handleUpload(Array.from(e.target.files)));

        // Drag & Drop
        if (uploadArea) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => uploadArea.classList.add('dragover'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => uploadArea.classList.remove('dragover'), false);
            });

            uploadArea.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                const files = dt.files;
                this.handleUpload(Array.from(files));
            }, false);
        }

        // Insert button
        document.getElementById('insert-media')?.addEventListener('click', () => this.insertSelected());

        // Event Delegation cho Grid - KHÔNG bao giờ bị mất khi render lại innerHTML
        const grid = document.getElementById('media-grid');
        if (grid) {
            grid.addEventListener('click', (e) => {
                const item = e.target.closest('.media-item');
                if (!item || e.target.type === 'checkbox') return;
                this.toggleSelect(item.dataset.id);
            });

            grid.addEventListener('dblclick', (e) => {
                const item = e.target.closest('.media-item');
                if (!item) return;
                const mediaItem = this.mediaItems.find(m => String(m.id) === String(item.dataset.id));
                if (mediaItem) this.openDetailPanel(mediaItem);
            });
        }

        // Shortcut Ctrl + A
        window.addEventListener('keydown', (e) => {
            if (this.modal && this.modal.style.display === 'flex') {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'a') {
                    // Chỉ thực hiện nếu đang ở tab library
                    if (this.currentTab === 'library') {
                        e.preventDefault();
                        this.selectAll();
                    }
                }
            }
        });
    }

    switchTab(tab) {
        if (this.currentTab === tab) return;
        this.currentTab = tab;
        this.modal.querySelectorAll('.tab-button').forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tab));
        this.modal.querySelectorAll('.tab-content').forEach(content => content.classList.toggle('active', content.id === `tab-${tab}`));

        if (tab === 'library' && !this.cacheValid) {
            this.loadMedia('', true);
        }
    }

    switchView(view) {
        if (this.currentView === view) return;
        this.currentView = view;
        const grid = document.getElementById('media-grid');
        if (grid) {
            grid.className = `media-library-grid ${view}-view`;
            this.renderMedia(); // Cần render lại vì cấu trúc Grid/List khác nhau
        }
        this.modal.querySelectorAll('.view-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.view === view));
    }

    async loadMedia(search = '', reset = true) {
        const grid = document.getElementById('media-grid');
        if (!grid || this.isLoading) return;

        this.isLoading = true;
        if (reset) {
            this.currentPage = 1;
            this.mediaItems = [];
            grid.innerHTML = '<div class="loading-spinner"><div class="spinner-border text-primary" role="status"></div></div>';
        }

        try {
            const url = new URL(this.apiUrl, window.location.origin);
            url.searchParams.set('context', this.context);
            url.searchParams.set('page', this.currentPage);
            url.searchParams.set('per_page', this.perPage);
            if (search) url.searchParams.set('search', search);

            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                const newItems = data.data || [];
                if (reset) {
                    this.mediaItems = newItems;
                    this.renderMedia();
                } else {
                    this.mediaItems = [...this.mediaItems, ...newItems];
                    this.appendMediaItems(newItems); // Tối ưu: Chỉ vẽ thêm ảnh mới
                }
                
                this.hasMore = !!data.has_more;
                this.cacheValid = true;
                this.updateLoadMoreButton();
            }
        } catch (error) {
            console.error('Fetch error:', error);
            if (reset) grid.innerHTML = '<div class="text-danger p-4 text-center">Lỗi tải thư viện</div>';
        } finally {
            this.isLoading = false;
        }
    }

    // Render toàn bộ (chỉ dùng khi reset hoặc đổi View)
    renderMedia() {
        const grid = document.getElementById('media-grid');
        if (!grid) return;
        if (this.mediaItems.length === 0) {
            grid.innerHTML = '<div class="text-muted p-4 text-center">Chưa có ảnh nào</div>';
            return;
        }
        grid.innerHTML = this.mediaItems.map(item => this.getItemHtml(item)).join('');
        this.updateLoadMoreButton();
    }

    // TỐI ƯU CỰC MẠNH: Chỉ vẽ thêm vào DOM mà không xóa cái cũ
    appendMediaItems(items) {
        const grid = document.getElementById('media-grid');
        if (!grid || !items.length) return;
        const html = items.map(item => this.getItemHtml(item)).join('');
        grid.insertAdjacentHTML('beforeend', html);
    }

    getItemHtml(item) {
        const isSelected = this.selectedItems.some(sel => String(sel.id) === String(item.id));
        const dimensions = item.dimensions ? `${item.dimensions.width} × ${item.dimensions.height}` : '';
        const size = this.formatFileSize(item.size);

        if (this.currentView === 'list') {
            return `
                <div class="media-item list-view ${isSelected ? 'selected' : ''}" data-id="${item.id}">
                    <input type="checkbox" class="media-item-checkbox" ${isSelected ? 'checked' : ''} onchange="window.mediaLibrary.toggleSelect('${item.id}')">
                    <img src="${item.url}" alt="${item.alt || item.name}" loading="lazy">
                    <div class="media-item-info">
                        <div class="media-item-name">${this.escapeHtml(item.title || item.name)}</div>
                        <div class="media-item-meta">${dimensions} • ${size}</div>
                    </div>
                </div>
            `;
        }
        return `
            <div class="media-item ${isSelected ? 'selected' : ''}" data-id="${item.id}">
                <input type="checkbox" class="media-item-checkbox" ${isSelected ? 'checked' : ''} onchange="window.mediaLibrary.toggleSelect('${item.id}')">
                <img src="${item.url}" alt="${item.alt || item.name}" loading="lazy">
                <div class="media-item-name" title="${item.title || item.name}">${this.escapeHtml(item.title || item.name)}</div>
            </div>
        `;
    }

    updateLoadMoreButton() {
        const container = document.getElementById('load-more-container');
        if (!container) return;
        
        if (this.hasMore) {
            container.style.display = 'flex';
            if (!document.getElementById('load-more-media')) {
                container.innerHTML = '<button id="load-more-media" class="btn btn-outline-primary">Tải thêm ảnh</button>';
                document.getElementById('load-more-media').addEventListener('click', () => {
                    this.currentPage++;
                    this.loadMedia(this.currentSearch, false);
                });
            }
        } else {
            container.style.display = 'none';
        }
    }

    toggleSelect(id) {
        const item = this.mediaItems.find(m => String(m.id) === String(id));
        if (!item) return;

        const index = this.selectedItems.findIndex(sel => String(sel.id) === String(id));
        if (index >= 0) {
            this.selectedItems.splice(index, 1);
        } else {
            if (this.insertMode === 'single') this.selectedItems = [item];
            else this.selectedItems.push(item);
        }

        this.syncSelectionDOM();
        this.updateFooter();
    }

    selectAll() {
        if (this.mediaItems.length === 0) return;
        
        // Nếu đã chọn tất cả rồi thì bỏ chọn tất cả (toggle behavior)
        if (this.selectedItems.length === this.mediaItems.length) {
            this.selectedItems = [];
        } else {
            this.selectedItems = [...this.mediaItems];
        }
        
        this.syncSelectionDOM();
        this.updateFooter();
        console.log(`Selected ${this.selectedItems.length} items`);
    }

    // TỐI ƯU: Chỉ cập nhật Class và Checkbox, không Render lại toàn bộ
    syncSelectionDOM() {
        const allItems = this.modal.querySelectorAll('.media-item');
        allItems.forEach(el => {
            const isSelected = this.selectedItems.some(sel => String(sel.id) === String(el.dataset.id));
            el.classList.toggle('selected', isSelected);
            const cb = el.querySelector('.media-item-checkbox');
            if (cb) cb.checked = isSelected;
        });
    }

    updateFooter() {
        const info = document.getElementById('selected-info');
        const countEl = document.getElementById('selected-count');
        const insertBtn = document.getElementById('insert-media');
        const deleteBulkBtn = document.getElementById('delete-bulk-media');
        const count = this.selectedItems.length;

        if (info) info.style.display = count > 0 ? 'inline-block' : 'none';
        if (countEl) countEl.textContent = count;
        if (insertBtn) insertBtn.disabled = count === 0;
        if (deleteBulkBtn) deleteBulkBtn.style.display = count > 0 ? 'inline-block' : 'none';
    }

    async handleUpload(files) {
        for (const file of files) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('context', this.context);
            try {
                const response = await fetch(`${this.apiUrl}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken },
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    this.cacheValid = false; // Đánh dấu cần tải lại sau khi upload
                    this.loadMedia(this.currentSearch, true);
                    this.switchTab('library');
                }
            } catch (err) { console.error('Upload failed', err); }
        }
    }

    searchMedia(query) {
        if (this.currentSearch === query) return;
        this.currentSearch = query;
        this.cacheValid = false;
        this.loadMedia(query, true);
    }

    open(options = {}) {
        if (options.onInsert) this.onInsert = options.onInsert;
        if (options.insertMode) this.insertMode = options.insertMode;
        if (options.context && this.context !== options.context) {
            this.context = options.context;
            this.cacheValid = false; // Context khác thì phải xóa cache
        }

        this.modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // TỐI ƯU: Nếu đã có dữ liệu và cache còn hiệu lực, không load lại mạng
        if (!this.cacheValid || this.mediaItems.length === 0) {
            this.loadMedia('', true);
        }
    }

    close() {
        this.modal.style.display = 'none';
        document.body.style.overflow = '';
        this.closeDetailPanel();
    }

    // ===== DETAIL PANEL (CẬP NHẬT TRỰC TIẾP DOM) =====
    openDetailPanel(item) {
        this.currentDetailItem = item;
        const panel = document.getElementById('media-detail-panel');
        if (!panel) return;

        const img = document.getElementById('detail-preview-img');
        const meta = document.getElementById('detail-meta');
        const title = document.getElementById('detail-title');
        const alt = document.getElementById('detail-alt');

        if (img) img.src = item.url;
        if (title) title.value = item.title || '';
        if (alt) alt.value = item.alt || '';
        if (meta) {
            const dim = item.dimensions ? `${item.dimensions.width} × ${item.dimensions.height} px` : '';
            meta.innerHTML = `<strong>${this.escapeHtml(item.name)}</strong>${dim}<br>${this.formatFileSize(item.size)}`;
        }

        panel.style.display = 'flex';

        if (!panel.dataset.initialized) {
            document.getElementById('close-detail-panel')?.addEventListener('click', () => this.closeDetailPanel());
            document.getElementById('save-detail')?.addEventListener('click', () => this.saveDetail());
            document.getElementById('delete-detail')?.addEventListener('click', () => this.deleteDetail());
            panel.dataset.initialized = 'true';
        }
    }

    closeDetailPanel() {
        const panel = document.getElementById('media-detail-panel');
        if (panel) panel.style.display = 'none';
        this.currentDetailItem = null;
    }

    async saveDetail() {
        if (!this.currentDetailItem) return;
        const id = this.currentDetailItem.id;
        const title = document.getElementById('detail-title').value;
        const alt = document.getElementById('detail-alt').value;
        const btn = document.getElementById('save-detail');

        btn.disabled = true;
        try {
            const res = await fetch(`${this.apiUrl}/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                body: JSON.stringify({ title, alt })
            });
            if ((await res.json()).success) {
                // TỐI ƯU: Cập nhật dữ liệu bộ nhớ
                const item = this.mediaItems.find(m => String(m.id) === String(id));
                if (item) { item.title = title; item.alt = alt; }
                
                // TỐI ƯU: Cập nhật DOM trực tiếp không render lại grid
                const itemEl = this.modal.querySelector(`.media-item[data-id="${id}"] .media-item-name`);
                if (itemEl) itemEl.textContent = title || item.name;

                const txt = document.getElementById('save-detail-text');
                txt.textContent = 'Đã lưu!';
                setTimeout(() => { txt.textContent = 'Lưu thay đổi'; btn.disabled = false; }, 1500);
            }
        } catch (e) { btn.disabled = false; }
    }

    async deleteDetail() {
        if (!this.currentDetailItem || !confirm('Xóa vĩnh viễn tệp này?')) return;
        const id = this.currentDetailItem.id;
        try {
            const res = await fetch(`${this.apiUrl}/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrfToken }
            });
            if ((await res.json()).success) {
                this.closeDetailPanel();
                // TỐI ƯU: Xóa khỏi bộ nhớ
                this.mediaItems = this.mediaItems.filter(m => String(m.id) !== String(id));
                this.selectedItems = this.selectedItems.filter(m => String(m.id) !== String(id));
                // TỐI ƯU: Xóa trực tiếp khỏi DOM
                this.modal.querySelector(`.media-item[data-id="${id}"]`)?.remove();
                this.updateFooter();
            }
        } catch (e) {}
    }

    async deleteBulk() {
        const count = this.selectedItems.length;
        if (count === 0 || !confirm(`Bạn có chắc muốn xóa vĩnh viễn ${count} tệp đã chọn?`)) return;

        const ids = this.selectedItems.map(item => item.id);
        const btn = document.getElementById('delete-bulk-media');
        if (btn) btn.disabled = true;

        try {
            const response = await fetch(`${this.apiUrl}/bulk-delete`, {
                method: 'DELETE',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken 
                },
                body: JSON.stringify({ ids })
            });
            const data = await response.json();

            if (data.success) {
                // Xóa khỏi bộ nhớ
                const deletedIds = ids.map(id => String(id));
                this.mediaItems = this.mediaItems.filter(m => !deletedIds.includes(String(m.id)));
                this.selectedItems = [];

                // Xóa khỏi DOM
                deletedIds.forEach(id => {
                    this.modal.querySelector(`.media-item[data-id="${id}"]`)?.remove();
                });

                this.updateFooter();
                if (this.mediaItems.length === 0 && this.hasMore) {
                    this.loadMedia(this.currentSearch, true);
                }
            } else {
                alert(data.message || 'Lỗi khi xóa hàng loạt');
            }
        } catch (error) {
            console.error('Bulk delete error:', error);
            alert('Lỗi kết nối khi xóa hàng loạt');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    formatFileSize(b) {
        if (!b) return '0 B';
        const k = 1024, s = ['B', 'KB', 'MB', 'GB'], i = Math.floor(Math.log(b) / Math.log(k));
        return parseFloat((b / Math.pow(k, i)).toFixed(1)) + ' ' + s[i];
    }

    escapeHtml(t) {
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }
}

// Singleton auto-init
if (typeof window !== 'undefined') {
    window.mediaLibrary = new MediaLibrary();
}
