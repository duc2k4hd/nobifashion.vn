<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chào mừng bạn đến với {{ $settings->site_name }}</title>
    <style>
        body {
            background: #f7f7f7;
            margin: 0;
            padding: 0;
            font-family: 'Helvetica', Arial, sans-serif;
        }

        .email-wrapper {
            max-width: 650px;
            background: #ffffff;
            margin: 30px auto;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #ececec;
        }

        /* HEADER */
        .email-header {
            background: linear-gradient(135deg, #ff4b6e, #ff8098);
            padding: 35px 20px 45px 20px;
            text-align: center;
            color: #fff;
            position: relative;
        }

        .email-header img {
            max-width: 150px;
            margin-bottom: 18px;
        }

        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        /* CONTENT */
        .email-content {
            padding: 35px 40px;
            color: #333;
            line-height: 1.6;
            font-size: 16px;
        }

        .email-content h2 {
            font-size: 22px;
            color: #ff4b6e;
            margin-bottom: 16px;
            font-weight: 700;
        }

        .email-btn {
            display: inline-block;
            background: #ff4b6e;
            padding: 14px 26px;
            border-radius: 8px;
            color: #fff !important;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: 0.25s ease;
        }

        .email-btn:hover {
            background: #ff2b52;
        }

        /* Divider */
        .email-divider {
            height: 1px;
            background: #eee;
            margin: 30px 0;
        }

        /* FOOTER */
        .email-footer {
            text-align: center;
            padding: 20px 10px 35px 10px;
            font-size: 13px;
            color: #777;
        }

        .email-footer a {
            color: #ff4b6e;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="email-wrapper">

        <!-- HEADER -->
        <div class="email-header">

            <!-- LOGO -->
            <img src="{{ asset('clients/assets/img/business/' . ($settings->site_logo ?? '')) }}"
                alt="{{ $settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM' }} Logo">

            <h1>Chào mừng bạn đến với {{ $settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM' }}!</h1>
        </div>

        <!-- CONTENT -->
        <div class="email-content">
            <h2>Xin chào {{ $email ?? 'bạn' }},</h2>

            <p>Cảm ơn bạn đã đăng ký nhận bản tin từ <strong>{{ $settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM' }}</strong>.
                Từ hôm nay bạn sẽ nhận được:</p>

            <ul>
                <li>🎁 Ưu đãi độc quyền chỉ dành cho thành viên</li>
                <li>🛍 Thông báo ra mắt bộ sưu tập mới</li>
                <li>🔥 Flash sale – deal sốc theo tuần</li>
                <li>👗 Gợi ý phong cách thời trang theo xu hướng</li>
            </ul>

            <p>Chúng tôi rất vui khi được đồng hành cùng bạn trên hành trình thời trang mỗi ngày.</p>

            <a href="https://nobifashion.vn" class="email-btn">Truy cập website</a>

            <div class="email-divider"></div>

            <p>Nếu bạn không đăng ký bản tin này, vui lòng bỏ qua email.</p>
        </div>

        <!-- FOOTER -->
        <div class="email-footer">
            <p>{!! Blade::render($settings->copyright ?? '') !!}</p>
            <p>Thiết kế bởi <a href="https://www.facebook.com/ducnobi2004">Đức Nobi ❤️</a></p>
            <p>MST: {{ $settings->site_tax_code ?? '' }}</p>
            <br><br>
            <a href="{{ route('client.home.index') }}">Trang chủ</a> ·
            <a href="{{ route('client.policy.privacy') }}">Bảo mật</a> ·
            <a href="{{ route('client.page.contact') }}">Liên hệ</a>
        </div>

    </div>

</body>

</html>
