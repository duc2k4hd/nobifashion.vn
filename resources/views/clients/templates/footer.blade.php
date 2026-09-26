@if (!empty($settings->is_demo))
    <div
        style="
    position: fixed;
    left: 16px;
    right: 16px;
    bottom: 16px;
    z-index: 9999;
    max-width: 420px;
    margin: auto;
    background: rgba(255, 240, 240, 0.95);
    border: 1px solid #ff7c7c;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    padding: 14px 18px;
    border-radius: 10px;
    font-size: 15px;
    line-height: 1.5;
    font-weight: 500;
    color: #333;
    text-align: center;
    backdrop-filter: blur(6px);
">
        Đây là web
        <span style="color: #28a745; font-weight: 700;">DEMO</span>
        vẫn còn nhiều lỗi, nếu có thắc mắc vui lòng liên hệ
        <a href="https://www.facebook.com/ducnobi2004" style="color: #007bff; text-decoration: none; font-weight: 600;">
            Đức Nobi
        </a>
    </div>
@endif
{{-- <div class="nobifashion_context_menu" id="contextMenu">
    <ul>
        <li class="nobifashion_context_menu_item">🛒 Thêm vào giỏ hàng</li>
        <li class="nobifashion_context_menu_item">❤️ Thêm vào yêu thích</li>
        <li class="nobifashion_context_menu_item">🔍 Xem nhanh</li>
        <li class="nobifashion_context_menu_item">📊 So sánh sản phẩm</li>
        <li class="nobifashion_context_menu_divider"></li>
        <li class="nobifashion_context_menu_item">🔗 Sao chép liên kết</li>
        <li class="nobifashion_context_menu_item">📤 Chia sẻ Facebook</li>
        <li class="nobifashion_context_menu_item">🐦 Chia sẻ Twitter</li>
    </ul>
</div> --}}


{{-- <script>
    const menu = document.getElementById("contextMenu");

    document.addEventListener("contextmenu", function(e) {
        e.preventDefault();

        // lấy kích thước menu
        menu.style.display = "block";
        const menuWidth = menu.offsetWidth;
        const menuHeight = menu.offsetHeight;
        const pageWidth = window.innerWidth;
        const pageHeight = window.innerHeight;

        let posX = e.clientX;
        let posY = e.clientY;

        // kiểm tra tràn phải
        if (posX + menuWidth > pageWidth) {
            posX = pageWidth - menuWidth - 5; // cách 5px
        }

        // kiểm tra tràn dưới
        if (posY + menuHeight > pageHeight) {
            posY = pageHeight - menuHeight - 5;
        }

        menu.style.left = posX + "px";
        menu.style.top = posY + "px";
    });

    document.addEventListener("click", function() {
        menu.style.display = "none";
    });
</script> --}}

