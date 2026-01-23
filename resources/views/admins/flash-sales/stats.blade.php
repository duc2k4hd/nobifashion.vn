@extends('admins.layouts.master')

@section('title', 'Thống kê Flash Sale: ' . $flashSale->title)
@section('page-title', '📊 Thống kê Flash Sale')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/flash-sale-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }
    .stat-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
    }
    .stat-card h4 {
        margin: 0;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #94a3b8;
    }
    .stat-card .value {
        font-size: 32px;
        font-weight: 700;
        color: #0f172a;
        margin-top: 8px;
    }
    .stat-card .sub-value {
        font-size: 14px;
        color: #64748b;
    }
    .chart-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
        margin-bottom: 24px;
    }
    .chart-card canvas {
        width: 100% !important;
        max-height: 360px;
    }
    .conversion-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
    }
    .conversion-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px;
        background: #f8fafc;
    }
    .conversion-card h5 {
        font-size: 13px;
        text-transform: uppercase;
        color: #94a3b8;
        margin: 0 0 6px 0;
    }
    .conversion-card .metric-value {
        font-size: 26px;
        font-weight: 700;
        color: #0f172a;
    }
    .conversion-card .metric-sub {
        color: #475569;
        font-size: 12px;
    }
    .heatmap-grid {
        overflow-x: auto;
    }
    .heatmap-grid table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    .heatmap-grid th,
    .heatmap-grid td {
        border: 1px solid #e2e8f0;
        text-align: center;
        padding: 6px;
    }
    .heatmap-cell {
        min-width: 34px;
        min-height: 34px;
        border-radius: 6px;
        color: #0f172a;
        font-weight: 600;
    }
    .compare-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16px;
    }
    .compare-table th,
    .compare-table td {
        border: 1px solid #e2e8f0;
        padding: 10px;
        text-align: left;
    }
    .compare-table th {
        background: #f8fafc;
        font-size: 12px;
        text-transform: uppercase;
        color: #64748b;
    }
    .compare-flex {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }
    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    .chart-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #0f172a;
    }
    .table-top {
        width: 100%;
        border-collapse: collapse;
    }
    .table-top th,
    .table-top td {
        padding: 12px 16px;
        border-bottom: 1px solid #edf2f7;
        text-align: left;
    }
    .table-top th {
        font-size: 12px;
        text-transform: uppercase;
        color: #94a3b8;
    }
    .table-top tr:hover td {
        background-color: #f8fafc;
    }
</style>
@endpush

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
    <div>
        <a href="{{ route('admin.flash-sales.items', $flashSale) }}" class="btn btn-secondary">← Quay lại danh sách sản phẩm</a>
        <a href="{{ route('admin.flash-sales.preview', $flashSale) }}" class="btn btn-secondary">👁️ Xem trước</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h4>Tổng sản phẩm</h4>
        <div class="value">{{ $stats['total_items'] }}</div>
        <div class="sub-value">{{ $stats['active_items'] }} đang bật</div>
    </div>
    <div class="stat-card">
        <h4>Tổng đã bán</h4>
        <div class="value">{{ $stats['sold_items'] }}</div>
        <div class="sub-value">{{ $flashSale->items->count() > 0 ? round(($stats['sold_items'] / max(1,$stats['total_stock'])) * 100, 1) : 0 }}% stock</div>
    </div>
    <div class="stat-card">
        <h4>Tổng còn lại</h4>
        <div class="value">{{ $stats['total_remaining'] }}</div>
        <div class="sub-value">Trên {{ $stats['total_stock'] }} stock</div>
    </div>
    <div class="stat-card">
        <h4>Doanh thu</h4>
        <div class="value">{{ number_format($stats['total_revenue'], 0, ',', '.') }}₫</div>
    </div>
</div>

<div class="chart-card">
    <div class="chart-header" style="gap:10px;flex-wrap:wrap;">
        <h3 style="margin-right:auto;">Biểu đồ doanh thu theo thời gian</h3>
        <div>
            <button class="btn btn-sm btn-outline-secondary revenue-interval-btn active" data-interval="hour">Theo giờ</button>
            <button class="btn btn-sm btn-outline-secondary revenue-interval-btn" data-interval="day">Theo ngày</button>
            <button class="btn btn-sm btn-outline-secondary revenue-interval-btn" data-interval="week">Theo tuần</button>
        </div>
    </div>
    <div id="revenueChartWrapper" style="position:relative;min-height:280px;">
        <canvas id="revenueChart"></canvas>
        <div id="revenueChartEmpty" style="display:none;text-align:center;color:#94a3b8;padding:40px;">
            Chưa có dữ liệu doanh thu.
        </div>
    </div>
