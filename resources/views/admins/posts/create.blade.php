@extends('admins.layouts.master')

@section('page-title', 'Viết bài mới')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/posts-icon.png') }}" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">✍️ Viết bài mới</h2>
            <p class="text-muted mb-0">Tạo bài viết chuẩn SEO với trình soạn thảo hiện đại</p>
        </div>
        <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary">← Quay lại danh sách</a>
    </div>

    <form action="{{ route('admin.posts.store') }}" method="POST">
        @csrf
        @include('admins.posts.partials.form', [
            'post' => $post,
            'categories' => $categories,
            'tags' => $tags,
            'seoInsights' => ['score' => 0, 'warnings' => []],
        ])
        <div class="mt-4 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary">Hủy</a>
            <button class="btn btn-primary">Lưu bài viết</button>
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


