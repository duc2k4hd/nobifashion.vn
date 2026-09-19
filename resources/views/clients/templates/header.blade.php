@php
    $isHomePage = request()->is('/');
@endphp
<header class="nobifashion_home_header {{ !$isHomePage ? 'nobifashion_header_inner_page' : '' }}" id="nobifashion_home_header" data-tone="{{ $isHomePage ? 'dark' : 'light' }}">
    <div class="nobifashion_home_container nobifashion_home_header_inner">
      <a class="nobifashion_home_logo" href="/" aria-label="Trang chủ UNIQLO">
        <img width="auto" height="60px" src="{{ asset('clients/assets/img/business/' . $settings->site_logo ?? '' ) }}"
                alt="Shop {{ renderMeta($settings->subname ?? '' ) }}" title="Shop {{ renderMeta($settings->site_name ?? '' ) }}">
      </a>
      <nav class="nobifashion_home_tabs nobifashion_home_desktop_tabs" aria-label="Đối tượng mua sắm">
        @if(isset($categories) && $categories->isNotEmpty())
            @foreach($categories->take(5) as $cat)
                <a class="nobifashion_home_tab" 
                   href="/{{ $cat->slug }}" 
                   @if(request()->is($cat->slug)) aria-current="page" @endif>
                    {{ $cat->name }}
                </a>
            @endforeach
        @endif
      </nav>
      <div class="nobifashion_home_header_actions">
        <button class="nobifashion_home_search_trigger" type="button" data-nobifashion-open="search"
          aria-label="Tìm kiếm sản phẩm" aria-controls="nobifashion_home_search" aria-expanded="false">
          <span>Bạn đang tìm sản phẩm gì?</span>
          <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
            <circle cx="10.8" cy="10.8" r="6.8"></circle>
            <path d="m16 16 5 5"></path>
          </svg>
        </button>
        <button class="nobifashion_home_icon_button" type="button" aria-label="Yêu thích"
          data-nobifashion-open="wishlist" aria-controls="nobifashion_home_wishlist" aria-expanded="false">
          <svg class="nobifashion_home_icon" viewbox="0 0 24 24" aria-hidden="true">
            <path d="M20 9.2c0 3.4-8 9.5-8 9.5S4 12.6 4 9.2C4 4.4 9.5 3.3 12 7c2.5-3.7 8-2.6 8 2.2Z"></path>
          </svg>
          <span class="nobifashion_home_count" id="nobifashion_home_wishlist_count" hidden>0</span>
        </button>
        <a class="nobifashion_home_icon_button" href="https://www.uniqlo.com/vn/vi/member"
          aria-label="Thành viên / Lịch sử mua hàng">
          <svg class="nobifashion_home_icon nobifashion_home_icon_solid" viewbox="0 0 24 24" aria-hidden="true">
            <path fill="currentColor"
              d="M15.727 10.787c.513-.7.82-1.548.82-2.464C16.546 5.94 14.506 4 12 4S7.454 5.94 7.454 8.323c0 .916.306 1.763.82 2.464L5 12.983V20h14v-7.017zM12 5.178c1.824 0 3.306 1.412 3.306 3.145S13.823 11.468 12 11.468s-3.306-1.411-3.306-3.145S10.177 5.178 12 5.178m5.76 13.644H6.24v-5.227l2.894-1.942a4.64 4.64 0 0 0 2.865.994 4.65 4.65 0 0 0 2.865-.994l2.895 1.941z">
            </path>
          </svg>
        </a>
        <a class="nobifashion_home_icon_button" href="https://www.uniqlo.com/vn/vi/cart" aria-label="Xe đẩy">
          <svg class="nobifashion_home_icon nobifashion_home_icon_solid" viewbox="0 0 24 24" aria-hidden="true">
            <path fill="currentColor"
              d="m7.044 6.566-.004-3.06H2.008v1.199h3.834l.014 10.754h12.897l2.24-8.894zm.01 7.694-.007-6.495h12.407l-1.635 6.495zm9.915 6.242a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3m-9.516 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3">
            </path>
          </svg>
        </a>
        <button class="nobifashion_home_icon_button" type="button" aria-label="Danh Mục Sản Phẩm / Tìm Kiếm Sản Phẩm"
          data-nobifashion-open="menu" aria-controls="nobifashion_home_menu" aria-expanded="false">
          <svg class="nobifashion_home_icon nobifashion_home_icon_solid" viewbox="0 0 24 24" aria-hidden="true">
            <path fill="currentColor" d="M5 5.8v1.29h14V5.8zm0 5.504v1.291h14v-1.29zm0 5.605v1.29h14v-1.29z"></path>
          </svg>
        </button>
      </div>
    </div>
    <nav class="nobifashion_home_tabs nobifashion_home_mobile_tabs" aria-label="Đối tượng mua sắm">
      @if(isset($categories) && $categories->isNotEmpty())
          @foreach($categories->take(5) as $cat)
              <a class="nobifashion_home_tab" 
                 href="/{{ $cat->slug }}" 
                 @if(request()->is($cat->slug)) aria-current="page" @endif>
                  {{ $cat->name }}
              </a>
          @endforeach
      @endif
    </nav>
  </header>
  @include('clients.templates.header_modals')
