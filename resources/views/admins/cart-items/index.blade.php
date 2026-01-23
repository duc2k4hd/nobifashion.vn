@extends('admins.layouts.master')

@section('title', 'Quản lý sản phẩm trong giỏ')
@section('page-title', '🛒 Sản phẩm trong giỏ')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/cart-item-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .items-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .items-table th, .items-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #eef2f7;
            text-align: left;
            font-size: 13px;
        }
        .items-table th {
            background: #f8fafc;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
        }
        .items-table tr:hover td {
            background: #f1f5f9;
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
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
        }
    </style>
@endpush

@section('content')
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="margin:0;">Danh sách sản phẩm trong giỏ</h2>
        </div>

        <form class="filter-bar" method="GET">
            <input type="text" name="keyword" placeholder="Tìm sản phẩm..."
                   value="{{ request('keyword') }}">
            <select name="status">
                <option value="">-- Trạng thái --</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Hoạt động</option>
                <option value="removed" {{ request('status') === 'removed' ? 'selected' : '' }}>Đã xóa</option>
            </select>
            <button type="submit" class="btn btn-primary">Lọc</button>
        </form>

        <div class="table-responsive">
            <table class="items-table">
                <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Sản phẩm</th>
                    <th>Giỏ hàng</th>
                    <th>Số lượng</th>
                    <th>Đơn giá</th>
                    <th>Thành tiền</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>
                            @php
                                $imageUrl = $item->variant?->primaryVariantImage
                                    ? asset('clients/assets/img/clothes/' . $item->variant->primaryVariantImage->filename)
                                    : ($item->product->primaryImage
                                        ? asset('clients/assets/img/clothes/' . $item->product->primaryImage->filename)
                                        : asset('clients/assets/img/placeholder.png'));
                            @endphp
                            <img src="{{ $imageUrl }}" alt="" class="product-image">
                        </td>
                        <td>
                            <strong>{{ $item->product->name }}</strong><br>
                            <small style="color:#64748b;">SKU: {{ $item->product->sku }}</small>
                        </td>
                        <td>
                            <a href="{{ route('admin.carts.show', $item->cart) }}">
                                {{ $item->cart->code ?? 'Cart #' . $item->cart->id }}
                            </a>
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
                        <td colspan="8" style="text-align:center;padding:30px;color:#94a3b8;">Chưa có sản phẩm nào</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px;">
            {{ $items->links() }}
        </div>
    </div>
@endsection

