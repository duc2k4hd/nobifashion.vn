document.addEventListener("click", async (e) => {
    // Bỏ qua nếu click vào menu mobile
    if (e.target.closest(".nobifashion_header_main_mobile_bars") || 
        e.target.closest(".nobifashion_header_mobile_main_nav")) {
        return;
    }
    
    const btn = e.target.closest(".nobifashion_fav_btn");
    if (!btn) return;
    e.preventDefault();
    const productId = btn.getAttribute("data-product-id");
    if (!productId) return;
    const outlineSvg =
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="#ff0000" d="M442.9 144C415.6 144 389.9 157.1 373.9 179.2L339.5 226.8C335 233 327.8 236.7 320.1 236.7C312.4 236.7 305.2 233 300.7 226.8L266.3 179.2C250.3 157.1 224.6 144 197.3 144C150.3 144 112.2 182.1 112.2 229.1C112.2 279 144.2 327.5 180.3 371.4C221.4 421.4 271.7 465.4 306.2 491.7C309.4 494.1 314.1 495.9 320.2 495.9C326.3 495.9 331 494.1 334.2 491.7C368.7 465.4 419 421.3 460.1 371.4C496.3 327.5 528.2 279 528.2 229.1C528.2 182.1 490.1 144 443.1 144zM335 151.1C360 116.5 400.2 96 442.9 96C516.4 96 576 155.6 576 229.1C576 297.7 533.1 358 496.9 401.9C452.8 455.5 399.6 502 363.1 529.8C350.8 539.2 335.6 543.9 320 543.9C304.4 543.9 289.2 539.2 276.9 529.8C240.4 502 187.2 455.5 143.1 402C106.9 358.1 64 297.7 64 229.1C64 155.6 123.6 96 197.1 96C239.8 96 280 116.5 305 151.1L320 171.8L335 151.1z"/></svg>';
    const filledSvg =
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="#ff0000" d="M305 151.1L320 171.8L335 151.1C360 116.5 400.2 96 442.9 96C516.4 96 576 155.6 576 229.1L576 231.7C576 343.9 436.1 474.2 363.1 529.9C350.7 539.3 335.5 544 320 544C304.5 544 289.2 539.4 276.9 529.9C203.9 474.2 64 343.9 64 231.7L64 229.1C64 155.6 123.6 96 197.1 96C239.8 96 280 116.5 305 151.1z"/></svg>';
    try {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfTokenValue = csrfMeta ? csrfMeta.getAttribute("content") : csrfToken;
        
        const res = await fetch(`/favorites/toggle/${productId}`, {
            method: "POST",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfTokenValue,
                Accept: "application/json",
            },
        });
        const data = await res.json();
        if (data.success) {
            const isAdded = data.action === "added";
            btn.classList.toggle("active", isAdded);
            // swap svg content for consistent look across pages
            btn.innerHTML = isAdded ? filledSvg : outlineSvg;
            try {
                if (typeof showCustomToast === "function")
                    showCustomToast(
                        isAdded
                            ? "Đã thêm vào yêu thích"
                            : "Đã xóa khỏi yêu thích",
                        isAdded ? "success" : "info"
                    );
            } catch (_) {}
            // Optionally update header wishlist counter if present
            const headerCount = document.querySelector(
                ".nobifashion_header_main_icon_wishlist_count"
            );
            if (headerCount) {
                let count = parseInt(headerCount.textContent || "0", 10);
                if (isAdded) count += 1;
                else count = Math.max(0, count - 1);
                headerCount.textContent = String(count);
            }
        }
    } catch (err) {
        console.error(err);
        try {
            if (typeof showCustomToast === "function")
                showCustomToast(
                    "Không thể cập nhật yêu thích. Vui lòng thử lại.",
                    "error"
                );
        } catch (_) {}
    }
});

const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute("content") : '';

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

