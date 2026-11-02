@extends('admins.layouts.master')

@section('title', 'Quản lý Người dùng Ẩn danh (Anonymous Users)')

@push('styles')
<link rel="stylesheet" href="{{ asset('admins/css/reels_admin.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="nobifashion_admin_reels_container">
    <div class="nobifashion_admin_reels_header">
        <div>
            <h1 class="nobifashion_admin_reels_title">
                <i class="fa-solid fa-user-secret text-primary"></i> Quản lý Người dùng Ẩn danh
            </h1>
            <p class="nobifashion_admin_reels_subtitle">
                Theo dõi hoạt động, quản lý biệt danh và kiểm soát quyền tương tác/khóa tài khoản vi phạm.
            </p>
        </div>
    </div>

    @include('admins.reels.partials.subnav')

    <!-- Status Tabs -->
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('admin.reels.anonymous-users.index', ['status' => 'all']) }}" class="btn btn-sm {{ request('status', 'all') === 'all' ? 'btn-dark' : 'btn-outline-secondary' }}" style="border-radius:20px;">
            Tất cả ({{ $counts['all'] }})
        </a>
        <a href="{{ route('admin.reels.anonymous-users.index', ['status' => 'active']) }}" class="btn btn-sm {{ request('status') === 'active' ? 'btn-success text-white' : 'btn-outline-secondary' }}" style="border-radius:20px;">
            Đang hoạt động ({{ $counts['active'] }})
        </a>
        <a href="{{ route('admin.reels.anonymous-users.index', ['status' => 'muted']) }}" class="btn btn-sm {{ request('status') === 'muted' ? 'btn-warning text-dark' : 'btn-outline-secondary' }}" style="border-radius:20px;">
            Bị chặn chat (Muted) ({{ $counts['muted'] }})
        </a>
        <a href="{{ route('admin.reels.anonymous-users.index', ['status' => 'banned']) }}" class="btn btn-sm {{ request('status') === 'banned' ? 'btn-danger text-white' : 'btn-outline-secondary' }}" style="border-radius:20px;">
            Đang bị khóa (Banned) ({{ $counts['banned'] }})
        </a>
    </div>

    <div class="nobifashion_admin_reels_card">
        <!-- Filter Bar -->
        <form action="{{ route('admin.reels.anonymous-users.index') }}" method="GET" class="nobifashion_admin_reels_filter_bar">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            <div style="flex: 1; min-width: 240px;">
                <input type="text" name="search" class="nobifashion_admin_reels_input w-100" placeholder="Tìm theo Nickname, Public ID, ID số..." value="{{ request('search') }}">
            </div>

            <select name="sort_by" class="nobifashion_admin_reels_select">
                <option value="id" {{ request('sort_by') === 'id' ? 'selected' : '' }}>Mới nhất (ID)</option>
                <option value="last_seen_at" {{ request('sort_by') === 'last_seen_at' ? 'selected' : '' }}>Hoạt động gần nhất</option>
                <option value="comments_count" {{ request('sort_by') === 'comments_count' ? 'selected' : '' }}>Nhiều bình luận nhất</option>
                <option value="likes_count" {{ request('sort_by') === 'likes_count' ? 'selected' : '' }}>Nhiều lượt thích nhất</option>
            </select>

            <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                <i class="fa-solid fa-filter"></i> Lọc
            </button>
        </form>

        <div class="nobifashion_admin_reels_table_wrapper">
            <table class="nobifashion_admin_reels_table">
                <thead>
                    <tr>
                        <th>Người dùng</th>
                        <th>Mã Public ID</th>
                        <th>Trạng thái</th>
                        <th>Bình luận</th>
                        <th>Lượt thả tim</th>
                        <th>Hoạt động gần nhất</th>
                        <th>Ngày tạo</th>
                        <th style="text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width: 36px; height: 36px; border-radius: 50%; background: {{ $u->avatar_color ?: '#3b82f6' }}; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:bold; font-size:13px; flex-shrink:0;">
                                    {{ mb_substr($u->nickname, 0, 1) }}
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size: 13.5px;">
                                        <a href="{{ route('admin.reels.anonymous-users.show', $u->id) }}" class="text-decoration-none text-dark">
                                            {{ $u->nickname }}
                                        </a>
                                    </div>
                                    <div class="text-muted" style="font-size: 11.5px;">ID: #{{ $u->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <code style="font-size: 12px;">{{ $u->public_id }}</code>
                        </td>
                        <td>
                            @if($u->is_banned || $u->status === 'banned')
                                <span class="nobifashion_admin_reels_badge nobifashion_admin_reels_badge_banned" title="{{ $u->ban_reason }}">
                                    <i class="fa-solid fa-lock"></i> Đang bị khóa
                                </span>
                                @if($u->banned_until)
                                    <div class="text-muted mt-1" style="font-size: 11px;">Đến: {{ $u->banned_until->format('d/m/Y H:i') }}</div>
                                @else
                                    <div class="text-muted mt-1" style="font-size: 11px;">Vĩnh viễn</div>
                                @endif
                            @elseif($u->status === 'muted')
                                <span class="nobifashion_admin_reels_badge nobifashion_admin_reels_badge_hidden">
                                    <i class="fa-solid fa-volume-xmark"></i> Bị chặn chat
                                </span>
                            @else
                                <span class="nobifashion_admin_reels_badge nobifashion_admin_reels_badge_published">
                                    <i class="fa-solid fa-circle-check"></i> Hoạt động
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ number_format($u->comments_count) }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ number_format($u->likes_count) }}</span>
                        </td>
                        <td style="font-size: 12.5px;" class="text-muted">
                            {{ $u->last_seen_at ? $u->last_seen_at->diffForHumans() : 'Chưa ghi nhận' }}
                        </td>
                        <td style="font-size: 12.5px;" class="text-muted">
                            {{ $u->created_at ? $u->created_at->format('d/m/Y') : '' }}
                        </td>
                        <td style="text-align: right;">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.reels.anonymous-users.show', $u->id) }}" class="btn btn-outline-secondary" title="Xem chi tiết">
                                    <i class="fa-solid fa-eye"></i>
                                </a>

                                <form action="{{ route('admin.reels.anonymous-users.toggle-mute', $u->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn {{ $u->status === 'muted' ? 'btn-outline-success' : 'btn-outline-warning' }}" title="{{ $u->status === 'muted' ? 'Mở chat' : 'Chặn chat' }}">
                                        <i class="fa-solid {{ $u->status === 'muted' ? 'fa-volume-high' : 'fa-volume-xmark' }}"></i>
                                    </button>
                                </form>

                                @if($u->is_banned || $u->status === 'banned')
                                    <form action="{{ route('admin.reels.anonymous-users.unban', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có muốn mở khóa cho người dùng này?')">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-success" title="Mở khóa tài khoản">
                                            <i class="fa-solid fa-lock-open"></i>
                                        </button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-outline-danger btn-open-ban-modal" data-id="{{ $u->id }}" data-name="{{ $u->nickname }}" title="Khóa tài khoản">
                                        <i class="fa-solid fa-lock"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-user-secret mb-2" style="font-size: 32px; color: #cbd5e1;"></i>
                            <div>Không tìm thấy người dùng ẩn danh nào.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="text-muted" style="font-size: 13px;">
                Hiển thị từ {{ $users->firstItem() ?? 0 }} đến {{ $users->lastItem() ?? 0 }} trong tổng số {{ $users->total() }} người dùng
            </div>
            <div>
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal Khóa Người Dùng -->
<div class="modal fade" id="banUserModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="banUserForm" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-lock"></i> Khóa người dùng: <span id="banUserNickname"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:13.5px;">Lý do khóa <span class="text-danger">*</span></label>
                    <input type="text" name="reason" class="form-control" placeholder="Spam bình luận, ngôn từ thù địch..." required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:13.5px;">Thời hạn khóa</label>
                    <select name="duration_days" class="form-select">
                        <option value="1">1 Ngày</option>
                        <option value="3">3 Ngày</option>
                        <option value="7" selected>7 Ngày</option>
                        <option value="30">30 Ngày</option>
                        <option value="0">Vĩnh viễn</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-danger fw-bold">Xác nhận Khóa</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('banUserModal');
    const banForm = document.getElementById('banUserForm');
    const nicknameSpan = document.getElementById('banUserNickname');

    if (modalEl && banForm) {
        const modal = new bootstrap.Modal(modalEl);
        document.querySelectorAll('.btn-open-ban-modal').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                nicknameSpan.textContent = name;
                banForm.action = `/admin/reels/anonymous-users/${id}/ban`;
                modal.show();
            });
        });
    }
});
</script>
@endpush

