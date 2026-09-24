@extends('admins.layouts.master')

@section('title', 'Danh mục bài viết')
@section('page-title', '📁 Quản lý Danh mục Bài viết')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/posts-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .cat-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .cat-table th, .cat-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #eef2f7;
            font-size: 13.5px;
            vertical-align: middle;
        }
        .cat-table th {
            background: #f8fafc;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            font-weight: 600;
            font-size: 12px;
        }
        .cat-table tr:hover td {
            background: #f8fafc;
        }
    </style>
@endpush

@section('content')
<div class="row g-4">
    <!-- CỘT TRÁI: FORM THÊM / SỬA DANH MỤC -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0 text-primary" id="form-title">➕ Thêm danh mục mới</h5>
            </div>
            <div class="card-body p-4">
                <form id="categoryForm" action="{{ route('admin.post-categories.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">
                    <input type="hidden" id="categoryId" value="">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên danh mục <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="catName" class="form-control" placeholder="VD: Xu hướng thời trang" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Đường dẫn (Slug)</label>
                        <input type="text" name="slug" id="catSlug" class="form-control" placeholder="Tự động tạo nếu để trống">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Danh mục cha</label>
                        <select name="parent_id" id="catParentId" class="form-select">
                            <option value="">-- Không có (Danh mục gốc) --</option>
                            @foreach($parentCategories as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả ngắn</label>
                        <textarea name="description" id="catDescription" class="form-control" rows="3" placeholder="Mô tả nội dung chủ đề..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Thứ tự sắp xếp</label>
                            <input type="number" name="sort_order" id="catSortOrder" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Trạng thái</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="catIsActive" value="1" checked>
                                <label class="form-check-label" for="catIsActive">Hiển thị</label>
                            </div>
                        </div>
                    </div>

                    <!-- SEO Section Collapse -->
                    <div class="accordion mb-3" id="seoAccordion">
                        <div class="accordion-item border rounded">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-2 text-muted fw-semibold small" type="button" data-bs-toggle="collapse" data-bs-target="#seoCollapse">
                                    ⚙️ Cấu hình SEO (Tùy chọn)
                                </button>
                            </h2>
                            <div id="seoCollapse" class="accordion-collapse collapse p-3">
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Meta Title</label>
                                    <input type="text" name="meta_title" id="catMetaTitle" class="form-control form-control-sm">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Meta Description</label>
                                    <textarea name="meta_description" id="catMetaDesc" class="form-control form-control-sm" rows="2"></textarea>
                                </div>
                                <div>
                                    <label class="form-label small fw-semibold">Meta Keywords</label>
                                    <input type="text" name="meta_keywords" id="catMetaKeywords" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1" id="btnSubmit">Lưu danh mục</button>
                        <button type="button" class="btn btn-outline-secondary d-none" id="btnCancel" onclick="resetForm()">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- CỘT PHẢI: BẢNG DANH SÁCH DANH MỤC -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">📋 Danh sách Danh mục Bài viết</h5>
                <form action="{{ route('admin.post-categories.index') }}" method="GET" class="d-flex gap-2" style="max-width: 320px;">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm theo tên hoặc slug..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Tìm</button>
                    @if(request('search'))
                        <a href="{{ route('admin.post-categories.index') }}" class="btn btn-sm btn-outline-secondary">Xóa</a>
                    @endif
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="cat-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Tên danh mục</th>
                                <th>Đường dẫn (Slug)</th>
                                <th class="text-center" style="width: 100px;">Bài viết</th>
                                <th class="text-center" style="width: 90px;">Thứ tự</th>
                                <th class="text-center" style="width: 90px;">Trạng thái</th>
                                <th class="text-end" style="width: 130px;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $cat)
                                <tr>
                                    <td class="text-muted small">#{{ $cat->id }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $cat->name }}</div>
                                        @if($cat->description)
                                            <div class="text-muted small text-truncate" style="max-width: 200px;">{{ $cat->description }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark font-monospace">{{ $cat->slug }}</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.posts.index', ['category_id' => $cat->id]) }}" class="badge bg-primary text-white text-decoration-none rounded-pill">
                                            {{ number_format($cat->posts_count) }} bài
                                        </a>
                                    </td>
                                    <td class="text-center text-muted font-monospace">{{ $cat->sort_order }}</td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                   {{ $cat->is_active ? 'checked' : '' }}
                                                   onchange="toggleStatus({{ $cat->id }}, this)">
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('client.blog.category', $cat) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Xem ngoài web">
                                                ↗
                                            </a>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editCategory({{ $cat->id }})" title="Chỉnh sửa">
                                                ✏️
                                            </button>
                                            <form action="{{ route('admin.post-categories.destroy', $cat) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này? Các bài viết thuộc danh mục này sẽ được chuyển thành Không có danh mục.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">
                                                    🗑️
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Chưa có danh mục bài viết nào.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($categories->hasPages())
                    <div class="p-3 border-top">
                        {{ $categories->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const baseUrl = "{{ url('admin/post-categories') }}";
    const csrfToken = "{{ csrf_token() }}";

    function editCategory(id) {
        fetch(`${baseUrl}/${id}/edit`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const data = res.data;
                document.getElementById('form-title').innerText = '✏️ Chỉnh sửa danh mục: ' + data.name;
                document.getElementById('categoryForm').action = `${baseUrl}/${id}`;
                document.getElementById('formMethod').value = 'PUT';
                document.getElementById('categoryId').value = data.id;

                document.getElementById('catName').value = data.name || '';
                document.getElementById('catSlug').value = data.slug || '';
                document.getElementById('catParentId').value = data.parent_id || '';
                document.getElementById('catDescription').value = data.description || '';
                document.getElementById('catSortOrder').value = data.sort_order || 0;
                document.getElementById('catIsActive').checked = !!data.is_active;

                document.getElementById('catMetaTitle').value = data.meta_title || '';
                document.getElementById('catMetaDesc').value = data.meta_description || '';
                document.getElementById('catMetaKeywords').value = data.meta_keywords || '';

                document.getElementById('btnSubmit').innerText = 'Cập nhật danh mục';
                document.getElementById('btnCancel').classList.remove('d-none');

                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }

    function resetForm() {
        document.getElementById('form-title').innerText = '➕ Thêm danh mục mới';
        document.getElementById('categoryForm').action = "{{ route('admin.post-categories.store') }}";
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('categoryId').value = '';
        document.getElementById('categoryForm').reset();
        document.getElementById('catIsActive').checked = true;

        document.getElementById('btnSubmit').innerText = 'Lưu danh mục';
        document.getElementById('btnCancel').classList.add('d-none');
    }

    function toggleStatus(id, switchEl) {
        fetch(`${baseUrl}/${id}/toggle`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (!res.success) {
                switchEl.checked = !switchEl.checked;
                alert('Có lỗi xảy ra khi cập nhật trạng thái.');
            }
        })
        .catch(() => {
            switchEl.checked = !switchEl.checked;
            alert('Lỗi kết nối máy chủ.');
        });
    }
</script>
@endpush
