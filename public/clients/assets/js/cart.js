document.addEventListener("DOMContentLoaded", () => {
    // ================== XSS PROTECTION ==================
    const sanitizeInput = (input) => {
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
    };

    const formatCurrency = (value) =>
        new Intl.NumberFormat("vi-VN", {
            style: "currency",
            currency: "VND",
            maximumFractionDigits: 0,
        }).format(value);

    const debounceTimers = new Map();

    const updateOrder = async (data) => {
        const subTotal = document.querySelector('.shop_haiphonglife_cart_summary_row_subtotal');
        // const tax = document.querySelector('.shop_haiphonglife_cart_summary_row_tax');
        const amount = document.querySelector('.shop_haiphonglife_cart_summary_amount');

        subTotal.textContent = formatCurrency(data);
        // tax.textContent = formatCurrency(data * 0.05);
        amount.textContent = formatCurrency(data);
    }

    const updateCartItem = (row, price, quantity, totalPrice) => {
        const cartItemId = row.dataset.cartItemId;

        // Nếu có timeout trước đó, hủy đi
        if (debounceTimers.has(cartItemId)) {
            clearTimeout(debounceTimers.get(cartItemId));
        }

        // Tạo timeout mới
        
        const timeout = setTimeout(async () => {
            try {
                const res = await fetch(`/api/cart/update-quantity/${cartItemId}`, {
                    method: "POST",
                    credentials: "include",
                    cache: "no-cache",
                    headers: {
                        "Accept": "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        "X-CART-TOKEN": sessionToken,
                    },
                    body: JSON.stringify({ cartItemId, price, quantity, totalPrice }),
                });

                const data = await res.json();
                
                updateOrder(data.cart.total_price)
                showCustomToast(data.message || (data.status ? "Cập nhật giỏ hàng thành công" : "Cập nhật giỏ hàng thất bại"), data.status ? "success" : "error");
            } catch (err) {
                showCustomToast("Lỗi khi cập nhật giỏ hàng", "error");
                console.error(err);
            }
        }, 2000);

        debounceTimers.set(cartItemId, timeout);
    };

    const updateLineTotal = (row) => {
        const input = row.querySelector(".shop_haiphonglife_cart_item_quantity_input");
        const priceText = row.querySelector(".shop_haiphonglife_cart_item_price")?.textContent?.replace(/[^\d]/g, "");
        const totalCell = row.querySelector(".shop_haiphonglife_cart_item_total");

        const price = parseFloat(priceText) || 0;
        const quantity = parseInt(input.value) || 1;
        const total = price * quantity;

        totalCell.textContent = formatCurrency(total);
        updateCartItem(row, price, quantity, total);
    };

    const rows = document.querySelectorAll(".shop_haiphonglife_cart_item");

    rows.forEach((row) => {
        const input = row.querySelector(".shop_haiphonglife_cart_item_quantity_input");
        const btnIncrease = row.querySelector(".shop_haiphonglife_cart_item_quantity_increase");
        const btnDecrease = row.querySelector(".shop_haiphonglife_cart_item_quantity_decrease");
        const stockNotice = row.querySelector(".shop_haiphonglife_cart_item_stock_notice");
        const max = parseInt(input.dataset.maxQuantity) || 100;

        const updateStockLeft = (qty) => {
            const remaining = max - qty;
            if (stockNotice) {
                stockNotice.textContent = remaining;
                stockNotice.style.color = remaining <= 2 ? "red" : "";
            }
        };

        const onQuantityChange = () => {
            showOverlayMain(2000);
            let value = parseInt(input.value);
            if (isNaN(value) || value < 1) value = 1;
            if (value > max) {
                showCustomToast("Số lượng tối đa đã đạt", "error");
                value = max;
            }
            input.value = value;
            updateStockLeft(value);
            updateLineTotal(row);
        };

        btnIncrease?.addEventListener("click", () => {
            let val = parseInt(input.value) || 1;
            if (val >= max) {
                showCustomToast("Số lượng tối đa đã đạt", "error");
                return;
            }
            input.value = val + 1;
            onQuantityChange();
        });

        btnDecrease?.addEventListener("click", () => {
            let val = parseInt(input.value) || 1;
            if (val > 1) {
                input.value = val - 1;
                onQuantityChange();
            } else {
                showCustomToast("Số lượng phải lớn hơn 0", "error");
            }
        });

        input?.addEventListener("change", onQuantityChange);

        // Init ban đầu
        updateStockLeft(parseInt(input.value) || 1);
        // updateLineTotal(row);
    });
});
