@extends('clients.layouts.master')

@section('title', 'Chính sách đổi trả hàng trong 15 ngày | ' . renderMeta($settings->site_name ?? ($settings->subname ?? 'NOBI FASHION')))

@section('head')
    <meta name="description"
        content="{{ renderMeta('Chính sách đổi trả hàng tại ' . ($settings->site_name ?? 'NOBI FASHION') . ' - Hỗ trợ đổi size, đổi mẫu trong 15 ngày, thủ tục đơn giản, miễn phí 100% khi phát sinh lỗi từ nhà sản xuất.') }}">
    <meta name="keywords" content="chính sách đổi trả, đổi hàng 15 ngày, nobi fashion, đổi size áo, hoàn tiền đổi trả, đổi hàng tận nhà">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Chính sách đổi trả hàng trong 15 ngày | {{ $settings->site_name ?? 'NOBI FASHION' }}">
    <meta property="og:description" content="Chính sách đổi size, đổi mẫu linh hoạt trong 15 ngày, hỗ trợ đổi tận nhà và bảo vệ tối đa quyền lợi khách hàng tại {{ $settings->site_name ?? 'NOBI FASHION' }}.">
    <meta property="og:image" content="{{ asset('clients/assets/img/business/' . (($settings->site_banner ?? null) ?: (($settings->site_logo ?? null) ?: 'banner.webp'))) }}">
    <meta property="og:image:alt" content="Chính sách đổi trả hàng NOBI FASHION">
    <meta property="og:site_name" content="{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}">
    <meta property="og:locale" content="vi_VN">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Chính sách đổi trả hàng trong 15 ngày | {{ $settings->site_name ?? 'NOBI FASHION' }}">
    <meta name="twitter:description" content="Chính sách đổi size, đổi mẫu linh hoạt trong 15 ngày, hỗ trợ đổi tận nhà và bảo vệ tối đa quyền lợi khách hàng tại {{ $settings->site_name ?? 'NOBI FASHION' }}.">
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
                    '@id' => route('client.policy.return') . '#webpage',
                    'url' => route('client.policy.return'),
                    'name' => 'Chính sách đổi trả hàng trong 15 ngày | ' . (($settings->site_name ?? null) ?: 'NOBI FASHION'),
                    'description' => 'Điều kiện đổi hàng, thủ tục đổi size đổi mẫu tận nhà và cam kết bảo hành quyền lợi người tiêu dùng tại NOBI FASHION.',
                    'isPartOf' => [
                        '@id' => $siteUrl . '#website'
                    ],
                    'breadcrumb' => [
                        '@id' => route('client.policy.return') . '#breadcrumb'
                    ],
                    'about' => [
                        '@id' => $siteUrl . '#organization'
                    ],
                    'inLanguage' => 'vi-VN'
                ],
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => route('client.policy.return') . '#breadcrumb',
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
                            'name' => 'Chính sách đổi trả',
                            'item' => route('client.policy.return')
                        ]
                    ]
                ],
                [
                    '@type' => 'FAQPage',
                    '@id' => route('client.policy.return') . '#faq',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => 'Thời hạn được phép đổi sản phẩm là bao lâu?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'NOBI FASHION hỗ trợ đổi hàng trong vòng 15 ngày kể từ ngày mua hàng tại showroom hoặc kể từ ngày khách hàng nhận được kiện hàng online.'
                            ]
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Nếu mặc không vừa size thì đổi hàng có mất phí ship không?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Với nhu cầu đổi size hoặc đổi mẫu từ phía khách hàng, khách hàng chỉ cần hỗ trợ 1 chiều cước chuyển phát bưu điện. Nếu lỗi do NOBI FASHION giao nhầm size hoặc lỗi vải, chúng tôi chi trả 100% phí ship 2 chiều.'
                            ]
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Tôi có thể đổi sản phẩm sang mẫu khác có giá cao hơn không?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Có. Bạn hoàn toàn có thể đổi sang sản phẩm bất kỳ có giá trị bằng hoặc cao hơn (chỉ cần thanh toán thêm phần chênh lệch).'
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
                <li class="active" aria-current="page">Chính sách đổi trả</li>
            </ol>
        </nav>

        {{-- Hero Header --}}
        <section class="policy-hero">
            <div class="policy-tags">
                <span class="policy-tag">Đổi hàng 15 ngày</span>
                <span class="policy-tag">Đổi tận nơi</span>
                <span class="policy-tag">Lỗi 1 đổi 1</span>
            </div>
            <h1>Chính sách đổi trả hàng</h1>
            <p>
                Với phương châm <em>"Trải nghiệm khách hàng là ưu tiên số một"</em>, <strong>{{ $settings->site_name ?? ($settings->subname ?? 'NOBI FASHION') }}</strong> xây dựng chính sách đổi trả linh hoạt, giúp bạn an tâm tuyệt đối khi mua sắm online cũng như tại hệ thống cửa hàng.
            </p>
            <div class="policy-meta">
                <div class="policy-meta-card">
                    <span>Thời hạn đổi hàng</span>
                    <strong>15 Ngày</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Tình trạng sản phẩm</span>
                    <strong>Nguyên tem mác</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Lỗi từ nhà sản xuất</span>
                    <strong>Đổi mới 100%</strong>
                </div>
            </div>
        </section>

        {{-- Section 1: Điều kiện đổi trả --}}
        <section class="policy-section">
            <h2>1. Điều kiện áp dụng đổi sản phẩm</h2>
            <p>Để đảm bảo quyền lợi đổi hàng hợp lệ, sản phẩm cần thỏa mãn các tiêu chí sau:</p>
            <div class="policy-table-wrapper">
                <table class="policy-table">
                    <thead>
                        <tr>
                            <th>Hạng mục</th>
                            <th>Đủ điều kiện đổi</th>
                            <th>Không đủ điều kiện đổi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Thời gian</strong></td>
                            <td>Trong vòng <strong>15 ngày</strong> kể từ khi nhận hàng.</td>
                            <td>Quá 15 ngày kể từ ngày giao hàng thành công.</td>
                        </tr>
                        <tr>
                            <td><strong>Tình trạng hàng hóa</strong></td>
                            <td>Nguyên tem, tag treo, chưa qua giặt ủi, không có mùi lạ.</td>
                            <td>Đã cắt tem, đã giặt tẩy, dính bẩn hoặc có mùi nước hoa.</td>
                        </tr>
                        <tr>
                            <td><strong>Chứng từ mua hàng</strong></td>
                            <td>Hóa đơn mua hàng hoặc số điện thoại đặt hàng trên hệ thống.</td>
                            <td>Không xác minh được lịch sử giao dịch mua hàng.</td>
                        </tr>
                        <tr>
                            <td><strong>Loại sản phẩm</strong></td>
                            <td>Áo polo, sơ mi, quần âu, kaki, áo khoác nguyên giá.</td>
                            <td>Quần lót, tất chân, phụ kiện hoặc hàng xả kho giảm >50%.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Section 2: Quy trình đổi hàng --}}
        <section class="policy-section">
            <h2>2. Quy trình đổi hàng nhanh chóng (4 bước)</h2>
            <div class="policy-timeline">
                <div class="policy-timeline-item">
                    <strong>Bước 1: Đăng ký đổi hàng</strong><br>
                    Liên hệ Hotline/Zalo CSKH hoặc nhắn tin trực tiếp qua Fanpage, cung cấp mã đơn hàng và nhu cầu đổi (đổi size, đổi màu hoặc đổi mẫu khác).
                </div>
                <div class="policy-timeline-item">
                    <strong>Bước 2: Xác nhận và chuẩn bị hàng mới</strong><br>
                    Chuyên viên tư vấn giữ lại mẫu mã và kích cỡ mới bạn cần, đồng thời tạo mã vận đơn đổi hàng 2 chiều.
                </div>
                <div class="policy-timeline-item">
                    <strong>Bước 3: Shipper giao hàng tận nơi & Thu hồi hàng cũ</strong><br>
                    Shipper mang sản phẩm mới đến tận nhà bạn, bạn nhận sản phẩm mới và gửi lại sản phẩm cần đổi cho shipper cùng một lúc (không cần phải ra bưu cục gửi hàng).
                </div>
                <div class="policy-timeline-item">
                    <strong>Bước 4: Hoàn tất đối soát</strong><br>
                    Thanh toán khoản chênh lệch (nếu đổi sang mẫu có giá trị cao hơn) hoặc nhận voucher chênh lệch theo thỏa thuận.
                </div>
            </div>
        </section>

        {{-- Section 3: Đổi do lỗi nhà sản xuất --}}
        <section class="policy-section">
            <h2>3. Chính sách đổi mới do lỗi kỹ thuật (Miễn phí 100%)</h2>
            <p>NOBI FASHION cam kết <strong>đổi mới 100%</strong> và chi trả toàn bộ phí vận chuyển hai chiều nếu sản phẩm phát sinh các lỗi kỹ thuật sau:</p>
            <ul class="policy-list">
                <li>Lỗi vải: Xước vải, phai màu bất thường, co rút sợi ngoài tiêu chuẩn khi mở gói.</li>
                <li>Lỗi đường may: Đứt chỉ, may lệch phom dáng, rách đường may tại các mối nối.</li>
                <li>Lỗi phụ liệu: Cúc áo bị vỡ, khóa kéo bị kẹt, hỏng chốt cài.</li>
                <li>Giao sai đơn: Nhân viên đóng gói nhầm size, sai màu sắc hoặc sai kiểu dáng so với đơn đã đặt.</li>
            </ul>
            <div class="policy-note">
                ❤️ <em>Cam kết:</em> Sự hài lòng của bạn là danh dự của NOBI FASHION. Mọi sai sót từ khâu kiểm định sẽ được giải quyết nhanh nhất trong 24 giờ.
            </div>
        </section>

        {{-- Section 4: Câu hỏi thường gặp FAQ --}}
        <section class="policy-section">
            <h2>4. Câu hỏi thường gặp về đổi trả</h2>
            <div class="policy-faq-item">
                <strong>Đổi size áo thì ai chịu phí vận chuyển?</strong>
                <p>Nếu bạn muốn đổi kích cỡ để vừa vặn hơn, NOBI FASHION sẽ hỗ trợ điều phối shipper giao tận nơi và bạn chỉ cần hỗ trợ 1 chiều cước bưu điện (20.000đ – 30.000đ). Nếu sản phẩm bị lỗi từ shop, shop chịu 100% cước phí.</p>
            </div>
            <div class="policy-faq-item">
                <strong>Tôi có được đổi sang sản phẩm khác loại không?</strong>
                <p>Hoàn toàn được. Bạn có thể đổi sang bất kỳ mẫu mã nào khác trên website. Nếu mẫu mới có giá cao hơn, bạn bù phần chênh lệch; nếu thấp hơn, phần thừa sẽ được hoàn lại hoặc quy đổi voucher tích điểm.</p>
            </div>
            <div class="policy-faq-item">
                <strong>Tôi có thể đến trực tiếp cửa hàng để đổi không?</strong>
                <p>Có. Bạn chỉ cần mang sản phẩm còn nguyên tem mác kèm số điện thoại mua hàng đến bất kỳ showroom nào của NOBI FASHION để nhân viên hỗ trợ đổi ngay tại quầy.</p>
            </div>
        </section>

        {{-- Contact Support --}}
        <section class="policy-contact">
            <h3>Trung tâm tiếp nhận đổi trả</h3>
            <p>📞 Hotline đổi hàng nhanh: <a href="tel:{{ $settings->contact_phone ?? '0981985361' }}">{{ $settings->contact_phone ?? '0981985361' }}</a></p>
            <p>✉ Email bộ phận đổi trả: <a href="mailto:{{ $settings->contact_email ?? 'cskh@nobifashion.vn' }}">{{ $settings->contact_email ?? 'cskh@nobifashion.vn' }}</a></p>
            <p>🏢 Địa chỉ tiếp nhận bưu phẩm đổi trả: {{ $settings->contact_address ?? 'Hải Phòng' }}</p>
        </section>

        <p class="policy-updated">
            Chính sách đổi trả áp dụng cập nhật mới nhất từ tháng 09/2026 theo quy định của Luật Bảo vệ quyền lợi người tiêu dùng số 19/2023/QH15 và các quy chuẩn dịch vụ thương mại điện tử hiện hành.
        </p>
    </div>
@endsection
