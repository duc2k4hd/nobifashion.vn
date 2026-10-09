/**
 * Nobi Fashion - Shop Instant AJAX Filter Engine
 * Tối ưu hóa siêu tốc: 0ms Client-side Color Search, Sub-100ms AJAX Filter, History PushState
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Toggle bộ lọc danh mục trên Mobile
    const filterContainer = document.querySelector('.nobifashion_shop_products_filter');
    const filterTitle = document.querySelector('.nobifashion_shop_products_filter_categories_title');
    if (filterTitle && filterContainer) {
        filterTitle.addEventListener('click', function () {
            filterContainer.classList.toggle('nobifashion_shop_products_filter_height_full');
        });
    }

    // 2. Tìm kiếm nhanh màu sắc phía Client-side (0ms, không tốn tài nguyên server)
    const colorSearchInput = document.getElementById('shop-filter-color-search');
    if (colorSearchInput) {
        colorSearchInput.addEventListener('input', function () {
            const keyword = this.value.trim().toLowerCase();
            const colorItems = document.querySelectorAll('.nobifashion_shop_products_filter_color_form .shop-color-item');

            colorItems.forEach(function (item) {
                const colorName = item.getAttribute('data-name') || item.textContent.trim().toLowerCase();
                if (!keyword || colorName.includes(keyword)) {
                    item.style.display = 'inline-flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // 3. Bắt sự kiện click phân trang không reload trang (AJAX Pagination)
    const productsContainer = document.getElementById('shop-products-container');
    if (productsContainer) {
        productsContainer.addEventListener('click', function (e) {
            const link = e.target.closest('a');
            if (!link) return;

            // Kiểm tra nếu link nằm trong khối phân trang
            const paginationBox = link.closest('.nobifashion_shop_products_content_products_paginate') ||
                                  link.closest('.pagination') ||
                                  link.closest('nav');
            if (paginationBox && link.href) {
                e.preventDefault();
                // Cuộn mượt lên đầu danh sách sản phẩm
                const offsetTop = productsContainer.getBoundingClientRect().top + window.pageYOffset - 90;
                window.scrollTo({ top: offsetTop, behavior: 'smooth' });
                // Tải trang mới bằng AJAX ngay lập tức
                applyShopFilters(link.href, true, true);
            }
        });
    }

    // 4. Lắng nghe sự kiện Back/Forward của trình duyệt
    window.addEventListener('popstate', function (event) {
        if (event.state && event.state.shopUrl) {
            applyShopFilters(event.state.shopUrl, false, true);
        } else {
            applyShopFilters(window.location.href, false, true);
        }
    });
});

/**
 * Lọc theo khoảng giá
 */
function setPriceFilter(min, max, clickedLabel = null) {
    document.getElementById('minPriceRange').value = (min !== '' && min !== null) ? min : '';
    document.getElementById('maxPriceRange').value = (max !== '' && max !== null) ? max : '';
    document.getElementById('shop-filter-page').value = 1;

    // Cập nhật class active tức thì trên UI
    const priceLabels = document.querySelectorAll('.nobifashion_shop_products_filter_price_content_form_label');
    priceLabels.forEach(el => el.classList.remove('nobifashion_shop_products_filter_price_content_form_label_active'));
    if (clickedLabel) {
        clickedLabel.classList.add('nobifashion_shop_products_filter_price_content_form_label_active');
    }

    applyShopFilters();
}

/**
 * Lọc theo màu sắc
 */
function setColorFilter(color, clickedLabel = null) {
    document.getElementById('shop-filter-colorRange').value = color || '';
    document.getElementById('shop-filter-page').value = 1;

    // Cập nhật class active tức thì trên UI
    const colorPills = document.querySelectorAll('.shop-filter-color-pill');
    colorPills.forEach(el => el.classList.remove('nobifashion_shop_products_filter_color_form_label_active'));
    if (clickedLabel) {
        clickedLabel.classList.add('nobifashion_shop_products_filter_color_form_label_active');
    }

    applyShopFilters();
}

/**
 * Lọc theo kích cỡ (Size)
 */
function setSizeFilter(size, clickedLabel = null) {
    document.getElementById('shop-filter-sizeRange').value = size || '';
    document.getElementById('shop-filter-page').value = 1;

    // Cập nhật class active tức thì trên UI
    const sizePills = document.querySelectorAll('.shop-filter-size-pill');
    sizePills.forEach(el => el.classList.remove('nobifashion_shop_products_filter_size_form_label_active'));
    if (clickedLabel) {
        clickedLabel.classList.add('nobifashion_shop_products_filter_size_form_label_active');
    }

    applyShopFilters();
}

/**
 * Sắp xếp sản phẩm
 */
function setSortFilter(sortValue) {
    document.getElementById('shop-filter-sort').value = sortValue || 'default';
    document.getElementById('shop-filter-page').value = 1;
    applyShopFilters();
}

/**
 * Số sản phẩm mỗi trang
 */
function setPerPageFilter(perPageValue) {
    document.getElementById('shop-filter-perPage').value = perPageValue || 30;
    document.getElementById('shop-filter-page').value = 1;
    applyShopFilters();
}

/**
 * Xóa toàn bộ bộ lọc
 */
