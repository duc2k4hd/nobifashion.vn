// ================== CONFIG ==================
const SELECTORS = {
    province: ".nobifashion_main_checkout_flex_province",
    district: ".nobifashion_main_checkout_flex_district",
    ward: ".nobifashion_main_checkout_flex_ward",
    form_checkout: ".nobifashion_main_checkout_left",
};

// ================== LOADING OVERLAY ==================
let loadingTimeout = null;

function toggleFormOverlay(show = true) {
    try {
        const overlay = document.querySelector('.nobifashion_main_loading_form_overlay');
        if (!overlay) {
            return;
        }
        
        // Clear any existing timeout
        if (loadingTimeout) {
            clearTimeout(loadingTimeout);
            loadingTimeout = null;
        }
        
        if (show) {
            overlay.removeAttribute('hidden');
            
            // Auto-hide loading after 10 seconds as safety measure
            loadingTimeout = setTimeout(() => {
                toggleFormOverlay(false);
            }, 10000);
        } else {
            overlay.setAttribute('hidden', '');
        }
    } catch (error) {
        console.error('Error in toggleFormOverlay:', error);
    }
}

// Force hide loading overlay (emergency function)
function forceHideLoading() {
    try {
        const overlay = document.querySelector('.nobifashion_main_loading_form_overlay');
        if (overlay) {
            overlay.setAttribute('hidden', '');
            console.log('Loading overlay force hidden');
        }
        if (loadingTimeout) {
            clearTimeout(loadingTimeout);
            loadingTimeout = null;
        }
    } catch (error) {
        console.error('Error in forceHideLoading:', error);
    }
}

// Make forceHideLoading available globally for debugging
window.forceHideLoading = forceHideLoading;

// ================== STATE ===================
const dataMain = {};
let isSubmitting = false;
let appliedVoucher = null;

// SlimSelect instances
const SS = { province: null, district: null, ward: null };

// ================== HELPERS =================
const domCache = {};
const getDOMElement = (selector) => {
    if (!domCache[selector])
        domCache[selector] = document.querySelector(selector);
    return domCache[selector];
};

const toArray = (x) => (Array.isArray(x) ? x : x ? [x] : []);
const hasSelection = (v) =>
    !(v === null || v === undefined || v === "" || v === "null");

async function apiRequest(
    url,
    method = "GET",
    body = null,
    headers = {},
    timeout = 8000
) {
    const defaultHeaders = {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        ...headers,
    };
    const controller = new AbortController();
    const id = setTimeout(() => controller.abort(), timeout);

    try {
        const res = await fetch(url, {
            method,
            headers: defaultHeaders,
            body: body ? JSON.stringify(body) : null,
            signal: controller.signal,
        });
        clearTimeout(id);
        if (!res.ok)
            return {
                ok: false,
                status: res.status,
                message: `HTTP error ${res.status}`,
            };
        const data = await res.json().catch(() => ({}));
        return { ok: true, status: res.status, data };
    } catch (e) {
        clearTimeout(id);
        console.error('API request error:', e);
        return {
            ok: false,
            message: e.name === "AbortError" ? "Request timeout!" : e.message,
        };
    }
}

function collectCartItems() {
    const rows = document.querySelectorAll(
        ".nobifashion_main_checkout_table tbody tr"
    );

    // Kiểm tra loại checkout
    const isSingleItem = document.querySelector('input[name="product_id"]') !== null;

    const productIdEl = document.querySelector('input[name="product_id"]');
    const productId = parseInt(productIdEl?.value.trim() || '0', 10);

    const cartIdEl = document.querySelector('input[name="cart_id"]');
    const cartId = parseInt(cartIdEl?.value.trim() || '0', 10);

    const uuid = document.querySelector('input[name="uuid"]');

    const items = [];

    rows.forEach((row) => {
        const nameEl = row.querySelector(
            ".nobifashion_main_checkout_table_product_name"
        );
        const attrEl = row.querySelector(
            ".nobifashion_main_checkout_table_product_attrs"
        );
        const qtyEl = row.querySelector(
            ".nobifashion_main_checkout_table_product_qty"
        );
        const totalEl = row.querySelector(
            ".nobifashion_main_checkout_table_total"
        );

        

        // Lấy dữ liệu text
        const name = nameEl?.textContent?.trim() || "Sản phẩm không tên";
        const attrs = attrEl?.textContent?.trim() || "";
        const qtyText = qtyEl?.textContent?.trim() || "";
        const totalText = totalEl?.textContent?.trim() || "";
        

        // Parse giá và số lượng
        const matchPrice = qtyText.match(/([\d.,]+)đ?/);
        const matchQty = qtyText.match(/x\s*(\d+)/i);

        const price = matchPrice
            ? parseInt(matchPrice[1].replace(/[.,đ\s]/g, ""))
            : 0;
        const quantity = matchQty ? parseInt(matchQty[1]) : 1;
        const total_price =
            parseInt(totalText.replace(/[.,đ\s]/g, "")) || price * quantity;

        // Lấy product_id từ data attribute hoặc từ row
        const productId = row.getAttribute('data-product-id') || 
                         row.querySelector('[data-product-id]')?.getAttribute('data-product-id') ||
                         null;
        
        // Lấy variant_id nếu có
        const variantId = row.getAttribute('data-variant-id') || 
                         row.querySelector('[data-variant-id]')?.getAttribute('data-variant-id') ||
                         null;
        
        // Lấy category_id từ product (nếu có)
        const categoryId = row.getAttribute('data-category-id') || 
                          row.querySelector('[data-category-id]')?.getAttribute('data-category-id') ||
                          null;

        items.push({
            name,
            price,
            quantity,
            total_price,
            attributes: attrs,
            product_id: productId ? parseInt(productId, 10) : null,
            product_variant_id: variantId ? parseInt(variantId, 10) : null,
            category_id: categoryId ? parseInt(categoryId, 10) : null,
        });
    });

    // Lấy tạm tính, phí ship, tổng cộng
    const subtotalText = document.querySelector(
        ".nobifashion_main_checkout_table_foot_value:not(#checkout_shipping_fee):not(#checkout_total)"
    )?.textContent;
    const subtotal = subtotalText
        ? parseInt(subtotalText.replace(/[.,đ\s]/g, ""))
        : 0;

    const shippingText = document.getElementById(
        "checkout_shipping_fee"
    )?.textContent;
    const shipping_fee = shippingText
        ? parseInt(shippingText.replace(/[.,đ\s]/g, ""))
        : 0;

    const totalText = document.getElementById("checkout_total")?.textContent;
    const total = totalText
        ? parseInt(totalText.replace(/[.,đ\s]/g, ""))
        : subtotal + shipping_fee;

    // Gán vào dataMain
    if (isSingleItem) {
    dataMain.productId = productId;
        dataMain.uuid = uuid?.value.trim() || null;
    } else {
    dataMain.cartId = cartId;
    }
    dataMain.items = items;
    dataMain.subtotal = subtotal;
    dataMain.shipping_fee = shipping_fee;
    dataMain.total = total;
    return dataMain;
}

