@extends('admins.layouts.master')

@section('title', 'Quản lý giỏ hàng')
@section('page-title', '🛒 Giỏ hàng')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/cart-icon.webp') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .cart-table th, .cart-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #eef2f7;
            text-align: left;
            font-size: 13px;
        }
        .cart-table th {
            background: #f8fafc;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
        }
        .cart-table tr:hover td {
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
        .badge {
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-active { background:#dcfce7;color:#15803d;}
        .badge-ordered { background:#dbeafe;color:#1d4ed8;}
        .badge-abandoned { background:#fee2e2;color:#b91c1c;}
        .badge-user { background:#e2e8f0;color:#475569;}
        .badge-guest { background:#fef3c7;color:#92400e;}
    </style>
@endpush

@section('content')
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="margin:0;">Danh sách giỏ hàng</h2>
        </div>

        <form class="filter-bar" method="GET">
            <input type="text" name="keyword" placeholder="Tìm mã, tên, email..."
                   value="{{ request('keyword') }}">
            <select name="status">
                <option value="">-- Trạng thái --</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                <option value="ordered" {{ request('status') === 'ordered' ? 'selected' : '' }}>Đã đặt hàng</option>
                <option value="abandoned" {{ request('status') === 'abandoned' ? 'selected' : '' }}>Bỏ quên</option>
            </select>
            <select name="has_session">
                <option value="">-- Loại --</option>
                <option value="0" {{ request('has_session') === '0' ? 'selected' : '' }}>Người dùng</option>
                <option value="1" {{ request('has_session') === '1' ? 'selected' : '' }}>Khách</option>
            </select>
            <button type="submit" class="btn btn-primary">Lọc</button>
        </form>

        <div class="table-responsive">
            <table class="cart-table">
                <thead>
                <tr>
                    <th>Mã</th>
                    <th>Người dùng</th>
                    <th>Số lượng</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Cập nhật</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($carts as $cart)
                    <tr>
                        <td><strong>{{ $cart->code ?? '—' }}</strong></td>
                        <td>
                            @if($cart->account)
                                <span class="badge badge-user">{{ $cart->account->name ?? $cart->account->email }}</span>
                            @else
                                <span class="badge badge-guest">Khách ({{ substr($cart->session_id, 0, 8) }}...)</span>
                            @endif
                        </td>
                        <td>{{ number_format($cart->total_quantity) }}</td>
                        <td><strong>{{ number_format($cart->total_price) }} đ</strong></td>
                        <td>
                            @php
                                $statusBadge = match($cart->status) {
                                    'active' => 'badge-active',
                                    'ordered' => 'badge-ordered',
                                    'abandoned' => 'badge-abandoned',
                                    default => '',
                                };
                                $statusText = match($cart->status) {
                                    'active' => 'Đang hoạt động',
                                    'ordered' => 'Đã đặt hàng',
                                    'abandoned' => 'Bỏ quên',
                                    default => $cart->status,
                                };
                            @endphp
                            <span class="badge {{ $statusBadge }}">{{ $statusText }}</span>
                        </td>
                        <td>{{ $cart->updated_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="{{ route('admin.carts.show', $cart) }}" class="btn btn-secondary btn-sm">Xem</a>
                                <a href="{{ route('admin.carts.edit', $cart) }}" class="btn btn-primary btn-sm">Sửa</a>
                                <form action="{{ route('admin.carts.destroy', $cart) }}" method="POST" onsubmit="return confirm('Xác nhận xóa giỏ hàng này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:30px;color:#94a3b8;">Chưa có giỏ hàng nào</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px;">
            {{ $carts->links() }}
        </div>
    </div>
@endsection

