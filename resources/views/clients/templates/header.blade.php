{{-- @php
    dd($categories)
@endphp --}}

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
            <svg xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 448 512"><path
                    d="M0 96C0 78.3 14.3 64 32 64l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 128C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32L32 448c-17.7 0-32-14.3-32-32s14.3-32 32-32l384 0c17.7 0 32 14.3 32 32z" />
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
                <h3 class="nobifashion_header_main_nav_links_item_title"><a href="{{ route('client.blog.index') }}">Tin tức</a></h3>
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
        <div class="nobifashion_header_mobile_main_nav_close">
            <svg xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 448 512"><path
                    d="M64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l320 0c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64L64 32zm79 143c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z" />
            </svg>
        </div>
    
        <div class="nobifashion_header_mobile_main_nav_logo">
            <a href="/">
                <img width="180px" height="55px" src="{{ asset('clients/assets/img/business/'. ($settings->site_logo ?? '') ) }}" alt="Shop {{ $settings->subname ?? '' }}" title="Shop {{ $settings->subname ?? '' }}">
            </a>
        </div>
    
        <div class="nobifashion_header_mobile_main_nav_search">
            <select class="nobifashion_header_mobile_main_nav_search_select" name="" id="">
                <option value="">Tất cả danh mục</option>
                @foreach ($categories as $category)
                    <option value="">{{ $category->name }}</option>
                @endforeach
            </select>
            <input class="nobifashion_header_mobile_main_nav_search_input" type="text"
                placeholder="Tìm kiếm sản phẩm...">
            <button class="nobifashion_header_mobile_main_nav_search_btn" type="submit"><svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 512 512"><path
                        d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z" />
                </svg></button>
        </div>
    
        <div class="nobifashion_header_mobile_main_nav_links">
            @foreach ($categories as $category)
                <div class="nobifashion_header_mobile_main_nav_links_item">
                    <h3 class="nobifashion_header_mobile_main_nav_links_item_title"><a href="/{{ $category->slug }}">{{ $category->name }}</a><svg xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 448 512"><path
                                d="M201.4 374.6c12.5 12.5 32.8 12.5 45.3 0l160-160c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 306.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160z" />
                        </svg>
                    </h3>
                    <div class="nobifashion_header_mobile_main_nav_links_item_list">
                        @foreach ($category->children as $children)
                            <a href="/{{ $children->slug }}">{{ $children->name }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</header>