// ================== SLIM SELECT ==================
function ensureSlimSelect(selector, placeholder, disabled = false) {
    const el = getDOMElement(selector);
    if (!el) return null;
    
    // Check if SlimSelect is available
    if (typeof SlimSelect === 'undefined') {
        console.error('SlimSelect is not loaded');
        return null;
    }
    
    if (!el._slim) {
        el._slim = new SlimSelect({
            select: el,
            placeholder,
            allowDeselect: true,
            hideSelectedOption: true,
        });
    }
    if (disabled) el._slim.disable();
    else el._slim.enable();
    return el._slim;
}

function resetSelect(ss, placeholderText) {
    if (!ss) return;
    ss.setData([{ text: placeholderText, value: "" }]);
    ss.setSelected([]);
}

// ================== API GHN ==================
async function getProvince() {
    try {
    const url = "/api/v1/ghn/province";
        
        // Check if SlimSelect is available
        const useFallback = typeof SlimSelect === 'undefined';
        
        if (useFallback) {
            // Fallback: populate native select
            const provinceSelect = document.querySelector(SELECTORS.province);
            const districtSelect = document.querySelector(SELECTORS.district);
            const wardSelect = document.querySelector(SELECTORS.ward);
            
            if (provinceSelect) {
                provinceSelect.innerHTML = '<option value="">Chọn Tỉnh/Thành Phố</option>';
                provinceSelect.disabled = false;
            }
            if (districtSelect) {
                districtSelect.innerHTML = '<option value="">Chọn Quận/Huyện</option>';
                districtSelect.disabled = true;
            }
            if (wardSelect) {
                wardSelect.innerHTML = '<option value="">Chọn Xã/Phường</option>';
                wardSelect.disabled = true;
            }
            
            // Vẫn gọi API để lấy dữ liệu thật
            const res = await apiRequest(url, "GET");
            
            if (res.ok && res.data?.data && Array.isArray(res.data.data)) {
                res.data.data.forEach((p) => {
                    const option = document.createElement('option');
                    option.value = String(p.provinceId || p.ProvinceID || p.province_id);
                    option.textContent = p.provinceName || p.ProvinceName;
                    provinceSelect.appendChild(option);
                });
            } else {
                // Nếu API fail, dùng default options
                const defaultOptions = [
                    { text: "Hải Phòng", value: "225" },
                    { text: "Hà Nội", value: "201" },
                    { text: "TP. Hồ Chí Minh", value: "202" },
                ];
                defaultOptions.forEach(option => {
                    const optionEl = document.createElement('option');
                    optionEl.value = option.value;
                    optionEl.textContent = option.text;
                    provinceSelect.appendChild(optionEl);
                });
            }
            return;
        }
        
        // Initialize SlimSelect instances first
    SS.province = ensureSlimSelect(
        SELECTORS.province,
        "Chọn Tỉnh/Thành Phố",
        false
    );
    SS.district = ensureSlimSelect(SELECTORS.district, "Chọn Quận/Huyện", true);
    SS.ward = ensureSlimSelect(SELECTORS.ward, "Chọn Xã/Phường", true);

        if (!SS.province) {
            return;
        }

    resetSelect(SS.province, "Chọn Tỉnh/Thành Phố");
    resetSelect(SS.district, "Chọn Quận/Huyện");
    resetSelect(SS.ward, "Chọn Xã/Phường");

        const res = await apiRequest(url, "GET");

    if (!res.ok) {
            // Set default options even if API fails
            SS.province.setData([
                { text: "Chọn Tỉnh/Thành Phố", value: "" },
                { text: "Hải Phòng", value: "225" },
                { text: "Hà Nội", value: "201" },
                { text: "TP. Hồ Chí Minh", value: "202" },
            ]);
        return;
    }

    // Kiểm tra cấu trúc response
    // Server trả về: { code: 200, message: "...", data: [...] }
    // apiRequest trả về: { ok: true, data: { code: 200, message: "...", data: [...] } }
    const responseData = res.data;
    let list = [];
    
    if (responseData && responseData.data && Array.isArray(responseData.data)) {
        // Cấu trúc: { code: 200, data: [...] }
        list = responseData.data;
    } else if (Array.isArray(responseData)) {
        // Nếu responseData trực tiếp là array
        list = responseData;
    }
    
    if (!list || list.length === 0) {
        SS.province.setData([
            { text: "Chọn Tỉnh/Thành Phố", value: "" },
            { text: "Hải Phòng", value: "225" },
            { text: "Hà Nội", value: "201" },
            { text: "TP. Hồ Chí Minh", value: "202" },
        ]);
        return;
    }
    
    const options = list.map((p) => ({
        text: p.provinceName || p.ProvinceName || p.name || '',
        value: String(p.provinceId || p.ProvinceID || p.province_id || p.id || ''),
    })).filter(opt => opt.text && opt.value); // Lọc bỏ các option không hợp lệ
    
    if (options.length === 0) {
        SS.province.setData([
            { text: "Chọn Tỉnh/Thành Phố", value: "" },
            { text: "Hải Phòng", value: "225" },
            { text: "Hà Nội", value: "201" },
            { text: "TP. Hồ Chí Minh", value: "202" },
        ]);
        return;
    }
    
    SS.province.setData([
        { text: "Chọn Tỉnh/Thành Phố", value: "" },
        ...options,
    ]);
    SS.province.setSelected([]);
    } catch (error) {
        console.error('Error in getProvince:', error);
        // Set default options on error
        if (SS.province) {
            SS.province.setData([
                { text: "Chọn Tỉnh/Thành Phố", value: "" },
                { text: "Hải Phòng", value: "225" },
                { text: "Hà Nội", value: "201" },
                { text: "TP. Hồ Chí Minh", value: "202" },
            ]);
        }
    }
}

async function getDistrict(provinceId) {
    try {
    const url = `/api/v1/ghn/district/${provinceId}`;
    const res = await apiRequest(url, "POST", { province_id: provinceId });

    // Kiểm tra SlimSelect có sẵn sàng không
    if (typeof SlimSelect === 'undefined' || !SS.district) {
        const districtSelect = document.querySelector(SELECTORS.district);
        if (districtSelect) {
            districtSelect.innerHTML = '<option value="">Chọn Quận/Huyện</option>';
            districtSelect.disabled = false;
        }
        const wardSelect = document.querySelector(SELECTORS.ward);
        if (wardSelect) {
            wardSelect.innerHTML = '<option value="">Chọn Xã/Phường</option>';
            wardSelect.disabled = true;
        }
        
        if (!res.ok || !res.data?.data) {
            return;
        }
        
        const list = toArray(res.data?.data);
        list.forEach((d) => {
            const option = document.createElement('option');
            option.value = String(d.districtID ?? d.DistrictID ?? d.districtId ?? d.district_id);
            option.textContent = d.districtName || d.DistrictName;
            districtSelect.appendChild(option);
        });
        return;
    }

    resetSelect(SS.district, "Chọn Quận/Huyện");
    resetSelect(SS.ward, "Chọn Xã/Phường");
    SS.district.enable();
    SS.ward.disable();

    if (!res.ok) {
        return;
    }

    const list = toArray(res.data?.data);
    const options = list.map((d) => ({
        text: d.districtName || d.DistrictName,
        value: String(d.districtID ?? d.DistrictID ?? d.districtId ?? d.district_id),
    }));

    SS.district.setData([{ text: "Chọn Quận/Huyện", value: "" }, ...options]);
    SS.district.setSelected([]);
    } catch (error) {
        console.error('Error in getDistrict:', error);
    }
}

