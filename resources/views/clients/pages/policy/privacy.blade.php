@extends('clients.layouts.master')

@section('title', 'Chính sách bảo mật - ' . renderMeta($settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM'))

@section('head')
    <meta name="description"
          content="{{ renderMeta('Chính sách bảo mật ' . ($settings->site_name ?? 'NOBI FASHION VIỆT NAM') . ' - cách thu thập, sử dụng và bảo vệ dữ liệu cá nhân của khách hàng.') }}">
    <link rel="canonical" href="{{ url()->current() }}">
@endsection

@push('styles')
    @include('clients.pages.policy.partials.styles')
@endpush

@section('content')
    <div class="policy-page">
        <section class="policy-hero">
            <div class="policy-tags">
                <span class="policy-tag">Privacy Policy</span>
                <span class="policy-tag">Data protection</span>
            </div>
            <h1>Chính sách bảo mật thông tin</h1>
            <p>
                <strong>NOBI FASHION</strong> tôn trọng tuyệt đối quyền riêng tư của bạn. Chính sách này mô tả cách chúng tôi
                thu thập, sử dụng, lưu trữ và bảo vệ dữ liệu cá nhân trong mọi hoạt động mua sắm tại
                <a href="{{ $settings->site_url ?? '#' }}">{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}</a>.
            </p>
            <div class="policy-meta">
                <div class="policy-meta-card">
                    <span>Dữ liệu mã hóa</span>
                    <strong>100%</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Thời hạn lưu trữ</span>
                    <strong>Đến khi khách yêu cầu</strong>
                </div>
                <div class="policy-meta-card">
                    <span>Truy cập dữ liệu</span>
                    <strong>Có kiểm soát</strong>
                </div>
            </div>
        </section>

        <section class="policy-section">
            <h2>Mục đích & phạm vi thu thập</h2>
            <ul class="policy-list">
                <li>Xử lý đơn hàng, giao nhận, chăm sóc khách hàng.</li>
                <li>Liên hệ khi có thay đổi về đơn hoặc chương trình ưu đãi.</li>
                <li>Gửi thông tin khuyến mãi nếu khách đồng ý nhận.</li>
            </ul>
        </section>

        <section class="policy-section">
            <h2>Phạm vi sử dụng thông tin</h2>
            <p>
                Dữ liệu chỉ được dùng để đảm bảo quyền lợi mua sắm và chăm sóc khách hàng. Chúng tôi không bán, chia sẻ hay trao
                đổi thông tin cho bên thứ ba khi chưa được sự đồng ý hoặc yêu cầu hợp pháp.
            </p>
        </section>

        <section class="policy-section">
            <h2>Thời gian lưu trữ</h2>
            <p>
                Thông tin cá nhân được lưu trong hệ thống nội bộ cho đến khi khách hàng yêu cầu xóa hoặc ngừng dịch vụ. Trong
                suốt thời gian lưu trữ, dữ liệu luôn được bảo vệ bằng các chuẩn bảo mật cao.
            </p>
        </section>

        <section class="policy-section">
            <h2>Đơn vị có thể tiếp cận</h2>
            <ul class="policy-list">
                <li>Ban quản lý website <strong>nobifashion.vn</strong>.</li>
                <li>Đối tác vận chuyển để phục vụ giao hàng.</li>
                <li>Cơ quan nhà nước khi có yêu cầu chính thức.</li>
            </ul>
        </section>

        <section class="policy-section">
            <h2>Cam kết bảo mật</h2>
            <ul class="policy-list">
                <li>Áp dụng biện pháp kỹ thuật và quản trị để phòng tránh truy cập trái phép.</li>
                <li>Mã hóa toàn bộ thông tin giao dịch.</li>
                <li>Thông báo kịp thời cho cơ quan chức năng và người dùng nếu xảy ra sự cố bảo mật.</li>
            </ul>
        </section>

        <section class="policy-section">
            <h2>Quyền của khách hàng</h2>
            <ul class="policy-list">
                <li>Kiểm tra, cập nhật, chỉnh sửa hoặc yêu cầu xóa dữ liệu bất kỳ lúc nào.</li>
                <li>Yêu cầu ngừng nhận thông tin marketing.</li>
            </ul>
        </section>

        <section class="policy-contact">
            <h3>Thông tin liên hệ</h3>
            <p>📞 Hotline: <a href="tel:{{ $settings->contact_phone ?? '' }}">{{ $settings->contact_phone ?? '' }}</a></p>
            <p>✉ Email: <a href="mailto:{{ $settings->contact_email ?? '' }}">{{ $settings->contact_email ?? '' }}</a></p>
            <p>🌐 Website: <a href="{{ $settings->site_url ?? '#' }}">{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}</a></p>
        </section>

        <p class="policy-updated">
            Chính sách bảo mật có hiệu lực từ 01/11/2025 và sẽ được cập nhật để phù hợp quy định pháp luật cũng như nhu cầu
            phục vụ khách hàng.
        </p>
    </div>
@endsection
