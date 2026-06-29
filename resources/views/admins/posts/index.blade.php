@extends('admins.layouts.master')

@section('page-title', 'Quản lý bài viết')

@push('head')
    @php
        $slimSelectCssPath = public_path('admins/vendor/slimselect/slimselect.css');
        $slimSelectCssVersion = file_exists($slimSelectCssPath) ? filemtime($slimSelectCssPath) : null;
        $slimSelectCssAsset = asset('admins/vendor/slimselect/slimselect.css') . ($slimSelectCssVersion ? '?v=' . $slimSelectCssVersion : '');
        $slimSelectJsPath = public_path('admins/vendor/slimselect/slimselect.min.js');
        $slimSelectJsVersion = file_exists($slimSelectJsPath) ? filemtime($slimSelectJsPath) : null;
        $slimSelectJsAsset = asset('admins/vendor/slimselect/slimselect.min.js') . ($slimSelectJsVersion ? '?v=' . $slimSelectJsVersion : '');
    @endphp
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/posts-icon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ $slimSelectCssAsset }}">
@endpush

@push('styles')
    <style>
        .posts-filters .ss-main,
        .posts-filters .ss-content {
            border-radius: 0.375rem;
        }

        .posts-filters .ss-main {
            min-height: 38px;
            border-color: var(--bs-border-color);
        }

        .posts-filters .ss-main:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }

        .posts-search-hint {
            font-size: 0.8rem;
        }

        .post-thumb-wrap {
            width: 80px;
            min-width: 80px;
        }

        .post-thumb-wrap img {
            width: 80px;
            height: 45px;
            object-fit: cover;
            border-radius: 4px;
            display: block;
            background: #f0f0f0;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Quản lý bài viết</h2>
            <p class="text-muted mb-0">Theo dõi, lọc và xuất bản nội dung như một mini CMS.</p>
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

    <div class="card shadow-sm border-0 mb-4 posts-filters">
        <div class="card-body">
            <form action="{{ route('admin.posts.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Trạng thái</label>
                    <select name="status" class="form-select" data-slim-select data-allow-deselect="true" data-placeholder="Chọn trạng thái">
                        <option value="">Tất cả</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                        <option value="trashed" @selected(($filters['status'] ?? '') === 'trashed')>🗑 Đã xóa mềm</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Danh mục</label>
                    <select name="category_id" class="form-select" data-slim-select data-allow-deselect="true" data-placeholder="Chọn danh mục">
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
                    <select name="tag_id" class="form-select" data-slim-select data-allow-deselect="true" data-placeholder="Chọn tag">
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
                    <select name="author_id" class="form-select" data-slim-select data-allow-deselect="true" data-placeholder="Chọn tác giả">
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
                    <select name="is_featured" class="form-select" data-slim-select data-allow-deselect="true" data-placeholder="Chọn loại">
                        <option value="">Tất cả</option>
                        <option value="1" @selected(($filters['is_featured'] ?? '') === '1')>Chỉ nổi bật</option>
                        <option value="0" @selected(($filters['is_featured'] ?? '') === '0')>Không nổi bật</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Thiếu thumbnail</label>
                    <select name="without_thumbnail" class="form-select" data-slim-select data-allow-deselect="true" data-placeholder="Chọn kiểu">
                        <option value="">Không lọc</option>
                        <option value="1" @selected(($filters['without_thumbnail'] ?? '') === '1')>Chỉ bài chưa có thumbnail</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Hiển thị</label>
                    <select name="limit" class="form-select" data-slim-select data-allow-deselect="false" data-placeholder="Số lượng">
                        <option value="50" @selected(($filters['limit'] ?? 50) == 50)>50 bài / trang</option>
                        <option value="100" @selected(($filters['limit'] ?? 50) == 100)>100 bài / trang</option>
                        <option value="300" @selected(($filters['limit'] ?? 50) == 300)>300 bài / trang</option>
                        <option value="1000" @selected(($filters['limit'] ?? 50) == 1000)>1000 bài / trang</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label text-uppercase text-muted small">Từ khóa</label>
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Tìm theo tiêu đề, ưu tiên đúng cụm từ trước"
                        value="{{ $filters['search'] ?? '' }}"
                    >
                    @if(($searchMeta['mode'] ?? null) === 'exact_phrase')
                        <div class="form-text posts-search-hint text-success">
                            Đang ưu tiên kết quả khớp đúng cụm từ.
                        </div>
                    @elseif(($searchMeta['mode'] ?? null) === 'progressive')
                        <div class="form-text posts-search-hint text-warning">
                            Không có bản ghi khớp đúng cụm từ. Hệ thống đang fallback theo các cụm gần đúng:
                            {{ collect($searchMeta['segments'] ?? [])->take(5)->implode(', ') }}.
                        </div>
                    @endif
                </div>

                <div class="col-md-4 text-end ms-auto">
                    <button type="submit" class="btn btn-dark me-2">Lọc kết quả</button>
                    <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
                </div>
            </form>
        </div>
    </div>


    <form action="{{ route('admin.posts.bulk-destroy') }}" method="POST" id="bulkDeleteForm">
        @csrf
        {{-- Truyền cờ để controller biết đang ở chế độ xóa vĩnh viễn hay xóa mềm --}}
        <input type="hidden" name="is_trashed" value="{{ ($filters['status'] ?? '') === 'trashed' ? '1' : '0' }}">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                @if(($filters['status'] ?? '') === 'trashed')
                    <button type="submit" class="btn btn-sm btn-danger" id="btnBulkDelete" disabled onclick="return confirm('Xóa VĨNH VIỄN các bài đã chọn? Hành động này không thể hoàn tác!');">
                        <i class="fas fa-trash me-1"></i> Xóa vĩnh viễn các mục đã chọn
                    </button>
                @else
                    <button type="submit" class="btn btn-sm btn-danger" id="btnBulkDelete" disabled onclick="return confirm('Bạn có chắc chắn muốn xóa các bài viết đã chọn?');">
                        <i class="fas fa-trash me-1"></i> Xóa các mục đã chọn
                    </button>
                @endif
            </div>
        </div>


    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:40px">
                                <input class="form-check-input" type="checkbox" id="checkAll">
                            </th>
                            <th style="width:40px">ID</th>
                            <th style="width:90px">Ảnh</th>
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
                                <td>
                                    <input class="form-check-input item-check" type="checkbox" name="ids[]" value="{{ $post->id }}">
                                </td>
                                <td>#{{ $post->id }}</td>
                                <td class="post-thumb-wrap">
                                    @php
                                        $thumbSrc = asset('clients/assets/img/posts/'.$post->thumbnail ?? 'https://placehold.co/80x45/e9ecef/adb5bd?text=No+Img');
                                    @endphp
                                    <img
                                        src="{{ $thumbSrc }}"
                                        alt="{{ $post->title }}"
                                        loading="lazy"
                                        onerror="this.onerror=null;this.src='https://placehold.co/80x45/e9ecef/adb5bd?text=No+Img'"
                                        @if(!$thumbSrc) src="https://placehold.co/80x45/e9ecef/adb5bd?text=No+Img" @endif
                                    >
                                </td>
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
                                    @if($post->trashed())
                                        <span class="badge bg-danger">🗑 Đã xóa mềm</span>
                                    @else
                                        <span class="badge bg-{{ $statusBadge[$post->status] ?? 'secondary' }}">
                                            {{ $statusOptions[$post->status] ?? ucfirst($post->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($post->is_featured)
                                        <span class="badge bg-gradient text-uppercase">★</span>
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

                                            <form
                                                action="{{ route('admin.posts.destroy', $post) }}"
                                                method="POST"
                                                class="dropdown-item p-0"
                                                onsubmit="return confirm('Xóa bài viết này?')"
                                            >
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
                                <td colspan="10" class="text-center py-5 text-muted">
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
    </form>
@endsection

@push('scripts')
    <script src="{{ $slimSelectJsAsset }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof SlimSelect !== 'undefined') {
                document.querySelectorAll('select[data-slim-select]').forEach((select) => {
                    new SlimSelect({
                        select,
                        settings: {
                            allowDeselect: select.dataset.allowDeselect === 'true',
                            searchPlaceholder: 'Tìm kiếm...',
                            searchText: 'Không tìm thấy dữ liệu phù hợp',
                            placeholderText: select.dataset.placeholder || '',
                            closeOnSelect: true,
                        }
                    });
                });
            }

            const exportButton = document.getElementById('btnExportCSV');
            if (!exportButton) {
                return;
            }

            exportButton.addEventListener('click', async function () {
                const originalContent = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang xuất CSV...';

                try {
                    const response = await fetch("{{ route('admin.posts.export-data') }}");
                    const result = await response.json();

                    if (!result.success) {
                        alert('Lỗi: ' + (result.message || 'Không thể lấy dữ liệu'));
                        return;
                    }

                    const worksheet = XLSX.utils.json_to_sheet(result.data);
                    const workbook = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(workbook, worksheet, 'Posts');

                    const date = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
                    XLSX.writeFile(workbook, `posts_export_${date}.csv`, { bookType: 'csv' });

                    if (window.Toast) {
                        Toast.fire({ icon: 'success', title: 'Đã xuất CSV thành công' });
                    }
                } catch (error) {
                    console.error(error);
                    alert('Lỗi hệ thống khi xuất CSV: ' + error.message);
                } finally {
                    this.disabled = false;
                    this.innerHTML = originalContent;
                }
            });

            // Handle Checkboxes for Bulk Delete
            const checkAll = document.getElementById('checkAll');
            const itemChecks = document.querySelectorAll('.item-check');
            const btnBulkDelete = document.getElementById('btnBulkDelete');

            if (checkAll && itemChecks.length > 0) {
                checkAll.addEventListener('change', function () {
                    itemChecks.forEach(cb => cb.checked = this.checked);
                    toggleBulkDeleteButton();
                });

                itemChecks.forEach(cb => {
                    cb.addEventListener('change', function () {
                        if (!this.checked) checkAll.checked = false;
                        if (document.querySelectorAll('.item-check:checked').length === itemChecks.length) {
                            checkAll.checked = true;
                        }
                        toggleBulkDeleteButton();
                    });
                });
            }

            function toggleBulkDeleteButton() {
                if (btnBulkDelete) {
                    btnBulkDelete.disabled = document.querySelectorAll('.item-check:checked').length === 0;
                }
            }
        });
    </script>
@endpush