function resetAllShopFilters() {
    document.getElementById('minPriceRange').value = '';
    document.getElementById('maxPriceRange').value = '';
    document.getElementById('shop-filter-colorRange').value = '';
    document.getElementById('shop-filter-sizeRange').value = '';
    document.getElementById('shop-filter-sort').value = 'default';
    document.getElementById('shop-filter-page').value = 1;

    // Reset UI active
    document.querySelectorAll('.nobifashion_shop_products_filter_price_content_form_label')
        .forEach(el => el.classList.remove('nobifashion_shop_products_filter_price_content_form_label_active'));
    document.querySelectorAll('.shop-filter-color-pill')
        .forEach(el => el.classList.remove('nobifashion_shop_products_filter_color_form_label_active'));
    document.querySelectorAll('.shop-filter-size-pill')
        .forEach(el => el.classList.remove('nobifashion_shop_products_filter_size_form_label_active'));

    // Kích hoạt nhãn mặc định "Tất cả"
    const defaultPriceLabel = document.querySelector('.nobifashion_shop_products_filter_price_content_form_label:last-child');
    if (defaultPriceLabel) defaultPriceLabel.classList.add('nobifashion_shop_products_filter_price_content_form_label_active');

    const defaultColorLabel = document.querySelector('.shop-filter-color-pill');
    if (defaultColorLabel) defaultColorLabel.classList.add('nobifashion_shop_products_filter_color_form_label_active');

    const defaultSizeLabel = document.querySelector('.shop-filter-size-pill');
    if (defaultSizeLabel) defaultSizeLabel.classList.add('nobifashion_shop_products_filter_size_form_label_active');

    const sortSelect = document.getElementById('shop-sort-select');
    if (sortSelect) sortSelect.value = 'default';

    applyShopFilters();
}

// Biến lưu controller và timer debounce để chống spam click dồn dập
let currentShopAbortController = null;
let currentShopDebounceTimer = null;

/**
 * Điều phối gọi bộ lọc: Tích hợp Debounce chống spam click liên tục
 */
function applyShopFilters(customUrl = null, pushHistory = true, immediate = false) {
    if (currentShopDebounceTimer) {
        clearTimeout(currentShopDebounceTimer);
        currentShopDebounceTimer = null;
    }

    const container = document.getElementById('shop-products-container');
    if (container) {
        container.classList.add('nobifashion_shop_loading');
    }

    const delay = immediate ? 0 : 160;
    currentShopDebounceTimer = setTimeout(function () {
        executeShopFilters(customUrl, pushHistory);
    }, delay);
}

/**
 * Hàm cốt lõi: Gửi AJAX request và cập nhật danh sách sản phẩm tức thì
 */
function executeShopFilters(customUrl = null, pushHistory = true) {
    const form = document.getElementById('nobifashion_shop_filter_form');
    if (!form) return;

    let targetUrl;
    if (customUrl) {
        targetUrl = customUrl;
    } else {
        const baseUrl = form.getAttribute('action') || window.location.pathname;
        const formData = new FormData(form);
        const searchParams = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            if (value !== '' && value !== null && key !== '_colorRadio' && key !== '_sizeRadio') {
                searchParams.append(key, value);
            }
        }
        targetUrl = baseUrl + (searchParams.toString() ? ('?' + searchParams.toString()) : '');
    }

    // Cập nhật trạng thái hiển thị nút "Xóa tất cả bộ lọc"
    const clearBox = document.getElementById('shop-clear-filters-box');
    if (clearBox) {
        const hasFilters = (
            document.getElementById('minPriceRange').value !== '' ||
            document.getElementById('maxPriceRange').value !== '' ||
            document.getElementById('shop-filter-colorRange').value !== '' ||
            document.getElementById('shop-filter-sizeRange').value !== '' ||
            document.getElementById('shop-filter-sort').value !== 'default'
        );
        if (hasFilters) {
            clearBox.classList.remove('nobifashion_shop_hide');
        } else {
            clearBox.classList.add('nobifashion_shop_hide');
        }
    }

    const container = document.getElementById('shop-products-container');
    if (!container) return;

    // Hủy request trước đó nếu còn đang bay trên mạng
    if (currentShopAbortController) {
        currentShopAbortController.abort();
    }
    currentShopAbortController = new AbortController();

    fetch(targetUrl, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json, text/javascript, */*; q=0.01'
        },
        signal: currentShopAbortController.signal
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data && data.success && typeof data.html !== 'undefined') {
            // Cập nhật DOM danh sách sản phẩm siêu tốc
            container.innerHTML = data.html;

            // Cập nhật số lượng tổng
            const totalCountEl = document.getElementById('shop-total-count');
            if (totalCountEl && typeof data.total !== 'undefined') {
                totalCountEl.textContent = Number(data.total).toLocaleString('vi-VN');
            }

            // Đồng bộ lịch sử trình duyệt (pushState)
            if (pushHistory) {
                window.history.pushState({ shopUrl: targetUrl }, '', targetUrl);
            }
        }
    })
    .catch(err => {
        if (err.name === 'AbortError') {
            // Request bị hủy do người dùng bấm thao tác mới -> bỏ qua
            return;
        }
        console.error('Lỗi khi tải bộ lọc sản phẩm:', err);
        // Fallback: nếu có lỗi bất ngờ, nạp lại theo cách chuẩn
        window.location.href = targetUrl;
    })
    .finally(() => {
        container.classList.remove('nobifashion_shop_loading');
    });
}

