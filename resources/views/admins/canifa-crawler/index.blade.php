@extends('admins.layouts.master')

@section('page-title', 'Tool Cào Canifa.com')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/posts-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">🕷️ Tool Cào Canifa.com</h2>
            <p class="text-muted mb-0">Cào bài viết từ canifa.com và tự động viết lại bằng AI</p>
        </div>
    </div>

    <!-- Tool tạo danh sách URL -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">🔗 Tool Tạo Danh Sách URL</h5>
            <form id="generateUrlsForm">
                @csrf
                <div class="mb-3">
                    <label for="url_input" class="form-label fw-bold">
                        Nhập URL có số trang <span class="text-danger">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="url_input" 
                        name="url" 
                        class="form-control" 
                        placeholder="https://canifa.com/blog/category/tu-van-thoi-trang/phoi-do-dep/page/%20*%2037"
                        required
                    >
                    <small class="form-text text-muted">
                        Tool sẽ tạo danh sách URL từ trang 2 đến trang cuối (trừ trang 1)
                    </small>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="btnGenerateUrls">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        ✨ Tạo danh sách URL
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="clearUrlForm()">
                        🗑️ Xóa
                    </button>
                </div>
            </form>
        </div>
        <div class="card-body border-top" id="urlResultCard" style="display: none;">
            <h6 class="mb-3">📋 Danh sách URL đã tạo (<span id="urlCount">0</span> URL)</h6>
            <div class="mb-3">
                <button type="button" class="btn btn-sm btn-success" onclick="copyAllUrls()">
                    📋 Copy tất cả
                </button>
                <button type="button" class="btn btn-sm btn-info" onclick="copyToTextarea()">
                    📝 Copy vào ô crawl
                </button>
            </div>
            <textarea 
                id="generatedUrls" 
                class="form-control" 
                rows="15" 
                readonly
                style="font-family: monospace; font-size: 12px;"
            ></textarea>
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
                        placeholder="Nhập mỗi URL danh mục trên một dòng, ví dụ:&#10;https://canifa.com/danh-muc-1&#10;https://canifa.com/danh-muc-2"
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
            const response = await fetch('{{ route("admin.canifa-crawler.crawl") }}', {
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

// Tool tạo danh sách URL
document.addEventListener('DOMContentLoaded', function() {
    const generateUrlsForm = document.getElementById('generateUrlsForm');
    const btnGenerateUrls = document.getElementById('btnGenerateUrls');
    const spinnerGenerate = btnGenerateUrls.querySelector('.spinner-border');
    const urlResultCard = document.getElementById('urlResultCard');
    const generatedUrls = document.getElementById('generatedUrls');
    const urlCount = document.getElementById('urlCount');

    if (generateUrlsForm) {
        generateUrlsForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const url = document.getElementById('url_input').value.trim();
            if (!url) {
                alert('Vui lòng nhập URL!');
                return;
            }

            // Disable button và hiển thị spinner
            btnGenerateUrls.disabled = true;
            spinnerGenerate.classList.remove('d-none');

            try {
                const response = await fetch('{{ route("admin.canifa-crawler.generate-urls") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        url: url
                    })
                });

                const data = await response.json();

                if (data.success) {
                    const urlsText = data.urls.join('\n');
                    generatedUrls.value = urlsText;
                    urlCount.textContent = data.count;
                    urlResultCard.style.display = 'block';
                } else {
                    alert('Lỗi: ' + data.message);
                }
            } catch (error) {
                alert('Lỗi: ' + error.message);
            } finally {
                btnGenerateUrls.disabled = false;
                spinnerGenerate.classList.add('d-none');
            }
        });
    }
});

function clearUrlForm() {
    if (confirm('Bạn có chắc muốn xóa?')) {
        document.getElementById('url_input').value = '';
        document.getElementById('urlResultCard').style.display = 'none';
        document.getElementById('generatedUrls').value = '';
    }
}

function copyAllUrls() {
    const textarea = document.getElementById('generatedUrls');
    textarea.select();
    document.execCommand('copy');
    alert('Đã copy ' + textarea.value.split('\n').length + ' URL vào clipboard!');
}

function copyToTextarea() {
    const generatedUrls = document.getElementById('generatedUrls').value;
    if (generatedUrls) {
        document.getElementById('category_urls').value = generatedUrls;
        alert('Đã copy vào ô crawl!');
    } else {
        alert('Chưa có danh sách URL nào!');
    }
}
</script>
@endpush
