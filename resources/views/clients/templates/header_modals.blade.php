<dialog class="nobifashion_home_dialog" id="nobifashion_home_search"
    aria-labelledby="nobifashion_home_search_title">
    <div class="nobifashion_home_dialog_shell">
        <div class="nobifashion_home_dialog_header nobifashion_home_container">
            <h2 class="nobifashion_home_dialog_title" id="nobifashion_home_search_title">Tìm kiếm sản phẩm</h2>
            <button class="nobifashion_home_icon_button nobifashion_modal_close_btn" type="button" aria-label="Đóng" data-nobifashion-close
                autofocus>
                <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
                    <path d="m5 5 14 14M19 5 5 19"></path>
                </svg>
            </button>
        </div>
        <div class="nobifashion_home_dialog_content nobifashion_home_container">
            <form class="nobifashion_home_search_form" id="nobifashion_home_search_form"
                action="{{ route('client.product.shop.search.keyword') }}" method="get" role="search">
                <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
                    <circle cx="10.8" cy="10.8" r="6.8"></circle>
                    <path d="m16 16 5 5"></path>
                </svg>
                <label class="nobifashion_home_sr_only" for="nobifashion_home_search_input">Bạn đang tìm sản phẩm gì?</label>
                <input class="nobifashion_home_search_input" id="nobifashion_home_search_input" name="keyword"
                    type="search" placeholder="Nhập tên sản phẩm, mã SKU, danh mục..." autocomplete="off" maxlength="100">
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
                Tìm theo tên sản phẩm hoặc danh mục.</p>
            <ul class="nobifashion_home_search_list" id="nobifashion_home_search_results"></ul>
            <a class="nobifashion_home_search_more" id="nobifashion_home_search_more"
                href="{{ route('client.product.shop.search.keyword') }}" hidden>Xem tất cả kết quả tìm kiếm →</a>
        </div>
    </div>
</dialog>

<dialog class="nobifashion_home_dialog" id="nobifashion_home_menu" aria-labelledby="nobifashion_home_menu_title">
    <div class="nobifashion_home_dialog_shell">
        <div class="nobifashion_home_dialog_header nobifashion_home_container">
            <h2 class="nobifashion_home_dialog_title" id="nobifashion_home_menu_title">Danh mục sản phẩm</h2>
            <button class="nobifashion_home_icon_button nobifashion_modal_close_btn" type="button" aria-label="Đóng" data-nobifashion-close
                autofocus>
                <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
                    <path d="m5 5 14 14M19 5 5 19"></path>
                </svg>
            </button>
        </div>
        <div class="nobifashion_home_dialog_content nobifashion_home_container">
            <div class="nobifashion_home_tabs nobifashion_home_menu_tabs nobifashion_menu_tabs_bar" role="tablist" aria-label="Đối tượng mua sắm">
                @if (isset($categories) && $categories->isNotEmpty())
                    @foreach ($categories as $rootIndex => $rootCat)
                        <button class="nobifashion_home_tab nobifashion_home_menu_tab nobifashion_menu_tab_item" type="button"
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

            @if (isset($categories) && $categories->isNotEmpty())
                @foreach ($categories as $rootIndex => $rootCat)
                    <div class="nobifashion_home_menu_grid nobifashion_menu_grid_panel" id="nobifashion_home_menu_{{ $rootCat->slug }}"
                        role="tabpanel" aria-labelledby="nobifashion_home_menu_tab_{{ $rootCat->slug }}"
                        data-nobifashion-menu-panel="{{ $rootCat->slug }}"
                        @if ($rootIndex !== 0) hidden @endif>

                        @if ($rootCat->children && $rootCat->children->isNotEmpty())
                            @foreach ($rootCat->children as $child)
                                <details class="nobifashion_home_menu_group nobifashion_menu_accordion_group">
                                    <summary>{{ mb_strtoupper($child->name, 'UTF-8') }}</summary>
                                    <ul class="nobifashion_home_menu_links nobifashion_menu_links_list">
                                        <li>
                                            <a href="{{ url($child->slug) }}"
                                                class="nobifashion_menu_view_all_link">Xem tất cả</a>
                                        </li>
                                        @if ($child->children && $child->children->isNotEmpty())
                                            @foreach ($child->children as $grandChild)
                                                <li>
                                                    <a href="{{ url($grandChild->slug) }}" class="nobifashion_menu_sub_link">{{ $grandChild->name }}</a>
                                                    @if ($grandChild->children && $grandChild->children->isNotEmpty())
                                                        <ul class="nobifashion_menu_great_sublinks">
                                                            @foreach ($grandChild->children as $greatChild)
                                                                <li>
                                                                    <a href="{{ url($greatChild->slug) }}">
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
                            <p style="padding: 32px 0; color: #888; grid-column: 1 / -1; text-align: center;">Chưa có danh mục con.</p>
                        @endif

                    </div>
                @endforeach
            @endif
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
