@extends('admins.layouts.master')

@section('title', 'Quản lý Danh sách Video Reels')

@push('styles')
<link rel="stylesheet" href="{{ asset('admins/css/reels_admin.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="nobifashion_admin_reels_container">
    <div class="nobifashion_admin_reels_header">
        <div>
            <h1 class="nobifashion_admin_reels_title">
                <i class="fa-solid fa-film text-primary"></i> Quản lý Video Reels
            </h1>
            <p class="nobifashion_admin_reels_subtitle">
                Xem danh sách, tìm kiếm, lọc theo thương hiệu và thực hiện thao tác hàng loạt trên toàn bộ video.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.reels.import.index') }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline">
                <i class="fa-solid fa-file-import"></i> Import Video
            </a>
            <a href="{{ route('admin.reels.videos.create') }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                <i class="fa-solid fa-plus"></i> Đăng Video Mới
            </a>
        </div>
    </div>

    @include('admins.reels.partials.subnav')

    <!-- Status Tabs -->
    <div class="d-flex align-items-center gap-2 mb-3 overflow-auto pb-1">
        <a href="{{ route('admin.reels.videos.index', array_merge(request()->except(['status', 'page']), ['status' => 'all'])) }}"
           class="btn btn-sm {{ request('status', 'all') === 'all' ? 'btn-dark' : 'btn-outline-secondary' }}" style="border-radius:20px;">
            Tất cả ({{ $counts['all'] }})
        </a>
        <a href="{{ route('admin.reels.videos.index', array_merge(request()->except(['status', 'page']), ['status' => 'published'])) }}"
           class="btn btn-sm {{ request('status') === 'published' ? 'btn-success text-white' : 'btn-outline-secondary' }}" style="border-radius:20px;">
            Đã xuất bản ({{ $counts['published'] }})
        </a>
        <a href="{{ route('admin.reels.videos.index', array_merge(request()->except(['status', 'page']), ['status' => 'draft'])) }}"
           class="btn btn-sm {{ request('status') === 'draft' ? 'btn-secondary text-white' : 'btn-outline-secondary' }}" style="border-radius:20px;">
            Bản nháp ({{ $counts['draft'] }})
        </a>
        <a href="{{ route('admin.reels.videos.index', array_merge(request()->except(['status', 'page']), ['status' => 'hidden'])) }}"
           class="btn btn-sm {{ request('status') === 'hidden' ? 'btn-warning text-dark' : 'btn-outline-secondary' }}" style="border-radius:20px;">
            Tạm ẩn ({{ $counts['hidden'] }})
        </a>
        <a href="{{ route('admin.reels.videos.index', array_merge(request()->except(['status', 'page']), ['status' => 'trashed'])) }}"
           class="btn btn-sm {{ request('status') === 'trashed' ? 'btn-danger text-white' : 'btn-outline-secondary' }}" style="border-radius:20px;">
            Thùng rác ({{ $counts['trashed'] }})
        </a>
    </div>

    <div class="nobifashion_admin_reels_card">
        <!-- Filter Bar -->
        <form action="{{ route('admin.reels.videos.index') }}" method="GET" class="nobifashion_admin_reels_filter_bar">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            <div style="flex: 1; min-width: 220px;">
                <input type="text" name="search" class="nobifashion_admin_reels_input w-100" placeholder="Tìm theo ID, tiêu đề, tác giả, hashtag..." value="{{ request('search') }}">
            </div>

            <select name="brand_id" class="nobifashion_admin_reels_select">
                <option value="">-- Tất cả Hãng --</option>
                @foreach($brands as $b)
                    <option value="{{ $b->id }}" {{ request('brand_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>

            <select name="category" class="nobifashion_admin_reels_select">
                <option value="all">-- Danh mục --</option>
                <option value="trending" {{ request('category') === 'trending' ? 'selected' : '' }}>Trending</option>
                <option value="new" {{ request('category') === 'new' ? 'selected' : '' }}>New Arrivals</option>
                <option value="style_tips" {{ request('category') === 'style_tips' ? 'selected' : '' }}>Phối đồ / Tips</option>
                <option value="lookbook" {{ request('category') === 'lookbook' ? 'selected' : '' }}>Lookbook</option>
            </select>

            <select name="has_products" class="nobifashion_admin_reels_select">
                <option value="">-- Gắn sản phẩm --</option>
                <option value="yes" {{ request('has_products') === 'yes' ? 'selected' : '' }}>Có sản phẩm</option>
                <option value="no" {{ request('has_products') === 'no' ? 'selected' : '' }}>Không có sản phẩm</option>
            </select>

            <select name="sort_by" class="nobifashion_admin_reels_select">
                <option value="id" {{ request('sort_by') === 'id' ? 'selected' : '' }}>Mới nhất (ID)</option>
                <option value="view_count" {{ request('sort_by') === 'view_count' ? 'selected' : '' }}>Nhiều lượt xem</option>
                <option value="like_count" {{ request('sort_by') === 'like_count' ? 'selected' : '' }}>Nhiều lượt tim</option>
                <option value="comment_count" {{ request('sort_by') === 'comment_count' ? 'selected' : '' }}>Nhiều bình luận</option>
                <option value="published_at" {{ request('sort_by') === 'published_at' ? 'selected' : '' }}>Ngày xuất bản</option>
            </select>

            <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                <i class="fa-solid fa-filter"></i> Lọc
            </button>

            @if(request()->hasAny(['search', 'brand_id', 'category', 'has_products', 'sort_by']))
                <a href="{{ route('admin.reels.videos.index', ['status' => request('status', 'all')]) }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline">
                    Đặt lại
                </a>
            @endif
        </form>

        <!-- Bulk Action Form Container -->
        <form id="bulkActionForm" action="{{ route('admin.reels.videos.bulk-action') }}" method="POST">
            @csrf
            <div class="px-3 py-2 bg-white border-bottom d-flex align-items-center gap-3" style="font-size:13px;">
                <div class="form-check m-0">
                    <input class="form-check-input" type="checkbox" id="selectAllReels">
                    <label class="form-check-label fw-bold" for="selectAllReels">Chọn tất cả</label>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select name="action" class="form-select form-select-sm" style="width: auto;">
                        <option value="">-- Thao tác hàng loạt --</option>
                        <option value="publish">Xuất bản (Publish)</option>
                        <option value="unpublish">Chuyển về Nháp</option>
                        <option value="feature">Đánh dấu Nổi bật</option>
                        <option value="unfeature">Bỏ dấu Nổi bật</option>
                        @if(request('status') === 'trashed')
                            <option value="restore">Khôi phục</option>
                        @else
                            <option value="delete">Chuyển vào thùng rác</option>
                        @endif
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-dark" onclick="return confirm('Bạn có chắc chắn muốn thực hiện thao tác này cho các video đã chọn?')">
                        Áp dụng
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="nobifashion_admin_reels_table_wrapper">
                <table class="nobifashion_admin_reels_table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th style="width: 80px;">Poster</th>
                            <th>Tiêu đề & Tác giả</th>
                            <th>Hãng</th>
                            <th>Tương tác</th>
                            <th>Sản phẩm</th>
                            <th>Trạng thái</th>
                            <th>Ngày đăng</th>
                            <th style="text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reels as $reel)
                        <tr>
                            <td>
                                <input class="form-check-input reel-checkbox" type="checkbox" name="ids[]" value="{{ $reel->id }}">
                            </td>
                            <td>
                                <div style="width: 50px; height: 80px; background: #000; border-radius: 6px; overflow: hidden; position: relative;">
                                    @if($reel->poster_url || $reel->thumbnail_url)
                                        <img src="{{ $reel->poster_url ?: $reel->thumbnail_url }}" alt="" style="width:100%; height:100%; object-fit:cover;">
                                    @else
                                        <div class="d-flex align-items-center justify-content-center h-100 text-muted" style="font-size:10px;">9:16</div>
                                    @endif
                                    @if($reel->is_featured)
                                        <span style="position: absolute; top: 3px; right: 3px; color: #f59e0b; font-size: 11px;" title="Video nổi bật">★</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold" style="font-size: 14px;">
                                    <a href="{{ route('admin.reels.videos.edit', $reel->id) }}" class="text-decoration-none text-dark">
                                        {{ $reel->title }}
                                    </a>
                                </div>
                                <div class="text-muted" style="font-size: 12px;">
                                    <span class="text-dark">{{ $reel->author_name }}</span> ({{ $reel->author_handle }})
                                </div>
                                <div class="text-muted" style="font-size: 11.5px; font-family: monospace;">
                                    ID: {{ $reel->id }} | {{ $reel->slug }}
                                </div>
                            </td>
                            <td>
                                @if($reel->brand)
                                    <span class="badge bg-light text-dark border">
                                        {{ $reel->brand->name }}
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size: 12px;">Mặc định</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-size: 12.5px;">
                                    <div><i class="fa-solid fa-eye text-muted me-1"></i> {{ number_format($reel->view_count) }}</div>
                                    <div><i class="fa-solid fa-heart text-danger me-1"></i> {{ number_format($reel->like_count) }}</div>
                                    <div><i class="fa-solid fa-comment text-info me-1"></i> {{ number_format($reel->comment_count) }}</div>
                                </div>
                            </td>
                            <td>
                                @if($reel->products_count > 0)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="fa-solid fa-bag-shopping"></i> {{ $reel->products_count }} SP
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size: 12px;">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="nobifashion_admin_reels_badge nobifashion_admin_reels_badge_{{ $reel->status }}">
                                    {{ $reel->status }}
                                </span>
                            </td>
                            <td style="font-size: 12.5px;" class="text-muted">
                                {{ $reel->published_at ? $reel->published_at->format('d/m/Y H:i') : 'Chưa xuất bản' }}
                            </td>
                            <td style="text-align: right;">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.reels.videos.show', $reel->id) }}" class="btn btn-outline-secondary" title="Xem chi tiết">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.reels.videos.edit', $reel->id) }}" class="btn btn-outline-primary" title="Chỉnh sửa">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    @if($reel->trashed())
                                        <form action="{{ route('admin.reels.videos.restore', $reel->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline-success" title="Khôi phục">
                                                <i class="fa-solid fa-rotate-left"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.reels.videos.force-delete', $reel->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn XÓA VĨNH VIỄN video này? Thao tác không thể hoàn tác!')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Xóa vĩnh viễn">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.reels.videos.destroy', $reel->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có muốn chuyển video này vào thùng rác?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Xóa">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-film mb-2" style="font-size: 32px; color: #cbd5e1;"></i>
                                <div>Không tìm thấy video Reel nào phù hợp với bộ lọc.</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        <!-- Pagination -->
        <div class="p-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="text-muted" style="font-size: 13px;">
                Hiển thị từ {{ $reels->firstItem() ?? 0 }} đến {{ $reels->lastItem() ?? 0 }} trong tổng số {{ $reels->total() }} video
            </div>
            <div>
                {{ $reels->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAllReels');
    const checkboxes = document.querySelectorAll('.reel-checkbox');

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        });
    }
});
</script>
@endpush

