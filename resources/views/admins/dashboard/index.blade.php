@extends('admins.layouts.master')

@section('title', 'Dashboard Admin')
@section('page-title', '📊 Dashboard')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/dashboard-icon.png') }}" type="image/x-icon">
@endpush

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@push('styles')
    <style>
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .header h1 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .stat-card-title {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        .stat-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .stat-card-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        .stat-card-change {
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .stat-card-change.positive {
            color: #10b981;
        }
        .stat-card-change.negative {
            color: #ef4444;
        }
        .section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
            font-size: 13px;
            text-transform: uppercase;
        }
        .table tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-primary { background: #e0e7ff; color: #3730a3; }
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s;
        }
        .icon-blue { background: #dbeafe; color: #1e40af; }
        .icon-green { background: #d1fae5; color: #065f46; }
        .icon-purple { background: #e0e7ff; color: #3730a3; }
        .icon-orange { background: #fed7aa; color: #9a3412; }
        .icon-pink { background: #fce7f3; color: #9f1239; }
        .icon-yellow { background: #fef3c7; color: #92400e; }
    </style>
@endpush

@section('content')
    <div class="container">

        <!-- TỔNG QUAN -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Tổng Sản Phẩm</span>
                    <div class="stat-card-icon icon-blue">📦</div>
                </div>
                <div class="stat-card-value">{{ number_format($stats['total_products']) }}</div>
                <div class="stat-card-change">
                    <span>Đang hoạt động: <strong>{{ number_format($stats['active_products']) }}</strong></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Tổng Đơn Hàng</span>
                    <div class="stat-card-icon icon-green">🛒</div>
                </div>
                <div class="stat-card-value">{{ number_format($stats['total_orders']) }}</div>
                <div class="stat-card-change">
                    <span>Hôm nay: <strong>{{ number_format($orders['today']) }}</strong></span>
                    @if($orders['today_change'] != 0)
                        <span class="{{ $orders['today_change'] > 0 ? 'positive' : 'negative' }}">
                            ({{ $orders['today_change'] > 0 ? '+' : '' }}{{ $orders['today_change'] }}%)
                        </span>
                    @endif
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Tổng Khách Hàng</span>
                    <div class="stat-card-icon icon-purple">👥</div>
                </div>
                <div class="stat-card-value">{{ number_format($stats['total_customers']) }}</div>
                <div class="stat-card-change">
                    <span>Đã đăng ký tài khoản</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Doanh Thu Hôm Nay</span>
                    <div class="stat-card-icon icon-orange">💰</div>
                </div>
                <div class="stat-card-value">{{ number_format($revenue['today']) }}₫</div>
                <div class="stat-card-change {{ $revenue['today_change'] >= 0 ? 'positive' : 'negative' }}">
                    @if($revenue['today_change'] != 0)
                        <span>{{ $revenue['today_change'] > 0 ? '↑' : '↓' }} {{ abs($revenue['today_change']) }}%</span>
                        <span>so với hôm qua</span>
                    @else
                        <span>Không có thay đổi</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- DOANH THU -->
        <div class="section">
            <h2 class="section-title">💰 Doanh Thu</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-title">Hôm Nay</span>
                        <div class="stat-card-icon icon-green">📈</div>
                    </div>
                    <div class="stat-card-value">{{ number_format($revenue['today']) }}₫</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-title">Tháng Này</span>
                        <div class="stat-card-icon icon-blue">📊</div>
                    </div>
                    <div class="stat-card-value">{{ number_format($revenue['this_month']) }}₫</div>
                    <div class="stat-card-change {{ $revenue['month_change'] >= 0 ? 'positive' : 'negative' }}">
                        @if($revenue['month_change'] != 0)
                            <span>{{ $revenue['month_change'] > 0 ? '↑' : '↓' }} {{ abs($revenue['month_change']) }}%</span>
                            <span>so với tháng trước</span>
                        @endif
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-title">Năm Nay</span>
                        <div class="stat-card-icon icon-purple">🎯</div>
                    </div>
                    <div class="stat-card-value">{{ number_format($revenue['this_year']) }}₫</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-title">Tổng Doanh Thu</span>
                        <div class="stat-card-icon icon-orange">🏆</div>
                    </div>
                    <div class="stat-card-value">{{ number_format($revenue['all_time']) }}₫</div>
                </div>
            </div>
        </div>

        <!-- BIỂU ĐỒ -->
        <div class="grid-2">
            <div class="section">
                <h2 class="section-title">📈 Doanh Thu 7 Ngày Gần Nhất</h2>
                <div class="chart-container">
                    <canvas id="dailyRevenueChart"></canvas>
                </div>
            </div>
            <div class="section">
                <h2 class="section-title">📊 Đơn Hàng 7 Ngày Gần Nhất</h2>
                <div class="chart-container">
                    <canvas id="dailyOrdersChart"></canvas>
                </div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">📅 Doanh Thu 12 Tháng Gần Nhất</h2>
            <div class="chart-container" style="height: 400px;">
                <canvas id="monthlyRevenueChart"></canvas>
            </div>
        </div>

        <!-- TRẠNG THÁI ĐƠN HÀNG -->
        <div class="section">
            <h2 class="section-title">🛒 Trạng Thái Đơn Hàng</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-title">Chờ Xử Lý</span>
                        <span class="badge badge-warning">{{ $orders['pending'] }}</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ $stats['total_orders'] > 0 ? ($orders['pending'] / $stats['total_orders'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-title">Đang Xử Lý</span>
                        <span class="badge badge-info">{{ $orders['processing'] }}</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ $stats['total_orders'] > 0 ? ($orders['processing'] / $stats['total_orders'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-title">Hoàn Thành</span>
                        <span class="badge badge-success">{{ $orders['completed'] }}</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ $stats['total_orders'] > 0 ? ($orders['completed'] / $stats['total_orders'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-title">Đã Hủy</span>
                        <span class="badge badge-danger">{{ $orders['cancelled'] }}</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ $stats['total_orders'] > 0 ? ($orders['cancelled'] / $stats['total_orders'] * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- THANH TOÁN & GIAO HÀNG -->
        <div class="grid-2">
            <div class="section">
                <h2 class="section-title">💳 Tỷ Lệ Thanh Toán</h2>
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 48px; font-weight: 700; color: #667eea; margin-bottom: 10px;">
                        {{ $paymentStats['paid_percentage'] }}%
                    </div>
                    <div style="color: #666; margin-bottom: 20px;">Đã thanh toán</div>
                    <div style="display: flex; justify-content: space-around; margin-top: 20px;">
                        <div>
                            <div style="font-size: 24px; font-weight: 600; color: #10b981;">{{ $paymentStats['paid'] }}</div>
                            <div style="font-size: 12px; color: #666;">Đã thanh toán</div>
                        </div>
                        <div>
                            <div style="font-size: 24px; font-weight: 600; color: #ef4444;">{{ $paymentStats['unpaid'] }}</div>
                            <div style="font-size: 12px; color: #666;">Chưa thanh toán</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="section">
                <h2 class="section-title">🚚 Tỷ Lệ Giao Hàng</h2>
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 48px; font-weight: 700; color: #667eea; margin-bottom: 10px;">
                        {{ $deliveryStats['delivered_percentage'] }}%
                    </div>
                    <div style="color: #666; margin-bottom: 20px;">Đã giao hàng</div>
                    <div style="display: flex; justify-content: space-around; margin-top: 20px;">
                        <div>
                            <div style="font-size: 24px; font-weight: 600; color: #10b981;">{{ $deliveryStats['delivered'] }}</div>
                            <div style="font-size: 12px; color: #666;">Đã giao</div>
                        </div>
                        <div>
                            <div style="font-size: 24px; font-weight: 600; color: #f59e0b;">{{ $deliveryStats['shipping'] }}</div>
                            <div style="font-size: 12px; color: #666;">Đang giao</div>
                        </div>
                        <div>
                            <div style="font-size: 24px; font-weight: 600; color: #6b7280;">{{ $deliveryStats['pending'] }}</div>
                            <div style="font-size: 12px; color: #666;">Chờ giao</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SẢN PHẨM BÁN CHẠY -->
        <div class="section">
            <h2 class="section-title">🔥 Top 10 Sản Phẩm Bán Chạy</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên Sản Phẩm</th>
                        <th>SKU</th>
                        <th>Số Lượng Bán</th>
                        <th>Doanh Thu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProducts as $index => $product)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><strong>{{ $product['name'] }}</strong></td>
                            <td><code>{{ $product['sku'] }}</code></td>
                            <td><span class="badge badge-primary">{{ number_format($product['total_sold']) }}</span></td>
                            <td><strong>{{ number_format($product['total_revenue']) }}₫</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: #999; padding: 40px;">
                                Chưa có dữ liệu bán hàng
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- ĐƠN HÀNG GẦN ĐÂY -->
        <div class="section">
            <h2 class="section-title">📋 Đơn Hàng Gần Đây</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Mã Đơn</th>
                        <th>Khách Hàng</th>
                        <th>Tổng Tiền</th>
                        <th>Trạng Thái</th>
                        <th>Thanh Toán</th>
                        <th>Ngày Tạo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                        <tr>
                            <td><code>{{ $order->code }}</code></td>
                            <td>{{ $order->account->name ?? $order->receiver_name ?? 'Khách vãng lai' }}</td>
                            <td><strong>{{ number_format($order->final_price) }}₫</strong></td>
                            <td>
                                @if($order->status == 'completed')
                                    <span class="badge badge-success">Hoàn thành</span>
                                @elseif($order->status == 'processing')
                                    <span class="badge badge-info">Đang xử lý</span>
                                @elseif($order->status == 'pending')
                                    <span class="badge badge-warning">Chờ xử lý</span>
                                @else
                                    <span class="badge badge-danger">Đã hủy</span>
                                @endif
                            </td>
                            <td>
                                @if($order->payment_status == 'paid')
                                    <span class="badge badge-success">Đã thanh toán</span>
                                @else
                                    <span class="badge badge-warning">Chưa thanh toán</span>
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: #999; padding: 40px;">
                                Chưa có đơn hàng nào
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- TOP CATEGORIES -->
        <div class="section">
            <h2 class="section-title">📂 Top 10 Danh Mục</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên Danh Mục</th>
                        <th>Slug</th>
                        <th>Số Sản Phẩm</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topCategories as $index => $category)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><strong>{{ $category->name }}</strong></td>
                            <td><code>{{ $category->slug }}</code></td>
                            <td><span class="badge badge-primary">{{ number_format($category->product_count) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: #999; padding: 40px;">
                                Chưa có danh mục nào
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- THÔNG TIN KHÁC -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Voucher</span>
                    <div class="stat-card-icon icon-yellow">🎫</div>
                </div>
                <div class="stat-card-value">{{ $voucherStats['total'] }}</div>
                <div class="stat-card-change">
                    <span>Đang hoạt động: <strong>{{ $voucherStats['active'] }}</strong></span>
                    <br>
                    <span>Đã sử dụng: <strong>{{ $voucherStats['used'] }}</strong></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Liên Hệ Mới</span>
                    <div class="stat-card-icon icon-pink">📧</div>
                </div>
                <div class="stat-card-value">{{ $newContacts }}</div>
                <div class="stat-card-change">
                    <span>Hôm nay</span>
                    <br>
                    <span>Chưa đọc: <strong>{{ $unreadContacts }}</strong></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Danh Mục</span>
                    <div class="stat-card-icon icon-blue">📁</div>
                </div>
                <div class="stat-card-value">{{ $stats['total_categories'] }}</div>
                <div class="stat-card-change">
                    <span>Đang hoạt động</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Đơn Hàng Tháng Này</span>
                    <div class="stat-card-icon icon-green">📊</div>
                </div>
                <div class="stat-card-value">{{ number_format($orders['this_month']) }}</div>
                <div class="stat-card-change">
                    <span>Tổng đơn hàng trong tháng</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Biểu đồ doanh thu 7 ngày
        const dailyRevenueCtx = document.getElementById('dailyRevenueChart');
        if (dailyRevenueCtx) {
            new Chart(dailyRevenueCtx, {
                type: 'line',
                data: {
                    labels: @json(array_column($dailyStats, 'date')),
                    datasets: [{
                        label: 'Doanh Thu (₫)',
                        data: @json(array_column($dailyStats, 'revenue')),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('vi-VN').format(value) + '₫';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Biểu đồ đơn hàng 7 ngày
        const dailyOrdersCtx = document.getElementById('dailyOrdersChart');
        if (dailyOrdersCtx) {
            new Chart(dailyOrdersCtx, {
                type: 'bar',
                data: {
                    labels: @json(array_column($dailyStats, 'date')),
                    datasets: [{
                        label: 'Số Đơn Hàng',
                        data: @json(array_column($dailyStats, 'orders')),
                        backgroundColor: '#764ba2',
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Biểu đồ doanh thu 12 tháng
        const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart');
        if (monthlyRevenueCtx) {
            new Chart(monthlyRevenueCtx, {
                type: 'bar',
                data: {
                    labels: @json(array_column($monthlyStats, 'month')),
                    datasets: [{
                        label: 'Doanh Thu (₫)',
                        data: @json(array_column($monthlyStats, 'revenue')),
                        backgroundColor: 'rgba(102, 126, 234, 0.8)',
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('vi-VN').format(value) + '₫';
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
@endsection

