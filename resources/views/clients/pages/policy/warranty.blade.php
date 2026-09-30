@extends('clients.layouts.master')

@section('title', 'Chính sách bảo hành sản phẩm thời trang 30 ngày | ' . renderMeta($settings->site_name ?? ($settings->subname ?? 'NOBI FASHION')))

@section('head')
    <meta name="description"
        content="{{ renderMeta('Chính sách bảo hành chính hãng tại ' . ($settings->site_name ?? 'NOBI FASHION') . ' - Bảo hành đường may, phụ kiện, khuy khóa trong 30 ngày. Cam kết xử lý nhanh, bảo vệ tối đa quyền lợi người tiêu dùng.') }}">
    <meta name="keywords" content="chính sách bảo hành, bảo hành quần áo, nobi fashion, bảo hành đường may, bảo hành khuy khóa, dịch vụ hậu mãi thời trang">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Chính sách bảo hành sản phẩm thời trang 30 ngày | {{ $settings->site_name ?? 'NOBI FASHION' }}">
    <meta property="og:description" content="Cam kết bảo hành đường may, khuy khóa, phụ kiện chính hãng trong 30 ngày tại {{ $settings->site_name ?? 'NOBI FASHION' }}.">
    <meta property="og:image" content="{{ asset('clients/assets/img/business/' . (($settings->site_banner ?? null) ?: (($settings->site_logo ?? null) ?: 'banner.webp'))) }}">
    <meta property="og:image:alt" content="Chính sách bảo hành NOBI FASHION">
    <meta property="og:site_name" content="{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}">
    <meta property="og:locale" content="vi_VN">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Chính sách bảo hành sản phẩm thời trang 30 ngày | {{ $settings->site_name ?? 'NOBI FASHION' }}">
    <meta name="twitter:description" content="Cam kết bảo hành đường may, khuy khóa, phụ kiện chính hãng trong 30 ngày tại {{ $settings->site_name ?? 'NOBI FASHION' }}.">
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
                    '@id' => route('client.policy.warranty') . '#webpage',
                    'url' => route('client.policy.warranty'),
                    'name' => 'Chính sách bảo hành sản phẩm thời trang 30 ngày | ' . (($settings->site_name ?? null) ?: 'NOBI FASHION'),
                    'description' => 'Phạm vi bảo hành đường may, phụ kiện khuy khóa và quy trình xử lý sản phẩm lỗi tại NOBI FASHION.',
                    'isPartOf' => [
                        '@id' => $siteUrl . '#website'
                    ],
                    'breadcrumb' => [
                        '@id' => route('client.policy.warranty') . '#breadcrumb'
                    ],
                    'about' => [
                        '@id' => $siteUrl . '#organization'
                    ],
                    'inLanguage' => 'vi-VN'
                ],
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => route('client.policy.warranty') . '#breadcrumb',
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
                            'name' => 'Chính sách bảo hành',
                            'item' => route('client.policy.warranty')
                        ]
                    ]
                ],
                [
                    '@type' => 'FAQPage',
                    '@id' => route('client.policy.warranty') . '#faq',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => 'Thời gian bảo hành cho sản phẩm là bao lâu?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'NOBI FASHION áp dụng thời hạn bảo hành 30 ngày kể từ ngày mua hàng tại cửa hàng hoặc ngày nhận kiện hàng online.'
                            ]
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Những lỗi nào được bảo hành miễn phí?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Các lỗi kỹ thuật từ nhà sản xuất như: bung chỉ, đứt đường may, tuột khuy bấm, hỏng khóa kéo (dây kéo bị kẹt/gãy) hoặc lem màu lỗi chất liệu trong điều kiện giặt thông thường.'
                            ]
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Nếu sản phẩm bị lỗi nhưng không sửa được thì sao?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Trường hợp sản phẩm gặp lỗi nặng không thể khắc phục về trạng thái ban đầu, NOBI FASHION sẽ đổi mới 1:1 sản phẩm mới tinh cho khách hàng hoặc hoàn tiền nếu sản phẩm đã hết size/mẫu.'
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
                <li class="active" aria-current="page">Chính sách bảo hành</li>
            </ol>
        </nav>

        {{-- Hero Header --}}
        <section class="policy-hero">
            <div class="policy-tags">
                <span class="policy-tag">Bảo hành 30 ngày</span>
                <span class="policy-tag">Chăm sóc trọn đời</span>
                <span class="policy-tag">Chính hãng 100%</span>
            </div>
            <h1>Chính sách bảo hành sản phẩm</h1>
            <p>
                <strong>{{ $settings->site_name ?? ($settings->subname ?? 'NOBI FASHION') }}</strong> luôn theo đuổi tiêu chuẩn may mặc cao cấp, chú trọng từng đường kim mũi chỉ và độ bền bỉ của phụ liệu. Mọi sản phẩm đều được cam kết bảo hành minh bạch nhằm mang lại sự tin cậy lâu dài cho khách hàng.
            </p>
            <div class="policy-meta">
                <div class="policy-meta-card">
                    <span>Thời hạn bảo hành</span>
                    <strong>30 Ngày</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Bảo hành phụ liệu</span>
                    <strong>Miễn phí 100%</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Thời gian xử lý</span>
                    <strong>2 – 5 Ngày</strong>
                </div>
            </div>
        </section>

        {{-- Section 1: Phạm vi bảo hành --}}
        <section class="policy-section">
            <h2>1. Phạm vi & Nội dung bảo hành miễn phí</h2>
            <p>Trong thời hạn 30 ngày kể từ khi mua, NOBI FASHION bảo hành miễn phí các lỗi phát sinh do kỹ thuật sản xuất:</p>
            <div class="policy-table-wrapper">
                <table class="policy-table">
                    <thead>
                        <tr>
                            <th>Hạng mục</th>
                            <th>Nội dung được bảo hành</th>
                            <th>Hình thức khắc phục</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Đường may</strong></td>
                            <td>Bung chỉ, tuột chỉ gấu, rách mối nối chỉ do lỗi may trong quá trình sử dụng thông thường.</td>
                            <td>Gia cố may lại miễn phí nguyên bản như mới.</td>
                        </tr>
                        <tr>
                            <td><strong>Khuy & Nút bấm</strong></td>
                            <td>Đứt cúc áo sơ mi/polo, bung nút bấm kim loại trên quần jean, quần âu.</td>
                            <td>Thay thế cúc/nút bấm đồng bộ chính hãng.</td>
                        </tr>
                        <tr>
                            <td><strong>Khóa kéo (Zipper)</strong></td>
                            <td>Khóa kéo áo khoác, quần âu bị kẹt răng, gãy tay cầm hoặc tuột đầu khóa.</td>
                            <td>Sửa chữa hoặc thay mới dây khóa kéo nguyên bản.</td>
                        </tr>
                        <tr>
                            <td><strong>Chất liệu vải</strong></td>
                            <td>Phai màu loang lổ bất thường, xù lông nghiêm trọng dù giặt đúng hướng dẫn.</td>
                            <td>Đổi mới 1:1 sản phẩm cùng loại hoặc hoàn tiền.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Section 2: Trường hợp không bảo hành --}}
        <section class="policy-section">
            <h2>2. Các trường hợp từ chối bảo hành</h2>
            <ul class="policy-list">
                <li>Sản phẩm đã bị rách, cháy, thủng, co rút sợi do va quẹt vật sắc nhọn hoặc là/ủi ở nhiệt độ quá cao sai hướng dẫn giặt ủi.</li>
                <li>Hư hỏng do sử dụng hóa chất tẩy rửa mạnh, ngâm thuốc tẩy hoặc giặt chung với các loại vải dễ phai màu khác.</li>
                <li>Sản phẩm đã bị khách hàng tự ý can thiệp: cắt gấu, sửa form, bóp eo hoặc sửa chữa tại các tiệm may bên ngoài.</li>
                <li>Sản phẩm đã quá thời hạn bảo hành 30 ngày (với trường hợp này, NOBI FASHION vẫn hỗ trợ sửa chữa tính phí ưu đãi).</li>
                <li>Các dòng sản phẩm phụ kiện (vớ chân, đồ lót) hoặc hàng giảm giá thanh lý đặc biệt trên 50%.</li>
            </ul>
        </section>

        {{-- Section 3: Quy trình tiếp nhận bảo hành --}}
        <section class="policy-section">
            <h2>3. Quy trình tiếp nhận & xử lý bảo hành (4 bước)</h2>
            <div class="policy-timeline">
                <div class="policy-timeline-item">
                    <strong>Bước 1: Tiếp nhận thông tin</strong><br>
                    Khách hàng chụp ảnh/quay video vị trí sản phẩm cần bảo hành và gửi qua Hotline/Zalo CSKH kèm số điện thoại mua hàng.
                </div>
                <div class="policy-timeline-item">
                    <strong>Bước 2: Đánh giá lỗi kỹ thuật</strong><br>
                    Bộ phận Kỹ thuật kiểm tra xác nhận lỗi trong vòng 4 – 8 giờ làm việc và hướng dẫn khách gửi sản phẩm về trung tâm.
                </div>
                <div class="policy-timeline-item">
                    <strong>Bước 3: Xử lý bảo hành chuyên nghiệp</strong><br>
                    Kỹ thuật viên may mặc tiến hành thay thế phụ liệu, dập lại khuy hoặc gia cố đường may chuẩn form dáng ban đầu (từ 2 – 5 ngày).
                </div>
                <div class="policy-timeline-item">
                    <strong>Bước 4: Bàn giao sản phẩm tận nơi</strong><br>
                    Sản phẩm được giặt là phẳng phiu, đóng gói cẩn thận và gửi trả về tận địa chỉ của bạn.
                </div>
            </div>
        </section>

        {{-- Section 4: Chi phí bảo hành --}}
        <section class="policy-section">
            <h2>4. Chi phí & Trách nhiệm vận chuyển</h2>
            <ul class="policy-list">
                <li><strong>Chi phí sửa chữa/thay thế:</strong> Hoàn toàn miễn phí 100% trong thời hạn bảo hành hợp lệ.</li>
                <li><strong>Phí vận chuyển:</strong> Khách hàng chỉ cần gửi sản phẩm về trung tâm bảo hành, NOBI FASHION chi trả toàn bộ phí vận chuyển gửi trả sản phẩm hoàn thiện về tận nhà bạn.</li>
            </ul>
        </section>

        {{-- Section 5: Câu hỏi thường gặp FAQ --}}
        <section class="policy-section">
            <h2>5. Câu hỏi thường gặp về bảo hành</h2>
            <div class="policy-faq-item">
                <strong>Tôi làm mất hóa đơn giấy thì có được bảo hành không?</strong>
                <p>Có. Hệ thống NOBI FASHION lưu trữ thông tin mua hàng bằng số điện thoại điện tử, bạn chỉ cần đọc số điện thoại đã đặt hàng là được áp dụng chính sách bảo hành ngay lập tức.</p>
            </div>
            <div class="policy-faq-item">
                <strong>Sau 30 ngày nếu cúc áo bị đứt thì shop có hỗ trợ không?</strong>
                <p>Có. Với tinh thần hỗ trợ trọn đời, NOBI FASHION nhận bảo dưỡng khuy cúc phụ liệu trọn đời cho mọi khách hàng thân thiết với chi phí phụ liệu bằng 0.</p>
            </div>
        </section>

        {{-- Contact Support --}}
        <section class="policy-contact">
            <h3>Trung tâm bảo hành & kỹ thuật may mặc</h3>
            <p>📞 Hotline kỹ thuật: <a href="tel:{{ $settings->contact_phone ?? '0981985361' }}">{{ $settings->contact_phone ?? '0981985361' }}</a></p>
            <p>✉ Email tiếp nhận: <a href="mailto:{{ $settings->contact_email ?? 'cskh@nobifashion.vn' }}">{{ $settings->contact_email ?? 'cskh@nobifashion.vn' }}</a></p>
            <p>🏢 Địa chỉ xưởng bảo hành: {{ $settings->contact_address ?? 'Hải Phòng' }}</p>
        </section>

        <p class="policy-updated">
            Chính sách bảo hành áp dụng cập nhật mới nhất từ tháng 09/2026 theo quy định của Luật Bảo vệ quyền lợi người tiêu dùng số 19/2023/QH15 và các quy chuẩn may mặc hiện hành.
        </p>
    </div>
@endsection
