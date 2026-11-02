@extends('admins.layouts.master')

@section('title', 'Trung tâm Quản trị Nobi Reels')

@push('styles')
<link rel="stylesheet" href="{{ asset('admins/css/reels_admin.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="nobifashion_admin_reels_container">
    <div class="nobifashion_admin_reels_header">
        <div>
            <h1 class="nobifashion_admin_reels_title">
                <i class="fa-solid fa-film text-primary"></i> Quản trị Nobi Fashion Reels
            </h1>
            <p class="nobifashion_admin_reels_subtitle">
                Trung tâm điều hành nền tảng video ngắn, phân phối thông minh, kiểm duyệt và phân tích hành vi người xem.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.reels.dashboard', ['refresh' => 1]) }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline" title="Làm mới bộ đếm cache">
                <i class="fa-solid fa-arrows-rotate"></i> Làm mới KPI
            </a>
            <a href="{{ route('admin.reels.videos.create') }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                <i class="fa-solid fa-plus"></i> Đăng Video Mới
            </a>
            <a href="/reels" target="_blank" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Xem Frontend
            </a>
        </div>
    </div>

    @include('admins.reels.partials.subnav')

    <!-- KPI Cards Grid -->
    <div class="nobifashion_admin_reels_kpi_grid">
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">Tổng Video</span>
                <div class="nobifashion_admin_reels_kpi_icon"><i class="fa-solid fa-video"></i></div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value">{{ number_format($kpis['total_videos']) }}</div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span class="text-success"><i class="fa-solid fa-circle-check"></i> {{ $kpis['published_videos'] }} xuất bản</span>
                <span class="text-muted ms-2">• {{ $kpis['draft_videos'] }} nháp</span>
            </div>
        </div>

        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">Lượt xem tích lũy</span>
                <div class="nobifashion_admin_reels_kpi_icon"><i class="fa-solid fa-eye"></i></div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value">{{ number_format($kpis['total_views']) }}</div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span>+{{ $kpis['new_videos_7days'] }} video mới trong 7 ngày</span>
            </div>
        </div>

        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">Lượt Thích</span>
                <div class="nobifashion_admin_reels_kpi_icon text-danger"><i class="fa-solid fa-heart"></i></div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value">{{ number_format($kpis['total_likes']) }}</div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span>{{ number_format($kpis['total_saves']) }} lượt lưu bộ sưu tập</span>
            </div>
        </div>

        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">Bình luận</span>
                <div class="nobifashion_admin_reels_kpi_icon text-info"><i class="fa-solid fa-comments"></i></div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value">{{ number_format($kpis['total_comments']) }}</div>
            <div class="nobifashion_admin_reels_kpi_footer">
                @if($kpis['pending_comments'] > 0)
                    <span class="text-warning fw-bold"><i class="fa-solid fa-clock"></i> {{ $kpis['pending_comments'] }} chờ duyệt</span>
                @else
                    <span class="text-success"><i class="fa-solid fa-check"></i> Đã kiểm duyệt 100%</span>
                @endif
            </div>
        </div>

        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">Thời gian xem</span>
                <div class="nobifashion_admin_reels_kpi_icon text-warning"><i class="fa-solid fa-stopwatch"></i></div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value">{{ number_format($kpis['total_watch_time_hours']) }}h</div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span>{{ number_format($kpis['total_watch_time_seconds']) }} giây xem thực tế</span>
            </div>
        </div>

        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">Khách vãng lai</span>
                <div class="nobifashion_admin_reels_kpi_icon"><i class="fa-solid fa-user-secret"></i></div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value">{{ number_format($kpis['unique_anonymous_visitors']) }}</div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span class="{{ $kpis['banned_anonymous_users'] > 0 ? 'text-danger fw-bold' : '' }}">
                    {{ $kpis['banned_anonymous_users'] }} người dùng bị khóa
                </span>
            </div>
        </div>
    </div>

    <!-- Charts & System Health Section -->
    <div class="row g-4 mb-4">
        <!-- Biểu đồ phân tích tương tác theo thời gian -->
        <div class="col-lg-8">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header">
                    <h2 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-chart-area text-primary"></i> Xu hướng Lượt xem & Tương tác
                    </h2>
                    <div class="d-flex align-items-center gap-2">
                        <select id="reels_chart_period" class="nobifashion_admin_reels_select">
                            <option value="today">Hôm nay</option>
                            <option value="yesterday">Hôm qua</option>
                            <option value="7days" selected>7 ngày qua</option>
                            <option value="30days">30 ngày qua</option>
                            <option value="90days">90 ngày qua</option>
                        </select>
                    </div>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    <div style="position: relative; height: 320px; width: 100%;">
                        <canvas id="reels_main_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tình trạng vận hành (System Health) -->
        <div class="col-lg-4">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header">
                    <h2 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-heart-pulse text-danger"></i> Tình trạng Vận hành
                    </h2>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Online</span>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0 py-3 d-flex align-items-start gap-3">
                            <i class="fa-solid fa-database text-success mt-1"></i>
                            <div>
                                <div class="fw-bold" style="font-size:13.5px;">Cơ sở dữ liệu MySQL</div>
                                <div class="text-muted" style="font-size:12px;">{{ $health['database']['message'] }}</div>
                            </div>
                        </div>

                        <div class="list-group-item px-0 py-3 d-flex align-items-start gap-3">
                            <i class="fa-solid fa-shuffle {{ $health['snapshot']['status'] === 'ok' ? 'text-success' : 'text-warning' }} mt-1"></i>
                            <div>
                                <div class="fw-bold" style="font-size:13.5px;">Snapshot Phân phối Feed</div>
                                <div class="text-muted" style="font-size:12px;">{{ $health['snapshot']['message'] }}</div>
                                <div class="mt-1">
                                    <form action="{{ route('admin.reels.distribution.rebuild-snapshot') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary py-0" style="font-size:11.5px;">
                                            <i class="fa-solid fa-rotate"></i> Rebuild Snapshot
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="list-group-item px-0 py-3 d-flex align-items-start gap-3">
                            <i class="fa-solid fa-shield-halved text-success mt-1"></i>
                            <div>
                                <div class="fw-bold" style="font-size:13.5px;">Hệ thống Kiểm duyệt Tự động</div>
                                <div class="text-muted" style="font-size:12px;">{{ $health['moderation']['message'] }}</div>
                            </div>
                        </div>

                        <div class="list-group-item px-0 py-3 d-flex align-items-start gap-3">
                            <i class="fa-solid fa-bolt {{ $health['redis']['status'] === 'ok' ? 'text-success' : 'text-secondary' }} mt-1"></i>
                            <div>
                                <div class="fw-bold" style="font-size:13.5px;">Redis Caching Layer</div>
                                <div class="text-muted" style="font-size:12px;">{{ $health['redis']['message'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities & Top Performing Reels -->
    <div class="row g-4">
        <!-- Top Video Nhiều Lượt Xem Nhất -->
        <div class="col-lg-6">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header">
                    <h2 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-fire text-warning"></i> Video Mới Nhất
                    </h2>
                    <a href="{{ route('admin.reels.videos.index') }}" class="text-decoration-none" style="font-size:13px; font-weight:600;">Xem tất cả</a>
                </div>
                <div class="nobifashion_admin_reels_table_wrapper">
                    <table class="nobifashion_admin_reels_table">
                        <thead>
                            <tr>
                                <th>Video</th>
                                <th>Lượt xem</th>
                                <th>Thả tim</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentReels as $reel)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width:36px; height:50px; background:#000; border-radius:4px; overflow:hidden; flex-shrink:0;">
                                            @if($reel->poster_url || $reel->thumbnail_url)
                                                <img src="{{ $reel->poster_url ?: $reel->thumbnail_url }}" alt="" style="width:100%; height:100%; object-fit:cover;">
                                            @endif
                                        </div>
                                        <div>
                                            <a href="{{ route('admin.reels.videos.edit', $reel->id) }}" class="fw-bold text-decoration-none text-dark" style="font-size:13px;">
                                                {{ Str::limit($reel->title, 32) }}
                                            </a>
                                            <div class="text-muted" style="font-size:11.5px;">{{ $reel->created_at->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ number_format($reel->view_count) }}</td>
                                <td>{{ number_format($reel->like_count) }}</td>
                                <td>
                                    <span class="nobifashion_admin_reels_badge nobifashion_admin_reels_badge_{{ $reel->status }}">
                                        {{ $reel->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Chưa có video nào.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Bình luận mới nhất -->
        <div class="col-lg-6">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header">
                    <h2 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-message text-info"></i> Bình luận Gần Đây
                    </h2>
                    <a href="{{ route('admin.reels.comments.index') }}" class="text-decoration-none" style="font-size:13px; font-weight:600;">Xem tất cả</a>
                </div>
                <div class="nobifashion_admin_reels_table_wrapper">
                    <table class="nobifashion_admin_reels_table">
                        <thead>
                            <tr>
                                <th>Người gửi</th>
                                <th>Nội dung</th>
                                <th>Thời gian</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentComments as $comment)
                            <tr>
                                <td>
                                    <span class="fw-bold" style="font-size:12.5px;">
                                        {{ $comment->author_name ?: ($comment->anonymousUser?->nickname ?? 'Khách') }}
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size:12.5px;">{{ Str::limit($comment->content, 35) }}</span>
                                </td>
                                <td style="font-size:12px;" class="text-muted">
                                    {{ $comment->created_at->diffForHumans() }}
                                </td>
                                <td>
                                    <span class="nobifashion_admin_reels_badge nobifashion_admin_reels_badge_{{ $comment->status }}">
                                        {{ $comment->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Chưa có bình luận nào.</td>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('reels_main_chart');
    if (!ctx) return;

    let mainChart = null;

    function loadChart(period = '7days') {
        fetch(`{{ route('admin.reels.chart-data') }}?period=${encodeURIComponent(period)}`)
            .then(res => res.json())
            .then(res => {
                if (!res.success) return;
                const d = res.data;

                if (mainChart) {
                    mainChart.destroy();
                }

                mainChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: d.labels,
                        datasets: [
                            {
                                label: 'Lượt xem video (Watches)',
                                data: d.watches,
                                borderColor: '#0f172a',
                                backgroundColor: 'rgba(15, 23, 42, 0.05)',
                                fill: true,
                                tension: 0.35,
                                borderWidth: 2,
                                pointRadius: 3
                            },
                            {
                                label: 'Thời gian xem (giây)',
                                data: d.watch_time,
                                borderColor: '#f59e0b',
                                backgroundColor: 'transparent',
                                borderDash: [4, 4],
                                tension: 0.35,
                                borderWidth: 2,
                                pointRadius: 2
                            },
                            {
                                label: 'Bình luận mới',
                                data: d.comments,
                                borderColor: '#3b82f6',
                                backgroundColor: 'transparent',
                                tension: 0.35,
                                borderWidth: 2,
                                pointRadius: 3
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#f1f5f9'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    boxWidth: 12,
                                    font: { size: 12, weight: 600 }
                                }
                            }
                        }
                    }
                });
            })
            .catch(err => console.error('Lỗi khi tải biểu đồ reels:', err));
    }

    const select = document.getElementById('reels_chart_period');
    if (select) {
        select.addEventListener('change', function () {
            loadChart(this.value);
        });
    }

    loadChart('7days');
});
</script>
@endpush

