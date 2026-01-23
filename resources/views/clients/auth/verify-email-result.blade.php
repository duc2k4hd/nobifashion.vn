<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác minh email</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0f172a;
        }
        .card {
            background: #fff;
            padding: 32px;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(15,23,42,0.12);
            width: min(420px, 90%);
            text-align: center;
        }
        h1 {
            font-size: 22px;
            margin-bottom: 12px;
        }
        p {
            margin: 0;
            color: #475569;
        }
        .card.success h1 {
            color: #059669;
        }
        .card.error h1 {
            color: #b91c1c;
        }
        a {
            display: inline-block;
            margin-top: 24px;
            padding: 10px 20px;
            border-radius: 999px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="card {{ $status }}">
        <h1>{{ $status === 'success' ? 'Hoàn tất!' : 'Không thể xác minh' }}</h1>
        <p>{{ $message }}</p>
        <a href="{{ url('/') }}">Về trang chủ</a>
    </div>
</body>
</html>

