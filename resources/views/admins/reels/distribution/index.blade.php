@extends('admins.layouts.master')

@section('title', 'Trung tâm phân phối video Nobi Reels (Distribution Center)')

@push('styles')
<link rel="stylesheet" href="{{ asset('admins/css/reels_admin.css') }}?v={{ time() }}">
<style>
    .distribution_kpi_grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }
    @media (max-width: 1200px) {
        .distribution_kpi_grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 640px) {
        .distribution_kpi_grid {
            grid-template-columns: 1fr;
        }
    }
    .engine_progress_bar {
        height: 6px;
        border-radius: 999px;
        display: flex;
        overflow: hidden;
        margin-top: 8px;
    }
    .engine_progress_trending {
        width: 60%;
        background-color: #ef4444;
    }
    .engine_progress_newest {
        width: 40%;
        background-color: #3b82f6;
    }
    .sim_video_card {
        border-radius: 12px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 10px;
        text-align: center;
        position: relative;
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease;
    }
    .sim_video_card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    .sim_video_thumb_box {
        position: relative;
        width: 100%;
        padding-top: 133%; /* Tỷ lệ 3:4 gọn gàng */
        border-radius: 8px;
        overflow: hidden;
        background: #0f172a;
        margin-bottom: 8px;
    }
    .sim_video_thumb_box img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .sim_chip_badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12.5px;
        font-weight: 600;
        background: #ffffff;
        border: 1px solid #e2e8f0;
    }
</style>
@endpush

