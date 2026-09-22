@extends('admins.layouts.master')

@section('page-title', 'Tool Cào Coolmate.me')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/posts-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">🕷️ Tool Cào Coolmate.me</h2>
            <p class="text-muted mb-0">
                Crawl bài viết theo batch song song, lưu ảnh vào thư mục tạm và xuất toàn bộ dữ liệu thành CSV.
                Tool không ghi dữ liệu vào database.
            </p>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form id="coolmateCrawlForm">
                @csrf
                <div class="mb-4">
                    <label for="coolmate_post_urls" class="form-label fw-bold">
                        Danh sách URL bài viết Coolmate <span class="text-danger">*</span>
                    </label>
                    <textarea
                        id="coolmate_post_urls"
                        name="post_urls"
                        class="form-control"
                        rows="10"
                        placeholder="Mỗi dòng một URL bài viết, ví dụ:&#10;https://www.coolmate.me/blog/cach-phoi-do-nam&#10;https://www.coolmate.me/blog/ao-thun-nam-dep"
                        required
                    ></textarea>
                    <small class="form-text text-muted">
                        Mỗi dòng một URL thuộc <code>coolmate.me</code> hoặc <code>www.coolmate.me</code>.
                        URL trùng hoặc đã crawl thành công trước đó sẽ tự động được bỏ qua.
                    </small>
                </div>

                <div class="form-check mb-3">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="coolmate_download_main_image"
                        name="download_main_image"
                        value="1"
                    >
                    <label class="form-check-label fw-semibold" for="coolmate_download_main_image">
                        Tải ảnh đại diện chính (main image) về máy
                    </label>
                    <div class="form-text">
                        Mặc định <strong>tắt</strong> để tăng tốc độ cào tối đa (chỉ lưu URL ảnh gốc vào CSV). Bật lên nếu muốn tải file ảnh đại diện về <code>storage/app/tmp/coolmate/main</code>.
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="coolmate_recrawl_existing"
                        name="recrawl_existing"
                        value="1"
                    >
                    <label class="form-check-label fw-semibold" for="coolmate_recrawl_existing">
                        Cào lại những link đã có trong <code>crawled_urls.json</code>
                    </label>
                    <div class="form-text">
                        Khi bật, lịch sử crawl sẽ bị bỏ qua; URL trùng trong chính danh sách nhập vẫn chỉ được cào một lần.
                    </div>
                </div>

                <div class="alert alert-info">
                    <div><strong>Ảnh chính:</strong> <code>storage/app/tmp/coolmate/main</code></div>
                    <div><strong>Ảnh trong content:</strong> <code>storage/app/tmp/coolmate/extra</code></div>
                    <div><strong>CSV:</strong> <code>storage/app/tmp/coolmate</code></div>
                    <div class="small mt-1">
                        Mỗi bài viết là một bản ghi CSV; toàn bộ HTML content được giữ nguyên, không chia nhỏ hoặc cắt bớt.
                    </div>
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

        const postUrls = document.getElementById('coolmate_post_urls').value.trim();
        const recrawlExisting = document.getElementById('coolmate_recrawl_existing').checked;
        const downloadMainImage = document.getElementById('coolmate_download_main_image').checked;
        if (!postUrls) {
            alert('Vui lòng nhập ít nhất một URL bài viết Coolmate!');
            return;
        }

        btn.disabled = true;
        spinner.classList.remove('d-none');
        resultCard.style.display = 'block';
        resultContent.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div><p class="mt-2">Đang crawl song song, xử lý dữ liệu và tạo file CSV...</p></div>';

        try {
            const response = await fetch('{{ route("admin.coolmate-crawler.crawl") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    post_urls: postUrls,
                    recrawl_existing: recrawlExisting,
                    download_main_image: downloadMainImage
                })
            });

            const data = await response.json().catch(() => {
                throw new Error('Máy chủ trả về dữ liệu không hợp lệ.');
            });

            if (data.success) {
                const summary = data.data || {};
                const alertType = (summary.failed || 0) === 0 ? 'success' : ((summary.success || 0) > 0 ? 'warning' : 'danger');
                let html = `
                    <div class="alert alert-${alertType}">
                        <strong>Kết quả:</strong> ${coolmateEscapeHtml(data.message || '')}
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3>${Number(summary.success || 0)}</h3>
                                    <p class="mb-0">Bài viết thành công</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h3>${Number(summary.failed || 0)}</h3>
                                    <p class="mb-0">Bài viết thất bại</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3>${Number(summary.image_downloaded_count || 0)}</h3>
                                    <p class="mb-0">Ảnh đã tải</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3>${Number(summary.total || 0)}</h3>
                                    <p class="mb-0">URL đã nhập</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                if (summary.download_url) {
                    html += `
                        <div class="alert alert-light border d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div>
                                <div class="fw-bold">File CSV đã tạo</div>
                                <div class="small text-muted text-break">${coolmateEscapeHtml(summary.file_path || summary.file_name || '')}</div>
                                <div class="small text-muted">
                                    Batch trang: ${Number(summary.page_batch_size || 0)} ·
                                    Batch ảnh: ${Number(summary.image_batch_size || 0)} ·
                                    URL bỏ qua: ${Number(summary.skipped || 0)}
                                    (${Number(summary.skipped_history || 0)} đã crawl,
                                    ${Number(summary.skipped_duplicate || 0)} trùng trong lần nhập)
                                </div>
                            </div>
                            <a href="${coolmateEscapeHtml(summary.download_url)}" class="btn btn-success">
                                ⬇️ Tải file CSV
                            </a>
                        </div>
                    `;
                }

                if (summary.warnings && summary.warnings.length > 0) {
                    html += `
                        <div class="mt-4">
                            <h6>⚠️ Cảnh báo:</h6>
                            <ul class="list-group">
                                ${summary.warnings.map(warning => `<li class="list-group-item text-warning">${coolmateEscapeHtml(warning)}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                }

                if (summary.errors && summary.errors.length > 0) {
                    html += `
                        <div class="mt-4">
                            <h6>⚠️ Lỗi:</h6>
                            <ul class="list-group">
                                ${summary.errors.map(error => `<li class="list-group-item text-danger">${coolmateEscapeHtml(error)}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                }

                if (summary.posts && summary.posts.length > 0) {
                    html += `
                        <div class="mt-4">
                            <h6>📝 Bài viết đã ghi vào CSV:</h6>
                            <ul class="list-group">
                    `;
                    summary.posts.forEach(post => {
                        html += `
                            <li class="list-group-item">
                                <div class="fw-semibold">${coolmateEscapeHtml(post.title || '')}</div>
                                <div><code>${coolmateEscapeHtml(post.slug || '')}</code></div>
                                <div class="small text-muted text-break">${coolmateEscapeHtml(post.source_url || '')}</div>
                                <div class="small text-muted">
                                    ${Number(post.extra_image_count || 0)} ảnh content ·
                                    ${Number(post.content_length || 0).toLocaleString('vi-VN')} ký tự content
                                </div>
                            </li>
                        `;
                    });
                    html += `</ul></div>`;
                }

                resultContent.innerHTML = html;
                resultCard.style.display = 'block';
            } else {
                const validationMessage = coolmateValidationMessage(data);
                const summary = data.data || {};
                const errorItems = Array.isArray(summary.errors)
                    ? `<ul class="list-group mt-3">${summary.errors.map(error => `<li class="list-group-item text-danger">${coolmateEscapeHtml(error)}</li>`).join('')}</ul>`
                    : '';
                resultContent.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>❌ Lỗi!</strong> ${coolmateEscapeHtml(validationMessage)}
                    </div>
                    ${errorItems}
                `;
                resultCard.style.display = 'block';
            }
        } catch (error) {
            resultContent.innerHTML = `
                <div class="alert alert-danger">
                    <strong>❌ Lỗi!</strong> ${coolmateEscapeHtml(error.message)}
                </div>
            `;
            resultCard.style.display = 'block';
        } finally {
            btn.disabled = false;
            spinner.classList.add('d-none');
        }
    });
});

function coolmateEscapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = String(value ?? '');
    return element.innerHTML;
}

function coolmateValidationMessage(data) {
    if (data && data.errors) {
        const messages = Object.values(data.errors).flat();
        if (messages.length > 0) {
            return messages.join(' ');
        }
    }

    return data && data.message ? data.message : 'Không thể crawl dữ liệu Coolmate.';
}

function coolmateClearForm() {
    if (confirm('Bạn có chắc muốn xóa tất cả nội dung?')) {
        document.getElementById('coolmate_post_urls').value = '';
        document.getElementById('coolmate_download_main_image').checked = false;
        document.getElementById('coolmate_recrawl_existing').checked = false;
        document.getElementById('coolmateResultCard').style.display = 'none';
        document.getElementById('coolmateResultContent').innerHTML = '';
    }
}
</script>
@endpush
