@extends('admins.layouts.master')

@section('title', 'Trung tâm nhập dữ liệu video hàng loạt (Reels Import Center)')

@push('styles')
<link rel="stylesheet" href="{{ asset('admins/css/reels_admin.css') }}?v={{ time() }}">
<style>
    .import_kpi_grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }
    @media (max-width: 1200px) {
        .import_kpi_grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 640px) {
        .import_kpi_grid {
            grid-template-columns: 1fr;
        }
    }
    .import_upload_box {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        background: #f8fafc;
        padding: 24px 16px;
        text-align: center;
        transition: all 0.2s ease;
        cursor: pointer;
        position: relative;
    }
    .import_upload_box:hover {
        border-color: #3b82f6;
        background: #eff6ff;
    }
    .import_upload_box input[type="file"] {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }
</style>
@endpush

@section('content')
<div class="nobifashion_admin_reels_container">
    <!-- Header -->
    <div class="nobifashion_admin_reels_header mb-3">
        <div>
            <h1 class="nobifashion_admin_reels_title" style="font-size: 20px;">
                <i class="fa-solid fa-file-import text-primary me-2"></i> Trung tâm nhập dữ liệu Video Reels (CSV Import)
            </h1>
            <p class="nobifashion_admin_reels_subtitle mb-0">
                Nhập hàng loạt video Reels từ file CSV/Excel UTF-8, tự động liên kết thương hiệu, sản phẩm và gắn hashtag.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.reels.import.sample-csv') }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                <i class="fa-solid fa-download me-1"></i> Tải file CSV mẫu (Excel UTF-8)
            </a>
        </div>
    </div>

    <!-- Subnav Tabs -->
    @include('admins.reels.partials.subnav')

    <!-- 4 THẺ KPI METRICS TỔNG QUAN TÁC VỤ IMPORT -->
    <div class="import_kpi_grid">
        <!-- 1. Tổng lượt import -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">TỔNG LƯỢT IMPORT</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #3b82f6; background: #eff6ff;">
                    <i class="fa-solid fa-list-check"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value">
                {{ number_format($stats['total_jobs'] ?? 0) }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span class="text-muted small">Tác vụ đã thực thi</span>
            </div>
        </div>

        <!-- 2. Nhập thành công -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">VIDEO THÊM THÀNH CÔNG</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #10b981; background: #ecfdf5;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value text-success">
                +{{ number_format($stats['total_success'] ?? 0) }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span class="text-success small fw-semibold">Đã lưu vào cơ sở dữ liệu</span>
            </div>
        </div>

        <!-- 3. Bỏ qua do trùng lặp -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">DÒNG BỎ QUA (TRÙNG LẶP)</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #64748b; background: #f1f5f9;">
                    <i class="fa-solid fa-forward"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value text-secondary">
                {{ number_format($stats['total_skipped'] ?? 0) }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span class="text-muted small">Trùng slug hoặc external ID</span>
            </div>
        </div>

        <!-- 4. Dòng lỗi phát sinh -->
        <div class="nobifashion_admin_reels_kpi_card">
            <div class="nobifashion_admin_reels_kpi_header">
                <span class="nobifashion_admin_reels_kpi_label">DÒNG LỖI PHÁT SINH</span>
                <div class="nobifashion_admin_reels_kpi_icon" style="color: #ef4444; background: #fef2f2;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <div class="nobifashion_admin_reels_kpi_value text-danger">
                {{ number_format($stats['total_failed'] ?? 0) }}
            </div>
            <div class="nobifashion_admin_reels_kpi_footer">
                <span class="text-muted small">Thiếu tiêu đề hoặc URL video</span>
            </div>
        </div>
    </div>

    <!-- KHUNG UPLOAD VÀ XEM TRƯỚC PREVIEW -->
    <div class="row g-3 mb-4">
        <!-- Cột Trái: Form Upload CSV -->
        <div class="col-lg-5">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i> Tải lên tệp CSV Reels
                    </h3>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    <form id="importForm" action="{{ route('admin.reels.import.process') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <!-- Dropzone Chọn Tệp -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted mb-2">Chọn tệp dữ liệu CSV / TXT <span class="text-danger">*</span></label>
                            <div class="import_upload_box">
                                <i class="fa-solid fa-file-csv fa-3x text-primary mb-2 d-block"></i>
                                <div class="fw-semibold text-dark" id="uploadFileName">Nhấp hoặc Kéo thả tệp CSV vào đây</div>
                                <div class="text-muted small mt-1">Hỗ trợ định dạng .csv, .txt (Tối đa 50MB, mã hóa UTF-8)</div>
                                <input type="file" id="csvFileInput" name="file" accept=".csv,text/csv,text/plain" required>
                            </div>
                            <div class="d-flex justify-content-end mt-1">
                                <a href="{{ route('admin.reels.import.sample-csv') }}" class="small text-decoration-none text-primary">
                                    <i class="fa-solid fa-circle-down me-1"></i> Tải file mẫu chuẩn Excel
                                </a>
                            </div>
                        </div>

                        <!-- Tùy Chọn Nhập -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-muted mb-1">Xử lý trùng lặp</label>
                                <select name="duplicate_action" class="form-select form-select-sm">
                                    <option value="skip" selected>Bỏ qua nếu đã tồn tại</option>
                                    <option value="update">Cập nhật đè dữ liệu mới</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-muted mb-1">Trạng thái xuất bản</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="published" selected>Xuất bản ngay</option>
                                    <option value="draft">Lưu dưới dạng nháp</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Danh mục mặc định (nếu dòng trống)</label>
                            <select name="category" class="form-select form-select-sm">
                                <option value="trending" selected>Xu hướng (Trending)</option>
                                <option value="new">Hàng mới về (New)</option>
                                <option value="highlight">Nổi bật (Highlight)</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" id="btnSubmitImport" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary w-100 py-2">
                                <i class="fa-solid fa-play me-1"></i> Bắt đầu xử lý nhập dữ liệu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Cột Phải: Xem Trước Dữ Liệu (Preview) -->
        <div class="col-lg-7">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header d-flex justify-content-between align-items-center">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-table-list text-info me-2"></i> Xem trước dữ liệu tệp (Preview 10 dòng)
                    </h3>
                    <span id="previewCountBadge" class="badge bg-light text-dark border d-none">0 dòng</span>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    <!-- Placeholder khi chưa chọn file -->
                    <div id="previewPlaceholder" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-file-csv fa-3x text-secondary mb-3 opacity-50"></i>
                        <p class="mb-0">Chọn tệp CSV ở khung bên trái để hệ thống tự động đọc cấu trúc và hiển thị mẫu 10 dòng dữ liệu trước khi bấm thực thi.</p>
                    </div>

                    <!-- Loading Spinner -->
                    <div id="previewLoading" class="text-center py-5 d-none">
                        <i class="fa-solid fa-spinner fa-spin fa-2x text-primary mb-2"></i>
                        <p class="text-muted">Đang đọc và phân tích cấu trúc tệp CSV...</p>
                    </div>

                    <!-- Bảng Preview Data -->
                    <div id="previewTableContainer" class="table-responsive d-none" style="max-height: 320px;">
                        <table class="nobifashion_admin_reels_table">
                            <thead class="sticky-top bg-light">
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Tiêu đề video</th>
                                    <th>Đường dẫn video</th>
                                    <th>Danh mục</th>
                                    <th>Hashtags</th>
                                </tr>
                            </thead>
                            <tbody id="previewTbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BẢNG LỊCH SỬ CÁC TÁC VỤ NHẬP CSV GẦN ĐÂY -->
    <div class="nobifashion_admin_reels_card">
        <div class="nobifashion_admin_reels_card_header">
            <h3 class="nobifashion_admin_reels_card_title">
                <i class="fa-solid fa-clock-rotate-left text-muted me-2"></i> Lịch sử các tác vụ nhập dữ liệu gần đây
            </h3>
        </div>
        <div class="nobifashion_admin_reels_card_body p-0">
            <div class="table-responsive">
                <table class="nobifashion_admin_reels_table">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Job ID</th>
                            <th>Tên tệp CSV</th>
                            <th>Người thực hiện</th>
                            <th style="text-align: center;">Tổng dòng</th>
                            <th style="text-align: center;">Thành công</th>
                            <th style="text-align: center;">Bỏ qua</th>
                            <th style="text-align: center;">Lỗi</th>
                            <th style="text-align: center;">Trạng thái</th>
                            <th style="text-align: right;">Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jobs as $job)
                            <tr>
                                <td><span class="text-muted small">#{{ $job->id }}</span></td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $job->file_name }}</span>
                                    @if($job->file_path)
                                        <div class="text-muted small" style="font-size: 11px;">{{ $job->file_path }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="small text-dark">{{ $job->creator ? $job->creator->fullname : 'Admin System' }}</span>
                                </td>
                                <td style="text-align: center;">
                                    <strong>{{ number_format($job->total_rows ?? 0) }}</strong>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">+{{ number_format($job->successful_rows ?? 0) }}</span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge bg-light text-secondary border px-2 py-1">{{ number_format($job->skipped_rows ?? 0) }}</span>
                                </td>
                                <td style="text-align: center;">
                                    @if(($job->failed_rows ?? 0) > 0)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">{{ number_format($job->failed_rows) }}</span>
                                        @if(!empty($job->errors_log))
                                            <button type="button" class="btn btn-sm btn-link p-0 text-danger ms-1" title="Xem chi tiết lỗi" onclick="alert({{ json_encode(json_encode($job->errors_log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }})">
                                                <i class="fa-solid fa-circle-question"></i>
                                            </button>
                                        @endif
                                    @else
                                        <span class="badge bg-light text-muted border px-2 py-1">0</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if($job->status === 'completed')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Hoàn thành</span>
                                    @elseif($job->status === 'failed')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Thất bại</span>
                                    @elseif($job->status === 'processing')
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Đang chạy...</span>
                                    @else
                                        <span class="badge bg-secondary px-2 py-1">{{ $job->status }}</span>
                                    @endif
                                </td>
                                <td style="text-align: right;" class="small text-muted">
                                    {{ $job->created_at ? $job->created_at->format('d/m/Y H:i') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fa-2x mb-2 d-block text-secondary opacity-50"></i>
                                    Chưa có tác vụ nhập CSV nào được ghi nhận.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($jobs->hasPages())
                <div class="p-3 border-top">
                    {{ $jobs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const fileInput = document.getElementById('csvFileInput');
if (fileInput) {
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Cập nhật tên tệp trên hộp upload
        const uploadFileName = document.getElementById('uploadFileName');
        if (uploadFileName) {
            uploadFileName.innerHTML = `<i class="fa-solid fa-circle-check text-success me-1"></i> ${file.name}`;
        }

        const placeholder = document.getElementById('previewPlaceholder');
        const loading = document.getElementById('previewLoading');
        const container = document.getElementById('previewTableContainer');
        const tbody = document.getElementById('previewTbody');
        const badge = document.getElementById('previewCountBadge');

        placeholder.classList.add('d-none');
        loading.classList.remove('d-none');
        container.classList.add('d-none');

        const formData = new FormData();
        formData.append('file', file);

        fetch("{{ route('admin.reels.import.preview') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            loading.classList.add('d-none');
            const dataObj = res.data || {};
            const rows = dataObj.rows || dataObj.preview_rows || [];
            const total = dataObj.total_rows || dataObj.total_preview || rows.length;

            if (res.success && rows.length > 0) {
                container.classList.remove('d-none');
                badge.classList.remove('d-none');
                badge.textContent = `Xem trước ${rows.length} / ~${total} dòng`;

                let rowsHtml = '';
                rows.forEach((row, i) => {
                    const videoLink = row.video_url || row.url_video || row.duong_dan_video || '';
                    rowsHtml += `
                        <tr>
                            <td>${i + 1}</td>
                            <td class="fw-semibold text-dark text-truncate" style="max-width: 180px;">${row.title || row.tieu_de || '—'}</td>
                            <td class="text-truncate" style="max-width: 150px;">
                                ${videoLink ? `<a href="${videoLink}" target="_blank" class="text-decoration-none small">${videoLink}</a>` : '—'}
                            </td>
                            <td><span class="badge bg-light text-dark border">${row.category || 'trending'}</span></td>
                            <td class="text-muted small text-truncate" style="max-width: 140px;">${row.hashtags || '—'}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = rowsHtml;
            } else {
                placeholder.classList.remove('d-none');
                alert(res.message || 'Không thể đọc dữ liệu hoặc tệp CSV rỗng');
            }
        })
        .catch(err => {
            loading.classList.add('d-none');
            placeholder.classList.remove('d-none');
            console.error(err);
        });
    });
}
</script>
@endpush
