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
    <!-- Compress Posts HTML Tool -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="fas fa-compress-alt text-primary me-2"></i> Ép gọn HTML Nội dung Bài viết
                </h5>
            </div>
            <div class="card-body d-flex flex-column justify-content-between">
                <p class="text-muted">
                    Công cụ này sẽ phân tích và ép gọn toàn bộ mã HTML trong bài viết: loại bỏ các khoảng trắng và xuống dòng thừa (<code class="text-danger">\r\n</code>), ép các thẻ HTML liền nhau, loại bỏ chú thích thừa giúp tránh tràn giới hạn ô Excel khi xuất file và tối ưu dung lượng trang.
                </p>
                <div id="compressHtmlResult" class="d-none alert alert-success py-2 px-3 small mb-3"></div>
                <div class="text-end mt-3">
                    <button type="button" id="btnCompressHtml" class="btn btn-primary fw-bold">
                        <i class="fas fa-bolt me-1"></i> Bắt đầu ép gọn HTML
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Image Links Tool -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="fas fa-file-export text-success me-2"></i> Xuất danh sách URL Ảnh
                </h5>
            </div>
            <div class="card-body d-flex flex-column justify-content-between">
                <p class="text-muted">
                    Công cụ này sẽ phân tích toàn bộ dữ liệu (kể cả các bài viết trong Thùng rác) và xuất ra file text (.txt) chứa tất cả đường dẫn ảnh đang được sử dụng.
                </p>
                <div class="text-end mt-3">
                    <a href="{{ route('admin.tools.export-post-images') }}" target="_blank" class="btn btn-success fw-bold">
                        <i class="fas fa-download me-1"></i> Tải danh sách (.txt)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnCompressHtml = document.getElementById('btnCompressHtml');
        const compressResult = document.getElementById('compressHtmlResult');

        if (btnCompressHtml) {
            btnCompressHtml.addEventListener('click', async function () {
                if (!confirm('Bạn có chắc chắn muốn ép gọn HTML nội dung của tất cả bài viết trong cơ sở dữ liệu?')) {
                    return;
                }

                const originalHtml = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang xử lý...';
                if (compressResult) compressResult.classList.add('d-none');

                try {
                    const response = await fetch("{{ route('admin.tools.compress-posts-html') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });

                    const res = await response.json();

                    if (res.success) {
                        if (compressResult) {
                            compressResult.classList.remove('d-none');
                            compressResult.innerHTML = `
                                <strong><i class="fas fa-check-circle me-1"></i> ${res.message}</strong><br>
                                • Tổng bài viết đã quét: <strong>${res.total}</strong><br>
                                • Số bài viết được ép gọn: <strong>${res.updated}</strong><br>
                                • Dung lượng tiết kiệm: <strong>${res.saved_kb} KB</strong> (~${Number(res.saved_chars).toLocaleString()} ký tự, giảm ${res.percent}%)
                            `;
                        }
                        if (window.Toast) {
                            Toast.fire({ icon: 'success', title: res.message });
                        }
                    } else {
                        alert(res.message || 'Có lỗi xảy ra khi xử lý.');
                    }
                } catch (error) {
                    console.error(error);
                    alert('Lỗi kết nối máy chủ: ' + error.message);
                } finally {
                    this.disabled = false;
                    this.innerHTML = originalHtml;
                }
            });
        }
    });
</script>
@endpush

@endsection
