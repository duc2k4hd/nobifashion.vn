@extends('admins.layouts.master')

@section('title', 'Chi tiết Analytics: ' . $voucher->code)

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/vouchers-icon.png') }}" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Chi tiết Analytics: <span class="text-primary">{{ $voucher->code }}</span></h1>
            <p class="text-muted mb-0">{{ $voucher->name }}</p>
        </div>
        <div>
            <a href="{{ route('admin.vouchers.analytics') }}" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left me-1"></i> Quay lại Dashboard
            </a>
            <a href="{{ route('admin.vouchers.edit', $voucher) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i> Sửa Voucher
            </a>
        </div>
    </div>

    {{-- Date Filter --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Chu kỳ</label>
                    <select name="period" class="form-select">
                        <option value="daily" @selected($period === 'daily')>Theo ngày</option>
                        <option value="weekly" @selected($period === 'weekly')>Theo tuần</option>
                        <option value="monthly" @selected($period === 'monthly')>Theo tháng</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> Lọc
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Key Metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Tổng đơn hàng</p>
                    <h3 class="fw-bold mb-0">{{ number_format($performance['total_orders']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Tổng doanh thu</p>
                    <h3 class="fw-bold mb-0 text-success">{{ number_format($performance['total_revenue'], 0, ',', '.') }}đ</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Conversion Rate</p>
                    <h3 class="fw-bold mb-0 text-primary">{{ number_format($performance['conversion_rate'], 1) }}%</h3>
                    <small class="text-muted">{{ $performance['total_orders'] }} / {{ $voucher->usage_count }} lượt dùng</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">ROI</p>
                    <h3 class="fw-bold mb-0 text-{{ $performance['roi'] > 0 ? 'success' : 'danger' }}">
                        {{ $performance['roi'] > 0 ? '+' : '' }}{{ number_format($performance['roi'], 1) }}%
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Revenue Trend Chart --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Xu hướng Doanh thu</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueTrendChart" height="100"></canvas>
                </div>
            </div>
        </div>

        {{-- Additional Metrics --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Tác động Doanh thu</h6>
                    <div class="mb-3">
                        <small class="text-muted d-block">Revenue Impact</small>
                        <strong class="fs-5">{{ number_format($performance['revenue_impact'], 0, ',', '.') }}đ</strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Tổng giảm giá</small>
                        <strong class="fs-5 text-danger">{{ number_format($performance['total_discount'], 0, ',', '.') }}đ</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Lợi nhuận ròng</small>
                        <strong class="fs-5 text-{{ ($performance['total_revenue'] - $performance['total_discount']) >= 0 ? 'success' : 'danger' }}">
                            {{ number_format($performance['total_revenue'] - $performance['total_discount'], 0, ',', '.') }}đ
                        </strong>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Customer Metrics</h6>
                    <div class="mb-3">
                        <small class="text-muted d-block">Tổng khách hàng</small>
                        <strong class="fs-5">{{ number_format($performance['unique_customers']) }}</strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Khách hàng mới</small>
                        <strong class="fs-5">{{ number_format($performance['new_customers']) }}</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Customer Acquisition Cost</small>
                        <strong class="fs-5">{{ number_format($performance['customer_acquisition_cost'], 0, ',', '.') }}đ</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const revenueData = @json($revenueTrend);
        
        const ctx = document.getElementById('revenueTrendChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: revenueData.map(item => item.period),
                    datasets: [
                        {
                            label: 'Doanh thu',
                            data: revenueData.map(item => item.revenue),
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Giảm giá',
                            data: revenueData.map(item => item.discount),
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            tension: 0.1,
                            yAxisID: 'y',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += new Intl.NumberFormat('vi-VN').format(context.parsed.y) + 'đ';
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush

