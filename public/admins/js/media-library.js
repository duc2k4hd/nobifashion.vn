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
        console.log('MediaLibrary.init() called');
        console.log('Looking for modal element with id: media-library-modal');
        this.modal = document.getElementById('media-library-modal');
        
        if (!this.modal) {
            console.error('❌ Modal element not found! Make sure media-library-modal is included in the page.');
            console.log('Available elements with "media" in id:', 
                Array.from(document.querySelectorAll('[id*="media"]')).map(el => el.id));
            return;
        }

        console.log('✅ Modal element found:', this.modal);

        // 初始化grid view
        const grid = document.getElementById('media-grid');
        if (grid) {
            grid.className = `media-library-grid ${this.currentView}-view`;
            console.log('✅ Grid element found and initialized');
        } else {
            console.warn('⚠️ Grid element not found');
        }

        this.setupEventListeners();
        console.log('✅ MediaLibrary initialization complete');
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
        document.getElementById('insert-media')?.addEventListener('click', () => {
            this.insertSelected();
        });

        // Escape key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.style.display !== 'none') {
                this.close();
            }
        });
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

            console.log('=== MEDIA LIBRARY DEBUG ===');
            console.log('API Response:', data);
            console.log('URL:', url.toString());

            if (data.success) {
                if (reset) {
                    this.mediaItems = data.data || [];
                } else {
                    // Append new items
                    this.mediaItems = [...this.mediaItems, ...(data.data || [])];
                }
                
                // 确保正确设置 hasMore
                this.hasMore = Boolean(data.has_more === true || data.has_more === 1 || data.has_more === 'true' || data.has_more === '1');
                console.log('Media loaded:', {
                    total: data.total,
                    page: data.page,
                    per_page: data.per_page,
                    has_more_raw: data.has_more,
                    has_more_type: typeof data.has_more,
                    has_more_bool: this.hasMore,
                    itemsCount: this.mediaItems.length,
                    debug: data.debug || {}
                });
                this.renderMedia();
                // 延迟一点确保DOM更新完成
                setTimeout(() => {
                    this.updateLoadMoreButton();
                }, 100);
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
                        <img src="${item.url}" alt="${item.name}" loading="lazy">
                        <div class="media-item-info">
                            <div class="media-item-name">${this.escapeHtml(item.name)}</div>
                            <div class="media-item-meta">${dimensions} • ${size}</div>
                        </div>
                    </div>
                `;
            } else {
                return `
                    <div class="media-item ${isSelected ? 'selected' : ''}" data-id="${item.id}">
                        <input type="checkbox" class="media-item-checkbox" ${isSelected ? 'checked' : ''} 
                               onchange="window.mediaLibrary.toggleSelect('${item.id}')">
                        <img src="${item.url}" alt="${item.name}" loading="lazy">
                        <div class="media-item-name" style="padding: 8px; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            ${this.escapeHtml(item.name)}
                        </div>
                    </div>
                `;
            }
        }).join('');

        grid.innerHTML = itemsHtml;

        // Add click handler for media items
        grid.querySelectorAll('.media-item').forEach(item => {
            item.addEventListener('click', (e) => {
                if (e.target.type !== 'checkbox') {
                    const id = item.dataset.id;
                    this.toggleSelect(id);
                }
            });
        });

        this.updateLoadMoreButton();
    }

    updateLoadMoreButton() {
        console.log('=== updateLoadMoreButton DEBUG ===');
        const loadMoreContainer = document.getElementById('load-more-container');
        if (!loadMoreContainer) {
            console.error('❌ load-more-container not found in DOM!');
            console.log('Searching for element...');
            const allContainers = document.querySelectorAll('[id*="load"]');
            console.log('Found elements with "load" in id:', allContainers);
            return;
        }

        console.log('✅ load-more-container found:', loadMoreContainer);
        console.log('Current container style:', {
            display: loadMoreContainer.style.display,
            visibility: loadMoreContainer.style.visibility,
            innerHTML: loadMoreContainer.innerHTML
        });

        // 只要hasMore为true就显示按钮
        console.log('Button state check:', {
            hasMore: this.hasMore,
            hasMoreType: typeof this.hasMore,
            hasMoreStrict: this.hasMore === true,
            mediaItemsLength: this.mediaItems.length,
            currentPage: this.currentPage,
            shouldShow: this.hasMore === true && this.mediaItems.length > 0
        });
        
        // 只在hasMore为true时显示按钮
        const shouldShow = this.hasMore === true && this.mediaItems.length > 0;
        
        console.log('shouldShow calculation:', {
            hasMore: this.hasMore,
            hasMoreStrict: this.hasMore === true,
            itemsLength: this.mediaItems.length,
            shouldShow: shouldShow
        });
        
        if (shouldShow) {
            console.log('✅ Showing load more button');
            // 强制移除所有可能隐藏的样式
            loadMoreContainer.removeAttribute('style');
            loadMoreContainer.style.display = 'flex';
            loadMoreContainer.style.visibility = 'visible';
            loadMoreContainer.style.opacity = '1';
            loadMoreContainer.classList.remove('d-none');
            
            let loadMoreBtn = document.getElementById('load-more-media');
            if (!loadMoreBtn) {
                console.log('Creating new load more button...');
                loadMoreBtn = document.createElement('button');
                loadMoreBtn.id = 'load-more-media';
                loadMoreBtn.className = 'btn btn-outline-primary';
                loadMoreBtn.textContent = 'Tải thêm ảnh';
                loadMoreBtn.style.display = 'inline-block';
                loadMoreBtn.style.visibility = 'visible';
                loadMoreBtn.style.opacity = '1';
                loadMoreBtn.addEventListener('click', () => {
                    console.log('Load more button clicked, page:', this.currentPage + 1);
                    this.currentPage++;
                    this.loadMedia(this.currentSearch, false);
                });
                loadMoreContainer.innerHTML = '';
                loadMoreContainer.appendChild(loadMoreBtn);
                console.log('✅ Load more button created and added to container');
                console.log('Button element:', loadMoreBtn);
                console.log('Container after append:', loadMoreContainer.innerHTML);
            } else {
                console.log('✅ Load more button already exists, updating...');
                loadMoreBtn.disabled = false;
                loadMoreBtn.textContent = 'Tải thêm ảnh';
                loadMoreBtn.style.display = 'inline-block';
                loadMoreBtn.style.visibility = 'visible';
                loadMoreBtn.style.opacity = '1';
            }
            console.log('Final container style:', {
                display: loadMoreContainer.style.display,
                visibility: loadMoreContainer.style.visibility,
                computedDisplay: window.getComputedStyle(loadMoreContainer).display,
                computedVisibility: window.getComputedStyle(loadMoreContainer).visibility
            });
        } else {
            console.log('❌ Hiding load more button');
            console.log('Reason:', {
                hasMore: this.hasMore,
                hasMoreType: typeof this.hasMore,
                itemsLength: this.mediaItems.length
            });
            loadMoreContainer.style.display = 'none';
            loadMoreContainer.innerHTML = '';
        }
        console.log('=== END updateLoadMoreButton DEBUG ===');
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
        this.renderMedia();
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
        const uploadArea = document.getElementById('upload-area');

        for (const file of files) {
            if (!file.type.startsWith('image/')) {
                alert(`${file.name} không phải là file ảnh`);
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

                const data = await response.json();
                if (data.success) {
                    // Reload media để có thứ tự đúng
                    this.currentPage = 1;
                    this.loadMedia(this.currentSearch, true);
                    this.switchTab('library');
                } else {
                    alert(`Lỗi khi upload ${file.name}: ${data.message || 'Unknown error'}`);
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert(`Lỗi khi upload ${file.name}`);
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
}

// Initialize global instance
if (typeof window !== 'undefined') {
    console.log('=== MediaLibrary Script Loading ===');
    window.MediaLibrary = MediaLibrary;
    window.mediaLibrary = new MediaLibrary({
        apiUrl: '/admin/media/library',
    });
    console.log('✅ MediaLibrary initialized:', window.mediaLibrary);
    console.log('Modal element:', window.mediaLibrary.modal);
    console.log('=== End MediaLibrary Script Loading ===');
}
