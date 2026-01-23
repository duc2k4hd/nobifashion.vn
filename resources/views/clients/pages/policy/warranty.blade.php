@extends('clients.layouts.master')

@section('title', 'Chính sách bảo hành - ' . renderMeta($settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM'))

@section('head')
    <meta name="description"
          content="{{ renderMeta('Chính sách bảo hành ' . ($settings->site_name ?? 'NOBI FASHION VIỆT NAM') . ' - phạm vi áp dụng, điều kiện bảo hành và quy trình xử lý chi tiết.') }}">
    <link rel="canonical" href="{{ url()->current() }}">
@endsection

@push('styles')
    @include('clients.pages.policy.partials.styles')
@endpush

@section('content')
    <div class="policy-page">
        <section class="policy-hero">
            <div class="policy-tags">
                <span class="policy-tag">Warranty</span>
                <span class="policy-tag">After-sale care</span>
            </div>
            <h1>Chính sách bảo hành</h1>
            <p>
                Cảm ơn bạn đã tin tưởng lựa chọn <strong>{{ $settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM' }}</strong>.
                Chính sách này áp dụng cho tất cả đơn hàng mua tại showroom, website và các kênh chính thức của NOBI FASHION.
            </p>
        </section>

        <section class="policy-section">
            <h2>1. Phạm vi áp dụng</h2>
            <ul class="policy-list">
                <li>Lỗi kỹ thuật phát sinh từ nhà sản xuất.</li>
                <li>Lỗi chất liệu: phai màu bất thường, bong tróc không do tác động mạnh.</li>
                <li>Lỗi đường may: bung chỉ, may lệch, tuột chỉ trong điều kiện sử dụng thông thường.</li>
                <li>Phụ kiện kèm sản phẩm bị hỏng do lỗi sản xuất.</li>
            </ul>
        </section>

        <section class="policy-section">
            <h2>2. Thời hạn bảo hành</h2>
            <ul class="policy-list">
                <li><strong>30 ngày</strong> kể từ ngày mua trực tiếp.</li>
                <li><strong>30 ngày</strong> kể từ ngày nhận hàng online.</li>
            </ul>
            <div class="policy-note">Vui lòng giữ hóa đơn hoặc mã đơn hàng để được hỗ trợ nhanh chóng.</div>
        </section>

        <section class="policy-section">
            <h2>3. Điều kiện bảo hành</h2>
            <ul class="policy-list">
                <li>Còn tem mác hoặc nhãn nhận diện.</li>
                <li>Không giặt tẩy bằng hóa chất mạnh gây hư hại vải.</li>
                <li>Không rách, cháy, thủng do tác động bên ngoài.</li>
                <li>Không biến dạng do giặt sấy sai quy cách.</li>
                <li>Có hóa đơn mua hàng hoặc mã đơn hợp lệ.</li>
            </ul>
        </section>

        <section class="policy-section">
            <h2>4. Trường hợp không áp dụng</h2>
            <ul class="policy-list">
                <li>Sản phẩm hư hỏng do sử dụng không đúng hướng dẫn.</li>
                <li>Bám mùi hôi, ẩm mốc do bảo quản kém.</li>
                <li>Lem màu do giặt chung với đồ đậm màu.</li>
                <li>Tự ý chỉnh sửa, cắt, nới hoặc thay đổi thiết kế.</li>
                <li>Mất hóa đơn hoặc không xác minh được lịch sử mua.</li>
                <li>Phụ kiện, đồ lót, hàng giảm giá trên 30%.</li>
            </ul>
        </section>

        <section class="policy-section">
            <h2>5. Quy trình tiếp nhận</h2>
            <div class="policy-timeline">
                <div class="policy-timeline-item"><strong>Bước 1:</strong> Liên hệ hotline/inbox/email mô tả lỗi.</div>
                <div class="policy-timeline-item"><strong>Bước 2:</strong> Xác minh đơn hàng và hướng dẫn gửi sản phẩm.</div>
                <div class="policy-timeline-item"><strong>Bước 3:</strong> Kỹ thuật kiểm tra lỗi trong 1–3 ngày.</div>
                <div class="policy-timeline-item"><strong>Bước 4:</strong> Sửa chữa miễn phí, đổi mới 1:1 hoặc hoàn tiền nếu hết hàng.</div>
            </div>
        </section>

        <section class="policy-section">
            <h2>6. Chi phí & thời gian</h2>
            <ul class="policy-list">
                <li>Miễn phí 100% với lỗi nhà sản xuất.</li>
                <li>Khách chịu phí vận chuyển khi lỗi do sử dụng hoặc quá thời hạn.</li>
                <li>Thời gian xử lý: tối thiểu 2 ngày, tối đa 7 ngày với sản phẩm phức tạp.</li>
            </ul>
        </section>

        <section class="policy-contact">
            <h3>Liên hệ hỗ trợ</h3>
            <p>📞 Hotline: <a href="tel:{{ $settings->contact_phone ?? '' }}">{{ $settings->contact_phone ?? '' }}</a></p>
            <p>✉ Email: <a href="mailto:{{ $settings->contact_email ?? '' }}">{{ $settings->contact_email ?? '' }}</a></p>
            <p>🌐 Website: <a href="{{ $settings->site_url ?? '#' }}">{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}</a></p>
        </section>

        <p class="policy-updated">Chính sách bảo hành có hiệu lực từ ngày 01/11/2025 và sẽ được cập nhật để nâng cao quyền lợi khách hàng.</p>
    </div>
@endsection
