<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập vào shop {{ renderMeta($settings->subname ?? config('site.short_name')) }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="robots" content="follow, noindex"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .social-btn {
            transition: all 0.3s ease;
        }
        
        .social-btn:hover {
            transform: translateY(-3px) scale(1.05);
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        .nobifashion_message {
            font-size: .9rem;
            color: red;
            text-align: start;
            margin-top: 2px
        }

        .custom-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            z-index: 9999;
            cursor: pointer;
        }

        .custom-toast {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 16px;
            color: #fff;
            max-width: 320px;
            opacity: 0;
            transform: translateX(100%);
            transition: transform 0.4s ease, opacity 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .custom-toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .custom-toast.success {
            background-color: #1c9a4a;
        }

        .custom-toast.error {
            background-color: #ef4444;
        }

        .custom-toast.warning {
            background-color: #f59e0b;
        }

        .custom-toast.info {
            background-color: #3b82f6;
        }

        .custom-toast-icon {
            font-size: 18px;
        }
        
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-6xl w-full mx-auto bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row">
        <!-- Left Side - Illustration -->
        <div class="w-full md:w-1/2 gradient-bg text-white p-10 flex flex-col justify-center items-center">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold mb-2">Chào mừng quay trở lại</h1>
                <p class="opacity-90">Đăng nhập để mua sắm tiện lợi và nhận được hàng ngàn ưu đãi từ {{ renderMeta($settings->subname ?? config('site.short_name')) }}</p>
            </div>
            
            <div class="relative w-full max-w-xs floating">
                <div class="absolute -top-10 -left-10 w-24 h-24 bg-purple-300 rounded-full opacity-20"></div>
                <div class="absolute -bottom-10 -right-10 w-24 h-24 bg-blue-300 rounded-full opacity-20"></div>
                <img src="{{ asset('clients/assets/img/business/'. ($settings->site_logo ?? 'logo-nobi-fashion.png')) }}" alt="Login Illustration" class="relative z-10 w-full">
            </div>
            
            <div class="mt-8 text-center">
                <p class="text-sm opacity-80">Bạn chưa có tài khoản? <a href="{{ route('client.auth.register') }}" class="font-semibold underline hover:opacity-90">Đăng ký</a></p>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="w-full md:w-1/2 p-10 md:p-12 flex flex-col justify-center">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Đăng nhập tài khoản của bạn</h2>
                <p class="text-gray-600 mt-2">Nhập thông tin của bạn để tiếp tục</p>
            </div>

            @if (session('status'))
                <div class="mb-6 p-4 rounded-lg text-sm text-green-700 bg-green-50 border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-lg text-sm text-red-700 bg-red-50 border border-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('client.auth.login.handle') }}" method="POST" class="nobifashion_form_login space-y-6">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="text" name="email" value="{{ old('email') }}" id="email" class="nobifashion_form_login_email input-effect w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none" placeholder="admin@gmail.com">
                    </div>
                    <p class="nobifashion_message nobifashion_message_email">
                        @error('email')
                            {{ $message }}
                        @enderror
                    </p>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Nhập mật khẩu</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input value="{{ old('password') }}" type="password" name="password" id="password" class="nobifashion_form_login_password input-effect w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none" placeholder="••••••••">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer">
                            <i class="fas fa-eye-slash text-gray-400 hover:text-gray-600" id="togglePassword"></i>
                        </div>
                    </div>
                    <p class="nobifashion_message nobifashion_message_password">
                        @error('password')
                            {{ $message }}
                        @enderror
                    </p>
                    <div class="flex justify-end mt-1">
                        <a href="{{ route('client.auth.forgot-password') }}" class="text-sm text-purple-600 hover:text-purple-800">Bạn quên mật khẩu?</a>
                    </div>
                </div>
                
                <div class="flex items-center">
                    <input
                        class="nobifashion_form_login_remember h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                        name="remember"
                        type="checkbox"
                        id="remember"
                        value="1"
                        {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember" class="ml-2 block text-sm text-gray-700">Lưu trạng thái đăng nhập</label>
                </div>
                
                <button type="submit" class="nobifashion_form_login_submit btn-hover w-full py-3 px-4 rounded-lg text-white font-semibold transition duration-300">
                    Đăng nhập
                </button>
                
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">Hoặc đăng nhập bằng</span>
                    </div>
                </div>
                
                <div class="grid grid-cols-3 gap-4">
                    <a href="#" class="social-btn flex items-center justify-center py-2 px-4 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="fab fa-google text-red-500 text-xl"></i>
                    </a>
                    <a style="pointer-events: none; opacity: .2;" @disabled(true) href="#" class="social-btn flex items-center justify-center py-2 px-4 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="fab fa-facebook-f text-blue-600 text-xl"></i>
                    </a>
                    <a style="pointer-events: none; opacity: .2;" @disabled(true) href="#" class="social-btn flex items-center justify-center py-2 px-4 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="fab fa-apple text-gray-800 text-xl"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="custom-toast-container" class="custom-toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('clients/assets/js/main.js') }}"></script>
    <script src="{{ asset('clients/assets/js/login.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hiển thị thông báo từ session
            @if (session('status'))
                if (typeof showCustomToast === 'function') {
                    showCustomToast(@json(session('status')), 'success');
                }
            @endif

            @if (session('success'))
                if (typeof showCustomToast === 'function') {
                    showCustomToast(@json(session('success')), 'success');
                }
            @endif

            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    if (typeof showCustomToast === 'function') {
                        showCustomToast(@json($error), 'error');
                    }
                @endforeach
            @endif
        });
    </script>
</body>
</html>