@extends('clients.layouts.master')

@section('title', 'Chính sách bảo mật thông tin khách hàng an toàn | ' . renderMeta($settings->site_name ?? ($settings->subname ?? 'NOBI FASHION')))

@section('head')
    <meta name="description"
        content="{{ renderMeta('Chính sách bảo mật tại ' . ($settings->site_name ?? 'NOBI FASHION') . ' - Cam kết bảo vệ dữ liệu cá nhân theo Nghị định 13/2023/NĐ-CP, minh bạch mục đích thu thập và tuyệt đối không chia sẻ cho bên thứ ba.') }}">
    <meta name="keywords" content="chính sách bảo mật, bảo vệ dữ liệu cá nhân, nobi fashion, nghị định 13, an toàn thông tin, bảo mật khách hàng">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Chính sách bảo mật thông tin khách hàng an toàn | {{ $settings->site_name ?? 'NOBI FASHION' }}">
    <meta property="og:description" content="Cam kết bảo mật dữ liệu cá nhân chuẩn Nghị định 13/2023/NĐ-CP và bảo vệ tuyệt đối quyền riêng tư của khách hàng tại {{ $settings->site_name ?? 'NOBI FASHION' }}.">
    <meta property="og:image" content="{{ asset('clients/assets/img/business/' . (($settings->site_banner ?? null) ?: (($settings->site_logo ?? null) ?: 'banner.webp'))) }}">
    <meta property="og:image:alt" content="Chính sách bảo mật thông tin NOBI FASHION">
    <meta property="og:site_name" content="{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}">
    <meta property="og:locale" content="vi_VN">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Chính sách bảo mật thông tin khách hàng an toàn | {{ $settings->site_name ?? 'NOBI FASHION' }}">
    <meta name="twitter:description" content="Cam kết bảo mật dữ liệu cá nhân chuẩn Nghị định 13/2023/NĐ-CP và bảo vệ tuyệt đối quyền riêng tư của khách hàng tại {{ $settings->site_name ?? 'NOBI FASHION' }}.">
    <meta name="twitter:image" content="{{ asset('clients/assets/img/business/' . (($settings->site_banner ?? null) ?: (($settings->site_logo ?? null) ?: 'banner.webp'))) }}">
@endsection