async function getWard(districtId) {
    try {
    const url = `/api/v1/ghn/ward/${districtId}`;
    const res = await apiRequest(url, "POST", { district_id: districtId });

    // Kiểm tra SlimSelect có sẵn sàng không
    if (typeof SlimSelect === 'undefined' || !SS.ward) {
        const wardSelect = document.querySelector(SELECTORS.ward);
        if (wardSelect) {
            wardSelect.innerHTML = '<option value="">Chọn Xã/Phường</option>';
            wardSelect.disabled = false;
        }
        
        if (!res.ok || !res.data?.data) {
            console.error("Lỗi load xã/phường:", res.message);
            return;
        }
        
        const list = toArray(res.data?.data);
        list.forEach((w) => {
            const option = document.createElement('option');
            option.value = String(w.wardCode ?? w.WardCode ?? w.ward_code);
            option.textContent = w.wardName ?? w.WardName;
            wardSelect.appendChild(option);
        });
        return;
    }

    resetSelect(SS.ward, "Chọn Xã/Phường");
    SS.ward.enable();

    if (!res.ok) {
            console.error("Lỗi load xã/phường:", res.message);
        return;
    }

    const list = toArray(res.data?.data);
    const options = list.map((w) => ({
        text: w.wardName ?? w.WardName,
        value: String(w.wardCode ?? w.WardCode ?? w.ward_code),
    }));

    SS.ward.setData([{ text: "Chọn Xã/Phường", value: "" }, ...options]);
    SS.ward.setSelected([]);
    } catch (error) {
        console.error('Error in getWard:', error);
    }
}

// ================== HANDLERS ==================
async function onProvinceChange(el) {
    const value = el.value;
    if (hasSelection(value)) {
        // Lấy ID chính xác từ GHN (value đã là ID từ GHN)
        dataMain.provinceId = parseInt(value, 10);
        // Cập nhật hidden field trong form
        const provinceInput = document.getElementById('checkout_province_id') || document.querySelector('input[name="provinceId"]');
        if (provinceInput) provinceInput.value = dataMain.provinceId;
        
        if (typeof toggleFormOverlay === "function") toggleFormOverlay(true);
        await getDistrict(value);
        if (typeof toggleFormOverlay === "function") toggleFormOverlay(false);
    } else {
        resetSelect(SS.district, "Chọn Quận/Huyện");
        resetSelect(SS.ward, "Chọn Xã/Phường");
        SS.district.disable();
        SS.ward.disable();
        dataMain.provinceId = null;
        const provinceInput = document.getElementById('checkout_province_id') || document.querySelector('input[name="provinceId"]');
        if (provinceInput) provinceInput.value = '';
    }

    // Reset shipping fee khi thay đổi tỉnh/thành
    dataMain.shipping_fee = 0;
    totalAmount(0);
    
    // Cập nhật trạng thái voucher input (disable vì chưa có shipping fee)
    if (typeof updateVoucherInputState === 'function') {
        updateVoucherInputState();
    }
    
    // Revalidate voucher nếu đang có (có thể sẽ fail vì shipping = 0)
    if (typeof revalidateVoucher === 'function') {
        await revalidateVoucher();
    }
}

async function onDistrictChange(el) {
    const value = el.value;
    if (hasSelection(value)) {
        // Lấy ID chính xác từ GHN (value đã là ID từ GHN)
        dataMain.districtId = parseInt(value, 10);
        // Cập nhật hidden field trong form
        const districtInput = document.getElementById('checkout_district_id') || document.querySelector('input[name="districtId"]');
        if (districtInput) districtInput.value = dataMain.districtId;
        
        if (typeof toggleFormOverlay === "function") toggleFormOverlay(true);
        await getWard(value);
        if (typeof toggleFormOverlay === "function") toggleFormOverlay(false);
    } else {
        resetSelect(SS.ward, "Chọn Xã/Phường");
        SS.ward.disable();
        dataMain.districtId = null;
        const districtInput = document.querySelector('input[name="districtId"]');
        if (districtInput) districtInput.value = '';
    }

    // Reset shipping fee khi thay đổi quận/huyện
    dataMain.shipping_fee = 0;
    totalAmount(0);
    
    // Cập nhật trạng thái voucher input
    if (typeof updateVoucherInputState === 'function') {
        updateVoucherInputState();
    }
    
    // Revalidate voucher nếu đang có
    if (typeof revalidateVoucher === 'function') {
        await revalidateVoucher();
    }
}

function totalAmount(fee) {
  const estimatedEl = document.querySelector('#checkout_estimated');
  const totalEl = document.querySelector('#checkout_total');
  const shipFeeEl = document.querySelector('#checkout_shipping_fee');
  const voucherDiscountEl = document.querySelector('#checkout_voucher_discount');
  const voucherDiscountRow = document.querySelector('#voucher_discount_row');

  // Kiểm tra các element có tồn tại không
  if (!estimatedEl || !totalEl || !shipFeeEl) {
    return;
  }

  // Lấy text và chuyển thành số
  const estimatedText = estimatedEl?.textContent || "0";
  const estimated = parseInt(estimatedText.replace(/[^\d]/g, ""), 10) || 0;
  const shipping = Number(fee ?? 0);
  const voucherDiscount = appliedVoucher ? appliedVoucher.discount_amount : 0;

  // Tính tổng
  const grandTotal = estimated + shipping - voucherDiscount;

  // Hiển thị định dạng tiền
  shipFeeEl.innerHTML = `${formatCurrencyVND(shipping)}đ`;
  
  // Xử lý voucher discount nếu có element
  if (voucherDiscountEl && voucherDiscountRow) {
    if (voucherDiscount > 0) {
      voucherDiscountEl.innerHTML = `-${formatCurrencyVND(voucherDiscount)}đ`;
      voucherDiscountRow.style.display = 'table-row';
    } else {
      voucherDiscountRow.style.display = 'none';
    }
  }
  
  totalEl.innerHTML = `${formatCurrencyVND(grandTotal)}đ`;

  // Cập nhật dataMain
  dataMain.shipping_fee = shipping;
  dataMain.voucher_discount = voucherDiscount;
  dataMain.total = grandTotal;
}

