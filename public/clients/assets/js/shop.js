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

    // 3. Khởi tạo thanh trượt danh mục con: Click-Drag chuột và nút điều hướng mượt mà
    initSubcategoriesSlider();

    // 4. Đồng bộ trạng thái active cho Quick Filter Pills
    syncShopQuickFilterPills();

    // 5. Khởi tạo Chế độ xem Lưới (Grid View 3 cột / 4 cột)
    initShopGridView();

    // 6. Khởi tạo Danh sách Yêu thích (Wishlist)
    initShopWishlist();

    // 7. Khởi tạo Khối Sản phẩm vừa xem (Recently Viewed)
    initRecentlyViewed();

    // 8. Bắt sự kiện click phân trang không reload trang (AJAX Pagination)
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

    // 9. Lắng nghe sự kiện Back/Forward của trình duyệt
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

    syncShopQuickFilterPills();
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

    syncShopQuickFilterPills();
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

    syncShopQuickFilterPills();
    applyShopFilters();
}

/**
 * Sắp xếp sản phẩm
 */
function setSortFilter(sortValue) {
    document.getElementById('shop-filter-sort').value = sortValue || 'default';
    const sortSelect = document.getElementById('shop-sort-select');
    if (sortSelect) sortSelect.value = sortValue || 'default';
    document.getElementById('shop-filter-page').value = 1;
    syncShopQuickFilterPills();
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
 * Áp dụng bộ lọc nhanh 1 chạm (Quick Filter Pills)
 */
function applyQuickFilter(type) {
    if (type === 'all') {
        resetAllShopFilters();
        return;
    }

    // Reset màu và size để áp dụng tiêu chí nhanh
    document.getElementById('shop-filter-colorRange').value = '';
    document.getElementById('shop-filter-sizeRange').value = '';
    document.getElementById('shop-filter-page').value = 1;

    // Bỏ active trên các label sidebar
    document.querySelectorAll('.shop-filter-color-pill')
        .forEach(el => el.classList.remove('nobifashion_shop_products_filter_color_form_label_active'));
    document.querySelectorAll('.shop-filter-size-pill')
        .forEach(el => el.classList.remove('nobifashion_shop_products_filter_size_form_label_active'));
    document.querySelectorAll('.nobifashion_shop_products_filter_price_content_form_label')
        .forEach(el => el.classList.remove('nobifashion_shop_products_filter_price_content_form_label_active'));

    const sortSelect = document.getElementById('shop-sort-select');

    if (type === 'newest') {
        document.getElementById('minPriceRange').value = '';
        document.getElementById('maxPriceRange').value = '';
        document.getElementById('shop-filter-sort').value = 'newest';
        if (sortSelect) sortSelect.value = 'newest';
    } else if (type === 'price-asc') {
        document.getElementById('minPriceRange').value = '';
        document.getElementById('maxPriceRange').value = '';
        document.getElementById('shop-filter-sort').value = 'price-asc';
        if (sortSelect) sortSelect.value = 'price-asc';
    } else if (type === 'price-0-250') {
        document.getElementById('minPriceRange').value = '0';
        document.getElementById('maxPriceRange').value = '250000';
        document.getElementById('shop-filter-sort').value = 'default';
        if (sortSelect) sortSelect.value = 'default';
    } else if (type === 'price-250-500') {
        document.getElementById('minPriceRange').value = '250000';
        document.getElementById('maxPriceRange').value = '500000';
        document.getElementById('shop-filter-sort').value = 'default';
        if (sortSelect) sortSelect.value = 'default';
    } else if (type === 'price-500-plus') {
        document.getElementById('minPriceRange').value = '500000';
        document.getElementById('maxPriceRange').value = '';
        document.getElementById('shop-filter-sort').value = 'default';
        if (sortSelect) sortSelect.value = 'default';
    }

    syncShopQuickFilterPills();
    applyShopFilters();
}

/**
 * Đồng bộ chính xác tab active trên Quick Filter Pills
 */
function syncShopQuickFilterPills() {
    const sort = document.getElementById('shop-filter-sort')?.value || 'default';
    const min = document.getElementById('minPriceRange')?.value ?? '';
    const max = document.getElementById('maxPriceRange')?.value ?? '';
    const color = document.getElementById('shop-filter-colorRange')?.value ?? '';
    const size = document.getElementById('shop-filter-sizeRange')?.value ?? '';

    let activeType = null;
    if ((sort === 'default' || !sort) && (min === '' || min === null) && (max === '' || max === null) && !color && !size) {
        activeType = 'all';
    } else if (sort === 'newest') {
        activeType = 'newest';
    } else if (sort === 'price-asc') {
        activeType = 'price-asc';
    } else if (String(min) === '0' && String(max) === '250000') {
        activeType = 'price-0-250';
    } else if (String(min) === '250000' && String(max) === '500000') {
        activeType = 'price-250-500';
    } else if (String(min) === '500000' && (max === '' || max === null || Number(max) >= 100000000)) {
        activeType = 'price-500-plus';
    }

    document.querySelectorAll('.shop_quick_pill').forEach(btn => {
        const btnType = btn.getAttribute('data-quick');
        btn.classList.toggle('active', btnType === activeType);
    });
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

    syncShopQuickFilterPills();
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

            // Đồng bộ lại Chế độ Grid, Trạng thái Wishlist và Quick Filter Pills cho DOM mới
            initShopGridView();
            initShopWishlist();
            syncShopQuickFilterPills();

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

// 5. Khởi tạo tính năng Xem thêm / Thu gọn cho Khối Nội dung SEO danh mục
function initShopSeoToggle() {
    const wrapper = document.getElementById('shopSeoContentWrapper');
    const btn = document.getElementById('btnToggleShopSeo');
    const contentBody = document.getElementById('shopSeoContentBody');
    if (!wrapper || !btn || !contentBody) return;

    // Nếu nội dung thực tế ngắn hơn hoặc bằng 290px thì ẩn nút Xem thêm và ẩn dải Gradient
    if (contentBody.scrollHeight <= 290) {
        if (btn.parentElement) btn.parentElement.style.display = 'none';
        const fade = document.getElementById('shopSeoFade');
        if (fade) fade.style.display = 'none';
        wrapper.classList.add('is-expanded');
        return;
    }

    btn.addEventListener('click', function () {
        const isExpanded = wrapper.classList.contains('is-expanded');
        const btnText = btn.querySelector('.btn-text');
        const btnIcon = btn.querySelector('.btn-icon');

        if (isExpanded) {
            wrapper.classList.remove('is-expanded');
            btn.classList.remove('is-active');
            if (btnText) btnText.textContent = 'Xem thêm nội dung';
            // Cuộn nhẹ về đầu khối SEO để người dùng dễ theo dõi
            const section = document.getElementById('category-seo-section');
            if (section) {
                const offset = section.getBoundingClientRect().top + window.pageYOffset - 90;
                window.scrollTo({ top: offset, behavior: 'smooth' });
            }
        } else {
            wrapper.classList.add('is-expanded');
            btn.classList.add('is-active');
            if (btnText) btnText.textContent = 'Thu gọn nội dung';
        }
    });
}

// Tự động khởi chạy khi tải trang
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initShopSeoToggle);
} else {
    initShopSeoToggle();
}

