@extends('clients.layouts.master')

@section('title', 'Thông tin đặt hàng - ' . renderMeta($settings->site_name ?? ($settings->subname ?? 'NOBI FASHION')))

@section('head')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('clients/assets/css/checkout.css') }}">
    <meta name="robots" content="follow, noindex"/>
@endsection

@section('foot')
    <script src="https://unpkg.com/slim-select@latest/dist/slimselect.min.js"></script>
    <link href="https://unpkg.com/slim-select@latest/dist/slimselect.css" rel="stylesheet">
    </link>
    <script src="{{ asset('clients/assets/js/fallback-select.js') }}"></script>
    <script defer src="{{ asset('clients/assets/js/order.js') }}"></script>
    
    <!-- Debug button for loading overlay -->
    <script>
        // Add debug button if loading overlay is stuck
        setTimeout(() => {
            const overlay = document.querySelector('.nobifashion_main_loading_form_overlay');
            if (overlay && !overlay.hasAttribute('hidden')) {
                console.warn('Loading overlay is still visible after 5 seconds');
                // Create debug button
                const debugBtn = document.createElement('button');
                debugBtn.innerHTML = 'Ẩn Loading (Debug)';
                debugBtn.style.cssText = 'position:fixed;top:10px;right:10px;z-index:9999;background:red;color:white;border:none;padding:10px;border-radius:5px;cursor:pointer;';
                debugBtn.onclick = () => {
                    if (typeof forceHideLoading === 'function') {
                        forceHideLoading();
                    } else {
                        overlay.setAttribute('hidden', '');
                    }
                    debugBtn.remove();
                };
                document.body.appendChild(debugBtn);
            }
        }, 5000);
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const autofillFields = document.querySelectorAll('.nobifashion_no_autofill');
            autofillFields.forEach(field => {
                field.setAttribute('readonly', 'readonly');
                field.setAttribute('autocapitalize', 'none');
                field.setAttribute('autocorrect', 'off');
                field.setAttribute('spellcheck', 'false');
                field.dataset.preventAutofill = 'true';

                const enableTyping = () => {
                    if (field.hasAttribute('readonly')) {
                        setTimeout(() => field.removeAttribute('readonly'), 60);
                    }
                };

                const disableTyping = () => {
                    if (!field.hasAttribute('readonly')) {
                        field.setAttribute('readonly', 'readonly');
                    }
                };

                field.addEventListener('focus', enableTyping, { passive: true });
                field.addEventListener('mousedown', enableTyping, { passive: true });
                field.addEventListener('touchstart', enableTyping, { passive: true });
                field.addEventListener('blur', disableTyping, { passive: true });
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const buttons = document.querySelectorAll('[data-address-json]');
            if (!buttons.length) return;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const setSelectValue = (selector, value) => {
                const el = document.querySelector(selector);
                if (el && value) {
                    el.value = value;
                    el.dispatchEvent(new Event('change'));
                }
            };

            const fillAddressForm = (address) => {
                const fullname = document.querySelector('input[name="fullname"]');
                const phone = document.querySelector('input[name="phone"]');
                const detail = document.querySelector('input[name="address"]');
                if (fullname) fullname.value = address.full_name || fullname.value;
                if (phone) phone.value = address.phone_number || phone.value;
                if (detail) detail.value = address.detail_address || detail.value;

                setSelectValue('select[name="provinceId"]', address.province_code);
                setTimeout(() => setSelectValue('select[name="districtId"]', address.district_code), 500);
                setTimeout(() => setSelectValue('select[name="wardId"]', address.ward_code), 900);
            };

            const notify = (message, type = 'success') => {
                const container = document.createElement('div');
                container.className = `alert alert-${type}`;
                container.textContent = message;
                container.style.position = 'fixed';
                container.style.top = '15px';
                container.style.right = '15px';
                container.style.zIndex = '9999';
                document.body.appendChild(container);
                setTimeout(() => container.remove(), 2500);
            };

            buttons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const address = JSON.parse(btn.dataset.addressJson);
                    fillAddressForm(address);
                    const modalEl = document.getElementById('addressBookModal');
                    if (modalEl && window.bootstrap) {
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        modalInstance?.hide();
                    }

                    if (csrf) {
                        fetch('{{ route('client.checkout.address.select') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ address_id: address.id }),
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data?.success) {
                                notify('Đã áp dụng địa chỉ giao hàng.');
                            }
                        })
                        .catch(() => {});
                    }
                });
            });
        });
    </script>