function showCustomToast(
    message = "Thông báo!",
    type = "info",
    duration = 5000
) {
    const container = document.getElementById("custom-toast-container");
    if (!container) {
        console.warn('Toast container not found');
        return;
    }
    
    const toast = document.createElement("div");
    const icon = document.createElement("span");

    toast.className = `custom-toast ${type}`;
    icon.className = "custom-toast-icon";

    // Gán biểu tượng theo loại
    const icons = {
        success: "✅",
        error: "❌",
        warning: "⚠️",
        info: "💬",
    };
    icon.textContent = icons[type] || "🔔";

    toast.appendChild(icon);
    toast.appendChild(document.createTextNode(message));
    container.appendChild(toast);

    // Kích hoạt animation
    setTimeout(() => {
        if (toast && toast.classList) {
            toast.classList.add("show");
        }
    }, 100);

    toast.addEventListener("click", () => {
        if (toast && toast.classList) {
            toast.classList.remove("show");
        }
        setTimeout(() => {
            if (container && toast && container.contains(toast)) {
                container.removeChild(toast);
            }
        }, 300);
        return;
    });

    // Gỡ thông báo sau duration
    setTimeout(() => {
        if (toast && toast.classList) {
            toast.classList.remove("show");
        }
        setTimeout(() => {
            if (container && toast && container.contains(toast)) {
                container.removeChild(toast);
            }
        }, 300);
        return;
    }, duration);
}

async function showOverlayMain(ms) {
    const overlay = document.querySelector(".nobifashion_loading_overlay");
    if (!overlay) return;
    overlay.style.display = "flex";
    await sleep(ms);
    if (overlay) {
        overlay.style.display = "none";
    }
}

function parseVND(value) {
    if (typeof value !== "string") return 0;

    return parseInt(
        value.replace(/[^\d]/g, "") // Xoá mọi ký tự không phải số
    );
}

function formatCurrencyVND(amount) {
    if (isNaN(amount)) return 0;
    return Number(amount).toLocaleString("vi-VN");
}

function postAndRedirect(url, data = {}) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = url;

    // CSRF token nếu dùng Laravel web.php
    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = csrf.getAttribute("content") || csrfToken;
        form.appendChild(token);
    }

    // Đệ quy xử lý mảng/lồng object
    function appendFormData(key, value) {
        if (Array.isArray(value)) {
            value.forEach((v, i) => {
                for (const subKey in v) {
                    appendFormData(`${key}[${i}][${subKey}]`, v[subKey]);
                }
            });
        } else if (typeof value === "object") {
            for (const subKey in value) {
                appendFormData(`${key}[${subKey}]`, value[subKey]);
            }
        } else {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }
    }

    for (const key in data) {
        if (data.hasOwnProperty(key)) {
            appendFormData(key, data[key]);
        }
    }

    document.body.appendChild(form);
    form.submit();
}
// XỬ LÝ ĐỊNH VỊ VỊ TRÍ MENU
document
    .querySelectorAll(".nobifashion_header_main_nav_links_item_title")
    .forEach((item, index) => {
        if (!item) return;
        
        const itemList = document.querySelectorAll(
            ".nobifashion_header_main_nav_links_item_list"
        )[index];
        
        // Kiểm tra phần tử tồn tại trước khi truy cập style
        if (!itemList) {
            return;
        }
        
        try {
            const percent =
                (item.getBoundingClientRect().left / window.innerWidth) * 100;
            itemList.style.transform = `translateX(-${percent - 0.65}%)`;
        } catch (e) {
            console.warn('Error calculating menu position:', e);
        }
    });

// MENU CỐ ĐỊNH KHI CUỘN
const mainMenu = document.querySelector(".nobifashion_header_main_nav");
if (mainMenu) {
    window.addEventListener("scroll", () => {
        if (mainMenu && mainMenu.classList) {
            mainMenu.classList.toggle(
                "nobifashion_header_main_nav_fixed",
                window.scrollY > 240
            );
        }
    });
}

