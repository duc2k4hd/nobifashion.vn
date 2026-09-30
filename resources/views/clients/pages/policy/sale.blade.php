@extends('clients.layouts.master')

@section('title', 'Chính sách bán hàng & Cam kết chất lượng | ' . renderMeta($settings->site_name ?? ($settings->subname ?? 'NOBI FASHION')))

@section('head')
    <meta name="description"
        content="{{ renderMeta('Chính sách bán hàng tại ' . ($settings->site_name ?? 'NOBI FASHION') . ' - Cam kết sản phẩm chính hãng 100%, giá niêm yết minh bạch, quyền lợi thành viên VIP, tư vấn tận tâm và hậu mãi uy tín.') }}">
    <meta name="keywords" content="chính sách bán hàng, cam kết chất lượng, nobi fashion, thời trang nam chính hãng, quyền lợi thành viên, dịch vụ khách hàng">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Chính sách bán hàng & Cam kết chất lượng | {{ $settings->site_name ?? 'NOBI FASHION' }}">
    <meta property="og:description" content="Cam kết sản phẩm chuẩn phom dáng người Việt, chất lượng bền đẹp và trải nghiệm dịch vụ khách hàng cao cấp tại {{ $settings->site_name ?? 'NOBI FASHION' }}.">
    <meta property="og:image" content="{{ asset('clients/assets/img/business/' . (($settings->site_banner ?? null) ?: (($settings->site_logo ?? null) ?: 'banner.webp'))) }}">
    <meta property="og:image:alt" content="Chính sách bán hàng NOBI FASHION">
    <meta property="og:site_name" content="{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}">
    <meta property="og:locale" content="vi_VN">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Chính sách bán hàng & Cam kết chất lượng | {{ $settings->site_name ?? 'NOBI FASHION' }}">
    <meta name="twitter:description" content="Cam kết sản phẩm chuẩn phom dáng người Việt, chất lượng bền đẹp và trải nghiệm dịch vụ khách hàng cao cấp tại {{ $settings->site_name ?? 'NOBI FASHION' }}.">
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
                    '@id' => route('client.policy.sale') . '#webpage',
                    'url' => route('client.policy.sale'),
                    'name' => 'Chính sách bán hàng & Cam kết chất lượng | ' . (($settings->site_name ?? null) ?: 'NOBI FASHION'),
                    'description' => 'Cam kết chất lượng thời trang nam, quyền lợi khách hàng và chuẩn mực phục vụ chuyên nghiệp tại NOBI FASHION.',
                    'isPartOf' => [
                        '@id' => $siteUrl . '#website'
                    ],
                    'breadcrumb' => [
                        '@id' => route('client.policy.sale') . '#breadcrumb'
                    ],
                    'about' => [
                        '@id' => $siteUrl . '#organization'
                    ],
                    'inLanguage' => 'vi-VN'
                ],
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => route('client.policy.sale') . '#breadcrumb',
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
                            'name' => 'Chính sách bán hàng',
                            'item' => route('client.policy.sale')
                        ]
                    ]
                ],
                [
                    '@type' => 'FAQPage',
                    '@id' => route('client.policy.sale') . '#faq',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => 'Sản phẩm của NOBI FASHION có xuất xứ từ đâu?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => '100% sản phẩm được thiết kế và sản xuất trực tiếp tại Việt Nam, trải qua quy trình kiểm soát chất lượng may mặc nghiêm ngặt nhằm mang lại phom dáng chuẩn nhất cho người Việt.'
                            ]
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Làm thế nào để được hưởng ưu đãi khách hàng VIP?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Khách hàng có tổng chi tiêu tích lũy từ 3.000.000đ sẽ được nâng hạng VIP Silver và từ 5.000.000đ nâng hạng VIP Gold với mức chiết khấu giảm trực tiếp từ 5% – 10% cho mọi đơn hàng tiếp theo.'
                            ]
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Giá bán trên website đã bao gồm thuế VAT chưa?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Giá niêm yết trên website là giá bán chính thức cuối cùng. Nếu quý khách là doanh nghiệp cần xuất hóa đơn VAT điện tử, vui lòng cung cấp thông tin xuất hóa đơn khi đặt hàng.'
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
                <li class="active" aria-current="page">Chính sách bán hàng</li>
            </ol>
        </nav>

        {{-- Hero Header --}}
        <section class="policy-hero">
            <div class="policy-tags">
                <span class="policy-tag">Chính hãng 100%</span>
                <span class="policy-tag">Giá niêm yết</span>
                <span class="policy-tag">Đặc quyền VIP</span>
            </div>
            <h1>Chính sách bán hàng & Cam kết chất lượng</h1>
            <p>
                <strong>{{ $settings->site_name ?? ($settings->subname ?? 'NOBI FASHION') }}</strong> định hình phong cách thời trang nam hiện đại, tinh giản và thanh lịch. Chúng tôi cam kết chất lượng sản phẩm chuẩn phom dáng người Việt, thông tin minh bạch và trải nghiệm dịch vụ khách hàng tận tâm nhất.
            </p>
            <div class="policy-meta">
                <div class="policy-meta-card">
                    <span>Cam kết chất lượng</span>
                    <strong>100% Chính hãng</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Miễn phí giao hàng</span>
                    <strong>Từ 499.000đ</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Chăm sóc khách hàng</span>
                    <strong>Tận tâm 24/7</strong>
                </div>
            </div>
        </section>

        {{-- Section 1: Cam kết chất lượng --}}
        <section class="policy-section">
            <h2>1. Cam kết chất lượng sản phẩm</h2>
            <ul class="policy-list">
                <li><strong>Phom dáng chuẩn người Việt:</strong> Mọi mẫu thiết kế đều được nghiên cứu kỹ lưỡng về tỷ lệ hình thể nam giới Việt Nam, mang lại sự vừa vặn, thoải mái và tôn dáng tối đa.</li>
                <li><strong>Chất liệu tuyển chọn:</strong> Ưu tiên các dòng sợi tự nhiên như Cotton Compact, Modal, Bamboo và sợi Spandex co giãn đa chiều, đảm bảo thoáng mát, thấm hút mồ hôi và giữ phom bền bỉ sau nhiều lần giặt.</li>
                <li><strong>Hình ảnh chân thực:</strong> 100% hình ảnh sản phẩm được chụp trực tiếp tại studio hoặc lookbook thực tế của thương hiệu, độ chuẩn xác màu sắc đạt 95% – 100% so với thực tế.</li>
                <li><strong>Nói không với hàng giả:</strong> Tuyệt đối không kinh doanh hàng lỗi, hàng tồn kho kém chất lượng hay hàng gia công không rõ nguồn gốc.</li>
            </ul>
        </section>

        {{-- Section 2: Niêm yết giá & Chương trình khuyến mãi --}}
        <section class="policy-section">
            <h2>2. Chính sách niêm yết giá & Minh bạch khuyến mãi</h2>
            <div class="policy-grid">
                <div class="policy-card">
                    <strong>Giá niêm yết đồng bộ</strong>
                    <p>Giá hiển thị trên website, sàn thương mại điện tử và tại hệ thống cửa hàng là đồng nhất, không tự ý tăng giá trong các dịp lễ Tết.</p>
                </div>
                <div class="policy-card">
                    <strong>Chương trình ưu đãi thật</strong>
                    <p>Mọi chương trình giảm giá, Flash Sale đều dựa trên giá trị thực của sản phẩm, không nâng giá ảo rồi giảm sâu gây hiểu lầm cho khách hàng.</p>
                </div>
                <div class="policy-card">
                    <strong>Hóa đơn VAT điện tử</strong>
                    <p>Sẵn sàng xuất hóa đơn tài chính điện tử hợp pháp theo đúng quy định của Tổng cục Thuế đối với mọi giao dịch phát sinh.</p>
                </div>
                <div class="policy-card">
                    <strong>Voucher giảm giá</strong>
                    <p>Các mã giảm giá tri ân, voucher sinh nhật được trừ trực tiếp vào giá trị đơn hàng một cách rõ ràng và minh bạch.</p>
                </div>
            </div>
        </section>

        {{-- Section 3: Quyền lợi thành viên VIP --}}
        <section class="policy-section">
            <h2>3. Chương trình Khách hàng thân thiết (VIP Membership)</h2>
            <p>
                Mọi đơn hàng thành công của bạn đều được tự động cộng dồn doanh số tích lũy gắn liền với số điện thoại để hưởng đặc quyền trọn đời:
            </p>
            <div class="policy-table-wrapper">
                <table class="policy-table">
                    <thead>
                        <tr>
                            <th>Hạng thành viên</th>
                            <th>Mức chi tiêu tích lũy</th>
                            <th>Đặc quyền ưu đãi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Member (Mặc định)</strong></td>
                            <td>Từ đơn hàng đầu tiên</td>
                            <td>Tích điểm đổi voucher, nhận thông báo sự kiện sớm.</td>
                        </tr>
                        <tr>
                            <td><strong>VIP Silver</strong></td>
                            <td>Từ 3.000.000đ</td>
                            <td><strong>Giảm 5%</strong> trọn đời cho mọi đơn hàng + Quà tặng sinh nhật.</td>
                        </tr>
                        <tr>
                            <td><strong>VIP Gold</strong></td>
                            <td>Từ 5.000.000đ</td>
                            <td><strong>Giảm 10%</strong> trọn đời + Miễn phí vận chuyển toàn quốc không giới hạn.</td>
                        </tr>
                        <tr>
                            <td><strong>VIP Diamond</strong></td>
                            <td>Từ 10.000.000đ</td>
                            <td><strong>Giảm 15%</strong> trọn đời + Hộp quà tri ân Tết + Stylist tư vấn riêng.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Section 4: Tư vấn & Chăm sóc khách hàng --}}
        <section class="policy-section">
            <h2>4. Tiêu chuẩn tư vấn & Chăm sóc khách hàng</h2>
            <ul class="policy-list">
                <li><strong>Tư vấn size chuẩn xác:</strong> Đội ngũ tư vấn viên am hiểu thông số chiều cao – cân nặng và form dáng từng chất liệu, giúp khách hàng chọn đúng size ngay từ lần đầu tiên.</li>
                <li><strong>Gợi ý phối đồ (Mix & Match):</strong> Sẵn sàng hỗ trợ tư vấn trang phục theo từng hoàn cảnh: công sở, dạo phố, hẹn hò hoặc sự kiện trang trọng.</li>
                <li><strong>Bảo mật thông tin:</strong> Tôn trọng sự riêng tư và bảo vệ thông tin cá nhân khách hàng theo đúng quy chuẩn pháp lý.</li>
            </ul>
        </section>

        {{-- Section 5: Câu hỏi thường gặp FAQ --}}
        <section class="policy-section">
            <h2>5. Câu hỏi thường gặp về mua sắm</h2>
            <div class="policy-faq-item">
                <strong>Tôi muốn mua số lượng lớn làm đồng phục công ty thì có chiết khấu không?</strong>
                <p>Có. NOBI FASHION có chính sách chiết khấu linh hoạt từ 15% – 30% cho các đơn hàng đồng phục doanh nghiệp, trường học kèm dịch vụ in/thêu logo chuyên nghiệp.</p>
            </div>
            <div class="policy-faq-item">
                <strong>Nếu nhận hàng mà sản phẩm không giống như ảnh chụp thì sao?</strong>
                <p>Bạn có toàn quyền từ chối nhận hàng ngay khi đồng kiểm cùng shipper. Nếu đã nhận, bạn được hỗ trợ đổi trả hoặc hoàn tiền 100% trong 15 ngày.</p>
            </div>
        </section>

        {{-- Contact Support --}}
        <section class="policy-contact">
            <h3>Liên hệ bộ phận chăm sóc khách hàng</h3>
            <p>📞 Hotline bán hàng & CSKH: <a href="tel:{{ $settings->contact_phone ?? '0981985361' }}">{{ $settings->contact_phone ?? '0981985361' }}</a></p>
            <p>✉ Email hỗ trợ: <a href="mailto:{{ $settings->contact_email ?? 'cskh@nobifashion.vn' }}">{{ $settings->contact_email ?? 'cskh@nobifashion.vn' }}</a></p>
            <p>🌐 Website chính thức: <a href="{{ config('app.url') ?? url('/') }}">{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}</a></p>
        </section>

        <p class="policy-updated">
            Chính sách bán hàng áp dụng cập nhật mới nhất từ tháng 09/2026 theo quy định của Luật Thương mại và Luật Bảo vệ quyền lợi người tiêu dùng số 19/2023/QH15.
        </p>
    </div>
@endsection
