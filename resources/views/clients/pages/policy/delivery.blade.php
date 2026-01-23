@extends('clients.layouts.master')

@section('title', 'Chính sách giao hàng - ' . renderMeta($settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM'))

@section('head')
    <meta name="description"
          content="{{ renderMeta('Chính sách giao hàng ' . ($settings->site_name ?? 'NOBI FASHION VIỆT NAM') . ' - thông tin phí vận chuyển, thời gian giao và hỗ trợ đổi trả trên toàn quốc.') }}">
    <link rel="canonical" href="{{ url()->current() }}">
@endsection

@push('styles')
    @include('clients.pages.policy.partials.styles')
@endpush

@section('content')
    <div class="policy-page">
        <section class="policy-hero">
            <div class="policy-tags">
                <span class="policy-tag">Delivery Policy</span>
                <span class="policy-tag">Nationwide shipping</span>
            </div>
            <h1>Chính sách giao hàng</h1>
            <p>
                <strong>NOBI FASHION</strong> phối hợp cùng các đối tác vận chuyển cao cấp để đảm bảo từng kiện hàng được giao
                nhanh, an toàn và minh bạch trạng thái. Bạn luôn được theo dõi hành trình giao nhận theo thời gian thực và hỗ trợ
                24/7.
            </p>
            <div class="policy-meta">
                <div class="policy-meta-card">
                    <span>Phủ sóng</span>
                    <strong>34 tỉnh thành</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Miễn phí ship</span>
                    <strong>Từ 1.000.000đ</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Hỗ trợ đổi hàng</span>
                    <strong>15 ngày</strong>
                </div>
            </div>
        </section>

        <section class="policy-section">
            <h2>Phương thức giao hàng</h2>
            <div class="policy-grid">
                <div class="policy-card">
                    <strong>COD toàn quốc</strong>
                    <p>Nhận hàng – kiểm tra – thanh toán linh hoạt. Áp dụng cho mọi tỉnh thành.</p>
                </div>
                <div class="policy-card">
                    <strong>Vận chuyển tiêu chuẩn</strong>
                    <p>Kết nối các hãng GHN, GHTK, Viettel Post với bảo hiểm đơn hàng đầy đủ.</p>
                </div>
                <div class="policy-card">
                    <strong>Kiểm hàng trước khi trả</strong>
                    <p>Được mở niêm phong, kiểm sản phẩm trước khi xác nhận thanh toán với shipper.</p>
                </div>
                <div class="policy-card">
                    <strong>Đơn khẩn</strong>
                    <p>Ưu tiên xử lý trong ngày với đơn đặt trước 16h tại nội thành Hải Phòng.</p>
                </div>
            </div>
        </section>

        <section class="policy-section">
            <h2>Phí vận chuyển</h2>
            <ul class="policy-list">
                <li>Miễn phí ship trên toàn quốc cho đơn từ <strong>1.000.000đ</strong>.</li>
                <li>Đơn dưới 1.000.000đ áp dụng phí cố định <strong>20.000đ</strong>.</li>
                <li>Trong các dịp khuyến mãi đặc biệt, phí ship có thể điều chỉnh theo thông báo tại trang thanh toán.</li>
            </ul>
            <div class="policy-note">
                Phí vận chuyển sẽ được hiển thị rõ ràng ở bước Checkout để khách hàng chủ động kiểm soát chi phí.
            </div>
        </section>

        <section class="policy-section">
            <h2>Thời gian giao hàng</h2>
            <div class="policy-timeline">
                <div class="policy-timeline-item">
                    <strong>Nội thành Hải Phòng:</strong> 4 – 24 giờ (giao trong ngày nếu đặt trước 16h).
                </div>
                <div class="policy-timeline-item">
                    <strong>Khu vực lân cận & ngoại tỉnh:</strong> 2 – 5 ngày làm việc tuỳ tuyến vận chuyển.
                </div>
                <div class="policy-timeline-item">
                    <strong>Khu vực xa, hải đảo:</strong> 5 – 7 ngày làm việc.
                </div>
            </div>
            <div class="policy-note">
                Thời gian giao hàng có thể thay đổi vì yếu tố thời tiết, lễ Tết, giãn cách hoặc địa chỉ khó tìm. Bộ phận CSKH
                sẽ chủ động liên hệ khi có phát sinh chậm trễ.
            </div>
        </section>

        <section class="policy-section">
            <h2>Đổi hàng & xử lý sự cố</h2>
            <ul class="policy-list">
                <li>Đổi sản phẩm trong vòng <strong>15 ngày</strong> từ khi nhận hàng.</li>
                <li>Miễn phí đổi mới nếu lỗi phát sinh từ NOBI FASHION hoặc đơn vị vận chuyển.</li>
                <li>Trường hợp thiếu hàng, sai mẫu, hư hại khi vận chuyển hãy liên hệ ngay để được xử lý trong 24h.</li>
            </ul>
        </section>

        <section class="policy-contact">
            <h3>Liên hệ hỗ trợ</h3>
            <p>📞 Hotline: <a href="tel:{{ $settings->contact_phone ?? '' }}">{{ $settings->contact_phone ?? '' }}</a></p>
            <p>✉ Email: <a href="mailto:{{ $settings->contact_email ?? '' }}">{{ $settings->contact_email ?? '' }}</a></p>
            <p>🌐 Website: <a href="{{ $settings->site_url ?? '#' }}">{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}</a></p>
        </section>

        <p class="policy-updated">
            Chính sách giao hàng có hiệu lực từ ngày 01/11/2025 và sẽ được cập nhật định kỳ để nâng cao trải nghiệm của bạn.
        </p>
    </div>
@endsection