</div>

<div class="chart-card">
    <div class="chart-header">
        <h3>Tỷ lệ chuyển đổi</h3>
    </div>
    <div id="conversionMetrics" class="conversion-grid">
        <div class="conversion-card">
            <h5>Lượt xem</h5>
            <div class="metric-value" data-metric="views">--</div>
            <div class="metric-sub">Tổng lượt xem Flash Sale</div>
        </div>
        <div class="conversion-card">
            <h5>Lượt thêm giỏ</h5>
            <div class="metric-value" data-metric="cart_adds">--</div>
            <div class="metric-sub">Số lần thêm sản phẩm Flash Sale vào giỏ</div>
        </div>
        <div class="conversion-card">
            <h5>Đơn hàng</h5>
            <div class="metric-value" data-metric="orders">--</div>
            <div class="metric-sub">Số đơn chứa sản phẩm Flash Sale</div>
        </div>
        <div class="conversion-card">
            <h5>Tỷ lệ chuyển đổi</h5>
            <div class="metric-value" data-metric="conversion_rate">--%</div>
            <div class="metric-sub">Đơn hàng / Lượt xem</div>
        </div>
        <div class="conversion-card">
            <h5>Cart → Order</h5>
            <div class="metric-value" data-metric="cart_to_order_rate">--%</div>
            <div class="metric-sub">Đơn hàng / Lượt thêm giỏ</div>
        </div>
    </div>
</div>

<div class="chart-card">
    <div class="chart-header">
        <h3>Heatmap theo khung giờ bán hàng</h3>
    </div>
    <div id="heatmapGrid" class="heatmap-grid">
        <div style="text-align:center;color:#94a3b8;padding:30px;">Đang tải dữ liệu...</div>
    </div>
</div>

<div class="chart-card">
    <div class="chart-header">
        <h3>So sánh các Flash Sale khác</h3>
    </div>
    <div class="compare-flex">
        <select multiple id="flashSaleCompareSelect" class="form-control" style="min-width:200px;">
            @foreach(\App\Models\FlashSale::orderByDesc('start_time')->limit(20)->get() as $sale)
                <option value="{{ $sale->id }}" {{ $sale->id === $flashSale->id ? 'disabled' : '' }}>
                    #{{ $sale->id }} - {{ $sale->title }}
                </option>
            @endforeach
        </select>
        <button class="btn btn-primary" id="compareButton">So sánh</button>
    </div>
    <div id="compareResult">
        <div style="text-align:center;color:#94a3b8;padding:30px;">Chọn 1-3 Flash Sale để so sánh.</div>
    </div>
</div>

