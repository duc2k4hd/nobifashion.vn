@php
    $types = [
        \App\Models\Voucher::TYPE_FIXED_AMOUNT => 'Giảm số tiền cố định',
        \App\Models\Voucher::TYPE_PERCENTAGE => 'Giảm theo % đơn hàng',
        \App\Models\Voucher::TYPE_FREE_SHIPPING => 'Miễn phí vận chuyển',
        \App\Models\Voucher::TYPE_SHIPPING_PERCENTAGE => 'Giảm % phí vận chuyển',
        \App\Models\Voucher::TYPE_SHIPPING_FIXED => 'Giảm số tiền phí vận chuyển',
    ];

    $applicableOptions = [
        \App\Models\Voucher::APPLICABLE_ALL => 'Tất cả sản phẩm',
        \App\Models\Voucher::APPLICABLE_PRODUCTS => 'Sản phẩm cụ thể',
        \App\Models\Voucher::APPLICABLE_CATEGORIES => 'Danh mục cụ thể',
    ];

    $statusOptions = [
        \App\Models\Voucher::STATUS_ACTIVE => 'Hoạt động',
        \App\Models\Voucher::STATUS_SCHEDULED => 'Lên lịch',
        \App\Models\Voucher::STATUS_DISABLED => 'Tạm tắt',
    ];
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Mã voucher *</label>
                        <input type="text" name="code" class="form-control" maxlength="50"
                               value="{{ old('code', $voucher->code) }}" required>
                        <small class="text-muted">Chỉ bao gồm chữ in hoa, số, dấu gạch ngang.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tên hiển thị *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $voucher->name) }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Mô tả</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $voucher->description) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Loại voucher *</label>
                        <select name="type" class="form-select" required>
                            @foreach($types as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $voucher->type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Giá trị</label>
                        <input type="number" step="0.01" min="0" name="value" class="form-control"
                               value="{{ old('value', $voucher->value) }}" {{ old('type', $voucher->type) === \App\Models\Voucher::TYPE_FREE_SHIPPING ? 'readonly' : '' }}>
                        <small class="text-muted">Đơn vị: vnđ hoặc % tùy theo loại.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Giới hạn lượt dùng</label>
                        <input type="number" min="1" name="usage_limit" class="form-control"
                               value="{{ old('usage_limit', $voucher->usage_limit) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Giới hạn mỗi khách</label>
                        <input type="number" min="1" name="per_user_limit" class="form-control"
                               value="{{ old('per_user_limit', $voucher->per_user_limit) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Đơn hàng tối thiểu</label>
                        <input type="number" min="0" step="0.01" name="min_order_amount" class="form-control"
                               value="{{ old('min_order_amount', $voucher->min_order_amount) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Giảm tối đa</label>
                        <input type="number" min="0" step="0.01" name="max_discount_amount" class="form-control"
                               value="{{ old('max_discount_amount', $voucher->max_discount_amount) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h5 class="fw-semibold mb-3">Điều kiện áp dụng</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phạm vi áp dụng</label>
                        <select name="applicable_to" id="applicable_to" class="form-select">
                            @foreach($applicableOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('applicable_to', $voucher->applicable_to) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6" id="applicable_ids_wrapper" style="{{ old('applicable_to', $voucher->applicable_to) === \App\Models\Voucher::APPLICABLE_ALL ? 'display:none;' : '' }}">
                        <label class="form-label fw-semibold">Danh sách áp dụng</label>
                        <div class="input-group mb-2">
                            <input type="text" id="applicable_ids_display" class="form-control" readonly 
                                   placeholder="Chưa chọn sản phẩm/danh mục" 
                                   value="{{ old('applicable_to', $voucher->applicable_to) === \App\Models\Voucher::APPLICABLE_PRODUCTS ? 'Sản phẩm' : 'Danh mục' }}">
                            <button type="button" class="btn btn-outline-primary" onclick="openProductPicker()">
                                <i class="bi bi-search me-1"></i> Chọn
                            </button>
                        </div>
                        <div id="selected-items-display" class="mb-2">
                            @php
                                $selectedIds = collect(old('applicable_ids', $voucher->applicable_ids ?? []))->map(fn($id) => (int)$id)->filter()->values();
                            @endphp
                            @foreach($selectedIds as $id)
                                <input type="hidden" name="applicable_ids[]" value="{{ $id }}">
                            @endforeach
                        </div>
                        <small class="text-muted">Click "Chọn" để mở danh sách sản phẩm/danh mục.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ngày bắt đầu</label>
                        <input type="datetime-local" name="start_at" class="form-control"
                               value="{{ old('start_at', optional($voucher->start_at)->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ngày kết thúc</label>
                        <input type="datetime-local" name="end_at" class="form-control"
                               value="{{ old('end_at', optional($voucher->end_at)->format('Y-m-d\TH:i')) }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <h5 class="fw-semibold mb-3">Cài đặt hiển thị</h5>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Ảnh voucher</label>
                    <div class="input-group mb-2">
                        <input type="text" name="image" id="voucher-image-input" class="form-control" value="{{ old('image', $voucher->image) }}"
                               placeholder="Đường dẫn ảnh hoặc CDN">
                        <button type="button" class="btn btn-outline-secondary" onclick="openVoucherImagePicker()">Chọn ảnh</button>
                    </div>
                    <div class="mb-2">
                        <input type="file" name="image_file" id="voucher-image-file" class="form-control form-control-sm" accept="image/*">
                        <small class="text-muted">Hoặc upload ảnh mới (JPG, PNG, GIF, WEBP, tối đa 2MB)</small>
                    </div>
                    <div id="voucher-image-preview" class="mt-2">
                        @if(!empty($voucher->image))
                            <img src="{{ str_starts_with($voucher->image, 'http') ? $voucher->image : asset('clients/assets/img/vouchers/' . $voucher->image) }}" 
                                 class="img-fluid rounded shadow-sm" alt="preview" style="max-height: 200px;">
                        @endif
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Trạng thái *</label>
                    <select name="status" class="form-select">
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $voucher->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="alert alert-info mb-0">
                    <strong>Lưu ý:</strong> Nếu chọn trạng thái "Lên lịch" mà không chọn ngày bắt đầu, hệ thống sẽ tự động đặt ngày bắt đầu +1 ngày kể từ hiện tại.
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Lưu voucher</button>
                <p class="text-muted small mb-0 mt-2">Voucher sẽ được tự động đồng bộ cache & lịch sử thay đổi sau khi lưu.</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        const voucherImages = @json($voucherImages ?? []);
        const productCategories = @json($productCategories ?? []);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let selectedProductIds = @json(collect(old('applicable_ids', $voucher->applicable_ids ?? []))->map(fn($id) => (int)$id)->filter()->values()->toArray());
        let currentApplicableTo = '{{ old('applicable_to', $voucher->applicable_to) }}';

        document.addEventListener('DOMContentLoaded', () => {
            const applicableSelect = document.getElementById('applicable_to');
            const wrapper = document.getElementById('applicable_ids_wrapper');
            const typeSelect = document.querySelector('select[name="type"]');
            const valueInput = document.querySelector('input[name="value"]');
            const imageFileInput = document.getElementById('voucher-image-file');

            if (applicableSelect) {
                applicableSelect.addEventListener('change', () => {
                    currentApplicableTo = applicableSelect.value;
                    if (applicableSelect.value === '{{ \App\Models\Voucher::APPLICABLE_ALL }}') {
                        wrapper.style.display = 'none';
                        selectedProductIds = [];
                        updateSelectedDisplay();
                    } else {
                        wrapper.style.display = 'block';
                    }
                });
            }

            if (typeSelect && valueInput) {
                const toggleValueInput = () => {
                    const isFreeShip = typeSelect.value === '{{ \App\Models\Voucher::TYPE_FREE_SHIPPING }}';
                    valueInput.readOnly = isFreeShip;
                    if (isFreeShip) {
                        valueInput.value = 0;
                    }
                };

                typeSelect.addEventListener('change', toggleValueInput);
                toggleValueInput();
            }

            // Xử lý upload ảnh
            if (imageFileInput) {
                imageFileInput.addEventListener('change', async function(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('image', file);
                    formData.append('_token', csrfToken);

                    try {
                        const response = await fetch('{{ route('admin.vouchers.upload-image') }}', {
                            method: 'POST',
                            body: formData,
                        });

                        const result = await response.json();
                        if (result.success) {
                            document.getElementById('voucher-image-input').value = result.path;
                            document.getElementById('voucher-image-preview').innerHTML = 
                                `<img src="${result.url}" class="img-fluid rounded shadow-sm" alt="preview" style="max-height: 200px;">`;
                            imageFileInput.value = '';
                        } else {
                            alert('Upload ảnh thất bại: ' + (result.message || 'Lỗi không xác định'));
                        }
                    } catch (error) {
                        console.error('Upload error:', error);
                        alert('Có lỗi xảy ra khi upload ảnh.');
                    }
                });
            }
        });

        function openVoucherImagePicker() {
            if (!voucherImages || voucherImages.length === 0) {
                alert('Chưa có ảnh trong thư viện vouchers.');
                return;
            }

            const overlay = document.createElement('div');
            overlay.className = 'media-picker-overlay';
            overlay.style.position = 'fixed';
            overlay.style.inset = '0';
            overlay.style.background = 'rgba(15,23,42,0.55)';
            overlay.style.zIndex = '9999';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';

            const modal = document.createElement('div');
            modal.style.background = '#fff';
            modal.style.borderRadius = '16px';
            modal.style.padding = '20px';
            modal.style.width = '580px';
            modal.style.maxHeight = '80vh';
            modal.style.overflowY = 'auto';
            modal.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Chọn ảnh từ thư viện vouchers</h5>
                    <button type="button" class="btn btn-link text-danger" data-close>&times;</button>
                </div>
                <div class="row g-3 media-picker-grid">
                    ${voucherImages.map(file => `
                        <div class="col-4">
                            <button type="button" class="w-100 border rounded p-0 bg-white" data-url="${file.url}" data-path="${file.path}" data-name="${file.name}">
                                <img src="${file.url}" alt="${file.name}" class="img-fluid" style="height:120px;object-fit:cover;border-top-left-radius:8px;border-top-right-radius:8px;">
                                <div class="p-2 small text-truncate">${file.name}</div>
                            </button>
                        </div>
                    `).join('')}
                </div>
            `;

            const closeModal = () => {
                overlay.removeEventListener('click', handleClick);
                document.body.removeChild(overlay);
            };

            const handleClick = (event) => {
                if (event.target.dataset.close !== undefined || event.target === overlay) {
                    closeModal();
                    return;
                }

                const button = event.target.closest('button[data-url]');
                if (button) {
                    const input = document.getElementById('voucher-image-input');
                    const preview = document.getElementById('voucher-image-preview');
                    if (input) {
                        input.value = button.dataset.path;
                    }
                    if (preview) {
                        preview.innerHTML = `<img src="${button.dataset.url}" class="img-fluid rounded shadow-sm" alt="${button.dataset.name}" style="max-height: 200px;">`;
                    }
                    closeModal();
                }
            };

            overlay.addEventListener('click', handleClick);
            overlay.appendChild(modal);
            document.body.appendChild(overlay);
        }

        function openProductPicker() {
            const overlay = document.createElement('div');
            overlay.className = 'product-picker-overlay';
            overlay.style.position = 'fixed';
            overlay.style.inset = '0';
            overlay.style.background = 'rgba(15,23,42,0.75)';
            overlay.style.zIndex = '9999';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.style.padding = '20px';

            const modal = document.createElement('div');
            modal.style.background = '#fff';
            modal.style.borderRadius = '16px';
            modal.style.padding = '24px';
            modal.style.width = '95%';
            modal.style.height = '95%';
            modal.style.maxWidth = '1400px';
            modal.style.maxHeight = '95vh';
            modal.style.display = 'flex';
            modal.style.flexDirection = 'column';
            modal.style.overflow = 'hidden';

            let currentCategoryId = '';
            let currentSearch = '';
            let currentPage = 1;
            let allProducts = [];
            let loading = false;

            const loadProducts = async (page = 1, append = false) => {
                if (loading) return;
                loading = true;

                const loadingEl = modal.querySelector('.products-loading');
                if (loadingEl) loadingEl.style.display = 'block';

                try {
                    const params = new URLSearchParams({
                        page: page,
                    });
                    if (currentCategoryId) params.append('category_id', currentCategoryId);
                    if (currentSearch) params.append('search', currentSearch);

                    const response = await fetch(`{{ route('admin.vouchers.products') }}?${params}`);
                    const result = await response.json();

                    if (result.success) {
                        if (append) {
                            allProducts = [...allProducts, ...result.data];
                        } else {
                            allProducts = result.data;
                        }

                        renderProducts();
                        renderPagination(result.pagination);

                        currentPage = result.pagination.current_page;
                    }
                } catch (error) {
                    console.error('Error loading products:', error);
                    alert('Có lỗi xảy ra khi tải danh sách sản phẩm.');
                } finally {
                    loading = false;
                    const loadingEl = modal.querySelector('.products-loading');
                    if (loadingEl) loadingEl.style.display = 'none';
                }
            };

            const renderProducts = () => {
                const container = modal.querySelector('.products-grid');
                if (!container) return;

                if (allProducts.length === 0) {
                    container.innerHTML = '<div class="text-center text-muted py-5">Không tìm thấy sản phẩm nào.</div>';
                    return;
                }

                container.innerHTML = allProducts.map(product => {
                    const isSelected = selectedProductIds.includes(product.id);
                    const price = product.sale_price || product.price;
                    const categoryName = product.primary_category?.name || 'Chưa phân loại';
                    
                    return `
                        <div class="product-item card border ${isSelected ? 'border-primary bg-light' : ''}" 
                             data-product-id="${product.id}" 
                             style="cursor: pointer; transition: all 0.2s;">
                            <div class="card-body p-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           id="product-${product.id}" 
                                           ${isSelected ? 'checked' : ''}
                                           onchange="toggleProduct(${product.id}, this.checked)">
                                    <label class="form-check-label w-100" for="product-${product.id}" style="cursor: pointer;">
                                        <div class="fw-semibold text-truncate">${product.name || 'N/A'}</div>
                                        <div class="small text-muted">Mã: ${product.sku || 'N/A'}</div>
                                        <div class="small text-muted">Danh mục: ${categoryName}</div>
                                        <div class="small fw-semibold text-primary mt-1">
                                            ${new Intl.NumberFormat('vi-VN').format(price)}đ
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            };

            const renderPagination = (pagination) => {
                const paginationEl = modal.querySelector('.products-pagination');
                if (!paginationEl) return;

                if (pagination.last_page <= 1) {
                    paginationEl.innerHTML = '';
                    return;
                }

                let html = '<nav><ul class="pagination justify-content-center">';
                
                // Previous
                html += `<li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="event.preventDefault(); loadProducts(${pagination.current_page - 1}); return false;">Trước</a>
                </li>`;

                // Pages
                for (let i = 1; i <= pagination.last_page; i++) {
                    if (i === 1 || i === pagination.last_page || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                        html += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="event.preventDefault(); loadProducts(${i}); return false;">${i}</a>
                        </li>`;
                    } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                        html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }

                // Next
                html += `<li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="event.preventDefault(); loadProducts(${pagination.current_page + 1}); return false;">Sau</a>
                </li>`;

                html += '</ul></nav>';
                paginationEl.innerHTML = html;
            };

            modal.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold">Chọn ${currentApplicableTo === 'specific_products' ? 'sản phẩm' : 'danh mục'}</h5>
                    <button type="button" class="btn btn-link text-danger p-0" data-close style="font-size: 24px; line-height: 1;">&times;</button>
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Lọc theo danh mục</label>
                        <select class="form-select form-select-sm" id="product-category-filter" onchange="filterByCategory(this.value)">
                            <option value="">Tất cả danh mục</option>
                            ${productCategories.map(cat => `
                                <option value="${cat.id}">${cat.name}</option>
                            `).join('')}
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Tìm kiếm theo tên hoặc mã</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="product-search" 
                                   placeholder="Nhập tên sản phẩm hoặc mã SKU..." 
                                   onkeyup="debounceSearch(this.value)">
                            <button class="btn btn-outline-secondary" type="button" onclick="searchProducts()">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex-grow-1 overflow-auto mb-3" style="border: 1px solid #dee2e6; border-radius: 8px; padding: 16px;">
                    <div class="products-loading text-center py-4" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                    </div>
                    <div class="products-grid row g-3"></div>
                </div>

                <div class="products-pagination"></div>

                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <div class="text-muted small">
                        Đã chọn: <strong id="selected-count">${selectedProductIds.length}</strong> ${currentApplicableTo === 'specific_products' ? 'sản phẩm' : 'danh mục'}
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary me-2" data-close>Hủy</button>
                        <button type="button" class="btn btn-primary" onclick="confirmProductSelection()">Xác nhận</button>
                    </div>
                </div>
            `;

            // Global functions for modal
            window.filterByCategory = (categoryId) => {
                currentCategoryId = categoryId;
                currentPage = 1;
                loadProducts(1, false);
            };

            let searchTimeout;
            window.debounceSearch = (value) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentSearch = value;
                    currentPage = 1;
                    loadProducts(1, false);
                }, 500);
            };

            window.searchProducts = () => {
                const searchInput = modal.querySelector('#product-search');
                currentSearch = searchInput.value;
                currentPage = 1;
                loadProducts(1, false);
            };

            window.toggleProduct = (productId, checked) => {
                if (checked) {
                    if (!selectedProductIds.includes(productId)) {
                        selectedProductIds.push(productId);
                    }
                } else {
                    selectedProductIds = selectedProductIds.filter(id => id !== productId);
                }
                updateSelectedCount();
                renderProducts();
            };

            window.confirmProductSelection = () => {
                updateSelectedDisplay();
                closeModal();
            };

            const updateSelectedCount = () => {
                const countEl = modal.querySelector('#selected-count');
                if (countEl) {
                    countEl.textContent = selectedProductIds.length;
                }
            };

            const closeModal = () => {
                overlay.remove();
            };

            const handleClick = (event) => {
                if (event.target.dataset.close !== undefined || event.target === overlay) {
                    closeModal();
                }
            };

            overlay.addEventListener('click', handleClick);
            overlay.appendChild(modal);
            document.body.appendChild(overlay);

            // Load initial products
            loadProducts(1, false);
        }

        function updateSelectedDisplay() {
            const displayEl = document.getElementById('applicable_ids_display');
            const selectedEl = document.getElementById('selected-items-display');
            
            if (!displayEl || !selectedEl) return;

            if (selectedProductIds.length === 0) {
                displayEl.value = 'Chưa chọn sản phẩm/danh mục';
                selectedEl.innerHTML = '';
                return;
            }

            const typeLabel = currentApplicableTo === 'specific_products' ? 'sản phẩm' : 'danh mục';
            displayEl.value = `Đã chọn ${selectedProductIds.length} ${typeLabel}`;
            
            selectedEl.innerHTML = selectedProductIds.map(id => 
                `<input type="hidden" name="applicable_ids[]" value="${id}">`
            ).join('');
        }
    </script>
@endpush