// Xử lý menu mobile - đảm bảo chạy sau khi DOM ready
function initMobileMenu() {
    const openMenuMobile = document.querySelector(
        ".nobifashion_header_main_mobile_bars"
    );
    const closeMenuMobile = document.querySelector(
        ".nobifashion_header_mobile_main_nav_close"
    );
    const menuMobile = document.querySelector(
        ".nobifashion_header_mobile_main_nav"
    );
    const overlay = document.querySelector(".nobifashion_header_mobile_overlay");

    // Kiểm tra phần tử tồn tại
    if (!openMenuMobile) {
        return;
    }
    if (!closeMenuMobile) {
        return;
    }
    if (!menuMobile) {
        return;
    }

    // open - sử dụng stopPropagation để tránh conflict
    openMenuMobile.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (menuMobile && menuMobile.classList) {
            menuMobile.classList.add("active");
        }
        if (overlay && overlay.classList) {
            overlay.classList.add("active");
        }
        // Ngăn scroll body khi menu mở
        if (document.body) {
            document.body.style.overflow = "hidden";
        }
    });

    // close
    closeMenuMobile.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (menuMobile && menuMobile.classList) {
            menuMobile.classList.remove("active");
        }
        if (overlay && overlay.classList) {
            overlay.classList.remove("active");
        }
        // Khôi phục scroll body
        if (document.body) {
            document.body.style.overflow = "";
        }
    });

    // Close khi click overlay
    if (overlay) {
        overlay.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (menuMobile && menuMobile.classList) {
                menuMobile.classList.remove("active");
            }
            if (overlay && overlay.classList) {
                overlay.classList.remove("active");
            }
            if (document.body) {
                document.body.style.overflow = "";
            }
        });
    }

    // Close khi click ra ngoài menu (nếu không có overlay)
    if (!overlay) {
        document.addEventListener("click", (e) => {
            if (menuMobile && menuMobile.classList && menuMobile.classList.contains("active")) {
                // Nếu click không phải vào menu hoặc button mở menu
                if (openMenuMobile && !menuMobile.contains(e.target) && !openMenuMobile.contains(e.target)) {
                    menuMobile.classList.remove("active");
                    if (document.body) {
                        document.body.style.overflow = "";
                    }
                }
            }
        });
    }
}

// Chạy khi DOM ready
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initMobileMenu);
} else {
    // DOM đã sẵn sàng
    initMobileMenu();
}

// submenu toggle
document
    .querySelectorAll(".nobifashion_header_mobile_main_nav_links_item_title")
    .forEach((title) => {
        if (!title) {
            return;
        }
        title.addEventListener("click", () => {
            const subMenu = title.nextElementSibling;
            const svg = title.querySelector("svg");

            if (!subMenu || !subMenu.classList) {
                return;
            }

            const isOpen = subMenu.classList.contains("show");

            if (isOpen) {
                subMenu.classList.remove("show");
                if (svg && svg.style) {
                    svg.style.transform = "rotate(0deg)";
                }
            } else {
                subMenu.classList.add("show");
                if (svg && svg.style) {
                    svg.style.transform = "rotate(180deg)";
                }
            }
        });
    });

const backToTopBtn = document.querySelector(".nobifashion_back_to_top");

if (backToTopBtn) {
    window.addEventListener("scroll", () => {
        if (window.scrollY > 300) {
            if (backToTopBtn) {
                backToTopBtn.style.display = "flex";
            }
            const orderSummary = document.querySelector(".nobifashion_order_summary");
            if (orderSummary && orderSummary.classList) {
                orderSummary.classList.add("shop_haiphonglife_order_summary_fixed");
            }
        } else {
            if (backToTopBtn) {
                backToTopBtn.style.display = "none";
            }
            const orderSummary = document.querySelector(".nobifashion_order_summary");
            if (orderSummary && orderSummary.classList) {
                orderSummary.classList.remove("shop_haiphonglife_order_summary_fixed");
            }
        }
    });

    backToTopBtn.addEventListener("click", () => {
        window.scrollTo({ top: 0, behavior: "smooth" });
    });
}

function toggleFormOverlay(show = true) {
    const overlay = document.querySelector(
        ".nobifashion_main_loading_form_overlay"
    );
    if (!overlay) return;
    if (show) overlay.removeAttribute("hidden");
    else overlay.setAttribute("hidden", "");
}

