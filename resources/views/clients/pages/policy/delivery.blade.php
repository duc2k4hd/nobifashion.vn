@extends('clients.layouts.master')

@section('title', 'Chính sách giao hàng & Vận chuyển toàn quốc | ' . renderMeta($settings->site_name ?? ($settings->subname ?? 'NOBI FASHION')))

@section('head')
    <meta name="description"
        content="{{ renderMeta('Chính sách giao hàng toàn quốc từ ' . ($settings->site_name ?? 'NOBI FASHION') . ' - Biểu phí vận chuyển minh bạch, thời gian giao hàng nhanh chóng, quy trình đồng kiểm trước khi thanh toán.') }}">
    <meta name="keywords" content="chính sách giao hàng, vận chuyển toàn quốc, nobi fashion, phí ship, thời gian giao hàng, đồng kiểm hàng, freeship">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Chính sách giao hàng & Vận chuyển toàn quốc | {{ $settings->site_name ?? 'NOBI FASHION' }}">
    <meta property="og:description" content="Biểu phí vận chuyển minh bạch, thời gian giao hàng hỏa tốc và quy trình đồng kiểm bảo đảm quyền lợi khách hàng tại {{ $settings->site_name ?? 'NOBI FASHION' }}.">
    <meta property="og:image" content="{{ asset('clients/assets/img/business/' . (($settings->site_banner ?? null) ?: (($settings->site_logo ?? null) ?: 'banner.webp'))) }}">
    <meta property="og:image:alt" content="Chính sách giao hàng NOBI FASHION">
    <meta property="og:site_name" content="{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}">
    <meta property="og:locale" content="vi_VN">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Chính sách giao hàng & Vận chuyển toàn quốc | {{ $settings->site_name ?? 'NOBI FASHION' }}">
    <meta name="twitter:description" content="Biểu phí vận chuyển minh bạch, thời gian giao hàng hỏa tốc và quy trình đồng kiểm bảo đảm quyền lợi khách hàng tại {{ $settings->site_name ?? 'NOBI FASHION' }}.">
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
                    '@id' => route('client.policy.delivery') . '#webpage',
                    'url' => route('client.policy.delivery'),
                    'name' => 'Chính sách giao hàng & Vận chuyển toàn quốc | ' . (($settings->site_name ?? null) ?: 'NOBI FASHION'),
                    'description' => 'Biểu phí giao hàng minh bạch, quy định đồng kiểm và thời gian vận chuyển đơn hàng trên toàn quốc tại NOBI FASHION.',
                    'isPartOf' => [
                        '@id' => $siteUrl . '#website'
                    ],
                    'breadcrumb' => [
                        '@id' => route('client.policy.delivery') . '#breadcrumb'
                    ],
                    'about' => [
                        '@id' => $siteUrl . '#organization'
                    ],
                    'inLanguage' => 'vi-VN'
                ],
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => route('client.policy.delivery') . '#breadcrumb',
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
                            'name' => 'Chính sách giao hàng',
                            'item' => route('client.policy.delivery')
                        ]
                    ]
                ],
                [
                    '@type' => 'FAQPage',
                    '@id' => route('client.policy.delivery') . '#faq',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => 'Tôi có được kiểm tra sản phẩm trước khi thanh toán không?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Có. NOBI FASHION hỗ trợ chính sách đồng kiểm hàng trước khi thanh toán cho nhân viên giao hàng trên toàn quốc (kiểm tra ngoại quan, mẫu mã, màu sắc và kích cỡ).'
                            ]
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Đơn hàng bao nhiêu thì được miễn phí vận chuyển (Freeship)?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'NOBI FASHION miễn phí vận chuyển toàn quốc cho mọi đơn hàng có giá trị thanh toán từ 499.000 VNĐ trở lên.'
                            ]
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Thời gian nhận hàng thông thường mất bao lâu?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Khu vực nội thành Hải Phòng, Hà Nội nhận trong 4 - 24 giờ. Các tỉnh thành khác trên toàn quốc từ 2 - 4 ngày làm việc tuỳ khu vực.'
                            ]
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
                <li class="active" aria-current="page">Chính sách giao hàng</li>
            </ol>
        </nav>

        {{-- Hero Header --}}
        <section class="policy-hero">
            <div class="policy-tags">
                <span class="policy-tag">Giao hàng toàn quốc</span>
                <span class="policy-tag">Đồng kiểm khi nhận</span>
                <span class="policy-tag">Freeship từ 499K</span>
            </div>
            <h1>Chính sách giao hàng & vận chuyển</h1>
            <p>
                <strong>{{ $settings->site_name ?? ($settings->subname ?? 'NOBI FASHION') }}</strong> hợp tác cùng các đối tác vận chuyển chuyên nghiệp hàng đầu Việt Nam (Giao Hàng Nhanh, Giao Hàng Tiết Kiệm, Viettel Post) nhằm đảm bảo từng kiện hàng tới tay khách hàng nhanh chóng, nguyên vẹn và an toàn tuyệt đối.
            </p>
            <div class="policy-meta">
                <div class="policy-meta-card">
                    <span>Phủ sóng giao nhận</span>
                    <strong>Toàn quốc</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Miễn phí vận chuyển</span>
                    <strong>Từ 499.000đ</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Chính sách đổi hàng</span>
                    <strong>15 Ngày</strong>
                </div>
            </div>
        </section>

        {{-- Section 1: Biểu phí vận chuyển --}}
        <section class="policy-section">
            <h2>1. Biểu phí vận chuyển minh bạch</h2>
            <p>
                Phí vận chuyển được hệ thống tính toán tự động dựa trên giá trị đơn hàng và khu vực giao nhận, hiển thị công khai ở bước Thanh toán trước khi bạn xác nhận đặt hàng:
            </p>
            <div class="policy-table-wrapper">
                <table class="policy-table">
                    <thead>
                        <tr>
                            <th>Giá trị đơn hàng</th>
                            <th>Khu vực giao hàng</th>
                            <th>Mức phí áp dụng</th>
                            <th>Thời gian dự kiến</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Từ 499.000đ trở lên</strong></td>
                            <td>Toàn quốc (Mọi tỉnh, thành phố)</td>
                            <td><strong style="color: #10b981;">MIỄN PHÍ (0đ)</strong></td>
                            <td>Theo tiêu chuẩn vùng</td>
                        </tr>
                        <tr>
                            <td>Dưới 499.000đ</td>
                            <td>Nội thành Hải Phòng</td>
                            <td><strong>20.000đ</strong></td>
                            <td>4 – 24 giờ</td>
                        </tr>
                        <tr>
                            <td>Dưới 499.000đ</td>
                            <td>Hà Nội & TP. Hồ Chí Minh</td>
                            <td><strong>25.000đ</strong></td>
                            <td>1 – 2 ngày</td>
                        </tr>
                        <tr>
                            <td>Dưới 499.000đ</td>
                            <td>Các tỉnh, thành phố khác</td>
                            <td><strong>30.000đ</strong></td>
                            <td>2 – 4 ngày</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="policy-note">
                💡 <strong>Ưu đãi thành viên:</strong> Các chương trình Flash Sale hoặc tri ân đặc biệt có thể áp dụng mã FreeShip độc quyền được công bố trực tiếp tại giỏ hàng.
            </div>
        </section>

        {{-- Section 2: Thời gian giao hàng --}}
        <section class="policy-section">
            <h2>2. Thời gian giao hàng dự kiến</h2>
            <div class="policy-timeline">
                <div class="policy-timeline-item">
                    <strong>Nội thành Hải Phòng:</strong> Giao trong vòng 4 – 12 giờ làm việc (hỗ trợ giao gấp trong ngày nếu đặt trước 16h00).
                </div>
                <div class="policy-timeline-item">
                    <strong>Hà Nội & TP. Hồ Chí Minh:</strong> Từ 1 – 2 ngày làm việc kể từ thời điểm bàn giao bưu cục.
                </div>
                <div class="policy-timeline-item">
                    <strong>Các tỉnh/thành phố trung tâm khác:</strong> Từ 2 – 3 ngày làm việc.
                </div>
                <div class="policy-timeline-item">
                    <strong>Huyện, xã vùng xa, biên giới, hải đảo:</strong> Từ 3 – 5 ngày làm việc tuỳ thuộc điều kiện di chuyển thực tế.
                </div>
            </div>
            <div class="policy-note">
                ⚠️ <em>Lưu ý:</em> Thời gian giao hàng không tính Chủ nhật và các ngày lễ Tết theo quy định nhà nước. Trong các trường hợp thiên tai, thời tiết cực đoan hoặc dịch bệnh, thời gian giao hàng có thể kéo dài hơn, bộ phận CSKH sẽ chủ động nhắn tin/gọi điện thông báo tới bạn.
            </div>
        </section>

        {{-- Section 3: Quy định đồng kiểm hàng --}}
        <section class="policy-section">
            <h2>3. Quyền lợi đồng kiểm khi nhận hàng</h2>
            <p>
                Để khách hàng hoàn toàn an tâm khi mua sắm online, NOBI FASHION áp dụng chính sách <strong>ĐỒNG KIỂM HÀNG</strong> trước khi thanh toán cho shipper:
            </p>
            <ul class="policy-list">
                <li>Khách hàng được mở gói hàng kiểm tra ngoại quan sản phẩm: đúng mẫu mã, màu sắc, số lượng, kích cỡ theo đơn đã đặt.</li>
                <li>Kiểm tra tình trạng vật lý của sản phẩm: tem mác nguyên vẹn, cúc áo, khóa kéo và đường may không bị rách, sờn bẩn.</li>
                <li><em>Lưu ý:</em> Không hỗ trợ thử đồ tại chỗ để đảm bảo vệ sinh sản phẩm và bảo vệ quyền lợi chung.</li>
                <li>Nếu sản phẩm không đúng đơn hoặc có dấu hiệu móp rách, bạn hoàn toàn có quyền <strong>từ chối nhận hàng</strong> mà không mất bất kỳ khoản phí nào.</li>
            </ul>
        </section>

        {{-- Section 4: Quy trình xử lý sự cố --}}
        <section class="policy-section">
            <h2>4. Xử lý sự cố giao nhận & Bảo hiểm hàng hóa</h2>
            <div class="policy-grid">
                <div class="policy-card">
                    <strong>Hàng thất lạc / Bể vỡ</strong>
                    <p>100% đơn hàng gửi đi đều có bảo hiểm. Nếu đơn bị thất lạc do bưu cục, NOBI FASHION sẽ gửi đơn mới ngay lập tức hoặc hoàn tiền 100% cho bạn.</p>
                </div>
                <div class="policy-card">
                    <strong>Giao không thành công</strong>
                    <p>Đơn vị vận chuyển sẽ liên hệ giao hàng tối đa 03 lần vào các khung giờ khác nhau trước khi chuyển trạng thái lưu kho bưu cục.</p>
                </div>
                <div class="policy-card">
                    <strong>Đổi địa chỉ nhận hàng</strong>
                    <p>Vui lòng liên hệ hotline CSKH trong vòng 2 giờ sau khi đặt để cập nhật địa chỉ trước khi đơn được bàn giao cho đối tác vận chuyển.</p>
                </div>
                <div class="policy-card">
                    <strong>Hỗ trợ đổi trả 15 ngày</strong>
                    <p>Nếu mặc không vừa hoặc muốn đổi kiểu dáng, NOBI FASHION hỗ trợ đổi size/mẫu tận nhà trong 15 ngày với quy trình cực kỳ tiện lợi.</p>
                </div>
            </div>
        </section>

        {{-- Section 5: Câu hỏi thường gặp FAQ --}}
        <section class="policy-section">
            <h2>5. Câu hỏi thường gặp về giao hàng</h2>
            <div class="policy-faq-item">
                <strong>Tôi có được kiểm tra sản phẩm trước khi thanh toán không?</strong>
                <p>Có. Bạn được mở gói bưu kiện kiểm tra số lượng, mẫu mã, màu sắc và ngoại quan sản phẩm trước khi thanh toán tiền cho nhân viên giao hàng.</p>
            </div>
            <div class="policy-faq-item">
                <strong>Tôi muốn nhận hàng vào khung giờ cố định có được không?</strong>
                <p>Bạn có thể ghi chú khung giờ thuận tiện khi đặt hàng (ví dụ: giao giờ hành chính). Nhân viên giao hàng sẽ gọi điện hẹn trước khi tới giao.</p>
            </div>
            <div class="policy-faq-item">
                <strong>Nếu nhận hàng mà sản phẩm bị lỗi do vận chuyển thì xử lý thế nào?</strong>
                <p>Bạn chỉ cần chụp ảnh gói hàng và liên hệ Hotline/Zalo CSKH. NOBI FASHION sẽ gửi sản phẩm mới đổi tận nhà miễn phí 100% cho bạn trong 24-48 giờ.</p>
            </div>
        </section>

        {{-- Contact Support --}}
        <section class="policy-contact">
            <h3>Trung tâm chăm sóc & hỗ trợ đơn hàng</h3>
            <p>📞 Hotline hỗ trợ giao nhận: <a href="tel:{{ $settings->contact_phone ?? '0981985361' }}">{{ $settings->contact_phone ?? '0981985361' }}</a></p>
            <p>✉ Email hỗ trợ: <a href="mailto:{{ $settings->contact_email ?? 'cskh@nobifashion.vn' }}">{{ $settings->contact_email ?? 'cskh@nobifashion.vn' }}</a></p>
            <p>🌐 Website chính thức: <a href="{{ config('app.url') ?? url('/') }}">{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}</a></p>
            <p>⏰ Thời gian làm việc: 08:30 – 22:00 (Tất cả các ngày trong tuần, kể cả Thứ 7 & CN)</p>
        </section>

        <p class="policy-updated">
            Chính sách giao hàng áp dụng cập nhật mới nhất từ tháng 09/2026 theo quy định của Luật Bảo vệ quyền lợi người tiêu dùng số 19/2023/QH15 và các quy chuẩn dịch vụ thương mại điện tử hiện hành.
        </p>
    </div>
@endsection
