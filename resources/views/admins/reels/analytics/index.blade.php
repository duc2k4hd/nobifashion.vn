@extends('admins.layouts.master')

@section('title', 'Báo cáo phân tích chuyên sâu Reels Analytics')

@section('content')
<div class="nobifashion_admin_reels_wrapper container-fluid px-3 py-3">
    <!-- Header -->
    <div class="nobifashion_admin_reels_page_header mb-3">
        <div>
            <h1 class="nobifashion_admin_reels_page_title">
                <i class="fa-solid fa-chart-line text-primary me-2"></i>Phân tích & Thống kê Chuyên sâu Reels
            </h1>
            <div class="nobifashion_admin_reels_breadcrumb">
                <a href="{{ route('admin.reels.dashboard') }}">Reels</a>
                <span>/</span>
                <span>Báo cáo hiệu suất & Hành vi người xem</span>
            </div>
        </div>
        <div class="nobifashion_admin_reels_page_actions">
            <!-- Time Filter -->
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ route('admin.reels.analytics.index', ['days' => 7]) }}" class="nobifashion_admin_reels_btn {{ $days == 7 ? 'nobifashion_admin_reels_btn_primary' : 'nobifashion_admin_reels_btn_secondary' }}">7 ngày</a>
                <a href="{{ route('admin.reels.analytics.index', ['days' => 14]) }}" class="nobifashion_admin_reels_btn {{ $days == 14 ? 'nobifashion_admin_reels_btn_primary' : 'nobifashion_admin_reels_btn_secondary' }}">14 ngày</a>
                <a href="{{ route('admin.reels.analytics.index', ['days' => 30]) }}" class="nobifashion_admin_reels_btn {{ $days == 30 ? 'nobifashion_admin_reels_btn_primary' : 'nobifashion_admin_reels_btn_secondary' }}">30 ngày</a>
                <a href="{{ route('admin.reels.analytics.index', ['days' => 90]) }}" class="nobifashion_admin_reels_btn {{ $days == 90 ? 'nobifashion_admin_reels_btn_primary' : 'nobifashion_admin_reels_btn_secondary' }}">90 ngày</a>
            </div>
            <a href="{{ route('admin.reels.analytics.visitors') }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline">
                <i class="fa-solid fa-users-viewfinder me-1"></i> Nhật ký phiên truy cập
            </a>
        </div>
    </div>

    <!-- Subnav Tabs -->
    @include('admins.reels.partials.subnav')

    <!-- KPI SUMMARY CARDS -->
    @php
        $sum = $overview['summary'] ?? [];
        $totalHours = isset($sum['total_watch_time']) ? round($sum['total_watch_time'] / 3600, 1) : 0;
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="nobifashion_admin_reels_kpi_card">
                <div class="nobifashion_admin_reels_kpi_label">Phiên truy cập</div>
                <div class="nobifashion_admin_reels_kpi_value text-dark">{{ number_format($sum['total_sessions'] ?? 0) }}</div>
                <div class="small text-muted mt-1"><i class="fa-solid fa-arrow-trend-up text-success me-1"></i>{{ $days }} ngày qua</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="nobifashion_admin_reels_kpi_card">
                <div class="nobifashion_admin_reels_kpi_label">Lượt xem video</div>
                <div class="nobifashion_admin_reels_kpi_value text-primary">{{ number_format($sum['total_watches'] ?? 0) }}</div>
                <div class="small text-muted mt-1">Watch logs thực tế</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="nobifashion_admin_reels_kpi_card">
                <div class="nobifashion_admin_reels_kpi_label">Tổng giờ xem</div>
                <div class="nobifashion_admin_reels_kpi_value text-warning">{{ $totalHours }}h</div>
                <div class="small text-muted mt-1">{{ number_format($sum['total_watch_time'] ?? 0) }} giây</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="nobifashion_admin_reels_kpi_card">
                <div class="nobifashion_admin_reels_kpi_label">Tỷ lệ xem hết (Completion)</div>
                <div class="nobifashion_admin_reels_kpi_value text-success">{{ $sum['avg_completion_rate'] ?? 0 }}%</div>
                <div class="small text-muted mt-1">Độ giữ chân trung bình</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="nobifashion_admin_reels_kpi_card">
                <div class="nobifashion_admin_reels_kpi_label">Tỷ lệ xem lặp (Loop)</div>
                <div class="nobifashion_admin_reels_kpi_value text-info">{{ $sum['avg_loops'] ?? 1 }}x</div>
                <div class="small text-muted mt-1">Lặp lại trên mỗi video</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="nobifashion_admin_reels_kpi_card">
                <div class="nobifashion_admin_reels_kpi_label">Click vào sản phẩm</div>
                <div class="nobifashion_admin_reels_kpi_value text-danger">{{ number_format($interactionRates->total_product_clicks ?? 0) }}</div>
                <div class="small text-muted mt-1">Tỷ lệ: {{ $productClickRate }}%</div>
            </div>
        </div>
    </div>

    <!-- INTERACTION CONVERSION RATES -->
    <div class="nobifashion_admin_reels_card mb-4">
        <div class="nobifashion_admin_reels_card_header">
            <h3 class="nobifashion_admin_reels_card_title">
                <i class="fa-solid fa-bullseye text-primary me-2"></i>Tỷ lệ chuyển đổi hành vi tương tác trên lượt xem
            </h3>
        </div>
        <div class="nobifashion_admin_reels_card_body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="p-3 border rounded bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Tỷ lệ Thích (Like Rate)</span>
                            <span class="badge bg-danger">{{ $likeRate }}%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-danger" style="width: {{ min(100, $likeRate * 5) }}%"></div>
                        </div>
                        <div class="small text-muted mt-2">{{ number_format($interactionRates->total_likes ?? 0) }} lượt thả tim</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 border rounded bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Tỷ lệ Bình luận</span>
                            <span class="badge bg-primary">{{ $commentRate }}%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: {{ min(100, $commentRate * 10) }}%"></div>
                        </div>
                        <div class="small text-muted mt-2">{{ number_format($interactionRates->total_comments ?? 0) }} bình luận</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 border rounded bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Tỷ lệ Lưu Video (Save Rate)</span>
                            <span class="badge bg-warning text-dark">{{ $saveRate }}%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-warning" style="width: {{ min(100, $saveRate * 10) }}%"></div>
                        </div>
                        <div class="small text-muted mt-2">{{ number_format($interactionRates->total_saves ?? 0) }} lượt lưu lại</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 border rounded bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Tỷ lệ Chia sẻ (Share Rate)</span>
                            <span class="badge bg-success">{{ $shareRate }}%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: {{ min(100, $shareRate * 10) }}%"></div>
                        </div>
                        <div class="small text-muted mt-2">{{ number_format($interactionRates->total_shares ?? 0) }} lượt chia sẻ</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DEVICE & PLATFORMS & RESOLUTION -->
    <div class="row g-3 mb-4">
        <!-- Devices -->
        <div class="col-lg-4">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-mobile-screen-button text-primary me-2"></i>Thiết bị truy cập
                    </h3>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    <div class="d-flex justify-content-center mb-3">
                        <div style="height: 180px; width: 180px;">
                            <canvas id="deviceChart"></canvas>
                        </div>
                    </div>
                    <ul class="list-group list-group-flush small">
                        @foreach($overview['devices'] ?? [] as $dev)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>{{ ucfirst($dev->device_type ?: 'Chưa xác định') }}</span>
                                <span class="fw-bold">{{ number_format($dev->total) }} phiên</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <!-- Platforms / OS -->
        <div class="col-lg-4">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-brands fa-apple text-dark me-2"></i>Hệ điều hành phổ biến
                    </h3>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    <ul class="list-group list-group-flush">
                        @forelse($overview['platforms'] ?? [] as $plat)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <div>
                                    @if(stripos($plat->platform, 'iOS') !== false || stripos($plat->platform, 'mac') !== false)
                                        <i class="fa-brands fa-apple text-dark me-2"></i>
                                    @elseif(stripos($plat->platform, 'Android') !== false)
                                        <i class="fa-brands fa-android text-success me-2"></i>
                                    @elseif(stripos($plat->platform, 'Windows') !== false)
                                        <i class="fa-brands fa-windows text-primary me-2"></i>
                                    @else
                                        <i class="fa-solid fa-globe text-secondary me-2"></i>
                                    @endif
                                    <strong>{{ $plat->platform ?: 'Khác' }}</strong>
                                </div>
                                <span class="badge bg-light text-dark border">{{ number_format($plat->total) }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted py-3">Chưa có dữ liệu nền tảng</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <!-- Screen Resolutions -->
        <div class="col-lg-4">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-expand text-info me-2"></i>Độ phân giải màn hình
                    </h3>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    <ul class="list-group list-group-flush">
                        @forelse($screenResolutions as $res)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <code>{{ $res->screen_resolution }}</code>
                                <span class="badge bg-light text-dark border">{{ number_format($res->total) }} thiết bị</span>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted py-3">Chưa có dữ liệu độ phân giải</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- UTM MARKETING CAMPAIGNS & TOP RETENTION -->
    <div class="row g-3">
        <!-- UTM Campaigns -->
        <div class="col-lg-6">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-bullhorn text-warning me-2"></i>Chiến dịch tiếp thị & Nguồn UTM
                    </h3>
                </div>
                <div class="nobifashion_admin_reels_card_body p-0">
                    <div class="table-responsive">
                        <table class="nobifashion_admin_reels_table">
                            <thead>
                                <tr>
                                    <th>Nguồn (Source / Medium)</th>
                                    <th>Chiến dịch (Campaign)</th>
                                    <th>Số phiên</th>
                                    <th>Tb thời gian xem</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($utmCampaigns as $camp)
                                    <tr>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $camp->utm_source }}</span>
                                            <small class="text-muted">/ {{ $camp->utm_medium ?? 'none' }}</small>
                                        </td>
                                        <td>{{ $camp->utm_campaign ?? '—' }}</td>
                                        <td class="fw-bold">{{ number_format($camp->sessions_count) }}</td>
                                        <td>{{ $camp->avg_watch_time }}s</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            Chưa ghi nhận nguồn traffic từ UTM parameters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Retention Reels -->
        <div class="col-lg-6">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-medal text-warning me-2"></i>Top Video Giữ Chân Người Xem Cao Nhất
                    </h3>
                </div>
                <div class="nobifashion_admin_reels_card_body p-0">
                    <div class="table-responsive">
                        <table class="nobifashion_admin_reels_table">
                            <thead>
                                <tr>
                                    <th>Video</th>
                                    <th>Lượt xem log</th>
                                    <th>Tỷ lệ hoàn thành</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($overview['top_retention_reels'] ?? [] as $retReel)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ $retReel->thumbnail_url ?: 'https://placehold.co/40x55' }}" class="rounded" style="width: 32px; height: 42px; object-fit: cover;">
                                                <div class="text-truncate" style="max-width: 220px;">
                                                    <a href="{{ route('admin.reels.show', $retReel->id) }}" class="fw-semibold text-dark text-decoration-none">
                                                        {{ $retReel->title ?: 'Video #' . $retReel->id }}
                                                    </a>
                                                    <div class="small text-muted">ID: {{ $retReel->id }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-bold">{{ number_format($retReel->views_count) }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                                    <div class="progress-bar bg-success" style="width: {{ min(100, $retReel->avg_completion) }}%"></div>
                                                </div>
                                                <span class="small fw-bold text-success">{{ round($retReel->avg_completion, 1) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">
                                            Chưa đủ dữ liệu ghi nhận tỷ lệ giữ chân.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Device Doughnut Chart
    const ctx = document.getElementById('deviceChart');
    if (ctx) {
        const deviceData = @json($overview['devices'] ?? []);
        const labels = deviceData.map(d => d.device_type ? d.device_type.toUpperCase() : 'UNKNOWN');
        const data = deviceData.map(d => d.total);

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels.length ? labels : ['Chưa có'],
                datasets: [{
                    data: data.length ? data : [1],
                    backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#6b7280'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '70%'
            }
        });
    }
});
</script>
@endpush

