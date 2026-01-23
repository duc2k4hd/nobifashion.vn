// Hiển thị ảnh chính khi click vào ảnh
const boxIMG = document.querySelector(".nobifashion_single_info_images_main");
const mainIMG = document.querySelector(
    ".nobifashion_single_info_images_main_image"
);

boxIMG?.addEventListener("click", (e) => {
    e.stopPropagation();
    const url = mainIMG?.getAttribute("src");
    if (!url) return;

    // Nếu overlay đã tồn tại thì remove
    const oldOverlay = document.querySelector(
        ".nobifashion_single_info_images_main_overlay"
    );
    if (oldOverlay) oldOverlay.remove();

    // Tạo overlay
    const overlay = document.createElement("div");
    overlay.classList.add("nobifashion_single_info_images_main_overlay");

    // Tạo ảnh popup
    const popupIMG = document.createElement("img");
    popupIMG.src = url;
    popupIMG.alt = "Preview";
    popupIMG.classList.add("nobifashion_single_info_images_main_image_show");

    overlay.appendChild(popupIMG);
    document.body.appendChild(overlay);

    // Đóng khi click overlay
    overlay.addEventListener("click", () => overlay.remove());
});

// Tabs mô tả
const tabButtons = document.querySelectorAll(
    ".nobifashion_single_desc_button button"
);
const tabContents = document.querySelectorAll(
    ".nobifashion_single_desc_tabs > div"
);

tabButtons.forEach((btn, i) => {
    btn.addEventListener("click", () => {
        tabButtons.forEach((b) =>
            b.classList.remove("nobifashion_single_desc_button_active")
        );
        tabContents.forEach((tab) =>
            tab.classList.remove("nobifashion_single_desc_tabs_active")
        );
        btn.classList.add("nobifashion_single_desc_button_active");
        tabContents[i].classList.add("nobifashion_single_desc_tabs_active");
    });
});
if (tabButtons[0]) {
    tabButtons[0]?.click();
}

function tabReview() {
    if (tabButtons[2]) {
        tabButtons[2]?.click();
    }
}

function tabSizeGuide() {
    if (tabButtons[1]) {
        tabButtons[1]?.click();
    }
}

// Click ảnh con => ảnh chính
const galleryImages = document.querySelectorAll(
    ".nobifashion_single_info_images_gallery_image"
);
galleryImages.forEach((img) => {
    img.addEventListener("click", () => {
        const newSrc = img.dataset.src || img.src;
        if (newSrc) {
            mainIMG?.setAttribute("src", newSrc);
            galleryImages.forEach((i) =>
                i.classList.remove(
                    "nobifashion_single_info_images_gallery_image_active"
                )
            );
            img.classList.add(
                "nobifashion_single_info_images_gallery_image_active"
            );
        }
    });
});

document
    .querySelectorAll(".nobifashion_single_info_voucher_code_item")
    ?.forEach((item) => {
        item.addEventListener("click", () => {
            navigator.clipboard
                .writeText(item.textContent.trim())
                .then(() =>
                    showCustomToast(
                        "Mã voucher đã được sao chép vào clipboard!",
                        "info"
                    )
                )
                .catch((error) => {
                    console.error("Error:", error);
                    showCustomToast(
                        "Có lỗi xảy ra khi sao chép mã voucher.",
                        "error"
                    );
                });
        });
    });

let maxStock = Math.max(...variants.map(v => parseInt(v.stock))) || 20;

function increaseQty() {
    // ❌ Nếu chưa chọn biến thể hợp lệ thì không cho tăng
    if (!hasSelection() || !findVariant()) {
        showCustomToast("Vui lòng chọn đủ biến thể trước khi chọn số lượng.", "warning");
        return;
    }
    const valueEl = document.querySelector(".nobifashion_single_info_specifications_actions_value");
    let current = parseInt(valueEl.textContent, 10) || 0;

    if (current < maxStock) {
        current++;
        valueEl.textContent = current;
        document.querySelector("input[name='quantity']").value = current;
    } else {
        showCustomToast(`Số lượng tối đa trong kho là ${maxStock}`, "warning");
    }
}

function decreaseQty() {
    // ❌ Nếu chưa chọn biến thể hợp lệ thì không cho giảm
    if (!hasSelection() || !findVariant()) {
        showCustomToast("Vui lòng chọn đủ biến thể trước khi chọn số lượng.", "warning");
        return;
    }
    const valueEl = document.querySelector(".nobifashion_single_info_specifications_actions_value");
    let current = parseInt(valueEl.textContent, 10) || 0;

    if (current > 1) {
        current--;
        valueEl.textContent = current;

        const input = document.querySelector("input[name='quantity']");
        if (input) input.value = current;
    } else {
        showCustomToast("Số lượng tối thiểu là 1", "warning");
    }
}

