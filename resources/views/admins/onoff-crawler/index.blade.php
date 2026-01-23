@extends('admins.layouts.master')

@section('page-title', 'Tool Cào Onoff.vn')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/posts-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">🕷️ Tool Cào Onoff.vn</h2>
            <p class="text-muted mb-0">Cào bài viết từ onoff.vn và tự động viết lại bằng AI</p>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form id="crawlForm">
                @csrf
                <div class="mb-4">
                    <label for="category_urls" class="form-label fw-bold">
                        Danh sách URL danh mục <span class="text-danger">*</span>
                    </label>
                    <textarea 
                        id="category_urls" 
                        name="category_urls" 
                        class="form-control" 
                        rows="10" 
                        placeholder="Nhập mỗi URL danh mục trên một dòng, ví dụ:&#10;https://onoff.vn/blog/category/danh-muc-1&#10;https://onoff.vn/blog/category/danh-muc-2"
                        required
                    ></textarea>
                    <small class="form-text text-muted">
                        Mỗi URL trên một dòng. Tool sẽ tự động tìm và crawl tất cả bài viết trong các danh mục này.
                    </small>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="btnCrawl">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        🚀 Bắt đầu crawl
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="clearForm()">
                        🗑️ Xóa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0" id="resultCard" style="display: none;">
        <div class="card-body">
            <h5 class="card-title mb-3">📊 Kết quả crawl</h5>
            <div id="resultContent"></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('crawlForm');
    const btnCrawl = document.getElementById('btnCrawl');
    const spinner = btnCrawl.querySelector('.spinner-border');
    const resultCard = document.getElementById('resultCard');
    const resultContent = document.getElementById('resultContent');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const categoryUrls = document.getElementById('category_urls').value.trim();
        if (!categoryUrls) {
            alert('Vui lòng nhập ít nhất một URL danh mục!');
            return;
        }

        // Disable button và hiển thị spinner
        btnCrawl.disabled = true;
        spinner.classList.remove('d-none');
        resultCard.style.display = 'none';
        resultContent.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div><p class="mt-2">Đang crawl dữ liệu...</p></div>';

        try {
            const response = await fetch('{{ route("admin.onoff-crawler.crawl") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    category_urls: categoryUrls
                })
            });

            const data = await response.json();

            if (data.success) {
                let html = `
                    <div class="alert alert-success">
                        <strong>✅ Thành công!</strong> ${data.message}
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3>${data.data.success}</h3>
                                    <p class="mb-0">Bài viết thành công</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h3>${data.data.failed}</h3>
                                    <p class="mb-0">Bài viết thất bại</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3>${data.data.posts.length}</h3>
                                    <p class="mb-0">Tổng bài viết</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                if (data.data.errors && data.data.errors.length > 0) {
                    html += `
                        <div class="mt-4">
                            <h6>⚠️ Lỗi:</h6>
                            <ul class="list-group">
                    `;
                    data.data.errors.forEach(error => {
                        html += `<li class="list-group-item text-danger">${error}</li>`;
                    });
                    html += `</ul></div>`;
                }

                if (data.data.posts && data.data.posts.length > 0) {
                    html += `
                        <div class="mt-4">
                            <h6>📝 Bài viết đã tạo:</h6>
                            <ul class="list-group">
                    `;
                    data.data.posts.forEach(post => {
                        html += `
                            <li class="list-group-item">
                                <a href="{{ url('/admin/posts') }}/${post.id}/edit" target="_blank">
                                    ${post.title}
                                </a>
                            </li>
                        `;
                    });
                    html += `</ul></div>`;
                }

                resultContent.innerHTML = html;
                resultCard.style.display = 'block';
            } else {
                resultContent.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>❌ Lỗi!</strong> ${data.message}
                    </div>
                `;
                resultCard.style.display = 'block';
            }
        } catch (error) {
            resultContent.innerHTML = `
                <div class="alert alert-danger">
                    <strong>❌ Lỗi!</strong> ${error.message}
                </div>
            `;
            resultCard.style.display = 'block';
        } finally {
            btnCrawl.disabled = false;
            spinner.classList.add('d-none');
        }
    });
});

function clearForm() {
    if (confirm('Bạn có chắc muốn xóa tất cả nội dung?')) {
        document.getElementById('category_urls').value = '';
        document.getElementById('resultCard').style.display = 'none';
    }
}
</script>
@endpush
