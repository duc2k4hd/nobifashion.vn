@extends('admins.layouts.master')

@section('page-title', 'Tool Cào Coolmate.me')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/posts-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">🕷️ Tool Cào Coolmate.me</h2>
            <p class="text-muted mb-0">Cào bài viết từ coolmate.me và tự động viết lại bằng AI cho Nobi Fashion Việt Nam</p>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form id="coolmateCrawlForm">
                @csrf
                <div class="mb-4">
                    <label for="coolmate_category_urls" class="form-label fw-bold">
                        Danh sách URL danh mục / endpoint JSON <span class="text-danger">*</span>
                    </label>
                    <textarea
                        id="coolmate_category_urls"
                        name="category_urls"
                        class="form-control"
                        rows="10"
                        placeholder="Mỗi dòng một URL danh mục hoặc endpoint JSON của Coolmate, ví dụ:&#10;https://www.coolmate.me/blog/wp-admin/admin-ajax.php?...&#10;https://www.coolmate.me/blog/category/thoi-trang-nam"
                        required
                    ></textarea>
                    <small class="form-text text-muted">
                        - Có thể nhập URL trang HTML danh mục hoặc URL API JSON (giống file JSON mẫu bạn đã debug).<br>
                        - Tool sẽ tự động nhận diện JSON/HTML, decode HTML và lấy link bài viết từ thẻ
                        <code>h5.post-title a</code>.
                    </small>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="btnCoolmateCrawl">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        🚀 Bắt đầu crawl Coolmate
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="coolmateClearForm()">
                        🗑️ Xóa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0" id="coolmateResultCard" style="display: none;">
        <div class="card-body">
            <h5 class="card-title mb-3">📊 Kết quả crawl Coolmate</h5>
            <div id="coolmateResultContent"></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('coolmateCrawlForm');
    const btn = document.getElementById('btnCoolmateCrawl');
    const spinner = btn.querySelector('.spinner-border');
    const resultCard = document.getElementById('coolmateResultCard');
    const resultContent = document.getElementById('coolmateResultContent');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const categoryUrls = document.getElementById('coolmate_category_urls').value.trim();
        if (!categoryUrls) {
            alert('Vui lòng nhập ít nhất một URL danh mục / JSON Coolmate!');
            return;
        }

        btn.disabled = true;
        spinner.classList.remove('d-none');
        resultCard.style.display = 'none';
        resultContent.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div><p class="mt-2">Đang crawl dữ liệu Coolmate...</p></div>';

        try {
            const response = await fetch('{{ route("admin.coolmate-crawler.crawl") }}', {
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
            btn.disabled = false;
            spinner.classList.add('d-none');
        }
    });
});

function coolmateClearForm() {
    if (confirm('Bạn có chắc muốn xóa tất cả nội dung?')) {
        document.getElementById('coolmate_category_urls').value = '';
        document.getElementById('coolmateResultCard').style.display = 'none';
    }
}
</script>
@endpush

