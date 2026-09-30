@extends('clients.layouts.master')

@section('title', 'Điều khoản dịch vụ & Quy chế sử dụng website | ' . renderMeta($settings->site_name ?? ($settings->subname ?? 'NOBI FASHION')))

@section('head')
    <meta name="description"
        content="{{ renderMeta('Điều khoản sử dụng website tại ' . ($settings->site_name ?? 'NOBI FASHION') . ' - Quy định quyền và nghĩa vụ của khách hàng khi truy cập, mua sắm, thanh toán và cam kết bảo vệ quyền sở hữu trí tuệ.') }}">
    <meta name="keywords" content="điều khoản sử dụng, quy chế hoạt động, nobi fashion, quy định mua sắm, bản quyền thương hiệu, quyền người tiêu dùng">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Điều khoản dịch vụ & Quy chế sử dụng website | {{ $settings->site_name ?? 'NOBI FASHION' }}">
    <meta property="og:description" content="Quy định rõ ràng quyền và nghĩa vụ của khách hàng khi sử dụng dịch vụ và mua sắm tại {{ $settings->site_name ?? 'NOBI FASHION' }}.">
    <meta property="og:image" content="{{ asset('clients/assets/img/business/' . (($settings->site_banner ?? null) ?: (($settings->site_logo ?? null) ?: 'banner.webp'))) }}">
    <meta property="og:image:alt" content="Điều khoản sử dụng NOBI FASHION">
    <meta property="og:site_name" content="{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}">
    <meta property="og:locale" content="vi_VN">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Điều khoản dịch vụ & Quy chế sử dụng website | {{ $settings->site_name ?? 'NOBI FASHION' }}">
    <meta name="twitter:description" content="Quy định rõ ràng quyền và nghĩa vụ của khách hàng khi sử dụng dịch vụ và mua sắm tại {{ $settings->site_name ?? 'NOBI FASHION' }}.">
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
                    '@id' => route('client.policy.terms') . '#webpage',
                    'url' => route('client.policy.terms'),
                    'name' => 'Điều khoản dịch vụ & Quy chế sử dụng website | ' . (($settings->site_name ?? null) ?: 'NOBI FASHION'),
                    'description' => 'Các điều khoản và quy chế hoạt động chính thức của website thương mại điện tử NOBI FASHION.',
                    'isPartOf' => [
                        '@id' => $siteUrl . '#website'
                    ],
                    'breadcrumb' => [
                        '@id' => route('client.policy.terms') . '#breadcrumb'
                    ],
                    'about' => [
                        '@id' => $siteUrl . '#organization'
                    ],
                    'inLanguage' => 'vi-VN'
                ],
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => route('client.policy.terms') . '#breadcrumb',
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
                            'name' => 'Điều khoản sử dụng',
                            'item' => route('client.policy.terms')
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
                <li class="active" aria-current="page">Điều khoản sử dụng</li>
            </ol>
        </nav>

        {{-- Hero Header --}}
        <section class="policy-hero">
            <div class="policy-tags">
                <span class="policy-tag">Quy chế hoạt động</span>
                <span class="policy-tag">Chuẩn pháp lý</span>
                <span class="policy-tag">Minh bạch 100%</span>
            </div>
            <h1>Điều khoản dịch vụ & sử dụng</h1>
            <p>
                Chào mừng bạn đến với <strong>{{ $settings->site_name ?? ($settings->subname ?? 'NOBI FASHION') }}</strong>. Khi truy cập, đăng ký tài khoản hoặc đặt hàng tại website, bạn đồng ý tuân thủ các điều khoản hoạt động dưới đây nhằm bảo đảm quyền lợi hợp pháp cho cả hai bên.
            </p>
        </section>

        {{-- Section 1 --}}
        <section class="policy-section">
            <h2>1. Chấp nhận & Cập nhật điều khoản</h2>
            <p>
                Bằng việc duyệt web, đăng ký tài khoản hoặc tiến hành mua hàng tại website <strong>nobifashion.vn</strong>, quý khách xác nhận đã đọc, hiểu rõ và đồng ý bị ràng buộc bởi các quy định này. NOBI FASHION bảo lưu quyền sửa đổi, bổ sung nội dung bất kỳ lúc nào để phù hợp quy định pháp luật hiện hành. Phiên bản cập nhật sẽ có hiệu lực ngay khi được đăng tải công khai trên website.
            </p>
        </section>

        {{-- Section 2 --}}
        <section class="policy-section">
            <h2>2. Quyền & Trách nhiệm của khách hàng</h2>
            <ul class="policy-list">
                <li>Cung cấp thông tin cá nhân (Họ tên, Số điện thoại, Địa chỉ nhận hàng) trung thực và chuẩn xác khi tạo đơn hoặc đăng ký thành viên.</li>
                <li>Tự chịu trách nhiệm bảo mật thông tin tài khoản và mật khẩu cá nhân; thông báo ngay cho ban quản trị nếu phát hiện dấu hiệu truy cập trái phép.</li>
                <li>Không sử dụng website vào mục đích gian lận, phá hoại hạ tầng mạng, phát tán mã độc hoặc gây ảnh hưởng đến người dùng khác.</li>
                <li>Không sao chép, sử dụng lại hình ảnh, video, nội dung bài viết thuộc bản quyền của thương hiệu cho mục đích thương mại khi chưa có sự đồng ý bằng văn bản.</li>
            </ul>
        </section>

        {{-- Section 3 --}}
        <section class="policy-section">
            <h2>3. Trách nhiệm của thương hiệu NOBI FASHION</h2>
            <ul class="policy-list">
                <li>Cung cấp thông tin mô tả sản phẩm, bảng quy đổi kích cỡ (Size Chart) và giá bán niêm yết rõ ràng, minh bạch.</li>
                <li>Bảo mật thông tin cá nhân khách hàng theo đúng quy định tại <a href="{{ route('client.policy.privacy') }}" style="color: #ff4b6e; font-weight: 600;">Chính sách bảo mật</a>.</li>
                <li>Đóng gói cẩn thận, bàn giao hàng hóa cho đơn vị vận chuyển đúng tiến độ cam kết.</li>
                <li>Thực hiện đầy đủ các nghĩa vụ hậu mãi: Đổi trả hàng trong 15 ngày và bảo hành sản phẩm trong 30 ngày theo quy chế đã công bố.</li>
            </ul>
        </section>

        {{-- Section 4 --}}
        <section class="policy-section">
            <h2>4. Xác nhận đơn hàng & Giá bán</h2>
            <p>
                Đơn hàng chỉ được coi là xác lập thành công sau khi hệ thống gửi thông báo mã đơn hoặc chuyên viên CSKH liên hệ xác nhận. Giá bán hiển thị trên website là giá cuối cùng bằng đồng Việt Nam (VNĐ). Trong trường hợp hệ thống phát sinh lỗi kỹ thuật dẫn đến hiển thị sai lệch giá sản phẩm nghiêm trọng, NOBI FASHION có quyền liên hệ thông báo hủy đơn hoặc thỏa thuận lại với khách hàng trước khi xuất kho.
            </p>
        </section>

        {{-- Section 5 --}}
        <section class="policy-section">
            <h2>5. Quyền sở hữu trí tuệ & Bản quyền</h2>
            <p>
                Toàn bộ nhãn hiệu, logo NOBI FASHION, hình ảnh lookbook, clip quảng bá, giao diện thiết kế và nội dung bài viết trên website thuộc quyền sở hữu trí tuệ độc quyền của <strong>NOBI FASHION</strong> và được bảo hộ theo Luật Sở hữu trí tuệ Việt Nam. Bất kỳ hành vi sao chép, phân phối hoặc mạo danh thương hiệu đều bị nghiêm cấm và sẽ được xử lý theo quy định pháp luật.
            </p>
        </section>

        {{-- Section 6 --}}
        <section class="policy-section">
            <h2>6. Giới hạn trách nhiệm pháp lý</h2>
            <ul class="policy-list">
                <li>NOBI FASHION không chịu trách nhiệm trong các trường hợp gián đoạn kết nối Internet từ phía nhà cung cấp dịch vụ viễn thông của khách hàng.</li>
                <li>Không chịu trách nhiệm bồi thường đối với các thiệt hại gián tiếp phát sinh từ việc khách hàng tự ý làm lộ thông tin tài khoản hoặc giao dịch ngoài các kênh chính thức của thương hiệu.</li>
                <li>Các trường hợp bất khả kháng (thiên tai, dịch bệnh, chiến tranh, sự cố lưới điện quốc gia) sẽ được giải quyết dựa trên tinh thần hỗ trợ và nỗ lực tối đa từ hai phía.</li>
            </ul>
        </section>

        {{-- Section 7 --}}
        <section class="policy-section">
            <h2>7. Cơ chế giải quyết tranh chấp & Khiếu nại</h2>
            <p>
                Mọi bất đồng hoặc tranh chấp phát sinh từ giao dịch sẽ được ưu tiên thương lượng giải quyết trên tinh thần thiện chí và tôn trọng lẫn nhau. Trong trường hợp không đạt được thỏa thuận chung, vụ việc sẽ được đưa ra Tòa án có thẩm quyền tại nơi đặt trụ sở của NOBI FASHION để giải quyết theo đúng pháp luật Việt Nam.
            </p>
        </section>

        {{-- Contact Support --}}
        <section class="policy-contact">
            <h3>Thông tin liên hệ & Pháp lý</h3>
            <p>🏢 Đơn vị chủ quản: <strong>{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}</strong></p>
            <p>📞 Hotline pháp lý & hỗ trợ: <a href="tel:{{ $settings->contact_phone ?? '0981985361' }}">{{ $settings->contact_phone ?? '0981985361' }}</a></p>
            <p>✉ Email đại diện: <a href="mailto:{{ $settings->contact_email ?? 'cskh@nobifashion.vn' }}">{{ $settings->contact_email ?? 'cskh@nobifashion.vn' }}</a></p>
            <p>📍 Địa chỉ: {{ $settings->contact_address ?? 'Hải Phòng' }}</p>
        </section>

        <p class="policy-updated">
            Điều khoản sử dụng áp dụng cập nhật mới nhất từ tháng 09/2026 và tuân thủ chặt chẽ các quy định tại Luật Giao dịch điện tử số 20/2023/QH15, Luật Bảo vệ quyền lợi người tiêu dùng số 19/2023/QH15 và các văn bản quy phạm pháp luật về Thương mại điện tử hiện hành.
        </p>
    </div>
@endsection
