/**
 * WordPress-style Media Library
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
        this.perPage = 100;
        this.hasMore = false;
        this.isLoading = false;
        this.currentSearch = '';
        
        this.init();
    }

    init() {
        this.modal = document.getElementById('media-library-modal');
        if (!this.modal) {
            console.error('MediaLibrary: modal #media-library-modal not found');
            return;
        }

        const grid = document.getElementById('media-grid');
        if (grid) grid.className = `media-library-grid ${this.currentView}-view`;

        this.setupEventListeners();
        console.log('>>> Media Library Active v2.1 <<<');
    }

    setupEventListeners() {
        // Tab switching
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tab = e.target.dataset.tab;
                this.switchTab(tab);
            });
        });

        // Close modal
        document.getElementById('close-media-library')?.addEventListener('click', () => this.close());
        document.getElementById('cancel-media-library')?.addEventListener('click', () => this.close());
        this.modal.querySelector('.media-library-overlay')?.addEventListener('click', () => this.close());

        // Search
        document.getElementById('media-search')?.addEventListener('input', (e) => {
            this.searchMedia(e.target.value);
        });

        // View toggle
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const view = e.target.closest('.view-btn').dataset.view;
                this.switchView(view);
            });
        });

        // Upload
        const uploadArea = document.getElementById('upload-area');
        const fileInput = document.getElementById('file-input');
        
        uploadArea?.addEventListener('click', () => fileInput?.click());
        uploadArea?.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        uploadArea?.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        uploadArea?.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            this.handleUpload(Array.from(files));
        });
        fileInput?.addEventListener('change', (e) => {
            this.handleUpload(Array.from(e.target.files));
        });

        // Insert button
        document.getElementById('insert-media')?.addEventListener('click', () => this.insertSelected());

        // Escape key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.style.display !== 'none') this.close();
        });

        // ===== Grid event delegation (tồn tại vĩnh viễn dù innerHTML reset) =====
        const grid = document.getElementById('media-grid');
        if (grid) {
            grid.addEventListener('click', (e) => {
                if (e.target.type === 'checkbox') return;
                const item = e.target.closest('.media-item');
                if (item) this.toggleSelect(item.dataset.id);
            });

            grid.addEventListener('dblclick', (e) => {
                e.preventDefault();
                if (e.target.type === 'checkbox') return;
                const item = e.target.closest('.media-item');
                if (!item) return;
                const id   = item.dataset.id;
                const data = this.mediaItems.find(m => String(m.id) === String(id));
                console.log('🖱️ dblclick id:', id, '| found:', !!data);
                if (data) this.openDetailPanel(data);
            });
        }
    }

    switchTab(tab) {
        this.currentTab = tab;
        
        // Update tab buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tab);
        });

        // Update tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.toggle('active', content.id === `tab-${tab}`);
        });

        if (tab === 'library') {
            this.currentPage = 1;
            this.loadMedia(this.currentSearch, true);
        }
    }

    switchView(view) {
        this.currentView = view;
        const grid = document.getElementById('media-grid');
        
        if (grid) {
            grid.className = `media-library-grid ${view}-view`;
        }

        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === view);
        });

        this.renderMedia();
    }

    async loadMedia(search = '', reset = true) {
        const grid = document.getElementById('media-grid');
        if (!grid) return;

        if (this.isLoading) return;
        this.isLoading = true;

        // Nếu reset, hiển thị loading spinner và reset page
        if (reset) {
            this.currentPage = 1;
            this.mediaItems = [];
            grid.innerHTML = '<div class="loading-spinner"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Đang tải...</span></div></div>';
        } else {
            // Nếu load more, hiển thị loading indicator ở cuối
            const loadMoreBtn = document.getElementById('load-more-media');
            if (loadMoreBtn) {
                loadMoreBtn.disabled = true;
                loadMoreBtn.textContent = 'Đang tải...';
            }
        }

        try {
            const url = new URL(this.apiUrl, window.location.origin);
            url.searchParams.set('context', this.context);
            url.searchParams.set('page', this.currentPage);
            url.searchParams.set('per_page', this.perPage);
            
            if (search) {
                url.searchParams.set('search', search);
                this.currentSearch = search;
            } else {
                this.currentSearch = '';
            }

            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                if (reset) {
                    this.mediaItems = data.data || [];
                } else {
                    this.mediaItems = [...this.mediaItems, ...(data.data || [])];
                }

                this.hasMore = data.has_more === true || data.has_more === 1;
                this.renderMedia();
                setTimeout(() => this.updateLoadMoreButton(), 100);
            } else {
                if (reset) {
                    grid.innerHTML = '<div class="text-center text-muted p-4">Không tải được ảnh</div>';
                }
            }
        } catch (error) {
            console.error('Error loading media:', error);
            if (reset) {
                grid.innerHTML = '<div class="text-center text-danger p-4">Lỗi khi tải ảnh</div>';
            }
        } finally {
            this.isLoading = false;
        }
    }

    renderMedia() {
        const grid = document.getElementById('media-grid');
        if (!grid) return;

        if (this.mediaItems.length === 0) {
            grid.innerHTML = '<div class="text-center text-muted p-4">Chưa có ảnh nào</div>';
            this.updateLoadMoreButton();
            return;
        }

        const isListView = this.currentView === 'list';
        const itemsHtml = this.mediaItems.map(item => {
            const isSelected = this.selectedItems.some(sel => sel.id === item.id);
            const dimensions = item.dimensions ? `${item.dimensions.width} × ${item.dimensions.height}` : '';
            const size = this.formatFileSize(item.size);

            if (isListView) {
                return `
                    <div class="media-item list-view ${isSelected ? 'selected' : ''}" data-id="${item.id}">
                        <input type="checkbox" class="media-item-checkbox" ${isSelected ? 'checked' : ''} 
                               onchange="window.mediaLibrary.toggleSelect('${item.id}')">
                        <img src="${item.url}" alt="${item.alt || item.name}" loading="lazy">
                        <div class="media-item-info">
                            <div class="media-item-name">${this.escapeHtml(item.title || item.name)}</div>
                            <div class="media-item-meta">${dimensions} • ${size}</div>
                        </div>
                    </div>
                `;
            } else {
                return `
                    <div class="media-item ${isSelected ? 'selected' : ''}" data-id="${item.id}">
                        <input type="checkbox" class="media-item-checkbox" ${isSelected ? 'checked' : ''} 
                               onchange="window.mediaLibrary.toggleSelect('${item.id}')">
                        <img src="${item.url}" alt="${item.alt || item.name}" loading="lazy">
                        <div class="media-item-name" style="padding: 8px; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            ${this.escapeHtml(item.title || item.name)}
                        </div>
                        <div class="media-item-dblclick-hint">Click 2 lần để sửa</div>
                    </div>
                `;
            }
        }).join('');

        grid.innerHTML = itemsHtml;
        // Không cần gắn per-item listeners — click/dblclick dùng event delegation tại setupEventListeners()
        this.updateLoadMoreButton();
    }

    updateLoadMoreButton() {
        const loadMoreContainer = document.getElementById('load-more-container');
        if (!loadMoreContainer) return;

        const shouldShow = this.hasMore === true && this.mediaItems.length > 0;

        if (shouldShow) {
            loadMoreContainer.removeAttribute('style');
            loadMoreContainer.style.display = 'flex';
            loadMoreContainer.classList.remove('d-none');

            let loadMoreBtn = document.getElementById('load-more-media');
            if (!loadMoreBtn) {
                loadMoreBtn = document.createElement('button');
                loadMoreBtn.id = 'load-more-media';
                loadMoreBtn.className = 'btn btn-outline-primary';
                loadMoreBtn.textContent = 'Tải thêm ảnh';
                loadMoreBtn.addEventListener('click', () => {
                    this.currentPage++;
                    this.loadMedia(this.currentSearch, false);
                });
                loadMoreContainer.innerHTML = '';
                loadMoreContainer.appendChild(loadMoreBtn);
            } else {
                loadMoreBtn.disabled = false;
                loadMoreBtn.textContent = 'Tải thêm ảnh';
            }
        } else {
            loadMoreContainer.style.display = 'none';
            loadMoreContainer.innerHTML = '';
        }
    }


    toggleSelect(id) {
        const item = this.mediaItems.find(m => m.id === id);
        if (!item) return;

        const index = this.selectedItems.findIndex(sel => sel.id === id);
        if (index >= 0) {
            this.selectedItems.splice(index, 1);
        } else {
            if (this.insertMode === 'single') {
                this.selectedItems = [item];
            } else {
                this.selectedItems.push(item);
            }
        }

        this.updateSelection();
        
        // Thay vì renderMedia() làm mất event dblclick, ta cập nhật DOM trực tiếp
        const allItems = this.modal.querySelectorAll('.media-item');
        allItems.forEach(el => {
            const isSelected = this.selectedItems.some(sel => String(sel.id) === String(el.dataset.id));
            el.classList.toggle('selected', isSelected);
            const cb = el.querySelector('.media-item-checkbox');
            if (cb) cb.checked = isSelected;
        });
    }

    updateSelection() {
        const count = this.selectedItems.length;
        const info = document.getElementById('selected-info');
        const countEl = document.getElementById('selected-count');
        const insertBtn = document.getElementById('insert-media');

        if (count > 0) {
            info.style.display = 'block';
            countEl.textContent = count;
            insertBtn.disabled = false;
        } else {
            info.style.display = 'none';
            insertBtn.disabled = true;
        }
    }

    async handleUpload(files) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
        const allowedLabels = 'JPG, PNG, GIF, WEBP, AVIF';

        for (const file of files) {
            if (!allowedTypes.includes(file.type)) {
                alert(`❌ Định dạng không được hỗ trợ: "${file.name}"\nChỉ chấp nhận: ${allowedLabels}`);
                continue;
            }

            if (file.size > 10 * 1024 * 1024) {
                alert(`❌ File quá lớn: "${file.name}" (${(file.size / 1024 / 1024).toFixed(1)} MB)\nGiới hạn tối đa: 10 MB`);
                continue;
            }

            const formData = new FormData();
            formData.append('file', file);
            formData.append('context', this.context);

            try {
                const response = await fetch(`${this.apiUrl}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: formData,
                });

                // Đọc body text trước để tránh crash khi server trả HTML thay vì JSON
                const bodyText = await response.text();
                let data = null;
                try {
                    data = JSON.parse(bodyText);
                } catch (parseErr) {
                    // Server trả HTML (lỗi 500 Laravel) – log để debug
                    console.error(`❌ Server error ${response.status} khi upload "${file.name}":`, bodyText.substring(0, 500));
                    alert(`❌ Lỗi server (${response.status}) khi upload "${file.name}".\nVui lòng kiểm tra console để xem chi tiết lỗi.`);
                    continue;
                }

                if (data && data.success) {
                    // Reload media để có thứ tự đúng
                    this.currentPage = 1;
                    this.loadMedia(this.currentSearch, true);
                    this.switchTab('library');
                } else {
                    // Hiển thị lỗi validation rõ ràng nếu có
                    let msg = '';
                    if (data && data.errors) {
                        msg = Object.values(data.errors).flat().join('\n');
                    } else {
                        msg = (data && data.message) || `Lỗi HTTP ${response.status}`;
                    }
                    alert(`❌ Không thể upload "${file.name}":\n${msg}`);
                }
            } catch (error) {
                console.error('Upload network error:', error);
                alert(`❌ Lỗi kết nối khi upload "${file.name}".\nVui lòng kiểm tra mạng và thử lại.`);
            }
        }

        // Reset file input
        document.getElementById('file-input').value = '';
    }

    searchMedia(query) {
        this.currentPage = 1;
        this.loadMedia(query, true);
    }

    insertSelected() {
        if (this.selectedItems.length === 0) return;

        if (this.onInsert) {
            if (this.insertMode === 'single') {
                this.onInsert(this.selectedItems[0]);
            } else {
                this.onInsert(this.selectedItems);
            }
        }

        this.close();
    }

    open(options = {}) {
        if (options.onInsert) {
            this.onInsert = options.onInsert;
        }
        if (options.insertMode) {
            this.insertMode = options.insertMode;
        }
        if (options.context) {
            this.context = options.context;
        }

        this.selectedItems = [];
        this.currentPage = 1;
        this.currentSearch = '';
        this.updateSelection();
        this.switchTab('library');
        this.modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        this.loadMedia('', true);
    }

    close() {
        this.modal.style.display = 'none';
        document.body.style.overflow = '';
        this.selectedItems = [];
        this.updateSelection();
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ===== Detail Panel Methods =====

    openDetailPanel(item) {
        this._detailItem = item;

        const panel  = document.getElementById('media-detail-panel');
        const img    = document.getElementById('detail-preview-img');
        const meta   = document.getElementById('detail-meta');
        const title  = document.getElementById('detail-title');
        const alt    = document.getElementById('detail-alt');

        if (!panel) return;

        img.src     = item.url;
        img.alt     = item.alt || item.name;
        title.value = item.title || '';
        alt.value   = item.alt   || '';

        const dim  = item.dimensions ? `${item.dimensions.width} \u00d7 ${item.dimensions.height} px` : '\u2013';
        const size = this.formatFileSize(item.size || 0);
        meta.innerHTML = `<strong>${this.escapeHtml(item.name)}</strong>${dim}<br>${size}`;

        panel.style.display = 'flex';

        // Gắn listener chỉ 1 lần
        if (!panel._listenersAttached) {
            panel._listenersAttached = true;
            document.getElementById('close-detail-panel')
                ?.addEventListener('click', () => this.closeDetailPanel());
            document.getElementById('save-detail')
                ?.addEventListener('click', () => this.saveDetail());
            document.getElementById('delete-detail')
                ?.addEventListener('click', () => this.deleteDetail());
        }
    }

    closeDetailPanel() {
        const panel = document.getElementById('media-detail-panel');
        if (panel) panel.style.display = 'none';
        this._detailItem = null;
    }

    async saveDetail() {
        if (!this._detailItem) return;

        const id    = this._detailItem.id;
        const title = document.getElementById('detail-title')?.value.trim() || '';
        const alt   = document.getElementById('detail-alt')?.value.trim()   || '';
        const btn   = document.getElementById('save-detail');
        const txt   = document.getElementById('save-detail-text');

        if (btn) btn.disabled = true;
        if (txt) txt.textContent = '\u0110ang l\u01b0u...';

        try {
            const res  = await fetch(`${this.apiUrl}/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({ title, alt }),
            });
            const data = await res.json();

            if (data.success) {
                if (txt) txt.textContent = '\u2713 \u0110\u00e3 l\u01b0u';
                setTimeout(() => { if (txt) txt.textContent = 'L\u01b0u thay \u0111\u1ed5i'; }, 2000);

                // Cập nhật trong mediaItems
                const idx = this.mediaItems.findIndex(m => m.id === id);
                if (idx !== -1) {
                    this.mediaItems[idx].title = title;
                    this.mediaItems[idx].alt   = alt;
                }
                // Cập nhật data-item trên DOM
                const el = document.querySelector(`.media-item[data-id="${id}"]`);
                if (el && el.dataset.item) {
                    try {
                        const d = JSON.parse(el.dataset.item);
                        d.title = title; d.alt = alt;
                        el.dataset.item = JSON.stringify(d);
                    } catch {}
                }
            } else {
                alert('\u274c L\u01b0u th\u1ea5t b\u1ea1i: ' + (data.message || 'L\u1ed7i kh\u00f4ng x\u00e1c \u0111\u1ecbnh'));
                if (txt) txt.textContent = 'L\u01b0u thay \u0111\u1ed5i';
            }
        } catch (err) {
            console.error('Save detail error:', err);
            alert('\u274c L\u1ed7i k\u1ebft n\u1ed1i khi l\u01b0u');
            if (txt) txt.textContent = 'L\u01b0u thay \u0111\u1ed5i';
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    async deleteDetail() {
        if (!this._detailItem) return;

        const item = this._detailItem;
        if (!confirm(`X\u00f3a \u1ea3nh "${item.name}"?\nH\u00e0nh \u0111\u1ed9ng n\u00e0y kh\u00f4ng th\u1ec3 ho\u00e0n t\u00e1c.`)) return;

        try {
            const res  = await fetch(`${this.apiUrl}/${item.id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Content-Type': 'application/json',
                },
            });
            const data = await res.json();

            if (data.success) {
                this.closeDetailPanel();
                this.mediaItems = this.mediaItems.filter(m => m.id !== item.id);
                this.renderMedia();
            } else {
                alert('\u274c X\u00f3a th\u1ea5t b\u1ea1i: ' + (data.message || 'L\u1ed7i kh\u00f4ng x\u00e1c \u0111\u1ecbnh'));
            }
        } catch (err) {
            console.error('Delete detail error:', err);
            alert('\u274c L\u1ed7i k\u1ebft n\u1ed1i khi x\u00f3a');
        }
    }
}


// Initialize global instance
if (typeof window !== 'undefined') {
    window.MediaLibrary = MediaLibrary;
    window.mediaLibrary = new MediaLibrary({
        apiUrl: '/admin/media/library',
    });
}
