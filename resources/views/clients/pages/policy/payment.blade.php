@extends('clients.layouts.master')

@section('title', 'Chính sách thanh toán & Hoàn tiền minh bạch | ' . renderMeta($settings->site_name ?? ($settings->subname ?? 'NOBI FASHION')))

@section('head')
    <meta name="description"
        content="{{ renderMeta('Chính sách thanh toán và hoàn tiền an toàn tại ' . ($settings->site_name ?? 'NOBI FASHION') . ' - Hỗ trợ COD, chuyển khoản QR Banking Napas247, cổng thanh toán bảo mật và cam kết hoàn tiền trong 1-3 ngày làm việc.') }}">
    <meta name="keywords" content="chính sách thanh toán, hoàn tiền, nobi fashion, thanh toán cod, chuyển khoản qr, bảo mật thanh toán, napas 247">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Chính sách thanh toán & Hoàn tiền minh bạch | {{ $settings->site_name ?? 'NOBI FASHION' }}">
    <meta property="og:description" content="Đa dạng phương thức thanh toán an toàn, bảo mật chuẩn ngân hàng và quy trình hoàn tiền nhanh chóng tại {{ $settings->site_name ?? 'NOBI FASHION' }}.">
    <meta property="og:image" content="{{ asset('clients/assets/img/business/' . (($settings->site_banner ?? null) ?: (($settings->site_logo ?? null) ?: 'banner.webp'))) }}">
    <meta property="og:image:alt" content="Chính sách thanh toán NOBI FASHION">
    <meta property="og:site_name" content="{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}">
    <meta property="og:locale" content="vi_VN">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Chính sách thanh toán & Hoàn tiền minh bạch | {{ $settings->site_name ?? 'NOBI FASHION' }}">
    <meta name="twitter:description" content="Đa dạng phương thức thanh toán an toàn, bảo mật chuẩn ngân hàng và quy trình hoàn tiền nhanh chóng tại {{ $settings->site_name ?? 'NOBI FASHION' }}.">
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
                    '@id' => route('client.policy.payment') . '#webpage',
                    'url' => route('client.policy.payment'),
                    'name' => 'Chính sách thanh toán & Hoàn tiền minh bạch | ' . (($settings->site_name ?? null) ?: 'NOBI FASHION'),
                    'description' => 'Quy định các hình thức thanh toán, bảo mật thông tin giao dịch và điều kiện hoàn tiền tại NOBI FASHION.',
                    'isPartOf' => [
                        '@id' => $siteUrl . '#website'
                    ],
                    'breadcrumb' => [
                        '@id' => route('client.policy.payment') . '#breadcrumb'
                    ],
                    'about' => [
                        '@id' => $siteUrl . '#organization'
                    ],
                    'inLanguage' => 'vi-VN'
                ],
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => route('client.policy.payment') . '#breadcrumb',
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
                            'name' => 'Chính sách thanh toán',
                            'item' => route('client.policy.payment')
                        ]
                    ]
                ],
                [
                    '@type' => 'FAQPage',
                    '@id' => route('client.policy.payment') . '#faq',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => 'Thanh toán khi nhận hàng (COD) có bị thu thêm phí không?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Không. NOBI FASHION hoàn toàn không thu phụ phí COD. Bạn chỉ cần thanh toán đúng số tiền in trên hóa đơn của đơn hàng.'
                            ]
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Thời gian hoàn tiền khi hủy đơn hoặc đổi trả mất bao lâu?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Thời gian hoàn tiền vào tài khoản ngân hàng của quý khách từ 1 – 3 ngày làm việc (không tính Thứ 7, Chủ nhật và ngày lễ).'
                            ]
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Tôi chuyển khoản nhưng quên ghi nội dung mã đơn hàng thì phải làm sao?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Bạn vui lòng chụp lại biên lai chuyển tiền thành công và liên hệ Hotline/Zalo CSKH của NOBI FASHION để nhân viên đối soát và kích hoạt đơn hàng nhanh chóng.'
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
                <li class="active" aria-current="page">Chính sách thanh toán</li>
            </ol>
        </nav>

        {{-- Hero Header --}}
        <section class="policy-hero">
            <div class="policy-tags">
                <span class="policy-tag">Thanh toán linh hoạt</span>
                <span class="policy-tag">Bảo mật chuẩn SSL</span>
                <span class="policy-tag">Hoàn tiền 1–3 ngày</span>
            </div>
            <h1>Chính sách thanh toán & hoàn tiền</h1>
            <p>
                <strong>{{ $settings->site_name ?? ($settings->subname ?? 'NOBI FASHION') }}</strong> cung cấp các phương thức thanh toán an toàn, bảo mật tuyệt đối theo tiêu chuẩn ngân hàng hiện đại. Khách hàng luôn được chủ động lựa chọn phương án thanh toán phù hợp nhất với sự bảo chứng cao nhất.
            </p>
            <div class="policy-meta">
                <div class="policy-meta-card">
                    <span>Hình thức thanh toán</span>
                    <strong>Đa phương thức</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Thời gian hoàn tiền</span>
                    <strong>1 – 3 Ngày</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Hỗ trợ kỹ thuật</span>
                    <strong>24/7</strong>
                </div>
            </div>
        </section>

        {{-- Section 1: Phương thức thanh toán --}}
        <section class="policy-section">
            <h2>1. Các phương thức thanh toán áp dụng</h2>
            <div class="policy-grid">
                <div class="policy-card">
                    <strong>1. Thanh toán khi nhận hàng (COD)</strong>
                    <p>Khách hàng kiểm tra sản phẩm khi shipper giao tới và thanh toán tiền mặt trực tiếp. Áp dụng cho 100% đơn hàng trên toàn quốc, không thu thêm phí phụ thu COD.</p>
                </div>
                <div class="policy-card">
                    <strong>2. Chuyển khoản QR Napas247</strong>
                    <p>Quét mã VietQR tự động qua ứng dụng của mọi ngân hàng (Vietcombank, MB, Techcombank, VPBank...). Hệ thống tự điền số tài khoản, số tiền và mã đơn hàng chính xác 100%.</p>
                </div>
                <div class="policy-card">
                    <strong>3. Thẻ ATM nội địa & Thẻ Quốc tế</strong>
                    <p>Hỗ trợ thanh toán qua thẻ ghi nợ nội địa Napas, thẻ Visa, MasterCard, JCB thông qua cổng thanh toán bảo mật tiêu chuẩn mã hóa quốc tế SSL/TLS 256-bit.</p>
                </div>
                <div class="policy-card">
                    <strong>4. Thanh toán tại cửa hàng</strong>
                    <p>Khách mua hàng trực tiếp tại showroom có thể thanh toán bằng tiền mặt, quẹt thẻ POS hoặc chuyển khoản QR trực tiếp tại quầy thu ngân.</p>
                </div>
            </div>
        </section>

        {{-- Section 2: Hướng dẫn chuyển khoản --}}
        <section class="policy-section">
            <h2>2. Hướng dẫn chuyển khoản qua ngân hàng</h2>
            <p>
                Đối với khách hàng lựa chọn thanh toán chuyển khoản thủ công, vui lòng thực hiện theo thông tin thụ hưởng chính thức:
            </p>
            <div class="policy-table-wrapper">
                <table class="policy-table">
                    <thead>
                        <tr>
                            <th>Tên ngân hàng</th>
                            <th>Số tài khoản</th>
                            <th>Chủ tài khoản</th>
                            <th>Nội dung chuyển khoản</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Ngân hàng TMCP Quân Đội (MB Bank)</strong></td>
                            <td><strong style="color: #ff4b6e; font-size: 16px;">{{ $settings->bank_account_number ?? '0339999999' }}</strong></td>
                            <td><strong>{{ $settings->bank_account_name ?? 'NGUYEN MINH DUC' }}</strong></td>
                            <td><code>NOBI [Mã đơn hàng] [Số điện thoại]</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="policy-note">
                ⚠️ <em>Lưu ý quan trọng:</em> Sau khi chuyển khoản thành công, hệ thống sẽ tự động xác nhận đơn trong 5 – 15 phút. Nếu có trục trặc kỹ thuật, bạn chỉ cần liên hệ Hotline CSKH để được hỗ trợ xác thực ngay tức thì.
            </div>
        </section>

        {{-- Section 3: Quy trình hoàn tiền --}}
        <section class="policy-section">
            <h2>3. Chính sách & Quy trình hoàn tiền</h2>
            <p>NOBI FASHION cam kết hoàn tiền 100% trong các trường hợp sau:</p>
            <ul class="policy-list">
                <li>Đơn hàng đã thanh toán trước nhưng sản phẩm hết hàng hoặc phát sinh lỗi chất lượng từ nhà sản xuất mà không có mẫu thay thế.</li>
                <li>Khách hàng đã trả hàng hợp lệ theo <a href="{{ route('client.policy.return') }}" style="color: #ff4b6e; font-weight: 600;">Chính sách đổi trả</a> và có nhu cầu nhận lại tiền.</li>
                <li>Khách hàng thanh toán trùng lặp đơn hàng do lỗi hệ thống mạng ngân hàng.</li>
            </ul>
            <div class="policy-timeline" style="margin-top: 18px;">
                <div class="policy-timeline-item">
                    <strong>Bước 1: Tiếp nhận yêu cầu hoàn tiền</strong><br>
                    Khách hàng gửi thông tin số tài khoản ngân hàng, tên chủ thẻ và lý do hoàn tiền cho CSKH.
                </div>
                <div class="policy-timeline-item">
                    <strong>Bước 2: Kế toán đối soát giao dịch</strong><br>
                    Phòng kế toán kiểm tra giao dịch gốc và sản phẩm hoàn về trong 24 giờ làm việc.
                </div>
                <div class="policy-timeline-item">
                    <strong>Bước 3: Thực hiện hoàn tiền</strong><br>
                    Tiền được chuyển khoản trực tiếp về số tài khoản của khách hàng trong 1 – 3 ngày làm việc kèm hóa đơn điện tử xác nhận.
                </div>
            </div>
        </section>

        {{-- Section 4: Cam kết bảo mật thanh toán --}}
        <section class="policy-section">
            <h2>4. Cam kết an toàn & Bảo mật thông tin</h2>
            <ul class="policy-list">
                <li>Hệ thống website NOBI FASHION không trực tiếp lưu trữ số thẻ ngân hàng, mã CVV/CVC của khách hàng trên máy chủ nội bộ.</li>
                <li>Mọi giao dịch trực tuyến đều được định tuyến qua các cổng thanh toán được Ngân hàng Nhà nước cấp phép, áp dụng mã hóa SSL 256-bit và xác thực 2 lớp (OTP SMS / Smart OTP).</li>
                <li>Tuyệt đối không tiết lộ thông tin thanh toán cho bất kỳ bên thứ ba nào khi không có sự đồng ý của khách hàng.</li>
            </ul>
        </section>

        {{-- Section 5: Câu hỏi thường gặp FAQ --}}
        <section class="policy-section">
            <h2>5. Câu hỏi thường gặp về thanh toán</h2>
            <div class="policy-faq-item">
                <strong>Thanh toán khi nhận hàng (COD) có bị tính thêm phí không?</strong>
                <p>Hoàn toàn không. Bạn chỉ cần thanh toán đúng số tiền đã chốt trên hóa đơn và thông báo đơn hàng của NOBI FASHION.</p>
            </div>
            <div class="policy-faq-item">
                <strong>Tôi đã chuyển khoản nhưng đơn hàng chưa cập nhật trạng thái "Đã thanh toán"?</strong>
                <p>Một số giao dịch ngân hàng ngoài giờ hành chính có thể mất từ 5-15 phút để đồng bộ. Bạn có thể gửi ảnh chụp màn hình chuyển khoản qua Hotline CSKH để nhân viên duyệt đơn ngay lập tức.</p>
            </div>
            <div class="policy-faq-item">
                <strong>Có hỗ trợ thanh toán qua thẻ tín dụng trả góp không?</strong>
                <p>NOBI FASHION hiện đang chuẩn bị tích hợp cổng trả góp 0% cho các đơn hàng giá trị cao. Chúng tôi sẽ có thông báo chính thức tới quý khách khi tính năng ra mắt.</p>
            </div>
        </section>

        {{-- Contact Support --}}
        <section class="policy-contact">
            <h3>Hỗ trợ kế toán & Tra soát thanh toán</h3>
            <p>📞 Hotline xử lý giao dịch: <a href="tel:{{ $settings->contact_phone ?? '0981985361' }}">{{ $settings->contact_phone ?? '0981985361' }}</a></p>
            <p>✉ Email kế toán: <a href="mailto:{{ $settings->contact_email ?? 'cskh@nobifashion.vn' }}">{{ $settings->contact_email ?? 'cskh@nobifashion.vn' }}</a></p>
            <p>🌐 Website chính thức: <a href="{{ config('app.url') ?? url('/') }}">{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}</a></p>
        </section>

        <p class="policy-updated">
            Chính sách thanh toán áp dụng cập nhật mới nhất từ tháng 09/2026 theo Nghị định số 52/2024/NĐ-CP về thanh toán không dùng tiền mặt và Luật Giao dịch điện tử số 20/2023/QH15.
        </p>
    </div>
@endsection