@section('schema')
    @php
        $siteUrl = config('app.url') ?? url('/');
        $logoUrl = asset('clients/assets/img/business/' . ($settings->site_logo ?? 'nobifashion-logo.png'));
        $bannerUrl = asset('clients/assets/img/business/' . ($settings->site_banner ?? 'banner.webp'));
        $socialLinks = array_values(array_filter([
            $settings->facebook_link ?? null,
            $settings->instagram_link ?? null,
            $settings->tiktok_link ?? null,
        ]));

        $schemaGraph = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => $siteUrl . '#website',
                    'url' => $siteUrl,
                    'name' => ($settings->site_name ?? null) ?: (($settings->subname ?? null) ?: 'NOBI FASHION'),
                    'description' => ($settings->site_description ?? null) ?: 'Thương hiệu thời trang nam hiện đại chuẩn phom dáng người Việt',
                    'publisher' => [
                        '@id' => $siteUrl . '#organization'
                    ],
                    'inLanguage' => 'vi-VN'
                ],
                [
                    '@type' => 'WebPage',
                    '@id' => route('client.policy.privacy') . '#webpage',
                    'url' => route('client.policy.privacy'),
                    'name' => 'Chính sách bảo mật thông tin khách hàng an toàn | ' . (($settings->site_name ?? null) ?: 'NOBI FASHION'),
                    'description' => 'Chính sách thu thập, xử lý và bảo vệ dữ liệu cá nhân theo quy định pháp luật Việt Nam tại NOBI FASHION.',
                    'isPartOf' => [
                        '@id' => $siteUrl . '#website'
                    ],
                    'breadcrumb' => [
                        '@id' => route('client.policy.privacy') . '#breadcrumb'
                    ],
                    'about' => [
                        '@id' => $siteUrl . '#organization'
                    ],
                    'inLanguage' => 'vi-VN'
                ],
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => route('client.policy.privacy') . '#breadcrumb',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Trang chủ',
                            'item' => route('client.home.index')
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'Chính sách bảo mật',
                            'item' => route('client.policy.privacy')
                        ]
                    ]
                ],
                [
                    '@type' => ['ClothingStore', 'Organization'],
                    '@id' => $siteUrl . '#organization',
                    'name' => ($settings->site_name ?? null) ?: (($settings->subname ?? null) ?: 'NOBI FASHION'),
                    'alternateName' => ($settings->subname ?? null) ?: 'NOBI FASHION VIỆT NAM',
                    'url' => $siteUrl,
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => $logoUrl,
                        'caption' => 'Logo NOBI FASHION'
                    ],
                    'image' => $bannerUrl,
                    'telephone' => ($settings->contact_phone ?? null) ?: '0981985361',
                    'email' => ($settings->contact_email ?? null) ?: 'cskh@nobifashion.vn',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'streetAddress' => ($settings->contact_address ?? null) ?: 'Hải Phòng',
                        'addressLocality' => ($settings->city ?? null) ?: 'Hải Phòng',
                        'addressRegion' => ($settings->city ?? null) ?: 'Hải Phòng',
                        'postalCode' => '180000',
                        'addressCountry' => 'VN'
                    ],
                    'sameAs' => $socialLinks
                ]
            ]
        ];
    @endphp
    <script type="application/ld+json">
        {!! json_encode($schemaGraph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endsection

@push('styles')
    @include('clients.pages.policy.partials.styles')
@endpush

@section('content')
    <div class="policy-page">
        {{-- Breadcrumb --}}
        <nav class="policy-breadcrumb" aria-label="Breadcrumb">
            <ol>
                <li>
                    <a href="{{ route('client.home.index') }}">Trang chủ</a>
                    <span class="separator">/</span>
                </li>
                <li class="active" aria-current="page">Chính sách bảo mật</li>
            </ol>
        </nav>

        {{-- Hero Header --}}
        <section class="policy-hero">
            <div class="policy-tags">
                <span class="policy-tag">Bảo mật dữ liệu</span>
                <span class="policy-tag">Nghị định 13/2023/NĐ-CP</span>
                <span class="policy-tag">Mã hóa SSL 256-bit</span>
            </div>
            <h1>Chính sách bảo mật thông tin</h1>
            <p>
                <strong>{{ $settings->site_name ?? ($settings->subname ?? 'NOBI FASHION') }}</strong> cam kết bảo vệ tuyệt đối sự riêng tư và dữ liệu cá nhân của quý khách. Chúng tôi tuân thủ nghiêm ngặt các quy định của pháp luật Việt Nam, đặc biệt là <strong>Nghị định 13/2023/NĐ-CP</strong> về bảo vệ dữ liệu cá nhân.
            </p>
            <div class="policy-meta">
                <div class="policy-meta-card">
                    <span>Mã hóa đường truyền</span>
                    <strong>100% SSL/TLS</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Chia sẻ bên thứ 3</span>
                    <strong>Không</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Quyền chủ thể dữ liệu</span>
                    <strong>Được bảo đảm</strong>
                </div>
            </div>
        </section>

        {{-- Section 1 --}}
        <section class="policy-section">
            <h2>1. Mục đích & Loại dữ liệu thu thập</h2>
            <p>Để phục vụ quá trình tư vấn size, xử lý đơn hàng và giao nhận, chúng tôi chỉ thu thập các thông tin tối thiểu cần thiết bao gồm:</p>
            <ul class="policy-list">
                <li><strong>Thông tin định danh:</strong> Họ và tên, giới tính.</li>
                <li><strong>Thông tin liên lạc:</strong> Số điện thoại di động, địa chỉ giao nhận hàng, địa chỉ email.</li>
                <li><strong>Lịch sử đơn hàng:</strong> Sản phẩm đã mua, kích cỡ, màu sắc, phương thức thanh toán đã lựa chọn.</li>
                <li><strong>Dữ liệu kỹ thuật:</strong> Địa chỉ IP, dữ liệu cookie trình duyệt nhằm tối ưu tốc độ tải trang và ghi nhớ giỏ hàng mua sắm.</li>
            </ul>
        </section>

        {{-- Section 2 --}}
        <section class="policy-section">
            <h2>2. Phạm vi & Mục đích sử dụng dữ liệu</h2>
            <p>Dữ liệu khách hàng chỉ được sử dụng cho các mục đích hợp pháp sau đây:</p>
            <ul class="policy-list">
                <li>Xác nhận đơn hàng, liên hệ điều phối giao hàng và thực hiện chính sách đổi trả, bảo hành sản phẩm.</li>
                <li>Gửi thông báo cập nhật hành trình vận đơn qua tin nhắn SMS, Zalo hoặc Email.</li>
                <li>Gửi các chương trình ưu đãi tri ân, mã giảm giá sinh nhật (chỉ khi có sự đồng ý của khách hàng).</li>
                <li>Nâng cấp hệ thống website, phát hiện và ngăn chặn các hành vi gian lận hoặc tấn công mạng.</li>
            </ul>
        </section>

        {{-- Section 3 --}}
        <section class="policy-section">
            <h2>3. Các đơn vị được phép tiếp cận thông tin</h2>
            <p>NOBI FASHION cam kết <strong>không bán, không cho thuê và không chia sẻ</strong> dữ liệu khách hàng cho bên thứ ba vì mục đích thương mại. Thông tin chỉ được chia sẻ trong phạm vi cần thiết cho các đơn vị sau:</p>
            <ul class="policy-list">
                <li><strong>Đối tác vận chuyển:</strong> Giao Hàng Nhanh, Giao Hàng Tiết Kiệm, Viettel Post (chỉ cung cấp tên, số điện thoại và địa chỉ nhận hàng để shipper liên hệ giao bưu kiện).</li>
                <li><strong>Cổng thanh toán điện tử:</strong> Các ngân hàng và cổng thanh toán được cấp phép (chỉ phục vụ xác thực đối soát thanh toán trực tuyến).</li>
                <li><strong>Cơ quan chức năng:</strong> Khi có yêu cầu bằng văn bản chính thức từ cơ quan quản lý nhà nước có thẩm quyền theo quy định của pháp luật.</li>
            </ul>
        </section>

        {{-- Section 4 --}}
        <section class="policy-section">
            <h2>4. Thời hạn lưu trữ & Biện pháp an toàn kỹ thuật</h2>
            <ul class="policy-list">
                <li>Dữ liệu cá nhân được lưu trữ an toàn trên hệ thống máy chủ đặt tại Việt Nam cho đến khi khách hàng có yêu cầu hủy bỏ hoặc theo quy định lưu trữ chứng từ kế toán của pháp luật.</li>
                <li>Áp dụng công nghệ mã hóa dữ liệu đường truyền <strong>SSL 256-bit</strong>, tường lửa WAF và hệ thống giám sát an ninh mạng 24/7 nhằm ngăn chặn truy cập trái phép.</li>
                <li>Quy định phân quyền nội bộ chặt chẽ: Chỉ nhân sự được giao nhiệm vụ mới có quyền truy cập dữ liệu để phục vụ việc giao nhận và chăm sóc khách hàng.</li>
            </ul>
        </section>

        {{-- Section 5 --}}
        <section class="policy-section">
            <h2>5. Quyền của chủ thể dữ liệu (Khách hàng)</h2>
            <p>Theo Nghị định 13/2023/NĐ-CP, quý khách có đầy đủ các quyền sau đối với dữ liệu cá nhân của mình:</p>
            <ul class="policy-list">
                <li><strong>Quyền được biết & đồng ý:</strong> Được biết rõ mục đích, loại dữ liệu thu thập và cách thức xử lý.</li>
                <li><strong>Quyền truy cập & chỉnh sửa:</strong> Yêu cầu xem, trích xuất hoặc chỉnh sửa thông tin cá nhân trong tài khoản bất kỳ lúc nào.</li>
                <li><strong>Quyền yêu cầu xóa dữ liệu:</strong> Yêu cầu NOBI FASHION xóa vĩnh viễn dữ liệu cá nhân khi không còn nhu cầu mua sắm.</li>
                <li><strong>Quyền từ chối nhận quảng cáo:</strong> Hủy đăng ký nhận tin khuyến mãi qua nút Unsubscribe trong email hoặc liên hệ CSKH.</li>
            </ul>
        </section>

        {{-- Contact Support --}}
        <section class="policy-contact">
            <h3>Bộ phận phụ trách bảo vệ dữ liệu cá nhân (DPO)</h3>
            <p>📞 Hotline tiếp nhận bảo mật: <a href="tel:{{ $settings->contact_phone ?? '0981985361' }}">{{ $settings->contact_phone ?? '0981985361' }}</a></p>
            <p>✉ Email chuyên trách: <a href="mailto:{{ $settings->contact_email ?? 'cskh@nobifashion.vn' }}">{{ $settings->contact_email ?? 'cskh@nobifashion.vn' }}</a></p>
            <p>🏢 Địa chỉ văn phòng: {{ $settings->contact_address ?? 'Hải Phòng' }}</p>
        </section>

        <p class="policy-updated">
            Chính sách bảo mật này áp dụng cập nhật mới nhất từ tháng 09/2026 và tuân thủ chặt chẽ theo Nghị định số 13/2023/NĐ-CP của Chính phủ về bảo vệ dữ liệu cá nhân, Luật An ninh mạng và Luật Giao dịch điện tử số 20/2023/QH15.
        </p>
    </div>
@endsection
