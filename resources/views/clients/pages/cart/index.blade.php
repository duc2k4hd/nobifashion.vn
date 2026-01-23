@extends('clients.layouts.master')

@section('title',
    $account?->name
        ? 'Giỏ hàng của ' . $account->name
        : 'Giỏ hàng - ' . renderMeta(data_get($settings ?? [], 'site_name', data_get($settings ?? [], 'subname', 'Bạn'))))

@section('head')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/cart.css') }}">
    <meta name="robots" content="follow, noindex"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('foot')
    <script src="{{ asset('clients/assets/js/cart.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Quantity update functionality
            const quantityInputs = document.querySelectorAll('.nobifashion_cart_item_quantity_input');
            const increaseBtns = document.querySelectorAll('.nobifashion_cart_item_quantity_increase');
            const decreaseBtns = document.querySelectorAll('.nobifashion_cart_item_quantity_decrease');

            function updateQuantity(itemId, newQuantity) {
                if (typeof toggleFormOverlay === "function") toggleFormOverlay(true);
                
                fetch('{{ route("client.cart.update.quantity") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        item_id: itemId,
                        quantity: newQuantity
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update item total
                        const itemRow = document.querySelector(`[data-cart-item-id="${itemId}"]`);
                        if (itemRow) {
                            const totalCell = itemRow.querySelector('.nobifashion_cart_item_total');
                            if (totalCell) {
                                totalCell.textContent = data.item_total + '₫';
                            }
                        }
                        
                        // Update cart total
                        const cartTotalElement = document.querySelector('.nobifashion_cart_summary_amount');
                        if (cartTotalElement) {
                            cartTotalElement.textContent = data.cart_total + ' đ';
                            // Cập nhật data-amount attribute
                            const amount = parseFloat(data.cart_total.replace(/[^\d]/g, '')) || 0;
                            cartTotalElement.setAttribute('data-amount', amount);
                        }
                        
                        // Update subtotal (Tổng phụ)
                        const subtotalElement = document.querySelector('.nobifashion_cart_summary_row_subtotal');
                        if (subtotalElement) {
                            subtotalElement.textContent = data.cart_total + ' đ';
                        }
                        
                        // Update số lượng sản phẩm trong "Tổng phụ (X sản phẩm)"
                        const subtotalLabel = document.querySelector('.nobifashion_cart_summary_subtotal_label');
                        if (subtotalLabel && data.cart_quantity !== undefined) {
                            subtotalLabel.textContent = `Tổng phụ (${data.cart_quantity} sản phẩm)`;
                        }
                        
                        // Update stock display
                        updateStockDisplay(itemId, newQuantity);
                        
                        // Show success message
                        showNotification(data.message, 'success');
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Có lỗi xảy ra khi cập nhật số lượng', 'error');
                })
                .finally(() => {
                    if (typeof toggleFormOverlay === "function") toggleFormOverlay(false);
                });
            }

            // Increase quantity
            increaseBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    if (typeof toggleFormOverlay === "function") toggleFormOverlay(true);
                    
                    const itemId = this.getAttribute('data-item-id');
                    const input = document.querySelector(`input[data-item-id="${itemId}"]`);
                    const maxQuantity = parseInt(input.getAttribute('data-max-quantity'));
                    const currentQuantity = parseInt(input.value);
                    
                    if (currentQuantity < maxQuantity) {
                        const newQuantity = currentQuantity + 1;
                        input.value = newQuantity;
                        updateQuantity(itemId, newQuantity);
                    } else {
                        if (typeof toggleFormOverlay === "function") toggleFormOverlay(false);
                        showNotification('Số lượng không thể vượt quá tồn kho', 'warning');
                    }
                });
            });

            // Decrease quantity
            decreaseBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    if (typeof toggleFormOverlay === "function") toggleFormOverlay(true);
                    
                    const itemId = this.getAttribute('data-item-id');
                    const input = document.querySelector(`input[data-item-id="${itemId}"]`);
                    const currentQuantity = parseInt(input.value);
                    
                    if (currentQuantity > 1) {
                        const newQuantity = currentQuantity - 1;
                        input.value = newQuantity;
                        updateQuantity(itemId, newQuantity);
                    } else {
                        if (typeof toggleFormOverlay === "function") toggleFormOverlay(false);
                    }
                });
            });

            // Direct input change
            quantityInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (typeof toggleFormOverlay === "function") toggleFormOverlay(true);
                    
                    const itemId = this.getAttribute('data-item-id');
                    const newQuantity = parseInt(this.value);
                    const maxQuantity = parseInt(this.getAttribute('data-max-quantity'));
                    
                    if (newQuantity < 1) {
                        this.value = 1;
                        updateQuantity(itemId, 1);
                    } else if (newQuantity > maxQuantity) {
                        this.value = maxQuantity;
                        if (typeof toggleFormOverlay === "function") toggleFormOverlay(false);
                        showNotification('Số lượng không thể vượt quá tồn kho', 'warning');
                    } else {
                        updateQuantity(itemId, newQuantity);
                    }
                });
            });

            // Notification function
            function showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                notification.className = `notification notification-${type}`;
                notification.textContent = message;
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 20px;
                    border-radius: 5px;
                    color: white;
                    font-weight: bold;
                    z-index: 9999;
                    max-width: 300px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                `;
                
                if (type === 'success') {
                    notification.style.backgroundColor = '#28a745';
                } else if (type === 'error') {
                    notification.style.backgroundColor = '#dc3545';
                } else if (type === 'warning') {
                    notification.style.backgroundColor = '#ffc107';
                    notification.style.color = '#000';
                } else {
                    notification.style.backgroundColor = '#17a2b8';
                }
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }

            // Update stock display function
            function updateStockDisplay(itemId, newQuantity) {
                const itemRow = document.querySelector(`[data-cart-item-id="${itemId}"]`);
                const stockElement = itemRow.querySelector('.nobifashion_cart_item_stock_notice');
                const stockContainer = itemRow.querySelector('.spec-stock');
                
                if (stockElement && stockContainer) {
                    const input = itemRow.querySelector(`input[data-item-id="${itemId}"]`);
                    const maxQuantity = parseInt(input.getAttribute('data-max-quantity'));
                    const remainingStock = maxQuantity - newQuantity;
                    
                    if (remainingStock <= 0) {
                        stockContainer.innerHTML = '<span style="color: red; font-size: 12px;">(Hết hàng trong kho)</span>';
                        stockContainer.className = 'spec-stock out-of-stock';
                    } else {
                        stockContainer.innerHTML = `(Tồn kho ${maxQuantity} - Còn <span style="font-size: 13px;" class="nobifashion_cart_item_stock_notice">${remainingStock}</span> sản phẩm)`;
                        stockContainer.className = 'spec-stock in-stock';
                    }
                }
            }
        });
    </script>
@endsection

@section('content')
    @if ((isset($settings) && (data_get($settings, 'enable_cart', 'true') === 'true')))
        <div id="cart" class="nobifashion_cart_container">
            <div class="nobifashion_cart_header">
                <h1 class="nobifashion_cart_title">Giỏ hàng</h1>
                <p style="font-size: 13px; color: red; font-style: italic">* Xem lại và kiểm tra các mặt hàng của bạn</p>
            </div>

            @if (isset($cart) && isset($cart->cartItems) && $cart->cartItems->count() > 0)
                @php
                    $computedSubtotal = 0;
                @endphp
                <div class="nobifashion_cart_layout">
                    <div class="nobifashion_cart_items">
                        <table class="nobifashion_cart_table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Sản phẩm</th>
                                    <th>Đơn giá</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($cart && $cart->count() > 0)
                                    @foreach ($cart->cartItems as $item)
                                        @php
                                            $variant = $item->variant;
                                            $product = $item->product;
                                            // QUAN TRỌNG: Dùng giá từ cart item (đã được tính từ Flash Sale)
                                            // KHÔNG dùng giá từ variant/product vì có thể đã có Flash Sale
                                            $unitPrice = (float) $item->price; // Giá đã lưu trong cart item (có thể là Flash Sale price)
                                            $line = (float) $item->total_price; // Thành tiền đã tính sẵn
                                            $computedSubtotal += $line;
                                            
                                            // Stock từ variant hoặc product
                                            $availableStock = $variant ? (int) $variant->stock_quantity : (int) $product->stock_quantity;
                                            $currentQty = (int) ($item->quantity ?? 0);
                                            $remainingStock = max($availableStock - $currentQty, 0);
                                            
                                            // Kiểm tra có Flash Sale không để hiển thị badge
                                            $isFlashSale = (bool) $item->is_flash_sale;
                                        @endphp
                                        <tr data-cart-item-id="{{ $item->id }}" class="nobifashion_cart_item">
                                            <!-- Xóa -->
                                            <td class="nobifashion_cart_item_remove" style="text-align: center;">
                                                <form action="{{ route('client.cart.remove.item', $item->id) }}"
                                                    method="post">
                                                    @csrf
                                                    <button
                                                        onclick="return confirm('Bạn có chắc chắn muốn xóa {{ $product->name ?? '' }}?')"
                                                        class="nobifashion_cart_item_remove_btn"
                                                        aria-label="Xóa sản phẩm">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                            <path
                                                                d="M22 5a1 1 0 0 1-1 1H3a1 1 0 0 1 0-2h5V3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v1h5a1 1 0 0 1 1 1zM4.934 21.071 4 8h16l-.934 13.071a1 1 0 0 1-1 .929H5.931a1 1 0 0 1-.997-.929zM15 18a1 1 0 0 0 2 0v-6a1 1 0 0 0-2 0zm-4 0a1 1 0 0 0 2 0v-6a1 1 0 0 0-2 0zm-4 0a1 1 0 0 0 2 0v-6a1 1 0 0 0-2 0z" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </td>

                                            <!-- Sản phẩm -->
                                            <td class="nobifashion_cart_item_product">
                                                <div class="nobifashion_cart_item_product_wrapper">
                                                    <img src="{{ asset('clients/assets/img/clothes/' . ($variant?->primaryVariantImage?->url ?? $product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                                        alt="Ảnh sản phẩm"
                                                        class="nobifashion_cart_item_product_image" />
                                                    <div class="nobifashion_cart_item_product_info">
                                                        <p class="nobifashion_cart_item_product_name">
                                                            <strong>{{ $product->name ?? '' }}</strong>
                                                        </p>
                                                        @if($variant)
                                                        @php
                                                            $rawAttrs = is_string($variant->attributes)
                                                                ? json_decode($variant->attributes, true)
                                                                : ($variant->attributes ?? []);
                                                            $normalized = \App\Models\ProductVariant::normalizeAttributesArray((array) $rawAttrs);
                                                            $displayAttrs = [];
                                                            foreach ($normalized as $attrKey => $attrVal) {
                                                                if ($attrVal === null || $attrVal === '') {
                                                                    continue;
                                                                }
                                                                $label = ucfirst(str_replace('_', ' ', $attrKey));
                                                                $displayAttrs[] = $label . ': ' . $attrVal;
                                                            }
                                                        @endphp
                                                        <p class="nobifashion_cart_item_product_variant">
                                                            <span class="nobifashion_cart_item_specifications">
                                                                @foreach($displayAttrs as $i => $text)
                                                                    <span class="spec-attr">{{ $text }}</span>
                                                                    @if($i < count($displayAttrs) - 1)
                                                                        <span class="spec-separator"> - </span>
                                                                    @endif
                                                                @endforeach
                                                                <span class="spec-stock {{ $remainingStock <= 0 ? 'out-of-stock' : 'in-stock' }}">
                                                                    {!! $remainingStock <= 0
                                                                        ? '<span style="color: red; font-size: 12px;">(Hết hàng trong kho)</span>'
                                                                        : '(Tồn kho ' . $availableStock . ' - Còn <span style="font-size: 13px;" class="nobifashion_cart_item_stock_notice">' . $remainingStock . '</span> sản phẩm)' !!}
                                                                </span>
                                                            </span>
                                                        </p>
                                                        @else
                                                        <p class="nobifashion_cart_item_product_variant">
                                                            <span class="nobifashion_cart_item_specifications">
                                                                <span class="spec-stock {{ $remainingStock <= 0 ? 'out-of-stock' : 'in-stock' }}">
                                                                    {!! $remainingStock <= 0
                                                                        ? '<span style="color: red; font-size: 12px;">(Hết hàng trong kho)</span>'
                                                                        : '(Tồn kho ' . $availableStock . ' - Còn <span style="font-size: 13px;" class="nobifashion_cart_item_stock_notice">' . $remainingStock . '</span> sản phẩm)' !!}
                                                                </span>
                                                            </span>
                                                        </p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Đơn giá -->
                                            <td class="nobifashion_cart_item_price" style="text-align: center;">
                                                <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                                                    @if($isFlashSale)
                                                        <span style="color: #dc3545; font-weight: bold; font-size: 14px;">
                                                            {{ number_format($unitPrice, 0, ',', '.') }}₫
                                                        </span>
                                                        <span style="font-size: 11px; color: #dc3545; background: #ffe6e6; padding: 2px 6px; border-radius: 3px;">
                                                            🔥 Flash Sale
                                                        </span>
                                                    @else
                                                        <span>{{ number_format($unitPrice, 0, ',', '.') }}₫</span>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- Số lượng -->
                                            <td class="nobifashion_cart_item_quantity" style="text-align: center;">
                                                <div class="nobifashion_cart_item_quantity_wrapper">
                                                    <button type="button" class="nobifashion_cart_item_quantity_decrease" data-item-id="{{ $item->id }}">-</button>
                                                    <input data-max-quantity="{{ $availableStock }}"
                                                        type="number" class="nobifashion_cart_item_quantity_input"
                                                        value="{{ $item->quantity }}" min="1"
                                                        max="{{ $availableStock }}" 
                                                        data-item-id="{{ $item->id }}" />
                                                    <button type="button" class="nobifashion_cart_item_quantity_increase" data-item-id="{{ $item->id }}">+</button>
                                                </div>
                                            </td>

                                            <!-- Thành tiền -->
                                            <td class="nobifashion_cart_item_total" style="text-align: center;">
                                                {{ number_format($line, 0, ',', '.') }}₫
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                        <div style="width: 100%; text-align: right;"><em
                                style="font-size: 13px; color: red; text-align: right;">*
                                Lưu ý nhỏ: Khi tăng giảm số lượng vui lòng đợi 2s để giỏ hàng tự cập nhật!</em></div>
                        <div class="nobifashion_cart_actions">
                            <a href="{{ route('client.home.index') }}" class="nobifashion_cart_continue">Tiếp tục mua
                                sắm</a>
                            <button onclick="location.reload()" class="nobifashion_cart_update">Cập nhật giỏ hàng thủ
                                công</button>
                            <form class="nobifashion_cart_remove_form"
                                action="{{ route('client.cart.clear') }}" method="post">
                                @csrf
                                <input type="hidden" name="cart_id" value="{{ $cart->id }}">
                                <button
                                    onclick="return confirm('Bạn có chắc chắn muốn xóa tất cả sản phẩm trong giỏ hàng?')"
                                    class="nobifashion_cart_remove_all">
                                    Xóa tất cả
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="nobifashion_cart_summary">
                        <h3 class="nobifashion_cart_summary_title">Tóm tắt đơn hàng <p
                                style="font-size: 13px; color: red; text-align: start; font-style: italic;">* Chưa bao gồm
                                phí vận chuyển</p>
                        </h3>

                        <div class="nobifashion_cart_summary_row">
                            <span class="nobifashion_cart_summary_subtotal_label">Tổng phụ ({{ $cart->cartItems->count() ?? 0 }} sản phẩm)</span>
                            <span class="nobifashion_cart_summary_row_subtotal">
                                {{ number_format($computedSubtotal, 0, ',', '.') }} đ
                            </span>
                        </div>
                        {{-- <div class="nobifashion_cart_summary_row">
                        <span>Thuế VAT (5%)</span>
                        <span class="nobifashion_cart_summary_row_tax">{{ number_format(round($cart->total_price * 0.05), 0, ',', '.') }} đ</span>
                    </div> --}}
                        <div class="nobifashion_cart_summary_row">
                            <span class="nobifashion_cart_summary_total">Tổng tiền</span>
                            <span data-amount="{{ $computedSubtotal }}"
                                class="nobifashion_cart_summary_amount">{{ number_format($computedSubtotal, 0, ',', '.') }}
                                đ</span>
                        </div>
                        <button
                            onclick="if(confirm('Bạn có muốn tiếp tục thanh toán?')) { window.location.href = '{{ route('client.checkout.cart') }}'; }"
                            class="nobifashion_cart_checkout">
                            Thông tin giao hàng
                        </button>
                        <button
                            onclick="if(confirm('Thanh toán toàn bộ giỏ hàng?')) { window.location.href = '{{ route('client.checkout.cart') }}'; }"
                            class="nobifashion_cart_checkout" style="margin-top: 8px;">
                            Thanh toán toàn bộ giỏ hàng
                        </button>
                    </div>
                </div>
            @else
                <div class="nobifashion_no_cart">
                    <div class="nobifashion_no_cart_icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                            <defs>
                                <style>
                                    .cls-2 {
                                        fill: #f16d8f
                                    }

                                    .cls-3 {
                                        fill: #f280a0
                                    }

                                    .cls-4 {
                                        fill: #f89bae
                                    }

                                    .cls-6 {
                                        fill: #bcc0ff
                                    }

                                    .cls-7 {
                                        fill: #98d7de
                                    }

                                    .cls-8 {
                                        fill: #fac8fc
                                    }

                                    .cls-10 {
                                        fill: #81c8d9
                                    }

                                    .cls-11 {
                                        fill: #a499d6
                                    }

                                    .cls-13 {
                                        fill: #f6fafd
                                    }
                                </style>
                            </defs>
                            <g id="_16-cart" data-name="16-cart">
                                <path style="fill:#ffdddf" d="m1 21 2 16h32.864L39.5 21H1z" />
                                <path class="cls-2" d="M31 1a2 2 0 0 1 2 2v2h-4V3a2 2 0 0 1 2-2z" />
                                <path class="cls-3"
                                    d="M17.192 19.353c-.565.571-9.612 4-14.7-1.142a4.685 4.685 0 0 1 0-6.852c1.7-1.713 3.958-1.713 6.219-.571C7.58 8.5 7.58 6.22 9.277 4.506a4.579 4.579 0 0 1 6.785 0c5.088 5.14 2.261 13.705 1.13 14.847z" />
                                <path class="cls-4"
                                    d="M33 3s0-2 3-2h2a3.942 3.942 0 0 1-3.636 3.956C33.954 4.984 33.5 5 33 5zM29 3s0-2-3-2h-2a3.942 3.942 0 0 0 3.636 3.956C28.046 4.984 28.5 5 29 5z" />
                                <path style="fill:#ffbafe" d="M18 11h2v10h-4V11h2z" />
                                <path class="cls-6" d="M16 11v10h-5V11h5zM25 11v10h-5V11h5z" />
                                <path class="cls-7" d="M29 5v16h-4V5h4zM33 5h4v16h-4z" />
                                <path class="cls-3" d="M29 5h4v16h-4z" />
                                <circle class="cls-7" cx="7" cy="45" r="2" />
                                <circle class="cls-7" cx="33" cy="45" r="2" />
                                <path class="cls-8" d="M18 9c0-1.66-1.79-2-4-2l1 2-1 2h4" />
                                <path class="cls-8"
                                    d="M18.32 7.98C18.93 7.19 20.35 7 22 7l-1 2 1 2h-4V9a1.547 1.547 0 0 1 .32-1.01" />
                                <path style="fill:#fcf1ed" d="M35.864 37 39.5 21H6l16 16h13.864z" />
                                <path class="cls-10" d="M25 17h4v4h-4zM33 17h4v4h-4z" />
                                <path class="cls-2" d="M29 17h4v4h-4z" />
                                <path class="cls-11" d="M11 17h5v4h-5z" />
                                <path style="fill:#faaafe" d="M16 17h4v4h-4z" />
                                <path class="cls-3"
                                    d="M22.5 24c1.75 0 3.5 1 3.5 3 0 4.5-5.83 7-7 7-.58 0-7-2.5-7-7 0-2 1.75-3 3.5-3a3.6 3.6 0 0 1 3.5 2.5 3.6 3.6 0 0 1 3.5-2.5z" />
                                <path class="cls-2"
                                    d="M15 27a2.824 2.824 0 0 1 1.963-2.713A3.763 3.763 0 0 0 15.5 24c-1.75 0-3.5 1-3.5 3 0 4.5 6.42 7 7 7a5.072 5.072 0 0 0 1.625-.478C18.569 32.6 15 30.356 15 27z" />
                                <path class="cls-4"
                                    d="M22.5 24a3.491 3.491 0 0 0-2.577 1.03A3.058 3.058 0 0 1 23 28c0 2.8-2.252 4.82-4.238 5.952A1.167 1.167 0 0 0 19 34c1.17 0 7-2.5 7-7 0-2-1.75-3-3.5-3z" />
                                <ellipse class="cls-13" cx="24" cy="27" rx=".825" ry="1.148"
                                    transform="rotate(-45.02 24 27)" />
                                <ellipse class="cls-13" cx="23.746" cy="28.5" rx=".413" ry=".574"
                                    transform="rotate(-45.02 23.745 28.5)" />
                                <path class="cls-2"
                                    d="M11 20.957V11H9v9.959a15.161 15.161 0 0 0 2-.002zM14.085 7.169A10.771 10.771 0 0 0 12 7l1 2-1 2h2l1-2z" />
                                <path class="cls-13" d="M34 8h2v2h-2zM34 11h2v2h-2zM9 34h2v2H9zM6 34h2v2H6z" />
                                <path class="cls-11" d="M23 11v6h-3v4h5V11h-2z" />
                                <circle class="cls-10" cx="33" cy="45" r="1" />
                                <circle class="cls-10" cx="7" cy="45" r="1" />
                                <path
                                    d="M35 42H3v-2h31.2l7.823-35.217A1 1 0 0 1 43 4h4v2h-3.2l-7.823 35.217A1 1 0 0 1 35 42z"
                                    style="fill:#7d649c" />
                            </g>
                        </svg>
                    </div>
                    <h2 class="nobifashion_no_cart_title">Giỏ hàng của bạn đang trống</h2>
                    <p class="nobifashion_no_cart_text">Hãy khám phá sản phẩm của chúng tôi và thêm vào giỏ ngay nhé!
                    </p>
                    <a href="{{ route('client.home.index') }}" class="nobifashion_no_cart_button">Tiếp tục mua
                        sắm</a>
                </div>
                @include('clients.templates.product_new')
            @endif
            @include('clients.templates.loding_form')
        </div>
        {{-- @include('clients.templates.chat') --}}
    @else
        <div class="nobifashion_cart_disabled">
            <h1>Giỏ hàng hiện đang bị tắt</h1>
            <p>Vui lòng liên hệ quản trị viên để biết thêm chi tiết.</p>
        </div>
    @endif
@endsection
