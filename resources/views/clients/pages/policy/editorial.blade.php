@extends('clients.layouts.master')

@section('title', 'Chính sách biên tập nội dung - ' . renderMeta($settings->site_name ?? ($settings->subname ?? 'NOBI FASHION VIỆT NAM')))

@section('head')
    <meta name="description"
        content="{{ renderMeta('Chính sách biên tập nội dung ' . ($settings->site_name ?? 'NOBI FASHION VIỆT NAM') . ' - nguyên tắc biên tập, nguồn thông tin và cam kết đính chính minh bạch.') }}">
    <link rel="canonical" href="{{ url()->current() }}">
@endsection

@push('styles')
    <style>
        :root {
            --ep-primary: #111827;
            --ep-accent: #e11d48;
            --ep-text: #374151;
            --ep-text-heading: #111827;
            --ep-muted: #6b7280;
            --ep-border: #e5e7eb;
            --ep-bg-card: #ffffff;
            --ep-bg-subtle: #f8fafc;
        }

        .editorial-policy-page {
            max-width: 960px;
            margin: 24px auto 60px;
            padding: 0 16px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: var(--ep-text);
            line-height: 1.8;
            font-size: 16px;
        }

        /* Breadcrumb */
        .editorial-breadcrumb {
            margin-bottom: 20px;
        }

        .editorial-breadcrumb ol {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            list-style: none !important;
            padding: 0;
            margin: 0;
            font-size: 13px;
        }

        .editorial-breadcrumb li {
            display: inline-flex !important;
            align-items: center;
            gap: 8px;
            color: var(--ep-muted);
        }

        .editorial-breadcrumb a {
            color: var(--ep-muted);
            text-decoration: none !important;
            transition: color 0.2s;
        }

        .editorial-breadcrumb a:hover {
            color: var(--ep-primary);
        }

        .editorial-breadcrumb li.active {
            color: var(--ep-primary);
            font-weight: 600;
        }

        .editorial-breadcrumb .separator {
            font-size: 10px;
            color: #9ca3af;
        }

        /* Hero Banner */
        .editorial-hero {
            position: relative;
            background: linear-gradient(135deg, #090e1a 0%, #1e293b 100%);
            color: #ffffff;
            border-radius: 20px;
            padding: 40px 36px;
            margin-bottom: 36px;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.12);
            overflow: hidden;
        }

        .editorial-hero::after {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(225, 29, 72, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .editorial-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
        }

        .editorial-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(8px);
            color: #f1f5f9;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 5px 14px;
            border-radius: 999px;
        }

        .editorial-hero h1 {
            font-size: clamp(24px, 3.5vw, 34px);
            font-weight: 800;
            line-height: 1.35;
            color: #ffffff;
            margin: 0 0 16px;
            letter-spacing: -0.02em;
        }

        .editorial-hero-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            color: #94a3b8;
            font-size: 14px;
        }

        .editorial-hero-meta span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Intro Statement Box */
        .editorial-intro-box {
            background: #ffffff;
            border: 1px solid var(--ep-border);
            border-left: 5px solid var(--ep-primary);
            border-radius: 12px;
            padding: 24px 26px;
            margin-bottom: 36px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
            font-size: 16.5px;
            line-height: 1.8;
            color: #1f2937;
        }

        /* Section Block */
        .editorial-section {
            background: var(--ep-bg-card);
            border: 1px solid var(--ep-border);
            border-radius: 16px;
            padding: 28px 30px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            transition: border-color 0.2s;
        }

        .editorial-section:hover {
            border-color: #cbd5e1;
        }

        .editorial-section h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--ep-text-heading);
            margin: 0 0 16px;
            line-height: 1.45;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            letter-spacing: -0.01em;
        }

        .editorial-section-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 30px;
            height: 30px;
            border-radius: 8px;
            background: #0f172a;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .editorial-section p {
            margin: 0 0 16px;
            color: var(--ep-text);
        }

        .editorial-section p:last-child {
            margin-bottom: 0;
        }

        /* Lists Styling - Khắc phục triệt để reset * { list-style: none } */
        .editorial-list {
            margin: 0 0 20px 0 !important;
            padding-left: 28px !important;
        }

        .editorial-list li {
            display: list-item !important;
            margin-bottom: 10px !important;
            padding-left: 4px;
            color: #374151;
            line-height: 1.7;
        }

        /* Danh sách tròn (disc) */
        .editorial-list.disc-list {
            list-style-type: disc !important;
        }

        .editorial-list.disc-list li {
            list-style: disc !important;
            list-style-type: disc !important;
        }

        /* Danh sách chữ cái (lower-alpha a, b, c) */
        .editorial-list.alpha-list {
            list-style-type: lower-alpha !important;
        }

        .editorial-list.alpha-list li {
            list-style: lower-alpha !important;
            list-style-type: lower-alpha !important;
            font-weight: 500;
        }

        /* Danh sách ô vuông (square) */
        .editorial-list.square-list {
            list-style-type: square !important;
        }

        .editorial-list.square-list li {
            list-style: square !important;
            list-style-type: square !important;
        }

        /* Highlight Boxes */
        .editorial-quote-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px 22px;
            margin: 20px 0;
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .editorial-quote-icon {
            color: #475569;
            font-size: 20px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .editorial-quote-content strong {
            display: block;
            margin-bottom: 6px;
            color: #0f172a;
            font-size: 15px;
        }

        .editorial-quote-content p {
            margin: 0;
            color: #475569;
            font-size: 15px;
        }

        /* Commitment Box */
        .editorial-commitment-card {
            background: linear-gradient(to bottom right, #ffffff, #f9fafb);
            border: 1.5px solid #cbd5e1;
            border-radius: 16px;
            padding: 26px 28px;
            margin: 24px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        }

        .editorial-commitment-card strong {
            font-size: 17px;
            color: #0f172a;
            display: block;
            margin-bottom: 12px;
        }

        .editorial-commitment-card p {
            margin-bottom: 12px;
            color: #334155;
        }

        .editorial-commitment-card p:last-child {
            margin-bottom: 0;
        }

        /* Contact Action Card */
        .editorial-contact-card {
            background: #0f172a;
            color: #ffffff;
            border-radius: 16px;
            padding: 30px 32px;
            margin-top: 24px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.15);
        }

        .editorial-contact-card h3 {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 10px;
        }

        .editorial-contact-card p {
            color: #cbd5e1;
            margin: 0 0 16px;
            font-size: 15px;
        }

        .editorial-contact-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            color: #0f172a !important;
            padding: 10px 22px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14.5px;
            text-decoration: none !important;
            transition: all 0.2s ease;
        }

        .editorial-contact-btn:hover {
            background: #f1f5f9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
        }

        .editorial-footer-note {
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid var(--ep-border);
            color: var(--ep-muted);
            font-size: 14.5px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .editorial-hero {
                padding: 28px 20px;
                border-radius: 16px;
            }

            .editorial-hero h1 {
                font-size: 22px;
            }

            .editorial-section {
                padding: 20px 18px;
                border-radius: 12px;
            }

            .editorial-section h2 {
                font-size: 18px;
            }

            .editorial-contact-card {
                padding: 22px 20px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="editorial-policy-page">

        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="editorial-breadcrumb">
            <ol>
                <li>
                    <a href="{{ route('client.home.index') }}">Trang chủ</a>
                    <span class="separator">/</span>
                </li>
                <li>
                    <a href="{{ route('client.blog.index') }}">Blog</a>
                    <span class="separator">/</span>
                </li>
                <li class="active" aria-current="page">
                    Chính sách biên tập nội dung
                </li>
            </ol>
        </nav>

        {{-- Hero Header --}}
        <header class="editorial-hero">
            <div class="editorial-badges">
                <span class="editorial-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    Minh bạch thông tin
                </span>
                <span class="editorial-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                    Chuẩn mực biên tập
                </span>
            </div>

            <h1>Nguyên tắc biên tập, nguồn thông tin và chính sách đính chính nội dung tại Nobi Fashion</h1>

            <div class="editorial-hero-meta">
                <span>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Cập nhật: 26/09/2026
                </span>
                <span>•</span>
                <span>Ban Biên Tập Nobi Fashion</span>
            </div>
        </header>

        {{-- Main Article --}}
        <article class="editorial-article">

            {{-- Giới thiệu chung --}}
            <div class="editorial-intro-box">
                <p style="margin: 0;">
                    <strong>Nobi Fashion Việt Nam</strong> xây dựng Blog với mục tiêu chia sẻ những thông tin hữu ích về thời trang, phong cách, làm đẹp, đời sống và các chủ đề liên quan. Phần lớn nội dung trên Blog được hình thành thông qua quá trình <strong>tìm kiếm, tổng hợp, tham khảo, đối chiếu và biên tập thông tin từ nhiều nguồn khác nhau</strong>, thay vì coi mọi thông tin được đăng tải là kiến thức hoặc nghiên cứu nguyên bản do Nobi Fashion tự tạo ra.
                </p>
            </div>

            {{-- Mục 1 --}}
            <section class="editorial-section">
                <h2>
                    <span class="editorial-section-number">01</span>
                    Vì sao Nobi Fashion công khai nguyên tắc biên tập nội dung?
                </h2>
                <p>
                    Internet là một kho thông tin rất lớn. Với cùng một chủ đề, người đọc có thể tìm thấy hàng trăm hoặc hàng nghìn bài viết, tài liệu, hình ảnh, ý kiến chuyên môn và kinh nghiệm được chia sẻ từ nhiều cá nhân, tổ chức khác nhau.
                </p>
                <p>
                    Nobi Fashion hiểu rằng không phải mọi thông tin trên Internet đều chính xác tuyệt đối, đồng thời nhiều thông tin có thể thay đổi theo thời gian. Vì vậy, chúng tôi muốn công khai cách nội dung trên Blog được xây dựng để người đọc hiểu rõ hơn về <strong>nguồn gốc, mục đích và giới hạn của thông tin</strong> được đăng tải.
                </p>
                <p>
                    Việc công khai nguyên tắc này không nhằm né tránh trách nhiệm đối với nội dung đã xuất bản. Ngược lại, đây là cam kết của Nobi Fashion về việc tiếp nhận phản hồi, kiểm tra lại thông tin và thực hiện đính chính khi phát hiện nội dung chưa chính xác hoặc không còn phù hợp.
                </p>
            </section>

            {{-- Mục 2 --}}
            <section class="editorial-section">
                <h2>
                    <span class="editorial-section-number">02</span>
                    Nội dung trên Blog Nobi Fashion được hình thành như thế nào?
                </h2>
                <p>
                    Các bài viết trên Blog Nobi Fashion có thể được xây dựng dựa trên quá trình tham khảo và tổng hợp thông tin từ nhiều nguồn công khai khác nhau tùy từng chủ đề:
                </p>
                <ul class="editorial-list disc-list">
                    <li>Website chính thức của thương hiệu, nhà sản xuất hoặc đơn vị liên quan.</li>
                    <li>Tài liệu, catalogue, hướng dẫn và thông tin được công bố công khai.</li>
                    <li>Các trang báo, tạp chí và website chuyên ngành.</li>
                    <li>Các nguồn kiến thức phổ biến trên Internet.</li>
                    <li>Thông tin về xu hướng thời trang và phong cách đang được cộng đồng quan tâm.</li>
                    <li>Kinh nghiệm, nhận xét và ý kiến được chia sẻ trong cộng đồng người dùng.</li>
                    <li>Quan sát và kinh nghiệm trong quá trình tìm hiểu, kinh doanh và tiếp xúc với các sản phẩm thời trang.</li>
                </ul>
                <p>
                    Nobi Fashion <strong>không tuyên bố rằng tất cả kiến thức, dữ liệu hoặc thông tin xuất hiện trên Blog đều do chúng tôi tự nghiên cứu hoặc phát hiện</strong>. Nhiều kiến thức vốn đã tồn tại và được công bố rộng rãi trước đó.
                </p>
                <p>
                    Vai trò của đội ngũ biên tập là tìm hiểu chủ đề, chọn lọc những thông tin được đánh giá là phù hợp, đối chiếu khi cần thiết, sắp xếp lại nội dung theo cách dễ hiểu hơn và trình bày sao cho người đọc có thể nhanh chóng tìm được câu trả lời cho vấn đề mình quan tâm.
                </p>
            </section>

            {{-- Mục 3 --}}
            <section class="editorial-section">
                <h2>
                    <span class="editorial-section-number">03</span>
                    Tổng hợp thông tin không có nghĩa là sao chép nội dung
                </h2>
                <p>
                    Việc một bài viết tham khảo nhiều nguồn khác nhau không đồng nghĩa với việc Nobi Fashion chủ trương sao chép nguyên văn nội dung của website khác.
                </p>
                <p>
                    Mục tiêu của quá trình biên tập là hiểu thông tin từ các nguồn tham khảo, sau đó tổ chức và diễn giải lại theo ngữ cảnh của chủ đề đang được người đọc quan tâm. Khi phù hợp, bài viết có thể bổ sung giải thích, ví dụ, cách lựa chọn, hướng dẫn thực tế hoặc góc nhìn của Nobi Fashion để nội dung trở nên dễ tiếp cận hơn.
                </p>
                <div class="editorial-quote-box">
                    <div class="editorial-quote-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    </div>
                    <div class="editorial-quote-content">
                        <strong>Quan điểm của Nobi Fashion:</strong>
                        <p>Kiến thức trên Internet có thể được chia sẻ và tham khảo từ nhiều nơi, nhưng một bài viết có ích cần giúp người đọc hiểu vấn đề rõ hơn thay vì chỉ lặp lại những thông tin đã có.</p>
                    </div>
                </div>
            </section>

            {{-- Mục 4 --}}
            <section class="editorial-section">
                <h2>
                    <span class="editorial-section-number">04</span>
                    Nobi Fashion không khẳng định mọi thông tin đều chính xác tuyệt đối
                </h2>
                <p>
                    Mặc dù chúng tôi cố gắng kiểm tra và trình bày thông tin một cách hợp lý trước khi xuất bản, Nobi Fashion không thể đảm bảo rằng mọi nội dung trên website luôn chính xác tuyệt đối tại mọi thời điểm.
                </p>
                <p>
                    Sai sót có thể xuất hiện vì nhiều nguyên nhân, chẳng hạn như nguồn tham khảo ban đầu chưa chính xác, thông tin thay đổi theo thời gian, xu hướng thay đổi, sản phẩm được nhà sản xuất cập nhật, dữ liệu cũ chưa được thay thế hoặc quá trình biên tập xuất hiện nhầm lẫn.
                </p>
                <p>
                    Đặc biệt với những chủ đề thay đổi nhanh như xu hướng thời trang, giá bán, thông tin sản phẩm, bảng xếp hạng, chương trình khuyến mại, mạng xã hội hoặc thông tin về cá nhân và thương hiệu, dữ liệu tại thời điểm bạn đọc bài viết có thể khác so với thời điểm bài viết được xuất bản.
                </p>
            </section>

            {{-- Mục 5 --}}
            <section class="editorial-section">
                <h2>
                    <span class="editorial-section-number">05</span>
                    Chúng tôi khuyến khích người đọc phản hồi và đính chính thông tin
                </h2>
                <p>
                    Nobi Fashion luôn hoan nghênh những phản hồi có căn cứ từ độc giả, thương hiệu, tác giả, chủ sở hữu nội dung hoặc những người có chuyên môn trong lĩnh vực liên quan.
                </p>
                <p>
                    Nếu bạn phát hiện một bài viết có thông tin chưa chính xác, thông tin đã lỗi thời, cách diễn đạt có thể gây hiểu nhầm hoặc có nội dung cần bổ sung, vui lòng thông báo cho chúng tôi bằng một trong các hình thức sau:
                </p>
                <ol class="editorial-list alpha-list">
                    <li>Bình luận trực tiếp bên dưới bài viết nếu chức năng bình luận đang được hỗ trợ.</li>
                    <li>Liên hệ trực tiếp với Nobi Fashion qua các kênh liên hệ chính thức trên website.</li>
                    <li>
                        Gửi email tới: 
                        <a href="mailto:{{ $settings->contact_email }}" style="color: #0f172a; font-weight: 600; text-decoration: underline !important;">
                            {{ $settings->contact_email }}
                        </a>
                    </li>
                </ol>
                <p>
                    Khi gửi yêu cầu đính chính, bạn nên cung cấp đường dẫn bài viết, vị trí thông tin cần kiểm tra, nội dung được cho là chưa chính xác và nguồn tham khảo hoặc tài liệu giúp xác minh thông tin nếu có.
                </p>
                <p>
                    Những thông tin cụ thể như vậy sẽ giúp đội ngũ Nobi Fashion kiểm tra vấn đề nhanh và chính xác hơn.
                </p>
            </section>

            {{-- Mục 6 --}}
            <section class="editorial-section">
                <h2>
                    <span class="editorial-section-number">06</span>
                    Nobi Fashion xử lý thông tin cần đính chính như thế nào?
                </h2>
                <p>
                    Khi nhận được phản hồi về một nội dung có khả năng chưa chính xác, chúng tôi có thể tiến hành kiểm tra lại bài viết và đối chiếu với những nguồn thông tin có liên quan. Tùy từng trường hợp, nội dung có thể được:
                </p>
                <ul class="editorial-list square-list">
                    <li>Sửa lại thông tin chưa chính xác.</li>
                    <li>Bổ sung thông tin còn thiếu.</li>
                    <li>Làm rõ cách diễn đạt có thể gây hiểu nhầm.</li>
                    <li>Cập nhật dữ liệu đã lỗi thời.</li>
                    <li>Bổ sung nguồn tham khảo phù hợp.</li>
                    <li>Gỡ bỏ một phần nội dung khi có căn cứ hợp lý.</li>
                    <li>Gỡ hoặc thay đổi toàn bộ bài viết trong trường hợp cần thiết.</li>
                </ul>
                <p>
                    Chúng tôi ưu tiên việc <strong>sửa đúng thông tin</strong> hơn là cố gắng bảo vệ một nội dung đã được chứng minh là không còn chính xác.
                </p>
            </section>

            {{-- Mục 7 --}}
            <section class="editorial-section">
                <h2>
                    <span class="editorial-section-number">07</span>
                    Với ý kiến, đánh giá và nội dung mang tính chủ quan
                </h2>
                <p>
                    Một số bài viết về thời trang và phong cách có thể chứa những nhận định mang tính tham khảo như sản phẩm đẹp, dễ phối, phù hợp với phong cách nào, màu sắc nào đang phổ biến hoặc xu hướng nào đang được nhiều người lựa chọn.
                </p>
                <p>
                    Những đánh giá như vậy không phải lúc nào cũng có một đáp án đúng tuyệt đối. Thời trang chịu ảnh hưởng lớn bởi sở thích cá nhân, vóc dáng, hoàn cảnh sử dụng, văn hóa, thời điểm và xu hướng.
                </p>
                <p>
                    Vì vậy, người đọc nên xem những nội dung này như một nguồn tham khảo để đưa ra lựa chọn phù hợp với bản thân thay vì coi đó là quy tắc bắt buộc.
                </p>
            </section>

            {{-- Mục 8 --}}
            <section class="editorial-section">
                <h2>
                    <span class="editorial-section-number">08</span>
                    Với các nội dung liên quan đến sức khỏe và làm đẹp
                </h2>
                <p>
                    Một số nội dung trên Blog có thể đề cập đến chăm sóc da, mỹ phẩm, tóc, cơ thể hoặc những vấn đề liên quan đến sức khỏe và làm đẹp.
                </p>
                <p>
                    Các nội dung này được cung cấp nhằm mục đích chia sẻ và tham khảo thông tin, <strong>không thay thế cho việc chẩn đoán, điều trị hoặc tư vấn trực tiếp từ bác sĩ, dược sĩ hoặc chuyên gia y tế có chuyên môn</strong>.
                </p>
                <p>
                    Nếu bạn có vấn đề về sức khỏe, dị ứng, bệnh lý hoặc phản ứng bất thường, hãy ưu tiên tham khảo ý kiến của người có chuyên môn phù hợp.
                </p>
            </section>

            {{-- Mục 9 --}}
            <section class="editorial-section">
                <h2>
                    <span class="editorial-section-number">09</span>
                    Tôn trọng tác giả, thương hiệu và chủ sở hữu nội dung
                </h2>
                <p>
                    Nobi Fashion tôn trọng quyền tác giả, quyền sở hữu trí tuệ và quyền lợi hợp pháp của cá nhân, tổ chức và thương hiệu có nội dung được đề cập trên Internet.
                </p>
                <p>
                    Nếu bạn là tác giả, chủ sở hữu hình ảnh, chủ sở hữu nội dung hoặc đại diện của một thương hiệu và cho rằng nội dung trên Nobi Fashion có vấn đề liên quan đến quyền sở hữu, nguồn thông tin hoặc cách sử dụng tài liệu, vui lòng liên hệ với chúng tôi và cung cấp thông tin giúp xác minh quyền sở hữu. Nobi Fashion sẽ kiểm tra phản hồi và thực hiện điều chỉnh phù hợp khi yêu cầu có căn cứ.
                </p>
            </section>

            {{-- Mục 10 --}}
            <section class="editorial-section">
                <h2>
                    <span class="editorial-section-number">10</span>
                    Mục tiêu cuối cùng của Blog Nobi Fashion
                </h2>
                <p>
                    Mục tiêu của Blog Nobi Fashion không phải là tạo ra thật nhiều bài viết chỉ để xuất hiện trên công cụ tìm kiếm. Điều chúng tôi hướng tới là xây dựng một thư viện nội dung mà người đọc có thể sử dụng để tìm hiểu về thời trang, cách phối đồ, lựa chọn sản phẩm, phong cách, làm đẹp và những chủ đề đời sống có liên quan.
                </p>
                <p>
                    Chúng tôi hiểu rằng một website có hàng nghìn bài viết chắc chắn cần được rà soát và cập nhật liên tục. Vì vậy, Nobi Fashion coi phản hồi của độc giả là một phần quan trọng giúp hoàn thiện chất lượng nội dung.
                </p>
                <p>
                    Một bài viết được xuất bản không có nghĩa là bài viết đó sẽ tồn tại mãi ở trạng thái ban đầu. Khi có thông tin mới, nguồn đáng tin cậy hơn hoặc phản hồi chính xác từ người đọc, nội dung có thể tiếp tục được sửa đổi và hoàn thiện.
                </p>
            </section>

            {{-- Mục 11 --}}
            <section class="editorial-section">
                <h2>
                    <span class="editorial-section-number">11</span>
                    Cam kết minh bạch của Nobi Fashion
                </h2>
                <div class="editorial-commitment-card">
                    <strong>Nobi Fashion không coi mọi thông tin trên Blog là những sự thật do chúng tôi tự tạo ra.</strong>
                    <p>• Nội dung có thể được xây dựng từ nhiều nguồn kiến thức đã được công khai, sau đó được tổng hợp, chọn lọc, đối chiếu và biên tập để phục vụ người đọc.</p>
                    <p>• Chúng tôi không cho rằng mình luôn đúng và sẵn sàng tiếp nhận những góp ý có căn cứ.</p>
                    <p>• Khi phát hiện sai sót, mục tiêu của chúng tôi là kiểm tra và sửa lại thông tin thay vì giữ nguyên một nội dung không còn chính xác.</p>
                </div>
            </section>

            {{-- Mục 12 --}}
            <section class="editorial-section" style="margin-bottom: 0;">
                <h2>
                    <span class="editorial-section-number">12</span>
                    Bạn phát hiện thông tin chưa chính xác?
                </h2>
                <p>
                    Nếu bạn đang đọc một bài viết trên Nobi Fashion và nhận thấy thông tin cần được kiểm tra hoặc đính chính, đừng ngần ngại thông báo cho chúng tôi.
                </p>

                <div class="editorial-contact-card">
                    <h3>Góp ý và đính chính nội dung</h3>
                    <p>
                        Bạn có thể bình luận trực tiếp tại bài viết hoặc gửi phản hồi tới hòm thư chính thức của Ban Biên Tập:
                    </p>
                    <a href="mailto:{{ $settings->contact_email }}" class="editorial-contact-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        Gửi phản hồi: {{ $settings->contact_email }}
                    </a>
                </div>
            </section>

            <p class="editorial-footer-note">
                Cảm ơn bạn đã đọc, đóng góp ý kiến và đồng hành cùng Nobi Fashion Việt Nam trong quá trình xây dựng một nguồn thông tin ngày càng hữu ích, minh bạch và chính xác hơn.
            </p>

        </article>
    </div>
@endsection
