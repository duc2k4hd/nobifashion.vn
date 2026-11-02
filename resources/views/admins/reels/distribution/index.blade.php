@extends('admins.layouts.master')

@section('title', 'Trung tâm phân phối video Nobi Reels (Distribution Center)')

@section('content')
<div class="nobifashion_admin_reels_wrapper container-fluid px-3 py-3">
    <!-- Header -->
    <div class="nobifashion_admin_reels_page_header mb-3">
        <div>
            <h1 class="nobifashion_admin_reels_page_title">
                <i class="fa-solid fa-satellite-dish text-primary me-2"></i>Trung tâm phân phối & Thuật toán Feed
            </h1>
            <div class="nobifashion_admin_reels_breadcrumb">
                <a href="{{ route('admin.reels.dashboard') }}">Reels</a>
                <span>/</span>
                <span>Thuật toán phân phối Dual-Engine</span>
            </div>
        </div>
        <div class="nobifashion_admin_reels_page_actions">
            <form action="{{ route('admin.reels.distribution.rebuild-snapshot') }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn muốn tái tạo Ranking Snapshot mới ngay bây giờ? Việc này sẽ tính toán lại toàn bộ điểm Trending và Newest.');">
                @csrf
                <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                    <i class="fa-solid fa-arrows-rotate me-1"></i> Tái tạo Ranking Snapshot
                </button>
            </form>
        </div>
    </div>

    <!-- Subnav Tabs -->
    @include('admins.reels.partials.subnav')

    <!-- Flash message -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- CURRENT SNAPSHOT & CYCLE METRICS -->
    <div class="row g-3 mb-4">
        <!-- Active Snapshot Card -->
        <div class="col-lg-6">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header d-flex justify-content-between align-items-center">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-database text-primary me-2"></i>Ranking Snapshot Hiện Hành
                    </h3>
                    @if($currentSnapshot)
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                            <i class="fa-solid fa-circle me-1" style="font-size: 8px;"></i>Đang áp dụng
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                            Chưa có Snapshot
                        </span>
                    @endif
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    @if($currentSnapshot)
                        <div class="row text-center mb-3">
                            <div class="col-4 border-end">
                                <span class="text-muted small d-block">Phiên bản</span>
                                <span class="fs-4 fw-bold text-dark">v{{ $currentSnapshot->version }}</span>
                            </div>
                            <div class="col-4 border-end">
                                <span class="text-muted small d-block">Tổng Video Pool</span>
                                <span class="fs-4 fw-bold text-primary">{{ number_format($currentSnapshot->total_videos) }}</span>
                            </div>
                            <div class="col-4">
                                <span class="text-muted small d-block">Khởi tạo</span>
                                <span class="small fw-semibold text-muted d-block mt-1">
                                    {{ $currentSnapshot->created_at ? $currentSnapshot->created_at->diffForHumans() : '—' }}
                                </span>
                            </div>
                        </div>
                        <div class="p-3 bg-light rounded small">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Engine A (Trending Pool):</span>
                                <span class="fw-bold">{{ count($currentSnapshot->trending_ids ?? []) }} video</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Engine B (Newest Pool):</span>
                                <span class="fw-bold">{{ count($currentSnapshot->newest_ids ?? []) }} video</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Tỷ lệ phân phối hòa trộn:</span>
                                <span class="badge bg-info text-dark">60% Trending / 40% Khám phá Mới</span>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fa-solid fa-layer-group fa-2x mb-2 d-block text-secondary"></i>
                            Chưa có snapshot phân phối nào được sinh. Bấm <strong>"Tái tạo Ranking Snapshot"</strong> để khởi tạo.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Full Coverage Feed Cycles -->
        <div class="col-lg-6">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-arrows-spin text-info me-2"></i>Chu kỳ xem Full Coverage (Không trùng lặp)
                    </h3>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    <p class="text-muted small">Thuật toán đảm bảo mỗi người dùng (đã đăng nhập hoặc ẩn danh) xem hết toàn bộ video trong hệ thống theo seed xáo trộn trước khi lặp lại.</p>
                    <div class="row g-2 text-center mt-2">
                        <div class="col-6 col-sm-3">
                            <div class="p-2 border rounded bg-white">
                                <div class="fs-5 fw-bold text-dark">{{ number_format($totalCycles) }}</div>
                                <div class="text-muted" style="font-size: 11px;">Tổng chu kỳ tạo</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="p-2 border rounded bg-white">
                                <div class="fs-5 fw-bold text-success">{{ number_format($activeCycles) }}</div>
                                <div class="text-muted" style="font-size: 11px;">Đang tiếp diễn</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="p-2 border rounded bg-white">
                                <div class="fs-5 fw-bold text-primary">{{ number_format($completedCycles) }}</div>
                                <div class="text-muted" style="font-size: 11px;">Xem trọn vẹn 100%</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="p-2 border rounded bg-white">
                                <div class="fs-5 fw-bold text-warning">{{ $avgDelivered }}</div>
                                <div class="text-muted" style="font-size: 11px;">Tb video / cycle</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DUAL ENGINE CANDIDATE PREVIEW -->
    <div class="row g-3 mb-4">
        <!-- Engine A -->
        <div class="col-lg-6">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header d-flex justify-content-between align-items-center">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-fire text-danger me-2"></i>Engine A: Top Trending & Tương tác
                    </h3>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Trọng số 60%</span>
                </div>
                <div class="nobifashion_admin_reels_card_body p-0">
                    <div class="table-responsive">
                        <table class="nobifashion_admin_reels_table">
                            <thead>
                                <tr>
                                    <th>Video</th>
                                    <th>Lượt xem</th>
                                    <th>Thích</th>
                                    <th>Bình luận</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topViewed as $reel)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ $reel->thumbnail_url ?: 'https://placehold.co/40x55' }}" class="rounded" style="width: 32px; height: 42px; object-fit: cover;">
                                                <div class="text-truncate" style="max-width: 200px;">
                                                    <a href="{{ route('admin.reels.show', $reel->id) }}" class="fw-semibold text-dark text-decoration-none">
                                                        {{ $reel->title ?: 'Video #' . $reel->id }}
                                                    </a>
                                                    <div class="small text-muted">ID: {{ $reel->id }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-bold">{{ number_format($reel->view_count) }}</td>
                                        <td class="text-danger"><i class="fa-solid fa-heart me-1"></i>{{ number_format($reel->like_count) }}</td>
                                        <td class="text-primary"><i class="fa-solid fa-comment me-1"></i>{{ number_format($reel->comment_count) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted">Chưa có video dữ liệu.</td>
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
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Trọng số 40%</span>
                </div>
                <div class="nobifashion_admin_reels_card_body p-0">
                    <div class="table-responsive">
                        <table class="nobifashion_admin_reels_table">
                            <thead>
                                <tr>
                                    <th>Video mới</th>
                                    <th>Tác giả</th>
                                    <th>Thời lượng</th>
                                    <th>Thời gian xuất bản</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topNewest as $reel)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ $reel->thumbnail_url ?: 'https://placehold.co/40x55' }}" class="rounded" style="width: 32px; height: 42px; object-fit: cover;">
                                                <div class="text-truncate" style="max-width: 200px;">
                                                    <a href="{{ route('admin.reels.show', $reel->id) }}" class="fw-semibold text-dark text-decoration-none">
                                                        {{ $reel->title ?: 'Video #' . $reel->id }}
                                                    </a>
                                                    <div class="small text-muted">ID: {{ $reel->id }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="small text-muted">{{ $reel->creator ? $reel->creator->fullname : 'Nobi Admin' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $reel->duration_seconds ?? 0 }}s</span>
                                        </td>
                                        <td class="small text-muted">
                                            {{ $reel->published_at ? $reel->published_at->diffForHumans() : 'Mới đây' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted">Chưa có video mới.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FEED SIMULATOR (MÔ PHỎNG LUỒNG XEM) -->
    <div class="nobifashion_admin_reels_card mb-4">
        <div class="nobifashion_admin_reels_card_header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="nobifashion_admin_reels_card_title">
                    <i class="fa-solid fa-gamepad text-success me-2"></i>Feed Simulator — Giả lập trải nghiệm người xem
                </h3>
                <p class="text-muted small mb-0">Mô phỏng chính xác thứ tự video người dùng sẽ lướt thấy trên Reels Player, kiểm tra tỷ lệ phối trộn 3:2 và xác nhận không có bất kỳ video nào bị lặp.</p>
            </div>
        </div>
        <div class="nobifashion_admin_reels_card_body">
            <form id="simulatorForm" class="row g-2 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="nobifashion_admin_reels_form_label">Seed định danh mô phỏng</label>
                    <input type="number" id="simSeed" class="nobifashion_admin_reels_form_control" placeholder="Tự sinh ngẫu nhiên nếu để trống">
                </div>
                <div class="col-md-3">
                    <label class="nobifashion_admin_reels_form_label">Số video muốn lấy</label>
                    <select id="simLimit" class="nobifashion_admin_reels_form_control">
                        <option value="10">10 video đầu tiên</option>
                        <option value="20" selected>20 video đầu tiên</option>
                        <option value="30">30 video</option>
                        <option value="50">50 video</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" id="btnRunSim" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary w-100">
                        <i class="fa-solid fa-play me-1"></i> Bắt đầu giả lập luồng
                    </button>
                </div>
            </form>

            <div id="simResultsPlaceholder" class="text-center py-4 text-muted bg-light rounded">
                <i class="fa-solid fa-film fa-2x mb-2 d-block text-secondary"></i>
                Bấm <strong>"Bắt đầu giả lập luồng"</strong> để xem sơ đồ thứ tự video hiển thị cho khách truy cập.
            </div>

            <div id="simResultsContainer" class="d-none">
                <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded small">
                    <div>
                        <strong>Seed sử dụng:</strong> <code id="resSeed"></code> | 
                        <strong>Tổng video:</strong> <span id="resTotal" class="badge bg-dark"></span>
                    </div>
                    <div>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle me-2">
                            Trending: <span id="resTrendingCount"></span>
                        </span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                            Newest: <span id="resNewestCount"></span>
                        </span>
                    </div>
                </div>

                <!-- Simulation Visual Track -->
                <div id="simVideoTrack" class="row g-2"></div>
            </div>
        </div>
    </div>

    <!-- SNAPSHOT HISTORY TABLE -->
    <div class="nobifashion_admin_reels_card">
        <div class="nobifashion_admin_reels_card_header">
            <h3 class="nobifashion_admin_reels_card_title">Lịch sử các Ranking Snapshot gần đây</h3>
        </div>
        <div class="nobifashion_admin_reels_card_body p-0">
            <div class="table-responsive">
                <table class="nobifashion_admin_reels_table">
                    <thead>
                        <tr>
                            <th>Phiên bản</th>
                            <th>Tổng video đủ chuẩn</th>
                            <th>Pool Trending</th>
                            <th>Pool Newest</th>
                            <th>Thời gian khởi tạo</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($snapshots as $idx => $snap)
                            <tr>
                                <td>
                                    <strong>v{{ $snap->version }}</strong>
                                    @if($idx === 0)
                                        <span class="badge bg-success ms-1">Mới nhất</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-dark">{{ $snap->total_videos }} video</span></td>
                                <td>{{ count($snap->trending_ids ?? []) }} video</td>
                                <td>{{ count($snap->newest_ids ?? []) }} video</td>
                                <td class="small text-muted">{{ $snap->created_at ? $snap->created_at->format('d/m/Y H:i:s') : '—' }}</td>
                                <td>
                                    @if($idx === 0)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Đang phục vụ feed</span>
                                    @else
                                        <span class="badge bg-light text-muted border">Lịch sử</span>
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

            document.getElementById('resSeed').textContent = data.seed;
            document.getElementById('resTotal').textContent = data.total_in_batch;
            document.getElementById('resTrendingCount').textContent = data.trending_count;
            document.getElementById('resNewestCount').textContent = data.newest_count;

            const track = document.getElementById('simVideoTrack');
            let trackHtml = '';

            (data.videos || []).forEach((item, index) => {
                const isTrending = item.engine.includes('Trending');
                const badgeClass = isTrending ? 'bg-danger' : 'bg-primary';
                const borderClass = isTrending ? 'border-danger-subtle' : 'border-primary-subtle';

                trackHtml += `
                    <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                        <div class="border ${borderClass} rounded p-2 text-center bg-white position-relative shadow-sm">
                            <span class="position-absolute top-0 start-0 badge bg-dark rounded-end rounded-bottom" style="font-size: 10px;">
                                #${index + 1}
                            </span>
                            <span class="badge ${badgeClass} text-white mb-1" style="font-size: 9px;">
                                ${item.engine}
                            </span>
                            <div class="d-flex justify-content-center my-1">
                                <img src="${item.thumbnail_url || 'https://placehold.co/60x80'}" class="rounded" style="width: 50px; height: 70px; object-fit: cover;">
                            </div>
                            <div class="small fw-bold text-truncate" title="${item.title}">${item.title}</div>
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

