@extends('admins.layouts.master')

@section('title', 'Quản lý Bình luận Reels')

@push('styles')
<link rel="stylesheet" href="{{ asset('admins/css/reels_admin.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="nobifashion_admin_reels_container">
    <div class="nobifashion_admin_reels_header">
        <div>
            <h1 class="nobifashion_admin_reels_title">
                <i class="fa-solid fa-comments text-primary"></i> Quản lý Bình luận Reels
            </h1>
            <p class="nobifashion_admin_reels_subtitle">
                Kiểm duyệt, phê duyệt, từ chối hoặc ẩn bình luận của khách vãng lai và thành viên đăng nhập.
            </p>
        </div>
    </div>

    @include('admins.reels.partials.subnav')

    <!-- Status Filter Buttons -->
    <div class="d-flex align-items-center gap-2 mb-3 overflow-auto pb-1">
        <a href="{{ route('admin.reels.comments.index', array_merge(request()->except(['status', 'page']), ['status' => 'all'])) }}"
           class="btn btn-sm {{ request('status', 'all') === 'all' ? 'btn-dark' : 'btn-outline-secondary' }}" style="border-radius:20px;">
            Tất cả ({{ $counts['all'] }})
        </a>
        <a href="{{ route('admin.reels.comments.index', array_merge(request()->except(['status', 'page']), ['status' => 'approved'])) }}"
           class="btn btn-sm {{ request('status') === 'approved' ? 'btn-success text-white' : 'btn-outline-secondary' }}" style="border-radius:20px;">
            Đã duyệt ({{ $counts['approved'] }})
        </a>
        <a href="{{ route('admin.reels.comments.index', array_merge(request()->except(['status', 'page']), ['status' => 'pending'])) }}"
           class="btn btn-sm {{ request('status') === 'pending' ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' }}" style="border-radius:20px;">
            Chờ duyệt ({{ $counts['pending'] }})
        </a>
        <a href="{{ route('admin.reels.comments.index', array_merge(request()->except(['status', 'page']), ['status' => 'rejected'])) }}"
           class="btn btn-sm {{ request('status') === 'rejected' ? 'btn-danger text-white' : 'btn-outline-secondary' }}" style="border-radius:20px;">
            Bị từ chối ({{ $counts['rejected'] }})
        </a>
        <a href="{{ route('admin.reels.comments.index', array_merge(request()->except(['status', 'page']), ['status' => 'hidden'])) }}"
           class="btn btn-sm {{ request('status') === 'hidden' ? 'btn-secondary text-white' : 'btn-outline-secondary' }}" style="border-radius:20px;">
            Đã ẩn ({{ $counts['hidden'] }})
        </a>
        <a href="{{ route('admin.reels.comments.index', array_merge(request()->except(['status', 'page']), ['status' => 'trashed'])) }}"
           class="btn btn-sm {{ request('status') === 'trashed' ? 'btn-danger text-white' : 'btn-outline-secondary' }}" style="border-radius:20px;">
            Thùng rác ({{ $counts['trashed'] }})
        </a>
    </div>

    <div class="nobifashion_admin_reels_card">
        <!-- Filter Form -->
        <form action="{{ route('admin.reels.comments.index') }}" method="GET" class="nobifashion_admin_reels_filter_bar">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            <div style="flex: 1; min-width: 220px;">
                <input type="text" name="search" class="nobifashion_admin_reels_input w-100" placeholder="Tìm kiếm nội dung, tên tác giả..." value="{{ request('search') }}">
            </div>

            <input type="number" name="reel_id" class="nobifashion_admin_reels_input" style="width: 140px;" placeholder="Lọc Reel ID..." value="{{ request('reel_id') }}">

            <select name="type" class="nobifashion_admin_reels_select">
                <option value="">-- Loại bình luận --</option>
                <option value="TEXT" {{ request('type') === 'TEXT' ? 'selected' : '' }}>Chữ (Text)</option>
                <option value="STICKER" {{ request('type') === 'STICKER' ? 'selected' : '' }}>Sticker / Nhãn dán</option>
            </select>

            <select name="sort_by" class="nobifashion_admin_reels_select">
                <option value="id" {{ request('sort_by') === 'id' ? 'selected' : '' }}>Mới nhất (ID)</option>
                <option value="likes_count" {{ request('sort_by') === 'likes_count' ? 'selected' : '' }}>Nhiều lượt thích</option>
                <option value="replies_count" {{ request('sort_by') === 'replies_count' ? 'selected' : '' }}>Nhiều phản hồi</option>
            </select>

            <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                <i class="fa-solid fa-filter"></i> Lọc
            </button>

            @if(request()->hasAny(['search', 'reel_id', 'type', 'sort_by']))
                <a href="{{ route('admin.reels.comments.index', ['status' => request('status', 'all')]) }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline">
                    Đặt lại
                </a>
            @endif
        </form>

        <!-- Bulk Actions Form -->
        <form id="bulkCommentForm" action="{{ route('admin.reels.comments.bulk-action') }}" method="POST">
            @csrf
            <div class="px-3 py-2 bg-white border-bottom d-flex align-items-center gap-3" style="font-size:13px;">
                <div class="form-check m-0">
                    <input class="form-check-input" type="checkbox" id="selectAllComments">
                    <label class="form-check-label fw-bold" for="selectAllComments">Chọn tất cả</label>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select name="action" class="form-select form-select-sm" style="width: auto;">
                        <option value="">-- Thao tác hàng loạt --</option>
                        <option value="approve">Duyệt bình luận (Approve)</option>
                        <option value="reject">Từ chối (Reject)</option>
                        <option value="hide">Ẩn (Hide)</option>
                        @if(request('status') === 'trashed')
                            <option value="restore">Khôi phục</option>
                        @else
                            <option value="delete">Chuyển vào thùng rác</option>
                        @endif
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-dark" onclick="return confirm('Bạn có chắc chắn muốn thực hiện thao tác này cho các bình luận đã chọn?')">
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
                            <th>Người gửi</th>
                            <th>Nội dung bình luận</th>
                            <th>Thuộc Video</th>
                            <th>Tương tác</th>
                            <th>Trạng thái</th>
                            <th>Thời gian</th>
                            <th style="text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($comments as $comment)
                        <tr>
                            <td>
                                <input class="form-check-input comment-checkbox" type="checkbox" name="ids[]" value="{{ $comment->id }}">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: {{ $comment->anonymousUser ? $comment->anonymousUser->avatar_color : '#e2e8f0' }}; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:bold; font-size:12px; flex-shrink:0;">
                                        {{ mb_substr($comment->author_name ?: ($comment->anonymousUser?->nickname ?? 'K'), 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="font-size: 13px;">
                                            {{ $comment->author_name ?: ($comment->anonymousUser?->nickname ?? 'Khách ẩn danh') }}
                                        </div>
                                        @if($comment->anonymousUser)
                                            <a href="{{ route('admin.reels.anonymous-users.show', $comment->anonymousUser->id) }}" class="text-muted text-decoration-none" style="font-size: 11px;">
                                                <i class="fa-solid fa-user-secret"></i> {{ $comment->anonymousUser->nickname }}
                                            </a>
                                        @elseif($comment->user)
                                            <span class="badge bg-light text-dark" style="font-size: 10px;">Thành viên</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="max-width: 380px;">
                                    @if($comment->comment_type === 'STICKER' && $comment->sticker)
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <img src="{{ $comment->sticker->asset_url }}" alt="" style="width: 42px; height: 42px; object-fit: contain;">
                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size: 11px;">Sticker: {{ $comment->sticker->name }}</span>
                                        </div>
                                    @endif
                                    <div style="font-size: 13px;">{{ $comment->content }}</div>

                                    @if($comment->parent)
                                        <div class="mt-1 text-muted" style="font-size: 11.5px; border-left: 2px solid #cbd5e1; padding-left: 6px;">
                                            Trả lời <strong>{{ $comment->parent->author_name }}</strong>: "{{ Str::limit($comment->parent->content, 25) }}"
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($comment->reel)
                                    <a href="{{ route('admin.reels.videos.edit', $comment->reel_id) }}" class="text-decoration-none text-dark fw-bold" style="font-size: 12.5px;">
                                        #{{ $comment->reel_id }} - {{ Str::limit($comment->reel->title, 25) }}
                                    </a>
                                @else
                                    <span class="text-muted" style="font-size: 12px;">Reel #{{ $comment->reel_id }} (Đã xóa)</span>
                                @endif
                            </td>
                            <td style="font-size: 12px;">
                                <div><i class="fa-solid fa-heart text-danger me-1"></i> {{ $comment->likes_count }}</div>
                                <div><i class="fa-solid fa-reply text-muted me-1"></i> {{ $comment->replies_count }}</div>
                            </td>
                            <td>
                                <span class="nobifashion_admin_reels_badge nobifashion_admin_reels_badge_{{ $comment->status }}">
                                    {{ $comment->status }}
                                </span>
                            </td>
                            <td style="font-size: 12px;" class="text-muted">
                                {{ $comment->created_at ? $comment->created_at->format('d/m/Y H:i') : '' }}
                            </td>
                            <td style="text-align: right;">
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Trạng thái
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 13px;">
                                        <li>
                                            <form action="{{ route('admin.reels.comments.status', $comment->id) }}" method="POST">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" class="dropdown-item text-success"><i class="fa-solid fa-circle-check me-2"></i> Duyệt (Approve)</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="{{ route('admin.reels.comments.status', $comment->id) }}" method="POST">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-circle-xmark me-2"></i> Từ chối (Reject)</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="{{ route('admin.reels.comments.status', $comment->id) }}" method="POST">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="hidden">
                                                <button type="submit" class="dropdown-item text-secondary"><i class="fa-solid fa-eye-slash me-2"></i> Ẩn bình luận</button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        @if($comment->trashed())
                                            <li>
                                                <form action="{{ route('admin.reels.comments.restore', $comment->id) }}" method="POST">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="dropdown-item text-primary"><i class="fa-solid fa-rotate-left me-2"></i> Khôi phục</button>
                                                </form>
                                            </li>
                                        @else
                                            <li>
                                                <form action="{{ route('admin.reels.comments.destroy', $comment->id) }}" method="POST" onsubmit="return confirm('Bạn có muốn xóa bình luận này?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-trash me-2"></i> Xóa bình luận</button>
                                                </form>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-comments mb-2" style="font-size: 32px; color: #cbd5e1;"></i>
                                <div>Không có bình luận nào phù hợp với bộ lọc.</div>
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
                Hiển thị từ {{ $comments->firstItem() ?? 0 }} đến {{ $comments->lastItem() ?? 0 }} trong tổng số {{ $comments->total() }} bình luận
            </div>
            <div>
                {{ $comments->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAllComments');
    const checkboxes = document.querySelectorAll('.comment-checkbox');

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        });
    }
});
</script>
@endpush