async function onWardChange(el) {
    const value = el.value;
    if (hasSelection(value)) {
        // Lấy WardCode chính xác từ GHN (value đã là WardCode từ GHN)
        dataMain.wardId = String(value); // WardCode có thể là string
        // Cập nhật hidden field trong form
        const wardInput = document.getElementById('checkout_ward_id') || document.querySelector('input[name="wardId"]');
        if (wardInput) wardInput.value = dataMain.wardId;
        
        toggleFormOverlay(true);
        try {
            const url = `/api/v1/ghn/services/${encodeURIComponent(
                dataMain.districtId
            )}`;
            const res = await fetch(url, { 
                method: "GET",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });

            if (!res.ok) {
                const errorText = await res.text();
                console.error('Services error response:', errorText);
                throw new Error(`HTTP ${res.status}: ${errorText}`);
            }

            const json = await res.json();

            if (json && json.data && Array.isArray(json.data)) {
                // Tìm dịch vụ "Hàng nhẹ"
                const lightService = json.data.find(
                    (s) => s.shortName === "Hàng nhẹ"
                );
                const heavyService = json.data.find(
                    (s) => s.shortName === "Hàng nặng"
                );

                if (lightService) {
                    // ✅ Có dịch vụ hàng nhẹ → dùng nó
                    dataMain.serviceId = lightService.serviceId || lightService.service_id;
                    dataMain.serviceTypeId = lightService.serviceTypeId || lightService.service_type_id;
                    
                    // Cập nhật hidden fields
                    const serviceIdInput = document.getElementById('checkout_service_id');
                    const serviceTypeIdInput = document.getElementById('checkout_service_type_id');
                    if (serviceIdInput) serviceIdInput.value = dataMain.serviceId;
                    if (serviceTypeIdInput) serviceTypeIdInput.value = dataMain.serviceTypeId;

                } else if (heavyService) {
                    // ⚙️ Không có hàng nhẹ → fallback sang hàng nặng
                    dataMain.serviceId = heavyService.serviceId || heavyService.service_id;
                    dataMain.serviceTypeId = heavyService.serviceTypeId || heavyService.service_type_id;
                    
                    // Cập nhật hidden fields
                    const serviceIdInput = document.getElementById('checkout_service_id');
                    const serviceTypeIdInput = document.getElementById('checkout_service_type_id');
                    if (serviceIdInput) serviceIdInput.value = dataMain.serviceId;
                    if (serviceTypeIdInput) serviceTypeIdInput.value = dataMain.serviceTypeId;

                    // Hiển thị cảnh báo thân thiện cho người dùng
                    showCustomToast(
                        "Không có phương thức 'Hàng nhẹ', hệ thống tự động chuyển sang 'Hàng nặng'.",
                        "warning"
                    );
                } else {
                    // ❌ Không có bất kỳ dịch vụ nào
                    showCustomToast(
                        "Hiện tại GHN chưa hỗ trợ giao hàng đến khu vực này.",
                        "error"
                    );
                }

                if (
                    dataMain &&
                    typeof dataMain.serviceId === "number" &&
                    dataMain.serviceId > 0 &&
                    typeof dataMain.serviceTypeId === "number" &&
                    dataMain.serviceTypeId > 0
                ) {
                    try {
                        const url = `/api/v1/ghn/calculate-fee`;
                        const requestData = {
                                items: dataMain.items || [],              // danh sách sản phẩm
                                provinceId: dataMain.provinceId || null,  // nếu có
                                districtId: dataMain.districtId || null,  // ID quận/huyện
                                wardId: dataMain.wardId || null,          // ID phường/xã
                                serviceId: dataMain.serviceId || null,    // ID dịch vụ GHN (vd: 53322)
                                serviceTypeId: dataMain.serviceTypeId || 2, // mặc định hàng nhẹ
                                subtotal: dataMain.subtotal || 0,         // tạm tính
                                total: dataMain.total || 0,               // tổng thanh toán (để làm insurance_value)
                                payment: dataMain.payment || "cod",       // phương thức thanh toán
                        };
                        
                        const res = await fetch(url, {
                            method: "POST",
                            headers: { 
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify(requestData),
                        });

                        if (!res.ok) {
                            const errorText = await res.text();
                            console.error('Calculate fee error response:', errorText);
                            throw new Error(`HTTP ${res.status}: ${errorText}`);
                        }

                        const json = await res.json();
                        

                        // Xử lý nếu thành công
                        if (json?.code === 200 && json?.data && typeof json.data === "object") {
                            const shippingFee = Math.round(Number(json.data?.total ?? 0) / 1000) * 1000;
                            
                            // Cập nhật hidden fields
                            const shippingInput = document.getElementById('checkout_shipping_value');
                            const shippingFeeInput = document.getElementById('checkout_shipping_fee_value');
                            if (shippingInput) shippingInput.value = shippingFee;
                            if (shippingFeeInput) shippingFeeInput.value = shippingFee;
                            
                            document.querySelector('.nobifashion_main_checkout_options').innerHTML = `<label><input value="${shippingFee}" type="radio" name="shipping" checked /> Giao hàng tiêu chuẩn (${formatCurrencyVND(shippingFee)}đ)</label>`;
                            
                            // Cập nhật shipping fee trong dataMain
                            dataMain.shipping_fee = shippingFee;
                            
                            // Cập nhật shipping fee trong dataMain
                            dataMain.shipping_fee = shippingFee;
                            
                            // Cập nhật trạng thái voucher input (enable nếu có shipping fee)
                            if (typeof updateVoucherInputState === 'function') {
                                updateVoucherInputState();
                            }
                            
                            totalAmount(shippingFee);
                            
                            // Revalidate voucher nếu đang có (để tính lại discount với shipping fee mới)
                            if (typeof revalidateVoucher === 'function') {
                                await revalidateVoucher();
                            } else {
                                // Nếu chưa có voucher, chỉ cập nhật total
                                totalAmount(shippingFee);
                            }
                            
                            showCustomToast(
                              "Đã lấy được phương thức giao hàng 🚚. Vui lòng chọn cách giao phù hợp nhé!",
                              "success"
                            );
                        }
                    } catch (err) {
                        console.error("❌ Lỗi khi gọi GHN:", err);
                        showCustomToast(
                            "Có lỗi xảy ra trong quá trình xử lý dữ liệu vận chuyển 🚚. Hệ thống đang khắc phục!",
                            "error"
                        );
                    }
                } else {
                    // ❌ Thiếu hoặc sai định dạng
                }
            } else {
                showCustomToast(
                    "Không lấy được danh sách dịch vụ từ GHN.",
                    "error"
                );
            }
        } catch (err) {
            console.error("Geocode error:", err);
            const box = ensureDropdown();
            box.innerHTML = `<p style="padding:8px;color:red;">Lỗi tải địa chỉ</p>`;
            box.style.display = "block";
        } finally {
            if (typeof toggleFormOverlay === "function")
                toggleFormOverlay(false);
        }
    } else {
        document.querySelector(
            ".nobifashion_main_checkout_options"
        ).innerHTML = `
              <div style="
                  padding: 16px;
                  border: 1px dashed #d0d0d0;
                  border-radius: 10px;
                  background: #fafafa;
                  text-align: center;
                  color: #ff0000ff;
                  font-size: 15px;
                  line-height: 1.6;
                  font-family: 'Segoe UI', Roboto, sans-serif;
                  margin-top: 10px;
              ">
                  🚚 <strong>Chưa có phương thức giao hàng</strong><br>
                  <span style="color:#666;">
                    Vui lòng chọn <b>Tỉnh/Thành</b>, <b>Quận/Huyện</b> và <b>Xã/Phường</b> 
                    để hiển thị các lựa chọn giao hàng phù hợp nhé 💌
                  </span>
              </div>
            `;
    }
}

// ================== VALIDATION ==================
function validateFormCheckout() {
    const form = getDOMElement(SELECTORS.form_checkout);
    if (!form) return false;

    let firstNoticeShown = false;
    let isValid = true;

    const showOnce = (msg, el, focusable = true) => {
        if (!firstNoticeShown) {
            showCustomToast(msg, "error");
            if (focusable && el?.focus) el.focus();
            else el?.scrollIntoView({ behavior: "smooth", block: "center" });
            firstNoticeShown = true;
        }
    };

    const markError = (el, hasError) => {
        if (!el) return;
        el.classList.toggle("error", !!hasError);
    };

    // === 1. INPUT / TEXTAREA ===
    const fullname = form.querySelector('input[placeholder="Nguyễn Văn A"]');
    const email = form.querySelector('input[type="email"]');
    const phone = form.querySelector('input[type="tel"]');
    const address = form.querySelector(
        'input[placeholder="Số nhà (ngõ), Đường, Xã/Phường"]'
    );

    const nameVal = fullname?.value.trim() || "";
    const emailVal = email?.value.trim() || "";
    const phoneVal = phone?.value.trim() || "";
    const addrVal = address?.value.trim() || "";

    // --- Họ tên ---
    const nameRegex = /^[A-Za-zÀ-ỹ\s'.-]+$/;
    if (!nameVal) {
        markError(fullname, true);
        showOnce("Vui lòng nhập Họ và tên", fullname);
        isValid = false;
    } else if (nameVal.length < 4 || !nameRegex.test(nameVal)) {
        markError(fullname, true);
        showOnce(
            "Họ và tên không hợp lệ (ít nhất 4 ký tự, không chứa số)",
            fullname
        );
        isValid = false;
    } else {
        dataMain.fullname = nameVal;
        markError(fullname, false);
    }

    // --- Email ---
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailVal) {
        markError(email, true);
        showOnce("Vui lòng nhập Email", email);
        isValid = false;
    } else if (!emailRegex.test(emailVal)) {
        markError(email, true);
        showOnce("Email không hợp lệ", email);
        isValid = false;
    } else {
        dataMain.email = emailVal;
        markError(email, false);
    }

    // --- SĐT ---
    const phoneRegex = /^(0|\+84)\d{9}$/;
    if (!phoneVal) {
        markError(phone, true);
        showOnce("Vui lòng nhập Số điện thoại", phone);
        isValid = false;
    } else if (!phoneRegex.test(phoneVal)) {
        markError(phone, true);
        showOnce("Số điện thoại không hợp lệ", phone);
        isValid = false;
    } else {
        dataMain.phone = phoneVal;
        markError(phone, false);
    }

    // --- Địa chỉ ---
    if (!addrVal) {
        markError(address, true);
        showOnce("Vui lòng nhập Địa chỉ", address);
        isValid = false;
    } else {
        dataMain.address = addrVal;
        markError(address, false);
    }

    // --- Ghi chú đơn hàng ---
    const customerNote = form.querySelector('textarea[name="customer_note"]');
    const noteVal = customerNote?.value.trim() || "";
    dataMain.customer_note = sanitizeInput(noteVal);

    // === 2. GHN Selects ===
    const province = getDOMElement(SELECTORS.province);
    const district = getDOMElement(SELECTORS.district);
    const ward = getDOMElement(SELECTORS.ward);
    

    const slimError = (sel, msg) => {
        const slimContainer = sel?.closest(".ss-main");
        if (slimContainer) slimContainer.classList.add("error");
        if (!firstNoticeShown) {
            showCustomToast(msg, "error");
            slimContainer?.scrollIntoView({
                behavior: "smooth",
                block: "center",
            });
            firstNoticeShown = true;
        }
        isValid = false;
    };

    const slimClear = (sel) =>
        sel?.closest(".ss-main")?.classList.remove("error");

    // Lấy value từ SlimSelect hoặc native select
    const getProvinceValue = () => {
        if (SS.province) {
            const selected = SS.province.selected();
            return selected && selected.length > 0 ? selected[0] : null;
        }
        return province?.value || null;
    };
    
    const getDistrictValue = () => {
        if (SS.district) {
            const selected = SS.district.selected();
            return selected && selected.length > 0 ? selected[0] : null;
        }
        return district?.value || null;
    };
    
    const getWardValue = () => {
        if (SS.ward) {
            const selected = SS.ward.selected();
            return selected && selected.length > 0 ? selected[0] : null;
        }
        return ward?.value || null;
    };

    const provinceValue = getProvinceValue();
    const districtValue = getDistrictValue();
    const wardValue = getWardValue();

    if (!provinceValue || provinceValue === "null" || provinceValue === "") {
      slimError(province, "Vui lòng chọn Tỉnh/Thành phố");
      totalAmount(0);
    } else {
        slimClear(province);
        dataMain.provinceId = parseInt(provinceValue, 10);
    }

    if (!districtValue || districtValue === "null" || districtValue === "") {
      slimError(district, "Vui lòng chọn Quận/Huyện");
      totalAmount(0);
    } else {
        slimClear(district);
        dataMain.districtId = parseInt(districtValue, 10);
    }

    if (!wardValue || wardValue === "null" || wardValue === "") {
        slimError(ward, "Vui lòng chọn Xã/Phường");
        totalAmount(0);
    } else {
        slimClear(ward);
        dataMain.wardId = String(wardValue); // WardCode có thể là string
    }

    // === 3. RADIO ===
    const shipping = form.querySelectorAll('input[name="shipping"]');
    const payment = form.querySelectorAll('input[name="payment"]');

    const radioGroups = [
        { key: "shipping", name: "Phương thức giao hàng", radios: shipping },
        { key: "payment", name: "Phương thức thanh toán", radios: payment },
    ];

    radioGroups.forEach(({ key, name, radios }) => {
        const checkedRadio = Array.from(radios).find((r) => r.checked);

        if (!checkedRadio) {
            // ❌ Chưa chọn radio nào
            radios.forEach((r) => r.closest("label")?.classList.add("error"));
            showOnce(`Vui lòng chọn ${name.toLowerCase()}`, radios[0], false);
            isValid = false;
        } else {
            // ✅ Đã chọn radio → bỏ class lỗi và gán value
            radios.forEach((r) =>
                r.closest("label")?.classList.remove("error")
            );
            const value = checkedRadio.value;
            dataMain[key] = value; // 🟢 Lưu giá trị vào object
            
            // Cập nhật hidden field cho shipping
            if (key === 'shipping') {
                const shippingInput = document.getElementById('checkout_shipping_value');
                if (shippingInput) shippingInput.value = value;
            }
        }
    });
    collectCartItems();
    
    // Đảm bảo các ID từ GHN được gửi đúng
    const provinceInput = document.getElementById('checkout_province_id') || form.querySelector('input[name="provinceId"]');
    const districtInput = document.getElementById('checkout_district_id') || form.querySelector('input[name="districtId"]');
    const wardInput = document.getElementById('checkout_ward_id') || form.querySelector('input[name="wardId"]');
    
    if (provinceInput && dataMain.provinceId) {
        provinceInput.value = dataMain.provinceId;
    }
    if (districtInput && dataMain.districtId) {
        districtInput.value = dataMain.districtId;
    }
    if (wardInput && dataMain.wardId) {
        wardInput.value = dataMain.wardId;
    }
    
    // Cập nhật các hidden fields khác
    const serviceIdInput = document.getElementById('checkout_service_id');
    const serviceTypeIdInput = document.getElementById('checkout_service_type_id');
    const shippingInput = document.getElementById('checkout_shipping_value');
    const shippingFeeInput = document.getElementById('checkout_shipping_fee_value');
    const subtotalInput = document.getElementById('checkout_subtotal_value');
    const totalInput = document.getElementById('checkout_total_value');
    
    if (serviceIdInput && dataMain.serviceId) serviceIdInput.value = dataMain.serviceId;
    if (serviceTypeIdInput && dataMain.serviceTypeId) serviceTypeIdInput.value = dataMain.serviceTypeId;
    if (shippingInput && dataMain.shipping) shippingInput.value = dataMain.shipping;
    if (shippingFeeInput && dataMain.shipping_fee) shippingFeeInput.value = dataMain.shipping_fee;
    if (subtotalInput && dataMain.subtotal) subtotalInput.value = dataMain.subtotal;
    if (totalInput && dataMain.total) totalInput.value = dataMain.total;

    return isValid;
}

// ================== GỢI Ý ĐỊA CHỈ (GEOCODE API - dùng GET ?q=) ==================
function setupAddressAutocomplete() {
    const input = document.getElementById(
        "nobifashion_main_checkout_form_address"
    );
    const addressWrapper = document.querySelector(
        ".nobifashion_main_checkout_form_address"
    );
    if (!input || !addressWrapper) return;

    let dropdown = null;

    // Hàm tạo box dropdown khi cần
    const ensureDropdown = () => {
        if (!dropdown) {
            dropdown = document.createElement("div");
            dropdown.className = "nobifashion_main_checkout_form_address_all";
            addressWrapper.appendChild(dropdown);
        }
        return dropdown;
    };

    let debounceTimer;

    input.addEventListener("input", () => {
        clearTimeout(debounceTimer);
        const query = input.value.trim();

        // Nếu trống => ẩn gợi ý
        if (!query) {
            if (dropdown) dropdown.style.display = "none";
            return;
        }

        debounceTimer = setTimeout(async () => {
            const box = ensureDropdown();
            box.style.display = "block";
            box.innerHTML = `<p style="padding:8px;color:#999;">Đang tìm kiếm...</p>`;

            if (typeof toggleFormOverlay === "function")
                toggleFormOverlay(true);

            try {
                const url = `/api/v1/general/geocode?q=${encodeURIComponent(
                    query
                )}`;
                const res = await fetch(url, { method: "GET" });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                const data = await res.json();

                // HERE API trả về { items: [ { address: { label: "..." } } ] }
                const items = Array.isArray(data.items) ? data.items : [];

                if (!items.length) {
                    box.innerHTML = `<p style="padding:8px;color:#999;">Không tìm thấy địa chỉ phù hợp</p>`;
                    box.style.display = "block";
                    return;
                }

                // Hiển thị danh sách gợi ý — mỗi item 1 dòng
                box.innerHTML = items
                    .map(
                        (item) =>
                            `<p class="nobifashion_main_checkout_form_address_item">${item.address.label}</p>`
                    )
                    .join("");

                box.style.display = "block";

                // Sự kiện click chọn địa chỉ
                box.querySelectorAll(
                    ".nobifashion_main_checkout_form_address_item"
                ).forEach((el) => {
                    el.addEventListener("click", () => {
                        input.value = el.textContent.trim();
                        box.style.display = "none";
                    });
                });
            } catch (err) {
                console.error("Geocode error:", err);
                const box = ensureDropdown();
                box.innerHTML = `<p style="padding:8px;color:red;">Lỗi tải địa chỉ</p>`;
                box.style.display = "block";
            } finally {
                if (typeof toggleFormOverlay === "function")
                    toggleFormOverlay(false);
            }
        }, 500);
    });

    // Click ra ngoài => ẩn dropdown
    // document.addEventListener('click', (e) => {
    //   if (!addressWrapper.contains(e.target)) {
    //     if (dropdown) dropdown.style.display = 'none';
    //   }
    // });
}

function submitCheckoutForm(dataMain) {
    // Sử dụng thẻ form bao ngoài toàn bộ checkout
    const form = document.querySelector('.nobifashion_checkout_form_wrapper');
    if (!form) {
        console.error('Checkout form not found');
        return;
    }

    // Đảm bảo tất cả hidden fields đã được cập nhật với ID chính xác từ GHN
    const provinceInput = document.getElementById('checkout_province_id') || form.querySelector('input[name="provinceId"]');
    const districtInput = document.getElementById('checkout_district_id') || form.querySelector('input[name="districtId"]');
    const wardInput = document.getElementById('checkout_ward_id') || form.querySelector('input[name="wardId"]');
    
    if (provinceInput && dataMain.provinceId) {
        provinceInput.value = dataMain.provinceId;
    }
    if (districtInput && dataMain.districtId) {
        districtInput.value = dataMain.districtId;
    }
    if (wardInput && dataMain.wardId) {
        wardInput.value = dataMain.wardId;
    }
    
    // Cập nhật các hidden fields khác
    const serviceIdInput = document.getElementById('checkout_service_id');
    const serviceTypeIdInput = document.getElementById('checkout_service_type_id');
    const shippingInput = document.getElementById('checkout_shipping_value');
    const shippingFeeInput = document.getElementById('checkout_shipping_fee_value');
    const subtotalInput = document.getElementById('checkout_subtotal_value');
    const totalInput = document.getElementById('checkout_total_value');
    
    if (serviceIdInput && dataMain.serviceId) serviceIdInput.value = dataMain.serviceId;
    if (serviceTypeIdInput && dataMain.serviceTypeId) serviceTypeIdInput.value = dataMain.serviceTypeId;
    if (shippingInput && dataMain.shipping) shippingInput.value = dataMain.shipping;
    if (shippingFeeInput && dataMain.shipping_fee) shippingFeeInput.value = dataMain.shipping_fee;
    if (subtotalInput && dataMain.subtotal) subtotalInput.value = dataMain.subtotal;
    if (totalInput && dataMain.total) totalInput.value = dataMain.total;
    
    // Add voucher data if applied
    if (appliedVoucher) {
        const voucherCodeInput = document.getElementById('voucher_code_input');
        const voucherDiscountInput = document.getElementById('voucher_discount_input');
        if (voucherCodeInput) voucherCodeInput.value = appliedVoucher.code;
        if (voucherDiscountInput) voucherDiscountInput.value = appliedVoucher.discount_amount;
    }
    
    // Cập nhật select values để đảm bảo form submit đúng
    const provinceSelect = form.querySelector('select[name="provinceId"]');
    const districtSelect = form.querySelector('select[name="districtId"]');
    const wardSelect = form.querySelector('select[name="wardId"]');
    
    if (provinceSelect && dataMain.provinceId) {
        provinceSelect.value = dataMain.provinceId;
    }
    if (districtSelect && dataMain.districtId) {
        districtSelect.value = dataMain.districtId;
    }
    if (wardSelect && dataMain.wardId) {
        wardSelect.value = dataMain.wardId;
    }

    // ✅ Gửi form thật sự
    form.submit();
}

// ================== INIT ==================
document.addEventListener("DOMContentLoaded", async () => {
    // Force hide any existing loading overlay first
    forceHideLoading();
    
    // ===== LOAD TỈNH =====
    if (typeof toggleFormOverlay === "function") toggleFormOverlay(true);
    await getProvince();
    if (typeof toggleFormOverlay === "function") toggleFormOverlay(false);

    const form = getDOMElement(SELECTORS.form_checkout);
    const orderBtn = document.querySelector(".nobifashion_main_checkout_btn");

    if (!form || !orderBtn) return;
    collectCartItems();

    // ===== Khi bấm nút "Đặt hàng" =====
    orderBtn.addEventListener("click", (e) => {
        e.preventDefault();
        if (isSubmitting) return;

        const isValid = validateFormCheckout(); // chạy validate form
        if (!isValid) return; // nếu form lỗi thì dừng

        // Form hợp lệ → gửi đơn
        isSubmitting = true;
        showCustomToast("Đơn hàng đang được xử lý...", "success");

        // TODO: Gọi API gửi đơn hàng ở đây
        setTimeout(() => {
            isSubmitting = false;
            collectCartItems();
            submitCheckoutForm(dataMain);
        }, 1500);
    });

    // ===== Khi người dùng nhập lại thì bỏ viền đỏ =====
    form.addEventListener("input", (e) => {
        if (
            e.target.classList.contains("error") &&
            e.target.value.trim() !== ""
        ) {
            e.target.classList.remove("error");
        }
    });

    setupAddressAutocomplete();
    
    // ===== VOUCHER FUNCTIONALITY =====
    setupVoucherHandlers();
    
    // ===== SHIPPING METHOD CHANGE LISTENER =====
    // Listen cho sự kiện thay đổi shipping method (radio button)
    // Sử dụng event delegation để bắt sự kiện từ các radio button được tạo động
    document.addEventListener('change', async function(e) {
        if (e.target.name === 'shipping' && e.target.checked) {
            const shippingFee = parseFloat(e.target.value) || 0;
            dataMain.shipping_fee = shippingFee;
            
            // Cập nhật hidden field
            const shippingFeeInput = document.getElementById('checkout_shipping_fee_value');
            if (shippingFeeInput) shippingFeeInput.value = shippingFee;
            
            // Cập nhật trạng thái voucher input
            if (typeof updateVoucherInputState === 'function') {
                updateVoucherInputState();
            }
            
            // Revalidate voucher nếu đang có (để tính lại discount với shipping fee mới)
            if (typeof revalidateVoucher === 'function') {
                await revalidateVoucher();
            } else {
                // Nếu chưa có voucher, chỉ cập nhật total
                totalAmount(shippingFee);
            }
        }
    });
    
    // ===== CUSTOMER NOTE COUNTER =====
    setupCustomerNoteCounter();
    
    // ===== ERROR HANDLING =====
    // Hide loading overlay on any error
    window.addEventListener('error', () => {
        forceHideLoading();
    });
    
    // Hide loading overlay on unhandled promise rejection
    window.addEventListener('unhandledrejection', () => {
        forceHideLoading();
    });
});

// ================== VOUCHER FUNCTIONALITY ==================
function setupVoucherHandlers() {
    const voucherCodeInput = document.getElementById('voucher_code');
    const applyVoucherBtn = document.getElementById('apply_voucher_btn');
    const removeVoucherBtn = document.getElementById('remove_voucher_btn');
    const voucherResult = document.getElementById('voucher_result');
    const voucherInfo = document.getElementById('voucher_info');

    if (!voucherCodeInput || !applyVoucherBtn) return;

    // Disable voucher input cho đến khi có shipping fee
    function updateVoucherInputState() {
        const shippingFee = dataMain.shipping_fee || 0;
        const hasShipping = shippingFee > 0;
        
        voucherCodeInput.disabled = !hasShipping;
        applyVoucherBtn.disabled = !hasShipping;
        
        if (!hasShipping) {
            voucherCodeInput.placeholder = 'Vui lòng chọn địa chỉ giao hàng trước';
            if (appliedVoucher) {
                // Xóa voucher nếu đang có nhưng shipping bị reset
                appliedVoucher = null;
                hideVoucherInfo();
            }
        } else {
            voucherCodeInput.placeholder = 'Nhập mã giảm giá (VD: SALE10, WELCOME20)';
        }
    }

    // Gọi lần đầu để set trạng thái ban đầu
    updateVoucherInputState();

    // Function để validate và apply voucher
    async function validateAndApplyVoucher(voucherCode) {
        if (!voucherCode || !voucherCode.trim()) {
            showVoucherMessage('Vui lòng nhập mã voucher.', 'error');
            return false;
        }

        // Kiểm tra đã có shipping fee chưa
        if (!dataMain.shipping_fee || dataMain.shipping_fee <= 0) {
            showVoucherMessage('Vui lòng chọn địa chỉ giao hàng trước khi áp dụng voucher.', 'error');
            return false;
        }

        if (typeof toggleFormOverlay === "function") toggleFormOverlay(true);
        
        // Add loading state to button
        applyVoucherBtn.classList.add('loading');
        applyVoucherBtn.disabled = true;

        try {
            const orderData = collectCartItems();
            const response = await fetch('/voucher/validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    voucher_code: voucherCode.trim(),
                    order_data: orderData
                })
            });

            const result = await response.json();

            if (result.success) {
                appliedVoucher = {
                    code: voucherCode.trim(),
                    discount_amount: result.discount_amount,
                    voucher: result.voucher
                };
                
                showVoucherApplied(result.voucher.name || result.voucher.code, result.discount_amount);
                totalAmount(dataMain.shipping_fee || 0);
                voucherCodeInput.value = '';
                return true;
            } else {
                showVoucherMessage(result.message, 'error');
                return false;
            }
        } catch (error) {
            console.error('Voucher validation error:', error);
            showVoucherMessage('Có lỗi xảy ra khi xử lý voucher.', 'error');
            return false;
        } finally {
            // Remove loading state
            applyVoucherBtn.classList.remove('loading');
            applyVoucherBtn.disabled = false;
            
            if (typeof toggleFormOverlay === "function") toggleFormOverlay(false);
        }
    }

    // Apply voucher
    applyVoucherBtn.addEventListener('click', async () => {
        await validateAndApplyVoucher(voucherCodeInput.value);
    });

    // Revalidate voucher khi shipping thay đổi
    window.revalidateVoucher = async function() {
        if (appliedVoucher && appliedVoucher.code) {
            // Revalidate với shipping fee mới
            const success = await validateAndApplyVoucher(appliedVoucher.code);
            if (!success) {
                // Nếu revalidate thất bại → xóa voucher
                appliedVoucher = null;
                hideVoucherInfo();
                totalAmount(dataMain.shipping_fee || 0);
            }
        }
    };

    // Remove voucher
    if (removeVoucherBtn) {
        removeVoucherBtn.addEventListener('click', () => {
            appliedVoucher = null;
            hideVoucherInfo();
            totalAmount(dataMain.shipping_fee || 0);
        });
    }

    // Enter key to apply voucher
    voucherCodeInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (!voucherCodeInput.disabled) {
                applyVoucherBtn.click();
            }
        }
    });

    // Expose updateVoucherInputState để có thể gọi từ bên ngoài
    window.updateVoucherInputState = updateVoucherInputState;
    
    // Load voucher suggestions khi có shipping fee
    function loadVoucherSuggestions() {
        if (!dataMain.shipping_fee || dataMain.shipping_fee <= 0) {
            document.getElementById('voucher_suggestions').style.display = 'none';
            return;
        }
        
        const orderData = collectCartItems();
        if (!orderData.items || orderData.items.length === 0) {
            return;
        }
        
        fetch('/voucher/available', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.vouchers && data.vouchers.length > 0) {
                const suggestionsEl = document.getElementById('voucher_suggestions');
                const listEl = document.getElementById('voucher_suggestions_list');
                listEl.innerHTML = '';
                
                // Filter và hiển thị tối đa 3 voucher phù hợp
                const applicableVouchers = data.vouchers.slice(0, 3);
                
                applicableVouchers.forEach(voucher => {
                    const voucherEl = document.createElement('div');
                    voucherEl.className = 'voucher-suggestion-item';
                    voucherEl.style.cssText = 'padding: 8px 12px; border: 1px solid #e0e0e0; border-radius: 6px; background: #f9f9f9; cursor: pointer; transition: all 0.2s;';
                    voucherEl.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="color: #FF3366;">${voucher.code}</strong>
                                ${voucher.name ? `<div style="font-size: 12px; color: #666;">${voucher.name}</div>` : ''}
                            </div>
                            <button type="button" class="btn-apply-suggestion" data-code="${voucher.code}" 
                                    style="padding: 4px 12px; background: #FF3366; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">
                                Áp dụng
                            </button>
                        </div>
                    `;
                    
                    voucherEl.addEventListener('mouseenter', () => {
                        voucherEl.style.background = '#fff';
                        voucherEl.style.borderColor = '#FF3366';
                    });
                    voucherEl.addEventListener('mouseleave', () => {
                        voucherEl.style.background = '#f9f9f9';
                        voucherEl.style.borderColor = '#e0e0e0';
                    });
                    
                    voucherEl.querySelector('.btn-apply-suggestion').addEventListener('click', async (e) => {
                        e.stopPropagation();
                        const code = e.target.getAttribute('data-code');
                        voucherCodeInput.value = code;
                        await validateAndApplyVoucher(code);
                    });
                    
                    listEl.appendChild(voucherEl);
                });
                
                suggestionsEl.style.display = 'block';
            } else {
                document.getElementById('voucher_suggestions').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error loading voucher suggestions:', error);
        });
    }
    
    // Load suggestions khi shipping fee thay đổi
    if (typeof updateVoucherInputState === 'function') {
        const originalUpdateVoucherInputState = updateVoucherInputState;
        window.updateVoucherInputState = function() {
            originalUpdateVoucherInputState();
            if (dataMain.shipping_fee > 0) {
                loadVoucherSuggestions();
            }
        };
    }
    
    // Load suggestions ban đầu nếu có shipping fee
    if (dataMain.shipping_fee > 0) {
        setTimeout(loadVoucherSuggestions, 500);
    }
}

function showVoucherMessage(message, type) {
    const voucherResult = document.getElementById('voucher_result');
    const voucherSuccess = voucherResult.querySelector('.voucher_success');
    const voucherError = voucherResult.querySelector('.voucher_error');

    voucherSuccess.style.display = type === 'success' ? 'block' : 'none';
    voucherError.style.display = type === 'error' ? 'block' : 'none';
    
    if (type === 'success') {
        voucherSuccess.textContent = message;
    } else {
        voucherError.textContent = message;
    }
    
    voucherResult.style.display = 'block';
    
    // Auto hide after 5 seconds
    setTimeout(() => {
        voucherResult.style.display = 'none';
    }, 5000);
}

function showVoucherApplied(voucherName, discountAmount) {
    const voucherInfo = document.getElementById('voucher_info');
    const voucherNameEl = voucherInfo.querySelector('.voucher_name');
    const voucherDiscountEl = voucherInfo.querySelector('.voucher_discount');

    voucherNameEl.textContent = voucherName;
    voucherDiscountEl.textContent = `-${formatCurrencyVND(discountAmount)}đ`;
    voucherInfo.style.display = 'block';
    
    // Hide result messages
    document.getElementById('voucher_result').style.display = 'none';
}

function hideVoucherInfo() {
    document.getElementById('voucher_info').style.display = 'none';
    document.getElementById('voucher_result').style.display = 'none';
}

// ================== CUSTOMER NOTE COUNTER ==================
function setupCustomerNoteCounter() {
    const noteTextarea = document.querySelector('textarea[name="customer_note"]');
    const counter = document.getElementById('note-counter');
    
    if (!noteTextarea || !counter) return;
    
    function updateCounter() {
        const length = noteTextarea.value.length;
        counter.textContent = length;
        
        // Change color based on length
        if (length > 450) {
            counter.style.color = '#dc2626';
        } else if (length > 400) {
            counter.style.color = '#f59e0b';
        } else {
            counter.style.color = '#666';
        }
    }
    
    // Update on input
    noteTextarea.addEventListener('input', updateCounter);
    
    // Initial update
    updateCounter();
}

// ================== XSS PROTECTION ==================
function sanitizeInput(input) {
    if (typeof input !== 'string') return input;
    
    // Remove HTML tags
    let sanitized = input.replace(/<[^>]*>/g, '');
    
    // Remove script tags and javascript: protocols
    sanitized = sanitized.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
    sanitized = sanitized.replace(/javascript:/gi, '');
    
    // Remove event handlers
    sanitized = sanitized.replace(/on\w+\s*=/gi, '');
    
    // Remove potential SQL injection patterns
    sanitized = sanitized.replace(/['";]/g, '');
    
    // Remove potential XSS patterns
    sanitized = sanitized.replace(/[<>]/g, '');
    
    return sanitized.trim();
}
