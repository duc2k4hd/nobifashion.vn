@extends('admins.layouts.master')

@section('title', 'Quản lý Thư viện Sticker bình luận Reels')

@push('styles')
<link rel="stylesheet" href="{{ asset('admins/css/reels_admin.css') }}?v={{ time() }}">
<style>
    /* Sticker cards */
    .sticker-preview-box {
        height: 110px;
        background: #f8fafc;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 8px;
        border: 1px dashed #e2e8f0;
        transition: transform 0.2s ease, background 0.2s ease, border-color 0.2s ease;
    }
    .sticker-card:hover .sticker-preview-box {
        transform: translateY(-2px);
        background: #f1f5f9;
        border-color: #94a3b8;
    }
    .sticker-preview-img {
        max-height: 90px;
        max-width: 90px;
        object-fit: contain;
    }

    /* Modal Styling */
    .sticker-modal-dialog {
        max-width: 720px;
    }
    .sticker-modal-content {
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        overflow: hidden;
    }
    .sticker-modal-header {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e2e8f0;
        padding: 16px 24px;
    }
    .sticker-modal-preview-box {
        background: radial-gradient(#cbd5e1 10%, transparent 11%), #f8fafc;
        background-size: 14px 14px;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    .sticker-modal-preview-img {
        max-height: 140px;
        max-width: 140px;
        object-fit: contain;
        transition: transform 0.2s ease;
    }
    .category-pill {
        cursor: pointer;
        user-select: none;
        transition: all 0.15s ease;
        font-size: 11px;
    }
    .category-pill:hover {
        background-color: #0f172a !important;
        color: #ffffff !important;
        border-color: #0f172a !important;
    }
    .source-tab-btn {
        border-radius: 8px;
        font-size: 12px;
        font-weight: 500;
        padding: 6px 14px;
        cursor: pointer;
    }
    .source-tab-btn.active {
        background-color: #0f172a;
        color: #fff;
    }
</style>
@endpush

@section('content')
<div class="nobifashion_admin_reels_wrapper container-fluid px-3 py-3">
    <!-- Header -->
    <div class="nobifashion_admin_reels_page_header mb-3">
        <div>
            <h1 class="nobifashion_admin_reels_page_title">
                <i class="fa-solid fa-icons text-warning me-2"></i>Thư viện Sticker bình luận
            </h1>
            <div class="nobifashion_admin_reels_breadcrumb">
                <a href="{{ route('admin.reels.dashboard') }}">Reels</a>
                <span>/</span>
                <span>Sticker tương tác</span>
            </div>
        </div>
        <div class="nobifashion_admin_reels_page_actions d-flex gap-2">
            <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_accent" data-bs-toggle="modal" data-bs-target="#zaloSyncModal" onclick="initZaloSyncModal()">
                <i class="fa-solid fa-cloud-arrow-down me-1"></i> Đồng bộ Sticker Zalo (Tool 1-Click)
            </button>
            <form action="{{ route('admin.reels.stickers.seed-presets') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary" onclick="return confirm('Bạn có muốn nạp bộ 12 sticker thời trang mẫu vào hệ thống?');">
                    <i class="fa-solid fa-wand-magic-sparkles text-primary me-1"></i> Nạp 12 Sticker Mẫu
                </button>
            </form>
            <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary" data-bs-toggle="modal" data-bs-target="#createStickerModal">
                <i class="fa-solid fa-plus me-1"></i> Thêm Sticker mới
            </button>
        </div>
    </div>

    <!-- Subnav Tabs -->
    @include('admins.reels.partials.subnav')

    <!-- Flash message -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <div>{{ session('warning') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- 4 KPI Cards -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="nobifashion_admin_reels_kpi_card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="nobifashion_admin_reels_kpi_label">Tổng số Sticker</div>
                        <div class="nobifashion_admin_reels_kpi_value text-primary">{{ number_format($counts['total'] ?? 0) }}</div>
                    </div>
                    <div class="nobifashion_admin_reels_kpi_icon bg-primary-subtle text-primary">
                        <i class="fa-solid fa-images"></i>
                    </div>
                </div>
                <div class="nobifashion_admin_reels_kpi_sub text-muted small mt-2">Toàn bộ kho biểu tượng</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="nobifashion_admin_reels_kpi_card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="nobifashion_admin_reels_kpi_label">Đang phát hành</div>
                        <div class="nobifashion_admin_reels_kpi_value text-success">{{ number_format($counts['active'] ?? 0) }}</div>
                    </div>
                    <div class="nobifashion_admin_reels_kpi_icon bg-success-subtle text-success">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
                <div class="nobifashion_admin_reels_kpi_sub text-muted small mt-2">Hiển thị trong khay bình luận</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="nobifashion_admin_reels_kpi_card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="nobifashion_admin_reels_kpi_label">Đang tạm ẩn</div>
                        <div class="nobifashion_admin_reels_kpi_value text-secondary">{{ number_format($counts['inactive'] ?? 0) }}</div>
                    </div>
                    <div class="nobifashion_admin_reels_kpi_icon bg-secondary-subtle text-secondary">
                        <i class="fa-solid fa-eye-slash"></i>
                    </div>
                </div>
                <div class="nobifashion_admin_reels_kpi_sub text-muted small mt-2">Tạm dừng cho người xem dùng</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="nobifashion_admin_reels_kpi_card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="nobifashion_admin_reels_kpi_label">Nhóm danh mục</div>
                        <div class="nobifashion_admin_reels_kpi_value text-warning">{{ number_format($counts['categories'] ?? 0) }}</div>
                    </div>
                    <div class="nobifashion_admin_reels_kpi_icon bg-warning-subtle text-warning">
                        <i class="fa-solid fa-folder-tree"></i>
                    </div>
                </div>
                <div class="nobifashion_admin_reels_kpi_sub text-muted small mt-2">Phân loại bộ sưu tập</div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="nobifashion_admin_reels_card mb-3">
        <div class="nobifashion_admin_reels_card_body py-2">
            <form action="{{ route('admin.reels.stickers.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Tìm theo tên hoặc mã code..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select form-select-sm">
                        <option value="all">Tất cả nhóm danh mục</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary btn-sm flex-fill">
                        <i class="fa-solid fa-filter me-1"></i> Lọc
                    </button>
                    @if(request()->hasAny(['search', 'category']))
                        <a href="{{ route('admin.reels.stickers.index') }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary btn-sm" title="Đặt lại bộ lọc">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Sticker Grid -->
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
        @forelse($stickers as $sticker)
            <div class="col">
                <div class="nobifashion_admin_reels_card sticker-card h-100 p-2 position-relative d-flex flex-column">
                    <!-- Status Badge -->
                    <div class="position-absolute top-0 end-0 p-2 z-1">
                        @if($sticker->is_active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 10px;">Hoạt động</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill" style="font-size: 10px;">Tạm ẩn</span>
                        @endif
                    </div>

                    <!-- Category Badge -->
                    <div class="position-absolute top-0 start-0 p-2 z-1">
                        <span class="badge bg-light text-dark border rounded-pill" style="font-size: 10px;">{{ $sticker->category }}</span>
                    </div>

                    <!-- Sticker Preview Box -->
                    <div class="sticker-preview-box mb-2 mt-4">
                        <img src="{{ Str::startsWith($sticker->asset_url, 'http') ? $sticker->asset_url : asset($sticker->asset_url) }}" 
                             alt="{{ $sticker->name }}" 
                             class="sticker-preview-img"
                             onerror="this.onerror=null; this.src='https://placehold.co/90x90?text=Sticker';">
                    </div>

                    <!-- Title & Code -->
                    <div class="text-center px-1">
                        <h6 class="fw-bold mb-1 text-truncate" title="{{ $sticker->name }}" style="font-size: 13px;">{{ $sticker->name }}</h6>
                        <span class="badge bg-secondary-subtle text-muted text-monospace px-2 py-1 mb-2" style="font-size: 11px;">:{{ $sticker->code }}:</span>
                    </div>

                    <!-- Action Bar -->
                    <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                        <!-- Toggle Button -->
                        <form action="{{ route('admin.reels.stickers.toggle', $sticker->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-link p-0 text-decoration-none" title="{{ $sticker->is_active ? 'Nhấn để tạm ẩn' : 'Nhấn để kích hoạt' }}">
                                <i class="fa-solid {{ $sticker->is_active ? 'fa-toggle-on text-success fs-5' : 'fa-toggle-off text-muted fs-5' }}"></i>
                            </button>
                        </form>

                        <div class="d-flex gap-1">
                            <!-- Edit Button -->
                            <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" onclick="openEditStickerModal({{ json_encode($sticker) }})" title="Chỉnh sửa sticker">
                                <i class="fa-solid fa-pencil" style="font-size: 11px;"></i>
                            </button>
                            <!-- Delete Button -->
                            <form action="{{ route('admin.reels.stickers.destroy', $sticker->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa sticker :{{ $sticker->code }}:? (Hệ thống sẽ bảo toàn nếu đã có bình luận sử dụng)');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Xóa sticker">
                                    <i class="fa-solid fa-trash" style="font-size: 11px;"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 w-100">
                <div class="nobifashion_admin_reels_card text-center py-5">
                    <div class="mb-3">
                        <i class="fa-solid fa-face-smile fa-3x text-warning"></i>
                    </div>
                    <h5 class="fw-bold">Thư viện Sticker hiện chưa có biểu tượng nào</h5>
                    <p class="text-muted small mb-3">Bạn có thể nạp nhanh bộ 12 sticker thời trang mẫu hoặc tự tải lên sticker mới!</p>
                    <div class="d-flex justify-content-center gap-2">
                        <form action="{{ route('admin.reels.stickers.seed-presets') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Nạp 12 Sticker Mẫu Ngay
                            </button>
                        </form>
                        <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary" data-bs-toggle="modal" data-bs-target="#createStickerModal">
                            <i class="fa-solid fa-plus me-1"></i> Tải Sticker Mới Lên
                        </button>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($stickers->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $stickers->links() }}
        </div>
    @endif
</div>

<!-- ========================================== -->
<!-- MODAL: THÊM STICKER MỚI                     -->
<!-- ========================================== -->
<div class="modal fade" id="createStickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered sticker-modal-dialog">
        <form action="{{ route('admin.reels.stickers.store') }}" method="POST" enctype="multipart/form-data" class="modal-content sticker-modal-content">
            @csrf
            <div class="modal-header sticker-modal-header">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary-subtle text-primary p-2 me-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fa-solid fa-plus fs-6"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" style="font-size: 16px;">Thêm Sticker Mới</h5>
                        <small class="text-muted">Tạo biểu tượng cảm xúc mới cho người xem bình luận</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-4">
                    <!-- Cột trái: Live Preview -->
                    <div class="col-md-5 text-center d-flex flex-column">
                        <label class="form-label fw-semibold text-secondary small text-start w-100 mb-2">
                            <i class="fa-solid fa-eye text-primary me-1"></i> Khung xem trước trực tiếp
                        </label>
                        <div class="sticker-modal-preview-box mb-3">
                            <img id="createPreviewImg" src="https://placehold.co/120x120?text=Preview" alt="Xem trước" class="sticker-modal-preview-img">
                            <span id="createPreviewActiveBadge" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill position-absolute top-0 end-0 m-2" style="font-size: 10px;">Hoạt động</span>
                        </div>

                        <div class="p-2 bg-light rounded-3 border text-start">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-muted">Hiển thị mã:</span>
                                <span id="createPreviewCodeBadge" class="badge bg-dark-subtle text-dark text-monospace">:sticker:</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted">Phân loại:</span>
                                <span id="createPreviewCategoryBadge" class="badge bg-warning-subtle text-dark">fashion</span>
                            </div>
                        </div>
                    </div>

                    <!-- Cột phải: Form nhập liệu -->
                    <div class="col-md-7">
                        <!-- Tên hiển thị & Mã code -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small mb-1">Mã code <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted">:</span>
                                    <input type="text" name="code" id="createCodeInput" class="form-control" required placeholder="chot_don" oninput="updateCreatePreview()">
                                    <span class="input-group-text bg-light text-muted">:</span>
                                </div>
                                <div class="text-muted" style="font-size: 11px;">Viết thường, không dấu</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small mb-1">Tên hiển thị <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm" required placeholder="Ví dụ: Chốt Đơn Nhanh">
                            </div>
                        </div>

                        <!-- Chọn nguồn hình ảnh (File hoặc URL) -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold text-secondary small mb-0">Nguồn hình ảnh Sticker <span class="text-danger">*</span></label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary py-0 px-2 active" id="btnTabCreateFile" onclick="toggleCreateSource('file')">
                                        <i class="fa-solid fa-upload me-1"></i> Tệp máy tính
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary py-0 px-2" id="btnTabCreateUrl" onclick="toggleCreateSource('url')">
                                        <i class="fa-solid fa-link me-1"></i> Link URL
                                    </button>
                                </div>
                            </div>

                            <div id="createFileInputWrapper">
                                <input type="file" name="image_file" id="createFileInput" class="form-control form-control-sm" accept=".svg,.png,.webp,.jpg,.jpeg,.gif" onchange="previewLocalImage(this, 'createPreviewImg')">
                                <div class="text-muted mt-1" style="font-size: 11px;">Hỗ trợ .SVG, .PNG trong suốt, .WEBP (Tối đa 3MB)</div>
                            </div>

                            <div id="createUrlInputWrapper" style="display: none;">
                                <input type="text" name="asset_url" id="createUrlInput" class="form-control form-control-sm" placeholder="https://pub-xxx.r2.dev/stickers/... hoặc /clients/assets/img/stickers/..." oninput="updateCreateUrlPreview(this.value)">
                                <div class="text-muted mt-1" style="font-size: 11px;">Đường dẫn Cloudflare R2 hoặc URL công khai</div>
                            </div>
                        </div>

                        <!-- Danh mục & Thứ tự -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold text-secondary small mb-1">Danh mục nhóm <span class="text-danger">*</span></label>
                                <input type="text" name="category" id="createCategoryInput" class="form-control form-control-sm" required value="fashion" oninput="updateCreateCategoryPreview(this.value)">
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    <span class="badge bg-light text-secondary border category-pill" onclick="setCreateCategory('shopping')">shopping</span>
                                    <span class="badge bg-light text-secondary border category-pill" onclick="setCreateCategory('fashion')">fashion</span>
                                    <span class="badge bg-light text-secondary border category-pill" onclick="setCreateCategory('emotion')">emotion</span>
                                    <span class="badge bg-light text-secondary border category-pill" onclick="setCreateCategory('brand')">brand</span>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold text-secondary small mb-1">Thứ tự sắp xếp</label>
                                <input type="number" name="sort_order" class="form-control form-control-sm" value="0" min="0">
                                <div class="text-muted" style="font-size: 11px;">Ưu tiên số nhỏ trước</div>
                            </div>
                        </div>

                        <!-- Trạng thái kích hoạt -->
                        <div class="form-check form-switch p-2 bg-light rounded-2 border">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="is_active" value="1" id="createStickerActive" checked onchange="updateCreateActiveBadge(this.checked)">
                            <label class="form-check-label fw-semibold small text-dark" for="createStickerActive">
                                Kích hoạt phát hành ngay trong bình luận
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light px-4 py-2 border-top">
                <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                    <i class="fa-solid fa-check me-1"></i> Lưu & Phát Hành Sticker
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: CHỈNH SỬA STICKER                    -->
<!-- ========================================== -->
<div class="modal fade" id="editStickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered sticker-modal-dialog">
        <form id="editStickerForm" method="POST" enctype="multipart/form-data" class="modal-content sticker-modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header sticker-modal-header">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary-subtle text-primary p-2 me-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fa-solid fa-pen-to-square fs-6"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" style="font-size: 16px;">Chỉnh sửa thông tin Sticker</h5>
                        <small class="text-muted">Cập nhật mã code, danh mục, hình ảnh hoặc trạng thái phát hành</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-4">
                    <!-- Cột trái: Live Preview -->
                    <div class="col-md-5 text-center d-flex flex-column">
                        <label class="form-label fw-semibold text-secondary small text-start w-100 mb-2">
                            <i class="fa-solid fa-eye text-primary me-1"></i> Khung xem trước trực tiếp
                        </label>
                        <div class="sticker-modal-preview-box mb-3">
                            <img id="editPreviewImg" src="" alt="Xem trước" class="sticker-modal-preview-img">
                            <span id="editPreviewActiveBadge" class="badge rounded-pill position-absolute top-0 end-0 m-2" style="font-size: 10px;">Hoạt động</span>
                        </div>

                        <div class="p-2 bg-light rounded-3 border text-start">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-muted">Hiển thị mã:</span>
                                <span id="editPreviewCodeBadge" class="badge bg-dark-subtle text-dark text-monospace">:code:</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted">Phân loại:</span>
                                <span id="editPreviewCategoryBadge" class="badge bg-warning-subtle text-dark">category</span>
                            </div>
                        </div>
                    </div>

                    <!-- Cột phải: Form nhập liệu -->
                    <div class="col-md-7">
                        <!-- Tên hiển thị & Mã code -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small mb-1">Mã code <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted">:</span>
                                    <input type="text" id="editStickerCode" name="code" class="form-control" required oninput="updateEditPreviewCode(this.value)">
                                    <span class="input-group-text bg-light text-muted">:</span>
                                </div>
                                <div class="text-muted" style="font-size: 11px;">Mã định danh duy nhất</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small mb-1">Tên hiển thị <span class="text-danger">*</span></label>
                                <input type="text" id="editStickerName" name="name" class="form-control form-control-sm" required>
                            </div>
                        </div>

                        <!-- Chọn nguồn hình ảnh (File hoặc URL) -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold text-secondary small mb-0">Hình ảnh biểu tượng</label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary py-0 px-2" id="btnTabEditFile" onclick="toggleEditSource('file')">
                                        <i class="fa-solid fa-upload me-1"></i> Đổi tệp mới
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary py-0 px-2 active" id="btnTabEditUrl" onclick="toggleEditSource('url')">
                                        <i class="fa-solid fa-link me-1"></i> Link URL hiện tại
                                    </button>
                                </div>
                            </div>

                            <div id="editFileInputWrapper" style="display: none;">
                                <input type="file" name="image_file" id="editFileInput" class="form-control form-control-sm" accept=".svg,.png,.webp,.jpg,.jpeg,.gif" onchange="previewLocalImage(this, 'editPreviewImg')">
                                <div class="text-muted mt-1" style="font-size: 11px;">Chọn tệp nếu bạn muốn thay đổi hình ảnh hiện tại</div>
                            </div>

                            <div id="editUrlInputWrapper">
                                <input type="text" id="editStickerUrl" name="asset_url" class="form-control form-control-sm" placeholder="https://pub-xxx.r2.dev/stickers/... hoặc /clients/assets/img/stickers/..." oninput="updateEditUrlPreview(this.value)">
                                <div class="text-muted mt-1" style="font-size: 11px;">Đường dẫn ảnh lưu trữ Cloudflare R2 hoặc Local</div>
                            </div>
                        </div>

                        <!-- Danh mục & Thứ tự -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold text-secondary small mb-1">Danh mục nhóm <span class="text-danger">*</span></label>
                                <input type="text" id="editStickerCategory" name="category" class="form-control form-control-sm" required oninput="updateEditPreviewCategory(this.value)">
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    <span class="badge bg-light text-secondary border category-pill" onclick="setEditCategory('shopping')">shopping</span>
                                    <span class="badge bg-light text-secondary border category-pill" onclick="setEditCategory('fashion')">fashion</span>
                                    <span class="badge bg-light text-secondary border category-pill" onclick="setEditCategory('emotion')">emotion</span>
                                    <span class="badge bg-light text-secondary border category-pill" onclick="setEditCategory('brand')">brand</span>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold text-secondary small mb-1">Thứ tự sắp xếp</label>
                                <input type="number" id="editStickerSort" name="sort_order" class="form-control form-control-sm" min="0">
                                <div class="text-muted" style="font-size: 11px;">Thứ tự hiển thị</div>
                            </div>
                        </div>

                        <!-- Trạng thái kích hoạt -->
                        <div class="form-check form-switch p-2 bg-light rounded-2 border">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="is_active" value="1" id="editStickerActive" onchange="updateEditActiveBadge(this.checked)">
                            <label class="form-check-label fw-semibold small text-dark" for="editStickerActive">
                                Đang kích hoạt phát hành trong bình luận
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light px-4 py-2 border-top">
                <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Cập Nhật Thay Đổi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: CÔNG CỤ ĐỒNG BỘ STICKER TỪ ZALO STORE -->
<!-- ========================================== -->
<div class="modal fade" id="zaloSyncModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content sticker-modal-content">
            <div class="modal-header sticker-modal-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-info-subtle text-info p-2 me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-cloud-arrow-down fs-5"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="modal-title fw-bold mb-0" style="font-size: 17px;">Công cụ Đồng bộ & Tách Sticker từ Zalo Store</h5>
                            <span id="zaloTotalPacksBadge" class="badge bg-primary">Đang tải...</span>
                        </div>
                        <small class="text-muted">Tự động tải ảnh lưới 3x3 và cắt thành 9 sticker con bằng thuật toán ma trận GD</small>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadZaloPacks(true)" title="Tải lại danh sách từ Zalo">
                        <i class="fa-solid fa-rotate me-1"></i> Làm mới danh sách
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="onCloseZaloModal()"></button>
                </div>
            </div>

            <div class="modal-body p-3 bg-light">
                <!-- Khung Điều khiển Tiến trình Auto-Sync 1-Click -->
                <div class="card border shadow-sm mb-3">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">
                                    <i class="fa-solid fa-wand-magic-sparkles text-primary me-1"></i> Tiến trình Đồng bộ 1-Click
                                </h6>
                                <div id="zaloProgressStatus" class="small text-muted">Sẵn sàng đồng bộ toàn bộ kho sticker Zalo vào hệ thống Reels...</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" id="btnStartAutoSync" class="btn btn-success btn-sm px-3 fw-semibold shadow-sm" onclick="startAutoSyncZalo()">
                                    <i class="fa-solid fa-play me-1"></i> Bắt đầu Đồng Bộ Tất Cả
                                </button>
                                <button type="button" id="btnPauseAutoSync" class="btn btn-warning btn-sm px-3 fw-semibold shadow-sm" style="display: none;" onclick="pauseAutoSyncZalo()">
                                    <i class="fa-solid fa-pause me-1"></i> Tạm dừng
                                </button>
                            </div>
                        </div>

                        <!-- Progress bar -->
                        <div class="progress mb-2" style="height: 14px; border-radius: 8px; background-color: #e2e8f0;">
                            <div id="zaloProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success fw-bold" role="progressbar" style="width: 0%; font-size: 10px;">0%</div>
                        </div>

                        <div class="d-flex justify-content-between small text-muted">
                            <span id="zaloProgressCounter">Đã hoàn thành: 0 / 0 bộ</span>
                            <span id="zaloStickerCountNotice" class="text-primary fw-semibold">0 sticker con đã cắt</span>
                        </div>
                    </div>
                </div>

                <!-- Thanh Tìm Kiếm & Lọc Bộ Sticker Zalo -->
                <div class="row g-2 mb-3">
                    <div class="col-md-7">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" id="zaloSearchInput" class="form-control border-start-0" placeholder="Tìm theo tên bộ sticker (ví dụ: Pepero, Hổ Béo, Mèo, Gấu...)" oninput="filterZaloPacks()">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <select id="zaloFilterSelect" class="form-select form-select-sm" onchange="filterZaloPacks()">
                            <option value="all">Tất cả các bộ sticker</option>
                            <option value="unsynced" selected>Chưa đồng bộ (Cần cắt)</option>
                            <option value="synced">Đã đồng bộ vào hệ thống</option>
                        </select>
                    </div>
                </div>

                <!-- Lưới hiển thị các bộ Sticker Zalo -->
                <div id="zaloPacksLoading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-muted small">Đang kết nối API Zalo Store và tải danh mục 214 bộ sticker...</div>
                </div>

                <div id="zaloPacksGrid" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3" style="display: none;">
                    <!-- Rendered by JS -->
                </div>

                <div id="zaloPacksEmpty" class="text-center py-5" style="display: none;">
                    <i class="fa-solid fa-box-open fa-3x text-muted mb-2"></i>
                    <h6 class="fw-bold">Không tìm thấy bộ sticker phù hợp với tìm kiếm</h6>
                </div>
            </div>

            <div class="modal-footer bg-light px-4 py-2 border-top d-flex justify-content-between">
                <div class="small text-muted">
                    <i class="fa-solid fa-circle-info text-info me-1"></i> Mỗi bộ sticker Zalo sẽ được cắt thành đúng 9 ô vuông kích thước ~146x146px bảo toàn độ trong suốt.
                </div>
                <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary" data-bs-dismiss="modal" onclick="onCloseZaloModal()">Đóng lại</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Chuyển đổi nguồn ảnh Create Modal
function toggleCreateSource(type) {
    const fileWrapper = document.getElementById('createFileInputWrapper');
    const urlWrapper = document.getElementById('createUrlInputWrapper');
    const btnFile = document.getElementById('btnTabCreateFile');
    const btnUrl = document.getElementById('btnTabCreateUrl');

    if (type === 'file') {
        fileWrapper.style.display = 'block';
        urlWrapper.style.display = 'none';
        btnFile.classList.add('active');
        btnUrl.classList.remove('active');
    } else {
        fileWrapper.style.display = 'none';
        urlWrapper.style.display = 'block';
        btnFile.classList.remove('active');
        btnUrl.classList.add('active');
    }
}

// Chuyển đổi nguồn ảnh Edit Modal
function toggleEditSource(type) {
    const fileWrapper = document.getElementById('editFileInputWrapper');
    const urlWrapper = document.getElementById('editUrlInputWrapper');
    const btnFile = document.getElementById('btnTabEditFile');
    const btnUrl = document.getElementById('btnTabEditUrl');

    if (type === 'file') {
        fileWrapper.style.display = 'block';
        urlWrapper.style.display = 'none';
        btnFile.classList.add('active');
        btnUrl.classList.remove('active');
    } else {
        fileWrapper.style.display = 'none';
        urlWrapper.style.display = 'block';
        btnFile.classList.remove('active');
        btnUrl.classList.add('active');
    }
}

// Xem trước ảnh upload từ máy tính
function previewLocalImage(input, previewImgId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(previewImgId).src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Cập nhật preview khi gõ URL
function updateCreateUrlPreview(url) {
    if (url && url.trim().length > 0) {
        document.getElementById('createPreviewImg').src = url.trim();
    }
}
function updateEditUrlPreview(url) {
    if (url && url.trim().length > 0) {
        document.getElementById('editPreviewImg').src = url.trim();
    }
}

// Cập nhật code & category preview
function updateCreatePreview() {
    const code = document.getElementById('createCodeInput').value || 'sticker';
    document.getElementById('createPreviewCodeBadge').innerText = ':' + code + ':';
}
function setCreateCategory(cat) {
    document.getElementById('createCategoryInput').value = cat;
    document.getElementById('createPreviewCategoryBadge').innerText = cat;
}
function updateCreateCategoryPreview(val) {
    document.getElementById('createPreviewCategoryBadge').innerText = val || 'category';
}
function updateCreateActiveBadge(isActive) {
    const badge = document.getElementById('createPreviewActiveBadge');
    if (isActive) {
        badge.className = 'badge bg-success-subtle text-success border border-success-subtle rounded-pill position-absolute top-0 end-0 m-2';
        badge.innerText = 'Hoạt động';
    } else {
        badge.className = 'badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill position-absolute top-0 end-0 m-2';
        badge.innerText = 'Tạm ẩn';
    }
}

// Edit Modal Handlers
function openEditStickerModal(sticker) {
    const form = document.getElementById('editStickerForm');
    form.action = `/admin/reels/stickers/${sticker.id}`;
    document.getElementById('editStickerCode').value = sticker.code;
    document.getElementById('editStickerName').value = sticker.name;
    document.getElementById('editStickerUrl').value = sticker.asset_url;
    document.getElementById('editStickerCategory').value = sticker.category;
    document.getElementById('editStickerSort').value = sticker.sort_order || 0;
    document.getElementById('editStickerActive').checked = !!sticker.is_active;

    // Reset về tab URL hiện tại
    toggleEditSource('url');

    // Cập nhật Live Preview
    let fullUrl = sticker.asset_url;
    if (!fullUrl.startsWith('http')) {
        fullUrl = fullUrl.startsWith('/') ? fullUrl : '/' + fullUrl;
    }
    document.getElementById('editPreviewImg').src = fullUrl;
    document.getElementById('editPreviewCodeBadge').innerText = ':' + sticker.code + ':';
    document.getElementById('editPreviewCategoryBadge').innerText = sticker.category;
    updateEditActiveBadge(!!sticker.is_active);

    const modal = new bootstrap.Modal(document.getElementById('editStickerModal'));
    modal.show();
}

function updateEditPreviewCode(code) {
    document.getElementById('editPreviewCodeBadge').innerText = ':' + (code || 'code') + ':';
}
function setEditCategory(cat) {
    document.getElementById('editStickerCategory').value = cat;
    document.getElementById('editPreviewCategoryBadge').innerText = cat;
}
function updateEditPreviewCategory(val) {
    document.getElementById('editPreviewCategoryBadge').innerText = val || 'category';
}
function updateEditActiveBadge(isActive) {
    const badge = document.getElementById('editPreviewActiveBadge');
    if (isActive) {
        badge.className = 'badge bg-success-subtle text-success border border-success-subtle rounded-pill position-absolute top-0 end-0 m-2';
        badge.innerText = 'Hoạt động';
    } else {
        badge.className = 'badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill position-absolute top-0 end-0 m-2';
        badge.innerText = 'Tạm ẩn';
    }
}

/* ======================================================== */
/* JS LOGIC: ĐỒNG BỘ & CẮT MA TRẬN 3x3 TỪ ZALO STORE TOOL   */
/* ======================================================== */
let allZaloPacks = [];
let isAutoSyncRunning = false;
let shouldPauseAutoSync = false;
let totalCutStickersCount = 0;
let hasNewStickersSynced = false;

function initZaloSyncModal() {
    if (allZaloPacks.length === 0) {
        loadZaloPacks(false);
    }
}

function loadZaloPacks(forceRefresh = false) {
    const loading = document.getElementById('zaloPacksLoading');
    const grid = document.getElementById('zaloPacksGrid');
    const empty = document.getElementById('zaloPacksEmpty');
    const badge = document.getElementById('zaloTotalPacksBadge');

    loading.style.display = 'block';
    grid.style.display = 'none';
    empty.style.display = 'none';
    badge.innerText = 'Đang tải...';

    const url = `/admin/reels/stickers/zalo/packs${forceRefresh ? '?refresh=1' : ''}`;

    fetch(url, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        loading.style.display = 'none';
        if (data.success && data.packs) {
            allZaloPacks = data.packs;
            badge.innerText = `${data.total} bộ sticker`;
            updateSyncProgressUI();
            filterZaloPacks();
        } else {
            empty.style.display = 'block';
            empty.innerHTML = `<i class="fa-solid fa-triangle-exclamation text-danger fa-2x mb-2"></i><h6 class="text-danger">${data.message || 'Không thể lấy dữ liệu Zalo'}</h6>`;
        }
    })
    .catch(err => {
        loading.style.display = 'none';
        empty.style.display = 'block';
        empty.innerHTML = `<i class="fa-solid fa-triangle-exclamation text-danger fa-2x mb-2"></i><h6 class="text-danger">Lỗi kết nối Zalo Store: ${err.message}</h6>`;
    });
}

function filterZaloPacks() {
    const search = (document.getElementById('zaloSearchInput').value || '').trim().toLowerCase();
    const filter = document.getElementById('zaloFilterSelect').value;

    const filtered = allZaloPacks.filter(p => {
        const matchSearch = p.name.toLowerCase().includes(search) || p.slug.includes(search);
        let matchFilter = true;
        if (filter === 'synced') matchFilter = !!p.is_synced;
        if (filter === 'unsynced') matchFilter = !p.is_synced;
        return matchSearch && matchFilter;
    });

    renderZaloPacks(filtered);
}

function renderZaloPacks(packs) {
    const grid = document.getElementById('zaloPacksGrid');
    const empty = document.getElementById('zaloPacksEmpty');

    if (!packs || packs.length === 0) {
        grid.style.display = 'none';
        empty.style.display = 'block';
        return;
    }

    empty.style.display = 'none';
    grid.style.display = 'flex';

    grid.innerHTML = packs.map(pack => `
        <div class="col" id="zalo-card-${pack.id}">
            <div class="card h-100 border shadow-sm rounded-3 p-2 text-center position-relative">
                <div class="position-absolute top-0 end-0 m-2 z-1">
                    ${pack.is_synced 
                        ? `<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 10px;"><i class="fa-solid fa-check me-1"></i>Đã cắt 9</span>` 
                        : `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill" style="font-size: 10px;">Chưa cắt</span>`
                    }
                </div>

                <div class="d-flex justify-content-center align-items-center mb-2 mt-2 bg-light rounded-2 p-1 border" style="height: 110px;">
                    <img src="${pack.thumbImg}" alt="${pack.name}" 
                         style="max-height: 100px; max-width: 100px; object-fit: contain;" 
                         loading="lazy"
                         onerror="this.onerror=null; this.src='${pack.iconUrl || 'https://placehold.co/100x100?text=Pack'}';">
                </div>

                <h6 class="fw-bold mb-1 text-truncate" title="${pack.name}" style="font-size: 13px;">${pack.name}</h6>
                <div class="small text-muted mb-2 text-truncate" style="font-size: 11px;">
                    <i class="fa-solid fa-user-pen me-1"></i>${pack.source || 'Zalo'}
                </div>

                <div class="mt-auto pt-2 border-top">
                    ${pack.is_synced
                        ? `<button type="button" class="btn btn-sm btn-outline-success w-100 py-1" onclick="syncSingleZaloPack('${pack.id}', this)" title="Cắt lại nếu muốn làm mới">
                               <i class="fa-solid fa-rotate me-1"></i> Cắt lại 9 Sticker
                           </button>`
                        : `<button type="button" class="btn btn-sm btn-primary w-100 py-1" onclick="syncSingleZaloPack('${pack.id}', this)">
                               <i class="fa-solid fa-scissors me-1"></i> Tách 9 Sticker
                           </button>`
                    }
                </div>
            </div>
        </div>
    `).join('');
}

function updateSyncProgressUI() {
    const syncedCount = allZaloPacks.filter(p => p.is_synced).length;
    const total = allZaloPacks.length;
    const pct = total > 0 ? Math.round((syncedCount / total) * 100) : 0;

    const bar = document.getElementById('zaloProgressBar');
    bar.style.width = pct + '%';
    bar.innerText = pct + '%';

    document.getElementById('zaloProgressCounter').innerText = `Đã hoàn thành: ${syncedCount} / ${total} bộ`;
    document.getElementById('zaloStickerCountNotice').innerText = `${totalCutStickersCount} sticker con đã cắt`;
}

// Đồng bộ lẻ 1 bộ sticker Zalo
function syncSingleZaloPack(packId, btn) {
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Đang cắt...`;

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    const formData = new FormData();
    formData.append('pack_id', packId);

    return fetch('/admin/reels/stickers/zalo/sync-pack', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            hasNewStickersSynced = true;
            totalCutStickersCount += (data.created_count || 9);

            // Cập nhật pack trong state
            const target = allZaloPacks.find(p => p.id === packId);
            if (target) {
                target.is_synced = true;
            }

            btn.className = 'btn btn-sm btn-outline-success w-100 py-1';
            btn.innerHTML = `<i class="fa-solid fa-check me-1"></i> Đã xong 9 ô!`;

            // Cập nhật card badge
            const card = document.getElementById(`zalo-card-${packId}`);
            if (card) {
                const badgeWrap = card.querySelector('.position-absolute.top-0.end-0');
                if (badgeWrap) {
                    badgeWrap.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 10px;"><i class="fa-solid fa-check me-1"></i>Đã cắt 9</span>`;
                }
            }

            updateSyncProgressUI();
            return true;
        } else {
            btn.innerHTML = originalHtml;
            alert(data.message || 'Lỗi khi cắt sticker bộ này.');
            return false;
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        alert('Lỗi kết nối máy chủ: ' + err.message);
        return false;
    });
}

// Bắt đầu chạy tự động đồng bộ tuần tự toàn bộ kho Zalo
async function startAutoSyncZalo() {
    const unsyncedPacks = allZaloPacks.filter(p => !p.is_synced);
    if (unsyncedPacks.length === 0) {
        alert('Tuyệt vời! Toàn bộ 214 bộ sticker trên Zalo Store đã được đồng bộ vào hệ thống.');
        return;
    }

    isAutoSyncRunning = true;
    shouldPauseAutoSync = false;

    document.getElementById('btnStartAutoSync').style.display = 'none';
    document.getElementById('btnPauseAutoSync').style.display = 'inline-block';

    const statusEl = document.getElementById('zaloProgressStatus');

    for (let i = 0; i < unsyncedPacks.length; i++) {
        if (shouldPauseAutoSync) {
            statusEl.innerText = `Đã tạm dừng tiến trình tại bộ: ${unsyncedPacks[i].name}`;
            break;
        }

        const pack = unsyncedPacks[i];
        statusEl.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1 text-primary"></i> Đang tải và cắt bộ: <strong>${pack.name}</strong> (${i + 1}/${unsyncedPacks.length})...`;

        // Tìm nút bấm của card để tạo animation
        const card = document.getElementById(`zalo-card-${pack.id}`);
        const btn = card ? card.querySelector('button') : null;

        if (btn) {
            await syncSingleZaloPack(pack.id, btn);
        } else {
            // Trường hợp đang ở filter khác mà không có element DOM
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            const formData = new FormData();
            formData.append('pack_id', pack.id);
            try {
                const res = await fetch('/admin/reels/stickers/zalo/sync-pack', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    hasNewStickersSynced = true;
                    totalCutStickersCount += (data.created_count || 9);
                    pack.is_synced = true;
                    updateSyncProgressUI();
                }
            } catch (e) {
                console.error(e);
            }
        }

        // Tạm nghỉ 100ms giữa các request để mượt mà
        await new Promise(r => setTimeout(r, 100));
    }

    isAutoSyncRunning = false;
    document.getElementById('btnStartAutoSync').style.display = 'inline-block';
    document.getElementById('btnPauseAutoSync').style.display = 'none';

    if (!shouldPauseAutoSync) {
        statusEl.innerHTML = `<i class="fa-solid fa-circle-check text-success me-1"></i> <strong>Hoàn tất!</strong> Đã đồng bộ toàn bộ kho sticker Zalo vào hệ thống.`;
    }
}

function pauseAutoSyncZalo() {
    shouldPauseAutoSync = true;
    const statusEl = document.getElementById('zaloProgressStatus');
    statusEl.innerText = 'Đang dừng sau bộ hiện tại...';
    document.getElementById('btnPauseAutoSync').disabled = true;
    setTimeout(() => {
        document.getElementById('btnPauseAutoSync').disabled = false;
        document.getElementById('btnStartAutoSync').style.display = 'inline-block';
        document.getElementById('btnPauseAutoSync').style.display = 'none';
    }, 800);
}

function onCloseZaloModal() {
    if (isAutoSyncRunning) {
        pauseAutoSyncZalo();
    }
    if (hasNewStickersSynced) {
        // Tự động tải lại trang để hiển thị số lượng và sticker mới được thêm vào
        window.location.reload();
    }
}
</script>
@endpush
