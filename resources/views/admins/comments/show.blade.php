@extends('admins.layouts.master')

@section('title', 'Chi tiết bình luận #' . $comment->id)
@section('page-title', '💬 Chi tiết bình luận #' . $comment->id)

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/comments-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
    <div class="card">
        <h3>Thông tin bình luận</h3>
        <div class="row">
            <div class="col-md-8">
                <form action="{{ route('admin.comments.update', $comment) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Nội dung</label>
                        <textarea name="content" class="form-control" rows="5" required>{{ old('content', $comment->content) }}</textarea>
                        @error('content')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rating (1-5, chỉ áp dụng cho sản phẩm)</label>
                        <input type="number" name="rating" class="form-control" min="1" max="5"
                               value="{{ old('rating', $comment->rating) }}">
                        @error('rating')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-3 form-check">
                        <input type="hidden" name="is_approved" value="0">
                        <input type="checkbox" class="form-check-input" id="is_approved" name="is_approved" value="1"
                               @checked(old('is_approved', $comment->is_approved))>
                        <label class="form-check-label" for="is_approved">Đã duyệt</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    <a href="{{ route('admin.comments.index') }}" class="btn btn-secondary">Quay lại</a>
                </form>
            </div>
            <div class="col-md-4">
                <h5>Thông tin hệ thống</h5>
                <ul class="list-unstyled">
                    <li><strong>ID:</strong> {{ $comment->id }}</li>
                    <li><strong>Người gửi:</strong>
                        @if($comment->account)
                            {{ $comment->account->name }} (ID: {{ $comment->account_id }})
                        @else
                            {{ $comment->guest_name ?? 'Guest' }} ({{ $comment->guest_email ?? 'N/A' }})
                        @endif
                    </li>
                    <li><strong>Loại:</strong> {{ class_basename($comment->commentable_type) }}</li>
                    <li><strong>Bài viết / Sản phẩm:</strong>
                        @if($comment->commentable)
                            {{ $comment->commentable->title ?? $comment->commentable->name ?? '—' }}
                        @else
                            N/A
                        @endif
                    </li>
                    <li><strong>IP:</strong> {{ $comment->ip_address ?? 'N/A' }}</li>
                    <li><strong>User Agent:</strong> <small>{{ $comment->user_agent ?? 'N/A' }}</small></li>
                    <li><strong>Đã báo cáo:</strong> {{ $comment->is_reported ? 'Có' : 'Không' }} ({{ $comment->reports_count }})</li>
                    <li><strong>Ngày tạo:</strong> {{ $comment->created_at->format('d/m/Y H:i') }}</li>
                    <li><strong>Cập nhật:</strong> {{ $comment->updated_at->format('d/m/Y H:i') }}</li>
                </ul>
            </div>
        </div>
    </div>

    @if($comment->replies->isNotEmpty())
        <div class="card">
            <h3>Replies</h3>
            <ul class="list-group">
                @foreach($comment->replies as $reply)
                    <li class="list-group-item">
                        <strong>
                            {{ $reply->account->name ?? $reply->guest_name ?? 'Guest' }}
                        </strong>
                        <span class="text-muted">({{ $reply->created_at->format('d/m/Y H:i') }})</span>
                        <div>{{ $reply->content }}</div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection


