@extends('clients.layouts.master')

@section('title', 'Cảm ơn bạn đã thanh toán')

@section('head')
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: #f00;
            opacity: 0;
            animation: confetti-fall 5s linear forwards;
        }
        
        @keyframes confetti-fall {
            0% {
                transform: translateY(-100vh) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
        
        .checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 2;
            stroke-miterlimit: 10;
            stroke: #4ade80;
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .checkmark {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: block;
            stroke-width: 2;
            stroke: #fff;
            stroke-miterlimit: 10;
            margin: 10% auto;
            box-shadow: 0 0 0 rgba(74, 222, 128, 0.4);
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }

        .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }

        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes scale {
            0%, 100% {
                transform: none;
            }
            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }

        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 30px #4ade80;
            }
        }
    </style>
@endsection

@section('foot')
    <script>
        // Set current date
        const now = new Date();
        document.getElementById('order-date').textContent = now.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        // Confetti effect
        function createConfetti() {
            const colors = ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899'];
            
            for (let i = 0; i < 100; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.width = Math.random() * 10 + 5 + 'px';
                confetti.style.height = Math.random() * 10 + 5 + 'px';
                confetti.style.animationDuration = Math.random() * 3 + 2 + 's';
                confetti.style.animationDelay = Math.random() * 2 + 's';
                
                document.body.appendChild(confetti);
                
                // Remove confetti after animation completes
                setTimeout(() => {
                    confetti.remove();
                }, 5000);
            }
            
            // Show a toast notification
            showToast('Receipt downloaded successfully!');
        }

        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-y-10 opacity-0 transition-all duration-300';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.remove('translate-y-10', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
            }, 100);
            
            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-10', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