@section('content')
<div class="nobifashion_admin_reels_container">
    <!-- Header -->
    <div class="nobifashion_admin_reels_header mb-3">
        <div>
            <h1 class="nobifashion_admin_reels_title" style="font-size: 20px;">
                <i class="fa-solid fa-satellite-dish text-primary me-2"></i> Trung tâm phân phối & Thuật toán Feed
            </h1>
            <p class="nobifashion_admin_reels_subtitle mb-0">
                Điều phối luồng video theo thuật toán Dual-Engine (60% Trending / 40% Khám phá Mới) và kiểm soát chu kỳ Full Coverage.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('admin.reels.distribution.rebuild-snapshot') }}" method="POST" onsubmit="return confirm('Bạn muốn tái tạo Ranking Snapshot mới ngay bây giờ? Việc này sẽ tính toán lại toàn bộ điểm Trending và Newest.');">
                @csrf
                <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                    <i class="fa-solid fa-arrows-rotate me-1"></i> Tái tạo Ranking Snapshot
                </button>
            </form>
        </div>
    </div>

    <!-- Subnav Tabs -->
    @include('admins.reels.partials.subnav')

    <!-- 4 THẺ KPI METRICS CHUẨN THIẾT KẾ NOBI REELS -->
    <div class="distribution_kpi_grid">
        <!-- Thẻ 1: Snapshot Hiện Hành -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">RANKING SNAPSHOT</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #3b82f6; background: #eff6ff;">
                    <i class="fa-solid fa-database"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value">
                v{{ $currentSnapshot ? $currentSnapshot->version : 1 }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer">
                @if($currentSnapshot)
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 me-1">
                        <i class="fa-solid fa-circle me-1" style="font-size: 7px;"></i>Đang áp dụng
                    </span>
                    <span class="text-muted">{{ $currentSnapshot->created_at ? $currentSnapshot->created_at->diffForHumans() : 'Mới tạo' }}</span>
                @else
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                        Chưa có Snapshot
                    </span>
                @endif
            </div>
        </div>

        <!-- Thẻ 2: Tổng Video Phân Phối -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">TỔNG VIDEO PHÂN PHỐI</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #10b981; background: #ecfdf5;">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value">
                {{ number_format($currentSnapshot ? $currentSnapshot->total_videos : 0) }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer justify-content-between">
                <span><i class="fa-solid fa-fire text-danger me-1"></i>Trending: <strong>{{ count($currentSnapshot->trending_ids ?? []) }}</strong></span>
                <span><i class="fa-solid fa-wand-magic-sparkles text-primary me-1"></i>Newest: <strong>{{ count($currentSnapshot->newest_ids ?? []) }}</strong></span>
            </div>
        </div>

        <!-- Thẻ 3: Tỷ Lệ Phối Trộn Dual-Engine -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">TỶ LỆ PHỐI TRỘN DUAL-ENGINE</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #8b5cf6; background: #f5f3ff;">
                    <i class="fa-solid fa-shuffle"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value" style="font-size: 22px;">
                <span class="text-danger">60%</span> <span class="text-muted fw-normal" style="font-size: 16px;">/</span> <span class="text-primary">40%</span>
            </div>
            <div class="engine_progress_bar">
                <div class="engine_progress_trending" title="60% Trending"></div>
                <div class="engine_progress_newest" title="40% Newest"></div>
            </div>
            <div class="nobifashion_admin_reels_kpi_footer justify-content-between mt-1">
                <span class="text-danger small">60% Trending</span>
                <span class="text-primary small">40% Khám phá</span>
            </div>
        </div>

        <!-- Thẻ 4: Chu Kỳ Full Coverage -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">CHU KỲ FULL COVERAGE</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #f59e0b; background: #fffbeb;">
                    <i class="fa-solid fa-arrows-spin"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value">
                {{ number_format($totalCycles) }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer justify-content-between">
                <span>Đang lướt: <strong class="text-success">{{ $activeCycles }}</strong></span>
                <span>Xem hết 100%: <strong class="text-primary">{{ $completedCycles }}</strong></span>
            </div>
        </div>
    </div>

    <!-- SO SÁNH 2 ENGINE: DUAL-ENGINE CANDIDATE POOL -->
    <div class="row g-3 mb-4">
        <!-- Engine A -->
        <div class="col-lg-6">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header d-flex justify-content-between align-items-center">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-fire text-danger me-2"></i>Engine A: Top Trending & Tương tác
                    </h3>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                        Trọng số 60% Trending
                    </span>
                </div>
                <div class="nobifashion_admin_reels_card_body p-0">
                    <div class="table-responsive">
                        <table class="nobifashion_admin_reels_table">
                            <thead>
                                <tr>
                                    <th style="width: 50%;">Video</th>
                                    <th style="width: 20%; text-align: right;">Lượt xem</th>
                                    <th style="width: 15%; text-align: center;">Thích</th>
                                    <th style="width: 15%; text-align: center;">Bình luận</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topViewed as $reel)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ $reel->thumbnail_url ?: 'https://placehold.co/40x55' }}" class="rounded shadow-sm" style="width: 34px; height: 46px; object-fit: cover;" onerror="this.src='https://placehold.co/40x55'">
                                                <div class="text-truncate" style="max-width: 220px;">
                                                    <a href="{{ route('admin.reels.videos.show', $reel->id) }}" class="fw-semibold text-dark text-decoration-none">
                                                        {{ $reel->title ?: 'Video #' . $reel->id }}
                                                    </a>
                                                    <div class="small text-muted" style="font-size: 11px;">ID: {{ $reel->id }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="text-align: right;">
                                            <span class="badge bg-light text-dark border fw-bold">{{ number_format($reel->view_count) }}</span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="text-danger small fw-semibold"><i class="fa-solid fa-heart me-1"></i>{{ number_format($reel->like_count) }}</span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="text-primary small fw-semibold"><i class="fa-solid fa-comment me-1"></i>{{ number_format($reel->comment_count) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Chưa có video dữ liệu.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Engine B -->
        <div class="col-lg-6">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header d-flex justify-content-between align-items-center">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i>Engine B: Khám Phá Video Mới
                    </h3>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                        Trọng số 40% Khám phá mới
                    </span>
                </div>
                <div class="nobifashion_admin_reels_card_body p-0">
                    <div class="table-responsive">
                        <table class="nobifashion_admin_reels_table">
                            <thead>
                                <tr>
                                    <th style="width: 50%;">Video mới</th>
                                    <th style="width: 25%;">Tác giả</th>
                                    <th style="width: 25%; text-align: right;">Thời gian</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topNewest as $reel)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ $reel->thumbnail_url ?: 'https://placehold.co/40x55' }}" class="rounded shadow-sm" style="width: 34px; height: 46px; object-fit: cover;" onerror="this.src='https://placehold.co/40x55'">
                                                <div class="text-truncate" style="max-width: 220px;">
                                                    <a href="{{ route('admin.reels.videos.show', $reel->id) }}" class="fw-semibold text-dark text-decoration-none">
                                                        {{ $reel->title ?: 'Video #' . $reel->id }}
                                                    </a>
                                                    <div class="small text-muted" style="font-size: 11px;">ID: {{ $reel->id }} • <span class="badge bg-light text-dark border">{{ $reel->duration_seconds ?? 0 }}s</span></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="small text-muted">{{ $reel->creator ? $reel->creator->fullname : 'Nobi Admin' }}</span>
                                        </td>
                                        <td style="text-align: right;">
                                            <span class="small text-muted">{{ $reel->published_at ? $reel->published_at->diffForHumans() : 'Mới đây' }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">Chưa có video mới.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FEED SIMULATOR STUDIO (GIẢ LẬP TRẢI NGHIỆM NGƯỜI XEM) -->
    <div class="nobifashion_admin_reels_card mb-4">
        <div class="nobifashion_admin_reels_card_header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="nobifashion_admin_reels_card_title">
                    <i class="fa-solid fa-vial-virus text-success me-2"></i>Feed Simulator Studio — Giả lập luồng xem thực tế
                </h3>
                <p class="nobifashion_admin_reels_subtitle mb-0" style="font-size: 12.5px;">
                    Mô phỏng chính xác thứ tự video người dùng sẽ lướt thấy trên Reels Player, kiểm tra tỷ lệ phối trộn 3:2 và bảo đảm 100% không trùng lặp video.
                </p>
            </div>
        </div>
        <div class="nobifashion_admin_reels_card_body">
            <!-- Form Giả Lập -->
            <form id="simulatorForm" class="row g-2 align-items-end mb-3 p-3 bg-light rounded-3 border">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Seed định danh mô phỏng (Người dùng)</label>
                    <input type="number" id="simSeed" class="form-control form-control-sm" placeholder="Tự sinh ngẫu nhiên nếu để trống">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Số lượng video muốn kiểm tra</label>
                    <select id="simLimit" class="form-select form-select-sm">
                        <option value="10">10 video đầu tiên</option>
                        <option value="20" selected>20 video đầu tiên (Chuẩn)</option>
                        <option value="30">30 video</option>
                        <option value="50">50 video</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" id="btnRunSim" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary w-100 py-1">
                        <i class="fa-solid fa-play me-1"></i> Bắt đầu giả lập luồng
                    </button>
                </div>
            </form>

            <div id="simResultsPlaceholder" class="text-center py-5 text-muted bg-light rounded-3 border border-dashed">
                <i class="fa-solid fa-film fa-2x mb-2 d-block text-secondary opacity-50"></i>
                Bấm nút <strong>"Bắt đầu giả lập luồng"</strong> để xem sơ đồ thứ tự video hiển thị cho người xem.
            </div>

            <div id="simResultsContainer" class="d-none">
                <!-- Thanh Tóm Tắt Thông Số -->
                <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded-3 border flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="sim_chip_badge">
                            <i class="fa-solid fa-fingerprint text-secondary"></i>
                            Seed: <code id="resSeed" class="text-dark"></code>
                        </span>
                        <span class="sim_chip_badge">
                            <i class="fa-solid fa-list-ol text-primary"></i>
                            Tổng video: <strong id="resTotal" class="text-dark"></strong>
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="sim_chip_badge" style="border-color: #fecaca; background: #fef2f2; color: #b91c1c;">
                            <i class="fa-solid fa-fire text-danger"></i>
                            Trending: <span id="resTrendingCount"></span>
                        </span>
                        <span class="sim_chip_badge" style="border-color: #bfdbfe; background: #eff6ff; color: #1d4ed8;">
                            <i class="fa-solid fa-wand-magic-sparkles text-primary"></i>
                            Newest: <span id="resNewestCount"></span>
                        </span>
                        <span class="sim_chip_badge" style="border-color: #bbf7d0; background: #f0fdf4; color: #15803d;">
                            <i class="fa-solid fa-circle-check text-success"></i>
                            100% Không trùng lặp
                        </span>
                    </div>
                </div>

                <!-- Simulation Visual Track -->
                <div id="simVideoTrack" class="row g-2"></div>
            </div>
        </div>
    </div>

    <!-- BẢNG LỊCH SỬ RANKING SNAPSHOT -->
    <div class="nobifashion_admin_reels_card">
        <div class="nobifashion_admin_reels_card_header">
            <h3 class="nobifashion_admin_reels_card_title">
                <i class="fa-solid fa-clock-rotate-left text-muted me-2"></i>Lịch sử các Ranking Snapshot gần đây
            </h3>
        </div>
        <div class="nobifashion_admin_reels_card_body p-0">
            <div class="table-responsive">
                <table class="nobifashion_admin_reels_table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Phiên bản</th>
                            <th style="width: 18%;">Tổng video đủ chuẩn</th>
                            <th style="width: 18%;">Pool Trending</th>
                            <th style="width: 18%;">Pool Newest</th>
                            <th style="width: 18%;">Thời gian khởi tạo</th>
                            <th style="width: 13%; text-align: center;">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($snapshots as $idx => $snap)
                            <tr>
                                <td>
                                    <strong class="text-dark">v{{ $snap->version }}</strong>
                                    @if($idx === 0)
                                        <span class="badge bg-success ms-1" style="font-size: 10px;">Mới nhất</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-dark fw-normal">{{ number_format($snap->total_videos) }} video</span></td>
                                <td><span class="text-danger fw-semibold">{{ count($snap->trending_ids ?? []) }}</span> video</td>
                                <td><span class="text-primary fw-semibold">{{ count($snap->newest_ids ?? []) }}</span> video</td>
                                <td class="small text-muted">{{ $snap->created_at ? $snap->created_at->format('d/m/Y H:i:s') : '—' }}</td>
                                <td style="text-align: center;">
                                    @if($idx === 0)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Đang phục vụ feed</span>
                                    @else
                                        <span class="badge bg-light text-muted border px-2 py-1">Lưu trữ</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Chưa có lịch sử snapshot.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const simForm = document.getElementById('simulatorForm');
if (simForm) {
    simForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const seed = document.getElementById('simSeed').value;
        const limit = document.getElementById('simLimit').value;
        const btn = document.getElementById('btnRunSim');

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang giả lập...';

        fetch("{{ route('admin.reels.distribution.simulate') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ seed: seed, limit: limit })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-play me-1"></i> Bắt đầu giả lập luồng';

            document.getElementById('simResultsPlaceholder').classList.add('d-none');
            const container = document.getElementById('simResultsContainer');
            container.classList.remove('d-none');

            const list = data.videos || data.items || [];
            document.getElementById('resSeed').textContent = data.seed || data.seed_used || 'N/A';
            document.getElementById('resTotal').textContent = (data.total_in_batch || list.length) + ' video';
            document.getElementById('resTrendingCount').textContent = data.trending_count !== undefined ? data.trending_count : list.filter(v => (v.engine || '').includes('Trending')).length;
            document.getElementById('resNewestCount').textContent = data.newest_count !== undefined ? data.newest_count : list.filter(v => (v.engine || '').includes('Newest')).length;

            const track = document.getElementById('simVideoTrack');
            let trackHtml = '';

            list.forEach((item, index) => {
                const isTrending = (item.engine || '').includes('Trending');
                const badgeBg = isTrending ? '#ef4444' : '#3b82f6';
                const borderClass = isTrending ? 'border-danger-subtle' : 'border-primary-subtle';

                trackHtml += `
                    <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                        <div class="sim_video_card ${borderClass} h-100">
                            <span class="position-absolute top-0 start-0 badge bg-dark rounded-end rounded-bottom" style="font-size: 10px; z-index: 2;">
                                #${index + 1}
                            </span>
                            <div class="sim_video_thumb_box">
                                <img src="${item.thumbnail_url || 'https://placehold.co/60x80'}" alt="${item.title || 'Video'}" loading="lazy" onerror="this.src='https://placehold.co/60x80'">
                            </div>
                            <span class="badge text-white mb-1" style="background-color: ${badgeBg}; font-size: 9px; font-weight: 600;">
                                ${item.engine}
                            </span>
                            <div class="small fw-bold text-truncate" title="${item.title || 'Video'}">${item.title || 'Video #' + item.id}</div>
                            <div class="text-muted" style="font-size: 10px;">ID: ${item.id}</div>
                        </div>
                    </div>
                `;
            });

            track.innerHTML = trackHtml;
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-play me-1"></i> Bắt đầu giả lập luồng';
            alert('Có lỗi khi chạy simulator.');
            console.error(err);
        });
    });
}
</script>
@endpush
