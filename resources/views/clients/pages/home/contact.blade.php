@extends('clients.layouts.master')

@section('title', 'Liên hệ NOBI FASHION - Thời trang Việt Nam | ' . renderMeta($settings->site_name ??
    $settings->subname))

@section('head')
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />

    <meta name="keywords"
        content="{{ $settings->seo_keywords ?? 'liên hệ NOBI FASHION, thời trang nam, áo polo, áo sơ mi, quần kaki, shop thời trang Việt Nam, gentzone' }}">

    <meta name="description"
        content="{{ renderMeta($settings->site_description) ?? 'Liên hệ NOBI FASHION để được tư vấn thời trang nam hiện đại, lịch lãm và năng động. Hỗ trợ đặt hàng, đổi size, giao hàng toàn quốc nhanh chóng.' }}">

    <meta http-equiv="date" content="{{ \Carbon\Carbon::now()->format('d/m/y') }}" />

    {{-- ✅ Open Graph --}}
    <meta property="og:title" content="{{ renderMeta('Liên hệ NOBI FASHION - Thời trang Việt Nam') }}">
    <meta property="og:description"
        content="{{ renderMeta('Liên hệ NOBI FASHION để được hỗ trợ tư vấn, mua hàng và đổi trả nhanh chóng trên toàn quốc. Phong cách hiện đại – Chất lượng Việt Nam.') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image"
        content="{{ asset('clients/assets/img/business/' . ($settings->site_banner ?: $settings->site_logo ?? 'logo-nobi-fashion.png')) }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ renderMeta('Liên hệ NOBI FASHION - Thời trang Việt Nam') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ renderMeta('NOBI FASHION') }}">
    <meta property="og:locale" content="vi_VN">

    {{-- ✅ Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ renderMeta('Liên hệ NOBI FASHION - Thời trang Việt Nam') }}">
    <meta name="twitter:description"
        content="{{ renderMeta('Hỗ trợ khách hàng NOBI FASHION 24/7. Giao hàng nhanh, đổi trả linh hoạt, tư vấn thời trang tận tâm.') }}">
    <meta name="twitter:image"
        content="{{ asset('clients/assets/img/business/' . ($settings->site_banner ?: $settings->site_logo ?? 'logo-nobi-fashion.png')) }}">
    <meta name="twitter:creator" content="{{ renderMeta('NOBI FASHION') }}">

    {{-- ✅ Canonical & Hreflang --}}
    <link rel="canonical" href="{{ route('client.page.contact') }}">
    <link rel="alternate" hreflang="vi" href="{{ route('client.page.contact') }}">
    <link rel="alternate" hreflang="x-default" href="{{ route('client.page.contact') }}">
@endsection