function countDownFlashSale(endTimestamp) {
    const daysEl = document.querySelector(
        ".nobifashion_single_info_specifications_box_days"
    );
    const hoursEl = document.querySelector(
        ".nobifashion_single_info_specifications_box_house"
    );
    const minutesEl = document.querySelector(
        ".nobifashion_single_info_specifications_box_minute"
    );
    const secondsEl = document.querySelector(
        ".nobifashion_single_info_specifications_box_second"
    );
    if (!daysEl || !hoursEl || !minutesEl || !secondsEl) return;

    const endTime = new Date(endTimestamp); // timestamp ms

    function updateCountdown() {
        const now = new Date();
        const distance = endTime.getTime() - now.getTime();

        if (distance <= 0) {
            // Hết hạn → reload 1 lần
            location.reload();
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor(
            (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
        );
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        updateBox(daysEl, days);
        updateBox(hoursEl, hours);
        updateBox(minutesEl, minutes);
        updateBox(secondsEl, seconds);
    }

    function updateBox(el, newValue) {
        const oldValue = el.textContent;
        const formatted = newValue.toString().padStart(2, "0");

        if (oldValue !== formatted) {
            el.textContent = formatted;
            el.classList.remove("animate");
            void el.offsetWidth; // trigger reflow
            el.classList.add("animate");
        }
    }

    // ✅ chạy ngay khi load
    updateCountdown();

    // Sau đó lặp lại mỗi giây
    setInterval(updateCountdown, 1000);
}


if(typeof endTime  !== "undefined") {
    // Truyền timestamp ms
    countDownFlashSale(endTime);
}

function showPopupVoucher() {
    const popup = document.querySelector(
        ".nobifashion_main_show_popup_voucher_overlay"
    );
    const closeBtn = document.querySelector(
        ".nobifashion_main_show_popup_voucher_close"
    );
    const codeEl = document.querySelectorAll(
        ".nobifashion_main_show_popup_voucher_code"
    );

    // // Hiện popup sau 10 giây
    // setTimeout(() => {

    // }, 10000);
    popup.style.display = "flex";

    // Đóng popup
    closeBtn.addEventListener("click", () => {
        popup.style.display = "none";
    });

    // Click ra ngoài để đóng
    popup.addEventListener("click", (e) => {
        if (e.target === popup) {
            popup.style.display = "none";
        }
    });

    // Copy voucher code khi click
    codeEl.forEach((el) => {
        el.addEventListener("click", () => {
            if (el.dataset.copied === "true") return; // nếu voucher này đã copy rồi thì bỏ qua

            const originalText = el.textContent.trim();

            navigator.clipboard
                .writeText(originalText)
                .then(() => {
                    showCustomToast("Mã voucher đã được sao chép!", "info");
                    el.textContent = "Đã sao chép!";
                    el.dataset.copied = "true"; // đánh dấu riêng cho voucher này

                    // Reset lại sau 2 giây
                    setTimeout(() => {
                        el.textContent = originalText;
                        el.dataset.copied = "false";
                    }, 5000);
                })
                .catch((err) => {
                    console.error("Copy thất bại: ", err);
                });
        });
    });
}

setTimeout(() => {
    showPopupVoucher();
}, 10000);



let selectedAttrs = {};
let hasInteracted = false;

function hasSelection() {
    return Object.keys(selectedAttrs).length > 0;
}

function findVariant() {
    return variants.find(v =>
        Object.entries(selectedAttrs).every(([k, vval]) => v.attrs[k] === vval)
    );
}

function refreshButtons() {
    document.querySelectorAll("[data-attr-key]").forEach(btn => {
        const key = btn.dataset.attrKey;
        const val = btn.dataset.attrValue;

        let temp = { ...selectedAttrs, [key]: val };

        let exists = variants.some(v =>
            Object.entries(temp).every(([k, vval]) => v.attrs[k] === vval)
        );

        btn.disabled = !exists;
        btn.classList.toggle("disabled", !exists);
    });
}

function toggleActionButtons() {
    const cartBtn = document.querySelector(".nobifashion_single_info_specifications_actions_cart");
    const buyBtn  = document.querySelector(".nobifashion_single_info_specifications_actions_buy");
    const form    = document.querySelector(".nobifashion_single_info_specifications_actions"); // form thật

    if (!cartBtn || !buyBtn || !form) return;

    const variant = findVariant();

    if (!hasSelection() || !variant) {
        setDisabled([cartBtn, buyBtn], true);
        removeVariantInput(form);
        return;
    }

    if (variant.stock > 0) {
        setDisabled([cartBtn, buyBtn], false);
        createOrUpdateVariantInput(form, variant);
    } else {
        setDisabled([cartBtn, buyBtn], true);
        removeVariantInput(form);
    }
}

function setDisabled(btns, disabled) {
    btns.forEach(btn => {
        btn.disabled = disabled;
        btn.classList.toggle("disabled", disabled);
    });
}

function createOrUpdateVariantInput(form, variant) { 
    let input = form.querySelector("input[name='variant_id']");
    if (!input) { input = document.createElement("input"); 
        input.type = "hidden"; 
        input.name = "variant_id"; 
        form.appendChild(input); 
    } 
    input.value = variant.id; 
} 

function removeVariantInput(form) {
    const input = form.querySelector("input[name='variant_id']");
    if (input) { 
        input.remove(); 
    } 
}

function getMinPriceVariant() {
    if (!variants || variants.length === 0) return null;
    return variants.reduce((min, v) =>
        parseFloat(v.price) < parseFloat(min.price) ? v : min
    );
}


function updateInfo() {
    const variant = findVariant();
    const minPriceVariant = getMinPriceVariant();
    let valueEl = document.querySelector(
        ".nobifashion_single_info_specifications_actions_value"
    );

    // cập nhật label text của từng thuộc tính
    for (const key of Object.keys(selectedAttrs)) {
        const el = document.querySelector(`#selected-${key}`);
        if (el) {
            el.textContent = selectedAttrs[key] || "-";
        }
    }

    let priceEl = document.querySelector(".nobifashion_single_info_specifications_price");
    let stockEl = document.querySelector("#product-stock");

    if (hasSelection() && variant) {
        let form = document.querySelector('.nobifashion_single_info_specifications_actions');
        
        // hiển thị giá của variant (ưu tiên giá variant, fallback minPriceVariant)
        let displayPrice = parseInt(variant.price) ?? parseInt(minPriceVariant?.price);
        priceEl.textContent = 
            typeof displayPrice === "number"
                ? new Intl.NumberFormat("vi-VN").format(displayPrice) + "đ"
                : "-";

        // hiển thị tồn kho
        stockEl.innerHTML =
            variant.stock > 0
                ? `Còn <span style="color: green;">${variant.stock}</span> sản phẩm`
                : `<span style="color:red;">Hết hàng</span>`;
        maxStock = variant.stock;

        const mainImage = document.querySelector('.nobifashion_single_info_images_main_image');
        if (mainImage && variant.image_url) {
            // variant.image_url đã là tên file (ví dụ: image.jpg)
            // Cần thêm đường dẫn đầy đủ
            const imagePath = `/clients/assets/img/clothes/${variant.image_url}`;
            mainImage.src = imagePath;
            
            
            // Cập nhật data-src cho gallery images nếu cần
            const galleryImages = document.querySelectorAll('.nobifashion_single_info_images_gallery_image');
            galleryImages.forEach(img => {
                if (img.src.includes(variant.image_url) || img.dataset.src?.includes(variant.image_url)) {
                    img.classList.add('nobifashion_single_info_images_gallery_image_active');
                } else {
                    img.classList.remove('nobifashion_single_info_images_gallery_image_active');
                }
            });
        } else if (mainImage && !variant.image_url) {
            // Nếu variant không có ảnh, giữ nguyên ảnh product hoặc dùng ảnh mặc định
            const defaultImage = mainImage.dataset.defaultSrc || '/clients/assets/img/clothes/no-image.webp';
            mainImage.src = defaultImage;
        }

        // xử lý input quantity
        let qtyInput = form.querySelector('input[name="quantity"]');
        let currentQty = parseInt(qtyInput?.value || 1, 10);

        if (!qtyInput) {
            qtyInput = document.createElement('input');
            qtyInput.type = "hidden";
            qtyInput.name = "quantity";
            form.appendChild(qtyInput);
        }
        qtyInput.value = currentQty;
    } else {
        // chưa chọn hoặc chọn sai biến thể
        priceEl.textContent = "-";
        stockEl.innerHTML = "<span style='color:red;'>Chưa chọn đủ biến thể</span>";
    }

    refreshButtons();
    toggleActionButtons();
}


// Click chọn attribute
document.querySelectorAll("[data-attr-key]").forEach(btn => {
    btn.addEventListener("click", () => {
        if (btn.classList.contains("disabled")) return;

        const key = btn.dataset.attrKey;
        const value = btn.dataset.attrValue;

        if (btn.classList.contains("active")) {
            btn.classList.remove("active");
            delete selectedAttrs[key];
        } else {
            document.querySelectorAll(`[data-attr-key="${key}"]`).forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            selectedAttrs[key] = value;
        }

        hasInteracted = true;
        updateInfo();
    });
});

// Submit (Add to cart / Buy now)
[".nobifashion_single_info_specifications_actions_cart", 
 ".nobifashion_single_info_specifications_actions_buy"].forEach(selector => {
    const btn = document.querySelector(selector);
    if (!btn) return;

    btn.addEventListener("click", e => {
        const variant = findVariant();

        if (!hasSelection() || !variant) {
            e.preventDefault();
            showCustomToast("Vui lòng chọn đầy đủ các thuộc tính sản phẩm.", "warning");
            return;
        }

        if (variant.stock <= 0) {
            e.preventDefault();
            showCustomToast("❌ Biến thể này đã hết hàng.", "error");
            return;
        }

        // ✅ Gửi request hợp lệ
        showCustomToast("Đang gửi request...", "success");
    });
});

// init
refreshButtons();
