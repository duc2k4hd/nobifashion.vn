@extends('admins.layouts.master')

@section('page-title', 'Quản lý bài viết')

@push('head')
    @php
        $slimSelectCssAsset = asset('admins/vendor/slimselect/slimselect.css') . '?v=' . env('APP_VERSION');
        $slimSelectJsAsset = asset('admins/vendor/slimselect/slimselect.min.js') . '?v=' . env('APP_VERSION');
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
            <button type="button" id="btnOpenExportModal" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#exportCsvModal">
                <i class="fas fa-file-download me-1"></i> Xuất CSV
            </button>
            <button type="button" id="btnOpenImportModal" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#importCsvModal">
                <i class="fas fa-file-upload me-1"></i> Nhập CSV/Excel
            </button>
            <label class="btn btn-danger mb-0" style="cursor:pointer;" title="Xóa bài viết từ file .txt chứa ID (1 ID/dòng)">
                <i class="fas fa-trash-alt me-1"></i> Xóa từ TXT
                <input type="file" id="btnDeleteFromTxt" accept=".txt" style="display:none">
            </label>
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
    </form>

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
                                    <input form="bulkDeleteForm" class="form-check-input item-check" type="checkbox" name="ids[]" value="{{ $post->id }}">
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
                                            @if($post->trashed())
                                                <!-- Hành động cho bài viết trong thùng rác -->
                                                <form action="{{ route('admin.posts.restore', $post->id) }}" method="POST" class="dropdown-item p-0">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-link dropdown-item text-success text-start" type="submit">Khôi phục</button>
                                                </form>

                                                <div class="dropdown-divider"></div>

                                                <form
                                                    action="{{ route('admin.posts.bulk-destroy') }}"
                                                    method="POST"
                                                    class="dropdown-item p-0"
                                                    onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn bài viết này? Hành động này không thể hoàn tác!')"
                                                >
                                                    @csrf
                                                    <input type="hidden" name="ids[]" value="{{ $post->id }}">
                                                    <input type="hidden" name="is_trashed" value="1">
                                                    <button class="btn btn-link dropdown-item text-danger text-start" type="submit">Xóa vĩnh viễn</button>
                                                </form>
                                            @else
                                                <!-- Hành động cho bài viết bình thường -->
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
                                            @endif
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

    <!-- Modal Xuất CSV -->
    <div class="modal fade" id="exportCsvModal" tabindex="-1" aria-labelledby="exportCsvModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold" id="exportCsvModalLabel">
                        <i class="fas fa-file-download text-success me-2"></i> Tùy chọn Xuất dữ liệu Bài viết ra CSV
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Phạm vi xuất -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-uppercase small text-muted">1. Phạm vi bài viết cần xuất</label>
                        <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded-3 border">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exportScope" id="scopeAll" value="all" checked>
                                <label class="form-check-label fw-semibold" for="scopeAll">
                                    <i class="fas fa-globe text-primary me-1"></i> Tất cả bài viết trong hệ thống
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exportScope" id="scopeFilter" value="filter">
                                <label class="form-check-label fw-semibold" for="scopeFilter">
                                    <i class="fas fa-filter text-info me-1"></i> Theo bộ lọc tìm kiếm hiện tại trên trang
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Chọn cột xuất -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold text-uppercase small text-muted mb-0">2. Chọn các cột cần xuất</label>
                            <div class="btn-group btn-group-sm">
                                <button type="button" id="btnExportSelectAll" class="btn btn-outline-secondary py-0">Chọn tất cả</button>
                                <button type="button" id="btnExportDeselectAll" class="btn btn-outline-secondary py-0">Bỏ chọn</button>
                                <button type="button" id="btnExportDefaultCols" class="btn btn-outline-primary py-0">Mặc định</button>
                            </div>
                        </div>
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="row g-2" id="exportColumnsGrid">
                                @php
                                    $allExportCols = [
                                        ['key' => 'ID', 'label' => 'ID bài viết', 'default' => true],
                                        ['key' => 'Tiêu đề', 'label' => 'Tiêu đề', 'default' => true],
                                        ['key' => 'Slug', 'label' => 'Slug (Đường dẫn)', 'default' => true],
                                        ['key' => 'Danh mục (Slug)', 'label' => 'Danh mục (Slug)', 'default' => false],
                                        ['key' => 'Nội dung', 'label' => 'Nội dung HTML (Content)', 'default' => true],
                                        ['key' => 'Tóm tắt', 'label' => 'Tóm tắt / Excerpt', 'default' => false],
                                        ['key' => 'Thumbnail URL', 'label' => 'Ảnh Thumbnail', 'default' => false],
                                        ['key' => 'Alt ảnh', 'label' => 'Alt Text ảnh', 'default' => false],
                                        ['key' => 'Trạng thái', 'label' => 'Trạng thái', 'default' => false],
                                        ['key' => 'Nổi bật', 'label' => 'Nổi bật (1/0)', 'default' => false],
                                        ['key' => 'Tags (phẩy)', 'label' => 'Tags (ngăn cách phẩy)', 'default' => false],
                                        ['key' => 'Meta Title', 'label' => 'SEO Meta Title', 'default' => false],
                                        ['key' => 'Meta Description', 'label' => 'SEO Meta Description', 'default' => false],
                                        ['key' => 'Meta Keywords', 'label' => 'SEO Meta Keywords', 'default' => false],
                                        ['key' => 'Meta Canonical', 'label' => 'SEO Canonical URL', 'default' => false],
                                        ['key' => 'Tác giả (Email)', 'label' => 'Tác giả (Email)', 'default' => false],
                                        ['key' => 'Ngày xuất bản', 'label' => 'Ngày xuất bản', 'default' => false],
                                    ];
                                @endphp
                                @foreach ($allExportCols as $col)
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-check">
                                            <input class="form-check-input export-col-check" type="checkbox" value="{{ $col['key'] }}" id="expCol_{{ $loop->index }}" {{ $col['default'] ? 'checked' : '' }} data-default="{{ $col['default'] ? '1' : '0' }}">
                                            <label class="form-check-label small" for="expCol_{{ $loop->index }}">
                                                {{ $col['label'] }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" id="btnConfirmExport" class="btn btn-success rounded-3 px-4 fw-bold">
                        <i class="fas fa-file-download me-1"></i> Tải File CSV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nhập CSV/Excel -->
    <div class="modal fade" id="importCsvModal" tabindex="-1" aria-labelledby="importCsvModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold" id="importCsvModalLabel">
                        <i class="fas fa-file-upload text-info me-2"></i> Nhập bài viết từ CSV/Excel (Batch Ultra Fast)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <!-- Cột trái: Chọn file & chọn cột -->
                        <div class="col-lg-6">
                            <!-- Dropzone chọn file -->
                            <div class="mb-3">
                                <label class="form-label fw-bold text-uppercase small text-muted">1. Chọn file CSV / Excel</label>
                                <div id="modalImportDropZone" class="border-2 border-dashed rounded-3 p-4 text-center transition-all" style="background: #f8fafc; border-color: #cbd5e1; cursor: pointer;">
                                    <input type="file" id="modalImportFileInput" class="d-none" accept=".csv, .xlsx, .xls">
                                    <div id="modalImportDropContent">
                                        <i class="fas fa-cloud-upload-alt text-primary fa-2x mb-2"></i>
                                        <p class="mb-1 fw-semibold small text-dark">Kéo thả file CSV/Excel vào đây hoặc nhấn để chọn</p>
                                        <span class="text-muted" style="font-size: 0.75rem;">Hỗ trợ .csv, .xlsx, .xls</span>
                                    </div>
                                    <div id="modalImportFileInfo" class="d-none text-start p-2 bg-white rounded shadow-sm">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center overflow-hidden">
                                                <i class="fas fa-file-csv text-success fa-2x me-2"></i>
                                                <div class="text-truncate">
                                                    <div id="modalImportFileName" class="fw-bold small text-truncate">file.csv</div>
                                                    <div id="modalImportFileSize" class="text-muted" style="font-size: 0.75rem;">0 KB</div>
                                                </div>
                                            </div>
                                            <button type="button" id="btnChangeImportFile" class="btn btn-sm btn-outline-secondary py-0">Đổi file</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quy tắc khớp bài viết -->
                            <div class="alert alert-info py-2 px-3 mb-3 small border-0" style="background: #f0f9ff; border-left: 4px solid #0284c7 !important;">
                                <div class="fw-bold mb-1 text-primary"><i class="fas fa-shield-alt me-1"></i> Quy tắc khớp bài viết thông minh:</div>
                                <ul class="mb-0 ps-3">
                                    <li><strong>Có ID:</strong> Bắt buộc là cập nhật bài viết theo ID. (Báo lỗi nếu ID không tồn tại trên hệ thống).</li>
                                    <li><strong>Không có ID:</strong> Khớp theo <code>Slug</code> (hoặc tự sinh Slug từ Tiêu đề). Nếu Slug đã có -> Cập nhật; nếu chưa có -> Tạo bài mới.</li>
                                </ul>
                            </div>

                            <!-- Chọn cột nhập vào (hiển thị khi đã load file) -->
                            <div id="importColumnsContainer" class="d-none">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-bold text-uppercase small text-muted mb-0">2. Chọn các cột cần nhập vào</label>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" id="btnImportSelectAllCols" class="btn btn-outline-secondary py-0">Chọn hết</button>
                                        <button type="button" id="btnImportDeselectAllCols" class="btn btn-outline-secondary py-0">Bỏ hết</button>
                                        <button type="button" id="btnImportDefaultCols" class="btn btn-outline-primary py-0">Mặc định</button>
                                    </div>
                                </div>
                                <div class="p-3 bg-light rounded-3 border" style="max-height: 200px; overflow-y: auto;">
                                    <div class="row g-2" id="importColumnsList">
                                        <!-- Render dynamic columns here -->
                                    </div>
                                </div>
                                <div class="text-muted small mt-1 fst-italic">* Những cột bạn bỏ chọn sẽ được giữ nguyên dữ liệu cũ đối với bài viết cập nhật.</div>
                            </div>
                        </div>

                        <!-- Cột phải: Tiến trình & Log -->
                        <div class="col-lg-6">
                            <label class="form-label fw-bold text-uppercase small text-muted">3. Trạng thái & Tiến trình xử lý</label>
                            
                            <!-- Thống kê 3 ô -->
                            <div class="row g-2 mb-3">
                                <div class="col-4">
                                    <div class="p-2 text-center rounded bg-light border">
                                        <div class="text-muted small" style="font-size: 0.75rem;">Tổng số</div>
                                        <div id="modalStatTotal" class="h5 fw-bold mb-0 text-dark">0</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 text-center rounded" style="background: #ecfdf5; border: 1px dashed #10b981;">
                                        <div class="text-success small" style="font-size: 0.75rem;">Thành công</div>
                                        <div id="modalStatSuccess" class="h5 fw-bold mb-0 text-success">0</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 text-center rounded" style="background: #fef2f2; border: 1px dashed #ef4444;">
                                        <div class="text-danger small" style="font-size: 0.75rem;">Lỗi</div>
                                        <div id="modalStatError" class="h5 fw-bold mb-0 text-danger">0</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mb-3 d-none" id="modalProgressArea">
                                <div class="d-flex justify-content-between mb-1 small fw-bold">
                                    <span id="modalProgressText">Đang xử lý: 0/0</span>
                                    <span id="modalPercentText">0%</span>
                                </div>
                                <div class="progress rounded-pill" style="height: 10px;">
                                    <div id="modalProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>

                            <!-- Activity Log -->
                            <div class="rounded-3 bg-dark overflow-hidden">
                                <div class="d-flex justify-content-between align-items-center px-3 py-1 bg-secondary text-white" style="font-size: 0.75rem;">
                                    <span class="fw-bold">NHẬT KÝ TIẾN TRÌNH</span>
                                    <span id="modalCurrentStatus" class="opacity-75">Sẵn sàng...</span>
                                </div>
                                <div id="modalImportLog" class="p-2 font-monospace text-white-50 small overflow-auto" style="height: 200px; font-size: 0.8rem; background: #0f172a;">
                                    <div>> Vui lòng chọn file CSV/Excel để bắt đầu...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" id="btnStartImportBatch" class="btn btn-primary rounded-3 px-4 fw-bold" disabled>
                        <i class="fas fa-rocket me-1"></i> Bắt đầu Nhập Dữ Liệu
                    </button>
                </div>
            </div>
        </div>
    </div>
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

            // ==========================================
            // LOGIC XUẤT CSV VỚI POPUP CHỌN CỘT
            // ==========================================
            const exportModalEl = document.getElementById('exportCsvModal');
            const btnConfirmExport = document.getElementById('btnConfirmExport');
            const btnExportSelectAll = document.getElementById('btnExportSelectAll');
            const btnExportDeselectAll = document.getElementById('btnExportDeselectAll');
            const btnExportDefaultCols = document.getElementById('btnExportDefaultCols');
            const exportColChecks = document.querySelectorAll('.export-col-check');

            if (btnExportSelectAll) {
                btnExportSelectAll.addEventListener('click', () => {
                    exportColChecks.forEach(cb => cb.checked = true);
                });
            }
            if (btnExportDeselectAll) {
                btnExportDeselectAll.addEventListener('click', () => {
                    exportColChecks.forEach(cb => cb.checked = false);
                });
            }
            if (btnExportDefaultCols) {
                btnExportDefaultCols.addEventListener('click', () => {
                    exportColChecks.forEach(cb => cb.checked = cb.dataset.default === '1');
                });
            }

            if (btnConfirmExport) {
                btnConfirmExport.addEventListener('click', async function () {
                    const selectedCols = Array.from(document.querySelectorAll('.export-col-check:checked')).map(cb => cb.value);
                    if (selectedCols.length === 0) {
                        alert('Vui lòng chọn ít nhất một cột để xuất CSV.');
                        return;
                    }

                    const originalContent = this.innerHTML;
                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang xuất dữ liệu...';

                    try {
                        const scope = document.querySelector('input[name="exportScope"]:checked')?.value || 'all';
                        const params = new URLSearchParams();

                        selectedCols.forEach(col => params.append('columns[]', col));

                        if (scope === 'filter') {
                            const filterForm = document.querySelector('.posts-filters form');
                            if (filterForm) {
                                const formData = new FormData(filterForm);
                                for (const [key, val] of formData.entries()) {
                                    if (val && key !== 'page') {
                                        params.append(key, val);
                                    }
                                }
                            }
                        }

                        const response = await fetch("{{ route('admin.posts.export-data') }}?" + params.toString());
                        const result = await response.json();

                        if (!result.success) {
                            alert('Lỗi: ' + (result.message || 'Không thể lấy dữ liệu'));
                            return;
                        }

                        if (!result.data || result.data.length === 0) {
                            alert('Không có dữ liệu bài viết nào phù hợp để xuất.');
                            return;
                        }

                        const worksheet = XLSX.utils.json_to_sheet(result.data, { header: selectedCols });
                        const workbook = XLSX.utils.book_new();
                        XLSX.utils.book_append_sheet(workbook, worksheet, 'Posts');

                        const date = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
                        XLSX.writeFile(workbook, `posts_export_${date}.csv`, { bookType: 'csv' });

                        // Đóng modal
                        const modalInstance = bootstrap.Modal.getInstance(exportModalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }

                        if (window.Toast) {
                            Toast.fire({ icon: 'success', title: `Đã xuất ${result.total || result.data.length} bài viết thành công!` });
                        }
                    } catch (error) {
                        console.error(error);
                        alert('Lỗi hệ thống khi xuất CSV: ' + error.message);
                    } finally {
                        this.disabled = false;
                        this.innerHTML = originalContent;
                    }
                });
            }

            // ==========================================
            // LOGIC NHẬP CSV VỚI POPUP CHỌN CỘT & BATCH
            // ==========================================
            const importDropZone = document.getElementById('modalImportDropZone');
            const importFileInput = document.getElementById('modalImportFileInput');
            const importDropContent = document.getElementById('modalImportDropContent');
            const importFileInfo = document.getElementById('modalImportFileInfo');
            const importFileName = document.getElementById('modalImportFileName');
            const importFileSize = document.getElementById('modalImportFileSize');
            const btnChangeImportFile = document.getElementById('btnChangeImportFile');

            const importColumnsContainer = document.getElementById('importColumnsContainer');
            const importColumnsList = document.getElementById('importColumnsList');
            const btnImportSelectAllCols = document.getElementById('btnImportSelectAllCols');
            const btnImportDeselectAllCols = document.getElementById('btnImportDeselectAllCols');
            const btnImportDefaultCols = document.getElementById('btnImportDefaultCols');

            const modalStatTotal = document.getElementById('modalStatTotal');
            const modalStatSuccess = document.getElementById('modalStatSuccess');
            const modalStatError = document.getElementById('modalStatError');
            const modalProgressArea = document.getElementById('modalProgressArea');
            const modalProgressBar = document.getElementById('modalProgressBar');
            const modalProgressText = document.getElementById('modalProgressText');
            const modalPercentText = document.getElementById('modalPercentText');
            const modalCurrentStatus = document.getElementById('modalCurrentStatus');
            const modalImportLog = document.getElementById('modalImportLog');
            const btnStartImportBatch = document.getElementById('btnStartImportBatch');

            let importJsonData = [];
            const IMPORT_BATCH_SIZE = 50;

            const addImportLog = (msg, type = 'info') => {
                const colorClass = type === 'success' ? 'text-success' : (type === 'error' ? 'text-danger' : (type === 'warn' ? 'text-warning' : 'text-white-50'));
                const div = document.createElement('div');
                div.className = `mb-1 ${colorClass}`;
                div.innerHTML = `<span class="opacity-50">></span> [${new Date().toLocaleTimeString()}] ${msg}`;
                modalImportLog.appendChild(div);
                modalImportLog.scrollTop = modalImportLog.scrollHeight;
            };

            const updateImportProgress = (current, total) => {
                const percent = total > 0 ? Math.round((current / total) * 100) : 0;
                modalProgressBar.style.width = `${percent}%`;
                modalProgressText.innerText = `Đang xử lý: ${current}/${total}`;
                modalPercentText.innerText = `${percent}%`;
            };

            if (importDropZone && importFileInput) {
                importDropZone.addEventListener('click', (e) => {
                    if (e.target !== btnChangeImportFile) {
                        importFileInput.click();
                    }
                });

                if (btnChangeImportFile) {
                    btnChangeImportFile.addEventListener('click', (e) => {
                        e.stopPropagation();
                        importFileInput.click();
                    });
                }

                importDropZone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    importDropZone.style.borderColor = '#3b82f6';
                    importDropZone.style.background = '#eff6ff';
                });

                importDropZone.addEventListener('dragleave', () => {
                    importDropZone.style.borderColor = '#cbd5e1';
                    importDropZone.style.background = '#f8fafc';
                });

                importDropZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    importDropZone.style.borderColor = '#cbd5e1';
                    importDropZone.style.background = '#f8fafc';
                    if (e.dataTransfer.files.length) {
                        handleSelectedImportFile(e.dataTransfer.files[0]);
                    }
                });

                importFileInput.addEventListener('change', (e) => {
                    if (e.target.files.length) {
                        handleSelectedImportFile(e.target.files[0]);
                    }
                });
            }

            function handleSelectedImportFile(file) {
                if (!file.name.match(/\.(csv|xlsx|xls)$/i)) {
                    alert('Định dạng tệp không hợp lệ. Vui lòng chọn tệp có đuôi .csv, .xlsx hoặc .xls');
                    return;
                }

                importFileName.innerText = file.name;
                importFileSize.innerText = `${Math.round(file.size / 1024)} KB`;
                importFileInfo.classList.remove('d-none');
                importDropContent.classList.add('d-none');

                modalCurrentStatus.innerText = 'Đang phân tích tệp...';
                addImportLog(`Đang đọc tệp: ${file.name} (${Math.round(file.size / 1024)} KB)...`, 'info');

                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[firstSheetName];

                        importJsonData = XLSX.utils.sheet_to_json(worksheet);
                        const rawHeaderRows = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
                        const detectedHeaders = (rawHeaderRows && rawHeaderRows.length > 0) ? rawHeaderRows[0] : [];

                        modalStatTotal.innerText = importJsonData.length;
                        modalStatSuccess.innerText = '0';
                        modalStatError.innerText = '0';

                        if (importJsonData.length === 0) {
                            addImportLog('Tệp không có dữ liệu bài viết hợp lệ.', 'error');
                            btnStartImportBatch.disabled = true;
                            importColumnsContainer.classList.add('d-none');
                            return;
                        }

                        // Render danh sách checkbox các cột tìm thấy (Mặc định: ID, Tiêu đề, Slug, Content/Nội dung)
                        importColumnsList.innerHTML = '';
                        detectedHeaders.forEach((colName, idx) => {
                            if (!colName || String(colName).trim() === '') return;
                            const trimmedName = String(colName).trim();
                            const lowerName = trimmedName.toLowerCase();
                            const isDefaultCol = (
                                lowerName === 'id' ||
                                lowerName === 'tiêu đề' || lowerName === 'tieu de' || lowerName === 'title' ||
                                lowerName === 'slug' ||
                                lowerName.startsWith('nội dung') || lowerName.startsWith('noi dung') || lowerName.startsWith('content')
                            );

                            const colDiv = document.createElement('div');
                            colDiv.className = 'col-sm-6';
                            colDiv.innerHTML = `
                                <div class="form-check">
                                    <input class="form-check-input import-col-check" type="checkbox" value="${trimmedName}" id="impCol_${idx}" ${isDefaultCol ? 'checked' : ''} data-default="${isDefaultCol ? '1' : '0'}">
                                    <label class="form-check-label small text-truncate" for="impCol_${idx}" title="${trimmedName}">
                                        ${trimmedName}
                                    </label>
                                </div>
                            `;
                            importColumnsList.appendChild(colDiv);
                        });

                        importColumnsContainer.classList.remove('d-none');
                        btnStartImportBatch.disabled = false;
                        modalCurrentStatus.innerText = `Đã sẵn sàng (${importJsonData.length} bài)`;
                        addImportLog(`Đọc tệp thành công! Tìm thấy ${importJsonData.length} dòng và ${detectedHeaders.length} cột.`, 'success');
                    } catch (err) {
                        console.error(err);
                        addImportLog('Lỗi khi đọc file: ' + err.message, 'error');
                        modalCurrentStatus.innerText = 'Lỗi đọc tệp';
                        btnStartImportBatch.disabled = true;
                    }
                };
                reader.readAsArrayBuffer(file);
            }

            if (btnImportSelectAllCols) {
                btnImportSelectAllCols.addEventListener('click', () => {
                    document.querySelectorAll('.import-col-check').forEach(cb => cb.checked = true);
                });
            }
            if (btnImportDeselectAllCols) {
                btnImportDeselectAllCols.addEventListener('click', () => {
                    document.querySelectorAll('.import-col-check').forEach(cb => cb.checked = false);
                });
            }
            if (btnImportDefaultCols) {
                btnImportDefaultCols.addEventListener('click', () => {
                    document.querySelectorAll('.import-col-check').forEach(cb => {
                        cb.checked = cb.dataset.default === '1';
                    });
                });
            }

            if (btnStartImportBatch) {
                btnStartImportBatch.addEventListener('click', async () => {
                    if (!importJsonData.length) return;

                    const selectedCols = Array.from(document.querySelectorAll('.import-col-check:checked')).map(cb => cb.value);
                    if (selectedCols.length === 0) {
                        alert('Vui lòng chọn ít nhất một cột cần nhập vào hệ thống.');
                        return;
                    }

                    btnStartImportBatch.disabled = true;
                    btnStartImportBatch.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> ĐANG NHẬP DỮ LIỆU...';
                    modalProgressArea.classList.remove('d-none');
                    modalCurrentStatus.innerText = 'Đang xử lý các mẻ batch...';

                    let processedCount = 0;
                    let successCount = 0;
                    let errorCount = 0;

                    addImportLog(`Bắt đầu nhập ${importJsonData.length} bài viết (mỗi mẻ ${IMPORT_BATCH_SIZE} bài)...`, 'info');

                    for (let i = 0; i < importJsonData.length; i += IMPORT_BATCH_SIZE) {
                        const chunk = importJsonData.slice(i, i + IMPORT_BATCH_SIZE);
                        const batchIndex = Math.floor(i / IMPORT_BATCH_SIZE) + 1;
                        const totalBatches = Math.ceil(importJsonData.length / IMPORT_BATCH_SIZE);

                        addImportLog(`Đang gửi mẻ ${batchIndex}/${totalBatches} (${chunk.length} bài)...`, 'info');

                        try {
                            const response = await fetch("{{ route('admin.posts.import-batch') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    items: chunk,
                                    selected_columns: selectedCols
                                })
                            });

                            const result = await response.json();

                            if (result.success) {
                                processedCount += chunk.length;
                                successCount += (result.success_count || 0);

                                if (result.errors && result.errors.length) {
                                    errorCount += result.errors.length;
                                    result.errors.forEach(err => addImportLog(`⚠️ ${err}`, 'error'));
                                }

                                modalStatSuccess.innerText = successCount;
                                modalStatError.innerText = errorCount;
                                updateImportProgress(processedCount, importJsonData.length);
                            } else {
                                throw new Error(result.message || 'Lỗi xử lý từ máy chủ');
                            }
                        } catch (err) {
                            console.error(err);
                            addImportLog(`Lỗi tại mẻ ${batchIndex}: ${err.message}`, 'error');
                            errorCount += chunk.length;
                            modalStatError.innerText = errorCount;
                        }
                    }

                    modalCurrentStatus.innerText = 'Hoàn tất!';
                    addImportLog(`🎉 Quá trình nhập hoàn tất! Thành công: ${successCount}, Lỗi: ${errorCount}`, 'success');
                    btnStartImportBatch.innerHTML = '<i class="fas fa-check me-2"></i> HOÀN TẤT NHẬP DỮ LIỆU';
                    btnStartImportBatch.classList.remove('btn-primary');
                    btnStartImportBatch.classList.add('btn-success');

                    if (window.Toast) {
                        Toast.fire({
                            icon: errorCount === 0 ? 'success' : 'warning',
                            title: `Đã nhập xong: ${successCount} thành công, ${errorCount} lỗi.`
                        });
                    }

                    setTimeout(() => {
                        if (confirm('Quá trình nhập dữ liệu đã hoàn tất! Bạn có muốn làm mới trang để xem danh sách bài viết cập nhật không?')) {
                            window.location.reload();
                        }
                    }, 1000);
                });
            }

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

            // Handle Delete from TXT
            const btnDeleteFromTxt = document.getElementById('btnDeleteFromTxt');
            if (btnDeleteFromTxt) {
                btnDeleteFromTxt.addEventListener('change', function (e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = async function (e) {
                        const content = e.target.result;
                        const ids = content.split('\n')
                            .map(id => id.trim())
                            .filter(id => id !== '' && !isNaN(id))
                            .map(id => parseInt(id, 10));

                        if (ids.length === 0) {
                            alert('Không tìm thấy ID hợp lệ nào trong file.');
                            btnDeleteFromTxt.value = '';
                            return;
                        }

                        if (!confirm(`Tìm thấy ${ids.length} ID. Bạn có chắc chắn muốn xóa cực nhanh TẤT CẢ dữ liệu liên quan (trừ ảnh) của các bài viết này? Hành động này KHÔNG THỂ HOÀN TÁC.`)) {
                            btnDeleteFromTxt.value = '';
                            return;
                        }

                        // Xử lý chia batch
                        const batchSize = 100;
                        let successCount = 0;
                        const parentLabel = btnDeleteFromTxt.parentElement;
                        const originalHTML = parentLabel.innerHTML;
                        parentLabel.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang xóa...';
                        parentLabel.style.pointerEvents = 'none';

                        try {
                            for (let i = 0; i < ids.length; i += batchSize) {
                                const batchIds = ids.slice(i, i + batchSize);
                                const response = await fetch("{{ route('admin.posts.bulk-destroy') }}", {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        ids: batchIds,
                                        force_clean: 1
                                    })
                                });
                                const result = await response.json();
                                if (result.success) {
                                    successCount += result.count || batchIds.length;
                                }
                            }
                            alert(`Đã xóa sạch thành công ${successCount} bài viết!`);
                            window.location.reload();
                        } catch (error) {
                            console.error(error);
                            alert('Có lỗi xảy ra trong quá trình xóa: ' + error.message);
                            window.location.reload();
                        }
                    };
                    reader.readAsText(file);
                });
            }
        });
    </script>
@endpush
