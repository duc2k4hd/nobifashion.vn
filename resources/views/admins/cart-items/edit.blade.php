@extends('admins.layouts.master')

@section('title', 'Chỉnh sửa sản phẩm trong giỏ')
@section('page-title', '🛒 Chỉnh sửa sản phẩm')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/cart-item-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .card {
            background:#fff;
            border-radius:10px;
            padding:16px;
            box-shadow:0 1px 6px rgba(15,23,42,0.06);
            margin-bottom:16px;
        }
        .card > h3 {
            margin:0 0 8px;
            font-size:16px;
            font-weight:600;
            color:#0f172a;
        }
        .grid-3 {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
            gap:12px 16px;
        }
        .form-control {
            width:100%;
            padding:8px 10px;
            border:1px solid #cbd5f5;
            border-radius:6px;
            font-size:13px;
        }
        label {
            display:block;
            font-size:13px;
            font-weight:500;
            margin-bottom:4px;
            color:#111827;
        }
        .readonly-field {
            background:#f8fafc;
            border:1px dashed #cbd5f5;
            padding:8px 10px;
            border-radius:6px;
            font-size:13px;
        }
    </style>
@endpush

@section('content')
    <form action="{{ route('admin.cart-items.update', $cartItem) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:16px;">
            <a href="{{ route('admin.carts.show', $cartItem->cart) }}" class="btn btn-secondary">↩️ Quay lại</a>
            <button type="submit" class="btn btn-primary">💾 Lưu</button>
        </div>

        <div class="card">
            <h3>Thông tin sản phẩm</h3>
            <div class="grid-3">
                <div>
                    <label>Sản phẩm</label>
                    <div class="readonly-field">{{ $cartItem->product->name }}</div>
                </div>
                <div>
                    <label>Biến thể</label>
                    <div class="readonly-field">
                        @if($cartItem->variant)
                            @php
                                $attrs = is_string($cartItem->variant->attributes) 
                                    ? json_decode($cartItem->variant->attributes, true) 
                                    : $cartItem->variant->attributes;
                            @endphp
                            @foreach($attrs as $key => $value)
                                {{ ucfirst($key) }}: {{ $value }}@if(!$loop->last), @endif
                            @endforeach
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div>
                    <label>Tồn kho</label>
                    <div class="readonly-field">
                        {{ number_format($cartItem->variant ? $cartItem->variant->stock_quantity : $cartItem->product->stock_quantity) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Chỉnh sửa</h3>
            <div class="grid-3">
                <div>
                    <label>Số lượng</label>
                    <input type="number" name="quantity" class="form-control" 
                           value="{{ old('quantity', $cartItem->quantity) }}" 
                           min="1" 
                           max="{{ $cartItem->variant ? $cartItem->variant->stock_quantity : $cartItem->product->stock_quantity }}"
                           required>
                </div>
                <div>
                    <label>Đơn giá (đ)</label>
                    <input type="number" name="price" class="form-control" 
                           value="{{ old('price', $cartItem->price) }}" 
                           min="0" 
                           step="1000"
                           required>
                    <small style="color:#94a3b8;">Giá hiện tại: {{ number_format($cartItem->variant ? ($cartItem->variant->sale_price ?? $cartItem->variant->price) : ($cartItem->product->sale_price ?? $cartItem->product->price)) }} đ</small>
                </div>
                <div>
                    <label>Thành tiền</label>
                    <div class="readonly-field">
                        <strong id="total-price">{{ number_format($cartItem->total_price) }} đ</strong>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:16px;">
            <a href="{{ route('admin.carts.show', $cartItem->cart) }}" class="btn btn-secondary">↩️ Quay lại</a>
            <button type="submit" class="btn btn-primary">💾 Lưu</button>
        </div>
    </form>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInput = document.querySelector('input[name="quantity"]');
            const priceInput = document.querySelector('input[name="price"]');
            const totalPriceEl = document.getElementById('total-price');

            function updateTotal() {
                const qty = parseInt(quantityInput.value) || 0;
                const price = parseFloat(priceInput.value) || 0;
                const total = qty * price;
                totalPriceEl.textContent = new Intl.NumberFormat('vi-VN').format(total) + ' đ';
            }

            quantityInput.addEventListener('input', updateTotal);
            priceInput.addEventListener('input', updateTotal);
        });
    </script>
    @endpush
@endsection

