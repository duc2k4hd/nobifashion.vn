@extends('admins.layouts.master')

@section('title', 'Quản lý bình luận')
@section('page-title', '💬 Quản lý bình luận')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/comments-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
    <div class="card">
        <h3>Bộ lọc</h3>
        <form method="GET" action="{{ route('admin.comments.index') }}" class="row g-2">
            <div class="col-md-2">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Từ khoá nội dung">
            </div>
            <div class="col-md-2">
                <select name="commentable_type" class="form-control">
                    <option value="">Loại nội dung</option>
                    @foreach(\App\Models\Comment::typeOptions() as $alias => $class)
                        <option value="{{ $class }}" @selected(request('commentable_type') === $class)>
                            {{ \App\Models\Comment::typeLabel($alias) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-control">
                    <option value="">Trạng thái duyệt</option>
                    <option value="approved" @selected(request('status') === 'approved')>Đã duyệt</option>
                    <option value="pending" @selected(request('status') === 'pending')>Chưa duyệt</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" name="account_id" value="{{ request('account_id') }}" class="form-control" placeholder="Account ID">
            </div>
            <div class="col-md-2">
                <input type="number" name="commentable_id" value="{{ request('commentable_id') }}" class="form-control" placeholder="ID bài viết/sản phẩm">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Lọc</button>
                <a href="{{ route('admin.comments.index') }}" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Danh sách bình luận</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="check-all-comments"></th>
                            <th>ID</th>
                            <th>Người gửi</th>
                            <th>Nội dung</th>
                            <th>Loại</th>
                            <th>Tiêu đề</th>
                            <th>Duyệt</th>
                            <th>Rating</th>
                            <th>Ngày</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($comments as $comment)
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="{{ $comment->id }}" form="comments-bulk-form"></td>
                                <td>#{{ $comment->id }}</td>
                                <td>
                                    @if($comment->account)
                                        {{ $comment->account->name }}<br>
                                        <small>ID: {{ $comment->account_id }}</small>
                                    @else
                                        <span class="text-muted">{{ $comment->guest_name ?? 'Guest' }}</span>
                                    @endif
                                </td>
                                <td>{{ \Illuminate\Support\Str::limit($comment->content, 80) }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ class_basename($comment->commentable_type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($comment->commentable)
                                        {{ $comment->commentable->title ?? $comment->commentable->name ?? '—' }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.comments.toggle-approve', $comment) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $comment->is_approved ? 'btn-success' : 'btn-outline-secondary' }}">
                                            {{ $comment->is_approved ? 'Đã duyệt' : 'Chưa duyệt' }}
                                        </button>
                                    </form>
                                </td>
                                <td>{{ $comment->rating ?? '—' }}</td>
                                <td>{{ $comment->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.comments.show', $comment) }}" class="btn btn-sm btn-primary">Chi tiết</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">Chưa có bình luận nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2">
                <div>
                    <button type="submit"
                            form="comments-bulk-form"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('Xoá các bình luận đã chọn?')">
                        Xoá đã chọn
                    </button>
                </div>
                <div>
                    {{ $comments->links() }}
                </div>
            </div>
        <form method="POST" action="{{ route('admin.comments.bulk-delete') }}" id="comments-bulk-form" class="d-none">
            @csrf
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkAll = document.getElementById('check-all-comments');
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');

            if (checkAll) {
                checkAll.addEventListener('change', function () {
                    checkboxes.forEach(cb => cb.checked = checkAll.checked);
                });
            }
        });
    </script>
@endsection