/**
 * 6. CHẾ ĐỘ XEM LƯỚI (GRID SWITCHER: 4 CỘT HOẶC 3 CỘT)
 */
function changeShopGrid(cols, clickedBtn) {
    const grid = document.getElementById('shopProductGrid');
    if (!grid) return;

    if (cols === 3) {
        grid.classList.add('grid-3-cols');
        try { localStorage.setItem('nobifashion_shop_grid', '3'); } catch (e) {}
    } else {
        grid.classList.remove('grid-3-cols');
        try { localStorage.setItem('nobifashion_shop_grid', '4'); } catch (e) {}
    }

    document.querySelectorAll('.shop_view_btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-grid') === String(cols));
    });
}

function initShopGridView() {
    let savedGrid = '4';
    try {
        savedGrid = localStorage.getItem('nobifashion_shop_grid') || '4';
    } catch (e) {}

    const grid = document.getElementById('shopProductGrid');
    if (!grid) return;

    if (savedGrid === '3') {
        grid.classList.add('grid-3-cols');
        document.querySelectorAll('.shop_view_btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-grid') === '3');
        });
    } else {
        grid.classList.remove('grid-3-cols');
        document.querySelectorAll('.shop_view_btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-grid') === '4');
        });
    }
}

/**
 * 7. DANH SÁCH YÊU THÍCH (WISHLIST TỐC ĐỘ 0MS QUA LOCALSTORAGE)
 */
function getStoredWishlist() {
    try {
        const stored = localStorage.getItem('nobifashion_shop_wishlist');
        return stored ? JSON.parse(stored) : [];
    } catch (e) {
        return [];
    }
}

