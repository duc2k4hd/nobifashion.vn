@extends('admins.layouts.master')

@section('title', 'Trung tâm nhập dữ liệu video hàng loạt (Reels Import Center)')

@section('content')
<div class="nobifashion_admin_reels_wrapper container-fluid px-3 py-3">
    <!-- Header -->
    <div class="nobifashion_admin_reels_page_header mb-3">
        <div>
            <h1 class="nobifashion_admin_reels_page_title">
                <i class="fa-solid fa-file-import text-primary me-2"></i>Trung tâm nhập dữ liệu Video Reels (CSV Import)
            </h1>
            <div class="nobifashion_admin_reels_breadcrumb">
                <a href="{{ route('admin.reels.dashboard') }}">Reels</a>
                <span>/</span>
                <span>Nhập dữ liệu hàng loạt</span>
            </div>
        </div>
        <div class="nobifashion_admin_reels_page_actions">
            <a href="{{ route('admin.reels.import.sample') }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary">
                <i class="fa-solid fa-download me-1"></i> Tải file CSV mẫu (Excel UTF-8)
            </a>
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
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Import Form & Upload Section -->
    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i>Tải lên tệp CSV Reels
                    </h3>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    <form id="importForm" action="{{ route('admin.reels.import.process') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="nobifashion_admin_reels_form_label">Chọn tệp CSV / TXT <span class="text-danger">*</span></label>
                            <input type="file" id="csvFileInput" name="file" class="nobifashion_admin_reels_form_control" accept=".csv,text/csv,text/plain" required>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Hỗ trợ tối đa 50MB, mã hóa UTF-8.</small>
                                <a href="{{ route('admin.reels.import.sample') }}" class="small text-decoration-none">Tải mẫu chuẩn</a>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="nobifashion_admin_reels_form_label">Xử lý trùng lặp</label>
                                <select name="duplicate_action" class="nobifashion_admin_reels_form_control">
                                    <option value="skip" selected>Bỏ qua nếu đã tồn tại</option>
                                    <option value="update">Cập nhật đè dữ liệu mới</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="nobifashion_admin_reels_form_label">Trạng thái xuất bản</label>
                                <select name="status" class="nobifashion_admin_reels_form_control">
                                    <option value="published" selected>Xuất bản ngay</option>
                                    <option value="draft">Lưu dưới dạng nháp</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="nobifashion_admin_reels_form_label">Danh mục mặc định (nếu dòng trống)</label>
                            <select name="category" class="nobifashion_admin_reels_form_control">
                                <option value="trending" selected>Xu hướng (Trending)</option>
                                <option value="new">Hàng mới về (New)</option>
                                <option value="highlight">Nổi bật (Highlight)</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" id="btnSubmitImport" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                                <i class="fa-solid fa-play me-1"></i> Bắt đầu xử lý nhập dữ liệu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="nobifashion_admin_reels_card h-100">
                <div class="nobifashion_admin_reels_card_header d-flex justify-content-between align-items-center">
                    <h3 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-table-list text-info me-2"></i>Xem trước dữ liệu tệp (Preview)
                    </h3>
                    <span id="previewCountBadge" class="badge bg-light text-dark border d-none">0 dòng</span>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    <div id="previewPlaceholder" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-file-csv fa-3x text-secondary mb-3"></i>
                        <p class="mb-0">Chọn tệp CSV ở khung bên trái để xem trước các cột và mẫu dữ liệu trước khi quyết định thực thi.</p>
                    </div>

                    <div id="previewLoading" class="text-center py-5 d-none">
                        <i class="fa-solid fa-spinner fa-spin fa-2x text-primary mb-2"></i>
                        <p class="text-muted">Đang đọc và phân tích cấu trúc tệp CSV...</p>
                    </div>

                    <div id="previewTableContainer" class="table-responsive d-none" style="max-height: 280px;">
                        <table class="table table-bordered table-sm small align-middle">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>#</th>
                                    <th>Tiêu đề</th>
                                    <th>URL Video</th>
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

    <!-- Import Jobs History -->
    <div class="nobifashion_admin_reels_card">
        <div class="nobifashion_admin_reels_card_header">
            <h3 class="nobifashion_admin_reels_card_title">Lịch sử các tác vụ nhập dữ liệu gần đây</h3>
        </div>
        <div class="nobifashion_admin_reels_card_body p-0">
            <div class="table-responsive">
                <table class="nobifashion_admin_reels_table">
                    <thead>
                        <tr>
                            <th width="60">Job ID</th>
                            <th>Tên tệp CSV</th>
                            <th>Người thực hiện</th>
                            <th>Tổng dòng</th>
                            <th>Thành công</th>
                            <th>Bỏ qua</th>
                            <th>Lỗi</th>
                            <th>Trạng thái</th>
                            <th>Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jobs as $job)
                            <tr>
                                <td class="text-muted small">#{{ $job->id }}</td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $job->file_name }}</span>
                                    @if($job->file_path)
                                        <div class="text-muted" style="font-size: 11px;">{{ $job->file_path }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($job->creator)
                                        <span class="small text-dark">{{ $job->creator->fullname }}</span>
                                    @else
                                        <span class="small text-muted">Admin System</span>
                                    @endif
                                </td>
                                <td><strong>{{ number_format($job->total_rows ?? 0) }}</strong></td>
                                <td><span class="badge bg-success">+{{ number_format($job->imported_count ?? 0) }}</span></td>
                                <td><span class="badge bg-secondary">{{ number_format($job->skipped_count ?? 0) }}</span></td>
                                <td>
                                    @if(($job->failed_count ?? 0) > 0)
                                        <span class="badge bg-danger">{{ number_format($job->failed_count) }}</span>
                                        @if(!empty($job->error_log))
                                            <button type="button" class="btn btn-sm btn-link p-0 text-danger ms-1" onclick="alert({{ json_encode(json_encode($job->error_log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }})">
                                                <i class="fa-solid fa-circle-question"></i>
                                            </button>
                                        @endif
                                    @else
                                        <span class="badge bg-light text-muted border">0</span>
                                    @endif
                                </td>
                                <td>
                                    @if($job->status === 'completed')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Hoàn thành</span>
                                    @elseif($job->status === 'failed')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Thất bại</span>
                                    @elseif($job->status === 'processing')
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Đang chạy...</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $job->status }}</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $job->created_at ? $job->created_at->format('d/m/Y H:i') : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-folder-open fa-2x mb-2 d-block text-secondary"></i>
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
            if (res.success && res.data) {
                container.classList.remove('d-none');
                badge.classList.remove('d-none');
                badge.textContent = `Tổng ~${res.data.total_rows || 0} dòng`;

                let rowsHtml = '';
                (res.data.rows || []).forEach((row, i) => {
                    rowsHtml += `
                        <tr>
                            <td>${i + 1}</td>
                            <td class="fw-semibold">${row.title || '—'}</td>
                            <td class="text-truncate" style="max-width: 150px;"><a href="${row.video_url}" target="_blank">${row.video_url || '—'}</a></td>
                            <td><span class="badge bg-light text-dark border">${row.category || 'trending'}</span></td>
                            <td class="text-muted small">${row.hashtags || '—'}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = rowsHtml;
            } else {
                placeholder.classList.remove('d-none');
                alert(res.message || 'Không thể đọc dữ liệu file CSV');
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

