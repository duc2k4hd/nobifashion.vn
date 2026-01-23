@extends('admins.layouts.master')

@section('title', 'Quản lý đơn hàng')
@section('page-title', '📦 Đơn hàng')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/order-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .order-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .order-table th, .order-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #eef2f7;
            text-align: left;
            font-size: 13px;
        }
        .order-table th {
            background: #f8fafc;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
        }
        .order-table tr:hover td {
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
        .badge-pending { background:#fef3c7;color:#92400e;}
        .badge-processing { background:#dbeafe;color:#1d4ed8;}
        .badge-completed { background:#dcfce7;color:#15803d;}
        .badge-cancelled { background:#fee2e2;color:#b91c1c;}
        .badge-paid { background:#dcfce7;color:#15803d;}
        .badge-failed { background:#fee2e2;color:#b91c1c;}
    </style>
@endpush

@section('content')
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="margin:0;">Danh sách đơn hàng</h2>
            <a href="{{ route('admin.orders.create') }}" class="btn btn-primary">➕ Tạo đơn hàng</a>
        </div>

        <form class="filter-bar" method="GET">
            <input type="text" name="keyword" placeholder="Tìm mã, tên, SĐT, email..."
                   value="{{ request('keyword') }}" style="flex:1;min-width:200px;">
            <select name="status">
                <option value="">-- Trạng thái --</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Đang xử lý</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
            </select>
            <select name="payment_status">
                <option value="">-- Thanh toán --</option>
                <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Chờ thanh toán</option>
                <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                <option value="failed" {{ request('payment_status') === 'failed' ? 'selected' : '' }}>Thất bại</option>
            </select>
            <select name="delivery_status">
                <option value="">-- Vận chuyển --</option>
                <option value="pending" {{ request('delivery_status') === 'pending' ? 'selected' : '' }}>Chờ giao</option>
                <option value="shipped" {{ request('delivery_status') === 'shipped' ? 'selected' : '' }}>Đang giao</option>
                <option value="delivered" {{ request('delivery_status') === 'delivered' ? 'selected' : '' }}>Đã giao</option>
                <option value="returned" {{ request('delivery_status') === 'returned' ? 'selected' : '' }}>Đã trả</option>
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="Từ ngày">
            <input type="date" name="date_to" value="{{ request('date_to') }}" placeholder="Đến ngày">
            <button type="submit" class="btn btn-primary">🔍 Lọc</button>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">🔄 Làm mới</a>
        </form>

        <div class="table-responsive">
            <table class="order-table">
                <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Người đặt</th>
                    <th>Người nhận</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Thanh toán</th>
                    <th>Vận chuyển</th>
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <strong>{{ $order->code }}</strong>
                        </td>
                        <td>
                            @if($order->account)
                                {{ $order->account->name ?? $order->account->email }}
                            @else
                                <span class="badge badge-guest">Khách</span>
                            @endif
                        </td>
                        <td>
                            <div>{{ $order->receiver_name }}</div>
                            <small style="color:#64748b;">{{ $order->receiver_phone }}</small>
                        </td>
                        <td>
                            <strong>{{ number_format($order->final_price) }} đ</strong>
                        </td>
                        <td>
                            <span class="badge badge-{{ $order->status }}">
                                @if($order->status === 'pending') Chờ xử lý
                                @elseif($order->status === 'processing') Đang xử lý
                                @elseif($order->status === 'completed') Hoàn thành
                                @else Đã hủy
                                @endif
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $order->payment_status }}">
                                @if($order->payment_status === 'pending') Chờ
                                @elseif($order->payment_status === 'paid') Đã thanh toán
                                @else Thất bại
                                @endif
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $order->delivery_status }}">
                                @if($order->delivery_status === 'pending') Chờ giao
                                @elseif($order->delivery_status === 'shipped') Đang giao
                                @elseif($order->delivery_status === 'delivered') Đã giao
                                @elseif($order->delivery_status === 'returned') Đã trả
                                @elseif($order->delivery_status === 'cancelled') Đã hủy hàng
                                @else Không xác định
                                @endif
                            </span>
                        </td>
                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-secondary">👁️</a>
                                @if(!in_array($order->status, ['completed', 'cancelled']))
                                    <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-sm btn-primary">✏️</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:40px;color:#64748b;">
                            Không có đơn hàng nào
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px;">
            {{ $orders->links() }}
        </div>
    </div>
@endsection

