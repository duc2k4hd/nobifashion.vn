@extends('clients.layouts.master')

@section('title', 'Chính sách đổi trả - ' . renderMeta($settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM'))

@section('head')
    <meta name="description"
          content="{{ renderMeta('Chính sách đổi trả & bảo hành ' . ($settings->site_name ?? 'NOBI FASHION VIỆT NAM') . ' - điều kiện đổi hàng, thời gian áp dụng và cách liên hệ hỗ trợ nhanh chóng.') }}">
    <link rel="canonical" href="{{ url()->current() }}">
@endsection

@push('styles')
    @include('clients.pages.policy.partials.styles')
@endpush

@section('content')
    <div class="policy-page">
        <section class="policy-hero">
            <div class="policy-tags">
                <span class="policy-tag">Return & Warranty</span>
                <span class="policy-tag">Customer care</span>
            </div>
            <h1>Chính sách đổi trả & bảo hành</h1>
            <p>
                Chúng tôi muốn mọi trải nghiệm mua sắm đều an tâm. Chính sách đổi trả linh hoạt giúp bạn dễ dàng tinh chỉnh sản
                phẩm phù hợp, đồng thời bảo hành rõ ràng cho mọi đơn hàng mua tại cửa hàng và online.
            </p>
            <div class="policy-meta">
                <div class="policy-meta-card">
                    <span>Thời gian đổi</span>
                    <strong>15 ngày</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Tình trạng sản phẩm</span>
                    <strong>Nguyên tem mác</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Hỗ trợ lỗi kỹ thuật</span>
                    <strong>Đổi mới 100%</strong>
                </div>
            </div>
        </section>

        <section class="policy-section">
            <h2>Điều kiện đổi hàng</h2>
            <ul class="policy-list">
                <li>Đổi trong vòng <strong>15 ngày</strong> kể từ ngày mua tại cửa hàng hoặc ngày nhận hàng online.</li>
                <li>Sản phẩm còn nguyên tem mác, chưa sử dụng, không bẩn, không hư hại.</li>
                <li>Xuất trình hóa đơn hoặc mã đơn hàng khi đổi sản phẩm.</li>
            </ul>
            <div class="policy-grid" style="margin-top: 18px;">
                <div class="policy-card">
                    <strong>Hình thức đổi</strong>
                    <p>Đổi sang sản phẩm cùng/ cao hơn giá trị. Nếu thấp hơn, phần chênh lệch được quy đổi thành voucher.</p>
                </div>
                <div class="policy-card">
                    <strong>Lưu ý khuyến mãi</strong>
                    <p>Đơn khuyến mãi chỉ đổi trong thời gian diễn ra chương trình và không áp dụng hoàn tiền.</p>
                </div>
                <div class="policy-card">
                    <strong>Không áp dụng</strong>
                    <p>Sản phẩm giảm giá từ 30% trở lên, đồ lót, phụ kiện.</p>
                </div>
            </div>
        </section>

        <section class="policy-section">
            <h2>Đổi hàng do lỗi kỹ thuật</h2>
            <p><strong>Áp dụng khi:</strong></p>
            <ul class="policy-list">
                <li>Lỗi kỹ thuật: phai màu, bung chỉ, lỗi may, lỗi chất liệu.</li>
                <li>Sai mẫu mã, kích cỡ, màu sắc so với đơn đặt hàng.</li>
            </ul>
            <div class="policy-note">
                Nếu lỗi thuộc về NOBI FASHION hoặc vận chuyển, chúng tôi đổi mới miễn phí 100% trong vòng 15 ngày.
            </div>
            <p style="margin-top: 12px; font-weight: 600;">Không áp dụng đổi trả với:</p>
            <ul class="policy-list">
                <li>Sản phẩm đã qua sử dụng, hư hỏng do tác động bên ngoài.</li>
                <li>Mất tem mác, thất lạc hóa đơn, không đủ điều kiện theo quy định.</li>
            </ul>
        </section>

        <section class="policy-contact">
            <h3>Hỗ trợ đổi trả</h3>
            <p>📞 Hotline: <a href="tel:{{ $settings->contact_phone ?? '' }}">{{ $settings->contact_phone ?? '' }}</a></p>
            <p>✉ Email: <a href="mailto:{{ $settings->contact_email ?? '' }}">{{ $settings->contact_email ?? '' }}</a></p>
            <p>🌐 Website: <a href="{{ $settings->site_url ?? '#' }}">{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}</a></p>
        </section>

        <p class="policy-updated">
            Chính sách có hiệu lực từ 01/11/2025 và sẽ được cập nhật khi cần để đảm bảo quyền lợi khách hàng.
        </p>
    </div>
@endsection
