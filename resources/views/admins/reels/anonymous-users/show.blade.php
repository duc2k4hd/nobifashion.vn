@extends('admins.layouts.master')

@section('title', 'Chi tiết Khách Ẩn danh: ' . $user->nickname)

@push('styles')
<link rel="stylesheet" href="{{ asset('admins/css/reels_admin.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="nobifashion_admin_reels_container">
    <div class="nobifashion_admin_reels_header">
        <div>
            <h1 class="nobifashion_admin_reels_title">
                <i class="fa-solid fa-user-secret text-primary"></i> Khách Ẩn danh: {{ $user->nickname }}
            </h1>
            <p class="nobifashion_admin_reels_subtitle">
                Public ID: <code>{{ $user->public_id }}</code> • Khởi tạo từ: {{ $user->created_at->format('d/m/Y H:i') }}
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.reels.anonymous-users.index') }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline">
                <i class="fa-solid fa-arrow-left"></i> Quay lại
            </a>
            @if($user->is_banned || $user->status === 'banned')
                <form action="{{ route('admin.reels.anonymous-users.unban', $user->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline text-success">
                        <i class="fa-solid fa-lock-open"></i> Mở khóa tài khoản
                    </button>
                </form>
            @endif
        </div>
    </div>

    @include('admins.reels.partials.subnav')

    <div class="row g-4">
        <!-- User Info Card (4 cols) -->
        <div class="col-lg-4">
            <div class="nobifashion_admin_reels_card">
                <div class="nobifashion_admin_reels_card_body text-center">
                    <div style="width: 72px; height: 72px; border-radius: 50%; background: {{ $user->avatar_color ?: '#3b82f6' }}; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 28px; font-weight: bold;">
                        {{ mb_substr($user->nickname, 0, 1) }}
                    </div>
                    <h3 class="fw-bold mb-1" style="font-size: 18px;">{{ $user->nickname }}</h3>
                    <div class="text-muted mb-3" style="font-size: 12px;">Icon: {{ $user->avatar_key }}</div>

                    <div>
                        @if($user->is_banned || $user->status === 'banned')
                            <span class="nobifashion_admin_reels_badge nobifashion_admin_reels_badge_banned px-3 py-1" style="font-size: 12.5px;">
                                <i class="fa-solid fa-lock"></i> Đang bị khóa
                            </span>
                            <div class="alert alert-danger mt-3 p-2 text-start" style="font-size: 12px;">
                                <div><strong>Lý do:</strong> {{ $user->ban_reason }}</div>
                                <div><strong>Thời hạn:</strong> {{ $user->banned_until ? $user->banned_until->format('d/m/Y H:i') : 'Vĩnh viễn' }}</div>
                            </div>
                        @else
                            <span class="nobifashion_admin_reels_badge nobifashion_admin_reels_badge_published px-3 py-1" style="font-size: 12.5px;">
                                <i class="fa-solid fa-circle-check"></i> Đang hoạt động
                            </span>
                        @endif
                    </div>

                    <hr>

                    <div class="text-start" style="font-size: 13px;">
                        <div class="mb-2"><strong>Tổng bình luận:</strong> <span class="badge bg-light text-dark border float-end">{{ $user->comments_count }}</span></div>
                        <div class="mb-2"><strong>Lượt thả tim Reel:</strong> <span class="badge bg-light text-dark border float-end">{{ $user->likes_count }}</span></div>
                        <div class="mb-2"><strong>Tim bình luận:</strong> <span class="badge bg-light text-dark border float-end">{{ $user->comment_likes_count }}</span></div>
                        <div class="mb-2"><strong>Lần online cuối:</strong> <span class="float-end text-muted">{{ $user->last_seen_at ? $user->last_seen_at->format('d/m/Y H:i') : 'N/A' }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comments & Moderation History (8 cols) -->
        <div class="col-lg-8">
            <!-- Comments History -->
            <div class="nobifashion_admin_reels_card mb-4">
                <div class="nobifashion_admin_reels_card_header">
                    <h2 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-comments text-primary"></i> Lịch sử Bình luận ({{ $comments->total() }})
                    </h2>
                </div>
                <div class="nobifashion_admin_reels_table_wrapper">
                    <table class="nobifashion_admin_reels_table">
                        <thead>
                            <tr>
                                <th>Nội dung</th>
                                <th>Video Reel</th>
                                <th>Trạng thái</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($comments as $c)
                            <tr>
                                <td style="max-width: 250px;">
                                    <div style="font-size: 13px;">{{ $c->content }}</div>
                                </td>
                                <td>
                                    @if($c->reel)
                                        <a href="{{ route('admin.reels.videos.edit', $c->reel_id) }}" class="text-decoration-none" style="font-size:12.5px;">
                                            #{{ $c->reel_id }} - {{ Str::limit($c->reel->title, 20) }}
                                        </a>
                                    @else
                                        <span class="text-muted" style="font-size:12px;">#{{ $c->reel_id }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="nobifashion_admin_reels_badge nobifashion_admin_reels_badge_{{ $c->status }}">
                                        {{ $c->status }}
                                    </span>
                                </td>
                                <td class="text-muted" style="font-size: 12px;">
                                    {{ $c->created_at->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Người dùng chưa đăng bình luận nào.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top">
                    {{ $comments->links() }}
                </div>
            </div>

            <!-- Moderation Flags -->
            @if($moderationLogs->count() > 0)
            <div class="nobifashion_admin_reels_card">
                <div class="nobifashion_admin_reels_card_header">
                    <h2 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-triangle-exclamation text-danger"></i> Lịch sử Bị Kiểm duyệt
                    </h2>
                </div>
                <div class="nobifashion_admin_reels_table_wrapper">
                    <table class="nobifashion_admin_reels_table">
                        <thead>
                            <tr>
                                <th>Nội dung bị chặn</th>
                                <th>Lý do vi phạm</th>
                                <th>Hành động</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($moderationLogs as $log)
                            <tr>
                                <td style="font-size: 12.5px;" class="text-danger">
                                    {{ $log->flagged_content ?: 'N/A' }}
                                </td>
                                <td style="font-size: 12.5px;">
                                    <code>{{ $log->reason_code }}</code>
                                </td>
                                <td>
                                    <span class="badge bg-danger-subtle text-danger">{{ $log->moderation_result }}</span>
                                </td>
                                <td style="font-size: 12px;" class="text-muted">
                                    {{ $log->created_at ? $log->created_at->format('d/m/Y H:i') : '' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