document.addEventListener('DOMContentLoaded', function() {
    // Ngăn form reload trang khi nhấn Enter
    document.querySelectorAll('.nobifashion_header_main_search_form').forEach(form => {
        form.addEventListener('submit', e => {
            e.preventDefault(); // ⛔ không cho submit
        });
    });

    const searchBtn = document.querySelector('.nobifashion_header_main_search_btn');
    if (searchBtn) {
        searchBtn.addEventListener('click', e => {
            e.preventDefault();
            const input = document.querySelector('.nobifashion_header_main_search_input');
            if (input) {
                const keyword = input.value.trim();
                if (keyword.length > 0) {
                    window.location.href = '/shop/search?keyword=' + encodeURIComponent(keyword);
                }
            }
        });
    }

    const inputs = document.querySelectorAll('.nobifashion_header_main_search_input, .nobifashion_header_mobile_main_nav_search_input');

    inputs.forEach(input => {
        // 🔹 Tạo vùng hiển thị gợi ý
        const suggestBox = document.createElement('div');
        suggestBox.className = 'nobifashion_search_suggestions';
        suggestBox.style.position = 'absolute';
        suggestBox.style.top = (input.offsetHeight + 5) + 'px';
        suggestBox.style.left = '0';
        suggestBox.style.right = '0';
        suggestBox.style.background = '#fff';
        suggestBox.style.border = '1px solid #ddd';
        suggestBox.style.zIndex = '9999';
        suggestBox.style.display = 'none';
        suggestBox.style.borderRadius = '8px';
        suggestBox.style.overflow = 'hidden';
        suggestBox.style.maxHeight = '300px';
        suggestBox.style.overflowY = 'auto';
        suggestBox.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
        if (input.parentNode) {
            input.parentNode.style.position = 'relative';
            input.parentNode.appendChild(suggestBox);
        }

        let timer;
        input.addEventListener('input', function() {
            clearTimeout(timer);
            const keyword = this.value.trim();
            if (keyword.length < 2) {
                suggestBox.style.display = 'none';
                return;
            }

            timer = setTimeout(async () => {
                try {
                    const res = await fetch('/api/search', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ keyword })
                    });
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const data = await res.json();

                    // 🔹 Xóa cũ
                    suggestBox.innerHTML = '';

                    if (data.length === 0) {
                        suggestBox.innerHTML = `<div style="padding: 10px; color: #666;">Không tìm thấy sản phẩm</div>`;
                        suggestBox.style.display = 'block';
                        return;
                    }

                    // 🔹 Render kết quả
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'nobifashion_search_suggestion_item';
                        div.style.padding = '10px 15px';
                        div.style.cursor = 'pointer';
                        div.style.transition = 'background 0.2s';
                        div.innerHTML = `<span style="color:#333;">${item.name}</span>`;
                        div.addEventListener('mouseenter', () => div.style.background = '#f9f9f9');
                        div.addEventListener('mouseleave', () => div.style.background = '#fff');
                        div.addEventListener('click', () => {
                            window.location.href = '/san-pham/' + item.slug;
                        });
                        suggestBox.appendChild(div);
                    });

                    suggestBox.style.display = 'block';
                } catch (err) {
                    console.error('Search error:', err);
                }
            }, 400);
        });

        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (input) {
                    const keyword = input.value.trim();
                    if (keyword.length > 0) {
                        window.location.href = '/shop/search?keyword=' + encodeURIComponent(keyword);
                    }
                }
            }
        });

        // 🔹 Ẩn box khi click ra ngoài
        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !suggestBox.contains(e.target)) {
                suggestBox.style.display = 'none';
            }
        });
    });
});

// Mobile category selection function
function selectMobileCategory(categoryId) {
    // Remove active class from all sidebar items
    document.querySelectorAll('.nobifashion_mobile_categories_sidebar_item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Add active class to clicked item
    const clickedItem = document.querySelector(`[data-category-id="${categoryId}"]`);
    if (clickedItem) {
        clickedItem.classList.add('active');
    }
    
    // Hide all content lists
    document.querySelectorAll('.nobifashion_mobile_categories_content_list').forEach(list => {
        list.style.display = 'none';
    });
    
    // Show selected category content
    const selectedContent = document.querySelector(`[data-category-content="${categoryId}"]`);
    if (selectedContent) {
        selectedContent.style.display = 'block';
    }
}

// Image search modal function
function openImageSearchModal() {
    // Placeholder function - implement image search functionality as needed
    if (typeof showCustomToast === "function") {
        showCustomToast('Tính năng tìm kiếm bằng hình ảnh đang phát triển', 'info', 3000);
    } else {
        alert('Tính năng tìm kiếm bằng hình ảnh đang phát triển');
    }
}

// Toggle subcategory (danh mục cháu chắt)
function toggleSubCategory(toggleBtn) {
    const wrapper = toggleBtn.closest('.nobifashion_mobile_categories_content_item_wrapper');
    if (wrapper) {
        wrapper.classList.toggle('expanded');
    }
}