@extends('admins.layouts.master')

@section('page-title', 'Công cụ hệ thống')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Công cụ Hệ thống</h2>
        <p class="text-muted mb-0">Các công cụ xử lý dọn dẹp và tối ưu hóa hệ thống.</p>
    </div>
</div>

<div class="row">
    <!-- Post Images Cleanup Tool -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="fas fa-broom text-primary me-2"></i> Dọn dẹp ảnh rác Bài viết
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Công cụ này sẽ quét toàn bộ bài viết (thumbnail và nội dung) để thu thập các ảnh đang được sử dụng. Sau đó đối chiếu với thư mục ảnh thực tế (<code>public/clients/assets/img/posts</code>) và xóa các ảnh không còn sử dụng để giải phóng dung lượng.
                </p>

                <div id="cleanup-status" class="alert alert-info d-none">
                    <span class="spinner-border spinner-border-sm me-2"></span> Đang quét dữ liệu, vui lòng chờ...
                </div>

                <div id="cleanup-result" class="alert alert-warning d-none">
                    <h6 class="alert-heading fw-bold">Kết quả quét:</h6>
                    <p class="mb-2">Tìm thấy <strong id="garbage-count" class="fs-5 text-danger">0</strong> file rác không được sử dụng.</p>
                    <hr>
                    <div class="progress mb-2 d-none" id="delete-progress-wrapper" style="height: 20px;">
                        <div id="delete-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <p class="mb-0 text-muted small" id="delete-status-text"></p>
                    
                    <button id="btn-start-delete" class="btn btn-danger mt-3 w-100 fw-bold">
                        <i class="fas fa-trash-alt me-1"></i> BẮT ĐẦU XÓA FILE RÁC
                    </button>
                </div>

                <div class="text-end">
                    <button id="btn-scan-images" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Phân tích Thư mục
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Image Links Tool -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="fas fa-file-export text-success me-2"></i> Xuất danh sách URL Ảnh
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Công cụ này sẽ phân tích toàn bộ dữ liệu (kể cả các bài viết trong Thùng rác) và xuất ra file text (.txt) chứa tất cả đường dẫn ảnh đang được sử dụng.
                </p>
                <div class="text-end mt-4">
                    <a href="{{ route('admin.tools.export-post-images') }}" target="_blank" class="btn btn-success fw-bold">
                        <i class="fas fa-download me-1"></i> Tải danh sách (.txt)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnScan = document.getElementById('btn-scan-images');
    const statusBox = document.getElementById('cleanup-status');
    const resultBox = document.getElementById('cleanup-result');
    const garbageCountEl = document.getElementById('garbage-count');
    const btnDelete = document.getElementById('btn-start-delete');
    
    const progressWrapper = document.getElementById('delete-progress-wrapper');
    const progressBar = document.getElementById('delete-progress-bar');
    const statusText = document.getElementById('delete-status-text');

    let garbageFiles = [];

    btnScan.addEventListener('click', async function () {
        btnScan.disabled = true;
        statusBox.classList.remove('d-none');
        resultBox.classList.add('d-none');
        
        try {
            const res = await fetch("{{ route('admin.tools.scan-post-images') }}", {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            
            if (data.success) {
                garbageFiles = data.files_to_delete || [];
                garbageCountEl.textContent = garbageFiles.length;
                
                statusBox.classList.add('d-none');
                resultBox.classList.remove('d-none');
                
                if (garbageFiles.length === 0) {
                    btnDelete.disabled = true;
                    btnDelete.innerHTML = '<i class="fas fa-check-circle me-1"></i> KHÔNG CÓ FILE RÁC';
                    btnDelete.classList.replace('btn-danger', 'btn-success');
                } else {
                    btnDelete.disabled = false;
                    btnDelete.innerHTML = '<i class="fas fa-trash-alt me-1"></i> BẮT ĐẦU XÓA FILE RÁC';
                    btnDelete.classList.replace('btn-success', 'btn-danger');
                }
                
                // reset progress
                progressWrapper.classList.add('d-none');
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                statusText.textContent = '';
                progressBar.classList.add('progress-bar-animated', 'progress-bar-striped');
                progressBar.classList.replace('bg-success', 'bg-danger');
            } else {
                alert('Có lỗi xảy ra: ' + data.message);
                statusBox.classList.add('d-none');
            }
        } catch (err) {
            console.error(err);
            alert('Lỗi kết nối máy chủ');
            statusBox.classList.add('d-none');
        } finally {
            btnScan.disabled = false;
        }
    });

    btnDelete.addEventListener('click', async function () {
        if (!confirm(`Bạn sắp XÓA VĨNH VIỄN ${garbageFiles.length} file ảnh không sử dụng. Hành động này không thể hoàn tác. Tiếp tục?`)) {
            return;
        }

        btnDelete.disabled = true;
        btnScan.disabled = true;
        progressWrapper.classList.remove('d-none');
        
        const batchSize = 100;
        let successCount = 0;
        const total = garbageFiles.length;

        try {
            for (let i = 0; i < total; i += batchSize) {
                const batch = garbageFiles.slice(i, i + batchSize);
                statusText.textContent = `Đang xóa ${i + 1} đến ${Math.min(i + batchSize, total)} / ${total} files...`;

                const res = await fetch("{{ route('admin.tools.delete-post-images') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ files: batch })
                });

                const data = await res.json();
                if (data.success) {
                    successCount += data.deleted_count || 0;
                }
                
                // Update progress
                const percent = Math.round(((i + batch.length) / total) * 100);
                progressBar.style.width = percent + '%';
                progressBar.textContent = percent + '%';
                progressBar.setAttribute('aria-valuenow', percent);
            }
            
            progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
            progressBar.classList.replace('bg-danger', 'bg-success');
            statusText.innerHTML = `<span class="text-success fw-bold">Hoàn tất! Đã xóa thành công ${successCount} file rác.</span>`;
            btnDelete.innerHTML = '<i class="fas fa-check-circle me-1"></i> HOÀN TẤT';
        } catch (err) {
            console.error(err);
            statusText.innerHTML = `<span class="text-danger fw-bold">Lỗi khi xóa: ${err.message}</span>`;
            btnDelete.disabled = false;
        } finally {
            btnScan.disabled = false;
        }
    });
});
</script>
@endpush
