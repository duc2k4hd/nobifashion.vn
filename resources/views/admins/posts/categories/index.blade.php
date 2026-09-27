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
            <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h5 class="fw-bold mb-0">📋 Danh sách Danh mục</h5>
                    <button type="button" id="btnOpenExportModal" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#exportPostCategoriesModal">
                        <i class="fas fa-file-download me-1"></i> Xuất CSV / Excel
                    </button>
                    <button type="button" id="btnOpenImportModal" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#importPostCategoriesModal">
                        <i class="fas fa-file-upload me-1"></i> Nhập CSV / Excel
                    </button>
                </div>
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

<!-- Modal Xuất CSV/Excel -->
<div class="modal fade" id="exportPostCategoriesModal" tabindex="-1" aria-labelledby="exportPostCategoriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="exportPostCategoriesModalLabel">
                    <i class="fas fa-file-download text-success me-2"></i> Xuất dữ liệu Danh mục Bài viết ra CSV / Excel
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Phạm vi xuất -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-uppercase small text-muted">1. Phạm vi danh mục bài viết cần xuất</label>
                    <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded-3 border">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportScope" id="scopeAll" value="all" checked>
                            <label class="form-check-label fw-semibold" for="scopeAll">
                                <i class="fas fa-globe text-primary me-1"></i> Tất cả danh mục bài viết trong hệ thống
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

                <!-- Định dạng xuất -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-uppercase small text-muted">2. Định dạng tệp xuất</label>
                    <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded-3 border">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportFormat" id="formatCsv" value="csv" checked>
                            <label class="form-check-label fw-semibold" for="formatCsv">
                                <i class="fas fa-file-csv text-success me-1"></i> CSV (Streaming siêu tốc, 0MB RAM, hỗ trợ 100k+ danh mục)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportFormat" id="formatXlsx" value="xlsx">
                            <label class="form-check-label fw-semibold" for="formatXlsx">
                                <i class="fas fa-file-excel text-success me-1"></i> Microsoft Excel (.xlsx)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Chọn cột xuất -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold text-uppercase small text-muted mb-0">3. Chọn các cột cần xuất</label>
                        <div class="btn-group btn-group-sm">
                            <button type="button" id="btnExportSelectAll" class="btn btn-outline-secondary py-0">Chọn tất cả</button>
                            <button type="button" id="btnExportDeselectAll" class="btn btn-outline-secondary py-0">Bỏ chọn</button>
                            <button type="button" id="btnExportDefaultCols" class="btn btn-outline-primary py-0">Mặc định</button>
                        </div>
                    </div>
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="row g-2" id="exportColumnsGrid">
                            @php
                                $allExportCols = \App\Http\Controllers\Admins\PostCategoryImportExportController::supportedColumns();
                            @endphp
                            @foreach ($allExportCols as $col)
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-col-check" type="checkbox" value="{{ $col['key'] }}" id="expCol_{{ $loop->index }}" {{ $col['default_export'] ? 'checked' : '' }} data-default="{{ $col['default_export'] ? '1' : '0' }}">
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
            <div class="modal-footer border-0 pt-0 pb-4 px-4 d-flex justify-content-between">
                <a href="{{ route('admin.post-categories.sample') }}" class="btn btn-outline-secondary rounded-3">
                    <i class="fas fa-download me-1"></i> Tải file mẫu CSV
                </a>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" id="btnConfirmExport" class="btn btn-success rounded-3 px-4 fw-bold">
                        <i class="fas fa-file-download me-1"></i> Bắt đầu Xuất Dữ Liệu
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nhập CSV/Excel -->
<div class="modal fade" id="importPostCategoriesModal" tabindex="-1" aria-labelledby="importPostCategoriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="importPostCategoriesModalLabel">
                    <i class="fas fa-file-upload text-info me-2"></i> Nhập danh mục bài viết từ CSV / Excel (Batch Ultra Fast)
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
                                    <span class="text-muted" style="font-size: 0.75rem;">Hỗ trợ .csv, .xlsx, .xls (Không giới hạn dung lượng)</span>
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
                                        <button type="button" id="btnChangeModalImportFile" class="btn btn-sm btn-outline-secondary py-0">Đổi file</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quy tắc khớp thông minh -->
                        <div class="alert alert-info py-2 px-3 mb-3 small border-0" style="background: #f0f9ff; border-left: 4px solid #0284c7 !important;">
                            <div class="fw-bold mb-1 text-primary"><i class="fas fa-shield-alt me-1"></i> Quy tắc khớp danh mục bài viết thông minh:</div>
                            <ul class="mb-0 ps-3">
                                <li><strong>Có ID:</strong> Bắt buộc là cập nhật danh mục theo ID. (Báo lỗi nếu ID không tồn tại).</li>
                                <li><strong>Không có ID:</strong> Khớp theo <code>Slug</code> (hoặc sinh từ Tên). Nếu có -> Cập nhật; nếu chưa -> Tạo mới.</li>
                                <li><strong>Danh mục cha:</strong> Tự động ánh xạ qua ID, Slug hoặc Tên. Tự tạo mới danh mục cha nếu chưa tồn tại.</li>
                            </ul>
                        </div>

                        <!-- Chọn cột nhập vào (hiển thị khi đã đọc file) -->
                        <div id="modalImportColumnsContainer" class="d-none mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold text-uppercase small text-muted mb-0">2. Chọn các cột cần nhập vào</label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" id="btnModalImportSelectAll" class="btn btn-outline-secondary py-0">Chọn hết</button>
                                    <button type="button" id="btnModalImportDeselectAll" class="btn btn-outline-secondary py-0">Bỏ hết</button>
                                    <button type="button" id="btnModalImportDefaultCols" class="btn btn-outline-primary py-0">Mặc định</button>
                                </div>
                            </div>
                            <div class="p-3 bg-light rounded-3 border" style="max-height: 180px; overflow-y: auto;">
                                <div class="row g-2" id="modalImportColumnsList">
                                    <!-- Render dynamic columns -->
                                </div>
                            </div>
                            <div class="text-muted small mt-1 fst-italic">* Cột bỏ chọn sẽ giữ nguyên dữ liệu cũ khi cập nhật.</div>
                        </div>

                        <!-- Cấu hình Batch & Đa luồng -->
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted mb-1">Kích thước mẻ:</label>
                                <select id="modalBatchSize" class="form-select form-select-sm">
                                    <option value="50">50 danh mục / mẻ</option>
                                    <option value="100" selected>100 danh mục / mẻ</option>
                                    <option value="200">200 danh mục / mẻ</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted mb-1">Luồng song song:</label>
                                <select id="modalConcurrency" class="form-select form-select-sm">
                                    <option value="1">1 luồng (Tuần tự)</option>
                                    <option value="3" selected>3 luồng song song (Nhanh)</option>
                                    <option value="5">5 luồng song song (Cực nhanh)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Cột phải: Tiến trình & Console Log -->
                    <div class="col-lg-6">
                        <label class="form-label fw-bold text-uppercase small text-muted">3. Trạng thái & Tiến trình xử lý</label>
                        
                        <!-- Thống kê 4 ô -->
                        <div class="row g-2 mb-3">
                            <div class="col-3">
                                <div class="p-2 text-center rounded bg-light border">
                                    <div class="text-muted small" style="font-size: 0.72rem;">Tổng số</div>
                                    <div id="modalStatTotal" class="h6 fw-bold mb-0 text-dark">0</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 text-center rounded" style="background: #ecfdf5; border: 1px dashed #10b981;">
                                    <div class="text-success small" style="font-size: 0.72rem;">Thành công</div>
                                    <div id="modalStatSuccess" class="h6 fw-bold mb-0 text-success">0</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 text-center rounded" style="background: #eff6ff; border: 1px dashed #3b82f6;">
                                    <div class="text-primary small" style="font-size: 0.72rem;">Tạo mới</div>
                                    <div id="modalStatCreated" class="h6 fw-bold mb-0 text-primary">0</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 text-center rounded" style="background: #fef2f2; border: 1px dashed #ef4444;">
                                    <div class="text-danger small" style="font-size: 0.72rem;">Lỗi</div>
                                    <div id="modalStatError" class="h6 fw-bold mb-0 text-danger">0</div>
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

                        <!-- Activity Log Terminal -->
                        <div class="rounded-3 bg-dark overflow-hidden">
                            <div class="d-flex justify-content-between align-items-center px-3 py-1 bg-secondary text-white" style="font-size: 0.75rem;">
                                <span class="fw-bold"><i class="fas fa-terminal me-1"></i> NHẬT KÝ TIẾN TRÌNH</span>
                                <span id="modalCurrentStatus" class="opacity-75">Sẵn sàng...</span>
                            </div>
                            <div id="modalImportLog" class="p-2 font-monospace text-white-50 overflow-auto" style="height: 220px; font-size: 0.78rem; background: #0f172a;">
                                <div>> Vui lòng chọn file CSV/Excel để bắt đầu...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4 d-flex justify-content-between">
                <a href="{{ route('admin.post-categories.sample') }}" class="btn btn-outline-secondary rounded-3">
                    <i class="fas fa-download me-1"></i> Tải file mẫu CSV
                </a>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" id="btnStartPostCategoryImportBatch" class="btn btn-primary rounded-3 px-4 fw-bold" disabled>
                        <i class="fas fa-rocket me-1"></i> Bắt đầu Nhập Dữ Liệu
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    const baseUrl = "{{ url('admin/post-categories') }}";
    const csrfToken = "{{ csrf_token() }}";

    function editCategory(id) {
        fetch(`${baseUrl}/${id}/edit`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => {
            if (!res.ok) throw new Error('Mã lỗi ' + res.status);
            return res.json();
        })
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

                // Tự động mở accordion cấu hình SEO nếu có dữ liệu
                if (data.meta_title || data.meta_description || data.meta_keywords) {
                    const seoCollapse = document.getElementById('seoCollapse');
                    if (seoCollapse && typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                        bootstrap.Collapse.getOrCreateInstance(seoCollapse).show();
                    }
                }

                // Cuộn mượt lên form và focus vào trường tên
                window.scrollTo({ top: 0, behavior: 'smooth' });
                document.getElementById('catName').focus();
            } else {
                alert('Không thể tải thông tin danh mục.');
            }
        })
        .catch(err => {
            console.error('Lỗi editCategory:', err);
            alert('Có lỗi xảy ra khi tải dữ liệu: ' + err.message);
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

        const seoCollapse = document.getElementById('seoCollapse');
        if (seoCollapse && typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
            bootstrap.Collapse.getOrCreateInstance(seoCollapse).hide();
        }
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

    // ==========================================
    // LOGIC XUẤT CSV / EXCEL DANH MỤC BÀI VIẾT
    // ==========================================
    document.addEventListener('DOMContentLoaded', function () {
        const exportModalEl = document.getElementById('exportPostCategoriesModal');
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
            btnConfirmExport.addEventListener('click', function () {
                const selectedCols = Array.from(document.querySelectorAll('.export-col-check:checked')).map(cb => cb.value);
                if (selectedCols.length === 0) {
                    alert('Vui lòng chọn ít nhất một cột để xuất dữ liệu.');
                    return;
                }

                const scope = document.querySelector('input[name="exportScope"]:checked')?.value || 'all';
                const format = document.querySelector('input[name="exportFormat"]:checked')?.value || 'csv';
                const params = new URLSearchParams();

                selectedCols.forEach(col => params.append('columns[]', col));
                params.append('format', format);

                if (scope === 'filter') {
                    const currentUrlParams = new URLSearchParams(window.location.search);
                    for (const [key, val] of currentUrlParams.entries()) {
                        if (val && key !== 'page') {
                            params.append(key, val);
                        }
                    }
                }

                if (typeof bootstrap !== 'undefined' && exportModalEl) {
                    const modalInstance = bootstrap.Modal.getInstance(exportModalEl) || new bootstrap.Modal(exportModalEl);
                    modalInstance.hide();
                }

                window.location.href = "{{ route('admin.post-categories.export') }}?" + params.toString();
            });
        }

        // ==========================================
        // LOGIC NHẬP CSV / EXCEL DANH MỤC BÀI VIẾT (BATCH ULTRA FAST)
        // ==========================================
        const modalImportDropZone = document.getElementById('modalImportDropZone');
        const modalImportFileInput = document.getElementById('modalImportFileInput');
        const modalImportDropContent = document.getElementById('modalImportDropContent');
        const modalImportFileInfo = document.getElementById('modalImportFileInfo');
        const modalImportFileName = document.getElementById('modalImportFileName');
        const modalImportFileSize = document.getElementById('modalImportFileSize');
        const btnChangeModalImportFile = document.getElementById('btnChangeModalImportFile');

        const modalImportColumnsContainer = document.getElementById('modalImportColumnsContainer');
        const modalImportColumnsList = document.getElementById('modalImportColumnsList');
        const btnModalImportSelectAll = document.getElementById('btnModalImportSelectAll');
        const btnModalImportDeselectAll = document.getElementById('btnModalImportDeselectAll');
        const btnModalImportDefaultCols = document.getElementById('btnModalImportDefaultCols');

        const modalBatchSize = document.getElementById('modalBatchSize');
        const modalConcurrency = document.getElementById('modalConcurrency');
        const btnStartPostCategoryImportBatch = document.getElementById('btnStartPostCategoryImportBatch');

        const modalProgressArea = document.getElementById('modalProgressArea');
        const modalProgressBar = document.getElementById('modalProgressBar');
        const modalProgressText = document.getElementById('modalProgressText');
        const modalPercentText = document.getElementById('modalPercentText');
        const modalCurrentStatus = document.getElementById('modalCurrentStatus');

        const modalStatTotal = document.getElementById('modalStatTotal');
        const modalStatSuccess = document.getElementById('modalStatSuccess');
        const modalStatCreated = document.getElementById('modalStatCreated');
        const modalStatError = document.getElementById('modalStatError');
        const modalImportLog = document.getElementById('modalImportLog');

        let postCategoryImportData = [];
        let detectedImportHeaders = [];

        const addModalLog = (msg, type = 'info') => {
            if (!modalImportLog) return;
            const colorClass = type === 'success' ? 'text-success' : (type === 'error' ? 'text-danger' : (type === 'warn' ? 'text-warning' : 'text-white-50'));
            const div = document.createElement('div');
            div.className = `mb-1 ${colorClass}`;
            div.innerHTML = `<span class="opacity-50">></span> [${new Date().toLocaleTimeString()}] ${msg}`;
            modalImportLog.appendChild(div);
            modalImportLog.scrollTop = modalImportLog.scrollHeight;
        };

        const updateModalProgress = (current, total) => {
            if (!modalProgressBar) return;
            const percent = total > 0 ? Math.round((current / total) * 100) : 0;
            modalProgressBar.style.width = `${percent}%`;
            if (modalProgressText) modalProgressText.innerText = `Đang xử lý: ${current}/${total}`;
            if (modalPercentText) modalPercentText.innerText = `${percent}%`;
        };

        if (modalImportDropZone && modalImportFileInput) {
            modalImportDropZone.addEventListener('click', (e) => {
                if (e.target !== btnChangeModalImportFile) {
                    modalImportFileInput.click();
                }
            });

            if (btnChangeModalImportFile) {
                btnChangeModalImportFile.addEventListener('click', (e) => {
                    e.stopPropagation();
                    modalImportFileInput.click();
                });
            }

            modalImportDropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                modalImportDropZone.style.borderColor = '#3b82f6';
                modalImportDropZone.style.background = '#eff6ff';
            });

            modalImportDropZone.addEventListener('dragleave', () => {
                modalImportDropZone.style.borderColor = '#cbd5e1';
                modalImportDropZone.style.background = '#f8fafc';
            });

            modalImportDropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                modalImportDropZone.style.borderColor = '#cbd5e1';
                modalImportDropZone.style.background = '#f8fafc';
                if (e.dataTransfer.files.length) {
                    handleSelectedModalImportFile(e.dataTransfer.files[0]);
                }
            });

            modalImportFileInput.addEventListener('change', (e) => {
                if (e.target.files.length) {
                    handleSelectedModalImportFile(e.target.files[0]);
                }
            });
        }

        function handleSelectedModalImportFile(file) {
            if (!file.name.match(/\.(csv|xlsx|xls)$/i)) {
                alert('Định dạng không hợp lệ. Vui lòng chọn tệp có đuôi .csv, .xlsx hoặc .xls');
                return;
            }

            if (modalImportFileName) modalImportFileName.innerText = file.name;
            if (modalImportFileSize) modalImportFileSize.innerText = `${Math.round(file.size / 1024)} KB`;
            if (modalImportFileInfo) modalImportFileInfo.classList.remove('d-none');
            if (modalImportDropContent) modalImportDropContent.classList.add('d-none');

            if (modalCurrentStatus) modalCurrentStatus.innerText = 'Đang đọc tệp...';
            addModalLog(`Đang đọc tệp: ${file.name} (${Math.round(file.size / 1024)} KB)...`, 'info');

            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];

                    postCategoryImportData = XLSX.utils.sheet_to_json(firstSheet);
                    const rawHeaderRows = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });
                    detectedImportHeaders = (rawHeaderRows && rawHeaderRows.length > 0) ? rawHeaderRows[0] : [];

                    if (modalStatTotal) modalStatTotal.innerText = postCategoryImportData.length;
                    if (modalStatSuccess) modalStatSuccess.innerText = '0';
                    if (modalStatCreated) modalStatCreated.innerText = '0';
                    if (modalStatError) modalStatError.innerText = '0';

                    if (postCategoryImportData.length === 0) {
                        addModalLog('Tệp không có dữ liệu danh mục hợp lệ.', 'error');
                        if (btnStartPostCategoryImportBatch) btnStartPostCategoryImportBatch.disabled = true;
                        if (modalImportColumnsContainer) modalImportColumnsContainer.classList.add('d-none');
                        return;
                    }

                    // Render dynamic checkboxes
                    if (modalImportColumnsList) {
                        modalImportColumnsList.innerHTML = '';
                        detectedImportHeaders.forEach((colName, idx) => {
                            if (!colName || String(colName).trim() === '') return;
                            const trimmed = String(colName).trim();
                            const lower = trimmed.toLowerCase();
                            const isDefault = (
                                lower === 'id' ||
                                lower.includes('tên') || lower.includes('name') ||
                                lower === 'slug' ||
                                lower.includes('cha') || lower.includes('parent') ||
                                lower.includes('thứ tự') || lower.includes('sort') ||
                                lower.includes('trạng thái') || lower.includes('status')
                            );

                            const colDiv = document.createElement('div');
                            colDiv.className = 'col-sm-6';
                            colDiv.innerHTML = `
                                <div class="form-check">
                                    <input class="form-check-input modal-col-check" type="checkbox" value="${trimmed}" id="pcCol_${idx}" ${isDefault ? 'checked' : ''} data-default="${isDefault ? '1' : '0'}">
                                    <label class="form-check-label small text-truncate" for="pcCol_${idx}" title="${trimmed}">
                                        ${trimmed}
                                    </label>
                                </div>
                            `;
                            modalImportColumnsList.appendChild(colDiv);
                        });
                    }

                    if (modalImportColumnsContainer) modalImportColumnsContainer.classList.remove('d-none');
                    if (btnStartPostCategoryImportBatch) btnStartPostCategoryImportBatch.disabled = false;
                    if (modalCurrentStatus) modalCurrentStatus.innerText = `Sẵn sàng (${postCategoryImportData.length} danh mục)`;
                    addModalLog(`Đọc tệp thành công! Tìm thấy ${postCategoryImportData.length} danh mục và ${detectedImportHeaders.length} cột.`, 'success');
                } catch (err) {
                    console.error(err);
                    addModalLog('Lỗi khi đọc file: ' + err.message, 'error');
                    if (modalCurrentStatus) modalCurrentStatus.innerText = 'Lỗi đọc tệp';
                    if (btnStartPostCategoryImportBatch) btnStartPostCategoryImportBatch.disabled = true;
                }
            };
            reader.readAsArrayBuffer(file);
        }

        if (btnModalImportSelectAll) {
            btnModalImportSelectAll.addEventListener('click', () => {
                document.querySelectorAll('.modal-col-check').forEach(cb => cb.checked = true);
            });
        }
        if (btnModalImportDeselectAll) {
            btnModalImportDeselectAll.addEventListener('click', () => {
                document.querySelectorAll('.modal-col-check').forEach(cb => cb.checked = false);
            });
        }
        if (btnModalImportDefaultCols) {
            btnModalImportDefaultCols.addEventListener('click', () => {
                document.querySelectorAll('.modal-col-check').forEach(cb => {
                    cb.checked = cb.dataset.default === '1';
                });
            });
        }

        if (btnStartPostCategoryImportBatch) {
            btnStartPostCategoryImportBatch.addEventListener('click', async () => {
                if (!postCategoryImportData.length) return;

                const selectedCols = Array.from(document.querySelectorAll('.modal-col-check:checked')).map(cb => cb.value);
                if (selectedCols.length === 0) {
                    alert('Vui lòng chọn ít nhất một cột cần nhập vào hệ thống.');
                    return;
                }

                const batchSize = parseInt(modalBatchSize?.value) || 100;
                const concurrency = parseInt(modalConcurrency?.value) || 3;

                btnStartPostCategoryImportBatch.disabled = true;
                btnStartPostCategoryImportBatch.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> ĐANG NHẬP DỮ LIỆU...';
                if (modalProgressArea) modalProgressArea.classList.remove('d-none');
                if (modalCurrentStatus) modalCurrentStatus.innerText = 'Đang xử lý các mẻ batch...';

                let processedCount = 0;
                let successCount = 0;
                let createdCount = 0;
                let errorCount = 0;

                addModalLog(`Bắt đầu nhập ${postCategoryImportData.length} danh mục bài viết với ${concurrency} luồng song song (mỗi mẻ ${batchSize} dòng)...`, 'info');

                const chunks = [];
                for (let i = 0; i < postCategoryImportData.length; i += batchSize) {
                    chunks.push({
                        index: Math.floor(i / batchSize) + 1,
                        items: postCategoryImportData.slice(i, i + batchSize)
                    });
                }

                const totalBatches = chunks.length;
                let chunkCursor = 0;

                async function runWorker(workerId) {
                    while (chunkCursor < totalBatches) {
                        const chunk = chunks[chunkCursor++];
                        if (!chunk) break;

                        addModalLog(`[Luồng ${workerId}] Đang gửi mẻ ${chunk.index}/${totalBatches} (${chunk.items.length} danh mục)...`, 'info');

                        try {
                            const response = await fetch("{{ route('admin.post-categories.import-batch') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    items: chunk.items,
                                    selected_columns: selectedCols
                                })
                            });

                            const result = await response.json();

                            if (result.success) {
                                processedCount += chunk.items.length;
                                successCount += (result.success_count || 0);
                                createdCount += (result.created_count || 0);

                                if (result.errors && result.errors.length) {
                                    errorCount += result.errors.length;
                                    result.errors.forEach(err => addModalLog(`⚠️ [Mẻ ${chunk.index}]: ${err}`, 'error'));
                                }

                                if (modalStatSuccess) modalStatSuccess.innerText = successCount;
                                if (modalStatCreated) modalStatCreated.innerText = createdCount;
                                if (modalStatError) modalStatError.innerText = errorCount;
                                updateModalProgress(processedCount, postCategoryImportData.length);
                                addModalLog(`[Luồng ${workerId}] Mẻ ${chunk.index}/${totalBatches} thành công (+${result.success_count} danh mục).`, 'success');
                            } else {
                                throw new Error(result.message || 'Lỗi xử lý từ máy chủ');
                            }
                        } catch (err) {
                            console.error(err);
                            addModalLog(`❌ [Luồng ${workerId}] Lỗi tại mẻ ${chunk.index}: ${err.message}`, 'error');
                            errorCount += chunk.items.length;
                            if (modalStatError) modalStatError.innerText = errorCount;
                        }
                    }
                }

                const workers = [];
                const activeWorkers = Math.min(concurrency, totalBatches);
                for (let w = 1; w <= activeWorkers; w++) {
                    workers.push(runWorker(w));
                }

                await Promise.all(workers);

                if (modalCurrentStatus) modalCurrentStatus.innerText = 'Hoàn tất!';
                addModalLog(`🎉 Quá trình nhập hoàn tất! Thành công: ${successCount} (Tạo mới: ${createdCount}), Lỗi: ${errorCount}`, 'success');
                btnStartPostCategoryImportBatch.innerHTML = '<i class="fas fa-check me-2"></i> HOÀN TẤT NHẬP DỮ LIỆU';
                btnStartPostCategoryImportBatch.classList.remove('btn-primary');
                btnStartPostCategoryImportBatch.classList.add('btn-success');

                setTimeout(() => {
                    if (confirm(`Quá trình nhập dữ liệu danh mục bài viết đã hoàn tất!\n- Thành công: ${successCount}\n- Tạo mới: ${createdCount}\n- Lỗi: ${errorCount}\n\nBạn có muốn làm mới trang để xem danh sách cập nhật không?`)) {
                        window.location.reload();
                    }
                }, 800);
            });
        }
    });
</script>
@endpush
