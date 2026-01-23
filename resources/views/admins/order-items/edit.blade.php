@extends('admins.layouts.master')

@section('title', 'Chỉnh sửa sản phẩm trong đơn')
@section('page-title', '✏️ Chỉnh sửa sản phẩm')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/order-icon.png') }}" type="image/x-icon">
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
            margin:0 0 12px;
            font-size:16px;
            font-weight:600;
            color:#0f172a;
        }
        .grid-2 {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
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
        .product-info {
            display:flex;
            gap:12px;
            align-items:center;
        }
        .product-image {
            width:60px;
            height:60px;
            object-fit:cover;
            border-radius:6px;
        }
    </style>
@endpush

@section('content')
    <form action="{{ route('admin.order-items.update', $orderItem) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="margin:0;">Chỉnh sửa sản phẩm trong đơn</h2>
            <div style="display:flex;gap:10px;">
                <a href="{{ route('admin.orders.show', $orderItem->order) }}" class="btn btn-secondary">↩️ Quay lại</a>
                <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
            </div>
        </div>

        <div class="card">
            <h3>Thông tin đơn hàng</h3>
            <div class="grid-2">
                <div>
                    <label>Mã đơn hàng</label>
                    <div class="readonly-field">{{ $orderItem->order->code }}</div>
                </div>
                <div>
                    <label>Trạng thái đơn hàng</label>
                    <div class="readonly-field">
                        @if($orderItem->order->status === 'pending') Chờ xử lý
                        @elseif($orderItem->order->status === 'processing') Đang xử lý
                        @elseif($orderItem->order->status === 'completed') Hoàn thành
                        @else Đã hủy
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Thông tin sản phẩm</h3>
            <div class="product-info">
                @php
                    $imageUrl = $orderItem->variant?->primaryVariantImage
                        ? asset('clients/assets/img/clothes/' . $orderItem->variant->primaryVariantImage->url)
                        : ($orderItem->product->primaryImage
                            ? asset('clients/assets/img/clothes/' . $orderItem->product->primaryImage->url)
                            : asset('clients/assets/img/clothes/no-image.webp'));
                @endphp
                <img src="{{ $imageUrl }}" alt="" class="product-image">
                <div>
                    <strong>{{ $orderItem->product->name }}</strong><br>
                    <small style="color:#64748b;">SKU: {{ $orderItem->product->sku }}</small>
                    @if($orderItem->variant)
                        <br>
                        @php
                            $attrs = is_string($orderItem->variant->attributes) 
                                ? json_decode($orderItem->variant->attributes, true) 
                                : $orderItem->variant->attributes;
                        @endphp
                        @foreach($attrs as $key => $value)
                            <small style="color:#64748b;">{{ ucfirst($key) }}: {{ $value }}</small>
                        @endforeach
                    @endif
                </div>
            </div>
            <div style="margin-top:12px;padding:8px;background:#fef3c7;border-radius:6px;font-size:12px;color:#92400e;">
                ⚠️ Lưu ý: Sản phẩm và biến thể không thể thay đổi. Chỉ có thể sửa số lượng và giá.
            </div>
        </div>

        <div class="card">
            <h3>Chỉnh sửa thông tin</h3>
            <div class="grid-2">
                <div>
                    <label>Số lượng <span style="color:red;">*</span></label>
                    <input type="number" name="quantity" class="form-control" value="{{ old('quantity', $orderItem->quantity) }}" min="1" required oninput="updateTotal()">
                </div>
                <div>
                    <label>Giá <span style="color:red;">*</span></label>
                    <input type="number" name="price" class="form-control" value="{{ old('price', $orderItem->price) }}" min="0" step="1000" required oninput="updateTotal()">
                </div>
                <div>
                    <label>Thành tiền</label>
                    <input type="text" class="form-control" id="total-display" value="{{ number_format($orderItem->total_price) }} đ" readonly style="background:#f8fafc;">
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:16px;">
            <a href="{{ route('admin.orders.show', $orderItem->order) }}" class="btn btn-secondary">↩️ Quay lại</a>
            <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
        </div>
    </form>

    @push('scripts')
    <script>
        function updateTotal() {
            const quantity = parseFloat(document.querySelector('input[name="quantity"]').value) || 0;
            const price = parseFloat(document.querySelector('input[name="price"]').value) || 0;
            const total = quantity * price;
            document.getElementById('total-display').value = new Intl.NumberFormat('vi-VN').format(total) + ' đ';
        }
    </script>
    @endpush
@endsection

