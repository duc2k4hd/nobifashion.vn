@extends('admins.layouts.master')

@section('page-title', 'Chỉnh sửa bài viết')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/posts-icon.png') }}" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1">📝 Chỉnh sửa: {{ $post->title }}</h2>
            <p class="text-muted mb-0">Slug: {{ $post->slug }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('client.blog.show', $post) }}" target="_blank" class="btn btn-outline-secondary">
                👁 Xem trên site
            </a>
            <form action="{{ route('admin.posts.publish', $post) }}" method="POST">
                @csrf
                <input type="hidden" name="published_at" value="{{ now()->format('Y-m-d\TH:i') }}">
                <button class="btn btn-success" type="submit">🚀 Xuất bản ngay</button>
            </form>
            <form action="{{ route('admin.posts.archive', $post) }}" method="POST">
                @csrf
                <button class="btn btn-outline-warning" type="submit">🗂 Lưu trữ</button>
            </form>
            <form action="{{ route('admin.posts.duplicate', $post) }}" method="POST">
                @csrf
                <button class="btn btn-outline-primary" type="submit">📄 Nhân bản</button>
            </form>
            <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" onsubmit="return confirm('Xóa bài viết này?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger" type="submit">🗑 Xóa</button>
            </form>
        </div>
    </div>

    <form action="{{ route('admin.posts.update', $post) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admins.posts.partials.form', [
            'post' => $post,
            'categories' => $categories,
            'tags' => $tags,
            'seoInsights' => $seoInsights,
        ])
        <div class="mt-4 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary">Quay lại</a>
            <button class="btn btn-primary">Cập nhật</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo TomSelect cho category_id
    if (document.querySelector('select[name="category_id"]')) {
        new TomSelect('select[name="category_id"]', {
            placeholder: '-- Chọn danh mục --',
            allowEmptyOption: true,
            create: false,
            sortField: {
                field: 'text',
                direction: 'asc'
            }
        });
    }

    // Khởi tạo TomSelect cho status
    if (document.querySelector('select[name="status"]')) {
        new TomSelect('select[name="status"]', {
            placeholder: 'Chọn trạng thái',
            allowEmptyOption: false,
            create: false
        });
    }

    // Khởi tạo TomSelect cho tag_ids (multiple select)
    if (document.querySelector('select[name="tag_ids[]"]')) {
        new TomSelect('select[name="tag_ids[]"]', {
            placeholder: 'Chọn tags từ danh sách...',
            plugins: ['remove_button'],
            maxItems: null,
            create: false,
            sortField: {
                field: 'text',
                direction: 'asc'
            }
        });
    }
});
</script>
@endpush


