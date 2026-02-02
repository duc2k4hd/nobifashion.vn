<header class="nobifashion_header">
    <div class="nobifashion_header_topbar">
        <div class="nobifashion_header_topbar_links">
            <a href="{{ route('client.page.contact') }}">Liên hệ</a>
            <a href="{{ route('client.order.index') }}">Đơn hàng</a>
            <a href="{{ route('client.product.shop.index') }}">Shop</a>
            <a href="{{ route('client.auth.register') }}">Đăng ký</a>
        </div>
        <div class="nobifashion_header_topbar_supports">
            <div class="nobifashion_header_topbar_supports_support">
                <a href="tel:123456789">Hỗ trợ: {{ preg_replace('/^(\d{4})(\d{3})(\d{3})$/', '$1.$2.$3', preg_replace('/\D/', '', $settings->contact_phone ?? '' )) }}</a>
            </div>
            <div class="nobifashion_header_topbar_supports_language">
                <select name="language" id="language" aria-label="Chọn ngôn ngữ" title="Chọn ngôn ngữ" onchange="showCustomToast('Tính năng đang phát triển', 'info', 3000)">
                    <option value="vn" title="Tiếng Việt">Tiếng Việt</option>
                    <option value="en" title="English">English</option>
                </select>
            </div>
            <div class="nobifashion_header_topbar_supports_currency">
                <select name="currency" id="currency" aria-label="Chọn đơn vị tiền tệ" title="Chọn đơn vị tiền tệ" onchange="showCustomToast('Tính năng đang phát triển', 'info', 3000)">
                    <option value="vnd" title="VND">VND</option>
                    <option value="usd" title="USD">USD</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="nobifashion_header_main">
        <div class="nobifashion_header_main_logo">
            <a href="/">
                <img width="180px" height="55px" src="{{ asset('clients/assets/img/business/'. ($settings->site_logo ?? '') ) }}" alt="Shop {{ $settings->subname ?? '' }}" title="Shop {{ $settings->subname ?? '' }}">
            </a>
        </div>
        <div class="nobifashion_header_main_search">
            <form class="nobifashion_header_main_search_form" action="{{ route('client.product.shop.search') }}" method="post">
                @csrf
                {{-- <select class="nobifashion_header_main_search_select" name="" id="">
                    <option value="">Tất cả danh mục</option>
                    @foreach ($categories as $category)
                        <option value="">{{ $category->name }}</option>
                    @endforeach
                </select> --}}
                <input name="search_text" class="nobifashion_header_main_search_input" type="text"
                    placeholder="Tìm kiếm sản phẩm..." value="{{ old('keyword', request('keyword')) }}">
                    <button class="nobifashion_header_main_search_btn" type="submit" aria-label="Tìm kiếm" title="Tìm kiếm"><svg xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 512 512"><path
                            d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z" />
                    </svg></button>
            </form>
        </div>
        <div class="nobifashion_header_main_icons">
            <div class="nobifashion_header_main_icon nobifashion_header_main_icons_compare">
                <a href="/">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 512 512"><path
                            d="M320 488c0 9.5-5.6 18.1-14.2 21.9s-18.8 2.3-25.8-4.1l-80-72c-5.1-4.6-7.9-11-7.9-17.8s2.9-13.3 7.9-17.8l80-72c7-6.3 17.2-7.9 25.8-4.1s14.2 12.4 14.2 21.9l0 40 16 0c35.3 0 64-28.7 64-64l0-166.7C371.7 141 352 112.8 352 80c0-44.2 35.8-80 80-80s80 35.8 80 80c0 32.8-19.7 61-48 73.3L464 320c0 70.7-57.3 128-128 128l-16 0 0 40zM456 80a24 24 0 1 0 -48 0 24 24 0 1 0 48 0zM192 24c0-9.5 5.6-18.1 14.2-21.9s18.8-2.3 25.8 4.1l80 72c5.1 4.6 7.9 11 7.9 17.8s-2.9 13.3-7.9 17.8l-80 72c-7 6.3-17.2 7.9-25.8 4.1s-14.2-12.4-14.2-21.9l0-40-16 0c-35.3 0-64 28.7-64 64l0 166.7c28.3 12.3 48 40.5 48 73.3c0 44.2-35.8 80-80 80s-80-35.8-80-80c0-32.8 19.7-61 48-73.3L48 192c0-70.7 57.3-128 128-128l16 0 0-40zM56 432a24 24 0 1 0 48 0 24 24 0 1 0 -48 0z" />
                    </svg>
                    <span
                        class="nobifashion_header_main_icon_count nobifashion_header_main_icon_compre__count">0</span>
                    <span class="nobifashion_header_main_icon_name">So sánh</span>
                </a>
            </div>
            <div class="nobifashion_header_main_icon nobifashion_header_main_icons_wishlist">
                <a href="{{ (($wishlistCount ?? 0) > 0) ? ($wishlistLink ?? route('client.favorites.index')) : '#' }}" class="nobifashion_header_wishlist_link">
                    <svg class="nobifashion_header_wishlist_heart" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="#ff3366" d="M305 151.1L320 171.8L335 151.1C360 116.5 400.2 96 442.9 96C516.4 96 576 155.6 576 229.1L576 231.7C576 343.9 436.1 474.2 363.1 529.9C350.7 539.3 335.5 544 320 544C304.5 544 289.2 539.4 276.9 529.9C203.9 474.2 64 343.9 64 231.7L64 229.1C64 155.6 123.6 96 197.1 96C239.8 96 280 116.5 305 151.1z"/></svg>
                    <span class="nobifashion_header_main_icon_count nobifashion_header_main_icon_wishlist_count">{{ $wishlistCount ?? 0 }}</span>
                    <span class="nobifashion_header_main_icon_name">Yêu thích</span>
                </a>
            </div>
            <div class="nobifashion_header_main_icon nobifashion_header_main_icons_cart">
                <a href="{{ (($cartCount ?? $cartQuantity ?? $cartQty ?? 0) > 0) ? ($cartLink ?? $cartUrl ?? route('client.cart.index')) : '#' }}">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 576 512"><path
                            d="M0 24C0 10.7 10.7 0 24 0L69.5 0c22 0 41.5 12.8 50.6 32l411 0c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3l-288.5 0 5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5L488 336c13.3 0 24 10.7 24 24s-10.7 24-24 24l-288.3 0c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5L24 48C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z" />
                    </svg>
                    <span
                        class="nobifashion_header_main_icon_count nobifashion_header_main_icon_cart_count">{{ $cartCount ?? $cartQuantity ?? $cartQty ?? 0 }}</span>
                    <span class="nobifashion_header_main_cart nobifashion_header_main_icon_name">Giỏ hàng</span>
                </a>
            </div>
            @php
                $isClientLoggedIn = auth('web')->check();
                $accountLink = $isClientLoggedIn ? route('client.profile.index') : route('client.auth.login');
                $rawName = $account?->profile?->full_name
                    ?? $account?->name
                    ?? $account?->email
                    ?? 'Đăng nhập';
                $accountLabel = $isClientLoggedIn
                    ? \Illuminate\Support\Str::limit($rawName, 20)
                    : 'Đăng nhập';
            @endphp
            <div class="nobifashion_header_main_icon nobifashion_header_main_icons_account">
                <a class="nobifashion_header_main_icon_link" href="{{ $accountLink }}">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 512 512"><path
                            d="M406.5 399.6C387.4 352.9 341.5 320 288 320l-64 0c-53.5 0-99.4 32.9-118.5 79.6C69.9 362.2 48 311.7 48 256C48 141.1 141.1 48 256 48s208 93.1 208 208c0 55.7-21.9 106.2-57.5 143.6zm-40.1 32.7C334.4 452.4 296.6 464 256 464s-78.4-11.6-110.5-31.7c7.3-36.7 39.7-64.3 78.5-64.3l64 0c38.8 0 71.2 27.6 78.5 64.3zM256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm0-272a40 40 0 1 1 0-80 40 40 0 1 1 0 80zm-88-40a88 88 0 1 0 176 0 88 88 0 1 0 -176 0z" />
                    </svg>
                        {{-- @if ($account)
                            @php
                                $accountName = $account->name;
                                $words = explode(' ', $accountName);
                                $firstWord = $words[0];
                                if (strlen($accountName) > 20) {
                                    $displayName = strlen($firstWord) > 20 ? '...' : $firstWord . '...';
                                } else {
                                    $displayName = trim($accountName);
                                }
                            @endphp
                        @endif --}}
                    <span class="nobifashion_header_main_titlenobifashion_header_main_icon_name">{{ $accountLabel }}</span>
                </a>
            </div>
        </div>
        <div class="nobifashion_header_main_mobile_bars">
            <svg xmlns="http://www.w3.org/2000/svg" version="1.0" width="24px" height="24px" viewBox="0 0 600 600" preserveAspectRatio="xMidYMid meet">

                <g transform="translate(0,600) scale(0.1,-0.1)" stroke="none" fill="#3bb77e">

                    <path d="M672 5989 c-165 -28 -308 -103 -433 -228 -128 -129 -200 -267 -228 -441 -15 -91 -15 -4549 0 -4640 28 -174 100 -312 228 -441 129 -128 267 -200 441 -228 91 -15 4549 -15 4640 0 174 28 312 100 441 228 128 129 200 267 228 441 15 91 15 4549 0 4640 -28 174 -100 312 -228 441 -129 128 -267 200 -441 228 -81 13 -4569 13 -4648 0z m1960 -1372 c68 -33 111 -82 135 -152 16 -45 18 -99 18 -565 0 -590 0 -586 -82 -666 -81 -80 -77 -79 -663 -79 -466 0 -520 2 -565 18 -70 24 -119 67 -152 135 l-28 57 -3 504 c-2 335 1 521 8 556 22 104 96 183 198 211 36 10 168 13 562 11 l515 -2 57 -28z m1895 4 c71 -32 127 -95 150 -168 16 -53 18 -105 18 -553 0 -441 -2 -501 -17 -551 -23 -72 -81 -139 -150 -170 l-53 -24 -530 0 -530 0 -52 24 c-71 32 -127 95 -150 168 -16 53 -18 105 -18 553 0 447 2 501 18 553 19 63 70 128 121 154 79 41 92 42 626 40 l515 -2 52 -24z m-1922 -1852 c66 -25 119 -68 145 -118 38 -75 41 -137 38 -646 l-3 -490 -26 -55 c-33 -71 -79 -114 -148 -142 -55 -22 -67 -23 -531 -26 -323 -2 -495 0 -540 8 -130 23 -205 89 -235 209 -14 51 -15 136 -13 563 l3 503 24 53 c13 30 42 69 67 91 80 70 78 70 651 70 497 1 514 0 568 -20z m1490 -35 c83 -21 209 -84 277 -139 121 -96 222 -265 254 -425 30 -151 5 -331 -66 -469 l-19 -38 160 -159 c165 -164 189 -194 189 -239 0 -35 -50 -85 -85 -85 -43 0 -63 16 -227 178 l-156 155 -40 -34 c-62 -53 -160 -107 -247 -136 -71 -24 -95 -27 -220 -27 -128 0 -147 2 -220 28 -307 107 -499 372 -499 686 1 130 21 217 76 330 34 68 60 103 133 176 97 98 172 146 285 183 132 43 275 48 405 15z"></path>
                    <path d="M3823 2569 c-370 -62 -563 -489 -368 -814 20 -33 51 -75 68 -94 40 -43 148 -112 207 -134 311 -113 647 76 716 403 61 286 -130 574 -421 635 -77 16 -124 17 -202 4z"></path>
                </g>
            </svg>
        </div>
    </div>
    
    <div class="nobifashion_header_main_nav">
        <div class="nobifashion_header_main_nav_category">
            <div class="nobifashion_header_main_nav_category_title">
                <svg xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 512 512"><path
                        d="M40 48C26.7 48 16 58.7 16 72l0 48c0 13.3 10.7 24 24 24l48 0c13.3 0 24-10.7 24-24l0-48c0-13.3-10.7-24-24-24L40 48zM192 64c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L192 64zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-288 0zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-288 0zM16 232l0 48c0 13.3 10.7 24 24 24l48 0c13.3 0 24-10.7 24-24l0-48c0-13.3-10.7-24-24-24l-48 0c-13.3 0-24 10.7-24 24zM40 368c-13.3 0-24 10.7-24 24l0 48c0 13.3 10.7 24 24 24l48 0c13.3 0 24-10.7 24-24l0-48c0-13.3-10.7-24-24-24l-48 0z" />
                </svg>
                <span class="nobifashion_header_main_nav_category_title_name">TẤT CẢ DANH MỤC</span>
                <svg xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 448 512"><path
                        d="M201.4 374.6c12.5 12.5 32.8 12.5 45.3 0l160-160c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 306.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160z" />
                </svg>
            </div>
            <div class="nobifashion_header_main_nav_category_lists">
                @foreach ($categories as $category)
                    <div class="nobifashion_header_main_nav_category_lists_items">
                        <div class="nobifashion_header_main_nav_category_lists_items_item">
                            <h3 class="nobifashion_header_main_nav_category_lists_items_item_title">{{ $category->name }}</h3>
                            <ul class="nobifashion_header_main_nav_category_lists_items_item_list">
                                @foreach ($category->children as $children)
                                    <li><a href="/{{ $children->slug }}">{{ $children->name }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
            <div class="nobifashion_header_main_nav_deals">
            <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <style>
                        .nobifashion_header_main_nav_deals_cls-1 {
                            fill: #FF3366;
                        }
                        .nobifashion_header_main_nav_deals_cls-2 {
                            fill: #FF3366;
                        }
                        .nobifashion_header_main_nav_deals_cls-3 {
                            fill: #ffffff;
                        }
                        .nobifashion_header_main_nav_deals_cls-4 {
                            fill: #ffffff;
                        }
                    </style>
                </defs>
                <title />
                <g id="hot-deal">
                    <path class="nobifashion_header_main_nav_deals_cls-1"
                        d="M58,35c0,14.82-11.18,26-26,26C16.45,61,6,50.55,6,35A53,53,0,0,1,8.08,19.45,2,2,0,0,1,10,18h0a2,2,0,0,1,1.9,1.37C12,19.6,12.65,21,15,21c4.84,0,5-9.9,5-10a2,2,0,0,1,2.89-1.79A9,9,0,0,0,27,10c2.44,0,4.06-2.39,5-4.46a18.58,18.58,0,0,0,1-3A2,2,0,0,1,35,1h0A2,2,0,0,1,37,2.67C37.68,6.91,40.86,19,46,19c4.12,0,5-3.9,5-4.34A2,2,0,0,1,52.82,13a2,2,0,0,1,2,1.28C58,22.64,58,31.94,58,35Z" />
                    <path class="nobifashion_header_main_nav_deals_cls-2"
                        d="M58,35c0,14.82-11.18,26-26,26V5.54a18.58,18.58,0,0,0,1-3A2,2,0,0,1,35,1h0A2,2,0,0,1,37,2.67C37.68,6.91,40.86,19,46,19c4.12,0,5-3.9,5-4.34A2,2,0,0,1,52.82,13a2,2,0,0,1,2,1.28C58,22.64,58,31.94,58,35Z" />
                    <path class="nobifashion_header_main_nav_deals_cls-3"
                        d="M42.41,30.41,32,40.82l-8.59,8.59a2,2,0,0,1-2.82-2.82L32,35.18l7.59-7.59a2,2,0,0,1,2.82,2.82Z" />
                    <path class="nobifashion_header_main_nav_deals_cls-3"
                        d="M24.5,37A5.5,5.5,0,1,0,19,31.5,5.51,5.51,0,0,0,24.5,37Zm0-7A1.5,1.5,0,1,1,23,31.5,1.5,1.5,0,0,1,24.5,30Z" />
                    <path class="nobifashion_header_main_nav_deals_cls-3"
                        d="M38.5,40A5.5,5.5,0,1,0,44,45.5,5.51,5.51,0,0,0,38.5,40Zm0,7A1.5,1.5,0,1,1,40,45.5,1.5,1.5,0,0,1,38.5,47Z" />
                    <path class="nobifashion_header_main_nav_deals_cls-4"
                        d="M42.41,27.59a2,2,0,0,0-2.82,0L32,35.18v5.64L42.41,30.41A2,2,0,0,0,42.41,27.59Z" />
                    <path class="nobifashion_header_main_nav_deals_cls-4"
                        d="M38.5,40A5.5,5.5,0,1,0,44,45.5,5.51,5.51,0,0,0,38.5,40Zm0,7A1.5,1.5,0,1,1,40,45.5,1.5,1.5,0,0,1,38.5,47Z" />
                </g>
            </svg>
            <a class="nobifashion_header_main_nav_deals_name" href="{{ route('client.deals.index') }}">DEALS HOT</a>
            <img width="100px" height="100px" class="nobifashion_header_main_nav_deals_img"
                src="{{ asset('clients/assets/img/icon/firework.gif') }}" alt="Hot Deals">
        </div>
        <div class="nobifashion_header_main_nav_links">
            @foreach ($categories as $category)
                @php
                    // Nếu danh mục có con → lấy sản phẩm của chính nó + các con
                    if ($category->children->isNotEmpty()) {
                        $childIds = $category->children->pluck('id')->toArray();
                        $categoryIds = array_merge([$category->id], $childIds);
                    } else {
                        // Nếu là danh mục con → chỉ lấy chính nó
                        $categoryIds = [$category->id];
                    }

                    // Lấy sản phẩm theo scope inCategory()
                    $productsCategories = \App\Models\Product::active()
                        ->inCategory($categoryIds)
                        ->with('primaryImage')
                        ->limit(5)
                        ->get();
                @endphp
                <div class="nobifashion_header_main_nav_links_item">
                    <h3 class="nobifashion_header_main_nav_links_item_title"><a href="/{{ $category->slug }}">{{ $category->name }}</a><svg xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 448 512"><path
                                d="M201.4 374.6c12.5 12.5 32.8 12.5 45.3 0l160-160c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 306.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160z" />
                        </svg>
                    </h3>
                    <div class="nobifashion_header_main_nav_links_item_list">
                        @foreach ($productsCategories as $product)
                            <div class="nobifashion_header_main_nav_links_item_list_product">
                                <div class="nobifashion_header_main_nav_links_item_list_product_label">
                                    <span class="nobifashion_header_main_nav_links_item_list_product_label_text">{{ $product?->label }}</span>
                                </div>
                                <div class="nobifashion_header_main_nav_links_item_list_product_img">
                                    <img class="nobifashion_header_main_nav_links_item_list_product_img_image"
                                        src="{{ asset('clients/assets/img/clothes/'. $product?->primaryImage?->url) }}" alt="{{ $product?->primaryImage?->alt }}" title="{{ $product?->primaryImage?->title }}">
                                    <a href="/san-pham/{{ $product?->slug }}">
                                        <img class="nobifashion_header_main_nav_links_item_list_product_img_khung"
                                            src="{{ asset('clients/assets/img/frame/'. ($product?->frame ?? 'frame-free-ship-hot.png')) }}" alt="Khung ảnh sản phẩm">
                                    </a>
                                </div>
                                <div class="nobifashion_header_main_nav_links_item_list_product_info">
                                    <h3 class="nobifashion_header_main_nav_links_item_list_product_info_title">
                                        <a href="/san-pham/{{ $product?->slug }}">{{ $product?->name }}</a>
                                    </h3>
                                    <div class="nobifashion_header_main_nav_links_item_list_product_info_rating">
                                        <span class="nobifashion_header_main_nav_links_item_list_product_info_rating_star">
                                            @php
                                                $star = rand(4, 5);
                                                for ($i = 1; $i <= $star; $i++) {
                                                    if ($star == 4) {
                                                        echo '<svg xmlns="http://www.w3.org/2000/svg" height="10" width="10" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#FFD43B" d="M341.5 45.1C337.4 37.1 329.1 32 320.1 32C311.1 32 302.8 37.1 298.7 45.1L225.1 189.3L65.2 214.7C56.3 216.1 48.9 222.4 46.1 231C43.3 239.6 45.6 249 51.9 255.4L166.3 369.9L141.1 529.8C139.7 538.7 143.4 547.7 150.7 553C158 558.3 167.6 559.1 175.7 555L320.1 481.6L464.4 555C472.4 559.1 482.1 558.3 489.4 553C496.7 547.7 500.4 538.8 499 529.8L473.7 369.9L588.1 255.4C594.5 249 596.7 239.6 593.9 231C591.1 222.4 583.8 216.1 574.8 214.7L415 189.3L341.5 45.1z"/></svg>';

                                                        if ($i == 4) {
                                                            echo '<svg xmlns="http://www.w3.org/2000/svg" height="10" width="10" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#FFD43B" d="M320.1 417.6C330.1 417.6 340 419.9 349.1 424.6L423.5 462.5L410.5 380C407.3 359.8 414 339.3 428.4 324.8L487.4 265.7L404.9 252.6C384.7 249.4 367.2 236.7 357.9 218.5L319.9 144.1L319.9 417.7zM489.4 553C482.1 558.3 472.4 559.1 464.4 555L320.1 481.6L175.8 555C167.8 559.1 158.1 558.3 150.8 553C143.5 547.7 139.8 538.8 141.2 529.8L166.4 369.9L52 255.4C45.6 249 43.4 239.6 46.2 231C49 222.4 56.3 216.1 65.3 214.7L225.2 189.3L298.8 45.1C302.9 37.1 311.2 32 320.2 32C329.2 32 337.5 37.1 341.6 45.1L415 189.3L574.9 214.7C583.8 216.1 591.2 222.4 594 231C596.8 239.6 594.5 249 588.2 255.4L473.7 369.9L499 529.8C500.4 538.7 496.7 547.7 489.4 553z"/></svg>';
                                                            break;
                                                        }
                                                    }
                                                    if ($star == 5) {
                                                        echo '<svg xmlns="http://www.w3.org/2000/svg" height="10" width="10" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#FFD43B" d="M341.5 45.1C337.4 37.1 329.1 32 320.1 32C311.1 32 302.8 37.1 298.7 45.1L225.1 189.3L65.2 214.7C56.3 216.1 48.9 222.4 46.1 231C43.3 239.6 45.6 249 51.9 255.4L166.3 369.9L141.1 529.8C139.7 538.7 143.4 547.7 150.7 553C158 558.3 167.6 559.1 175.7 555L320.1 481.6L464.4 555C472.4 559.1 482.1 558.3 489.4 553C496.7 547.7 500.4 538.8 499 529.8L473.7 369.9L588.1 255.4C594.5 249 596.7 239.6 593.9 231C591.1 222.4 583.8 216.1 574.8 214.7L415 189.3L341.5 45.1z"/></svg>';
                                                    }
                                                }
                                            @endphp
                                        </span>
                                        <span class="nobifashion_header_main_nav_links_item_list_product_info_rating_count"><a style="color: #FF3366; text-decoration: underline;" href="/san-pham/{{ $product->slug }}">({{ rand(4, 5) }} review)</a></span>
                                    </div>
                                    <div class="nobifashion_header_main_nav_links_item_list_product_info_price">
                                        @if (!empty($product?->sale_price) && $product?->sale_price < $product?->price)
                                            <span
                                                class="nobifashion_header_main_nav_links_item_list_product_info_price_new">{{ number_format($product?->sale_price ?? 0, 0, ',', '.') }}đ</span>
                                            <span
                                                class="nobifashion_header_main_nav_links_item_list_product_info_price_old">{{ number_format($product?->price ?? $product?->sale_price, 0, ',', '.') }}đ</span>
                                        @else
                                            <span class="nobifashion_header_main_nav_links_item_list_product_info_price_new">
                                                {{ number_format($product?->price ?? 0, 0, ',', '.') }} ₫
                                            </span>
                                        @endif
                                    </div>
                                    <div class="nobifashion_header_main_nav_links_item_list_product_info_actions">
                                        <button onclick="window.location.href = `/san-pham/{{ $product->slug }}`" class="nobifashion_header_main_nav_links_item_list_product_info_actions_add_to_cart">Xem chi tiết</button>
                                        <button data-product-id="{{ $product->id }}" class="nobifashion_fav_btn {{ in_array($product->id, $favoriteProductIds ?? []) ? 'active' : '' }}" aria-label="Yêu thích">
                                            @if(in_array($product->id, $favoriteProductIds ?? []))
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="#ff0000" d="M305 151.1L320 171.8L335 151.1C360 116.5 400.2 96 442.9 96C516.4 96 576 155.6 576 229.1L576 231.7C576 343.9 436.1 474.2 363.1 529.9C350.7 539.3 335.5 544 320 544C304.5 544 289.2 539.4 276.9 529.9C203.9 474.2 64 343.9 64 231.7L64 229.1C64 155.6 123.6 96 197.1 96C239.8 96 280 116.5 305 151.1z"/></svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="#ff0000" d="M442.9 144C415.6 144 389.9 157.1 373.9 179.2L339.5 226.8C335 233 327.8 236.7 320.1 236.7C312.4 236.7 305.2 233 300.7 226.8L266.3 179.2C250.3 157.1 224.6 144 197.3 144C150.3 144 112.2 182.1 112.2 229.1C112.2 279 144.2 327.5 180.3 371.4C221.4 421.4 271.7 465.4 306.2 491.7C309.4 494.1 314.1 495.9 320.2 495.9C326.3 495.9 331 494.1 334.2 491.7C368.7 465.4 419 421.3 460.1 371.4C496.3 327.5 528.2 279 528.2 229.1C528.2 182.1 490.1 144 443.1 144zM335 151.1C360 116.5 400.2 96 442.9 96C516.4 96 576 155.6 576 229.1C576 297.7 533.1 358 496.9 401.9C452.8 455.5 399.6 502 363.1 529.8C350.8 539.2 335.6 543.9 320 543.9C304.4 543.9 289.2 539.2 276.9 529.8C240.4 502 187.2 455.5 143.1 402C106.9 358.1 64 297.7 64 229.1C64 155.6 123.6 96 197.1 96C239.8 96 280 116.5 305 151.1L320 171.8L335 151.1z"/></svg>
                                            @endif
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <div class="nobifashion_header_main_nav_category_lists_items_item_list">
                <h3 class="nobifashion_header_main_nav_links_item_title"><a href="{{ route('client.blog.index') }}">Blog</a></h3>
            </div>
        </div>
        <div class="nobifashion_header_main_nav_support">
            <div class="nobifashion_header_main_nav_support_icon">
                <svg xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 512 512"><path
                        d="M256 80C149.9 80 62.4 159.4 49.6 262c9.4-3.8 19.6-6 30.4-6c26.5 0 48 21.5 48 48l0 128c0 26.5-21.5 48-48 48c-44.2 0-80-35.8-80-80l0-16 0-48 0-48C0 146.6 114.6 32 256 32s256 114.6 256 256l0 48 0 48 0 16c0 44.2-35.8 80-80 80c-26.5 0-48-21.5-48-48l0-128c0-26.5 21.5-48 48-48c10.8 0 21 2.1 30.4 6C449.6 159.4 362.1 80 256 80z" />
                </svg>
            </div>
            <div class="nobifashion_header_main_nav_support_content">
                <div class="nobifashion_header_main_nav_support_content_phone">
                    {{ preg_replace('/^(\d{4})(\d{3})(\d{3})$/', '$1.$2.$3', preg_replace('/\D/', '', $settings->contact_phone ?? '' )) }}
                </div>
                <div class="nobifashion_header_main_nav_support_content_text">
                    Hỗ trợ 24/7
                </div>
            </div>
        </div>
    </div>
    
    <div class="nobifashion_header_mobile_main_nav">
        <div class="nobifashion_mobile_categories_header">
            <button type="button" class="nobifashion_mobile_categories_back nobifashion_header_mobile_main_nav_close">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="24" height="24" fill="currentColor">
                    <path d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.2 288 416 288c17.7 0 32-14.3 32-32s-14.3-32-32-32l-306.7 0L214.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z"></path>
                </svg>
            </button>
            <h2 class="nobifashion_mobile_categories_title">Danh Mục Sản Phẩm</h2>
            <div style="width: 24px;"></div>
        </div>

        <div class="nobifashion_mobile_search_section">
            <form action="{{ route('client.product.shop.search') }}" method="POST" class="nobifashion_mobile_search_form">
                @csrf
                <select class="nobifashion_mobile_search_select" name="category">
                    <option value="">Danh mục</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <input class="nobifashion_mobile_search_input" type="text" name="search_text" value="" placeholder="Tìm kiếm sản phẩm...">
                <button class="nobifashion_mobile_search_btn" type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="18" height="18" fill="currentColor">
                        <path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"></path>
                    </svg>
                </button>
            </form>
            <div title="Tìm kiếm bằng hình ảnh" class="nobifashion_mobile_image_search" onclick="openImageSearchModal()">
                <span style="margin: 0 5px 0 0;">Tìm kiếm bằng hình ảnh</span>
                <svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" viewBox="0 0 512.000000 512.000000" preserveAspectRatio="xMidYMid meet">
                    <g transform="translate(0.000000,512.000000) scale(0.100000,-0.100000)" fill="#000000" stroke="none">
                        <path d="M376 4679 c-180 -26 -332 -175 -366 -358 -14 -74 -14 -2809 0 -2882 31 -165 151 -294 319 -345 39 -12 321 -14 1756 -14 l1710 1 -73 24 c-143 49 -290 136 -377 225 l-49 50 -1447 0 -1446 0 -34 23 c-19 12 -42 38 -51 57 -17 33 -18 121 -18 1420 0 1339 1 1386 19 1427 12 26 32 49 53 60 32 17 119 18 2038 18 1917 0 2007 -1 2033 -18 16 -10 37 -32 47 -50 19 -31 20 -55 20 -634 l0 -603 43 -21 c60 -31 178 -113 212 -149 16 -16 32 -30 37 -30 4 0 8 323 8 718 0 585 -3 725 -14 763 -45 147 -139 247 -285 303 l-56 21 -2010 1 c-1105 1 -2037 -2 -2069 -7z"></path>
                        <path d="M3030 3584 c-132 -57 -165 -232 -64 -335 132 -135 355 -43 355 146 0 119 -88 206 -208 205 -26 -1 -64 -8 -83 -16z"></path>
                        <path d="M1763 3424 c-29 -49 -577 -1006 -720 -1257 -2 -4 436 -6 974 -5 l978 3 7 82 c13 158 69 309 172 468 31 48 56 91 54 95 -3 9 -320 400 -324 400 -1 0 -124 -148 -273 -329 -149 -182 -275 -332 -279 -335 -4 -2 -124 201 -265 453 -142 252 -264 466 -271 476 -12 17 -17 12 -53 -51z"></path>
                        <path d="M3949 3030 c-193 -23 -372 -108 -519 -246 -125 -118 -216 -271 -261 -439 -32 -117 -32 -327 -1 -444 87 -322 318 -560 639 -658 80 -24 103 -26 248 -27 146 0 168 2 248 27 332 102 562 343 644 673 25 103 23 324 -5 427 -85 315 -313 554 -620 651 -77 24 -224 47 -282 45 -14 -1 -55 -5 -91 -9z m252 -164 c400 -72 684 -475 615 -875 -47 -270 -219 -478 -485 -589 -133 -55 -322 -66 -466 -27 -147 39 -307 146 -396 263 -65 86 -133 227 -153 319 -54 246 25 516 204 691 185 183 433 262 681 218z"></path>
                        <path d="M4560 1273 c-102 -58 -189 -109 -193 -113 -9 -8 281 -520 294 -520 4 0 93 50 197 110 l190 110 -145 253 c-80 138 -149 255 -152 259 -4 4 -90 -40 -191 -99z"></path>
                        <path d="M4888 662 c-103 -59 -188 -110 -188 -114 0 -15 55 -70 89 -87 154 -79 334 25 330 190 -1 43 -21 111 -34 115 -6 2 -94 -45 -197 -104z"></path>
                    </g>
                </svg>
            </div>
        </div>

        <div class="nobifashion_mobile_icons_section">
            <a href="#" class="nobifashion_mobile_icon_item" title="So sánh sản phẩm">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="24" height="24" fill="currentColor">
                    <path d="M320 488c0 9.5-5.6 18.1-14.2 21.9s-18.8 2.3-25.8-4.1l-80-72c-5.1-4.6-7.9-11-7.9-17.8s2.9-13.3 7.9-17.8l80-72c7-6.3 17.2-7.9 25.8-4.1s14.2 12.4 14.2 21.9l0 40 16 0c35.3 0 64-28.7 64-64l0-166.7C371.7 141 352 112.8 352 80c0-44.2 35.8-80 80-80s80 35.8 80 80c0 32.8-19.7 61-48 73.3L464 320c0 70.7-57.3 128-128 128l-16 0 0 40zM456 80a24 24 0 1 0 -48 0 24 24 0 1 0 48 0zM192 24c0-9.5 5.6-18.1 14.2-21.9s18.8-2.3 25.8 4.1l80 72c5.1 4.6 7.9 11 7.9 17.8s-2.9 13.3-7.9 17.8l-80 72c-7 6.3-17.2 7.9-25.8 4.1s-14.2-12.4-14.2-21.9l0-40-16 0c-35.3 0-64 28.7-64 64l0 166.7c28.3 12.3 48 40.5 48 73.3c0 44.2-35.8 80-80 80s-80-35.8-80-80c0-32.8 19.7-61 48-73.3L48 192c0-70.7 57.3-128 128-128l16 0 0-40zM56 432a24 24 0 1 0 48 0 24 24 0 1 0 -48 0z"></path>
                </svg>
                <span class="nobifashion_mobile_icon_count" id="comparisonCountMobile">0</span>
                <span class="nobifashion_mobile_icon_name">So sánh</span>
            </a>
            <a href="{{ (($wishlistCount ?? 0) > 0) ? ($wishlistLink ?? route('client.favorites.index')) : '#' }}" class="nobifashion_mobile_icon_item" title="Yêu thích">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="24" height="24" fill="#ff3366">
                    <path d="M305 151.1L320 171.8L335 151.1C360 116.5 400.2 96 442.9 96C516.4 96 576 155.6 576 229.1L576 231.7C576 343.9 436.1 474.2 363.1 529.9C350.7 539.3 335.5 544 320 544C304.5 544 289.2 539.4 276.9 529.9C203.9 474.2 64 343.9 64 231.7L64 229.1C64 155.6 123.6 96 197.1 96C239.8 96 280 116.5 305 151.1z"></path>
                </svg>
                <span class="nobifashion_mobile_icon_count">{{ $wishlistCount ?? 0 }}</span>
                <span class="nobifashion_mobile_icon_name">Yêu thích</span>
            </a>
            <a href="{{ (($cartCount ?? $cartQuantity ?? $cartQty ?? 0) > 0) ? ($cartLink ?? $cartUrl ?? route('client.cart.index')) : '#' }}" class="nobifashion_mobile_icon_item" title="Giỏ hàng">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="24" height="24" fill="currentColor">
                    <path d="M0 24C0 10.7 10.7 0 24 0L69.5 0c22 0 41.5 12.8 50.6 32l411 0c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3l-288.5 0 5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5L488 336c13.3 0 24 10.7 24 24s-10.7 24-24 24l-288.3 0c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5L24 48C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"></path>
                </svg>
                <span class="nobifashion_mobile_icon_count">{{ $cartCount ?? $cartQuantity ?? $cartQty ?? 0 }}</span>
                <span class="nobifashion_mobile_icon_name">Giỏ hàng</span>
            </a>
            @php
                $isClientLoggedIn = auth('web')->check();
                $accountLink = $isClientLoggedIn ? route('client.profile.index') : route('client.auth.login');
                $rawName = $account?->profile?->full_name ?? $account?->name ?? $account?->email ?? 'Đăng nhập';
                $accountLabel = $isClientLoggedIn ? \Illuminate\Support\Str::limit($rawName, 20) : 'Đăng nhập';
            @endphp
            <a href="{{ $accountLink }}" class="nobifashion_mobile_icon_item" title="{{ $accountLabel }}{{ $isClientLoggedIn ? ' ❤️' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="24" height="24" fill="currentColor">
                    <path d="M406.5 399.6C387.4 352.9 341.5 320 288 320l-64 0c-53.5 0-99.4 32.9-118.5 79.6C69.9 362.2 48 311.7 48 256C48 141.1 141.1 48 256 48s208 93.1 208 208c0 55.7-21.9 106.2-57.5 143.6zm-40.1 32.7C334.4 452.4 296.6 464 256 464s-78.4-11.6-110.5-31.7c7.3-36.7 39.7-64.3 78.5-64.3l64 0c38.8 0 71.2 27.6 78.5 64.3zM256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm0-272a40 40 0 1 1 0-80 40 40 0 1 1 0 80zm-88-40a88 88 0 1 0 176 0 88 88 0 1 0 -176 0z"></path>
                </svg>
                <span class="nobifashion_mobile_icon_name">{{ $accountLabel }}{{ $isClientLoggedIn ? ' ❤️' : '' }}</span>
            </a>
        </div>

        @if($isClientLoggedIn)
        <div class="nobifashion_mobile_logout_section">
            <form action="{{ route('client.auth.logout') }}" method="POST">
                @csrf
                <button onclick="return confirm('Bạn có chắc chắn muốn đăng xuất?')" class="nobifashion_mobile_logout_btn" type="submit">Đăng xuất</button>
            </form>
        </div>
        @endif

        <div class="nobifashion_mobile_categories_container">
            <div class="nobifashion_mobile_categories_sidebar">
                @foreach ($categories as $index => $category)
                    <div class="nobifashion_mobile_categories_sidebar_item {{ $index === 0 ? 'active' : '' }}" data-category-id="{{ $category->id }}" onclick="selectMobileCategory({{ $category->id }})">
                        <div class="nobifashion_mobile_categories_sidebar_icon">
                            <img src="{{ asset('clients/assets/img/categories/' . ($category->image ?? 'no-image.webp')) }}" 
                                alt="{{ $category->name }}"
                                width="20" 
                                height="20"
                                class="category-icon"
                                onerror="this.onerror=null; this.src='{{ asset('clients/assets/img/categories/no-image.webp') }}';">
                        </div>
                        <div class="nobifashion_mobile_categories_sidebar_text">{{ $category->name }}</div>
                    </div>
                @endforeach
            </div>

            <div class="nobifashion_mobile_categories_content">
                @foreach ($categories as $category)
                    <div class="nobifashion_mobile_categories_content_list" data-category-content="{{ $category->id }}" style="display: {{ $loop->first ? 'block' : 'none' }};">
                        <a href="{{ route('client.product.shop.index', ['category' => $category->slug]) }}" class="nobifashion_mobile_categories_content_item">
                            <span class="nobifashion_mobile_categories_content_item_text">TẤT CẢ SẢN PHẨM</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="16" height="16" fill="currentColor">
                                <path d="M438.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L338.8 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l306.7 0L233.4 412.7c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z"></path>
                            </svg>
                        </a>
                        @foreach ($category->children as $child)
                            @php
                                // Load children của child nếu chưa được eager load
                                if (!$child->relationLoaded('children')) {
                                    $child->load('children');
                                }
                                $childChildren = $child->children;
                            @endphp
                            <div class="nobifashion_mobile_categories_content_item_wrapper">
                                <div class="nobifashion_mobile_categories_content_item_header">
                                    <a href="/{{ $child->slug }}" class="nobifashion_mobile_categories_content_item">
                                        <span class="nobifashion_mobile_categories_content_item_text">{{ strtoupper($child->name) }}</span>
                                        @if($childChildren && $childChildren->isNotEmpty())
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="16" height="16" fill="currentColor" style="opacity: 0;">
                                                <path d="M438.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L338.8 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l306.7 0L233.4 412.7c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z"></path>
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="16" height="16" fill="currentColor">
                                                <path d="M438.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L338.8 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l306.7 0L233.4 412.7c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z"></path>
                                            </svg>
                                        @endif
                                    </a>
                                    @if($childChildren && $childChildren->isNotEmpty())
                                        <button type="button" class="nobifashion_mobile_categories_content_item_toggle" onclick="toggleSubCategory(this);" aria-label="Mở/Đóng danh mục con">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="16" height="16" fill="currentColor">
                                                <path d="M201.4 374.6c12.5 12.5 32.8 12.5 45.3 0l160-160c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 306.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160z"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                                @if($childChildren && $childChildren->isNotEmpty())
                                    <div class="nobifashion_mobile_categories_content_sublist">
                                        @foreach ($childChildren as $grandchild)
                                            <a href="/{{ $grandchild->slug }}" class="nobifashion_mobile_categories_content_subitem">
                                                {{ $grandchild->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</header>