function setStoredWishlist(list) {
    try {
        localStorage.setItem('nobifashion_shop_wishlist', JSON.stringify(list));
    } catch (e) {}
}

function toggleShopWishlist(productId, clickedBtn) {
    if (!productId) return;
    productId = Number(productId);
    let list = getStoredWishlist();
    const index = list.indexOf(productId);
    const isAdding = index === -1;

    if (isAdding) {
        list.push(productId);
    } else {
        list.splice(index, 1);
    }
    setStoredWishlist(list);

    // Cập nhật trạng thái hiển thị cho toàn bộ button có cùng productId trên trang
    document.querySelectorAll(`.product_card_wishlist_btn[data-product-id="${productId}"]`).forEach(btn => {
        btn.classList.toggle('active', isAdding);
    });

    if (clickedBtn && !clickedBtn.classList.contains('active') && isAdding) {
        clickedBtn.classList.add('active');
    }
}

function initShopWishlist() {
    const list = getStoredWishlist();
    if (!list || !list.length) return;

    const wishlistSet = new Set(list.map(Number));
    document.querySelectorAll('.product_card_wishlist_btn').forEach(btn => {
        const pId = Number(btn.getAttribute('data-product-id'));
        if (wishlistSet.has(pId)) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

/**
 * 8. SẢN PHẨM VỪA XEM (RECENTLY VIEWED - LƯU VÀ HIỂN THỊ TỰ ĐỘNG)
 */
function getStoredRecentlyViewed() {
    try {
        const raw = localStorage.getItem('nobifashion_recently_viewed');
        return raw ? JSON.parse(raw) : [];
    } catch (e) {
        return [];
    }
}

function setStoredRecentlyViewed(list) {
    try {
        localStorage.setItem('nobifashion_recently_viewed', JSON.stringify(list));
    } catch (e) {}
}

function recordProductClick(item) {
    if (!item || !item.id) return;
    let list = getStoredRecentlyViewed();
    // Lọc bỏ sản phẩm đã có trùng ID
    list = list.filter(p => Number(p.id) !== Number(item.id));
    // Đẩy sản phẩm vừa xem lên đầu danh sách
    list.unshift(item);
    // Giới hạn lưu tối đa 8 sản phẩm gần nhất
    if (list.length > 8) {
        list = list.slice(0, 8);
    }
    setStoredRecentlyViewed(list);
}

function renderRecentlyViewed() {
    const box = document.getElementById('shopRecentlyViewedBox');
    const grid = document.getElementById('shopRecentlyViewedGrid');
    if (!box || !grid) return;

    const list = getStoredRecentlyViewed();
    if (!list || list.length === 0) {
        box.style.display = 'none';
        return;
    }

    let html = '';
    list.forEach(p => {
        const priceFormatted = Number(p.price || 0).toLocaleString('vi-VN') + 'đ';
        const originalPriceHtml = (p.original_price && Number(p.original_price) > Number(p.price))
            ? `<span class="product_price_original">${Number(p.original_price).toLocaleString('vi-VN')}đ</span>`
            : '';

        html += `
            <div class="shop_recently_item">
                <a href="${p.url}" class="shop_recently_img_link" title="${p.name}">
                    <img src="${p.image}" alt="${p.name}" loading="lazy" onerror="this.onerror=null;this.src='/clients/assets/img/no-image.webp';">
                </a>
                <div class="shop_recently_info">
                    <h4 class="shop_recently_name">
                        <a href="${p.url}" title="${p.name}">${p.name}</a>
                    </h4>
                    <div class="product_card_price_row">
                        <span class="product_price_sale">${priceFormatted}</span>
                        ${originalPriceHtml}
                    </div>
                </div>
            </div>
        `;
    });

    grid.innerHTML = html;
    box.style.display = 'block';
}

function clearRecentlyViewed() {
    try {
        localStorage.removeItem('nobifashion_recently_viewed');
    } catch (e) {}
    const box = document.getElementById('shopRecentlyViewedBox');
    if (box) box.style.display = 'none';
}

function initRecentlyViewed() {
    // 1. Render lịch sử đã lưu
    renderRecentlyViewed();

    // 2. Bắt sự kiện người dùng nhấp vào xem bất kỳ sản phẩm nào trên danh sách
    document.addEventListener('click', function (e) {
        const link = e.target.closest('.nobifashion_shop_products_content_list_item a');
        if (!link) return;

        const card = link.closest('.nobifashion_shop_products_content_list_item');
        if (!card) return;

        const id = card.getAttribute('data-id');
        const titleEl = card.querySelector('.product_card_title a');
        const imgEl = card.querySelector('.product_img_primary');
        const salePriceEl = card.querySelector('.product_price_sale');
        const originalPriceEl = card.querySelector('.product_price_original');

        if (!id || !titleEl || !imgEl) return;

        const cleanNumber = (str) => str ? parseInt(str.replace(/[^\d]/g, ''), 10) || 0 : 0;

        const item = {
            id: id,
            name: titleEl.textContent.trim(),
            url: link.href,
            image: imgEl.src,
            price: salePriceEl ? cleanNumber(salePriceEl.textContent) : 0,
            original_price: originalPriceEl ? cleanNumber(originalPriceEl.textContent) : 0
        };

        recordProductClick(item);
    });
}

/**
 * 9. THANH TRƯỢT DANH MỤC CON (SUBCATEGORIES SLIDER: DRAG-TO-SCROLL & NAVIGATION BUTTONS)
 */
function initSubcategoriesSlider() {
    const slider = document.getElementById('shopSubcategoriesSlider');
    const prevBtn = document.getElementById('btnSubcatPrev');
    const nextBtn = document.getElementById('btnSubcatNext');
    const fadeLeft = document.getElementById('subcatFadeLeft');
    const fadeRight = document.getElementById('subcatFadeRight');

    if (!slider) return;

    // Cập nhật trạng thái hiển thị của nút bấm và hiệu ứng mờ 2 bên
    function updateControls() {
        const maxScroll = slider.scrollWidth - slider.clientWidth;
        if (maxScroll <= 4) {
            if (prevBtn) prevBtn.classList.remove('is-visible');
            if (nextBtn) nextBtn.classList.remove('is-visible');
            if (fadeLeft) fadeLeft.classList.remove('is-visible');
            if (fadeRight) fadeRight.classList.remove('is-visible');
            return;
        }

        const current = slider.scrollLeft;
        if (prevBtn) prevBtn.classList.toggle('is-visible', current > 10);
        if (fadeLeft) fadeLeft.classList.toggle('is-visible', current > 10);

        if (nextBtn) nextBtn.classList.toggle('is-visible', current < maxScroll - 10);
        if (fadeRight) fadeRight.classList.toggle('is-visible', current < maxScroll - 10);
    }

    // Nút bấm cuộn sang trái / sang phải
    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            slider.scrollBy({ left: -260, behavior: 'smooth' });
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            slider.scrollBy({ left: 260, behavior: 'smooth' });
        });
    }

    // Kéo trượt bằng chuột trên Desktop (Mouse Drag-to-Scroll)
    let isDown = false;
    let startX = 0;
    let scrollStart = 0;
    let hasDragged = false;

    slider.addEventListener('mousedown', function (e) {
        // Chỉ nhận chuột trái
        if (e.button !== 0) return;
        isDown = true;
        hasDragged = false;
        slider.classList.add('is-dragging');
        startX = e.pageX - slider.offsetLeft;
        scrollStart = slider.scrollLeft;
    });

    window.addEventListener('mouseup', function () {
        if (!isDown) return;
        isDown = false;
        slider.classList.remove('is-dragging');
        setTimeout(() => { hasDragged = false; }, 50);
    });

    slider.addEventListener('mousemove', function (e) {
        if (!isDown) return;
        const x = e.pageX - slider.offsetLeft;
        const walk = (x - startX);
        if (Math.abs(walk) > 4) {
            hasDragged = true;
        }
        slider.scrollLeft = scrollStart - walk;
    });

    // Chặn click vào link nếu vừa kéo chuột
    slider.addEventListener('click', function (e) {
        if (hasDragged) {
            e.preventDefault();
            e.stopPropagation();
        }
    }, true);

    // Hỗ trợ cuộn ngang bằng con lăn chuột (Wheel)
    slider.addEventListener('wheel', function (e) {
        if (e.deltaY !== 0 && Math.abs(e.deltaX) < Math.abs(e.deltaY)) {
            slider.scrollLeft += e.deltaY * 0.8;
            e.preventDefault();
        }
    }, { passive: false });

    slider.addEventListener('scroll', updateControls, { passive: true });
    window.addEventListener('resize', updateControls);

    // Kích hoạt ngay lúc tải xong
    updateControls();
    setTimeout(updateControls, 300);
}