@endsection

@section('content')
    @php
        $addresses = $addresses ?? collect();
        $defaultAddress = $defaultAddress ?? null;
    @endphp
    @if ($settings->enable_order == 'true')
        <div class="nobifashion_order_wrapper">
            <!-- Breadcrumb -->
            <section>
                <div class="nobifashion_order_breadcrumb">
                    <a href="{{ route('client.home.index') }}">Trang chủ</a>
                    <span class="separator">>></span>
                    <a href="{{ route('client.cart.index') }}">Giỏ hàng</a>
                    <span class="separator">>></span>
                    <span class="breadcrumb-current">Thông tin đặt hàng</span>
                </div>
            </section>

            <section class="nobifashion_main_checkout">
                <div class="nobifashion_main_checkout_container">
                    <!-- Cột trái -->
                    <form class="nobifashion_checkout_form_wrapper" action="{{ isset($cartItem) ? route('client.checkout.create.item') : route('client.checkout.create.cart') }}" method="POST" autocomplete="off">
                        @csrf
                        <div class="nobifashion_main_checkout_left">
                            <!-- Billing Info -->
                            <div class="nobifashion_main_checkout_box nobifashion_box_full">
                                <h2 class="nobifashion_main_checkout_title">
                                    Thông tin thanh toán
                                    @auth
                                        @if($addresses->isNotEmpty())
                                            <button type="button" class="btn btn-link btn-sm" data-bs-toggle="modal" data-bs-target="#addressBookModal">
                                                📚 Chọn từ sổ địa chỉ
                                            </button>
                                        @endif
                                    @endauth
                                </h2>
                                @auth
                                    @if($defaultAddress)
                                        <div class="alert alert-info d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>Địa chỉ mặc định:</strong> {{ $defaultAddress->full_name }} — {{ $defaultAddress->phone_number }}<br>
                                                {{ $defaultAddress->detail_address }}, {{ $defaultAddress->district }}, {{ $defaultAddress->province }}
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm" data-apply-address="{{ htmlspecialchars(json_encode($defaultAddress), ENT_QUOTES, 'UTF-8') }}">
                                                Áp dụng
                                            </button>
                                        </div>
                                    @endif
                                @endauth
                                <div class="nobifashion_main_checkout_form nobifashion_checkout_grid">
                                    <div class="nobifashion_checkout_field">
                                        <label>Họ và tên <span class="nobifashion_required">*</span></label>
                                        <input type="text" name="fullname" autocomplete="new-password" class="nobifashion_no_autofill"
                                            value="{{ htmlspecialchars(old('fullname', ''), ENT_QUOTES, 'UTF-8') }}" placeholder="Nguyễn Văn A" required />
                                    </div>

                                    <div class="nobifashion_checkout_field">
                                        <label>Email <small style="font-size: 10px;">(nhận thông báo)</small> <span class="nobifashion_required">*</span></label>
                                        <input type="email" name="email" autocomplete="new-password" class="nobifashion_no_autofill"
                                            value="{{ htmlspecialchars(old('email', ''), ENT_QUOTES, 'UTF-8') }}" placeholder="email@example.com" required />
                                    </div>

                                    <div class="nobifashion_checkout_field">
                                        <label>Số điện thoại <span class="nobifashion_required">*</span></label>
                                        <input type="tel" name="phone" autocomplete="new-password" class="nobifashion_no_autofill"
                                            value="{{ htmlspecialchars(old('phone', ''), ENT_QUOTES, 'UTF-8') }}" placeholder="090xxxxxxx" required />
                                    </div>

                                    <div class="nobifashion_checkout_field nobifashion_checkout_field--full">
                                        <label>Địa chỉ chi tiết <span class="nobifashion_required">*</span></label>
                                        <input value="{{ htmlspecialchars(old('address', ''), ENT_QUOTES, 'UTF-8') }}" name="address" type="text"
                                            id="nobifashion_main_checkout_form_address" autocomplete="new-password" class="nobifashion_no_autofill"
                                            placeholder="Số nhà (ngõ), Đường, Xã/Phường" required />
                                        <div class="nobifashion_main_checkout_form_address"></div>
                                    </div>

                                    <div class="nobifashion_checkout_field nobifashion_checkout_field--full nobifashion_checkout_hint">
                                        <small>Gợi ý *: Chỉ cần nhập số nhà + đường phố sẽ ra địa chỉ chi tiết</small>
                                    </div>

                                    <div class="nobifashion_checkout_field">
                                        <label>Tỉnh/Thành phố <span class="nobifashion_required">*</span></label>
                                        <select onchange="onProvinceChange(this)" name="provinceId" placeholder="Chọn Tỉnh/Thành Phố" autocomplete="off"
                                            class="nobifashion_main_checkout_flex_province" required>
                                            <option value="null">Chọn Tỉnh/Thành Phố</option>
                                        </select>
                                    </div>

                                    <div class="nobifashion_checkout_field">
                                        <label>Quận/Huyện <span class="nobifashion_required">*</span></label>
                                        <select onchange="onDistrictChange(this)" name="districtId" placeholder="Chọn Quận/Huyện" autocomplete="off"
                                            class="nobifashion_main_checkout_flex_district" required>
                                            <option value="null">Chọn Quận/Huyện</option>
                                        </select>
                                    </div>

                                    <div class="nobifashion_checkout_field">
                                        <label>Xã/Phường <span class="nobifashion_required">*</span></label>
                                        <select onchange="onWardChange(this)" name="wardId" placeholder="Chọn Xã/Phường" autocomplete="off"
                                            class="nobifashion_main_checkout_flex_ward" required>
                                            <option value="null">Chọn Xã/Phường</option>
                                        </select>
                                    </div>

                                    <div class="nobifashion_checkout_field nobifashion_checkout_field--full">
                                        <label>Ghi chú đơn hàng <small style="color: #666; font-weight: normal;">(Tùy chọn)</small></label>
                                        <textarea name="customer_note" class="nobifashion_main_checkout_box_note nobifashion_no_autofill"
                                            placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi giao, để tại cổng..." maxlength="500" autocomplete="new-password"></textarea>
                                        <div class="character-counter">
                                            <span id="note-counter">0</span>/500 ký tự
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Shipping -->
                            <div class="nobifashion_main_checkout_box">
                                <h2 class="nobifashion_main_checkout_title">Phương thức giao hàng</h2>
                                <div class="nobifashion_main_checkout_options nobifashion_main_checkout_options_shipping">
                                    <div
                                        style="
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
                                </div>
                            </div>

                            <!-- Voucher -->
                            @auth
                            <div class="nobifashion_main_checkout_box">
                                <h2 class="nobifashion_main_checkout_title">Mã giảm giá</h2>
                                <div class="nobifashion_main_checkout_voucher">
                                    <div class="nobifashion_main_checkout_voucher_input">
                                        <input type="text" id="voucher_code" placeholder="Nhập mã giảm giá (VD: SALE10, WELCOME20)" maxlength="50" autocomplete="new-password" class="nobifashion_no_autofill" />
                                        <button type="button" id="apply_voucher_btn">
                                            <span class="btn-text">Áp dụng</span>
                                        </button>
                                    </div>
                                    <div id="voucher_result" class="nobifashion_main_checkout_voucher_result" style="display: none;">
                                        <div class="voucher_success" style="color: green; font-size: 14px; margin-top: 8px;"></div>
                                        <div class="voucher_error" style="color: red; font-size: 14px; margin-top: 8px;"></div>
                                    </div>
                                    <div id="voucher_info" class="nobifashion_main_checkout_voucher_info" style="display: none;">
                                        <div class="voucher_applied">
                                            <span class="voucher_name"></span>
                                            <span class="voucher_discount" style="color: green; font-weight: bold;"></span>
                                            <button type="button" id="remove_voucher_btn" style="color: red; text-decoration: underline; background: none; border: none; cursor: pointer;">Xóa</button>
                                        </div>
                                    </div>
                                    <div id="voucher_suggestions" class="nobifashion_main_checkout_voucher_suggestions" style="display: none; margin-top: 12px;">
                                        <div style="font-size: 13px; color: #666; margin-bottom: 8px;">💡 Voucher có thể dùng:</div>
                                        <div id="voucher_suggestions_list" style="display: flex; flex-direction: column; gap: 8px;"></div>
                                    </div>
                                </div>
                            </div>
                            @endauth

                            <!-- Payment -->
                            <div class="nobifashion_main_checkout_box">
                                <h2 class="nobifashion_main_checkout_title">Phương thức thanh toán</h2>
                                <div class="nobifashion_main_checkout_options">
                                    <label><input value="cod" type="radio" name="payment" checked /> Thanh toán khi nhận
                                        hàng
                                        (COD)</label>
                                    <label><input value="bank" type="radio" name="payment" /> Chuyển khoản ngân hàng</label>
                                    {{-- <label><input value="payos" type="radio" name="payment" /> Thanh toán PayOS</label> --}}
                                </div>
                            </div>

                            <!-- Hidden fields populated by JS before submit -->
                            <div class="nobifashion_checkout_hidden_fields">
                                <input type="hidden" name="provinceId" id="checkout_province_id" value="">
                                <input type="hidden" name="districtId" id="checkout_district_id" value="">
                                <input type="hidden" name="wardId" id="checkout_ward_id" value="">
                                <input type="hidden" name="serviceId" id="checkout_service_id" value="">
                                <input type="hidden" name="serviceTypeId" id="checkout_service_type_id" value="">
                                <input type="hidden" name="shipping" id="checkout_shipping_value" value="">
                                <input type="hidden" name="shipping_fee" id="checkout_shipping_fee_value" value="">
                                <input type="hidden" name="subtotal" id="checkout_subtotal_value" value="">
                                <input type="hidden" name="total" id="checkout_total_value" value="">
                                <input type="hidden" name="voucher_code" id="voucher_code_input" value="">
                                <input type="hidden" name="voucher_discount" id="voucher_discount_input" value="">
                            </div>
                        </div>
                    
                    <!-- Cột phải -->
                    <div class="nobifashion_main_checkout_right">
                        <div class="nobifashion_main_checkout_box">
                            <h2 class="nobifashion_main_checkout_title">Đơn hàng của bạn</h2>
                            <table class="nobifashion_main_checkout_table">
                                <thead>
                                    <tr>
                                        <th class="nobifashion_main_checkout_table_head_product">Sản phẩm</th>
                                        <th class="nobifashion_main_checkout_table_head_total">Tổng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (isset($cartItem))
                                        {{-- Buy Now - Single Item --}}
                                        <tr data-product-id="{{ $cartItem->product_id }}" 
                                            data-variant-id="{{ $cartItem->product_variant_id ?? '' }}" 
                                            data-category-id="{{ $cartItem->product->primary_category_id ?? '' }}">
                                            <td class="nobifashion_main_checkout_table_product">
                                                <input type="hidden" value="{{ $cartItem->product_id }}" name="productId">
                                                <input type="hidden" value="{{ $cartItem->cart->id }}" name="cart_id">
                                                <input type="hidden" value="{{ $cartItem->uuid }}" name="uuid">
                                                <img src="{{ asset('clients/assets/img/clothes/' . ($cartItem->product->primaryImage->url ?? 'no-image.webp')) }}"
                                                    alt="{{ $cartItem->product->name }}"
                                                    class="nobifashion_main_checkout_table_product_img">
                                                <div class="nobifashion_main_checkout_table_product_info">
                                                    <div class="nobifashion_main_checkout_table_product_name">
                                                        {{ $cartItem->product->name }}
                                                    </div>
                                                    <div class="nobifashion_main_checkout_table_product_attrs">
                                                        @if($cartItem->variant)
                                                            @php
                                                                $attrs = is_string($cartItem->variant->attributes)
                                                                    ? json_decode($cartItem->variant->attributes, true)
                                                                    : $cartItem->variant->attributes;
                                                            @endphp
                                                            @if ($attrs && is_array($attrs))
                                                                {{ collect($attrs)->map(fn($val, $key) => ucfirst($key) . ': ' . $val)->join(', ') }}
                                                            @endif
                                                        @endif
                                                    </div>
                                                    <div class="nobifashion_main_checkout_table_product_qty">
                                                        {{ number_format($cartItem->price, 0, ',', '.') }}đ x {{ $cartItem->quantity }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="nobifashion_main_checkout_table_total">
                                                {{ number_format($cartItem->total_price, 0, ',', '.') }}đ
                                            </td>
                                        </tr>
                                    @elseif(isset($cartItems) && $cartItems->isNotEmpty())
                                        {{-- Checkout cả giỏ --}}
                                        <input type="hidden" value="{{ $cartItems->first()->cart->id }}" name="cartId">
                                        @foreach ($cartItems as $item)
                                            <tr data-product-id="{{ $item->product_id }}" 
                                                data-variant-id="{{ $item->product_variant_id ?? '' }}" 
                                                data-category-id="{{ $item->product->primary_category_id ?? '' }}">
                                                <td class="nobifashion_main_checkout_table_product">
                                                    <img src="{{ asset('clients/assets/img/clothes/' . ($item->product->primaryImage->url ?? 'no-image.webp')) }}"
                                                        alt="{{ $item->product->name }}"
                                                        class="nobifashion_main_checkout_table_product_img">
                                                    <div class="nobifashion_main_checkout_table_product_info">
                                                        <div class="nobifashion_main_checkout_table_product_name">
                                                            {{ $item->product->name }}
                                                        </div>
                                                        <div class="nobifashion_main_checkout_table_product_attrs">
                                                            @if($item->variant)
                                                                @php
                                                                    $attrs = is_string($item->variant->attributes)
                                                                        ? json_decode($item->variant->attributes, true)
                                                                        : $item->variant->attributes;
                                                                @endphp
                                                                @if ($attrs && is_array($attrs))
                                                                    {{ collect($attrs)->map(fn($val, $key) => ucfirst($key) . ': ' . $val)->join(', ') }}
                                                                @endif
                                                            @endif
                                                        </div>
                                                        <div class="nobifashion_main_checkout_table_product_qty">
                                                            {{ number_format($item->price, 0, ',', '.') }}đ x {{ $item->quantity }}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="nobifashion_main_checkout_table_total">
                                                    {{ number_format($item->total_price, 0, ',', '.') }}đ
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th class="nobifashion_main_checkout_table_foot_label">Tạm tính</th>
                                        <td class="nobifashion_main_checkout_table_foot_value" id="checkout_estimated">
                                            @if (isset($cartItem))
                                                {{ number_format($cartItem->total_price, 0, ',', '.') }}đ
                                            @elseif(isset($cartItems) && $cartItems->isNotEmpty())
                                                {{ number_format($cartItems->sum('total_price'), 0, ',', '.') }}đ
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="nobifashion_main_checkout_table_foot_label">Phí vận chuyển</th>
                                        <td class="nobifashion_main_checkout_table_foot_value" id="checkout_shipping_fee">0đ
                                        </td>
                                    </tr>
                                    @auth
                                    <tr id="voucher_discount_row" style="display: none;">
                                        <th class="nobifashion_main_checkout_table_foot_label">Giảm giá</th>
                                        <td class="nobifashion_main_checkout_table_foot_value" id="checkout_voucher_discount" style="color: green;">0đ</td>
                                    </tr>
                                    @endauth
                                    <tr>
                                        <th
                                            class="nobifashion_main_checkout_table_foot_label nobifashion_main_checkout_table_foot_total">
                                            Tổng cộng</th>
                                        <td class="nobifashion_main_checkout_table_foot_value nobifashion_main_checkout_table_foot_total_value"
                                            id="checkout_total">
                                            @if (isset($cartItem))
                                                {{ number_format($cartItem->total_price, 0, ',', '.') }}đ
                                            @elseif(isset($cartItems) && $cartItems->isNotEmpty())
                                                {{ number_format($cartItems->sum('total_price'), 0, ',', '.') }}đ
                                            @endif
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>

                            <button type="submit" class="nobifashion_main_checkout_btn">Đặt hàng</button>
                        </div>
                    </div>
                </form>

                    @include('clients.templates.loding_form')
                </div>
            </section>
            <!-- FORM ẢO GỬI DỮ LIỆU CHECKOUT -->
            {{-- <form id="fakeCheckoutForm" action="/check-out/create/item" method="POST" style="display:none;">
                <input type="hidden" name="fullname" value="Đức Nobi" />
                <input type="hidden" name="email" value="admin@gmail.com" />
                <input type="hidden" name="phone" value="0827786198" />
                <input type="hidden" name="address" value="512 Phố Thiên Lôi, Phường Vĩnh Niệm, Quận Lê Chân, Hải Phòng, Việt Nam" />
                <input type="hidden" name="provinceId" value="265" />
                <input type="hidden" name="districtId" value="2021" />
                <input type="hidden" name="wardId" value="620612" />
                <input type="hidden" name="serviceId" value="53322" />
                <input type="hidden" name="serviceTypeId" value="2" />
                <input type="hidden" name="shipping" value="45001" />
                <input type="hidden" name="shipping_fee" value="45000" />
                <input type="hidden" name="subtotal" value="1200000" />
                <input type="hidden" name="total" value="1245000" />
                <input type="hidden" name="payment" value="cod" />
                <input type="hidden" name="items" value='[{"name":"Áo Polo Nam Cao Cấp – Thời Trang Trẻ Trung, Lịch Lãm, Dễ Phối Đồ","price":150000,"quantity":8,"total_price":1200000,"attributes":"Size: L, Color: Trắng"}]' />
            </form> --}}
        </div>
        @auth
            @if($addresses->isNotEmpty())
                <div class="modal fade" id="addressBookModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Sổ địa chỉ của bạn</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                @foreach($addresses as $address)
                                    <div class="border rounded p-3 mb-3 {{ $address->is_default ? 'border-primary' : '' }}">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong>{{ $address->full_name }}</strong> — {{ $address->phone_number }}<br>
                                                {{ $address->detail_address }}, {{ $address->ward ? $address->ward.', ' : '' }}{{ $address->district }}, {{ $address->province }}<br>
                                                <small class="text-muted">Postal: {{ $address->postal_code }}</small>
                                                @if($address->notes)
                                                    <div class="text-muted small mt-1">{{ $address->notes }}</div>
                                                @endif
                                            </div>
                                            <button type="button" class="btn btn-sm btn-primary" data-address-json="{{ htmlspecialchars(json_encode($address), ENT_QUOTES, 'UTF-8') }}">
                                                Sử dụng
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endauth
    @else
        <div class="nobifashion_order_disabled">
            <h1>Chức năng checkout hiện đang bị tắt</h1>
            <p>Vui lòng liên hệ quản trị viên để biết thêm chi tiết.</p>
        </div>
    @endif

    @include('clients.templates.chat')
@endsection
