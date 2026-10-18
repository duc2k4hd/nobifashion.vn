@php
    $isBlog = request()->is('blog') || request()->is('blog/*');
@endphp
<dialog class="nobifashion_home_dialog" id="nobifashion_home_search"
    aria-labelledby="nobifashion_home_search_title"
    data-search-mode="{{ $isBlog ? 'blog' : 'product' }}">
    <div class="nobifashion_home_dialog_shell">
        <div class="nobifashion_home_dialog_header nobifashion_home_container">
            <h2 class="nobifashion_home_dialog_title" id="nobifashion_home_search_title">
                {{ $isBlog ? 'Tìm kiếm bài viết' : 'Tìm kiếm sản phẩm' }}
            </h2>
            <button class="nobifashion_home_icon_button nobifashion_modal_close_btn" type="button" aria-label="Đóng" data-nobifashion-close
                autofocus>
                <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
                    <path d="m5 5 14 14M19 5 5 19"></path>
                </svg>
            </button>
        </div>
        <div class="nobifashion_home_dialog_content nobifashion_home_container">
            <form class="nobifashion_home_search_form" id="nobifashion_home_search_form"
                action="{{ $isBlog ? route('client.blog.search.keyword') : route('client.product.shop.search.keyword') }}" 
                method="get" role="search"
                data-search-mode="{{ $isBlog ? 'blog' : 'product' }}"
                data-search-api="{{ $isBlog ? route('client.blog.search.api') : '/shop/search' }}">
                <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
                    <circle cx="10.8" cy="10.8" r="6.8"></circle>
                    <path d="m16 16 5 5"></path>
                </svg>
                <label class="nobifashion_home_sr_only" for="nobifashion_home_search_input">
                    {{ $isBlog ? 'Bạn đang tìm bài viết gì?' : 'Bạn đang tìm sản phẩm gì?' }}
                </label>
                <input class="nobifashion_home_search_input" id="nobifashion_home_search_input" name="keyword"
                    type="search" 
                    placeholder="{{ $isBlog ? 'Nhập tiêu đề bài viết, chủ đề, mẹo hay...' : 'Nhập tên sản phẩm, mã SKU, danh mục...' }}" 
                    autocomplete="off" maxlength="100">
                <button class="nobifashion_home_icon_button" type="button" aria-label="Xóa từ khóa"
                    id="nobifashion_home_search_clear" hidden>
                    <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
                        <path d="m5 5 14 14M19 5 5 19"></path>
                    </svg>
                </button>
                <button class="nobifashion_home_icon_button nobifashion_search_submit_btn" type="submit" aria-label="Tìm kiếm">
                    <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
                        <path d="m9 5 7 7-7 7"></path>
                    </svg>
                </button>
            </form>
            <div class="nobifashion_home_search_history" id="nobifashion_home_search_history"></div>
            <p class="nobifashion_home_search_status" id="nobifashion_home_search_status" role="status"
                aria-live="polite">
                {{ $isBlog ? 'Tìm theo tiêu đề bài viết hoặc chuyên mục.' : 'Tìm theo tên sản phẩm hoặc danh mục.' }}
            </p>
            <ul class="nobifashion_home_search_list" id="nobifashion_home_search_results"></ul>
            <a class="nobifashion_home_search_more" id="nobifashion_home_search_more"
                href="{{ $isBlog ? route('client.blog.search.keyword') : route('client.product.shop.search.keyword') }}" hidden>
                {{ $isBlog ? 'Xem tất cả kết quả bài viết →' : 'Xem tất cả kết quả tìm kiếm →' }}
            </a>
        </div>
    </div>
</dialog>

