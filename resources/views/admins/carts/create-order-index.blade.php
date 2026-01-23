@extends('admins.layouts.master')

@section('title', 'Lên đơn hàng')
@section('page-title', '📦 Lên đơn hàng')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/cart-icon.webp') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .cart-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 16px;
            border: 1px solid #eef2f7;
            transition: all 0.2s;
        }
        .cart-card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-color: #a855f7;
        }
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eef2f7;
        }
        .cart-info {
            flex: 1;
        }
        .cart-code {
            font-weight: 600;
            font-size: 14px;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .cart-owner {
            font-size: 12px;
            color: #64748b;
        }
        .cart-actions {
            display: flex;
            gap: 8px;
        }
        .cart-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 12px;
        }
        .detail-item {
            font-size: 12px;
        }
        .detail-label {
            color: #64748b;
            margin-bottom: 2px;
        }
        .detail-value {
            color: #0f172a;
            font-weight: 500;
        }
        .btn-create-order {
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-create-order:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(168, 85, 247, 0.4);
        }
        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .filter-bar input,
        .filter-bar select {
            padding: 6px 10px;
            border: 1px solid #cbd5f5;
            border-radius: 6px;
            font-size: 13px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
    </style>
@endpush

@section('content')
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="margin:0;">Lên đơn hàng</h2>
            <a href="{{ route('admin.carts.index') }}" class="btn btn-secondary">↩️ Quay lại</a>
        </div>

        <form method="GET" action="{{ route('admin.carts.create-order.index') }}" class="filter-bar">
            <input type="text" name="keyword" placeholder="Tìm kiếm mã, tên, email..." 
                   value="{{ request('keyword') }}" style="flex:1;min-width:200px;">
            <button type="submit" class="btn btn-primary">🔍 Tìm kiếm</button>
            <a href="{{ route('admin.carts.create-order.index') }}" class="btn btn-secondary">🔄 Làm mới</a>
        </form>

        @if($carts->count() > 0)
            @foreach($carts as $cart)
                <div class="cart-card">
                    <div class="cart-header">
                        <div class="cart-info">
                            <div class="cart-code">Mã giỏ hàng: {{ $cart->code ?? '—' }}</div>
                            <div class="cart-owner">
                                @if($cart->account)
                                    👤 {{ $cart->account->name ?? $cart->account->email }}
                                @else
                                    👤 Khách ({{ substr($cart->session_id, 0, 16) }}...)
                                @endif
                            </div>
                        </div>
                        <div class="cart-actions">
                            <a href="{{ route('admin.carts.show', $cart) }}" class="btn btn-secondary" style="font-size:12px;padding:6px 12px;">👁️ Xem</a>
                            <a href="{{ route('admin.carts.create-order', $cart) }}" class="btn-create-order">📦 Tạo đơn hàng</a>
                        </div>
                    </div>
                    <div class="cart-details">
                        <div class="detail-item">
                            <div class="detail-label">Tổng tiền</div>
                            <div class="detail-value">{{ number_format($cart->total_price) }} đ</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Số lượng</div>
                            <div class="detail-value">{{ number_format($cart->total_quantity) }} sản phẩm</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Số sản phẩm</div>
                            <div class="detail-value">{{ $cart->items->count() }} loại</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Cập nhật</div>
                            <div class="detail-value">{{ $cart->updated_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div style="margin-top:20px;">
                {{ $carts->links() }}
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon">🛒</div>
                <h3 style="color:#0f172a;margin-bottom:8px;">Không có giỏ hàng nào</h3>
                <p>Hiện tại không có giỏ hàng nào đang hoạt động và có sản phẩm.</p>
            </div>
        @endif
    </div>
@endsection

