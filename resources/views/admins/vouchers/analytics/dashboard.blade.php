@extends('admins.layouts.master')

@section('title', 'Voucher Analytics & Reporting')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/vouchers-icon.png') }}" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Voucher Analytics & Reporting</h1>
            <p class="text-muted mb-0">Phân tích hiệu suất, conversion rate, ROI và tác động doanh thu của vouchers.</p>
        </div>
        <a href="{{ route('admin.vouchers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Quay lại
        </a>
    </div>

    {{-- Date Filter --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> Lọc
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Overall Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Tổng đơn hàng</p>
                            <h3 class="fw-bold mb-0">{{ number_format($overallStats['total_orders']) }}</h3>
                        </div>
                        <div class="text-primary" style="font-size: 2rem;">
                            <i class="bi bi-cart-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Tổng doanh thu</p>
                            <h3 class="fw-bold mb-0">{{ number_format($overallStats['total_revenue'], 0, ',', '.') }}đ</h3>
                        </div>
                        <div class="text-success" style="font-size: 2rem;">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Tổng giảm giá</p>
                            <h3 class="fw-bold mb-0 text-danger">{{ number_format($overallStats['total_discount'], 0, ',', '.') }}đ</h3>
                        </div>
                        <div class="text-danger" style="font-size: 2rem;">
                            <i class="bi bi-tag"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Khách hàng</p>
                            <h3 class="fw-bold mb-0">{{ number_format($overallStats['unique_customers']) }}</h3>
                            <small class="text-muted">AOV: {{ number_format($overallStats['average_order_value'], 0, ',', '.') }}đ</small>
                        </div>
                        <div class="text-info" style="font-size: 2rem;">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Top Vouchers by Revenue --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Top Vouchers theo Doanh thu</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Mã</th>
                                    <th>Đơn hàng</th>
                                    <th>Doanh thu</th>
                                    <th>ROI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(array_slice($topVouchers, 0, 10) as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item['voucher']->code }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $item['voucher']->name }}</small>
                                        </td>
                                        <td>{{ number_format($item['total_orders']) }}</td>
                                        <td class="fw-semibold">{{ number_format($item['total_revenue'], 0, ',', '.') }}đ</td>
                                        <td>
                                            <span class="badge bg-{{ $item['roi'] > 0 ? 'success' : 'danger' }}">
                                                {{ $item['roi'] > 0 ? '+' : '' }}{{ number_format($item['roi'], 1) }}%
                                            </span>
                                            <br>
                                            <a href="{{ route('admin.vouchers.analytics.detail', $item['voucher']->id) }}" class="btn btn-sm btn-link p-0 mt-1">
                                                Xem chi tiết
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Chưa có dữ liệu</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Conversion Rate --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Conversion Rate theo Voucher</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Mã</th>
                                    <th>Lượt dùng</th>
                                    <th>Đơn hàng</th>
                                    <th>Conversion</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(array_slice($conversionRates, 0, 10) as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item['voucher_code'] }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $item['voucher_name'] }}</small>
                                        </td>
                                        <td>{{ number_format($item['usage_count']) }}</td>
                                        <td>{{ number_format($item['order_count']) }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                                    <div class="progress-bar bg-{{ $item['conversion_rate'] >= 50 ? 'success' : ($item['conversion_rate'] >= 30 ? 'warning' : 'danger') }}" 
                                                         role="progressbar" 
                                                         style="width: {{ min($item['conversion_rate'], 100) }}%;">
                                                    </div>
                                                </div>
                                                <span class="fw-semibold">{{ number_format($item['conversion_rate'], 1) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Chưa có dữ liệu</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ROI Tracking --}}
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">ROI Tracking - Tác động Doanh thu</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Mã Voucher</th>
                                    <th>Tên</th>
                                    <th>Doanh thu</th>
                                    <th>Giảm giá</th>
                                    <th>Lợi nhuận ròng</th>
                                    <th>ROI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roiTracking as $item)
                                    <tr>
                                        <td><strong>{{ $item['voucher_code'] }}</strong></td>
                                        <td>{{ $item['voucher_name'] }}</td>
                                        <td class="fw-semibold">{{ number_format($item['total_revenue'], 0, ',', '.') }}đ</td>
                                        <td class="text-danger">{{ number_format($item['total_discount'], 0, ',', '.') }}đ</td>
                                        <td class="fw-bold text-{{ $item['net_profit'] >= 0 ? 'success' : 'danger' }}">
                                            {{ number_format($item['net_profit'], 0, ',', '.') }}đ
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $item['roi'] > 0 ? 'success' : 'danger' }} fs-6">
                                                {{ $item['roi'] > 0 ? '+' : '' }}{{ number_format($item['roi'], 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Chưa có dữ liệu</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