<dialog class="nobifashion_home_dialog nobifashion_mobile_menu_dialog" id="nobifashion_home_menu" aria-labelledby="nobifashion_home_menu_title">
    <div class="nobifashion_home_dialog_shell nobifashion_menu_drawer_shell">
        <div class="nobifashion_home_dialog_header nobifashion_home_container">
            <div class="nobifashion_menu_header_left">
                @php
                    $menuLogoFile = ($settings->site_logo ?? null) ?: 'nobifashion-logo.png';
                    $menuLogoWebp = pathinfo($menuLogoFile, PATHINFO_FILENAME) . '.webp';
                    $menuBusinessDir = public_path('clients/assets/img/business');
                    $menuWebpPath = $menuBusinessDir . DIRECTORY_SEPARATOR . $menuLogoWebp;
                    $finalMenuLogo = file_exists($menuWebpPath) ? $menuLogoWebp : $menuLogoFile;
                @endphp
                <a href="/" class="nobifashion_menu_logo_link" aria-label="Trang chủ Nobi Fashion">
                    <img class="nobifashion_menu_logo_img"
                        src="{{ asset('clients/assets/img/business/' . $finalMenuLogo) }}"
                        alt="Shop {{ renderMeta($settings->subname ?? 'Nobi Fashion') }}"
                        title="Shop {{ renderMeta($settings->site_name ?? 'Nobi Fashion') }}"
                        width="120" height="38">
                </a>
                <div class="nobifashion_menu_title_group">
                    <span class="nobifashion_menu_logo_brand">{{ renderMeta($settings->site_name ?? 'NOBI FASHION') }}</span>
                    <span class="nobifashion_menu_subtitle">MENU &amp; DANH MỤC</span>
                </div>
            </div>
            <button class="nobifashion_home_icon_button nobifashion_modal_close_btn" type="button" aria-label="Đóng menu" data-nobifashion-close autofocus>
                <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
                    <path d="m5 5 14 14M19 5 5 19"></path>
                </svg>
            </button>
        </div>

        <div class="nobifashion_home_dialog_content nobifashion_home_container nobifashion_menu_body_content">
            {{-- 1. Thanh User Profile đồng bộ hệ thống tài khoản --}}
            <div class="nobifashion_menu_user_card">
                <div class="nobifashion_menu_user_avatar">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="nobifashion_menu_user_meta">
                    @if (auth('web')->check())
                        <div class="nobifashion_menu_user_name">Chào, {{ auth('web')->user()->name }}</div>
                        <a href="{{ route('client.profile.index') }}" class="nobifashion_menu_user_action">Quản lý tài khoản &amp; đơn hàng →</a>
                    @else
                        <div class="nobifashion_menu_user_name">Đăng nhập tài khoản</div>
                        <div class="nobifashion_menu_user_desc">Tích lũy điểm &amp; nhận voucher độc quyền</div>
                        <div class="nobifashion_menu_auth_btns">
                            <a href="{{ route('client.auth.login') }}" class="nobifashion_menu_btn_login">Đăng nhập</a>
                            <a href="{{ route('client.auth.register') }}" class="nobifashion_menu_btn_register">Đăng ký</a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 2. Dải Tabs Danh mục chính --}}
            <div class="nobifashion_menu_tabs_heading">DANH MỤC SẢN PHẨM</div>
            <div class="nobifashion_home_tabs nobifashion_home_menu_tabs nobifashion_menu_tabs_bar" role="tablist" aria-label="Đối tượng mua sắm">
                @if (isset($categories) && $categories->isNotEmpty())
                    @foreach ($categories as $rootIndex => $rootCat)
                        <button class="nobifashion_home_tab nobifashion_home_menu_tab nobifashion_menu_tab_item {{ $rootIndex === 0 ? 'nobifashion_tab_active' : '' }}" type="button"
                            id="nobifashion_home_menu_tab_{{ $rootCat->slug }}" role="tab"
                            aria-selected="{{ $rootIndex === 0 ? 'true' : 'false' }}"
                            aria-controls="nobifashion_home_menu_{{ $rootCat->slug }}"
                            tabindex="{{ $rootIndex === 0 ? '0' : '-1' }}"
                            data-nobifashion-menu-tab="{{ $rootCat->slug }}">
                            {{ mb_strtoupper($rootCat->name, 'UTF-8') }}
                        </button>
                    @endforeach
                @endif
            </div>

            {{-- 3. Nội dung Accordion Danh mục theo Tab --}}
            <div class="nobifashion_menu_categories_wrap">
                @if (isset($categories) && $categories->isNotEmpty())
                    @foreach ($categories as $rootIndex => $rootCat)
                        <div class="nobifashion_home_menu_grid nobifashion_menu_grid_panel" id="nobifashion_home_menu_{{ $rootCat->slug }}"
                            role="tabpanel" aria-labelledby="nobifashion_home_menu_tab_{{ $rootCat->slug }}"
                            data-nobifashion-menu-panel="{{ $rootCat->slug }}"
                            @if ($rootIndex !== 0) hidden @endif>

                            @if ($rootCat->children && $rootCat->children->isNotEmpty())
                                @foreach ($rootCat->children as $child)
                                    <details class="nobifashion_home_menu_group nobifashion_menu_accordion_group" {{ $loop->first ? 'open' : '' }}>
                                        <summary>
                                            <span class="nobifashion_summary_label">
                                                <span class="nobifashion_summary_icon">✦</span>
                                                <span>{{ mb_strtoupper($child->name, 'UTF-8') }}</span>
                                            </span>
                                        </summary>
                                        <ul class="nobifashion_home_menu_links nobifashion_menu_links_list">
                                            <li>
                                                <a href="{{ route('client.product.category.index', $child->slug) }}"
                                                    class="nobifashion_menu_view_all_link">
                                                    <span>Xem tất cả {{ $child->name }}</span>
                                                    <span class="nobifashion_view_all_arrow">→</span>
                                                </a>
                                            </li>
                                            @if ($child->children && $child->children->isNotEmpty())
                                                @foreach ($child->children as $grandChild)
                                                    <li>
                                                        <a href="{{ route('client.product.category.index', $grandChild->slug) }}" class="nobifashion_menu_sub_link">
                                                            <span>{{ $grandChild->name }}</span>
                                                        </a>
                                                        @if ($grandChild->children && $grandChild->children->isNotEmpty())
                                                            <ul class="nobifashion_menu_great_sublinks">
                                                                @foreach ($grandChild->children as $greatChild)
                                                                    <li>
                                                                        <a href="{{ route('client.product.category.index', $greatChild->slug) }}">
                                                                            {{ $greatChild->name }}
                                                                        </a>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            @endif
                                        </ul>
                                    </details>
                                @endforeach
                            @else
                                <p style="padding: 24px 0; color: #888; text-align: center;">Chưa có danh mục con.</p>
                            @endif

                        </div>
                    @endforeach
                @endif
            </div>

            {{-- 4. Khối Tiện ích & Điều hướng đồng bộ (Khắc phục hoàn toàn khoảng trống thừa ở đáy) --}}
            <div class="nobifashion_menu_quick_nav_section">
                <div class="nobifashion_menu_quick_heading">DỊCH VỤ &amp; TIỆN ÍCH</div>
                <div class="nobifashion_menu_quick_links">
                    <a href="{{ url('/thoi-trang-nam') }}" class="nobifashion_menu_quick_item nobifashion_quick_item_highlight">
                        <span class="nobifashion_quick_icon">⚡</span>
                        <div class="nobifashion_quick_info">
                            <span class="nobifashion_quick_title">Flash Sale &amp; Giá Tốt Hôm Nay</span>
                            <span class="nobifashion_quick_sub">Ưu đãi giảm tới 50%</span>
                        </div>
                        <span class="nobifashion_quick_arrow">→</span>
                    </a>

                    <a href="{{ route('client.blog.index') }}" class="nobifashion_menu_quick_item">
                        <span class="nobifashion_quick_icon">📰</span>
                        <div class="nobifashion_quick_info">
                            <span class="nobifashion_quick_title">Tạp Chí Phong Cách (Blog)</span>
                            <span class="nobifashion_quick_sub">Bí quyết phối đồ &amp; xu hướng mới</span>
                        </div>
                        <span class="nobifashion_quick_arrow">→</span>
                    </a>

                    <a href="{{ route('client.cart.index') }}" class="nobifashion_menu_quick_item">
                        <span class="nobifashion_quick_icon">🛒</span>
                        <div class="nobifashion_quick_info">
                            <span class="nobifashion_quick_title">Giỏ Hàng Của Bạn</span>
                            <span class="nobifashion_quick_sub">Xem các sản phẩm đã chọn</span>
                        </div>
                        <span class="nobifashion_quick_arrow">→</span>
                    </a>

                    <a href="{{ auth('web')->check() ? route('client.order.index') : route('client.auth.login') }}" class="nobifashion_menu_quick_item">
                        <span class="nobifashion_quick_icon">📦</span>
                        <div class="nobifashion_quick_info">
                            <span class="nobifashion_quick_title">Tra Cứu Đơn Hàng</span>
                            <span class="nobifashion_quick_sub">Theo dõi lộ trình giao nhận</span>
                        </div>
                        <span class="nobifashion_quick_arrow">→</span>
                    </a>
                </div>

                {{-- Khối Cam kết bán hàng & Hotline chân menu --}}
                <div class="nobifashion_menu_footer_box">
                    <div class="nobifashion_menu_support_row">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <span>Hotline Hỗ Trợ: <strong>{{ $settings->phone ?? '098.666.8888' }}</strong> (8h - 22h)</span>
                    </div>
                    <div class="nobifashion_menu_policies_row">
                        <span>✓ Đổi hàng 15 ngày</span>
                        <span>✓ Miễn phí ship từ 499k</span>
                        <span>✓ Đồng kiểm 100%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</dialog>

<dialog class="nobifashion_home_dialog" id="nobifashion_home_wishlist"
    aria-labelledby="nobifashion_home_wishlist_title">
    <div class="nobifashion_home_dialog_shell">
        <div class="nobifashion_home_dialog_header nobifashion_home_container">
            <h2 class="nobifashion_home_dialog_title" id="nobifashion_home_wishlist_title">Sản phẩm yêu thích</h2>
            <button class="nobifashion_home_icon_button nobifashion_modal_close_btn" type="button" aria-label="Đóng" data-nobifashion-close
                autofocus>
                <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
                    <path d="m5 5 14 14M19 5 5 19"></path>
                </svg>
            </button>
        </div>
        <div class="nobifashion_home_dialog_content nobifashion_home_container">
            <div id="nobifashion_home_wishlist_content"></div>
        </div>
    </div>
</dialog>

<div class="nobifashion_home_toast" id="nobifashion_home_toast" role="status" aria-live="polite" hidden></div>
