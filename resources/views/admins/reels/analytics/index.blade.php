@extends('admins.layouts.master')

@section('title', 'Báo cáo phân tích chuyên sâu Reels Analytics')

@push('styles')
<link rel="stylesheet" href="{{ asset('admins/css/reels_admin.css') }}?v={{ time() }}">
<style>
    .analytics_kpi_grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }
    @media (max-width: 1400px) {
        .analytics_kpi_grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    @media (max-width: 768px) {
        .analytics_kpi_grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 480px) {
        .analytics_kpi_grid {
            grid-template-columns: 1fr;
        }
    }
    .analytics_pill_btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        font-size: 13px;
        font-weight: 500;
        border-radius: 8px;
        color: #64748b;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .analytics_pill_btn:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .analytics_pill_btn.active {
        background: #0f172a;
        color: #ffffff;
        border-color: #0f172a;
        font-weight: 600;
    }
    .analytics_metric_box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 16px;
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease;
    }
    .analytics_metric_box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.06);
    }
</style>
@endpush

@section('content')
<div class="nobifashion_admin_reels_container">
    <!-- Header -->
    <div class="nobifashion_admin_reels_header mb-3">
        <div>
            <h1 class="nobifashion_admin_reels_title" style="font-size: 20px;">
                <i class="fa-solid fa-chart-line text-primary me-2"></i> Phân tích & Thống kê Chuyên sâu Reels
            </h1>
            <p class="nobifashion_admin_reels_subtitle mb-0">
                Báo cáo hiệu suất nội dung, hành vi người xem và tỷ lệ chuyển đổi sản phẩm theo thời gian thực.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Bộ lọc thời gian -->
            <div class="d-flex align-items-center gap-1 bg-white p-1 rounded-3 border">
                <a href="{{ route('admin.reels.analytics.index', ['days' => 7]) }}" class="analytics_pill_btn {{ $days == 7 ? 'active' : '' }}">7 ngày</a>
                <a href="{{ route('admin.reels.analytics.index', ['days' => 14]) }}" class="analytics_pill_btn {{ $days == 14 ? 'active' : '' }}">14 ngày</a>
                <a href="{{ route('admin.reels.analytics.index', ['days' => 30]) }}" class="analytics_pill_btn {{ $days == 30 ? 'active' : '' }}">30 ngày</a>
                <a href="{{ route('admin.reels.analytics.index', ['days' => 90]) }}" class="analytics_pill_btn {{ $days == 90 ? 'active' : '' }}">90 ngày</a>
            </div>
            <a href="{{ route('admin.reels.analytics.visitors') }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                <i class="fa-solid fa-users-viewfinder me-1"></i> Nhật ký phiên truy cập
            </a>
        </div>
    </div>

    <!-- Subnav Tabs -->
    @include('admins.reels.partials.subnav')

    <!-- 6 THẺ KPI METRICS TỔNG QUAN -->
    @php
        $sum = $overview['summary'] ?? [];
        $totalHours = isset($sum['total_watch_time']) ? round($sum['total_watch_time'] / 3600, 1) : 0;
    @endphp
    <div class="analytics_kpi_grid">
        <!-- 1. Phiên truy cập -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label" style="font-size: 11px;">PHIÊN TRUY CẬP</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #3b82f6; background: #eff6ff;">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value" style="font-size: 22px;">
                {{ number_format($sum['total_sessions'] ?? 0) }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span class="text-success small fw-semibold"><i class="fa-solid fa-arrow-trend-up me-1"></i>{{ $days }} ngày qua</span>
            </div>
        </div>

        <!-- 2. Lượt xem video -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label" style="font-size: 11px;">LƯỢT XEM VIDEO</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #8b5cf6; background: #f5f3ff;">
                    <i class="fa-solid fa-play"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value" style="font-size: 22px;">
                {{ number_format($sum['total_watches'] ?? 0) }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span class="text-muted small">Watch logs thực tế</span>
            </div>
        </div>

        <!-- 3. Tổng giờ xem -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label" style="font-size: 11px;">TỔNG GIỜ XEM</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #f59e0b; background: #fffbeb;">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value" style="font-size: 22px;">
                {{ $totalHours }}<span style="font-size: 15px; font-weight: 500;">h</span>
            </div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span class="text-muted small">{{ number_format($sum['total_watch_time'] ?? 0) }} giây</span>
            </div>
        </div>

        <!-- 4. Tỷ lệ xem hết -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label" style="font-size: 11px;">TỶ LỆ XEM HẾT</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #10b981; background: #ecfdf5;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value text-success" style="font-size: 22px;">
                {{ $sum['avg_completion_rate'] ?? 0 }}%
            </div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span class="text-muted small">Giữ chân trung bình</span>
            </div>
        </div>

        <!-- 5. Tỷ lệ lặp lại -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label" style="font-size: 11px;">TỶ LỆ XEM LẶP</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #06b6d4; background: #ecfeff;">
                    <i class="fa-solid fa-repeat"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value text-info" style="font-size: 22px;">
                {{ $sum['avg_loops'] ?? 1 }}<span style="font-size: 15px; font-weight: 500;">x</span>
            </div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span class="text-muted small">Lặp trên mỗi video</span>
            </div>
        </div>

        <!-- 6. Click sản phẩm -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label" style="font-size: 11px;">CLICK SẢN PHẨM</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #ef4444; background: #fef2f2;">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value text-danger" style="font-size: 22px;">
                {{ number_format($interactionRates->total_product_clicks ?? 0) }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span class="text-danger small fw-semibold">Tỷ lệ: {{ $productClickRate }}%</span>
            </div>
        </div>
    </div>

    <!-- TỶ LỆ CHUYỂN ĐỔI TƯƠNG TÁC (INTERACTION RATES) -->
    <div class="nobifashion_admin_reels_card mb-4">
        <div class="nobifashion_admin_reels_card_header">
            <h3 class="nobifashion_admin_reels_card_title">
                <i class="fa-solid fa-bullseye text-primary me-2"></i> Tỷ lệ chuyển đổi hành vi tương tác trên lượt xem
            </h3>
        </div>
        <div class="nobifashion_admin_reels_card_body">
            <div class="row g-3">
                <!-- Like Rate -->
                <div class="col-md-3">
                    <div class="analytics_metric_box">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold text-muted"><i class="fa-solid fa-heart text-danger me-1"></i>Thả tim (Like)</span>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ $likeRate }}%</span>
                        </div>
                        <div class="progress" style="height: 6px; background-color: #f1f5f9;">
                            <div class="progress-bar bg-danger" style="width: {{ min(100, $likeRate * 5) }}%"></div>
                        </div>
                        <div class="small text-muted mt-2">{{ number_format($interactionRates->total_likes ?? 0) }} lượt thả tim</div>
                    </div>
                </div>

                <!-- Comment Rate -->
                <div class="col-md-3">
                    <div class="analytics_metric_box">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold text-muted"><i class="fa-solid fa-comment text-primary me-1"></i>Bình luận</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $commentRate }}%</span>
                        </div>
                        <div class="progress" style="height: 6px; background-color: #f1f5f9;">
                            <div class="progress-bar bg-primary" style="width: {{ min(100, $commentRate * 10) }}%"></div>
                        </div>
                        <div class="small text-muted mt-2">{{ number_format($interactionRates->total_comments ?? 0) }} bình luận</div>
                    </div>
                </div>

                <!-- Save Rate -->
                <div class="col-md-3">
                    <div class="analytics_metric_box">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold text-muted"><i class="fa-solid fa-bookmark text-warning me-1"></i>Lưu video</span>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ $saveRate }}%</span>
                        </div>
                        <div class="progress" style="height: 6px; background-color: #f1f5f9;">
                            <div class="progress-bar bg-warning" style="width: {{ min(100, $saveRate * 10) }}%"></div>
                        </div>
                        <div class="small text-muted mt-2">{{ number_format($interactionRates->total_saves ?? 0) }} lượt lưu lại</div>
                    </div>
                </div>

                <!-- Share Rate -->
                <div class="col-md-3">
                    <div class="analytics_metric_box">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold text-muted"><i class="fa-solid fa-share-nodes text-success me-1"></i>Chia sẻ</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $shareRate }}%</span>
                        </div>
                        <div class="progress" style="height: 6px; background-color: #f1f5f9;">
                            <div class="progress-bar bg-success" style="width: {{ min(100, $shareRate * 10) }}%"></div>
                        </div>
                        <div class="small text-muted mt-2">{{ number_format($interactionRates->total_shares ?? 0) }} lượt chia sẻ</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PHÂN BỐ KỸ THUẬT: THIẾT BỊ, HỆ ĐIỀU HÀNH & ĐỘ PHÂN GIẢI -->
    <div class="row g-3 mb-4">
        <!-- 1. Devices (Chart) -->
        <div class="col-lg-4">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-mobile-screen-button text-primary me-2"></i> Thiết bị truy cập
                    </h3>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    <div class="d-flex justify-content-center mb-3">
                        <div style="height: 170px; width: 170px;">
                            <canvas id="deviceChart"></canvas>
                        </div>
                    </div>
                    <ul class="list-group list-group-flush small">
                        @forelse($overview['devices'] ?? [] as $dev)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span><i class="fa-solid fa-circle me-1" style="font-size: 8px; color: #3b82f6;"></i>{{ ucfirst($dev->device_type ?: 'Chưa xác định') }}</span>
                                <span class="badge bg-light text-dark border">{{ number_format($dev->total) }} phiên</span>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted py-3">Chưa có dữ liệu thiết bị</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <!-- 2. Platforms / OS -->
        <div class="col-lg-4">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-brands fa-apple text-dark me-2"></i> Hệ điều hành phổ biến
                    </h3>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    <ul class="list-group list-group-flush small">
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

        <!-- 3. Screen Resolutions -->
        <div class="col-lg-4">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-expand text-info me-2"></i> Độ phân giải màn hình
                    </h3>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    <ul class="list-group list-group-flush small">
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

    <!-- 2 BẢNG CHI TIẾT: CHIẾN DỊCH UTM & TOP VIDEO GIỮ CHÂN -->
    <div class="row g-3">
        <!-- Bảng 1: UTM Campaigns -->
        <div class="col-lg-6">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-bullhorn text-warning me-2"></i> Chiến dịch tiếp thị & Nguồn UTM
                    </h3>
                </div>
                <div class="nobifashion_admin_reels_card_body p-0">
                    <div class="table-responsive">
                        <table class="nobifashion_admin_reels_table">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Nguồn (Source/Medium)</th>
                                    <th style="width: 30%;">Chiến dịch</th>
                                    <th style="width: 15%; text-align: center;">Phiên</th>
                                    <th style="width: 15%; text-align: right;">Tb xem</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($utmCampaigns as $camp)
                                    <tr>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $camp->utm_source }}</span>
                                            <small class="text-muted">/ {{ $camp->utm_medium ?? 'none' }}</small>
                                        </td>
                                        <td><span class="small fw-semibold text-dark">{{ $camp->utm_campaign ?? '—' }}</span></td>
                                        <td style="text-align: center;"><span class="badge bg-light text-dark border">{{ number_format($camp->sessions_count) }}</span></td>
                                        <td style="text-align: right;"><span class="small text-muted">{{ $camp->avg_watch_time }}s</span></td>
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

        <!-- Bảng 2: Top Retention Reels -->
        <div class="col-lg-6">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-medal text-warning me-2"></i> Top Video Giữ Chân Người Xem Cao Nhất
                    </h3>
                </div>
                <div class="nobifashion_admin_reels_card_body p-0">
                    <div class="table-responsive">
                        <table class="nobifashion_admin_reels_table">
                            <thead>
                                <tr>
                                    <th style="width: 50%;">Video</th>
                                    <th style="width: 25%; text-align: center;">Lượt xem log</th>
                                    <th style="width: 25%; text-align: right;">Tỷ lệ hoàn thành</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($overview['top_retention_reels'] ?? [] as $retReel)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ $retReel->thumbnail_url ?: 'https://placehold.co/40x55' }}" class="rounded shadow-sm" style="width: 32px; height: 44px; object-fit: cover;" onerror="this.src='https://placehold.co/40x55'">
                                                <div class="text-truncate" style="max-width: 220px;">
                                                    <a href="{{ route('admin.reels.videos.show', $retReel->id) }}" class="fw-semibold text-dark text-decoration-none">
                                                        {{ $retReel->title ?: 'Video #' . $retReel->id }}
                                                    </a>
                                                    <div class="small text-muted" style="font-size: 11px;">ID: {{ $retReel->id }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge bg-light text-dark border">{{ number_format($retReel->views_count) }}</span>
                                        </td>
                                        <td style="text-align: right;">
                                            <div class="d-flex align-items-center justify-content-end gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px; max-width: 60px; background-color: #f1f5f9;">
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('deviceChart');
    if (ctx && typeof Chart !== 'undefined') {
        const deviceData = @json($overview['devices'] ?? []);
        const labels = deviceData.map(d => d.device_type ? d.device_type.toUpperCase() : 'UNKNOWN');
        const data = deviceData.map(d => d.total);

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels.length ? labels : ['Chưa có dữ liệu'],
                datasets: [{
                    data: data.length ? data : [1],
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#64748b'],
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
                cutout: '72%'
            }
        });
    }
});
</script>
@endpush
