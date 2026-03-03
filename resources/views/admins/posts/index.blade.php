@extends('admins.layouts.master')

@section('page-title', 'Quản lý bài viết')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/posts-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">📚 Quản lý bài viết</h2>
            <p class="text-muted mb-0">Theo dõi, lọc và xuất bản nội dung như một mini CMS</p>
        </div>
        <div class="d-flex gap-2">
            <button id="btnExportCSV" class="btn btn-outline-success">
                <i class="fas fa-file-download me-1"></i> Xuất CSV
            </button>
            <a href="{{ route('admin.posts.import-excel') }}" class="btn btn-outline-info">
                <i class="fas fa-file-upload me-1"></i> Nhập CSV/Excel
            </a>
            <a href="{{ route('admin.posts.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Viết bài mới
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="{{ route('admin.posts.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Danh mục</label>
                    <select name="category_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Tag</label>
                    <select name="tag_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}" @selected(($filters['tag_id'] ?? '') == $tag->id)>
                                {{ $tag->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Tác giả</label>
                    <select name="author_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach($authors as $author)
                            <option value="{{ $author->id }}" @selected(($filters['author_id'] ?? '') == $author->id)>
                                {{ $author->name ?? $author->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Ngày từ</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Ngày đến</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Nổi bật</label>
                    <select name="is_featured" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="1" @selected(($filters['is_featured'] ?? '') === '1')>Chỉ nổi bật</option>
                        <option value="0" @selected(($filters['is_featured'] ?? '') === '0')>Không nổi bật</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Thiếu thumbnail</label>
                    <select name="without_thumbnail" class="form-select">
                        <option value="">Không lọc</option>
                        <option value="1" @selected(($filters['without_thumbnail'] ?? '') === '1')>Chỉ bài chưa có thumbnail</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-uppercase text-muted small">Từ khóa</label>
                    <input type="text" name="search" class="form-control" placeholder="Tìm theo tiêu đề / slug"
                           value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-4 text-end ms-auto">
                    <button type="submit" class="btn btn-dark me-2">Lọc kết quả</button>
                    <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                    <tr>
                        <th style="width:40px">ID</th>
                        <th>Tiêu đề</th>
                        <th>Danh mục</th>
                        <th>Trạng thái</th>
                        <th>Nổi bật</th>
                        <th>Lượt xem</th>
                        <th>Tác giả</th>
                        <th>Xuất bản</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php
                        $statusBadge = [
                            'draft' => 'secondary',
                            'pending' => 'warning',
                            'published' => 'success',
                            'archived' => 'dark',
                        ];
                    @endphp
                    @forelse($posts as $post)
                        <tr>
                            <td>#{{ $post->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ renderMeta($post->title) }}</div>
                                <div class="text-muted small">{{ $post->slug }}</div>
                                @php
                                    $tagNames = $post->tag_ids ? $tags->whereIn('id', $post->tag_ids)->pluck('name')->implode(', ') : null;
                                @endphp
                                <div class="small text-muted">Tags: {{ $tagNames ?: '—' }}</div>
                            </td>
                            <td>{{ $post->category?->name ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $statusBadge[$post->status] ?? 'secondary' }}">
                                    {{ $statusOptions[$post->status] ?? ucfirst($post->status) }}
                                </span>
                            </td>
                            <td>
                                @if($post->is_featured)
                                    <span class="badge bg-gradient text-uppercase">⭐</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ number_format($post->views) }}</td>
                            <td>{{ $post->author?->displayName() ?? '—' }}</td>
                            <td>
                                @if($post->published_at)
                                    {{ $post->published_at->translatedFormat('d/m/Y H:i') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="{{ route('admin.posts.edit', $post) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"></button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="{{ route('client.blog.show', $post) }}" target="_blank">Xem ngoài site</a>
                                        <form action="{{ route('admin.posts.duplicate', $post) }}" method="POST" class="dropdown-item p-0">
                                            @csrf
                                            <button class="btn btn-link dropdown-item text-start" type="submit">Nhân bản</button>
                                        </form>
                                        @if(!$post->is_featured)
                                            <form action="{{ route('admin.posts.feature', $post) }}" method="POST" class="dropdown-item p-0">
                                                @csrf
                                                <button class="btn btn-link dropdown-item text-start" type="submit">Đánh dấu nổi bật</button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.posts.unfeature', $post) }}" method="POST" class="dropdown-item p-0">
                                                @csrf
                                                <button class="btn btn-link dropdown-item text-start" type="submit">Bỏ nổi bật</button>
                                            </form>
                                        @endif
                                        <div class="dropdown-divider"></div>
                                        <form action="{{ route('admin.posts.destroy', $post) }}" method="POST"
                                              onsubmit="return confirm('Xóa bài viết này?')" class="dropdown-item p-0">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-link dropdown-item text-danger text-start" type="submit">Xóa</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                Chưa có bài viết nào khớp bộ lọc.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0">
            {{ $posts->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
 
 @push('scripts')
 <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
 <script>
 document.getElementById('btnExportCSV').addEventListener('click', async function() {
     const btn = this;
     const originalContent = btn.innerHTML;
     btn.disabled = true;
     btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang xuất CSV...';
 
     try {
         const response = await fetch("{{ route('admin.posts.export-data') }}");
         const result = await response.json();
 
         if (result.success) {
             const worksheet = XLSX.utils.json_to_sheet(result.data);
             const workbook = XLSX.utils.book_new();
             XLSX.utils.book_append_sheet(workbook, worksheet, "Posts");
 
             const date = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
             XLSX.writeFile(workbook, `posts_export_${date}.csv`, { bookType: 'csv' });
             
             if (window.Toast) {
                 Toast.fire({ icon: 'success', title: 'Đã xuất CSV thành công' });
             }
         } else {
             alert('Lỗi: ' + (result.message || 'Không thể lấy dữ liệu'));
         }
     } catch (err) {
         console.error(err);
         alert('Lỗi hệ thống khi xuất CSV: ' + err.message);
     } finally {
         btn.disabled = false;
         btn.innerHTML = originalContent;
     }
 });
 </script>
 @endpush

