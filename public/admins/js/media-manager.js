(() => {
    class MediaManager {
        // Max files per upload request (phải ≤ PHP max_file_uploads)
        static MAX_FILES_PER_BATCH = 20;

        constructor(config) {
            this.config = config || {};
            this.routes = this.config.routes || {};
            this.csrfToken = this.config.csrfToken || '';
            this.fallbackImage = this.config.fallbackImage || '';
            this.toastTimer = null;
            this.keywordTimer = null;
            this.assignTargetSelect = null;

            this.state = {
                items: [],
                stats: {},
                meta: {
                    total: 0,
                    per_page: 50,
                    current_page: 1,
                    last_page: 1,
                    from: 0,
                    to: 0,
                },
                filters: {
                    type: 'all',
                    folder: 'all',
                    status: 'all',
                    q: '',
                    sort: 'created_at',
                    direction: 'desc',
                    page: 1,
                    per_page: 50,
                },
                view: 'grid',
                selectedKeys: new Set(),
                activeItemKey: null,
                gridRenderSignature: '',
                isLoading: false,
                isDeleting: false,
                uiLockDepth: 0,
            };

            this.elements = this.cacheElements();
        }

        setKeywordLocked(locked) {
            if (!this.elements.keyword) {
                return;
            }

            this.elements.keyword.disabled = Boolean(locked);
        }

        cacheElements() {
            return {
                refreshBtn: document.getElementById('mediaRefreshBtn'),
                toggleUploadBtn: document.getElementById('mediaToggleUploadBtn'),
                uploadPanel: document.getElementById('mediaUploadPanel'),
                collapseUploadBtn: document.getElementById('mediaCollapseUploadBtn'),
                uploadForm: document.getElementById('mediaUploadForm'),
                uploadFolder: document.getElementById('mediaUploadFolder'),
                dropzone: document.getElementById('mediaDropzone'),
                fileInput: document.getElementById('mediaFileInput'),
                selectedFilesLabel: document.getElementById('mediaSelectedFiles'),
                keyword: document.getElementById('mediaKeyword'),
                filterType: document.getElementById('mediaFilterType'),
                filterFolder: document.getElementById('mediaFilterFolder'),
                filterStatus: document.getElementById('mediaFilterStatus'),
                sort: document.getElementById('mediaSort'),
                perPage: document.getElementById('mediaPerPage'),
                statusButtons: Array.from(document.querySelectorAll('#mediaStatusList [data-status]')),
                folderButtons: Array.from(document.querySelectorAll('.media-folder-filter')),
                gridBtn: document.getElementById('mediaViewGridBtn'),
                listBtn: document.getElementById('mediaViewListBtn'),
                grid: document.getElementById('mediaGrid'),
                emptyState: document.getElementById('mediaEmptyState'),
                bulkBar: document.getElementById('mediaBulkBar'),
                bulkCount: document.getElementById('mediaBulkCount'),
                selectVisibleBtn: document.getElementById('mediaSelectVisibleBtn'),
                clearSelectionBtn: document.getElementById('mediaClearSelectionBtn'),
                bulkDeleteBtn: document.getElementById('mediaBulkDeleteBtn'),
                resultsSummary: document.getElementById('mediaResultsSummary'),
                currentFilterText: document.getElementById('mediaCurrentFilterText'),
                prevBtn: document.getElementById('mediaPrevBtn'),
                nextBtn: document.getElementById('mediaNextBtn'),
                paginationText: document.getElementById('mediaPaginationText'),
                inspector: document.getElementById('mediaInspector'),
                inspectorEmpty: document.getElementById('mediaInspectorEmpty'),
                inspectorContent: document.getElementById('mediaInspectorContent'),
                inspectorImage: document.getElementById('mediaInspectorImage'),
                inspectorTitle: document.getElementById('mediaInspectorTitle'),
                inspectorSubtitle: document.getElementById('mediaInspectorSubtitle'),
                inspectorBadges: document.getElementById('mediaInspectorBadges'),
                inspectorMeta: document.getElementById('mediaInspectorMeta'),
                inspectorForm: document.getElementById('mediaInspectorForm'),
                inspectorSource: document.getElementById('mediaInspectorSource'),
                inspectorId: document.getElementById('mediaInspectorId'),
                inspectorTitleInput: document.getElementById('mediaInspectorTitleInput'),
                inspectorAltInput: document.getElementById('mediaInspectorAltInput'),
                inspectorDescriptionInput: document.getElementById('mediaInspectorDescriptionInput'),
                inspectorPrimaryInput: document.getElementById('mediaInspectorPrimaryInput'),
                saveBtn: document.getElementById('mediaSaveBtn'),
                copyPathBtn: document.getElementById('mediaCopyPathBtn'),
                openOriginalBtn: document.getElementById('mediaOpenOriginalBtn'),
                deleteBtn: document.getElementById('mediaDeleteBtn'),
                assignForm: document.getElementById('mediaAssignForm'),
                assignSource: document.getElementById('mediaAssignSource'),
                assignMediaId: document.getElementById('mediaAssignMediaId'),
                assignTargetType: document.getElementById('mediaAssignTargetType'),
                assignTargetId: document.getElementById('mediaAssignTargetId'),
                assignBtn: document.getElementById('mediaAssignBtn'),
                loadingOverlay: document.getElementById('mediaLoadingOverlay'),
                loadingMessage: document.getElementById('mediaLoadingMessage'),
                loadingProgress: document.getElementById('mediaLoadingProgress'),
                loadingProgressText: document.getElementById('mediaLoadingProgressText'),
                loadingProgressPercent: document.getElementById('mediaLoadingProgressPercent'),
                loadingProgressBar: document.getElementById('mediaLoadingProgressBar'),
                toast: document.getElementById('mediaToast'),
                statsGrid: document.getElementById('mediaStatsGrid'),
            };
        }

        init() {
            if (!this.elements.grid) {
                return;
            }

            this.bootstrapInitialState();
            this.bindEvents();
            this.initAssignTargetSelect();
            this.ensureActiveItem();
            this.render();
        }

        isUiLocked() {
            return this.state.uiLockDepth > 0;
        }

        lockUi(message) {
            this.state.uiLockDepth += 1;
            this.setLoadingMessage(message || 'Vui lòng chờ đến khi thao tác hiện tại hoàn tất.');

            if (this.elements.loadingOverlay) {
                this.elements.loadingOverlay.hidden = false;
            }

            if (this.state.uiLockDepth === 1) {
                this.hideLoadingProgress();
            }

            document.body.classList.add('media-ui-locked');
        }

        unlockUi() {
            this.state.uiLockDepth = Math.max(0, this.state.uiLockDepth - 1);

            if (this.state.uiLockDepth > 0) {
                return;
            }

            if (this.elements.loadingOverlay) {
                this.elements.loadingOverlay.hidden = true;
            }

            document.body.classList.remove('media-ui-locked');
            this.setLoadingMessage('Vui lòng chờ đến khi thao tác hiện tại hoàn tất.');
            this.resetLoadingProgress();
        }

        setLoadingMessage(message) {
            if (!this.elements.loadingMessage) {
                return;
            }

            this.elements.loadingMessage.textContent = message || 'Vui lòng chờ đến khi thao tác hiện tại hoàn tất.';
        }

        showLoadingProgress() {
            if (this.elements.loadingProgress) {
                this.elements.loadingProgress.hidden = false;
            }
        }

        hideLoadingProgress() {
            if (this.elements.loadingProgress) {
                this.elements.loadingProgress.hidden = true;
            }
        }

        resetLoadingProgress() {
            this.hideLoadingProgress();
            this.setLoadingProgress(0, '0 B / 0 B');
        }

        setLoadingProgress(percent, detailText) {
            const normalizedPercent = Number.isFinite(percent)
                ? Math.min(100, Math.max(0, percent))
                : 0;

            if (this.elements.loadingProgressBar) {
                this.elements.loadingProgressBar.style.width = `${normalizedPercent}%`;
            }

            if (this.elements.loadingProgressPercent) {
                this.elements.loadingProgressPercent.textContent = `${normalizedPercent}%`;
            }

            if (this.elements.loadingProgressText) {
                this.elements.loadingProgressText.textContent = detailText || '0 B / 0 B';
            }
        }

        bootstrapInitialState() {
            const initialState = this.config.initialState || {};
            const initialMeta = initialState.meta || {};

            this.state.items = Array.isArray(initialState.items) ? initialState.items : [];
            this.state.stats = initialState.stats || {};
            this.state.meta = {
                ...this.state.meta,
                ...initialMeta,
            };

            const initialPerPage = Number(initialMeta.per_page || this.state.filters.per_page);
            if ([50, 200, 500, 2000].includes(initialPerPage)) {
                this.state.filters.per_page = initialPerPage;
                this.state.meta.per_page = initialPerPage;
            }

            this.state.filters.page = Number(initialMeta.current_page || 1);

            if (this.elements.perPage) {
                this.elements.perPage.value = String(this.state.filters.per_page);
            }
        }

        bindEvents() {
            this.elements.refreshBtn?.addEventListener('click', () => this.fetchItems());
            this.elements.toggleUploadBtn?.addEventListener('click', () => this.toggleUploadPanel(true));
            this.elements.collapseUploadBtn?.addEventListener('click', () => this.toggleUploadPanel(false));
            this.elements.dropzone?.addEventListener('click', () => this.elements.fileInput?.click());
            this.elements.fileInput?.addEventListener('change', () => this.updateSelectedFilesLabel());
            this.elements.uploadForm?.addEventListener('submit', (event) => this.handleUpload(event));

            this.elements.dropzone?.addEventListener('dragover', (event) => {
                event.preventDefault();
                this.elements.dropzone.classList.add('is-dragover');
            });
            this.elements.dropzone?.addEventListener('dragleave', () => {
                this.elements.dropzone.classList.remove('is-dragover');
            });
            this.elements.dropzone?.addEventListener('drop', (event) => {
                event.preventDefault();
                this.elements.dropzone.classList.remove('is-dragover');
                this.assignFiles(event.dataTransfer?.files);
            });

            this.elements.keyword?.addEventListener('input', (event) => {
                window.clearTimeout(this.keywordTimer);
                this.keywordTimer = window.setTimeout(() => {
                    this.handleFilterChange('q', event.target.value.trim());
                }, 1000);
            });

            this.elements.filterType?.addEventListener('change', (event) => this.handleFilterChange('type', event.target.value));
            this.elements.filterFolder?.addEventListener('change', (event) => this.handleFilterChange('folder', event.target.value));
            this.elements.filterStatus?.addEventListener('change', (event) => this.handleFilterChange('status', event.target.value));
            this.elements.sort?.addEventListener('change', (event) => this.handleFilterChange('sort', event.target.value));
            this.elements.perPage?.addEventListener('change', (event) => this.handleFilterChange('per_page', Number(event.target.value)));

            this.elements.statusButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    this.applyStatusShortcut(button.dataset.status || 'all');
                });
            });

            this.elements.folderButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const nextValue = this.resolveToggleFilterValue(this.state.filters.folder, button.dataset.folder || 'all');
                    this.handleFilterChange('folder', nextValue);
                });
            });

            this.elements.gridBtn?.addEventListener('click', () => this.setView('grid'));
            this.elements.listBtn?.addEventListener('click', () => this.setView('list'));
            this.elements.prevBtn?.addEventListener('click', () => this.changePage(-1));
            this.elements.nextBtn?.addEventListener('click', () => this.changePage(1));
            this.elements.grid?.addEventListener('click', (event) => {
                const checkbox = event.target.closest('.media-card-checkbox');
                if (checkbox) {
                    return;
                }

                const card = event.target.closest('.media-card');
                if (!card || !this.elements.grid?.contains(card)) {
                    return;
                }

                this.setActiveItem(card.dataset.key || '');
            });
            this.elements.grid?.addEventListener('change', (event) => {
                const checkbox = event.target.closest('.media-card-checkbox');
                if (!checkbox) {
                    return;
                }

                this.toggleSelection(checkbox.dataset.key || '', Boolean(checkbox.checked));
            });
            this.elements.clearSelectionBtn?.addEventListener('click', () => this.clearSelection());
            this.elements.selectVisibleBtn?.addEventListener('click', () => this.selectVisibleItems());
            this.elements.bulkDeleteBtn?.addEventListener('click', () => this.handleBulkDelete());
            this.elements.inspectorForm?.addEventListener('submit', (event) => this.handleSaveMetadata(event));
            this.elements.copyPathBtn?.addEventListener('click', () => this.copyActivePath());
            this.elements.openOriginalBtn?.addEventListener('click', () => this.openActiveOriginal());
            this.elements.deleteBtn?.addEventListener('click', () => this.handleDeleteCurrent());
            this.elements.assignForm?.addEventListener('submit', (event) => this.handleAssign(event));
            this.elements.assignTargetType?.addEventListener('change', () => this.loadAssignTargets('', { lockUi: true }));
        }

        resolveToggleFilterValue(currentValue, clickedValue) {
            if (!clickedValue || clickedValue === 'all') {
                return 'all';
            }

            return currentValue === clickedValue ? 'all' : clickedValue;
        }

        applyStatusShortcut(clickedStatus) {
            const nextStatus = this.resolveToggleFilterValue(this.state.filters.status, clickedStatus);

            this.state.filters.status = nextStatus;
            this.state.filters.type = 'all';
            this.state.filters.folder = 'all';
            this.state.filters.q = '';
            this.state.filters.page = 1;

            if (this.elements.filterStatus) {
                this.elements.filterStatus.value = nextStatus;
            }
            if (this.elements.filterType) {
                this.elements.filterType.value = 'all';
            }
            if (this.elements.filterFolder) {
                this.elements.filterFolder.value = 'all';
            }
            if (this.elements.keyword) {
                this.elements.keyword.value = '';
            }

            this.fetchItems({
                loadingMessage: nextStatus === 'all'
                    ? 'Đang tải lại toàn bộ thư viện ảnh...'
                    : 'Đang lọc theo trạng thái đã chọn...',
            });
        }

        initAssignTargetSelect() {
            if (!this.elements.assignTargetId) {
                return;
            }

            if (window.TomSelect) {
                this.assignTargetSelect = new window.TomSelect(this.elements.assignTargetId, {
                    valueField: 'id',
                    labelField: 'label',
                    searchField: ['label', 'description'],
                    options: [],
                    create: false,
                    preload: false,
                    loadThrottle: 250,
                    render: {
                        option(data, escape) {
                            const description = data.description ? `<small>${escape(data.description)}</small>` : '';
                            return `<div class="media-target-option"><span>${escape(data.label || '')}</span>${description}</div>`;
                        },
                        item(data, escape) {
                            return `<div>${escape(data.label || '')}</div>`;
                        },
                    },
                    load: async (query, callback) => {
                        try {
                            const results = await this.fetchAssignTargets(query);
                            callback(results);
                        } catch (error) {
                            callback();
                        }
                    },
                });
            }
        }

        async fetchItems(options = {}) {
            if (this.state.isLoading) {
                return;
            }

            const shouldLockUi = options.lockUi !== false;
            const shouldLockKeyword = Boolean(options.lockKeyword);
            if (shouldLockUi) {
                this.lockUi(options.loadingMessage || 'Đang tải thư viện ảnh...');
            }

            if (shouldLockKeyword) {
                this.setKeywordLocked(true);
            }

            this.state.isLoading = true;

            const params = new URLSearchParams({
                type: this.state.filters.type,
                folder: this.state.filters.folder,
                status: this.state.filters.status,
                q: this.state.filters.q,
                sort: this.state.filters.sort,
                direction: this.state.filters.direction,
                page: String(this.state.filters.page),
                per_page: String(this.state.filters.per_page),
            });

            try {
                const response = await fetch(`${this.routes.search}?${params.toString()}`, {
                    headers: {
                        Accept: 'application/json',
                    },
                });
                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'Không thể tải thư viện ảnh.');
                }

                this.state.items = Array.isArray(payload.data) ? payload.data : [];
                this.state.meta = {
                    ...this.state.meta,
                    ...(payload.meta || {}),
                };
                this.state.stats = payload.stats || {};

                if ((this.state.meta.last_page || 1) > 0 && this.state.filters.page > this.state.meta.last_page && options.adjustPage !== false) {
                    this.state.filters.page = this.state.meta.last_page;
                    this.state.isLoading = false;
                    return this.fetchItems({ adjustPage: false });
                }

                this.state.selectedKeys = new Set(
                    this.state.items
                        .filter((item) => this.state.selectedKeys.has(item.key))
                        .map((item) => item.key)
                );

                this.ensureActiveItem();
                this.render();
            } catch (error) {
                this.showToast(error.message || 'Không thể tải thư viện ảnh.', 'error');
            } finally {
                this.state.isLoading = false;
                this.renderPagination();
                if (shouldLockKeyword) {
                    this.setKeywordLocked(false);
                }
                if (shouldLockUi) {
                    this.unlockUi();
                }
            }
        }

        async handleUpload(event) {
            event.preventDefault();

            const files = Array.from(this.elements.fileInput?.files || []);
            if (!files.length) {
                this.showToast('Hãy chọn ít nhất một ảnh để upload.', 'warning');
                return;
            }

            const submitButton = this.elements.uploadForm?.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
            }

            /**
             * BATCH UPLOAD LOGIC
             * ==================
             * Chia files thành batches nhỏ để tránh:
             * - PHP max_file_uploads limit (20 files/request)
             * - POST body size limit
             * - Request timeout
             *
             * Ví dụ: 600 ảnh + batch size=20
             * → 30 batches × 20 files/batch
             * - Batch 1: Upload files 1-20
             * - Batch 2: Upload files 21-40
             * - ... 
             * - Batch 30: Upload files 581-600
             *
             * Per-batch error handling:
             * - Batch fail → skip batch, tiếp tục batch tiếp theo
             * - 1-2 files fail trong batch → vẫn upload những file còn lại
             * - Không bao giờ fail all vì vài file có vấn đề
             */
            const folder = this.elements.uploadFolder?.value || '';
            const totalFileSize = this.getTotalFileSize(files);
            const chunks = this.chunkItems(files, MediaManager.MAX_FILES_PER_BATCH); // 20 files/batch
            let uploadedCount = 0;
            let uploadedBytes = 0;
            let failedCount = 0;
            let failedFiles = [];

            this.lockUi(`Đang upload ${files.length} ảnh vào thư viện...`);
            this.showLoadingProgress();
            this.setLoadingProgress(0, `0 B / ${this.formatBytes(totalFileSize)}`);

            try {
                for (let chunkIndex = 0; chunkIndex < chunks.length; chunkIndex++) {
                    const chunk = chunks[chunkIndex];
                    const startNum = uploadedCount + 1;
                    const endNum = uploadedCount + chunk.length;

                    this.setLoadingMessage(`Đang upload batch ${chunkIndex + 1}/${chunks.length}: ${startNum}-${endNum}/${files.length} ảnh...`);
                    this.showToast(`Upload batch ${chunkIndex + 1}/${chunks.length}: ${startNum}-${endNum}/${files.length} ảnh...`, 'warning', false);

                    try {
                        const chunkSize = this.getTotalFileSize(chunk);
                        const response = await this.uploadFileChunk(chunk, folder);

                        uploadedCount += response.uploaded_count || 0;
                        uploadedBytes += chunkSize;
                        failedCount += response.failed_count || 0;

                        if (response.failed_files && Array.isArray(response.failed_files)) {
                            failedFiles.push(...response.failed_files);
                        }

                        const percent = totalFileSize > 0
                            ? Math.round((uploadedBytes / totalFileSize) * 100)
                            : 0;
                        this.setLoadingProgress(
                            percent,
                            `${this.formatBytes(uploadedBytes)} / ${this.formatBytes(totalFileSize)}`
                        );
                    } catch (chunkError) {
                        failedCount += chunk.length;
                        failedFiles.push(...chunk.map(f => ({
                            name: f.name,
                            error: chunkError.message
                        })));
                        this.showToast(`Batch ${chunkIndex + 1} thất bại: ${chunkError.message}`, 'warning');
                    }
                }

                this.setLoadingMessage('Đã gửi xong dữ liệu, đang cập nhật thư viện ảnh...');

                const messageParts = [];
                if (uploadedCount > 0) {
                    messageParts.push(`Đã upload thành công ${uploadedCount}/${files.length} ảnh.`);
                }
                if (failedCount > 0) {
                    messageParts.push(`${failedCount} ảnh upload thất bại.`);
                    if (failedFiles.length > 0 && failedFiles.length <= 5) {
                        const failedNames = failedFiles.map(f => f.name).join(', ');
                        messageParts.push(`(${failedNames})`);
                    }
                }

                this.showToast(messageParts.join(' '), failedCount > 0 ? 'warning' : 'success');
                this.elements.uploadForm?.reset();
                this.updateSelectedFilesLabel();
                this.toggleUploadPanel(false);
                await this.fetchItems({ lockUi: false });
            } catch (error) {
                this.showToast(error.message || 'Upload ảnh thất bại.', 'error');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                }
                this.unlockUi();
            }
        }

        uploadFileChunk(fileChunk, folder) {
            const formData = new FormData();
            formData.append('_token', this.csrfToken);
            formData.append('folder', folder);

            if (!Array.isArray(fileChunk)) {
                fileChunk = [fileChunk];
            }

            fileChunk.forEach((file) => formData.append('files[]', file));

            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', this.routes.upload, true);
                xhr.responseType = 'json';
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);

                xhr.addEventListener('load', () => {
                    const payload = this.parseJsonResponse(xhr);

                    // Accept 200-299 OR 422 with partial success (some files uploaded)
                    const isSuccessful = xhr.status >= 200 && xhr.status < 300;
                    const hasPartialSuccess = payload?.uploaded_count > 0;

                    if ((isSuccessful || hasPartialSuccess) && payload) {
                        resolve({
                            uploaded_count: payload.uploaded_count || 0,
                            failed_count: payload.failed_count || 0,
                            failed_files: payload.failed_files || [],
                        });
                        return;
                    }

                    reject(new Error(payload?.message || 'Upload batch thất bại.'));
                });

                xhr.addEventListener('error', () => {
                    reject(new Error('Không thể kết nối tới máy chủ trong lúc upload ảnh.'));
                });

                xhr.addEventListener('abort', () => {
                    reject(new Error('Upload ảnh đã bị hủy.'));
                });

                xhr.upload.addEventListener('progress', (event) => {
                    if (event.lengthComputable) {
                        const percent = Math.round((event.loaded / event.total) * 100);
                        // Có thể dùng để track progress per chunk nếu cần
                    }
                });

                xhr.send(formData);
            });
        }

        uploadFilesWithProgress(formData, files) {
            const fileCount = Array.isArray(files) ? files.length : 0;
            const fallbackTotalBytes = this.getTotalFileSize(files);

            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', this.routes.upload, true);
                xhr.responseType = 'json';
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);

                xhr.upload.addEventListener('progress', (event) => {
                    const totalBytes = event.lengthComputable ? event.total : fallbackTotalBytes;
                    const loadedBytes = event.lengthComputable ? event.loaded : 0;
                    const percent = totalBytes > 0
                        ? Math.round((loadedBytes / totalBytes) * 100)
                        : 0;

                    this.setLoadingMessage(`Đang upload ${fileCount} ảnh vào thư viện...`);
                    this.setLoadingProgress(
                        percent,
                        `${this.formatBytes(loadedBytes)} / ${this.formatBytes(totalBytes)}`
                    );
                });

                xhr.upload.addEventListener('load', () => {
                    this.setLoadingMessage('Đã gửi xong dữ liệu, đang xử lý thư viện ảnh...');
                    this.setLoadingProgress(100, `${this.formatBytes(fallbackTotalBytes)} / ${this.formatBytes(fallbackTotalBytes)}`);
                });

                xhr.addEventListener('load', () => {
                    const payload = this.parseJsonResponse(xhr);

                    if (xhr.status >= 200 && xhr.status < 300 && payload?.success) {
                        resolve(payload);
                        return;
                    }

                    reject(new Error(payload?.message || 'Upload ảnh thất bại.'));
                });

                xhr.addEventListener('error', () => {
                    reject(new Error('Không thể kết nối tới máy chủ trong lúc upload ảnh.'));
                });

                xhr.addEventListener('abort', () => {
                    reject(new Error('Upload ảnh đã bị hủy.'));
                });

                xhr.send(formData);
            });
        }

        parseJsonResponse(xhr) {
            if (xhr.response && typeof xhr.response === 'object') {
                return xhr.response;
            }

            if (!xhr.responseText) {
                return {};
            }

            try {
                return JSON.parse(xhr.responseText);
            } catch (error) {
                return {};
            }
        }

        getTotalFileSize(files) {
            return Array.isArray(files)
                ? files.reduce((total, file) => total + Number(file?.size || 0), 0)
                : 0;
        }

        formatBytes(bytes) {
            const normalizedBytes = Number(bytes) || 0;
            const units = ['B', 'KB', 'MB', 'GB', 'TB'];

            if (normalizedBytes <= 0) {
                return '0 B';
            }

            const unitIndex = Math.min(
                Math.floor(Math.log(normalizedBytes) / Math.log(1024)),
                units.length - 1
            );

            const value = normalizedBytes / (1024 ** unitIndex);
            const digits = unitIndex === 0 ? 0 : value >= 100 ? 0 : value >= 10 ? 1 : 2;

            return `${value.toFixed(digits)} ${units[unitIndex]}`;
        }

        handleFilterChange(key, value) {
            this.state.filters[key] = value;
            this.state.filters.page = 1;

            if (key === 'status' && this.elements.filterStatus) {
                this.elements.filterStatus.value = value;
            }

            if (key === 'folder' && this.elements.filterFolder) {
                this.elements.filterFolder.value = value;
            }

            if (key === 'type' && this.elements.filterType) {
                this.elements.filterType.value = value;
            }

            if (key === 'sort' && this.elements.sort) {
                this.elements.sort.value = value;
            }

            if (key === 'per_page' && this.elements.perPage) {
                this.elements.perPage.value = String(value);
            }

            const loadingMessages = {
                q: 'Đang tìm kiếm ảnh...',
                type: 'Đang lọc theo loại media...',
                folder: 'Đang lọc theo thư mục...',
                status: 'Đang lọc theo trạng thái...',
                sort: 'Đang sắp xếp lại thư viện ảnh...',
                per_page: 'Đang cập nhật số lượng ảnh hiển thị...',
            };

            this.fetchItems({
                loadingMessage: loadingMessages[key] || 'Đang cập nhật thư viện ảnh...',
                lockKeyword: key === 'q',
            });
        }

        changePage(delta) {
            const nextPage = this.state.filters.page + delta;
            if (nextPage < 1 || nextPage > (this.state.meta.last_page || 1)) {
                return;
            }

            this.state.filters.page = nextPage;
            this.fetchItems({
                loadingMessage: 'Đang chuyển trang thư viện ảnh...',
            });
        }

        setView(view) {
            this.state.view = view === 'list' ? 'list' : 'grid';
            this.renderGrid();
        }

        toggleUploadPanel(forceOpen) {
            if (!this.elements.uploadPanel) {
                return;
            }

            const shouldOpen = typeof forceOpen === 'boolean'
                ? forceOpen
                : this.elements.uploadPanel.classList.contains('is-collapsed');

            this.elements.uploadPanel.classList.toggle('is-collapsed', !shouldOpen);
        }

        assignFiles(fileList) {
            if (!this.elements.fileInput || !fileList) {
                return;
            }

            const transfer = new DataTransfer();
            Array.from(fileList).forEach((file) => transfer.items.add(file));
            this.elements.fileInput.files = transfer.files;
            this.updateSelectedFilesLabel();
        }

        updateSelectedFilesLabel() {
            if (!this.elements.selectedFilesLabel) {
                return;
            }

            const files = Array.from(this.elements.fileInput?.files || []);
            if (!files.length) {
                this.elements.selectedFilesLabel.textContent = 'Chưa chọn file nào.';
                return;
            }

            this.elements.selectedFilesLabel.textContent = `${files.length} file đã được chọn.`;
        }

        render() {
            this.renderStats();
            this.renderFilterState();
            this.renderBulkBar();
            this.renderResultsMeta();
            this.renderGrid();
            this.renderPagination();
            this.renderInspector();
        }

        renderStats() {
            document.querySelectorAll('[data-stat-key]').forEach((element) => {
                const key = element.dataset.statKey;
                const value = this.state.stats?.[key];
                if (value === undefined || value === null) {
                    return;
                }

                element.textContent = typeof value === 'number' ? this.formatNumber(value) : value;
            });

            document.querySelectorAll('[data-status-count]').forEach((element) => {
                const key = element.dataset.statusCount;
                const value = this.state.stats?.status_counts?.[key] ?? 0;
                element.textContent = this.formatNumber(value);
            });
        }

        renderFilterState() {
            this.elements.statusButtons.forEach((button) => {
                button.classList.toggle('is-active', (button.dataset.status || 'all') === this.state.filters.status);
            });

            this.elements.folderButtons.forEach((button) => {
                button.classList.toggle('is-active', (button.dataset.folder || 'all') === this.state.filters.folder);
            });

            this.elements.gridBtn?.classList.toggle('is-active', this.state.view === 'grid');
            this.elements.listBtn?.classList.toggle('is-active', this.state.view === 'list');
        }

        renderBulkBar() {
            const selectedCount = this.state.selectedKeys.size;
            if (!this.elements.bulkBar || !this.elements.bulkCount) {
                return;
            }

            this.elements.bulkBar.hidden = selectedCount === 0;
            this.elements.bulkCount.textContent = this.formatNumber(selectedCount);

            [this.elements.selectVisibleBtn, this.elements.clearSelectionBtn, this.elements.bulkDeleteBtn].forEach((button) => {
                if (button) {
                    button.disabled = this.state.isDeleting;
                }
            });
        }

        renderResultsMeta() {
            if (this.elements.resultsSummary) {
                const from = this.state.meta.from || 0;
                const to = this.state.meta.to || 0;
                const total = this.state.meta.total || 0;
                this.elements.resultsSummary.textContent = `Hiển thị ${this.formatNumber(from)} - ${this.formatNumber(to)} trên ${this.formatNumber(total)} mục`;
            }

            if (this.elements.currentFilterText) {
                const parts = [];
                if (this.state.filters.type !== 'all') {
                    parts.push(this.elements.filterType?.selectedOptions?.[0]?.textContent || this.state.filters.type);
                }
                if (this.state.filters.folder !== 'all') {
                    parts.push(this.elements.filterFolder?.selectedOptions?.[0]?.textContent || this.state.filters.folder);
                }
                if (this.state.filters.status !== 'all') {
                    parts.push(this.elements.filterStatus?.selectedOptions?.[0]?.textContent || this.state.filters.status);
                }
                if (this.state.filters.q) {
                    parts.push(`Từ khóa: "${this.state.filters.q}"`);
                }

                this.elements.currentFilterText.textContent = `Bộ lọc hiện tại: ${parts.length ? parts.join(' | ') : 'Tất cả'}`;
            }
        }

        buildGridRenderSignature() {
            const itemsSignature = this.state.items.map((item) => [
                item.key || '',
                item.file_name || '',
                item.title || '',
                item.relative_path || '',
                item.preview || '',
                item.original || '',
                item.size_human || '',
                item.type_label || '',
                Array.isArray(item.status_flags) ? item.status_flags.join(',') : '',
            ].join('~')).join('|');

            return `${this.state.view}::${itemsSignature}`;
        }

        findGridCardByKey(key) {
            if (!this.elements.grid || !key) {
                return null;
            }

            return Array.from(this.elements.grid.querySelectorAll('.media-card'))
                .find((card) => card.dataset.key === key) || null;
        }

        findGridCheckboxByKey(key) {
            return this.findGridCardByKey(key)?.querySelector('.media-card-checkbox') || null;
        }

        syncActiveCardState(previousKey = null) {
            if (!this.elements.grid) {
                return;
            }

            const previousCard = previousKey
                ? this.findGridCardByKey(previousKey)
                : this.elements.grid.querySelector('.media-card.is-selected');

            if (previousCard && previousCard.dataset.key !== this.state.activeItemKey) {
                previousCard.classList.remove('is-selected');
            }

            const activeCard = this.findGridCardByKey(this.state.activeItemKey);
            if (activeCard) {
                activeCard.classList.add('is-selected');
            }
        }

        syncVisibleSelectionState() {
            if (!this.elements.grid) {
                return;
            }

            this.elements.grid.querySelectorAll('.media-card-checkbox').forEach((checkbox) => {
                checkbox.checked = this.state.selectedKeys.has(checkbox.dataset.key || '');
            });
        }

        renderGrid() {
            if (!this.elements.grid) {
                return;
            }

            this.elements.grid.classList.toggle('is-grid', this.state.view === 'grid');
            this.elements.grid.classList.toggle('is-list', this.state.view === 'list');

            if (!this.state.items.length) {
                this.elements.grid.innerHTML = '';
                this.state.gridRenderSignature = '';
                if (this.elements.emptyState) {
                    this.elements.emptyState.hidden = false;
                }
                return;
            }

            if (this.elements.emptyState) {
                this.elements.emptyState.hidden = true;
            }

            const nextSignature = this.buildGridRenderSignature();
            if (this.state.gridRenderSignature !== nextSignature) {
                this.elements.grid.innerHTML = this.state.items.map((item) => this.renderCard(item)).join('');
                this.state.gridRenderSignature = nextSignature;
            }

            this.syncVisibleSelectionState();
            this.syncActiveCardState();
        }

        renderCard(item) {
            const isSelected = this.state.selectedKeys.has(item.key);
            const isActive = this.state.activeItemKey === item.key;
            const preview = this.escapeHtml(item.preview || this.fallbackImage || '');
            const title = this.escapeHtml(item.title || item.file_name || 'Ảnh chưa đặt tên');
            const path = this.escapeHtml(item.relative_path || item.original || '');
            const size = this.escapeHtml(item.size_human || 'Không rõ dung lượng');
            const typeLabel = this.escapeHtml(item.type_label || '');
            const badgeHtml = (item.status_flags || [])
                .slice(0, 3)
                .map((status) => `<span class="media-tag ${this.statusClass(status)}">${this.escapeHtml(this.statusLabel(item, status))}</span>`)
                .join('');

            return `
                <article class="media-card ${isActive ? 'is-selected' : ''}" data-key="${this.escapeHtml(item.key)}">
                    <div class="media-card-thumb">
                        <input type="checkbox" class="media-card-checkbox" data-key="${this.escapeHtml(item.key)}" ${isSelected ? 'checked' : ''}>
                        <img src="${preview}" alt="${title}" loading="lazy" onerror="this.onerror=null; this.src='${this.escapeHtml(this.fallbackImage || '')}'">
                    </div>
                    <div class="media-card-body">
                        <h3 class="media-card-title">${title}</h3>
                        <div class="media-card-path">${path}</div>
                        <div class="media-card-meta">${typeLabel} · ${size}</div>
                        <div class="media-card-tags">${badgeHtml}</div>
                    </div>
                </article>
            `;
        }

        renderPagination() {
            if (this.elements.paginationText) {
                this.elements.paginationText.textContent = `Trang ${this.formatNumber(this.state.filters.page)} / ${this.formatNumber(this.state.meta.last_page || 1)}`;
            }

            if (this.elements.prevBtn) {
                this.elements.prevBtn.disabled = this.state.filters.page <= 1 || this.state.isLoading;
            }

            if (this.elements.nextBtn) {
                this.elements.nextBtn.disabled = this.state.filters.page >= (this.state.meta.last_page || 1) || this.state.isLoading;
            }
        }

        isDeleteBlocked(item) {
            return Boolean(item && ['banner_desktop', 'banner_mobile'].includes(item.type));
        }

        getDeleteBlockedMessage(item) {
            if (!this.isDeleteBlocked(item)) {
                return '';
            }

            const label = item.type === 'banner_mobile' ? 'mobile' : 'desktop';
            return `Banner bắt buộc phải có ảnh ${label}. Hãy gán ảnh mới thay vì xóa trắng.`;
        }

        renderInspector() {
            const item = this.getActiveItem();
            const hasItem = Boolean(item);

            if (this.elements.inspectorEmpty) {
                this.elements.inspectorEmpty.hidden = hasItem;
            }

            if (this.elements.inspectorContent) {
                this.elements.inspectorContent.hidden = !hasItem;
            }

            if (!item) {
                return;
            }

            this.elements.inspectorImage.onerror = () => {
                this.elements.inspectorImage.onerror = null;
                this.elements.inspectorImage.src = this.fallbackImage || '';
            };
            this.elements.inspectorImage.src = item.original || item.preview || this.fallbackImage || '';
            this.elements.inspectorImage.alt = item.alt || item.title || item.file_name || '';
            this.elements.inspectorTitle.textContent = item.title || item.file_name || 'Ảnh chưa đặt tên';
            this.elements.inspectorSubtitle.textContent = item.relative_path || item.original || '-';
            this.elements.inspectorBadges.innerHTML = (item.status_flags || [])
                .map((status) => `<span class="media-tag ${this.statusClass(status)}">${this.escapeHtml(this.statusLabel(item, status))}</span>`)
                .join('');

            this.elements.inspectorSource.value = item.delete_source || item.type || '';
            this.elements.inspectorId.value = item.delete_id || item.id || '';
            this.elements.inspectorTitleInput.value = item.title || '';
            this.elements.inspectorAltInput.value = item.alt || '';
            this.elements.inspectorDescriptionInput.value = item.description || '';
            this.elements.inspectorPrimaryInput.checked = Boolean(item.is_primary);

            const canEdit = Boolean(item.can_edit_meta);
            [this.elements.inspectorTitleInput, this.elements.inspectorAltInput, this.elements.inspectorDescriptionInput, this.elements.inspectorPrimaryInput, this.elements.saveBtn]
                .forEach((element) => {
                    if (element) {
                        element.disabled = !canEdit || this.state.isDeleting;
                    }
                });

            if (this.elements.assignForm) {
                this.elements.assignForm.hidden = !item.can_assign;
            }

            if (this.elements.assignSource) {
                this.elements.assignSource.value = item.delete_source || item.type || '';
            }

            if (this.elements.assignMediaId) {
                this.elements.assignMediaId.value = item.delete_source === 'filesystem_file'
                    ? (item.relative_path || '')
                    : (item.delete_id || item.id || '');
            }

            if (this.elements.deleteBtn) {
                const blockedDelete = this.isDeleteBlocked(item);
                this.elements.deleteBtn.disabled = this.state.isDeleting || blockedDelete;
                this.elements.deleteBtn.title = blockedDelete ? this.getDeleteBlockedMessage(item) : '';
            }

            this.elements.inspectorMeta.innerHTML = this.buildMetaRows(item);
        }

        async handleSaveMetadata(event) {
            event.preventDefault();

            const item = this.getActiveItem();
            if (!item || !item.can_edit_meta) {
                return;
            }

            const body = new URLSearchParams();
            body.set('_token', this.csrfToken);
            body.set('source', item.delete_source || item.type || '');
            body.set('title', this.elements.inspectorTitleInput?.value || '');
            body.set('alt', this.elements.inspectorAltInput?.value || '');
            body.set('description', this.elements.inspectorDescriptionInput?.value || '');
            body.set('is_primary', this.elements.inspectorPrimaryInput?.checked ? '1' : '0');

            this.lockUi('Đang lưu metadata ảnh...');

            try {
                const response = await fetch(`${this.routes.updateBase}/${encodeURIComponent(item.delete_id || item.id || '')}`, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: body.toString(),
                });
                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Không thể lưu metadata.');
                }

                this.showToast(payload.message || 'Đã lưu metadata.', 'success');
                await this.fetchItems();
            } catch (error) {
                this.showToast(error.message || 'Không thể lưu metadata.', 'error');
            }
            this.unlockUi();
        }

        async handleAssign(event) {
            event.preventDefault();

            const item = this.getActiveItem();
            const targetType = this.elements.assignTargetType?.value || '';
            const targetId = this.assignTargetSelect?.getValue?.() || this.elements.assignTargetId?.value || '';

            if (!item || !item.can_assign || !targetType || !targetId) {
                this.showToast('Hãy chọn đối tượng cần gán.', 'warning');
                return;
            }

            const body = new URLSearchParams();
            body.set('_token', this.csrfToken);
            body.set('source', item.delete_source || item.type || '');
            body.set('media_id', item.delete_source === 'filesystem_file' ? (item.relative_path || '') : (item.delete_id || item.id || ''));
            body.set('target_type', targetType);
            body.set('target_id', targetId);

            this.lockUi('Đang gán ảnh vào đối tượng...');

            try {
                const response = await fetch(this.routes.assign, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: body.toString(),
                });
                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Không thể gán ảnh.');
                }

                this.showToast(payload.message || 'Đã gán ảnh.', 'success');
                await this.fetchItems();
            } catch (error) {
                this.showToast(error.message || 'Không thể gán ảnh.', 'error');
            }
            this.unlockUi();
        }

        async fetchAssignTargets(query) {
            const targetType = this.elements.assignTargetType?.value || '';
            if (!targetType) {
                return [];
            }

            const params = new URLSearchParams({
                type: targetType,
                q: query || '',
            });

            const response = await fetch(`${this.routes.targets}?${params.toString()}`, {
                headers: {
                    Accept: 'application/json',
                },
            });
            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Không thể tải danh sách đối tượng.');
            }

            return Array.isArray(payload.data) ? payload.data : [];
        }

        async loadAssignTargets(query, options = {}) {
            if (options.lockUi) {
                this.lockUi('Đang tải danh sách đối tượng...');
            }

            try {
                const targetOptions = await this.fetchAssignTargets(query);
                if (this.assignTargetSelect) {
                    this.assignTargetSelect.clear();
                    this.assignTargetSelect.clearOptions();
                    this.assignTargetSelect.addOptions(targetOptions);
                    this.assignTargetSelect.refreshOptions(false);
                } else if (this.elements.assignTargetId) {
                    this.elements.assignTargetId.innerHTML = targetOptions
                        .map((option) => `<option value="${this.escapeHtml(String(option.id))}">${this.escapeHtml(option.label)}</option>`)
                        .join('');
                }
            } catch (error) {
                this.showToast(error.message || 'Không thể tải danh sách đối tượng.', 'error');
            }
            if (options.lockUi) {
                this.unlockUi();
            }
        }

        async handleDeleteCurrent() {
            const item = this.getActiveItem();
            if (!item) {
                return;
            }

            if (this.isDeleteBlocked(item)) {
                this.showToast(this.getDeleteBlockedMessage(item), 'warning');
                return;
            }

            const confirmed = window.confirm(`Xóa ảnh "${item.title || item.file_name}"? Thao tác này không thể hoàn tác.`);
            if (!confirmed) {
                return;
            }

            await this.performDelete([item]);
        }

        async handleBulkDelete() {
            const items = this.state.items.filter((item) => this.state.selectedKeys.has(item.key));
            if (!items.length) {
                this.showToast('Chưa có ảnh nào được chọn.', 'warning');
                return;
            }

            const confirmed = window.confirm(`Xóa ${items.length} ảnh đang chọn? Hệ thống sẽ xóa theo từng lô nhỏ để tránh timeout.`);
            if (!confirmed) {
                return;
            }

            await this.performDelete(items);
        }

        async performDelete(items) {
            if (!items.length || this.state.isDeleting) {
                return;
            }

            const blockedItems = items.filter((item) => this.isDeleteBlocked(item));
            const deletableItems = items.filter((item) => !this.isDeleteBlocked(item));

            if (!deletableItems.length) {
                this.showToast(this.getDeleteBlockedMessage(blockedItems[0]), 'warning');
                return;
            }

            this.lockUi('Đang chuẩn bị xóa ảnh đã chọn...');
            this.state.isDeleting = true;
            this.renderBulkBar();
            this.renderInspector();

            const chunks = this.chunkItems(deletableItems, 50);
            let processed = 0;
            let deletedCount = 0;
            let preservedFilesCount = 0;
            let failedCount = 0;

            try {
                for (const chunk of chunks) {
                    this.setLoadingMessage(`Đang xóa ${processed + 1}-${processed + chunk.length}/${items.length} ảnh...`);
                    this.showToast(`Đang xóa ${processed + 1}-${processed + chunk.length}/${items.length} ảnh...`, 'warning', false);

                    const response = await fetch(this.routes.bulkDelete, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: JSON.stringify({
                            items: chunk.map((item) => this.buildDeletePayload(item)),
                        }),
                    });
                    const payload = await response.json();

                    if (!response.ok && !payload.deleted_count) {
                        throw new Error(payload.message || 'Không thể xóa ảnh.');
                    }

                    deletedCount += Number(payload.deleted_count || 0);
                    preservedFilesCount += Number(payload.preserved_files_count || 0);
                    failedCount += Number(payload.failed_count || 0);
                    processed += chunk.length;
                }

                this.state.selectedKeys.clear();

                const messageParts = [`Đã xử lý ${deletedCount} ảnh.`];
                if (preservedFilesCount > 0) {
                    messageParts.push(`${preservedFilesCount} file vật lý được giữ lại vì đang dùng chung.`);
                }
                if (failedCount > 0) {
                    messageParts.push(`${failedCount} ảnh không xóa được.`);
                }

                if (blockedItems.length > 0) {
                    messageParts.push(`${blockedItems.length} ảnh banner bị bỏ qua vì không được xóa trắng.`);
                }
                this.showToast(messageParts.join(' '), failedCount > 0 ? 'warning' : 'success');
            } catch (error) {
                this.showToast(error.message || 'Không thể xóa ảnh.', 'error');
            } finally {
                this.state.isDeleting = false;
                await this.fetchItems({
                    lockUi: false,
                });
                this.unlockUi();
            }
        }

        selectVisibleItems() {
            this.state.items.forEach((item) => this.state.selectedKeys.add(item.key));
            this.renderBulkBar();
            this.syncVisibleSelectionState();
        }

        clearSelection() {
            this.state.selectedKeys.clear();
            this.renderBulkBar();
            this.syncVisibleSelectionState();
        }

        toggleSelection(key, checked) {
            if (checked) {
                this.state.selectedKeys.add(key);
            } else {
                this.state.selectedKeys.delete(key);
            }

            const checkbox = this.findGridCheckboxByKey(key);
            if (checkbox) {
                checkbox.checked = checked;
            }

            this.renderBulkBar();
        }

        setActiveItem(key) {
            if (!key || this.state.activeItemKey === key) {
                return;
            }

            const previousKey = this.state.activeItemKey;
            this.state.activeItemKey = key;
            this.syncActiveCardState(previousKey);
            this.renderInspector();
        }

        ensureActiveItem() {
            const stillExists = this.state.items.find((item) => item.key === this.state.activeItemKey);
            if (stillExists) {
                return;
            }

            this.state.activeItemKey = this.state.items[0]?.key || null;
        }

        getActiveItem() {
            return this.state.items.find((item) => item.key === this.state.activeItemKey) || null;
        }

        copyActivePath() {
            const item = this.getActiveItem();
            const text = item?.relative_path || item?.original;
            if (!text) {
                this.showToast('Không có đường dẫn để sao chép.', 'warning');
                return;
            }

            if (navigator.clipboard?.writeText) {
                navigator.clipboard.writeText(text)
                    .then(() => this.showToast('Đã chép đường dẫn.', 'success'))
                    .catch(() => this.showToast('Không thể chép đường dẫn.', 'error'));
                return;
            }

            const input = document.createElement('textarea');
            input.value = text;
            input.setAttribute('readonly', 'readonly');
            input.style.position = 'absolute';
            input.style.left = '-9999px';
            document.body.appendChild(input);
            input.select();

            try {
                document.execCommand('copy');
                this.showToast('Đã chép đường dẫn.', 'success');
            } catch (error) {
                this.showToast('Không thể chép đường dẫn.', 'error');
            } finally {
                document.body.removeChild(input);
            }
        }

        openActiveOriginal() {
            const item = this.getActiveItem();
            if (!item?.original) {
                return;
            }

            window.open(item.original, '_blank', 'noopener,noreferrer');
        }

        buildDeletePayload(item) {
            return {
                source: item.delete_source,
                id: item.delete_source === 'filesystem_file' ? null : String(item.delete_id || item.id || ''),
                path: item.delete_source === 'filesystem_file' ? (item.relative_path || '') : null,
            };
        }

        buildMetaRows(item) {
            const rows = [
                ['Loại', item.type_label || '-'],
                ['Thư mục', item.folder_label || '-'],
                ['Trạng thái', (item.status_labels || []).join(', ') || '-'],
                ['Dung lượng', item.size_human || '-'],
                ['Kích thước', this.formatDimensions(item.dimensions)],
                ['Mime', item.mime_type || '-'],
                ['Gắn cho', item.entity_label || 'Chưa gắn'],
                ['Số nơi dùng', String(item.usage_count || 0)],
                ['Tạo lúc', item.created_at || '-'],
                ['Cập nhật', item.updated_at || '-'],
            ];

            return rows.map(([label, value]) => {
                const content = label === 'Gắn cho' && item.entity_edit_url && item.entity_label
                    ? `<a href="${this.escapeHtml(item.entity_edit_url)}">${this.escapeHtml(item.entity_label)}</a>`
                    : this.escapeHtml(value || '-');

                return `<div class="media-meta-row"><strong>${this.escapeHtml(label)}</strong><span>${content}</span></div>`;
            }).join('');
        }

        statusClass(status) {
            if (status === 'missing_file') {
                return 'is-danger';
            }
            if (status === 'orphan_file' || status === 'unassigned_record') {
                return 'is-warning';
            }
            if (status === 'in_use' || status === 'shared_file') {
                return 'is-success';
            }

            return 'is-primary';
        }

        statusLabel(item, status) {
            const statusLabels = Array.isArray(item.status_labels) ? item.status_labels : [];
            const statusFlags = Array.isArray(item.status_flags) ? item.status_flags : [];
            const index = statusFlags.indexOf(status);
            return index >= 0 ? (statusLabels[index] || status) : status;
        }

        chunkItems(items, size) {
            const chunks = [];
            for (let index = 0; index < items.length; index += size) {
                chunks.push(items.slice(index, index + size));
            }

            return chunks;
        }

        showToast(message, type = 'success', autoHide = true) {
            if (!this.elements.toast) {
                return;
            }

            this.elements.toast.hidden = false;
            this.elements.toast.textContent = message;
            this.elements.toast.className = `media-toast is-${type}`;

            window.clearTimeout(this.toastTimer);
            if (!autoHide) {
                return;
            }

            this.toastTimer = window.setTimeout(() => {
                this.elements.toast.hidden = true;
            }, 3200);
        }

        formatDimensions(dimensions) {
            if (!dimensions?.width || !dimensions?.height) {
                return '-';
            }

            return `${dimensions.width} × ${dimensions.height}`;
        }

        formatNumber(value) {
            return new Intl.NumberFormat('vi-VN').format(Number(value || 0));
        }

        escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    }

    window.MediaManager = MediaManager;

    document.addEventListener('DOMContentLoaded', () => {
        const manager = new MediaManager(window.mediaManagerConfig || {});
        manager.init();

        if (manager.elements.grid) {
            window.mediaManagerInstance = manager;
        }
    });
})();
