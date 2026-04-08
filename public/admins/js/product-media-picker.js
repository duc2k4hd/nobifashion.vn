(() => {
    class ProductMediaPicker {
        constructor(config) {
            this.config = config || {};
            this.modal = document.getElementById('productMediaPickerModal');
            this.closeBtn = document.getElementById('productMediaPickerCloseBtn');
            this.chooseBtn = document.getElementById('productMediaPickerChooseBtn');
            this.titleEl = document.getElementById('productMediaPickerTitle');
            this.manager = null;
            this.activeRow = null;
            this.hasFetched = false;
        }

        init() {
            if (!this.modal) {
                return;
            }

            this.manager = window.mediaManagerInstance || null;
            if (!this.manager && window.MediaManager) {
                this.manager = new window.MediaManager(window.mediaManagerConfig || {});
                this.manager.init();
                window.mediaManagerInstance = this.manager;
            }

            if (!this.manager) {
                return;
            }

            this.bindEvents();
            this.enhanceExistingRows();
            this.applyDefaultUploadFolder();
            this.updateChooseButtonState();
        }

        bindEvents() {
            document.addEventListener('click', (event) => {
                const openButton = event.target.closest('[data-product-media-open]');
                if (openButton) {
                    const row = openButton.closest('.repeater-item');
                    if (row) {
                        this.open(row);
                    }
                    return;
                }

                const clearButton = event.target.closest('[data-product-media-clear]');
                if (clearButton) {
                    const row = clearButton.closest('.repeater-item');
                    if (row) {
                        this.clearRow(row);
                    }
                    return;
                }

                if (event.target.closest('[data-product-media-close]') || event.target === this.closeBtn) {
                    this.close();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && this.modal && !this.modal.hidden) {
                    this.close();
                }
            });

            this.chooseBtn?.addEventListener('click', () => this.chooseActiveItem());

            this.manager.elements.grid?.addEventListener('click', () => {
                window.requestAnimationFrame(() => this.updateChooseButtonState());
            });

            this.manager.elements.grid?.addEventListener('dblclick', (event) => {
                if (!event.target.closest('.media-card')) {
                    return;
                }

                window.requestAnimationFrame(() => {
                    this.updateChooseButtonState();
                    this.chooseActiveItem();
                });
            });

            document.addEventListener('change', (event) => {
                if (!event.target.matches('.image-file-input')) {
                    return;
                }

                const row = event.target.closest('.repeater-item');
                if (!row) {
                    return;
                }

                const hiddenInput = row.querySelector('input[name$="[existing_path]"]');
                if (hiddenInput) {
                    hiddenInput.value = '';
                }

                this.syncRowState(row);
            });
        }

        enhanceExistingRows() {
            document.querySelectorAll('#image-list .repeater-item').forEach((row) => this.enhanceRow(row));
        }

        enhanceRow(row) {
            if (!row || row.dataset.productMediaEnhanced === '1') {
                return;
            }

            row.dataset.productMediaEnhanced = '1';

            const legacyLibrary = row.querySelector('.image-library');
            if (legacyLibrary) {
                legacyLibrary.remove();
            }

            if (!row.querySelector('[data-product-media-open]')) {
                const actions = document.createElement('div');
                actions.className = 'product-image-picker-actions';
                actions.innerHTML = `
                    <button type="button" class="btn btn-outline-primary btn-sm" data-product-media-open>Mở media picker</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-product-media-clear>Bỏ ảnh đã chọn</button>
                `;

                const preview = row.querySelector('.image-preview');
                preview?.insertAdjacentElement('afterend', actions);
            }

            if (!row.querySelector('[data-product-media-status]')) {
                const status = document.createElement('div');
                status.className = 'product-image-picker-status is-empty';
                status.setAttribute('data-product-media-status', '');
                status.textContent = 'Chưa chọn ảnh từ media. Bạn có thể upload file mới hoặc mở media picker.';

                const actions = row.querySelector('.product-image-picker-actions');
                actions?.insertAdjacentElement('afterend', status);
            }

            this.syncRowState(row);
        }

        syncRowState(row) {
            if (!row) {
                return;
            }

            const hiddenInput = row.querySelector('input[name$="[existing_path]"]');
            const fileInput = row.querySelector('.image-file-input');
            const status = row.querySelector('[data-product-media-status]');
            const clearButton = row.querySelector('[data-product-media-clear]');

            const selectedPath = hiddenInput?.value?.trim() || '';
            const uploadedFileName = fileInput?.files?.[0]?.name || '';

            if (status) {
                if (selectedPath) {
                    status.classList.remove('is-empty');
                    status.textContent = `Đang chọn: ${selectedPath}`;
                } else if (uploadedFileName) {
                    status.classList.remove('is-empty');
                    status.textContent = `Đang dùng file upload cục bộ: ${uploadedFileName}`;
                } else {
                    status.classList.add('is-empty');
                    status.textContent = 'Chưa chọn ảnh từ media. Bạn có thể upload file mới hoặc mở media picker.';
                }
            }

            if (clearButton) {
                clearButton.disabled = !selectedPath && !uploadedFileName;
            }
        }

        async open(row) {
            this.activeRow = row;
            this.enhanceRow(row);
            this.modal.hidden = false;
            document.body.classList.add('product-media-picker-open');

            const titleBase = this.config.modalTitleBase || 'Chọn ảnh cho gallery sản phẩm';
            const rowLabel = row.querySelector('.repeater-header strong')?.textContent?.trim();
            if (this.titleEl) {
                this.titleEl.textContent = rowLabel ? `${titleBase} - ${rowLabel}` : titleBase;
            }

            if (!this.hasFetched || !Array.isArray(this.manager.state.items) || this.manager.state.items.length === 0) {
                this.applyDefaultFolderFilter();
                await this.manager.fetchItems({
                    loadingMessage: 'Đang tải media picker cho sản phẩm...',
                });
                this.hasFetched = true;
            }

            this.syncSelectionFromRow();
            this.updateChooseButtonState();
        }

        close() {
            if (!this.modal) {
                return;
            }

            this.modal.hidden = true;
            document.body.classList.remove('product-media-picker-open');
            this.activeRow = null;
            this.updateChooseButtonState();
        }

        async chooseActiveItem() {
            if (!this.activeRow || !this.manager) {
                return;
            }

            const item = this.manager.getActiveItem();
            if (!item) {
                this.notify('Hãy chọn một ảnh trước khi gán.', 'warning');
                return;
            }

            if (!item.relative_path) {
                this.notify('Ảnh này không có đường dẫn file nội bộ nên không thể dùng cho gallery sản phẩm.', 'warning');
                return;
            }

            const hiddenInput = this.activeRow.querySelector('input[name$="[existing_path]"]');
            const preview = this.activeRow.querySelector('.image-preview');
            const fileInput = this.activeRow.querySelector('.image-file-input');
            const titleInput = this.activeRow.querySelector('input[name$="[title]"]');
            const altInput = this.activeRow.querySelector('input[name$="[alt]"]');

            if (hiddenInput) {
                hiddenInput.value = item.relative_path || '';
            }

            if (fileInput) {
                fileInput.value = '';
            }

            if (preview) {
                const previewUrl = this.escapeHtml(item.original || item.preview || '');
                const previewAlt = this.escapeHtml(item.alt || item.title || item.file_name || 'Ảnh đã chọn');
                preview.innerHTML = previewUrl ? `<img src="${previewUrl}" alt="${previewAlt}">` : '';
            }

            if (titleInput && !titleInput.value.trim()) {
                titleInput.value = item.title || item.file_name || '';
            }

            if (altInput && !altInput.value.trim()) {
                altInput.value = item.alt || item.title || item.file_name || '';
            }

            this.syncRowState(this.activeRow);
            this.close();
        }

        clearRow(row) {
            const hiddenInput = row.querySelector('input[name$="[existing_path]"]');
            const fileInput = row.querySelector('.image-file-input');
            const preview = row.querySelector('.image-preview');

            if (hiddenInput) {
                hiddenInput.value = '';
            }

            if (fileInput) {
                fileInput.value = '';
            }

            if (preview) {
                preview.innerHTML = '';
            }

            this.syncRowState(row);
        }

        syncSelectionFromRow() {
            if (!this.activeRow || !this.manager) {
                return;
            }

            const hiddenInput = this.activeRow.querySelector('input[name$="[existing_path]"]');
            const selectedPath = hiddenInput?.value?.trim();
            if (!selectedPath) {
                this.clearActiveSelection();
                return;
            }

            const selectedFileName = selectedPath.split('/').pop();
            const matchedItem = this.manager.state.items.find((item) => {
                const itemPath = (item.relative_path || '').trim();
                return itemPath === selectedPath || item.file_name === selectedFileName;
            });

            if (!matchedItem) {
                this.clearActiveSelection();
                return;
            }

            this.manager.setActiveItem(matchedItem.key);
        }

        clearActiveSelection() {
            if (!this.manager) {
                return;
            }

            const previousKey = this.manager.state.activeItemKey;
            this.manager.state.activeItemKey = null;
            this.manager.syncActiveCardState?.(previousKey);
            this.manager.renderInspector?.();
        }

        applyDefaultFolderFilter() {
            const defaultFolder = this.config.defaultFolder || 'clothes';
            this.manager.state.filters.folder = defaultFolder;
            this.manager.state.filters.page = 1;

            if (this.manager.elements.filterFolder) {
                this.manager.elements.filterFolder.value = defaultFolder;
            }
        }

        applyDefaultUploadFolder() {
            const defaultFolder = this.config.defaultFolder || 'clothes';
            if (this.manager?.elements?.uploadFolder) {
                this.manager.elements.uploadFolder.value = defaultFolder;
            }
        }

        updateChooseButtonState() {
            if (!this.chooseBtn || !this.manager) {
                return;
            }

            const activeItem = this.manager.getActiveItem();
            this.chooseBtn.disabled = !(this.activeRow && activeItem && activeItem.relative_path);
        }

        notify(message, type = 'success') {
            if (this.manager?.showToast) {
                this.manager.showToast(message, type);
                return;
            }

            window.alert(message);
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

    document.addEventListener('DOMContentLoaded', () => {
        const picker = new ProductMediaPicker(window.productMediaPickerConfig || {});
        picker.init();
        window.productMediaPicker = picker;
    });
})();
