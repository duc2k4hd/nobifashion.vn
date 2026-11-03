@extends('admins.layouts.master')

@section('title', 'Quản lý Cloudflare R2 Storage & Import Video')

@push('styles')
<link rel="stylesheet" href="{{ asset('admins/css/reels_admin.css') }}?v={{ time() }}">
<style>
    .nobifashion_r2_kpi_grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }
    @media (max-width: 1200px) {
        .nobifashion_r2_kpi_grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 640px) {
        .nobifashion_r2_kpi_grid {
            grid-template-columns: 1fr;
        }
    }
    .nobifashion_r2_preview_btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        color: #0f172a;
        border: 1px solid #e2e8f0;
        transition: all 0.15s ease;
    }
    .nobifashion_r2_preview_btn:hover {
        background: #0f172a;
        color: #ffffff;
        transform: scale(1.08);
    }
    .nobifashion_r2_key_badge {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 11px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #64748b;
        padding: 2px 6px;
        border-radius: 4px;
        max-width: 100%;
        display: inline-block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }
    .nobifashion_r2_table {
        table-layout: fixed;
        width: 100%;
        margin-bottom: 0;
    }
    .nobifashion_r2_table th,
    .nobifashion_r2_table td {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
        padding: 9px 10px;
    }
</style>
@endpush

