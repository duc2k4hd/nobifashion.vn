<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - {{ renderMeta($settings->subname ?? config('site.short_name')) }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="robots" content="nofollow, noindex"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/forgot-password.png') }}" type="image/x-icon">
    <link rel="icon" href="{{ asset('admins/img/icons/forgot-password.png') }}" type="image/x-icon">
    <link rel="mask-icon" href="{{ asset('admins/img/icons/forgot-password.png') }}" color="#000000">
    <link rel="apple-touch-icon" href="{{ asset('admins/img/icons/forgot-password.png') }}">
    <meta name="theme-color" content="#000000">
    <meta name="msapplication-TileColor" content="#000000">
    <meta name="msapplication-TileImage" content="{{ asset('admins/img/icons/forgot-password.png') }}">
    <meta name="msapplication-config" content="{{ asset('admins/img/icons/forgot-password.png') }}">
    <meta name="msapplication-navbutton-color" content="#000000">
    <meta name="msapplication-starturl" content="{{ url('/') }}">
    <meta name="msapplication-window" content="width=device-width, initial-scale=1.0">
    <meta name="msapplication-tap-highlight" content="no">
    <meta name="msapplication-task" content="name=Quên mật khẩu; action-uri={{ route('client.auth.forgot-password') }}; icon-uri={{ asset('admins/img/icons/forgot-password.png') }}">
    <meta name="msapplication-task" content="name=Đăng nhập; action-uri={{ route('client.auth.login') }}; icon-uri={{ asset('admins/img/icons/forgot-password.png') }}">
    <meta name="msapplication-task" content="name=Đăng ký; action-uri={{ route('client.auth.register') }}; icon-uri={{ asset('admins/img/icons/forgot-password.png') }}">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .gradient-bg {
            background: #ffffff;
        }
        
        .input-effect {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .input-effect:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .btn-hover {
            transition: all 0.3s ease;
            background-size: 200% auto;
            background-image: linear-gradient(to right, #007bff 0%, #0056b3 51%, #007bff 100%);
        }
        
        .btn-hover:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="gradient-bg rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Quên mật khẩu?</h1>
            <p class="text-gray-600">Nhập email của bạn để nhận link đặt lại mật khẩu</p>
        </div>

        @if (session('status'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <ul class="list-disc list-inside text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('client.auth.forgot-password.send') }}" class="space-y-6">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-envelope mr-2"></i>Địa chỉ email
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 input-effect focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Nhập email của bạn"
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full py-3 px-4 bg-blue-600 text-white font-semibold rounded-lg btn-hover focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                <i class="fas fa-paper-plane mr-2"></i>Gửi link đặt lại mật khẩu
            </button>
        </form>

        <div class="mt-6 text-center space-y-2">
            <a href="{{ route('client.auth.login') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                <i class="fas fa-arrow-left mr-1"></i>Quay lại đăng nhập
            </a>
            <p class="text-sm text-gray-600">
                Chưa có tài khoản?
                <a href="{{ route('client.auth.register') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                    Đăng ký ngay
                </a>
            </p>
        </div>
    </div>
</body>
</html>