<div class="chart-card">
    <div class="chart-header">
        <h3>Top 10 sản phẩm bán chạy</h3>
    </div>
    <table class="table-top">
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>SKU</th>
                <th>Đã bán</th>
                <th>Stock</th>
                <th>Doanh thu</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topSelling as $item)
            <tr>
                <td>{{ $item->product->name ?? 'Sản phẩm' }}</td>
                <td>{{ $item->product->sku ?? 'N/A' }}</td>
                <td>{{ $item->sold }}</td>
                <td>{{ $item->stock }}</td>
                <td>{{ number_format(($item->sale_price ?? 0) * $item->sold, 0, ',', '.') }}₫</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center;padding:30px;color:#94a3b8;">
                    Chưa có dữ liệu bán hàng
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="chart-card">
    <div class="chart-header">
        <h3>Top 5 sản phẩm mang lại doanh thu cao nhất</h3>
    </div>
    <table class="table-top">
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Giá Flash Sale</th>
                <th>Đã bán</th>
                <th>Doanh thu</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topRevenue as $item)
            <tr>
                <td>{{ $item->product->name ?? 'Sản phẩm' }}</td>
                <td>{{ number_format($item->sale_price ?? 0, 0, ',', '.') }}₫</td>
                <td>{{ $item->sold }}</td>
                <td>{{ number_format(($item->sale_price ?? 0) * $item->sold, 0, ',', '.') }}₫</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align:center;padding:30px;color:#94a3b8;">
                    Chưa có dữ liệu doanh thu
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;

        const emptyState = document.getElementById('revenueChartEmpty');
        const buttons = document.querySelectorAll('.revenue-interval-btn');
        let chartInstance = null;

        function setActiveButton(interval) {
            buttons.forEach(btn => {
                if (btn.dataset.interval === interval) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        function renderChart(data, interval) {
            if (!data.length) {
                ctx.style.display = 'none';
                emptyState.style.display = 'block';
                if (chartInstance) {
                    chartInstance.destroy();
                    chartInstance = null;
                }
                return;
            }

            ctx.style.display = '';
            emptyState.style.display = 'none';

            const labels = data.map(item => item.period);
            const revenues = data.map(item => item.revenue);
            const quantities = data.map(item => item.quantity);

            if (chartInstance) {
                chartInstance.destroy();
            }

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Doanh thu (₫)',
                            data: revenues,
                            borderColor: '#38bdf8',
                            backgroundColor: 'rgba(56, 189, 248, 0.2)',
                            tension: 0.3,
                            fill: true,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Số lượng bán',
                            data: quantities,
                            borderColor: '#f97316',
                            backgroundColor: 'rgba(249, 115, 22, 0.15)',
                            tension: 0.3,
                            fill: true,
                            yAxisID: 'y1',
                        }
                    ],
                },
                options: {
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            position: 'left',
                            ticks: {
                                callback: value => new Intl.NumberFormat('vi-VN').format(value) + ' ₫',
                            },
                        },
                        y1: {
                            type: 'linear',
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                            },
                        },
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    if (context.datasetIndex === 0) {
                                        return `${context.dataset.label}: ${new Intl.NumberFormat('vi-VN').format(context.raw)} ₫`;
                                    }
                                    return `${context.dataset.label}: ${context.raw}`;
                                }
                            }
                        }
                    }
                }
            });
        }

        function fetchRevenue(interval = 'hour') {
            setActiveButton(interval);
            fetch(`{{ route('admin.flash-sales.statistics.revenue', $flashSale) }}?interval=${interval}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
                .then(res => res.json())
                .then(json => renderChart(json.data || [], interval))
                .catch(() => {
                    ctx.style.display = 'none';
                    emptyState.style.display = 'block';
                    emptyState.textContent = 'Không thể tải dữ liệu.';
                });
        }

        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                fetchRevenue(btn.dataset.interval);
            });
        });

        fetchRevenue('hour');
    })();

    (function () {
        const container = document.getElementById('conversionMetrics');
        if (!container) return;

        const endpoint = `{{ route('admin.flash-sales.statistics.conversion', $flashSale) }}`;
        const metricElements = container.querySelectorAll('.metric-value');
        metricElements.forEach(el => el.textContent = '...');

        fetch(endpoint, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(res => res.json())
            .then(data => {
                container.querySelector('[data-metric="views"]').textContent = (data.views ?? 0).toLocaleString('vi-VN');
                container.querySelector('[data-metric="cart_adds"]').textContent = (data.cart_adds ?? 0).toLocaleString('vi-VN');
                container.querySelector('[data-metric="orders"]').textContent = (data.orders ?? 0).toLocaleString('vi-VN');
                container.querySelector('[data-metric="conversion_rate"]').textContent = ((data.conversion_rate ?? 0).toFixed(2)) + '%';
                container.querySelector('[data-metric="cart_to_order_rate"]').textContent = ((data.cart_to_order_rate ?? 0).toFixed(2)) + '%';
            })
            .catch(() => {
                container.innerHTML = '<div style="text-align:center;color:#b91c1c;">Không thể tải dữ liệu chuyển đổi.</div>';
            });
    })();

    (function () {
        const grid = document.getElementById('heatmapGrid');
        if (!grid) return;

        const endpoint = `{{ route('admin.flash-sales.statistics.heatmap', $flashSale) }}`;

        fetch(endpoint, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(res => res.json())
            .then(json => renderHeatmap(json.data || []))
            .catch(() => {
                grid.innerHTML = '<div style="text-align:center;color:#b91c1c;padding:30px;">Không thể tải dữ liệu heatmap.</div>';
            });

        function renderHeatmap(data) {
            if (!data.length) {
                grid.innerHTML = '<div style="text-align:center;color:#94a3b8;padding:30px;">Chưa có dữ liệu bán hàng.</div>';
                return;
            }

            const days = [...new Set(data.map(item => item.day))].sort();
            const hours = Array.from({ length: 24 }, (_, i) => i);
            const lookup = {};
            let maxRevenue = 0;

            data.forEach(item => {
                const key = `${item.day}_${item.hour}`;
                lookup[key] = item;
                if (item.revenue > maxRevenue) {
                    maxRevenue = item.revenue;
                }
            });

            const table = document.createElement('table');
            const thead = document.createElement('thead');
            const headerRow = document.createElement('tr');
            headerRow.innerHTML = '<th>Ngày</th>' + hours.map(h => `<th>${String(h).padStart(2, '0')}</th>`).join('');
            thead.appendChild(headerRow);
            table.appendChild(thead);

            const tbody = document.createElement('tbody');
            days.forEach(day => {
                const row = document.createElement('tr');
                const dayCell = document.createElement('th');
                dayCell.textContent = day;
                row.appendChild(dayCell);

                hours.forEach(hour => {
                    const key = `${day}_${hour}`;
                    const cell = document.createElement('td');
                    const entry = lookup[key];
                    if (entry) {
                        const intensity = maxRevenue > 0 ? (entry.revenue / maxRevenue) : 0;
                        const alpha = Math.max(0.2, intensity);
                        cell.innerHTML = `<div class="heatmap-cell" style="background: rgba(59,130,246, ${alpha});">
                            <div style="font-size:11px;">${entry.quantity}</div>
                            <div style="font-size:10px;">${(entry.revenue / 1000).toFixed(0)}k</div>
                        </div>`;
                    } else {
                        cell.innerHTML = '<div class="heatmap-cell" style="background:#f8fafc;color:#cbd5f5;">-</div>';
                    }
                    row.appendChild(cell);
                });

                tbody.appendChild(row);
            });

            table.appendChild(tbody);
            grid.innerHTML = '';
            grid.appendChild(table);
        }
        const compareSelect = document.getElementById('flashSaleCompareSelect');
        const compareButton = document.getElementById('compareButton');
        const compareResult = document.getElementById('compareResult');
        if (compareSelect && compareButton && compareResult) {
            compareButton.addEventListener('click', () => {
                const selected = Array.from(compareSelect.selectedOptions)
                    .map(option => option.value)
                    .filter(Boolean);

                if (!selected.length) {
                    compareResult.innerHTML = '<div style="text-align:center;color:#94a3b8;padding:30px;">Chọn ít nhất 1 Flash Sale.</div>';
                    return;
                }

                if (selected.length > 3) {
                    compareResult.innerHTML = '<div style="text-align:center;color:#b91c1c;padding:30px;">Chỉ so sánh tối đa 3 Flash Sale.</div>';
                    return;
                }

                compareResult.innerHTML = '<div style="text-align:center;color:#94a3b8;padding:30px;">Đang tải dữ liệu...</div>';

                const params = new URLSearchParams();
                params.append('ids', selected.join(','));

                fetch(`{{ route('admin.flash-sales.compare') }}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                    .then(res => res.json())
                    .then(json => renderCompareTable(json.data || []))
                    .catch(() => {
                        compareResult.innerHTML = '<div style="text-align:center;color:#b91c1c;padding:30px;">Không thể tải dữ liệu so sánh.</div>';
                    });
            });
        }

        function renderCompareTable(data) {
            if (!data.length) {
                compareResult.innerHTML = '<div style="text-align:center;color:#94a3b8;padding:30px;">Không có dữ liệu so sánh.</div>';
                return;
            }

            let html = '<table class="compare-table"><thead><tr><th>Tiêu đề</th><th>Thời gian</th><th>Tổng sản phẩm</th><th>Đã bán</th><th>Doanh thu</th><th>% Giảm TB</th></tr></thead><tbody>';
            data.forEach(row => {
                html += `<tr>
                    <td>#${row.id} - ${row.title}</td>
                    <td>${row.period || '-'}</td>
                    <td>${row.total_items}</td>
                    <td>${row.total_sold}</td>
                    <td>${new Intl.NumberFormat('vi-VN').format(row.total_revenue)}₫</td>
                    <td>${row.average_discount ?? 0}%</td>
                </tr>`;
            });
            html += '</tbody></table>';
            compareResult.innerHTML = html;
        }
    })();
</script>
@endpush

