@extends('admins.layouts.master')

@section('page-title', 'Tool Cào Yody.vn')

@php
    $tempProducts = $tempLibrary['products'] ?? [];
    $tempExcelFiles = $tempLibrary['excel_files'] ?? [];
    $hasTempProducts = is_object($tempProducts) && method_exists($tempProducts, 'count')
        ? $tempProducts->count() > 0
        : !empty($tempProducts);
@endphp

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Tool Cào Yody.vn</h2>
            <p class="text-muted mb-0">
                Crawl dữ liệu sản phẩm từ Yody, lưu ảnh vào <code>storage/app/tmp/yody</code>, sinh file Excel đúng chuẩn import
                và quản lý dữ liệu tạm ngay trong admin.
            </p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-{{ session('status_type', 'success') }} mb-4">
            {{ session('status') }}
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form id="yodyCrawlForm">
                @csrf

                <div class="mb-4">
                    <label for="yody_product_urls" class="form-label fw-bold">
                        Danh sách URL sản phẩm Yody <span class="text-danger">*</span>
                    </label>
                    <textarea
                        id="yody_product_urls"
                        name="product_urls"
                        class="form-control"
                        rows="8"
                        placeholder="Mỗi dòng một URL, ví dụ:&#10;https://yody.vn/product/quan-kaki-nam-sieu-co-gian-regular"
                        required
                    ></textarea>
                    <small class="form-text text-muted">
                        Nếu SKU đã tồn tại trong index temp Yody thì tool sẽ tự động bỏ qua.
                    </small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="primary_category_slug" class="form-label fw-bold">Primary Category Slug</label>
                        <input
                            id="primary_category_slug"
                            name="primary_category_slug"
                            type="text"
                            class="form-control"
                            placeholder="Ví dụ: quan-nam"
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="category_slugs" class="form-label fw-bold">Category Slugs bổ sung</label>
                        <input
                            id="category_slugs"
                            name="category_slugs"
                            type="text"
                            class="form-control"
                            placeholder="Ví dụ: quan-nam,thoi-trang-nam"
                        >
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="tag_names" class="form-label fw-bold">Tag mặc định</label>
                        <input
                            id="tag_names"
                            name="tag_names"
                            type="text"
                            class="form-control"
                            placeholder="Ví dụ: Yody, Quần kaki, Nam"
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold d-block">Thiết lập mặc định</label>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="is_active" checked>
                            <label class="form-check-label" for="is_active">Import sản phẩm ở trạng thái active</label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="is_featured">
                            <label class="form-check-label" for="is_featured">Đánh dấu featured</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="use_yody_category_as_tag" checked>
                            <label class="form-check-label" for="use_yody_category_as_tag">Thêm category Yody vào tag</label>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning mb-4">
                    <strong>Lưu ý:</strong>
                    Ảnh và file Excel đều được lưu trong <code>storage/app/tmp/yody</code>. File Excel sinh ra vẫn dùng đúng format
                    của tool Import Excel hiện tại.
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary" id="btnYodyCrawl">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Bắt đầu crawl Yody
                    </button>

                    <a href="{{ route('admin.products.import-excel') }}" class="btn btn-outline-secondary">
                        Mở trang Import Excel
                    </a>

                    <button type="button" class="btn btn-secondary" onclick="clearYodyForm()">
                        Xóa nội dung
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <div>
                    <h5 class="mb-1">Quản lý dữ liệu tạm Yody</h5>
                    <p class="text-muted mb-0">Index theo SKU trong DB, có tìm kiếm và phân trang để chịu tải lớn.</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.yody-crawler.index') }}" class="btn btn-outline-secondary">
                        Tải lại
                    </a>

                    <form method="POST" action="{{ route('admin.yody-crawler.clear-temp') }}" onsubmit="return confirm('Xóa toàn bộ ảnh, manifest và file Excel tạm của Yody?');">
                        @csrf
                        <button type="submit" class="btn btn-danger">Xóa toàn bộ temp Yody</button>
                    </form>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.yody-crawler.index') }}" class="row g-2 mb-4">
                <div class="col-md-8">
                    <input
                        type="text"
                        name="q"
                        value="{{ $tempLibrary['search'] ?? '' }}"
                        class="form-control"
                        placeholder="Tìm theo SKU, tên, slug hoặc source URL"
                    >
                </div>
                <div class="col-md-2">
                    <select name="per_page" class="form-select">
                        @foreach ([20, 50, 100] as $pageSize)
                            <option value="{{ $pageSize }}" {{ (int) ($tempLibrary['per_page'] ?? 20) === $pageSize ? 'selected' : '' }}>
                                {{ $pageSize }} / trang
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-outline-primary">Lọc danh sách</button>
                </div>
            </form>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card bg-light h-100">
                        <div class="card-body">
                            <div class="text-muted small">SKU đang lưu</div>
                            <div class="fs-3 fw-bold">{{ $tempLibrary['product_count'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card bg-light h-100">
                        <div class="card-body">
                            <div class="text-muted small">Ảnh tạm</div>
                            <div class="fs-3 fw-bold">{{ $tempLibrary['image_count'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card bg-light h-100">
                        <div class="card-body">
                            <div class="text-muted small">File Excel tạm</div>
                            <div class="fs-3 fw-bold">{{ $tempLibrary['excel_file_count'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-light border mb-4">
                <div><strong>Thư mục tạm:</strong> <code>{{ $tempLibrary['temp_directory'] ?? '' }}</code></div>
                <div><strong>Thư mục ảnh:</strong> <code>{{ $tempLibrary['image_directory'] ?? '' }}</code></div>
                <div><strong>Manifest cũ:</strong> <code>{{ $tempLibrary['manifest_path'] ?? '' }}</code></div>
            </div>

            <h6 class="fw-bold mb-3">Danh sách SKU đã crawl</h6>

            @if ($hasTempProducts)
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th style="width: 120px;">Preview</th>
                                <th>SKU / Tên</th>
                                <th>Nguồn</th>
                                <th>Biến thể</th>
                                <th>Ảnh</th>
                                <th>Thời gian</th>
                                <th style="width: 180px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tempProducts as $product)
                                <tr>
                                    <td>
                                        @if (!empty($product['preview_url']))
                                            <img
                                                src="{{ $product['preview_url'] }}"
                                                alt="{{ $product['name'] ?? $product['sku'] }}"
                                                style="width: 88px; height: 110px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb;"
                                            >
                                        @else
                                            <div class="text-muted small">Không có ảnh</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $product['name'] ?? $product['sku'] }}</div>
                                        <div><code>{{ $product['sku'] }}</code></div>
                                        @if (!empty($product['slug']))
                                            <div class="small text-muted">{{ $product['slug'] }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if (!empty($product['source_url']))
                                            <a href="{{ $product['source_url'] }}" target="_blank" rel="noopener noreferrer">
                                                {{ $product['source_url'] }}
                                            </a>
                                        @else
                                            <span class="text-muted">Không có nguồn</span>
                                        @endif
                                    </td>
                                    <td>{{ $product['variant_count'] ?? 0 }}</td>
                                    <td>
                                        <div>{{ $product['image_count'] ?? 0 }} ảnh</div>
                                        @if (!empty($product['preview_relative_path']))
                                            <div class="small text-muted">
                                                <code>{{ $product['preview_relative_path'] }}</code>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small">Lần đầu: {{ $product['first_crawled_at'] ?? '-' }}</div>
                                        <div class="small">Gần nhất: {{ $product['last_crawled_at'] ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.yody-crawler.delete-product', $product['sku']) }}"
                                            onsubmit="return confirm('Xóa toàn bộ ảnh tạm của SKU {{ $product['sku'] }}?');"
                                        >
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Xóa SKU này
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if (is_object($tempProducts) && method_exists($tempProducts, 'links'))
                    <div class="d-flex justify-content-end">
                        {{ $tempProducts->links() }}
                    </div>
                @endif
            @else
                <div class="alert alert-light border mb-4">
                    Chưa có SKU nào trong thư mục tạm Yody.
                </div>
            @endif

            <h6 class="fw-bold mb-3">File Excel tạm</h6>

            @if (!empty($tempExcelFiles))
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tên file</th>
                                <th>Dung lượng</th>
                                <th>Cập nhật</th>
                                <th style="width: 140px;">Tải xuống</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tempExcelFiles as $excelFile)
                                <tr>
                                    <td><code>{{ $excelFile['name'] }}</code></td>
                                    <td>{{ number_format(($excelFile['size_bytes'] ?? 0) / 1024, 1) }} KB</td>
                                    <td>{{ $excelFile['updated_at'] ?? '-' }}</td>
                                    <td>
                                        <a href="{{ $excelFile['download_url'] }}" class="btn btn-sm btn-outline-success">
                                            Tải file
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-light border mb-0">
                    Chưa có file Excel tạm nào.
                </div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm border-0" id="yodyResultCard" style="display: none;">
        <div class="card-body">
            <h5 class="card-title mb-3">Kết quả crawl Yody</h5>
            <div id="yodyResultContent"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('yodyCrawlForm');
            const button = document.getElementById('btnYodyCrawl');
            const spinner = button.querySelector('.spinner-border');
            const resultCard = document.getElementById('yodyResultCard');
            const resultContent = document.getElementById('yodyResultContent');

            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const productUrls = document.getElementById('yody_product_urls').value.trim();
                if (!productUrls) {
                    alert('Vui lòng nhập ít nhất một URL sản phẩm Yody.');
                    return;
                }

                button.disabled = true;
                spinner.classList.remove('d-none');
                resultCard.style.display = 'block';
                resultContent.innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status"></div>
                        <p class="mt-3 mb-0">Đang crawl Yody, tải ảnh và tạo file Excel...</p>
                    </div>
                `;

                try {
                    const response = await fetch('{{ route('admin.yody-crawler.crawl') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            product_urls: productUrls,
                            primary_category_slug: document.getElementById('primary_category_slug').value.trim(),
                            category_slugs: document.getElementById('category_slugs').value.trim(),
                            tag_names: document.getElementById('tag_names').value.trim(),
                            is_active: document.getElementById('is_active').checked,
                            is_featured: document.getElementById('is_featured').checked,
                            use_yody_category_as_tag: document.getElementById('use_yody_category_as_tag').checked
                        })
                    });

                    const data = await response.json();

                    if (!data.success) {
                        resultContent.innerHTML = `
                            <div class="alert alert-danger mb-0">
                                <strong>Lỗi:</strong> ${data.message}
                            </div>
                        `;
                        return;
                    }

                    const summary = data.data;
                    let html = `
                        <div class="alert alert-success">
                            <strong>Thành công:</strong> ${data.message}
                        </div>
                        <div class="alert alert-light border mb-4">
                            <div><strong>Thư mục tạm:</strong> <code>${summary.temp_directory || ''}</code></div>
                            <div><strong>Thư mục ảnh:</strong> <code>${summary.image_directory || ''}</code></div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <div class="fs-3 fw-bold">${summary.success}</div>
                                        <div>Sản phẩm mới</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-dark">
                                    <div class="card-body text-center">
                                        <div class="fs-3 fw-bold">${summary.skipped || 0}</div>
                                        <div>URL bị bỏ qua</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <div class="fs-3 fw-bold">${summary.variant_count}</div>
                                        <div>Biến thể</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <div class="fs-3 fw-bold">${summary.image_downloaded_count}</div>
                                        <div>Ảnh đã lưu</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    if (summary.download_url) {
                        html += `
                            <div class="mb-4 d-flex gap-2 flex-wrap">
                                <a href="${summary.download_url}" class="btn btn-success">
                                    Tải file Excel
                                </a>
                                <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
                                    Tải lại để cập nhật quản lý temp
                                </button>
                            </div>
                        `;
                    }

                    if (summary.products && summary.products.length > 0) {
                        html += `
                            <div class="table-responsive mb-4">
                                <table class="table table-bordered align-middle">
                                    <thead>
                                        <tr>
                                            <th>Tên sản phẩm</th>
                                            <th>SKU</th>
                                            <th>Slug</th>
                                            <th>Biến thể</th>
                                            <th>Ảnh</th>
                                            <th>Primary Category</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        summary.products.forEach((product) => {
                            html += `
                                <tr>
                                    <td>
                                        <div class="fw-semibold">${product.name}</div>
                                        <div class="small text-muted">${product.source_url}</div>
                                    </td>
                                    <td><code>${product.sku}</code></td>
                                    <td><code>${product.slug || ''}</code></td>
                                    <td>${product.variant_count}</td>
                                    <td>${product.image_count}</td>
                                    <td>${product.primary_category_slug || '<span class="text-muted">Để trống</span>'}</td>
                                </tr>
                            `;
                        });

                        html += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }

                    if (summary.warnings && summary.warnings.length > 0) {
                        html += `
                            <div class="mb-4">
                                <h6 class="fw-bold text-warning">Warnings</h6>
                                <ul class="list-group">
                                    ${summary.warnings.map((warning) => `<li class="list-group-item text-warning">${warning}</li>`).join('')}
                                </ul>
                            </div>
                        `;
                    }

                    if (summary.errors && summary.errors.length > 0) {
                        html += `
                            <div>
                                <h6 class="fw-bold text-danger">Errors</h6>
                                <ul class="list-group">
                                    ${summary.errors.map((error) => `<li class="list-group-item text-danger">${error}</li>`).join('')}
                                </ul>
                            </div>
                        `;
                    }

                    resultContent.innerHTML = html;
                } catch (error) {
                    resultContent.innerHTML = `
                        <div class="alert alert-danger mb-0">
                            <strong>Lỗi:</strong> ${error.message}
                        </div>
                    `;
                } finally {
                    button.disabled = false;
                    spinner.classList.add('d-none');
                }
            });
        });

        function clearYodyForm() {
            if (!confirm('Xóa toàn bộ nội dung form Yody crawler?')) {
                return;
            }

            document.getElementById('yodyCrawlForm').reset();
            document.getElementById('is_active').checked = true;
            document.getElementById('use_yody_category_as_tag').checked = true;
            document.getElementById('yodyResultCard').style.display = 'none';
            document.getElementById('yodyResultContent').innerHTML = '';
        }
    </script>
@endpush