@endsection
@section('content')
    <main class="flex-grow flex items-center justify-center py-12 px-4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full max-w-2xl">
            <div class="p-8 text-center">
                <div class="relative">
                    <svg class="checkmark mx-auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                        <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>
                </div>
                
                <h2 class="text-3xl font-bold text-gray-800 mt-6">Thanh toán thành công!</h2>
                <p class="text-gray-600 mt-2">Cảm ơn bạn đã mua hàng. Đơn hàng của bạn đang chờ xác nhận. Vui lòng check Email <strong class="text-red-500">({{ $order->shipping_payload['RECEIVER_EMAIL'] }})</strong> thường xuyên để nhận thông báo vận chuyển!</p>
                
                <div class="mt-8 bg-green-50 rounded-lg p-6 text-left">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Chi tiết đơn hàng</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Mã đơn hàng:</span>
                            <span class="font-medium">#{{ $order->code }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Ngày đặt:</span>
                            <span class="font-medium" id="order-date">{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Phương thức thanh toán:</span>
                            <span class="font-medium">{{ $order->payment_method == 'order_payment_cod' ? 'Thanh toán khi nhận hàng (COD)' : 'Thanh toán trực tuyến' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tổng tiền:</span>
                            <span class="font-medium text-green-600">{{ number_format($order->total, 0, ',', '.') }}đ</span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8">
                    <p class="text-gray-600 mb-6">Chúng tôi đã gửi email chi tiết đến <span class="font-medium">{{ $order->shipping_payload['RECEIVER_EMAIL'] }}</span> về đơn hàng của bạn.</p>
                    
                    <div class="flex flex-col sm:flex-row justify-center gap-4">
                        <button onclick="createConfetti()" class="px-6 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition duration-300 flex items-center justify-center">
                            <i class="fas fa-download mr-2"></i> Tải hóa đơn (PDF)
                        </button>
                        <a href="#" class="px-6 py-3 border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg font-medium transition duration-300 flex items-center justify-center">
                            <i class="fas fa-shopping-bag mr-2"></i> Xem đơn hàng
                        </a>
                        <a href="{{ route('client.shop.index') }}" class="px-6 py-3 border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg font-medium transition duration-300 flex items-center justify-center">
                            <i class="fas fa-shopping-bag mr-2"></i> Tiếp tục mua sắm
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 px-8 py-6 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Bạn cần hỗ trợ gì?</h3>
                <p class="text-gray-600 mb-4">Nếu bạn có bất kỳ câu hỏi nào về đơn hàng của mình, vui lòng liên hệ với bộ phận hỗ trợ khách hàng của chúng tôi.</p>
                <div class="flex items-center text-green-600">
                    <i class="fas fa-headset mr-2"></i>
                    <span>...</span>
                </div>
            </div>
        </div>
    </main>
@endsection
    


{{-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You for Your Payment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: #f0f;
            opacity: 0;
            animation: confetti-fall 5s linear forwards;
            z-index: 9999;
        }
        
        @keyframes confetti-fall {
            0% {
                transform: translateY(-100vh) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
        
        .checkmark {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: draw 1s ease-out forwards;
        }
        
        @keyframes draw {
            to {
                stroke-dashoffset: 0;
            }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full bg-white rounded-xl shadow-2xl overflow-hidden transition-all duration-300 transform hover:shadow-3xl">
        <div class="relative">
            <!-- Header with gradient background -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 py-8 px-6 text-center">
                <div class="flex justify-center mb-6">
                    <div class="relative">
                        <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center shadow-lg">
                            <svg class="w-16 h-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path class="checkmark" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="absolute -inset-2 border-4 border-green-300 rounded-full opacity-0 animate-ping-once"></div>
                    </div>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Payment Successful!</h1>
                <p class="text-indigo-100">Thank you for your purchase</p>
            </div>
            
            <!-- Content area -->
            <div class="p-8">
                <div class="text-center mb-8">
                    <p class="text-gray-600 mb-6">Your payment has been processed successfully. A confirmation email has been sent to your registered email address.</p>
                    
                    <div class="bg-blue-50 rounded-lg p-4 mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-600">Order ID:</span>
                            <span class="font-semibold">#ORD-78945612</span>
                        </div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-600">Date:</span>
                            <span class="font-semibold" id="current-date"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Amount Paid:</span>
                            <span class="text-xl font-bold text-indigo-600">$129.99</span>
                        </div>
                    </div>
                    
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">What's next?</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-white p-4 rounded-lg border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                                <div class="text-indigo-500 mb-2">
                                    <i class="fas fa-envelope-open-text text-2xl"></i>
                                </div>
                                <h4 class="font-medium text-gray-700">Check your email</h4>
                                <p class="text-sm text-gray-500">For order confirmation and details</p>
                            </div>
                            <div class="bg-white p-4 rounded-lg border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                                <div class="text-indigo-500 mb-2">
                                    <i class="fas fa-shipping-fast text-2xl"></i>
                                </div>
                                <h4 class="font-medium text-gray-700">Track your order</h4>
                                <p class="text-sm text-gray-500">We'll notify you when it ships</p>
                            </div>
                            <div class="bg-white p-4 rounded-lg border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                                <div class="text-indigo-500 mb-2">
                                    <i class="fas fa-headset text-2xl"></i>
                                </div>
                                <h4 class="font-medium text-gray-700">Need help?</h4>
                                <p class="text-sm text-gray-500">Contact our support team</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <button onclick="window.location.href='#'" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-6 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-store"></i> Continue Shopping
                    </button>
                    <button onclick="window.location.href='#'" class="border border-indigo-600 text-indigo-600 hover:bg-indigo-50 font-medium py-3 px-6 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-file-invoice"></i> View Invoice
                    </button>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 text-center">
                <p class="text-gray-500 text-sm">Having trouble? <a href="#" class="text-indigo-600 hover:underline">Contact Support</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Set current date
        const now = new Date();
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', options);
        
        // Create confetti effect
        function createConfetti() {
            const colors = ['#f0f', '#0ff', '#ff0', '#0f0', '#00f', '#f00'];
            for (let i = 0; i < 100; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDuration = Math.random() * 3 + 2 + 's';
                confetti.style.animationDelay = Math.random() * 2 + 's';
                document.body.appendChild(confetti);
                
                // Remove confetti after animation
                setTimeout(() => {
                    confetti.remove();
                }, 5000);
            }
        }
        
        // Trigger confetti on page load
        window.addEventListener('load', () => {
            createConfetti();
            
            // Add ping animation to checkmark border
            const pingElement = document.querySelector('.animate-ping-once');
            pingElement.classList.remove('opacity-0');
            setTimeout(() => {
                pingElement.classList.add('opacity-0');
            }, 1000);
        });
        
        // Add pulse animation to the main card periodically
        setInterval(() => {
            const card = document.querySelector('.shadow-2xl');
            card.classList.add('pulse');
            setTimeout(() => {
                card.classList.remove('pulse');
            }, 2000);
        }, 8000);
    </script>
</body>
</html> --}}