@section('content')
<div class="nobifashion_admin_reels_container">
    <!-- Header -->
    <div class="nobifashion_admin_reels_header mb-3">
        <div>
            <h1 class="nobifashion_admin_reels_title" style="font-size: 20px;">
                <i class="fa-brands fa-cloudflare text-warning"></i> Quản lý Cloudflare R2 Storage
            </h1>
            <p class="nobifashion_admin_reels_subtitle mb-0">
                Quản lý kho tệp video từ xa, phân trang tối ưu theo luồng S3 Token (15 video/trang) giúp tải siêu tốc trên mọi thiết bị.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline py-1 px-3" data-bs-toggle="modal" data-bs-target="#uploadR2Modal">
                <i class="fa-solid fa-cloud-arrow-up"></i> Tải lên R2
            </button>
            <form action="{{ route('admin.reels.cloudflare.sync-all') }}" method="POST" onsubmit="return confirm('Hệ thống sẽ quét bucket Cloudflare R2 và tự động nhập tất cả video mới chưa có vào Reels. Bạn có muốn tiếp tục?');">
                @csrf
                <input type="hidden" name="prefix" value="{{ $prefix }}">
                <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary py-1 px-3">
                    <i class="fa-solid fa-rotate"></i> Tự động đồng bộ
                </button>
            </form>
        </div>
    </div>

    <!-- Subnav Tabs -->
    @include('admins.reels.partials.subnav')

    <!-- KPI Metric Cards Grid (Vừa khít 1 màn hình) -->
    <div class="nobifashion_r2_kpi_grid">
        <div class="nobifashion_admin_reels_kpi_card p-3">
            <div class="nobifashion_admin_reels_kpi_header mb-2">
                <span class="nobifashion_admin_reels_kpi_label" style="font-size: 11px;">BUCKET R2</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #f59e0b; background: #fef3c7; width: 30px; height: 30px;">
                    <i class="fa-solid fa-box-archive" style="font-size: 13px;"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value text-truncate" style="font-size: 16px;" title="{{ $metrics['bucket_name'] ?? 'nobifashion-reels' }}">
                {{ $metrics['bucket_name'] ?? 'nobifashion-reels' }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer text-truncate mt-1" style="font-size: 11px;" title="{{ $metrics['public_url'] ?? '' }}">
                {{ $metrics['public_url'] ?? 'r2.dev' }}
            </div>
        </div>

        <div class="nobifashion_admin_reels_kpi_card p-3">
            <div class="nobifashion_admin_reels_kpi_header mb-2">
                <span class="nobifashion_admin_reels_kpi_label" style="font-size: 11px;">TỔNG TỆP TRÊN R2</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #3b82f6; background: #eff6ff; width: 30px; height: 30px;">
                    <i class="fa-solid fa-film" style="font-size: 13px;"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value" style="font-size: 18px;">
                {{ number_format($metrics['object_count'] ?? 0) }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer mt-1" style="font-size: 11px;">
                Dung lượng: <strong class="text-dark">{{ $metrics['payload_formatted'] ?? '0 MB' }}</strong>
            </div>
        </div>

        <div class="nobifashion_admin_reels_kpi_card p-3">
            <div class="nobifashion_admin_reels_kpi_header mb-2">
                <span class="nobifashion_admin_reels_kpi_label" style="font-size: 11px;">TRANG HIỆN TẠI (TRANG {{ $page }})</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #10b981; background: #ecfdf5; width: 30px; height: 30px;">
                    <i class="fa-solid fa-circle-check" style="font-size: 13px;"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value text-success" style="font-size: 18px;">
                {{ $pageImportedCount }} / {{ count($items) }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer mt-1" style="font-size: 11px;">
                Đã có trên web
            </div>
        </div>

        <div class="nobifashion_admin_reels_kpi_card p-3">
            <div class="nobifashion_admin_reels_kpi_header mb-2">
                <span class="nobifashion_admin_reels_kpi_label" style="font-size: 11px;">CHƯA NHẬP (TRANG NÀY)</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #ef4444; background: #fef2f2; width: 30px; height: 30px;">
                    <i class="fa-solid fa-cloud-arrow-down" style="font-size: 13px;"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value text-danger" style="font-size: 18px;">
                {{ $pageNotImportedCount }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer mt-1" style="font-size: 11px;">
                Sẵn sàng nhập dữ liệu
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="nobifashion_admin_reels_card overflow-hidden">
        <!-- Filter Bar -->
        <form action="{{ route('admin.reels.cloudflare.index') }}" method="GET" class="nobifashion_admin_reels_filter_bar p-3 border-bottom m-0" style="gap: 8px;">
            <div style="flex: 2; min-width: 140px;">
                <input type="text" name="search" class="nobifashion_admin_reels_input w-100" style="padding: 6px 10px; font-size: 13px;" placeholder="Tìm theo tên file..." value="{{ request('search') }}">
            </div>

            <div style="flex: 1; min-width: 120px;">
                <input type="text" name="prefix" class="nobifashion_admin_reels_input w-100" style="padding: 6px 10px; font-size: 13px;" placeholder="Thư mục: reels/" value="{{ $prefix }}">
            </div>

            <div style="flex: 1; min-width: 130px;">
                <select name="status" class="nobifashion_admin_reels_select w-100" style="padding: 6px 10px; font-size: 13px;">
                    <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Tất cả trạng thái</option>
                    <option value="not_imported" {{ request('status') === 'not_imported' ? 'selected' : '' }}>Chưa nhập web</option>
                    <option value="imported" {{ request('status') === 'imported' ? 'selected' : '' }}>Đã có trên web</option>
                </select>
            </div>

            <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary" style="padding: 6px 12px; font-size: 13px;">
                <i class="fa-solid fa-filter"></i> Lọc
            </button>

            @if(request()->hasAny(['search', 'prefix', 'status']) && (request('prefix') !== 'reels/' || request('search') || request('status') !== 'all'))
                <a href="{{ route('admin.reels.cloudflare.index') }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline" style="padding: 6px 10px;" title="Đặt lại bộ lọc">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            @endif
        </form>

        <!-- Bulk Action Toolbar & Table Form -->
        <form id="bulkImportForm" action="{{ route('admin.reels.cloudflare.import-selected') }}" method="POST">
            @csrf
            <div class="px-3 py-2 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-dark small">
                        Hiển thị <strong>{{ count($items) }}</strong> tệp · <strong>Trang {{ $page }}</strong>
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select name="category" class="nobifashion_admin_reels_select" style="padding: 4px 8px; font-size: 12px;">
                        <option value="trending" selected>Danh mục: Xu hướng</option>
                        <option value="new">Danh mục: Hàng mới về</option>
                        <option value="highlight">Danh mục: Nổi bật</option>
                    </select>
                    <select name="brand_id" class="nobifashion_admin_reels_select" style="padding: 4px 8px; font-size: 12px;">
                        <option value="">Hãng: Tự động phân tích</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" id="btnBulkImport" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary" style="background: #10b981; border-color: #10b981; padding: 4px 10px; font-size: 12px;" disabled>
                        <i class="fa-solid fa-file-import"></i> Nhập <span id="selectedCount">0</span> đã chọn
                    </button>
                </div>
            </div>

            <!-- Table vừa khít 100% màn hình, không tràn ngang -->
            <div class="table-responsive m-0">
                <table class="table table-hover align-middle nobifashion_r2_table">
                    <thead class="table-light text-muted small text-uppercase" style="font-size: 11.5px;">
                        <tr>
                            <th style="width: 38px;" class="text-center">
                                <input type="checkbox" id="checkAll" class="form-check-input" style="cursor: pointer;">
                            </th>
                            <th style="width: 48px;" class="text-center">Xem</th>
                            <th>Tên tệp & Đường dẫn R2</th>
                            <th style="width: 90px;">Dung lượng</th>
                            <th style="width: 110px;">Ngày cập nhật</th>
                            <th style="width: 130px;" class="text-center">Trạng thái</th>
                            <th style="width: 140px;" class="text-end pe-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td class="text-center">
                                    @if(!$item['is_imported'])
                                        <input type="checkbox" name="keys[]" value="{{ $item['key'] }}" class="form-check-input item-checkbox" style="cursor: pointer;">
                                    @else
                                        <i class="fa-solid fa-circle-check text-success" title="Đã có trên web"></i>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item['is_video'])
                                        <button type="button" class="nobifashion_r2_preview_btn" 
                                            onclick="openVideoModal('{{ $item['public_url'] }}', '{{ addslashes($item['file_name']) }}')" title="Xem thử video">
                                            <i class="fa-solid fa-play" style="font-size: 11px; margin-left: 2px;"></i>
                                        </button>
                                    @else
                                        <i class="fa-solid fa-file text-muted"></i>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark text-truncate" title="{{ $item['file_name'] }}" style="font-size: 13px;">
                                        {{ $item['file_name'] }}
                                    </div>
                                    <div class="d-flex align-items-center gap-1 mt-1 text-truncate">
                                        <span class="nobifashion_r2_key_badge" title="{{ $item['key'] }}">
                                            {{ $item['key'] }}
                                        </span>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-muted flex-shrink-0" onclick="copyToClipboard('{{ $item['public_url'] }}')" title="Sao chép link CDN">
                                            <i class="fa-regular fa-copy" style="font-size: 11px;"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border" style="font-size: 11px;">{{ $item['size_formatted'] }}</span>
                                    <span class="text-muted small ms-1">{{ strtoupper($item['extension']) }}</span>
                                </td>
                                <td>
                                    <div class="text-dark small" style="font-size: 12px;">{{ date('d/m/Y', strtotime($item['last_modified'])) }}</div>
                                    <div class="text-muted" style="font-size: 11px;">{{ date('H:i', strtotime($item['last_modified'])) }}</div>
                                </td>
                                <td class="text-center">
                                    @if($item['is_imported'])
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="border-radius: 4px; font-size: 11.5px;">
                                            <i class="fa-solid fa-check me-1"></i> Đã có trên Web
                                        </span>
                                        @if($item['reel'])
                                            <div class="text-muted mt-1" style="font-size: 11px;">#{{ $item['reel']->id }} · {{ number_format($item['reel']->view_count) }} xem</div>
                                        @endif
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1" style="border-radius: 4px; font-size: 11.5px;">
                                            <i class="fa-solid fa-clock me-1"></i> Chưa nhập
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-1">
                                        @if(!$item['is_imported'])
                                            <button type="button" class="btn btn-sm btn-success" style="border-radius: 6px; padding: 3px 8px; font-size: 12px;" onclick="importSingle('{{ addslashes($item['key']) }}')" title="Nhập video này vào Nobi Reels">
                                                <i class="fa-solid fa-plus me-1"></i> Nhập
                                            </button>
                                        @else
                                            @if($item['reel'])
                                                <a href="{{ url('/reel/' . $item['reel']->slug) }}" target="_blank" class="btn btn-sm btn-outline-info" style="border-radius: 6px; padding: 3px 7px; font-size: 11px;" title="Xem trên web">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                                </a>
                                                <a href="{{ route('admin.reels.videos.edit', $item['reel']->id) }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 6px; padding: 3px 7px; font-size: 11px;" title="Sửa video">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                            @endif
                                        @endif
                                        <button type="button" class="btn btn-sm btn-outline-danger" style="border-radius: 6px; padding: 3px 7px; font-size: 11px;" onclick="deleteR2File('{{ addslashes($item['key']) }}')" title="Xóa tệp trên Cloudflare R2">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fa-brands fa-cloudflare fa-2x mb-2 text-secondary opacity-50"></i>
                                    <div class="small fw-semibold">Không tìm thấy tệp video nào trong thư mục này</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar (Vừa vặn trên 1 màn hình) -->
            <div class="px-3 py-2 bg-white border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="small text-muted" style="font-size: 12px;">
                    Trang <strong>{{ $page }}</strong> · <strong>15 tệp/trang</strong> (Tải siêu tốc)
                </div>
                <div class="d-flex align-items-center gap-1">
                    @if($hasPrevPage)
                        <a href="{{ route('admin.reels.cloudflare.index', array_merge(request()->all(), ['page' => $page - 1])) }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 6px; padding: 3px 10px; font-size: 12px;">
                            <i class="fa-solid fa-arrow-left me-1"></i> Trang trước
                        </a>
                    @else
                        <button type="button" class="btn btn-sm btn-light border text-muted" style="border-radius: 6px; padding: 3px 10px; font-size: 12px;" disabled>
                            <i class="fa-solid fa-arrow-left me-1"></i> Trang trước
                        </button>
                    @endif

                    <span class="badge bg-dark text-white px-2 py-1" style="border-radius: 6px; font-size: 12px;">
                        {{ $page }}
                    </span>

                    @if($hasNextPage)
                        <a href="{{ route('admin.reels.cloudflare.index', array_merge(request()->all(), ['page' => $page + 1])) }}" class="btn btn-sm btn-primary" style="border-radius: 6px; padding: 3px 10px; font-size: 12px;">
                            Trang tiếp theo <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    @else
                        <button type="button" class="btn btn-sm btn-light border text-muted" style="border-radius: 6px; padding: 3px 10px; font-size: 12px;" disabled>
                            Trang tiếp theo <i class="fa-solid fa-arrow-right ms-1"></i>
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Xem trước Video (Live Preview Modal) -->
<div class="modal fade" id="videoPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content bg-dark text-white border-0 rounded-4 overflow-hidden shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-truncate text-white small" id="previewVideoTitle" style="max-width: 300px;">Xem trước video</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 text-center">
                <div class="position-relative bg-black rounded-3 overflow-hidden mx-auto" style="width: 100%; aspect-ratio: 9/16; max-height: 65vh;">
                    <video id="previewVideoPlayer" controls autoplay playsinline style="width: 100%; height: 100%; object-fit: contain;">
                        Trình duyệt không hỗ trợ phát video HTML5.
                    </video>
                </div>
                <div class="mt-2 text-start small text-muted text-break" id="previewVideoUrl" style="font-size: 11px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tải lên Video lên Cloudflare R2 -->
<div class="modal fade" id="uploadR2Modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ route('admin.reels.cloudflare.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-bottom py-2 px-3">
                    <h6 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i>Tải video lên Cloudflare R2
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label fw-semibold small mb-1">Chọn tệp Video (.mp4, .webm) <span class="text-danger">*</span></label>
                        <input type="file" name="video_file" class="form-control form-control-sm" accept="video/mp4,video/webm,video/quicktime" required>
                        <small class="text-muted" style="font-size: 11px;">Hỗ trợ tối đa 100MB, tỷ lệ 9:16 dọc.</small>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small mb-1">Thư mục trên R2</label>
                        <input type="text" name="folder" class="form-control form-control-sm" value="reels/coolmate">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small mb-1">Tiêu đề video (tùy chọn)</label>
                        <input type="text" name="title" class="form-control form-control-sm" placeholder="Tự sinh từ tên file nếu để trống">
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="auto_import" id="autoImportCheck" checked>
                        <label class="form-check-label fw-semibold small" for="autoImportCheck">
                            Tự động thêm vào Nobi Reels
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-2 px-3">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-upload me-1"></i> Tải lên
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form Xóa Tệp ẩn -->
<form id="deleteR2Form" action="{{ route('admin.reels.cloudflare.delete') }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
    <input type="hidden" name="key" id="deleteFileKey">
</form>

<!-- Form Import Đơn ẩn -->
<form id="importSingleForm" action="{{ route('admin.reels.cloudflare.import-selected') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="keys[]" id="singleImportKey">
    <input type="hidden" name="category" value="trending">
</form>

<script>
    // 1. Quản lý Checkbox chọn nhiều
    const checkAll = document.getElementById('checkAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const btnBulkImport = document.getElementById('btnBulkImport');
    const selectedCountSpan = document.getElementById('selectedCount');

    function updateSelectedState() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        const count = checked.length;
        if (selectedCountSpan) selectedCountSpan.textContent = count;
        if (btnBulkImport) btnBulkImport.disabled = count === 0;
    }

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            itemCheckboxes.forEach(cb => cb.checked = this.checked);
            updateSelectedState();
        });
    }

    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectedState);
    });

    // 2. Mở Modal phát video xem trước
    const videoModalEl = document.getElementById('videoPreviewModal');
    const videoPlayer = document.getElementById('previewVideoPlayer');
    const previewTitle = document.getElementById('previewVideoTitle');
    const previewUrl = document.getElementById('previewVideoUrl');

    function openVideoModal(url, title) {
        if (!videoPlayer) return;
        previewTitle.textContent = title;
        previewUrl.innerHTML = `<i class="fa-solid fa-link me-1"></i> <a href="${url}" target="_blank" class="text-info text-decoration-none">${url}</a>`;
        videoPlayer.src = url;
        videoPlayer.load();
        const bsModal = new bootstrap.Modal(videoModalEl);
        bsModal.show();
    }

    if (videoModalEl) {
        videoModalEl.addEventListener('hidden.bs.modal', function () {
            if (videoPlayer) {
                videoPlayer.pause();
                videoPlayer.src = '';
            }
        });
    }

    // 3. Sao chép liên kết vào clipboard
    function copyToClipboard(text) {
        if (!navigator.clipboard) {
            const el = document.createElement('textarea');
            el.value = text;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            alert('Đã sao chép liên kết CDN!');
            return;
        }
        navigator.clipboard.writeText(text).then(() => {
            alert('Đã sao chép liên kết CDN: ' + text);
        });
    }

    // 4. Nhập đơn lẻ 1 video
    function importSingle(key) {
        if (!confirm(`Bạn có muốn nhập video "${key}" vào hệ thống Nobi Reels?`)) return;
        document.getElementById('singleImportKey').value = key;
        document.getElementById('importSingleForm').submit();
    }

    // 5. Xóa tệp từ xa trên Cloudflare R2
    function deleteR2File(key) {
        if (!confirm(`CẢNH BÁO: Bạn có chắc chắn muốn xóa vĩnh viễn tệp "${key}" trên máy chủ Cloudflare R2? Hành động này không thể hoàn tác.`)) return;
        document.getElementById('deleteFileKey').value = key;
        document.getElementById('deleteR2Form').submit();
    }
</script>
@endsection
