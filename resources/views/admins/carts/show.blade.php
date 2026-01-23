@extends('admins.layouts.master')

@section('title', 'Chi tiết giỏ hàng')
@section('page-title', '🛒 Chi tiết giỏ hàng')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/cart-icon.webp') }}" type="image/x-icon">
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
        .info-grid {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
            gap:12px;
        }
        .info-item {
            padding:8px 0;
            border-bottom:1px solid #eef2f7;
        }
        .info-label {
            font-size:12px;
            color:#64748b;
            margin-bottom:4px;
        }
        .info-value {
            font-size:14px;
            font-weight:500;
            color:#0f172a;
        }
        .items-table {
            width:100%;
            border-collapse:collapse;
            background:#fff;
        }
        .items-table th, .items-table td {
            padding:10px;
            border-bottom:1px solid #eef2f7;
            text-align:left;
            font-size:13px;
        }
        .items-table th {
            background:#f8fafc;
            font-weight:600;
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
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="margin:0;">Chi tiết giỏ hàng</h2>
            <div style="display:flex;gap:10px;">
                <a href="{{ route('admin.carts.index') }}" class="btn btn-secondary">↩️ Quay lại</a>
                @if($cart->status === 'active' && $cart->items()->where('status', 'active')->count() > 0)
                    <a href="{{ route('admin.carts.create-order', $cart) }}" class="btn btn-success">📦 Tạo đơn hàng</a>
                @endif
                <a href="{{ route('admin.carts.edit', $cart) }}" class="btn btn-primary">✏️ Sửa</a>
                <form action="{{ route('admin.carts.recalculate', $cart) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-secondary">🔄 Tính lại</button>
                </form>
            </div>
        </div>

        <div class="card">
            <h3>Thông tin giỏ hàng</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Mã giỏ hàng</div>
                    <div class="info-value">{{ $cart->code ?? '—' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Người dùng</div>
                    <div class="info-value">
                        @if($cart->account)
                            {{ $cart->account->name ?? $cart->account->email }}
                        @else
                            Khách ({{ substr($cart->session_id, 0, 16) }}...)
                        @endif
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Trạng thái</div>
                    <div class="info-value">
                        @php
                            $statusText = match($cart->status) {
                                'active' => 'Đang hoạt động',
                                'ordered' => 'Đã đặt hàng',
                                'abandoned' => 'Bỏ quên',
                                default => $cart->status,
                            };
                        @endphp
                        {{ $statusText }}
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tổng số lượng</div>
                    <div class="info-value">{{ number_format($cart->total_quantity) }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tổng tiền</div>
                    <div class="info-value"><strong>{{ number_format($cart->total_price) }} đ</strong></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Ngày tạo</div>
                    <div class="info-value">{{ $cart->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Cập nhật</div>
                    <div class="info-value">{{ $cart->updated_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Sản phẩm trong giỏ ({{ $cart->items->count() }})</h3>
            <div class="table-responsive">
                <table class="items-table">
                    <thead>
                    <tr>
                        <th>Ảnh</th>
                        <th>Sản phẩm</th>
                        <th>Biến thể</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($cart->items as $item)
                        <tr>
                            <td>
                                @php
                                    $imageUrl = $item->variant?->primaryVariantImage
                                        ? asset('clients/assets/img/clothes/' . $item->variant->primaryVariantImage->url)
                                        : ($item->product->primaryImage
                                            ? asset('clients/assets/img/clothes/' . $item->product->primaryImage->url)
                                            : asset('clients/assets/img/clothes/no-image.webp'));
                                @endphp
                                <img src="{{ $imageUrl }}" alt="" class="product-image">
                            </td>
                            <td>
                                <strong>{{ $item->product->name }}</strong><br>
                                <small style="color:#64748b;">SKU: {{ $item->product->sku }}</small>
                            </td>
                            <td>
                                @if($item->variant)
                                    @php
                                        $attrs = is_string($item->variant->attributes) 
                                            ? json_decode($item->variant->attributes, true) 
                                            : $item->variant->attributes;
                                    @endphp
                                    @foreach($attrs as $key => $value)
                                        <span style="font-size:11px;color:#64748b;">{{ ucfirst($key) }}: {{ $value }}</span><br>
                                    @endforeach
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td>{{ number_format($item->quantity) }}</td>
                            <td>{{ number_format($item->price) }} đ</td>
                            <td><strong>{{ number_format($item->total_price) }} đ</strong></td>
                            <td>
                                @if($item->status === 'active')
                                    <span style="color:#15803d;font-size:11px;">✓ Hoạt động</span>
                                @else
                                    <span style="color:#b91c1c;font-size:11px;">✗ Đã xóa</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:4px;">
                                    <a href="{{ route('admin.cart-items.edit', $item) }}" class="btn btn-secondary btn-sm">Sửa</a>
                                    <form action="{{ route('admin.cart-items.destroy', $item) }}" method="POST" onsubmit="return confirm('Xóa sản phẩm này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:30px;color:#94a3b8;">Giỏ hàng trống</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