@section('content')
    <section class="contact-hero">
        <div class="contact-hero__content">
            <p class="hero-eyebrow">NOBI FASHION CARE</p>
            <h1 class="hero-title">Kết nối với chúng tôi</h1>
            <p class="hero-subtitle">Đội ngũ Stylist và CSKH luôn sẵn sàng đồng hành cùng bạn trong mọi hành trình mua sắm – từ tư vấn phong cách, lựa chọn size chuẩn đến hỗ trợ bảo hành đổi trả.</p>
            <div class="hero-actions">
                <a href="tel:{{ $settings->contact_phone ?? '' }}" class="hero-btn primary">Gọi ngay {{ $settings->contact_phone ?? '' }}</a>
                <a href="mailto:{{ $settings->contact_email ?? '' }}" class="hero-btn ghost">Gửi email</a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat-card">
                    <span class="label">Khách hàng hài lòng</span>
                    <strong>98%</strong>
                </div>
                <div class="hero-stat-card">
                    <span class="label">Thời gian phản hồi</span>
                    <strong>< 15 phút</strong>
                </div>
                <div class="hero-stat-card">
                    <span class="label">Chi nhánh toàn quốc</span>
                    <strong>12+</strong>
                </div>
            </div>
        </div>
        <div class="contact-hero__card">
            <div class="badge">Độc quyền khách hàng thân thiết</div>
            <div class="card-content">
                <h3>Chuyên viên cá nhân</h3>
                <p>Đặt lịch tư vấn 1-1 cùng stylist để chọn tủ đồ chuẩn xu hướng.</p>
                <ul>
                    <li>Miễn phí đo size online</li>
                    <li>Ưu tiên xử lý & giao nhận</li>
                    <li>Quà tặng sinh nhật riêng</li>
                </ul>
                <button type="button" class="hero-btn secondary">Đặt lịch ngay</button>
            </div>
        </div>
    </section>

    <section class="contact-core">
        <div class="contact-info">
            <div class="info-card glass">
                <div class="info-header">
                    <span>🌐 Trụ sở chính</span>
                    <small>Hoạt động 8h00 - 22h00</small>
                </div>
                <p class="info-value">{{ $settings->contact_address ?? 'Đang cập nhật' }}</p>
                <div class="info-divider"></div>
                <div class="info-stats">
                    <div>
                        <span>Hotline</span>
                        <strong><a href="tel:{{ $settings->contact_phone ?? '' }}">{{ $settings->contact_phone ?? '' }}</a></strong>
                    </div>
                    <div>
                        <span>Email</span>
                        <strong><a href="mailto:{{ $settings->contact_email ?? '' }}">{{ $settings->contact_form_recipient ?? '' }}</a></strong>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <h3>Kênh hỗ trợ ưu tiên</h3>
                <div class="info-channels">
                    <a href="{{ $settings?->facebook_link ?? '#' }}" target="_blank" class="channel">
                        <span>Facebook Concierge</span>
                        <small>Phản hồi dưới 5 phút</small>
                    </a>
                    <a href="{{ $settings?->tiktok_link ?? '#' }}" target="_blank" class="channel">
                        <span>TikTok Live Support</span>
                        <small>Livestream hàng ngày</small>
                    </a>
                    <a href="{{ $settings?->telegram_link ?? '#' }}" target="_blank" class="channel">
                        <span>Telegram VIP</span>
                        <small>Chốt đơn siêu tốc</small>
                    </a>
                    <a href="{{ $settings?->instagram_link ?? '#' }}" target="_blank" class="channel">
                        <span>Instagram DM</span>
                        <small>Stylist trực tuyến</small>
                    </a>
                </div>
            </div>

            <div class="info-card gradient">
                <div>
                    <p class="eyebrow">Dịch vụ cao cấp</p>
                    <h3>Bảo hành & đổi trả 30 ngày</h3>
                    <p class="muted">Sẵn sàng đổi size, chỉnh sửa dáng hoặc hoàn tiền trong vòng 30 ngày với mọi đơn hàng chính hãng.</p>
                </div>
                <ul class="bullet-list">
                    <li>Miễn phí thu hồi tại nhà</li>
                    <li>Cập nhật tiến trình qua SMS/Email</li>
                    <li>Đội ngũ kiểm định chất lượng riêng</li>
                </ul>
            </div>
        </div>

        <div class="contact-form-wrapper">
            <div class="form-header">
                <p class="eyebrow">Gửi yêu cầu</p>
                <h3>Giải quyết trong một lần liên hệ</h3>
                <p>Điền thông tin chi tiết, chúng tôi sẽ phản hồi qua email và gọi xác nhận ngay khi cần.</p>
            </div>
            <form id="contact-form" action="{{ route('client.contact.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div id="contact-form-message" class="form-alert" style="display:none;"></div>

                <div class="form-grid">
                    <label class="form-field">
                        <span>Họ và tên *</span>
                        <input type="text" id="name" name="name" placeholder="Nguyễn Minh Đức" required>
                    </label>
                    <label class="form-field">
                        <span>Email *</span>
                        <input type="email" id="email" name="email" placeholder="email@yourdomain.com" required>
                    </label>
                </div>

                <div class="form-grid">
                    <label class="form-field">
                        <span>Số điện thoại</span>
                        <input type="tel" id="phone" name="phone" placeholder="(+84) 090 xxx xxxx">
                    </label>
                    <label class="form-field">
                        <span>Chủ đề *</span>
                        <input type="text" id="subject" name="subject" placeholder="Cần tư vấn phối đồ công sở" required>
                    </label>
                </div>

                <label class="form-field">
                    <span>Nội dung *</span>
                    <textarea id="message" name="message" rows="5" placeholder="Hãy chia sẻ điều bạn cần hỗ trợ..." minlength="10" required></textarea>
                    <small>Tối thiểu 10 ký tự. Bạn có thể đính kèm thêm ảnh mô tả ở dưới.</small>
                </label>

                <label class="form-field file-field">
                    <span>File đính kèm (tùy chọn)</span>
                    <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx">
                    <small>Hỗ trợ jpg, png, pdf, doc, xlsx. Tối đa 10MB</small>
                </label>

                <button type="submit" class="hero-btn primary full" id="contact-submit-btn">
                    <span id="contact-submit-text">Gửi yêu cầu</span>
                    <span id="contact-submit-loading" style="display:none;">Đang gửi...</span>
                </button>
            </form>
        </div>
    </section>

    <section class="contact-support">
        <div class="support-card">
            <h4>Đơn hàng & vận chuyển</h4>
            <p>Kiểm tra trạng thái GHN, đổi địa chỉ giao, yêu cầu đóng gói quà tặng.</p>
            <a href="{{ route('client.order.track') ?? '#' }}">Tra cứu đơn hàng</a>
        </div>
        <div class="support-card">
            <h4>Stylist cá nhân</h4>
            <p>Nhận lookbook độc quyền và gợi ý outfit theo vóc dáng, lịch trình.</p>
            <a href="tel:{{ $settings->contact_phone ?? '' }}">Đặt lịch 1-1</a>
        </div>
        <div class="support-card">
            <h4>Doanh nghiệp & quà tặng</h4>
            <p>Ưu đãi may đo đồng phục, combo quà tặng đối tác, khắc tên thương hiệu.</p>
            <a href="mailto:{{ $settings->contact_email ?? '' }}">Nhận báo giá</a>
        </div>
    </section>

    <section class="contact-map">
        <div class="map-info">
            <p class="eyebrow">Showroom Flagship</p>
            <h3>Trải nghiệm thử đồ tại studio</h3>
            <p>Đặt lịch trước để được chuẩn bị phòng thử riêng, đồ uống chào đón và photographer hỗ trợ ghi lại khoảnh khắc.</p>
            <ul>
                <li>✔ Bãi đỗ xe miễn phí</li>
                <li>✔ Wi-Fi & minibar</li>
                <li>✔ Giữ đồ sửa chữa tại chỗ</li>
            </ul>
        </div>
        <div class="map-frame">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4010.734289061049!2d106.68005187555318!3d20.82730938077215!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x314a707bf0c0c6b3%3A0x270e1b278f753cae!2zNTk1LzEgUC4gVGhpw6puIEzDtGksIFThu5UgRHAgU-G7kSAzMCwgTMOqIENow6JuLCBI4bqjaSBQaMOybmcsIFZp4buHdCBOYW0!5e1!3m2!1svi!2s!4v1762164701486!5m2!1svi!2s"
                allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </section>

    <div class="contact-product">
        @include('clients.templates.product_new')
    </div>

    <style>
        :root {
            --contact-bg: #05070f;
            --contact-card: rgba(255, 255, 255, 0.08);
            --contact-border: rgba(255, 255, 255, 0.15);
        }

        .contact-hero {
            width: 92%;
            margin: 40px auto 0;
            padding: 48px;
            border-radius: 32px;
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
            gap: 32px;
            background: radial-gradient(circle at top, rgba(0, 161, 155, 0.2), transparent),
                linear-gradient(135deg, #05070f, #0c1021 60%, #0f172a);
            color: #fff;
        }

        .contact-hero__content .hero-eyebrow {
            letter-spacing: 0.35em;
            font-size: 12px;
            opacity: 0.8;
        }

        .hero-title {
            font-size: clamp(36px, 4vw, 52px);
            margin: 12px 0;
        }

        .hero-subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 18px;
            max-width: 580px;
        }

        .hero-actions {
            display: flex;
            gap: 16px;
            margin: 24px 0 32px;
        }

        .hero-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 999px;
            padding: 14px 24px;
            font-weight: 600;
            transition: 0.3s;
            border: 1px solid transparent;
        }

        .hero-btn.primary {
            background: linear-gradient(90deg, #00a19b, #00e0d3);
            color: #041014;
        }

        .hero-btn.secondary {
            background: #fff;
            color: #041014;
        }

        .hero-btn.ghost {
            border-color: rgba(255, 255, 255, 0.4);
            color: #fff;
        }

        .hero-btn.full {
            width: 100%;
        }

        .hero-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
        }

        .hero-stat-card {
            padding: 18px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(10px);
        }

        .hero-stat-card .label {
            font-size: 13px;
            opacity: 0.8;
        }

        .hero-stat-card strong {
            display: block;
            margin-top: 8px;
            font-size: 22px;
        }

        .contact-hero__card {
            background: rgba(15, 23, 42, 0.75);
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 32px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .contact-hero__card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top right, rgba(0, 224, 211, 0.35), transparent 45%);
            pointer-events: none;
        }

        .contact-hero__card .badge {
            width: fit-content;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.1);
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.1em;
        }

        .contact-hero__card .card-content {
            position: relative;
            z-index: 1;
        }

        .contact-hero__card ul {
            list-style: none;
            padding: 0;
            margin: 16px 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .contact-hero__card ul li::before {
            content: "•";
            margin-right: 8px;
            color: #00e0d3;
        }

        .contact-core {
            width: 92%;
            margin: 40px auto;
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
            gap: 32px;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .info-card {
            padding: 28px;
            border-radius: 24px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: #fff;
            box-shadow: 0 15px 40px rgba(15, 23, 42, 0.08);
        }

        .glass {
            background: linear-gradient(135deg, rgba(0, 161, 155, 0.08), rgba(15, 23, 42, 0.05));
            border-color: rgba(0, 161, 155, 0.15);
        }

        .gradient {
            background: linear-gradient(135deg, #06162a, #0a2c3f);
            color: #dff8ff;
            border-color: rgba(255, 255, 255, 0.08);
        }

        .info-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #4b5563;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .info-value {
            font-size: 22px;
            font-weight: 600;
            margin: 14px 0;
        }

        .info-divider {
            border-bottom: 1px dashed rgba(15, 23, 42, 0.2);
            margin: 20px 0;
        }

        .info-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .info-stats strong a {
            color: inherit;
            text-decoration: none;
        }

        .info-channels {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .channel {
            display: block;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(0, 161, 155, 0.05);
            color: inherit;
            text-decoration: none;
            transition: 0.3s;
        }

        .channel:hover {
            border-color: #00a19b;
            transform: translateY(-2px);
        }

        .bullet-list {
            list-style: none;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .bullet-list li::before {
            content: "✔";
            margin-right: 10px;
            color: #00e0d3;
        }

        .contact-form-wrapper {
            background: #fff;
            border-radius: 32px;
            padding: 36px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.12);
            position: relative;
            overflow: hidden;
        }

        .contact-form-wrapper::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0, 161, 155, 0.08), rgba(255, 255, 255, 0));
            pointer-events: none;
        }

        .contact-form-wrapper form {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-header .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.2em;
            font-size: 12px;
            color: #00a19b;
        }

        .form-header h3 {
            margin: 8px 0;
            font-size: 28px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 14px;
            color: #374151;
        }

        .form-field input,
        .form-field textarea,
        .form-field input[type="file"] {
            border-radius: 14px;
            border: 1px solid rgba(55, 65, 81, 0.2);
            padding: 12px 14px;
            font-size: 15px;
            transition: border 0.2s, box-shadow 0.2s;
        }

        .form-field input:focus,
        .form-field textarea:focus,
        .form-field input[type="file"]:focus {
            border-color: #00a19b;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 161, 155, 0.15);
        }

        .file-field input[type="file"] {
            padding: 10px;
            background: rgba(15, 23, 42, 0.02);
        }

        .form-alert {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
        }

        .contact-support {
            width: 92%;
            margin: 30px auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .support-card {
            padding: 24px;
            border-radius: 20px;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
        }

        .support-card a {
            margin-top: 12px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #00a19b;
            font-weight: 600;
        }

        .contact-map {
            width: 92%;
            margin: 40px auto 60px;
            display: grid;
            grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
            gap: 32px;
            align-items: stretch;
        }

        .map-info {
            background: #06162a;
            color: #f3fbff;
            border-radius: 28px;
            padding: 32px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .map-info ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .map-frame {
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(15, 23, 42, 0.2);
        }

        .map-frame iframe {
            width: 100%;
            height: 100%;
            min-height: 320px;
            border: 0;
        }

        .contact-product {
            width: 92%;
            margin: 30px auto 60px;
        }

        @media (max-width: 1024px) {
            .contact-hero,
            .contact-core,
            .contact-map {
                grid-template-columns: 1fr;
            }

            .hero-actions {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 768px) {
            .contact-hero,
            .contact-core,
            .contact-support,
            .contact-map,
            .contact-product {
                width: 94%;
            }

            .contact-hero {
                padding: 32px 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('foot')
    <script>
        // Xử lý form liên hệ
        document.addEventListener("DOMContentLoaded", function() {
            const contactForm = document.getElementById('contact-form');
            const messageDiv = document.getElementById('contact-form-message');
            const submitBtn = document.getElementById('contact-submit-btn');
            const submitText = document.getElementById('contact-submit-text');
            const submitLoading = document.getElementById('contact-submit-loading');

            if (contactForm) {
                contactForm.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    // Disable button
                    submitBtn.disabled = true;
                    submitText.style.display = 'none';
                    submitLoading.style.display = 'inline';

                    // Hide previous messages
                    messageDiv.style.display = 'none';

                    // Get form data
                    const formData = new FormData(contactForm);

                    try {
                        const response = await fetch(contactForm.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            }
                        });

                        const data = await response.json();

                        // Show message
                        messageDiv.style.display = 'block';
                        
                        if (data.success) {
                            messageDiv.style.background = '#d1fae5';
                            messageDiv.style.color = '#065f46';
                            messageDiv.style.border = '1px solid #10b981';
                            messageDiv.innerHTML = '<strong>✓ Thành công!</strong> ' + (data.message || 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất có thể.');
                            
                            // Reset form
                            contactForm.reset();
                        } else {
                            messageDiv.style.background = '#fee2e2';
                            messageDiv.style.color = '#991b1b';
                            messageDiv.style.border = '1px solid #ef4444';
                            
                            // Hiển thị lỗi validation nếu có
                            let errorMessage = data.message || 'Có lỗi xảy ra. Vui lòng thử lại sau.';
                            if (data.errors) {
                                const errorList = Object.values(data.errors).flat().join('<br>');
                                errorMessage = '<strong>✗ Lỗi:</strong><br>' + errorList;
                            }
                            messageDiv.innerHTML = errorMessage;
                        }
                    } catch (error) {
                        messageDiv.style.display = 'block';
                        messageDiv.style.background = '#fee2e2';
                        messageDiv.style.color = '#991b1b';
                        messageDiv.style.border = '1px solid #ef4444';
                        messageDiv.textContent = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
                        console.error('Contact form error:', error);
                    } finally {
                        // Re-enable button
                        submitBtn.disabled = false;
                        submitText.style.display = 'inline';
                        submitLoading.style.display = 'none';

                        // Scroll to message
                        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
            }
        });

        document.addEventListener("DOMContentLoaded", function() {

            // Tạo container tuyết rơi
            const snowContainer = document.createElement("div");
            snowContainer.id = "snow-container";

            // Style không ảnh hưởng trang
            snowContainer.style.position = "fixed";
            snowContainer.style.top = "0";
            snowContainer.style.left = "0";
            snowContainer.style.width = "100%";
            snowContainer.style.height = "100%";
            snowContainer.style.pointerEvents = "none"; // KHÔNG chặn cuộn
            snowContainer.style.zIndex = "1";
            snowContainer.style.overflow = "hidden";

            document.body.appendChild(snowContainer);

            const snowCount = 60;

            const svgSnow = `
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="#ffffff">
                    <path d="M344.1 56C344.1 42.7 333.4 32 320.1 32C306.8 32 296.1 42.7 296.1 56L296.1 134.1L273.1 111.1C263.7 101.7 
                    248.5 101.7 239.2 111.1C229.9 120.5 229.8 135.7 239.2 145L296.2 202L296.2 278.5L230 240.3L209.1 162.5C205.7 149.7 
                    192.5 142.1 179.7 145.5C166.9 148.9 159.2 162 162.7 174.8L171.1 206.3L103.5 167.3C92 160.6 77.3 164.5 70.7 176C64.1 
                    187.5 68 202.2 79.5 208.8L147.1 247.8L115.6 256.2C102.8 259.6 95.2 272.8 98.6 285.6C102 298.4 115.2 306 128 302.6L205.8 
                    281.7L272 319.9L205.8 358.1L128 337.2C115.2 333.8 102 341.4 98.6 354.2C95.2 367 102.8 380.2 115.6 383.6L147.1 
                    392L79.5 431C68 437.8 64.1 452.5 70.7 464C77.3 475.5 92 479.4 103.5 472.8L171.1 433.8L162.7 465.3C159.3 478.1 
                    166.9 491.3 179.7 494.7C192.5 498.1 205.7 490.5 209.1 477.7L230 399.9L296.2 361.7L296.2 438.2L239.2 495.2C229.8 
                    504.6 229.8 519.8 239.2 529.1C248.6 538.4 263.8 538.5 273.1 529.1L296.1 506.1L296.1 584.2C296.1 597.5 306.8 608.2 
                    320.1 608.2C333.4 608.2 344.1 597.5 344.1 584.2L344.1 506.1L367.1 529.1C376.5 538.5 391.7 538.5 401 529.1C410.3 
                    519.7 410.4 504.5 401 495.2L344 438.2L344 361.7L410.2 399.9L431.1 477.7C434.5 490.5 447.7 498.1 460.5 494.7C473.3 
                    491.3 480.9 478.1 477.5 465.3L469.1 433.8L536.7 472.8C548.2 479.4 562.9 475.5 569.5 464C576.1 452.5 572.2 437.8 
                    560.7 431.2L493.1 392.2L524.6 383.8C537.4 380.4 545 367.2 541.6 354.4C538.2 341.6 525 334 512.2 337.4L434.4 
                    358.3L368.2 320.1L434.4 281.9L512.2 302.8C525 306.2 538.2 298.6 541.6 285.8C545 273 537.4 259.8 524.6 256.4L493.1 
                    248L560.7 209C572.2 202.4 576.1 187.7 569.5 176.2C562.9 164.7 548.2 160.8 536.7 167.4L469.1 206.4L477.5 174.9C480.9 
                    162.1 473.3 148.9 460.5 145.5C447.7 142.1 434.5 149.7 431.1 162.5L410.2 240.3L344 278.5L344 202L401 145C410.4 
                    135.6 410.4 120.4 401 111.1C391.6 101.8 376.4 101.7 367.1 111.1L344.1 134.1L344.1 56z"/>
                </svg>
            `;

            function createSnowflake() {
                const wrapper = document.createElement("div");
                wrapper.innerHTML = svgSnow;

                const snow = wrapper.firstElementChild;

                // kích thước ngẫu nhiên
                const size = Math.random() * 20 + 15;
                snow.style.width = size + "px";
                snow.style.height = size + "px";
                snow.style.position = "absolute";
                snow.style.top = "-50px";
                snow.style.left = Math.random() * window.innerWidth + "px";
                snow.style.opacity = (Math.random() * 0.6 + 0.3).toString();
                snow.style.transform = `rotate(${Math.random() * 360}deg)`;
                snow.style.fill = "#faffff";

                // animation
                const fallDuration = Math.random() * 7 + 6;
                const spinDuration = fallDuration * 1.8;
                const delay = Math.random() * 3;

                snow.style.animation = `
                    fall ${fallDuration}s linear ${delay}s infinite,
                    spin ${spinDuration}s linear infinite
                `;

                snowContainer.appendChild(snow);
            }

            for (let i = 0; i < snowCount; i++) {
                createSnowflake();
            }

            const style = document.createElement("style");
            style.innerHTML = `
                @keyframes fall {
                    0% {
                        transform: translateY(-10px) translateX(0);
                        opacity: 1;
                    }
                    100% {
                        transform: translateY(${window.innerHeight + 50}px) translateX(-200px);
                        opacity: 0.4;
                    }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
@endsection