<footer class="nobifashion_footer">
    <div class="nobifashion_footer_content">
        <div class="nobifashion_footer_content_business">
            @php
                $footerLogoFile = (($settings->site_logo ?? null) ?: 'nobifashion-logo.png');
                $footerLogoWebp = pathinfo($footerLogoFile, PATHINFO_FILENAME) . '.webp';
            @endphp
            <picture>
                @if(file_exists(public_path('clients/assets/img/business/' . $footerLogoWebp)))
                    <source srcset="{{ asset('clients/assets/img/business/' . $footerLogoWebp) }}" type="image/webp">
                @endif
                <img loading="lazy" width="180" height="55" src="{{ asset('clients/assets/img/business/' . $footerLogoFile) }}"
                    alt="Shop {{ renderMeta(($settings->subname ?? null) ?: 'Đang cập nhật...') }}" title="Shop {{ renderMeta(($settings->site_name ?? null) ?: 'Đang cập nhật...') }}">
            </picture>
            <p class="nobifashion_footer_content_business_title">{{ renderMeta(($settings->site_name ?? null) ?: 'Đang cập nhật...') }}</p>
            <p class="nobifashion_footer_content_business_desc">{{ renderMeta(($settings->site_description ?? null) ?: 'Đang cập nhật...') }}</p>
            <p class="nobifashion_footer_content_business_address"><strong>Địa chỉ</strong>: {{ ($settings->contact_address ?? null) ?: 'Đang cập nhật...' }}</p>
            <p class="nobifashion_footer_content_business_phone"><strong>Điện thoại</strong>:
                @if (!empty($settings->contact_phone))
                    <a href="tel:{{ $settings->contact_phone }}">{{ preg_replace('/^(\d{4})(\d{3})(\d{3})$/', '$1.$2.$3', preg_replace('/\D/', '', $settings->contact_phone)) }}</a>
                @else
                    Đang cập nhật...
                @endif
            </p>
            <p class="nobifashion_footer_content_business_email"><strong>Email</strong>:
                @if (!empty($settings->contact_email))
                    <a href="mailto:{{ $settings->contact_email }}">{{ $settings->contact_email }}</a>
                @else
                    Đang cập nhật...
                @endif
            </p>
            <p class="nobifashion_footer_content_business_hours"><strong>Giờ làm việc</strong>: {{ ($settings->business_hours ?? null) ?: 'Đang cập nhật...' }}</p>
            <div class="nobifashion_footer_content_business_socials">
                @if (!empty($settings->facebook_link))
                    <a href="{{ $settings->facebook_link }}" target="_blank" rel="noopener noreferrer"><img loading="lazy"
                            src="{{ asset('clients/assets/img/icon/icon-facebook.webp') }}" alt="Facebook"></a>
                @endif
                @if (!empty($settings->instagram_link))
                    <a href="{{ $settings->instagram_link }}" target="_blank" rel="noopener noreferrer"><img loading="lazy"
                            src="{{ asset('clients/assets/img/icon/icon-Instagram.png') }}" alt="Instagram"></a>
                @endif
                @if (!empty($settings->twitter_link))
                    <a href="{{ $settings->twitter_link }}" target="_blank" rel="noopener noreferrer"><img loading="lazy"
                            src="{{ asset('clients/assets/img/icon/icon-twitter.webp') }}" alt="Twitter"></a>
                @endif
            </div>
            @if (!empty($settings->bo_cong_thuong))
                <a href="{{ $settings->bo_cong_thuong }}" target="_blank" rel="noopener noreferrer">
                    <img loading="lazy" style="object-fit: cover; height: 68px;" src="{{ asset('clients/assets/img/business/setting-bo_cong_thuong-1757497818.webp') }}"
                        alt="Bộ công thương">
                </a>
            @endif
        </div>

        <div class="nobifashion_footer_content_company">
            <p class="nobifashion_footer_content_company_title">Chính sách bán hàng</p>
            <div class="nobifashion_footer_content_company_links">
                <a href="{{ route('client.page.introduction') }}">Giới thiệu</a>
                <a href="{{ route('client.page.contact') }}">Liên hệ</a>
                <a href="{{ route('client.policy.privacy') }}">Chính sách bảo mật</a>
                <a href="{{ route('client.policy.terms') }}">Điều khoản sử dụng</a>
                <a href="{{ route('client.policy.return') }}">Chính sách đổi trả</a>
                <a href="{{ route('client.policy.delivery') }}">Chính sách vận chuyển</a>
                <a href="{{ route('client.policy.warranty') }}">Chính sách bảo hành</a>
                <a href="{{ route('client.policy.payment') }}">Chính sách thanh toán</a>
                <a href="{{ route('client.policy.privacy') }}">Chính sách bảo mật thông tin</a>
                <a href="{{ route('client.policy.privacy') }}">Chính sách bảo mật dữ liệu</a>
                <a href="{{ route('client.policy.editorial') }}">Chính sách biên tập và đính chính thông tin</a>
                @if (!empty($settings->dmca))
                    <a style="position: relative;" href="{!! $settings->dmca !!}" target="_blank" rel="noopener noreferrer" title="DMCA.com Protection Status" class="dmca-badge"> <img style="position: relative; object-fit: cover; max-width: 150px; height: auto;" loading="lazy" src ="{!! ($settings->dmca_logo ?? null) ?: asset('clients/assets/img/other/DMCA.webp') !!}"  alt="DMCA.com Protection Status" /></a>  <script defer src="https://images.dmca.com/Badges/DMCABadgeHelper.min.js"> </script>
                @endif
                <a style="position: relative;" href="{{ route('client.policy.sale') }}">
                    <img loading="lazy" style="position: relative; object-fit: cover; max-width: 90%;" src="{{ asset('clients/assets/img/other/sales-policy.png') }}" alt="Chính sách bán hàng được chứng nhận">
                </a>
            </div>
        </div>

        <div class="nobifashion_footer_content_accounts">
            <p class="nobifashion_footer_content_accounts_title">Tài khoản</p>
            <div class="nobifashion_footer_content_accounts_links">
                <a href="{{ route('client.auth.login') }}">Đăng nhập</a>
                <a href="{{ route('client.auth.register') }}">Đăng ký</a>
                <a href="{{ route('client.auth.forgot-password') }}">Quên mật khẩu</a>
                <a href="@auth
                    {{ route('client.profile.index') }}
                @else
                    {{ route('client.auth.login') }}
                @endauth">Thông tin tài khoản</a>
                <a href="@auth
                    {{ route('client.order.index') }}
                @else
                    {{ route('client.auth.login') }}
                @endauth">Lịch sử đơn hàng</a>
                <a href="@auth
                    {{ route('client.favorites.index') }}
                @else
                    {{ route('client.auth.login') }}
                @endauth">Danh sách yêu thích</a>
                <a href="@auth
                    {{ route('client.profile.index') }}
                @else
                    {{ route('client.auth.login') }}
                @endauth">Địa chỉ giao hàng</a>
                <a href="@auth
                    {{ route('client.profile.index') }}
                @else
                @endauth">Thông tin thanh toán</a>
                <a href="{{ route('client.blog.index') }}">Nobi Blog</a>
                {{-- <img loading="lazy" width="50%" src="{{ asset('clients/assets/img/other/tai-khoan-da-xac-thuc.png') }}" alt="Chính sách bán hàng được chứng nhận"> --}}
            </div>
        </div>

        <div class="nobifashion_footer_content_corporate">
            <p class="nobifashion_footer_content_corporate_title">Doanh nghiệp</p>
            <div class="nobifashion_footer_content_corporate_links">
                <a href="{{ route('client.page.introduction') }}">Giới thiệu doanh nghiệp</a>
                <a href="{{ route('client.page.contact') }}">Liên hệ doanh nghiệp</a>
                <a href="{{ route('client.policy.privacy') }}">Chính sách bảo mật doanh nghiệp</a>
                <a href="{{ route('client.policy.terms') }}">Điều khoản sử dụng doanh nghiệp</a>
                <a href="{{ route('client.policy.return') }}">Chính sách đổi trả doanh nghiệp</a>
                <a href="{{ route('client.policy.delivery') }}">Chính sách vận chuyển doanh nghiệp</a>
                <a href="{{ route('client.policy.warranty') }}">Chính sách bảo hành doanh nghiệp</a>
                <a href="{{ route('client.policy.payment') }}">Chính sách thanh toán doanh nghiệp</a>
                <a href="{{ route('client.policy.privacy') }}">Chính sách bảo mật thông tin doanh nghiệp</a>
                <a href="{{ route('client.policy.privacy') }}">Chính sách bảo mật dữ liệu doanh nghiệp</a>
            </div>
        </div>

        <div class="nobifashion_footer_content_services">
            <p class="nobifashion_footer_content_services_title">Dịch vụ</p>
            <div class="nobifashion_footer_content_services_links">
                <a href="{{ route('client.page.contact') }}">Hỗ trợ khách hàng</a>
                <a href="{{ route('client.page.contact') }}">Trung tâm hỗ trợ</a>
                <a href="#">Câu hỏi thường gặp</a>
                <a href="#">Hướng dẫn sử dụng</a>
                <a href="{{ route('client.policy.payment') }}">Hướng dẫn thanh toán</a>
                <a href="{{ route('client.policy.delivery') }}">Hướng dẫn vận chuyển</a>
                <a href="{{ route('client.policy.return') }}">Hướng dẫn đổi trả</a>
                <a href="{{ route('client.policy.warranty') }}">Hướng dẫn bảo hành</a>
                <a href="{{ route('client.policy.privacy') }}">Hướng dẫn bảo mật thông tin</a>
                <a href="{{ route('client.policy.privacy') }}">Hướng dẫn bảo mật dữ liệu</a>
                <a href="{{ route('client.sitemap.html') }}">🗺️ Sitemap</a>
                <img loading="lazy" width="240" height="35" style="max-width: 100%; height: auto; object-fit: contain;" src="{{ asset('clients/assets/img/other/footer_trustbadge.jpg') }}"
                    alt="Các phương thức thanh toán được tin cậy bởi Nobifashion.vn">
            </div>
        </div>
    </div>
    <hr>
    <div class="nobifashion_footer_bottom">
        <p>{!! !empty($settings->copyright) ? Blade::render($settings->copyright) : 'Đang cập nhật...' !!}</p>
        <p>Thiết kế bởi <a href="{{ ($settings->facebook_link ?? null) ?: 'https://www.facebook.com/ducnobi2004' }}" target="_blank" rel="noopener noreferrer">Đức Nobi</a></p>
        <p>MST: {{ ($settings->site_tax_code ?? null) ?: 'Đang cập nhật...' }}</p>
    </div>
</footer>