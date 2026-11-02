@extends('admins.layouts.master')

@section('title', 'Quản lý kho Sticker bình luận Reels')

@section('content')
<div class="nobifashion_admin_reels_wrapper container-fluid px-3 py-3">
    <!-- Header -->
    <div class="nobifashion_admin_reels_page_header mb-3">
        <div>
            <h1 class="nobifashion_admin_reels_page_title">
                <i class="fa-solid fa-face-smile text-warning me-2"></i>Thư viện Sticker bình luận
            </h1>
            <div class="nobifashion_admin_reels_breadcrumb">
                <a href="{{ route('admin.reels.dashboard') }}">Reels</a>
                <span>/</span>
                <span>Sticker tương tác</span>
            </div>
        </div>
        <div class="nobifashion_admin_reels_page_actions">
            <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary" data-bs-toggle="modal" data-bs-target="#createStickerModal">
                <i class="fa-solid fa-plus"></i> Thêm Sticker mới
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
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
            <i class="fa-solid fa-circle-info me-2"></i>
            <div>{{ session('info') }}</div>
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

    <!-- Filter Bar -->
    <div class="nobifashion_admin_reels_card mb-3">
        <div class="nobifashion_admin_reels_card_body py-2">
            <form action="{{ route('admin.reels.stickers.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Tìm theo tên hoặc mã code..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select form-select-sm">
                        <option value="all">Tất cả danh mục nhóm</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary btn-sm">
                        <i class="fa-solid fa-filter me-1"></i> Lọc
                    </button>
                    @if(request()->hasAny(['search', 'category']))
                        <a href="{{ route('admin.reels.stickers.index') }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary btn-sm">
                            <i class="fa-solid fa-rotate-left"></i> Đặt lại
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Sticker Grid -->
    <div class="row g-3">
        @forelse($stickers as $sticker)
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="nobifashion_admin_reels_card h-100 text-center position-relative p-3">
                    <!-- Status Indicator Badge -->
                    <div class="position-absolute top-0 end-0 p-2">
                        @if($sticker->is_active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;">Hoạt động</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size: 10px;">Vô hiệu</span>
                        @endif
                    </div>

                    <!-- Sticker Image -->
                    <div class="mb-2 d-flex justify-content-center align-items-center" style="height: 100px;">
                        <img src="{{ $sticker->asset_url }}" alt="{{ $sticker->name }}" 
                             class="img-fluid" style="max-height: 90px; max-width: 90px; object-fit: contain;"
                             onerror="this.onerror=null; this.src='https://placehold.co/90x90?text=Sticker';">
                    </div>

                    <!-- Title & Code -->
                    <h6 class="fw-bold mb-1 text-truncate" title="{{ $sticker->name }}">{{ $sticker->name }}</h6>
                    <code class="d-block text-muted small mb-2">:{{ $sticker->code }}:</code>

                    <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                        <span class="badge bg-light text-dark border" style="font-size: 10px;">{{ $sticker->category }}</span>
                        <div class="btn-group btn-group-sm">
                            <form action="{{ route('admin.reels.stickers.toggle', $sticker->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-link p-0 text-secondary me-2" title="{{ $sticker->is_active ? 'Tắt sticker' : 'Bật sticker' }}">
                                    <i class="fa-solid {{ $sticker->is_active ? 'fa-toggle-on text-success fs-5' : 'fa-toggle-off fs-5' }}"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-link p-0 text-primary me-2" onclick="openEditStickerModal({{ json_encode($sticker) }})" title="Chỉnh sửa">
                                <i class="fa-solid fa-pencil"></i>
                            </button>
                            <form action="{{ route('admin.reels.stickers.destroy', $sticker->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa hoặc ẩn sticker này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link p-0 text-danger" title="Xóa">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="nobifashion_admin_reels_card text-center py-5">
                    <i class="fa-solid fa-face-meh fa-3x text-secondary mb-3"></i>
                    <h5>Không tìm thấy Sticker nào</h5>
                    <p class="text-muted small">Hãy thêm bộ sticker đầu tiên để người xem có thể tương tác bình luận sinh động!</p>
                    <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary mt-2" data-bs-toggle="modal" data-bs-target="#createStickerModal">
                        <i class="fa-solid fa-plus me-1"></i> Thêm Sticker ngay
                    </button>
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

<!-- Modal: Create Sticker -->
<div class="modal fade" id="createStickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.reels.stickers.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-plus text-primary me-2"></i>Thêm Sticker mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="nobifashion_admin_reels_form_label">Mã code nhận diện <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="nobifashion_admin_reels_form_control" required placeholder="Ví dụ: heart_fire, wow_face, fashion_love">
                    <small class="text-muted">Mã duy nhất, viết thường không dấu hoặc dấu gạch dưới.</small>
                </div>
                <div class="mb-3">
                    <label class="nobifashion_admin_reels_form_label">Tên hiển thị <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="nobifashion_admin_reels_form_control" required placeholder="Ví dụ: Tim lửa nồng nhiệt, Đẹp xuất sắc">
                </div>
                <div class="mb-3">
                    <label class="nobifashion_admin_reels_form_label">URL ảnh Sticker (.png, .webp, .svg, .gif) <span class="text-danger">*</span></label>
                    <input type="url" name="asset_url" class="nobifashion_admin_reels_form_control" required placeholder="https://domain.com/stickers/heart.png">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="nobifashion_admin_reels_form_label">Danh mục / Nhóm</label>
                        <input type="text" name="category" class="nobifashion_admin_reels_form_control" required placeholder="default, emotion, fashion" value="default">
                    </div>
                    <div class="col-md-6">
                        <label class="nobifashion_admin_reels_form_label">Thứ tự sắp xếp</label>
                        <input type="number" name="sort_order" class="nobifashion_admin_reels_form_control" value="0" min="0">
                    </div>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="createStickerActive" checked>
                    <label class="form-check-label" for="createStickerActive">Cho phép hiển thị trong khay bình luận ngay</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">Lưu Sticker</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Sticker -->
<div class="modal fade" id="editStickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editStickerForm" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-pencil text-primary me-2"></i>Chỉnh sửa thông tin Sticker</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="nobifashion_admin_reels_form_label">Mã code</label>
                    <input type="text" id="editStickerCode" name="code" class="nobifashion_admin_reels_form_control" required>
                </div>
                <div class="mb-3">
                    <label class="nobifashion_admin_reels_form_label">Tên hiển thị</label>
                    <input type="text" id="editStickerName" name="name" class="nobifashion_admin_reels_form_control" required>
                </div>
                <div class="mb-3">
                    <label class="nobifashion_admin_reels_form_label">URL ảnh Sticker</label>
                    <input type="url" id="editStickerUrl" name="asset_url" class="nobifashion_admin_reels_form_control" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="nobifashion_admin_reels_form_label">Danh mục / Nhóm</label>
                        <input type="text" id="editStickerCategory" name="category" class="nobifashion_admin_reels_form_control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="nobifashion_admin_reels_form_label">Thứ tự sắp xếp</label>
                        <input type="number" id="editStickerSort" name="sort_order" class="nobifashion_admin_reels_form_control" min="0">
                    </div>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editStickerActive">
                    <label class="form-check-label" for="editStickerActive">Đang kích hoạt</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">Cập nhật thay đổi</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openEditStickerModal(sticker) {
    const form = document.getElementById('editStickerForm');
    form.action = `/admin/reels/stickers/${sticker.id}`;
    document.getElementById('editStickerCode').value = sticker.code;
    document.getElementById('editStickerName').value = sticker.name;
    document.getElementById('editStickerUrl').value = sticker.asset_url;
    document.getElementById('editStickerCategory').value = sticker.category;
    document.getElementById('editStickerSort').value = sticker.sort_order || 0;
    document.getElementById('editStickerActive').checked = !!sticker.is_active;

    const modal = new bootstrap.Modal(document.getElementById('editStickerModal'));
    modal.show();
}
</script>
@endpush

