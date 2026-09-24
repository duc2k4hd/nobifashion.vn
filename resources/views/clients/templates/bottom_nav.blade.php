<!-- Mobile Bottom Navigation Bar -->
<nav class="nobifashion_bottom_nav" aria-label="Thanh điều hướng nhanh">
    <!-- 1. Trang chủ -->
    <a href="{{ route('client.home.index') }}" class="nobifashion_bottom_nav_item {{ request()->routeIs('client.home.index') ? 'is-active' : '' }}">
        <div class="nobifashion_bottom_nav_icon_wrap">
            <svg viewBox="0 0 24 24" class="nobifashion_bottom_home_icon">
                <path d="M12 2.5a1 1 0 0 0-.64.23l-9 7.2a1 1 0 0 0 1.28 1.54L4 11.16V20a2 2 0 0 0 2 2h4a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h0a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h4a2 2 0 0 0 2-2v-8.84l.36.29a1 1 0 1 0 1.28-1.54l-9-7.2A1 1 0 0 0 12 2.5z"/>
            </svg>
        </div>
        <span class="nobifashion_bottom_nav_label">Trang chủ</span>
    </a>

    <!-- 2. Giỏ hàng -->
    <a href="{{ route('client.cart.index') }}" class="nobifashion_bottom_nav_item {{ request()->routeIs('client.cart.*') ? 'is-active' : '' }}">
        <div class="nobifashion_bottom_nav_icon_wrap">
            <svg viewBox="0 0 24 24" class="nobifashion_bottom_cart_icon">
                <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
            </svg>
            <span class="nobifashion_bottom_badge" id="nobifashionBottomCartBadge">{{ $cartCount ?? 0 }}</span>
        </div>
        <span class="nobifashion_bottom_nav_label">Giỏ hàng</span>
    </a>

    <!-- 3. Nút gọi điện trung tâm (FAB Animation) -->
    <div class="nobifashion_bottom_nav_call_wrap">
        <a href="tel:{{ $settings->contact_phone ?? '' }}" class="nobifashion_bottom_nav_call_btn" aria-label="Gọi điện ngay {{ $settings->contact_phone ?? '' }}">
            <div class="nobifashion_bottom_call_wave"></div>
            <div class="nobifashion_bottom_call_wave wave-2"></div>
            <div class="nobifashion_bottom_call_inner">
                <svg viewBox="0 0 24 24">
                    <path d="M6.62 10.79a15.053 15.053 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24 11.36 11.36 0 0 0 3.58.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.36 11.36 0 0 0 .57 3.58 1 1 0 0 1-.24 1.01l-2.21 2.2z"/>
                </svg>
            </div>
        </a>
    </div>

    <!-- 4. Trợ lý AI -->
    <button type="button" class="nobifashion_bottom_nav_item nobifashion_bottom_nav_btn_ai" id="nobifashionBottomAiBtn" aria-label="Mở Trợ lý AI">
        <div class="nobifashion_bottom_nav_icon_wrap">
            <svg viewBox="0 0 24 24" class="nobifashion_bottom_ai_icon">
                <path d="M12 2a1 1 0 0 1 1 1v1h1a5 5 0 0 1 5 5v1h1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-1v1a5 5 0 0 1-5 5h-4a5 5 0 0 1-5-5v-1H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2h1V9a5 5 0 0 1 5-5h1V3a1 1 0 0 1 1-1zm-3 9a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm6 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm-5.5 5a1 1 0 0 0 0 2h5a1 1 0 1 0 0-2h-5z"/>
            </svg>
        </div>
        <span class="nobifashion_bottom_nav_label">Trợ lý AI</span>
    </button>

    <!-- 5. Zalo -->
    <a href="https://zalo.me/{{ $settings->contact_zalo ?? '' }}" target="_blank" rel="noopener noreferrer" class="nobifashion_bottom_nav_item nobifashion_bottom_nav_item_zalo" aria-label="Chat Zalo">
        <div class="nobifashion_bottom_nav_icon_wrap">
            <svg viewBox="0 0 48 48" class="nobifashion_bottom_zalo_icon">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M24 4C12.954 4 4 12.954 4 24c0 3.738 1.026 7.237 2.809 10.233L4.236 42.18a1.5 1.5 0 0 0 1.936 1.838l8.28-3.072A19.887 19.887 0 0 0 24 44c11.046 0 20-8.954 20-20S35.046 4 24 4z" fill="#0068FF"/>
                <path d="M14 28h5.5l-5-8.5V18h7v2.5h-4.5l5 8.5V30H14V28zm10.5 2c-2.2 0-3.5-1.5-3.5-3.8 0-2.4 1.4-3.8 3.5-3.8 2.2 0 3.5 1.4 3.5 3.8 0 2.3-1.3 3.8-3.5 3.8zm0-1.8c1 0 1.6-.9 1.6-2 0-1.2-.6-2-1.6-2s-1.6.8-1.6 2c0 1.1.6 2 1.6 2zm6.5 1.8V17h2v13h-2zm9-3.8c0 2.3-1.3 3.8-3.5 3.8-2.2 0-3.5-1.5-3.5-3.8 0-2.4 1.4-3.8 3.5-3.8 2.2 0 3.5 1.4 3.5 3.8zm-1.8 0c0-1.2-.6-2-1.6-2s-1.6.8-1.6 2c0 1.1.6 2 1.6 2s1.6-.9 1.6-2z" fill="#ffffff"/>
            </svg>
        </div>
        <span class="nobifashion_bottom_nav_label">Zalo</span>
    </a>
</nav>
