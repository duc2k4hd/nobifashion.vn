@extends('clients.layouts.master')

@section('title', 'Thanh toán đơn hàng đang chờ | ' . renderMeta($settings->site_name ?? ($settings->subname ?? 'NOBI FASHION')))

@section('head')
    <meta name="robots" content="follow, noindex"/>
@endsection

@section('content')
<div class="nobifashion_order_wrapper" style="max-width:800px;margin:20px auto;padding:16px;background:#fff;border-radius:8px;">
    <h1 style="font-size:20px;margin-bottom:12px;">Đơn hàng đang chờ thanh toán</h1>
    <div style="padding:12px;border:1px solid #eee;border-radius:8px;">
        <p><strong>Mã đơn:</strong> {{ $order->id }}</p>
        <p><strong>Tổng tiền:</strong> {{ number_format($order->final_price, 0, ',', '.') }}đ</p>
        <p><strong>Phương thức:</strong> {{ strtoupper($order->payment_method) }}</p>
        <p><strong>Trạng thái thanh toán:</strong> {{ $order->payment_status }}</p>
    </div>

    <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;">
        @if($checkoutUrl)
            <!-- Nút chuyển đến link thanh toán đã tạo -->
            <a href="{{ $checkoutUrl }}" 
               target="_blank"
               style="padding:12px 20px;background:#10b981;color:#fff;border:none;border-radius:6px;cursor:pointer;text-decoration:none;display:inline-block;font-weight:500;">
               💳 Thanh toán ngay
            </a>
        @endif
        
        <!-- Thay thế form bằng link trực tiếp -->
        <a href="{{ route('payment.pending.retry.get') }}" 
           onclick="return confirm('Bạn có muốn tạo link thanh toán mới?')"
           style="padding:10px 16px;background:#2563eb;color:#fff;border:none;border-radius:6px;cursor:pointer;text-decoration:none;display:inline-block;">
           🔄 Tạo link thanh toán mới
        </a>
        
        <!-- Form backup (ẩn) -->
        <form action="{{ route('payment.pending.retry') }}" method="POST" id="retry-form" style="display:none;">
            @csrf
            <button type="submit" id="retry-btn">Thanh toán lại</button>
        </form>
        <form action="{{ route('payment.pending.cancel') }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn hủy đơn này?');">
            @csrf
            <button type="submit" style="padding:10px 16px;background:#ef4444;color:#fff;border:none;border-radius:6px;cursor:pointer;">❌ Hủy đơn hàng</button>
        </form>
        <a href="{{ route('client.cart.index') }}" style="padding:10px 16px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;">🛒 Về giỏ hàng</a>
    </div>
    
    <!-- Debug info -->
    <div id="debug-info" style="margin-top:20px;padding:10px;background:#f5f5f5;border-radius:6px;display:none;">
        <p><strong>Debug Info:</strong></p>
        <p id="debug-text"></p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const retryForm = document.getElementById('retry-form');
    const retryBtn = document.getElementById('retry-btn');
    const debugInfo = document.getElementById('debug-info');
    const debugText = document.getElementById('debug-text');
    
    retryForm.addEventListener('submit', function(e) {
        console.log('Form submitted');
        debugText.textContent = 'Đang xử lý thanh toán...';
        debugInfo.style.display = 'block';
        retryBtn.textContent = 'Đang xử lý...';
        retryBtn.disabled = true;
        
        // Log form data
        const formData = new FormData(this);
        console.log('Form data:', Object.fromEntries(formData));
        console.log('Form action:', this.action);
        
        // Let form submit normally - Laravel will handle redirect
        // No preventDefault() - let the browser handle the redirect
        
        // Add a small delay to show the loading state
        setTimeout(() => {
            console.log('Form should be submitting now...');
        }, 100);
    });
    
    // Show any flash messages
    @if(session('success'))
        alert('{{ session('success') }}');
    @endif
    
    @if(session('warning'))
        alert('{{ session('warning') }}');
    @endif
    
    @if(session('info'))
        alert('{{ session('info') }}');
    @endif
});
</script>
@endsection



