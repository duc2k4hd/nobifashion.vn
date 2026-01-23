@extends('admins.layouts.master')

@section('title', 'Chi tiết Voucher')
@section('page-title', 'Chi tiết Voucher')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/vouchers-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Voucher: {{ $voucher->code }}</h2>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.vouchers.analytics.detail', $voucher->id) }}" class="btn btn-info">
                        <i class="bi bi-graph-up"></i> Analytics
                    </a>
                    <a href="{{ route('admin.vouchers.edit', $voucher) }}" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Sửa
                    </a>
                    <a href="{{ route('admin.vouchers.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Quay lại
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Thông tin chính -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Thông tin Voucher</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Mã voucher:</th>
                            <td><strong class="text-primary">{{ $voucher->code }}</strong></td>
                        </tr>
                        <tr>
                            <th>Tên:</th>
                            <td>{{ $voucher->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Mô tả:</th>
                            <td>{{ $voucher->description ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Loại:</th>
                            <td>
                                <span class="badge bg-info">{{ $voucher->type_label }}</span>
                                <span class="ms-2">{{ $voucher->value_label }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>Trạng thái:</th>
                            <td>
                                @if($voucher->status === 'active')
                                    <span class="badge bg-success">Hoạt động</span>
                                @elseif($voucher->status === 'scheduled')
                                    <span class="badge bg-warning">Đã lên lịch</span>
                                @elseif($voucher->status === 'expired')
                                    <span class="badge bg-danger">Hết hạn</span>
                                @else
                                    <span class="badge bg-secondary">Vô hiệu</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Áp dụng cho:</th>
                            <td>
                                @if($voucher->applicable_to === 'all_products')
                                    <span class="badge bg-primary">Tất cả sản phẩm</span>
                                @elseif($voucher->applicable_to === 'specific_products')
                                    <span class="badge bg-info">Sản phẩm cụ thể</span>
                                    <small class="text-muted">({{ count($voucher->applicable_ids ?? []) }} sản phẩm)</small>
                                @else
                                    <span class="badge bg-info">Danh mục cụ thể</span>
                                    <small class="text-muted">({{ count($voucher->applicable_ids ?? []) }} danh mục)</small>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Thời gian:</th>
                            <td>
                                @if($voucher->start_at)
                                    <strong>Bắt đầu:</strong> {{ $voucher->start_at->format('d/m/Y H:i') }}<br>
                                @endif
                                @if($voucher->end_at)
                                    <strong>Kết thúc:</strong> {{ $voucher->end_at->format('d/m/Y H:i') }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Giới hạn:</th>
                            <td>
                                <strong>Lượt sử dụng:</strong> {{ $voucher->usage_count }} / {{ $voucher->usage_limit ?? '∞' }}<br>
                                @if($voucher->per_user_limit)
                                    <strong>Mỗi người:</strong> {{ $voucher->per_user_limit }} lần
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Điều kiện:</th>
                            <td>
                                @if($voucher->min_order_amount)
                                    <strong>Đơn tối thiểu:</strong> {{ number_format($voucher->min_order_amount, 0, ',', '.') }}đ<br>
                                @endif
                                @if($voucher->max_discount_amount)
                                    <strong>Giảm tối đa:</strong> {{ number_format($voucher->max_discount_amount, 0, ',', '.') }}đ
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Người tạo:</th>
                            <td>{{ $voucher->account->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Ngày tạo:</th>
                            <td>{{ $voucher->created_at->format('d/m/Y H:i:s') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Lịch sử sử dụng -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Lịch sử sử dụng ({{ $orders->total() }} đơn hàng)</h5>
                </div>
                <div class="card-body">
                    @if($orders->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Khách hàng</th>
                                        <th>Giảm giá</th>
                                        <th>Ngày sử dụng</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orders as $order)
                                        <tr>
                                            <td>
                                                <a href="{{ route('admin.orders.show', $order) }}">{{ $order->code }}</a>
                                            </td>
                                            <td>{{ $order->account->name ?? 'Khách vãng lai' }}</td>
                                            <td class="text-success">
                                                -{{ number_format($order->voucher_discount, 0, ',', '.') }}đ
                                            </td>
                                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                @if($order->status === 'completed')
                                                    <span class="badge bg-success">Hoàn thành</span>
                                                @elseif($order->status === 'pending')
                                                    <span class="badge bg-warning">Chờ xử lý</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $order->status }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $orders->links() }}
                        </div>
                    @else
                        <p class="text-muted mb-0">Chưa có đơn hàng nào sử dụng voucher này.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Thống kê -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Thống kê</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Tổng lượt sử dụng:</span>
                            <strong>{{ $stats['total_usage'] }}</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Tổng đơn hàng:</span>
                            <strong>{{ $stats['total_orders'] }}</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Tổng tiền đã giảm:</span>
                            <strong class="text-success">{{ number_format($stats['total_discount'], 0, ',', '.') }}đ</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Số người dùng:</span>
                            <strong>{{ $stats['unique_users'] }}</strong>
                        </div>
                    </div>
                    @if($voucher->usage_limit)
                        <div class="progress mb-2" style="height: 20px;">
                            @php
                                $percentage = min(100, ($voucher->usage_count / $voucher->usage_limit) * 100);
                            @endphp
                            <div class="progress-bar {{ $percentage >= 90 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success') }}" 
                                 role="progressbar" 
                                 style="width: {{ $percentage }}%">
                                {{ number_format($percentage, 1) }}%
                            </div>
                        </div>
                        <small class="text-muted">
                            Đã dùng {{ $voucher->usage_count }}/{{ $voucher->usage_limit }} lượt
                        </small>
                    @endif
                </div>
            </div>

            <!-- Lịch sử thay đổi -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Lịch sử thay đổi</h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    @if($voucher->histories->count() > 0)
                        <div class="timeline">
                            @foreach($voucher->histories->take(10) as $history)
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ $history->action_label }}</strong>
                                        <small class="text-muted">{{ $history->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                    @if($history->note)
                                        <p class="mb-1 small text-muted">{{ $history->note }}</p>
                                    @endif
                                    <small class="text-muted">
                                        Bởi: {{ $history->account->name ?? 'Hệ thống' }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">Chưa có lịch sử thay đổi.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


