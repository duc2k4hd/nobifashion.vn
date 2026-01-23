@extends('clients.layouts.master')

@section('title', 'Chính sách bán hàng - ' . renderMeta($settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM'))

@section('head')
    <meta name="description"
          content="{{ renderMeta('Chính sách bán hàng ' . ($settings->site_name ?? 'NOBI FASHION VIỆT NAM') . ' - cam kết chất lượng, giao hàng, ưu đãi và chăm sóc khách hàng chuyên nghiệp.') }}">
    <link rel="canonical" href="{{ url()->current() }}">
@endsection

@push('styles')
    @include('clients.pages.policy.partials.styles')
@endpush

@section('content')
    <div class="policy-page">
        <section class="policy-hero">
            <div class="policy-tags">
                <span class="policy-tag">Sales Policy</span>
                <span class="policy-tag">Premium service</span>
            </div>
            <h1>Chính sách bán hàng</h1>
            <p>
                <strong>NOBI FASHION</strong> cam kết mang đến trải nghiệm mua sắm đẳng cấp: sản phẩm chuẩn boutique,
                thông tin minh bạch, dịch vụ tư vấn tận tâm và bảo chứng hậu mãi rõ ràng trên mọi kênh bán hàng.
            </p>
            <div class="policy-meta">
                <div class="policy-meta-card">
                    <span>Cam kết chất lượng</span>
                    <strong>100% chính hãng</strong>
                </div>
                    <div class="policy-meta-card">
                    <span>Miễn phí giao hàng</span>
                    <strong>Từ 499.000đ</strong>
                </div>
                <div class="policy-meta-card">
                    <span>CSKH</span>
                    <strong>24/7</strong>
                </div>
            </div>
        </section>

        <section class="policy-section">
            <h2>Cam kết chất lượng sản phẩm</h2>
            <ul class="policy-list">
                <li>Sản phẩm thiết kế, sản xuất theo tiêu chuẩn nghiêm ngặt.</li>
                <li>Hình ảnh hiển thị khớp 95–100% với sản phẩm thực tế.</li>
                <li>Chất liệu đạt chuẩn may mặc Việt Nam, không bán hàng chợ, hàng lỗi.</li>
            </ul>
        </section>

        <section class="policy-section">
            <h2>Giao hàng & chăm sóc đơn</h2>
            <p><strong>NOBI FASHION</strong> giao hàng nhanh – an toàn trên toàn quốc.</p>
            <ul class="policy-list">
                <li><strong>Hà Nội – TP.HCM:</strong> 1 – 2 ngày.</li>
                <li><strong>Các tỉnh khác:</strong> 2 – 5 ngày.</li>
                <li>Cho phép kiểm hàng trước thanh toán (tùy khu vực hỗ trợ COD).</li>
                <li>Đóng gói chống sốc, chống ẩm kỹ lưỡng.</li>
                <li>Miễn phí giao hàng cho đơn từ <strong>499.000đ</strong>.</li>
            </ul>
            <div class="policy-note">
                Phí ship dao động 25.000 – 35.000đ tùy tỉnh và hiển thị rõ ràng ở bước Checkout.
            </div>
        </section>

        <section class="policy-section">
            <h2>Ưu đãi & quyền lợi khách hàng</h2>
            <div class="policy-grid">
                <div class="policy-card">
                    <strong>Voucher định kỳ</strong>
                    <p>Tặng mã giảm giá cho đơn kế tiếp.</p>
                </div>
                <div class="policy-card">
                    <strong>Sinh nhật & VIP</strong>
                    <p>Ưu đãi theo hạng thành viên và dịp sinh nhật.</p>
                </div>
                <div class="policy-card">
                    <strong>Sự kiện mùa lễ</strong>
                    <p>Voucher riêng cho 8/3, 20/10, Tết...</p>
                </div>
            </div>
        </section>

        <section class="policy-section">
            <h2>Tư vấn & hỗ trợ</h2>
            <ul class="policy-list">
                <li>Tư vấn chọn size theo số đo thực tế.</li>
                <li>Hỗ trợ xem hàng, đổi size, đổi mẫu.</li>
                <li>Giải đáp về chất liệu, bảo quản, kết hợp outfit.</li>
                <li>Xử lý khiếu nại nhanh chóng, chuyên nghiệp.</li>
            </ul>
        </section>

        <section class="policy-section">
            <h2>Chính sách đổi trả</h2>
            <ul class="policy-list">
                <li>Đổi hàng trong vòng <strong>15 ngày</strong>.</li>
                <li>Sản phẩm còn tem mác, chưa giặt, không hư hỏng.</li>
                <li>Đổi size/mẫu cùng hoặc cao hơn giá trị.</li>
                <li>Không hoàn tiền trừ trường hợp lỗi kỹ thuật.</li>
                <li>Không áp dụng cho sản phẩm giảm trên 30%, đồ lót, phụ kiện.</li>
            </ul>
            <p style="margin-top: 12px; font-weight: 600;">Đổi do lỗi nhà sản xuất:</p>
            <ul class="policy-list">
                <li>Bung chỉ, lỗi may, lem màu, sai mẫu.</li>
                <li>Đổi mới 100% trong 15 ngày và miễn phí vận chuyển.</li>
            </ul>
        </section>

        <section class="policy-contact">
            <h3>Liên hệ hỗ trợ</h3>
            <p>📞 Hotline: <a href="tel:{{ $settings->contact_phone ?? '' }}">{{ $settings->contact_phone ?? '' }}</a></p>
            <p>✉ Email: <a href="mailto:{{ $settings->contact_email ?? '' }}">{{ $settings->contact_email ?? '' }}</a></p>
            <p>🌐 Website: <a href="{{ $settings->site_url ?? '#' }}">{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}</a></p>
            <p>🛒 Fanpage: <a href="{{ $settings->facebook_link ?? '#' }}" target="_blank">Facebook NOBI FASHION</a></p>
        </section>

        <p class="policy-updated">
            Cảm ơn bạn đã đồng hành cùng NOBI FASHION. Chính sách bán hàng hiệu lực từ 01/11/2025 và sẽ tiếp tục được cập nhật
            để nâng cao chất lượng dịch vụ.
        </p>
    </div>
@endsection
