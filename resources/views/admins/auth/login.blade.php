<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập Admin - NobiFashion</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/login-icon.png') }}" type="image/x-icon">
    <style>
        :root {
            color-scheme: light;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #dde8ff, #fef4ff);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.15);
            width: 100%;
            max-width: 420px;
            padding: 40px;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 26px;
            color: #0f172a;
        }
        p.subtitle {
            margin: 0 0 24px;
            color: #64748b;
        }
        label {
            display: block;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 6px;
        }
        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d0d7ee;
            border-radius: 10px;
            font-size: 15px;
        }
        input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .form-group {
            margin-bottom: 18px;
        }
        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #475569;
        }
        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: #2563eb;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        button:hover {
            background: #1d4ed8;
        }
        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 16px;
        }
        .back-home {
            margin-top: 18px;
            text-align: center;
        }
        .back-home a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Đăng nhập Admin</h1>
        <p class="subtitle">Vui lòng đăng nhập để truy cập trang quản trị.</p>

        @if($errors->any())
            <div class="error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.attempt') }}">
            @csrf
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" required>
            </div>
            <label class="remember">
                <input type="checkbox" name="remember" value="1">
                Ghi nhớ đăng nhập
            </label>
            <button type="submit">Đăng nhập</button>
        </form>

        <div class="back-home">
            <a href="{{ route('client.home.index') }}">← Quay lại trang chủ</a>
        </div>
    </div>
</body>
</html>

