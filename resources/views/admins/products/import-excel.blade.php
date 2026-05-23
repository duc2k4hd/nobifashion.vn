@extends('admins.layouts.master')

@section('title', 'Import Sản Phẩm Từ Excel')
@section('page-title', 'Import Excel')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/imports-excel.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .import-page {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .import-hero,
        .import-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
        }
        .import-hero {
            padding: 24px 28px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }
        .import-hero h1 {
            margin: 0 0 10px;
            font-size: 28px;
            color: #0f172a;
        }
        .import-hero p {
            margin: 0;
            color: #475569;
            max-width: 760px;
            line-height: 1.6;
        }
        .import-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }
        .import-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            transition: 0.2s ease;
        }
        .import-link-secondary {
            background: #334155;
            color: #fff;
        }
        .import-link-secondary:hover {
            background: #1e293b;
        }
        .import-link-primary {
            background: linear-gradient(135deg, #0f766e, #0284c7);
            color: #fff;
        }
        .import-link-primary:hover {
            filter: brightness(1.05);
        }
        .import-card {
            padding: 22px 24px;
        }
        .import-card h2 {
            margin: 0 0 14px;
            font-size: 22px;
            color: #0f172a;
        }
        .import-card h3 {
            margin: 0 0 10px;
            font-size: 18px;
            color: #0f172a;
        }
        .import-card p,
        .import-card li {
            color: #334155;
            line-height: 1.7;
        }
        .import-card ul,
        .import-card ol {
            margin: 0;
            padding-left: 20px;
        }
        .import-card li + li {
            margin-top: 6px;
        }
        .import-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }
        .import-note {
            border-radius: 12px;
            padding: 16px 18px;
            border: 1px solid transparent;
        }
        .import-note-info {
            background: #eff6ff;
            border-color: #bfdbfe;
        }
        .import-note-warning {
            background: #fff7ed;
            border-color: #fed7aa;
        }
        .import-note-success {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }
        .import-note-danger {
            background: #fef2f2;
            border-color: #fecaca;
        }
        .import-note h3 {
            margin-bottom: 8px;
        }
        .import-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 14px;
        }
        .import-table th,
        .import-table td {
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            vertical-align: top;
            text-align: left;
        }
        .import-table th {
            background: #f8fafc;
            color: #0f172a;
            font-weight: 700;
        }
        .import-table code {
            white-space: nowrap;
        }
        .import-code {
            margin: 12px 0 0;
            padding: 14px 16px;
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 12px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.7;
        }
        .import-details {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }
        .import-details + .import-details {
            margin-top: 14px;
        }
        .import-details summary {
            cursor: pointer;
            padding: 16px 18px;
            font-weight: 700;
            color: #0f172a;
            background: #f8fafc;
        }
        .import-details-body {
            padding: 18px;
        }
        .alert-box {
            padding: 16px 18px;
            border-radius: 12px;
            margin-bottom: 16px;
            border: 1px solid transparent;
        }
        .alert-box-success {
            background: #f0fdf4;
            color: #166534;
            border-color: #bbf7d0;
        }
        .alert-box-error {
            background: #fef2f2;
            color: #991b1b;
            border-color: #fecaca;
        }
        .alert-box-warning {
            background: #fff7ed;
            color: #9a3412;
            border-color: #fdba74;
        }
        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .upload-form label {
            font-weight: 700;
            color: #0f172a;
        }
        .upload-form input[type="file"] {
            width: 100%;
            padding: 14px;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            cursor: pointer;
        }
        .upload-form input[type="file"]:hover {
            border-color: #0284c7;
        }
        .upload-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            align-self: flex-start;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }
        .upload-submit:hover {
            filter: brightness(1.05);
        }
        .upload-cancel {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            align-self: flex-start;
            padding: 12px 20px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            color: #334155;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }
        .upload-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }
        .upload-options {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .upload-options input,
        .upload-options select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            color: #0f172a;
        }
        .import-progress-card[hidden] {
            display: none;
        }
        .import-progress-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }
        .import-progress-summary > div {
            padding: 14px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
        }
        .import-progress-summary span {
            display: block;
            color: #64748b;
            font-size: 13px;
            margin-bottom: 6px;
        }
        .import-progress-summary strong {
            color: #0f172a;
            font-size: 18px;
        }
        .import-progress-bar {
            height: 14px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }
        .import-progress-fill {
            width: 0;
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(135deg, #0f766e, #0284c7);
            transition: width 0.25s ease;
        }
        .import-progress-meta {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: #475569;
            font-weight: 600;
        }
        .import-worker-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 18px;
        }
        .import-worker-item {
            padding: 14px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
        }
        .import-worker-item strong {
            display: block;
            color: #0f172a;
            margin-bottom: 6px;
        }
        .import-worker-item span {
            display: block;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }
        .import-runtime-note {
            margin-top: 16px;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid transparent;
        }
        .import-runtime-note-success {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }
        .import-runtime-note-warning {
            background: #fff7ed;
            border-color: #fdba74;
            color: #9a3412;
        }
        .import-runtime-note-error {
            background: #fef2f2;
            border-color: #fecaca;
            color: #991b1b;
        }
        .import-error-preview {
            margin-top: 16px;
            padding: 14px 16px;
            border: 1px solid #fecaca;
            border-radius: 12px;
            background: #fffafa;
        }
        .import-error-preview ul {
            margin-top: 10px;
        }
        .muted {
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 992px) {
            .import-hero {
                flex-direction: column;
            }
            .import-actions {
                justify-content: flex-start;
            }
            .import-grid {
                grid-template-columns: 1fr;
            }
            .upload-options,
            .import-progress-summary,
            .import-worker-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="import-page">
        <div class="import-hero">
            <div>
                <h1>Import sản phẩm từ Excel</h1>
                <p>
                    Trang này dùng để tạo mới hoặc cập nhật sản phẩm hàng loạt bằng file Excel. Hệ thống hỗ trợ import
                    5 sheet: sản phẩm, ảnh, biến thể, FAQ và hướng dẫn sử dụng. Nên dùng file export hiện tại làm template
                    rồi sửa dữ liệu trên đó để tránh lệch cột.
                </p>
            </div>

            <div class="import-actions">
                <a href="{{ route('admin.products.index') }}" class="import-link import-link-secondary">
                    Quản lý sản phẩm
                </a>
                <a href="{{ route('admin.products.export-excel') }}" class="import-link import-link-primary">
                    Export toàn bộ sản phẩm
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert-box alert-box-success">
                <strong>Import thành công:</strong> {{ session('success') }}
                @if(session('log_file'))
                    <div style="margin-top:8px;">
                        File log: <code>{{ session('log_file') }}</code><br>
                        <span class="muted">storage/logs/imports/{{ session('log_file') }}</span>
                    </div>
                @endif
            </div>
        @endif

        @if(session('warning'))
            <div class="alert-box alert-box-warning">
                <strong>Import cảnh báo:</strong> {{ session('warning') }}
                @if(session('log_file'))
                    <div style="margin-top:8px;">
                        File log: <code>{{ session('log_file') }}</code><br>
                        <span class="muted">storage/logs/imports/{{ session('log_file') }}</span>
                    </div>
                @endif
            </div>
        @endif

        @if(session('error'))
            <div class="alert-box alert-box-error">
                <strong>Import lỗi:</strong> {{ session('error') }}
                @if(session('log_file'))
                    <div style="margin-top:8px;">
                        File log: <code>{{ session('log_file') }}</code><br>
                        <span class="muted">storage/logs/imports/{{ session('log_file') }}</span>
                    </div>
                @endif
            </div>
        @endif

        <div class="import-note import-note-warning">
            <h3>Schema bắt buộc</h3>
            <ul>
                <li>File import phải đúng workbook export hiện tại với các sheet <code>products</code>, <code>images</code>, <code>faqs</code>, <code>how_tos</code>, <code>variants</code>.</li>
                <li>Header phải khớp tuyệt đối với file export. Nếu đang dùng file cũ, hãy export lại template mới rồi sửa dữ liệu trên đó.</li>
            </ul>
        </div>

        <div class="import-card">
            <h2>Quy trình chuẩn để tạo sản phẩm mới</h2>
            <ol>
                <li>Bấm <strong>Export toàn bộ sản phẩm</strong> để lấy đúng template 5 sheet hệ thống đang dùng.</li>
                <li>Điền sheet <code>products</code> trước. Đây là sheet bắt buộc để tạo sản phẩm.</li>
                <li>Chuẩn bị ảnh nguồn và điền sheet <code>images</code> nếu muốn tạo ảnh cho sản phẩm.</li>
                <li>Nếu sản phẩm có biến thể, điền thêm sheet <code>product_variants</code> và map đúng <code>image_key</code>.</li>
                <li>Nếu cần FAQ hoặc hướng dẫn sử dụng, điền thêm <code>product_faqs</code> và <code>product_how_tos</code>.</li>
                <li>Upload file Excel tại form bên dưới và kiểm tra file log nếu hệ thống báo có lỗi.</li>
            </ol>
        </div>

        <div class="import-grid">
            <div class="import-note import-note-info">
                <h3>Ảnh để ở đâu?</h3>
                <ul>
                    <li>Ảnh nguồn để trong <code>public/clients/assets/img/imports/</code>.</li>
                    <li>Trong Excel, cột <code>local_path</code> nên điền chỉ tên file hoặc path con nằm trong <code>imports</code>, ví dụ <code>ao-polo-nam-xam.webp</code> hoặc <code>yody/ao-polo-nam-xam.webp</code>.</li>
                    <li>Nếu file nguồn là <code>.webp</code> hoặc <code>.avif</code>, hệ thống chỉ copy sang <code>public/clients/assets/img/clothes/</code>, giữ nguyên kích thước và nội dung file.</li>
                    <li>Nếu file nguồn là <code>.jpg</code>, <code>.jpeg</code>, <code>.png</code>, <code>.gif</code>, <code>.bmp</code>..., hệ thống sẽ convert sang <code>.webp</code> và xuất ra kích thước chuẩn <code>400x600</code>.</li>
                    <li>Import Excel chỉ đọc ảnh từ thư mục <code>imports</code>. Nếu ảnh đang nằm ở nơi khác như <code>storage/app/tmp/yody/</code> thì cần tự chuyển vào <code>imports</code> trước khi import.</li>
                </ul>
            </div>

            <div class="import-note import-note-warning">
                <h3>Dữ liệu điền như thế nào?</h3>
                <ul>
                    <li><strong>SKU mới</strong>: tạo sản phẩm mới.</li>
                    <li><strong>SKU trùng</strong>: cập nhật sản phẩm cũ theo SKU đó.</li>
                    <li><code>primary_category_slug</code> và <code>category_slugs</code> phải là slug category đã có sẵn trong DB.</li>
                    <li><code>tag_slugs</code> thực tế đang nhập theo <strong>tên tag</strong>, phân tách bằng dấu phẩy. Hệ thống tự tạo slug tag.</li>
                    <li><code>slug</code> có thể để trống, hệ thống sẽ tự sinh từ tên sản phẩm và tự tránh trùng.</li>
                </ul>
            </div>
        </div>

        <div class="import-note import-note-danger">
            <h3>Lưu ý để tránh mất dữ liệu</h3>
            <ul>
                <li>Sheet <code>product_variants</code> được hiểu là <strong>danh sách biến thể cuối cùng</strong>. Variant cũ dư ra so với file sẽ bị xóa.</li>
                <li>Sheet <code>images</code> dùng để thay bộ ảnh của SKU đó. Nếu cập nhật sản phẩm cũ và muốn thay ảnh, nên chuẩn bị đầy đủ toàn bộ ảnh muốn giữ.</li>
                <li>FAQ được cập nhật theo cặp <code>SKU + question</code>. How-to được cập nhật theo cặp <code>SKU + title</code>.</li>
                <li>Nếu category slug không tồn tại, hệ thống sẽ bỏ qua category đó và ghi vào log lỗi.</li>
            </ul>
        </div>

        <div class="import-card">
            <h2>5 sheet hệ thống đang hỗ trợ</h2>
            <table class="import-table">
                <thead>
                    <tr>
                        <th>Sheet</th>
                        <th>Bắt buộc</th>
                        <th>Mục đích</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>products</code></td>
                        <td>Có</td>
                        <td>Tạo hoặc cập nhật thông tin sản phẩm chính.</td>
                    </tr>
                    <tr>
                        <td><code>images</code></td>
                        <td>Không</td>
                        <td>Import ảnh sản phẩm và tạo key để map sang variant.</td>
                    </tr>
                    <tr>
                        <td><code>product_variants</code></td>
                        <td>Không</td>
                        <td>Tạo hoặc cập nhật biến thể theo SKU sản phẩm cha.</td>
                    </tr>
                    <tr>
                        <td><code>product_faqs</code></td>
                        <td>Không</td>
                        <td>Tạo hoặc cập nhật câu hỏi thường gặp.</td>
                    </tr>
                    <tr>
                        <td><code>product_how_tos</code></td>
                        <td>Không</td>
                        <td>Tạo hoặc cập nhật hướng dẫn sử dụng.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="import-card">
            <h2>Chi tiết từng sheet</h2>

            <details class="import-details" open>
                <summary>Sheet <code>products</code></summary>
                <div class="import-details-body">
                    <p>Các cột đúng thứ tự:</p>
                    <div class="import-code">sku | name | slug | description | short_description | price | sale_price | cost_price | stock_quantity | meta_title | meta_description | meta_keywords | meta_canonical | primary_category_slug | category_slugs | tag_slugs | is_featured | has_variants | created_by | is_active | brand_slug</div>

                    <table class="import-table">
                        <thead>
                            <tr>
                                <th>Cột</th>
                                <th>Ý nghĩa</th>
                                <th>Ví dụ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>sku</code></td>
                                <td>Mã sản phẩm. Dùng để tạo mới hoặc cập nhật.</td>
                                <td><code>AOPOLO-NAM-001</code></td>
                            </tr>
                            <tr>
                                <td><code>name</code></td>
                                <td>Tên sản phẩm.</td>
                                <td><code>Áo polo nam basic</code></td>
                            </tr>
                            <tr>
                                <td><code>slug</code></td>
                                <td>Có thể để trống, hệ thống tự sinh.</td>
                                <td><code>ao-polo-nam-basic</code></td>
                            </tr>
                            <tr>
                                <td><code>primary_category_slug</code></td>
                                <td>Slug category chính đã có sẵn.</td>
                                <td><code>ao-polo-nam</code></td>
                            </tr>
                            <tr>
                                <td><code>category_slugs</code></td>
                                <td>Nhiều slug, phân tách bằng dấu phẩy.</td>
                                <td><code>ao-nam,ao-polo-nam</code></td>
                            </tr>
                            <tr>
                                <td><code>tag_slugs</code></td>
                                <td>Nhập tên tag, không cần nhập slug.</td>
                                <td><code>Hàng mới,Áo polo,Cotton</code></td>
                            </tr>
                            <tr>
                                <td><code>has_variants</code></td>
                                <td><code>1</code> nếu có biến thể, <code>0</code> nếu không.</td>
                                <td><code>1</code></td>
                            </tr>
                            <tr>
                                <td><code>is_featured</code>, <code>is_active</code></td>
                                <td>Dùng <code>1</code> hoặc <code>0</code>.</td>
                                <td><code>1</code></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="import-code">AOPOLO-NAM-001 | Áo polo nam basic | ao-polo-nam-basic | Mô tả dài... | Mô tả ngắn... | 390000 | 331500 | 250000 | 30 | Áo polo nam basic | Polo nam cotton | polo,cotton,nam |  | ao-polo-nam | ao-nam,ao-polo-nam | Hàng mới,Áo polo,Cotton | 1 | 1 | 1 | 1</div>
                </div>
            </details>

            <details class="import-details">
                <summary>Sheet <code>images</code></summary>
                <div class="import-details-body">
                    <p>Các cột đúng thứ tự:</p>
                    <div class="import-code">sku | image_key | local_path | title | notes | alt | is_primary | order</div>

                    <ul>
                        <li><code>image_key</code> phải là key duy nhất trong sheet này, ví dụ <code>IMG_AOPOLO_01</code>.</li>
                        <li><code>local_path</code> nên là tên file trong <code>public/clients/assets/img/imports/</code>.</li>
                        <li>Tên file sau import sẽ giữ nguyên đuôi nếu nguồn là <code>webp/avif</code>; các định dạng khác sẽ đổi sang <code>.webp</code>.</li>
                        <li>Ảnh không phải <code>webp/avif</code> sẽ được chuẩn hóa về kích thước <code>400x600</code> khi import.</li>
                        <li><code>is_primary</code> dùng <code>1</code> cho ảnh đại diện.</li>
                        <li><code>order</code> là thứ tự hiển thị ảnh.</li>
                    </ul>

                    <div class="import-code">AOPOLO-NAM-001 | IMG_AOPOLO_01 | ao-polo-nam-basic-xam.webp | Ảnh chính |  | Áo polo nam màu xám | 1 | 1
AOPOLO-NAM-001 | IMG_AOPOLO_02 | ao-polo-nam-basic-xam-mat-sau.webp | Ảnh sau |  | Áo polo nam mặt sau | 0 | 2</div>
                </div>
            </details>

            <details class="import-details">
                <summary>Sheet <code>product_variants</code></summary>
                <div class="import-details-body">
                    <p>Các cột tối thiểu:</p>
                    <div class="import-code">sku | price | stock_quantity | attributes_color | attributes_size | image_key</div>

                    <ul>
                        <li><code>sku</code> ở đây là SKU của sản phẩm cha, không phải SKU riêng của variant.</li>
                        <li>Hệ thống đọc mọi cột bắt đầu bằng <code>attributes_</code>, ví dụ <code>attributes_color</code>, <code>attributes_size</code>, <code>attributes_material</code>.</li>
                        <li><code>image_key</code> phải trỏ về một key đã có trong sheet <code>images</code>.</li>
                        <li>Nếu một sản phẩm có 5 variant trong file thì sau import, DB sẽ còn đúng 5 variant đó.</li>
                    </ul>

                    <div class="import-code">AOPOLO-NAM-001 | 331500 | 12 | Xám | M | IMG_AOPOLO_01
AOPOLO-NAM-001 | 331500 | 8 | Xám | L | IMG_AOPOLO_01
AOPOLO-NAM-001 | 331500 | 10 | Đen | M | IMG_AOPOLO_03</div>
                </div>
            </details>

            <details class="import-details">
                <summary>Sheet <code>product_faqs</code></summary>
                <div class="import-details-body">
                    <div class="import-code">sku | question | answer | order</div>
                    <div class="import-code">AOPOLO-NAM-001 | Áo có co giãn không? | Chất liệu cotton co giãn nhẹ, mặc thoải mái. | 1</div>
                </div>
            </details>

            <details class="import-details">
                <summary>Sheet <code>product_how_tos</code></summary>
                <div class="import-details-body">
                    <div class="import-code">sku | title | description | steps | supplies</div>
                    <ul>
                        <li><code>steps</code> có thể là JSON hoặc text nhiều dòng.</li>
                        <li><code>supplies</code> có thể là JSON hoặc danh sách phân tách bằng dấu phẩy.</li>
                    </ul>

                    <div class="import-code">AOPOLO-NAM-001 | Hướng dẫn giặt | Giặt riêng màu sáng. | ["Lộn trái áo","Giặt nước lạnh","Phơi nơi thoáng mát"] | ["Nước giặt dịu nhẹ","Móc phơi"]</div>
                </div>
            </details>
        </div>

        <div class="import-note import-note-success">
            <h3>Mẹo làm file nhanh và ít lỗi nhất</h3>
            <ul>
                <li>Dùng file export hiện tại làm mẫu, không tự tạo file mới từ đầu.</li>
                <li>Nhập thử 1-2 sản phẩm trước để xác nhận đúng category slug, ảnh và variant.</li>
                <li>Nếu crawl từ Yody, giữ nguyên file Excel crawler sinh ra rồi import trực tiếp.</li>
                <li>Sau khi import, mở file log nếu có để sửa dần các dòng lỗi thay vì nhập lại toàn bộ từ đầu.</li>
            </ul>
        </div>

        <div class="import-card">
            <h2>Upload file Excel</h2>
            <form
                id="product-import-form"
                action="{{ route('admin.products.import-excel.process') }}"
                method="POST"
                enctype="multipart/form-data"
                class="upload-form"
                data-start-url="{{ route('admin.products.import-excel.start') }}"
                data-process-chunk-url="{{ route('admin.products.import-excel.process-chunk') }}"
                data-progress-url="{{ route('admin.products.import-excel.progress') }}"
                data-cancel-url="{{ route('admin.products.import-excel.cancel') }}"
            >
                @csrf

                <div>
                    <label for="excel_file">Chọn file Excel (.xlsx, .xls)</label>
                    <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls" required data-max-size-mb="50">
                    @error('excel_file')
                        <div style="color:#dc2626; margin-top:6px;">{{ $message }}</div>
                    @enderror
                    <div class="muted" style="margin-top:8px;">
                        Dung lượng tối đa hiện tại: 50MB. Form này ưu tiên import client-side nhiều request nhỏ để tránh timeout.
                    </div>
                </div>

                <div class="upload-options">
                    <div>
                        <label for="import_workers">Số worker song song</label>
                        <input type="number" id="import_workers" name="workers" min="1" max="10" value="4">
                        <div class="muted" style="margin-top:8px;">Khuyến nghị `4` đến `6` worker cho file lớn.</div>
                    </div>

                    <div>
                        <label for="import_chunk_size">Số dòng mỗi chunk</label>
                        <select id="import_chunk_size" name="chunk_size">
                            <option value="25">25 dòng/request</option>
                            <option value="50" selected>50 dòng/request</option>
                            <option value="100">100 dòng/request</option>
                            <option value="150">150 dòng/request</option>
                            <option value="200">200 dòng/request</option>
                        </select>
                        <div class="muted" style="margin-top:8px;">Chunk nhỏ hơn an toàn hơn khi server yếu hoặc dữ liệu nặng.</div>
                    </div>
                </div>

                <div class="upload-actions">
                    <button type="submit" class="upload-submit" id="import-start-btn">Bắt đầu import</button>
                    <button type="button" class="upload-cancel" id="import-cancel-btn" hidden>Hủy import</button>
                </div>
            </form>
        </div>

        <div class="import-card import-progress-card" id="import-progress-card" hidden>
            <h2>Tiến độ import</h2>
            <div class="import-progress-summary">
                <div>
                    <span>Trạng thái</span>
                    <strong id="import-status-text">Chưa bắt đầu</strong>
                </div>
                <div>
                    <span>File</span>
                    <strong id="import-file-name">-</strong>
                </div>
                <div>
                    <span>Tổng dòng</span>
                    <strong id="import-total-rows">0</strong>
                </div>
                <div>
                    <span>Worker</span>
                    <strong id="import-workers-used">0</strong>
                </div>
            </div>

            <div class="import-progress-bar">
                <div class="import-progress-fill" id="import-progress-fill"></div>
            </div>

            <div class="import-progress-meta">
                <span id="import-progress-percent">0%</span>
                <span id="import-progress-count">0 / 0</span>
            </div>

            <div class="import-worker-list" id="import-worker-list"></div>
            <div class="import-runtime-note" id="import-runtime-note" hidden></div>
            <div class="import-error-preview" id="import-error-preview" hidden></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('product-import-form');
            const fileInput = document.getElementById('excel_file');
            const workersInput = document.getElementById('import_workers');
            const chunkSizeInput = document.getElementById('import_chunk_size');
            const startButton = document.getElementById('import-start-btn');
            const cancelButton = document.getElementById('import-cancel-btn');
            const progressCard = document.getElementById('import-progress-card');
            const statusText = document.getElementById('import-status-text');
            const fileNameText = document.getElementById('import-file-name');
            const totalRowsText = document.getElementById('import-total-rows');
            const workersUsedText = document.getElementById('import-workers-used');
            const progressFill = document.getElementById('import-progress-fill');
            const progressPercent = document.getElementById('import-progress-percent');
            const progressCount = document.getElementById('import-progress-count');
            const workerList = document.getElementById('import-worker-list');
            const runtimeNote = document.getElementById('import-runtime-note');
            const errorPreview = document.getElementById('import-error-preview');

            if (!form || !fileInput || !workersInput || !chunkSizeInput) {
                return;
            }

            const maxSizeMb = Number(fileInput.dataset.maxSizeMb || 50);
            const maxBytes = maxSizeMb * 1024 * 1024;
            const numberFormatter = new Intl.NumberFormat('vi-VN');
            const csrfToken = form.querySelector('input[name="_token"]')?.value || '';
            const routes = {
                start: form.dataset.startUrl,
                processChunk: form.dataset.processChunkUrl,
                progress: form.dataset.progressUrl,
                cancel: form.dataset.cancelUrl,
            };
            const state = {
                active: false,
                finished: false,
                cancelRequested: false,
                groupId: '',
                sessionIds: [],
                totalRows: 0,
                workers: 0,
                chunkSize: 50,
                fileName: '',
                workerStats: {},
                lastProgress: null,
            };

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const delay = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));

            const setRuntimeNote = (type, message) => {
                runtimeNote.hidden = false;
                runtimeNote.className = 'import-runtime-note import-runtime-note-' + type;
                runtimeNote.innerHTML = message;
            };

            const clearRuntimeNote = () => {
                runtimeNote.hidden = true;
                runtimeNote.className = 'import-runtime-note';
                runtimeNote.innerHTML = '';
            };

            const renderErrors = (errors = [], logFile = null) => {
                const safeErrors = Array.isArray(errors) ? errors.slice(0, 8) : [];

                if (safeErrors.length === 0 && !logFile) {
                    errorPreview.hidden = true;
                    errorPreview.innerHTML = '';
                    return;
                }

                let html = '';

                if (logFile) {
                    html += `<div><strong>File log:</strong> <code>${escapeHtml(logFile)}</code></div>`;
                    html += `<div class="muted" style="margin-top:6px;">storage/logs/imports/${escapeHtml(logFile)}</div>`;
                }

                if (safeErrors.length > 0) {
                    html += '<ul>';
                    safeErrors.forEach((error) => {
                        const row = error.row ?? error.line ?? 'N/A';
                        const sku = error.sku ?? 'N/A';
                        const message = error.message ?? 'Không có mô tả';
                        html += `<li><strong>SKU ${escapeHtml(sku)}</strong> | dòng ${escapeHtml(row)}: ${escapeHtml(message)}</li>`;
                    });
                    html += '</ul>';
                }

                errorPreview.hidden = false;
                errorPreview.innerHTML = html;
            };

            const renderWorkerStats = () => {
                if (!state.sessionIds.length) {
                    workerList.innerHTML = '';
                    return;
                }

                workerList.innerHTML = state.sessionIds.map((sessionId, index) => {
                    const worker = state.workerStats[sessionId] || {};
                    const processed = numberFormatter.format(worker.processed || 0);
                    const total = numberFormatter.format(worker.total || 0);
                    const status = worker.status || 'Đang chờ';

                    return `
                        <div class="import-worker-item">
                            <strong>Worker ${index + 1}</strong>
                            <span>Trạng thái: ${escapeHtml(status)}</span>
                            <span>Tiến độ: ${processed} / ${total}</span>
                            <span>Chunk hiện tại: ${escapeHtml(worker.chunk ?? 0)}</span>
                        </div>
                    `;
                }).join('');
            };

            const syncButtons = () => {
                const locked = state.active && !state.finished;

                startButton.disabled = locked;
                cancelButton.hidden = !locked;
                fileInput.disabled = locked;
                workersInput.disabled = locked;
                chunkSizeInput.disabled = locked;
            };

            const updateProgress = (processed, total, progress, status) => {
                const safeTotal = Number(total || 0);
                const safeProcessed = Number(processed || 0);
                const safeProgress = Math.max(0, Math.min(100, Number(progress || 0)));

                statusText.textContent = status || 'Đang xử lý';
                totalRowsText.textContent = numberFormatter.format(safeTotal);
                workersUsedText.textContent = numberFormatter.format(state.workers);
                fileNameText.textContent = state.fileName || '-';
                progressFill.style.width = `${safeProgress}%`;
                progressPercent.textContent = `${safeProgress.toFixed(2)}%`;
                progressCount.textContent = `${numberFormatter.format(safeProcessed)} / ${numberFormatter.format(safeTotal)}`;
            };

            const extractMessage = (payload, fallback = 'Có lỗi xảy ra.') => {
                if (payload?.message) {
                    return payload.message;
                }

                if (payload?.errors && typeof payload.errors === 'object') {
                    const firstError = Object.values(payload.errors).flat()[0];
                    if (firstError) {
                        return firstError;
                    }
                }

                return fallback;
            };

            const requestJson = async (url, options = {}) => {
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(options.headers || {}),
                    },
                    ...options,
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(extractMessage(payload, `HTTP ${response.status}`));
                }

                return payload;
            };

            const postForm = (url, formData) => requestJson(url, {
                method: 'POST',
                body: formData,
            });

            const postParams = (url, params) => {
                const body = new URLSearchParams();
                Object.entries(params).forEach(([key, value]) => {
                    if (value !== null && value !== undefined) {
                        body.append(key, String(value));
                    }
                });

                if (csrfToken && !body.has('_token')) {
                    body.append('_token', csrfToken);
                }

                return requestJson(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    },
                    body: body.toString(),
                });
            };

            const getProgress = (sessionId) => {
                const url = new URL(routes.progress, window.location.origin);
                url.searchParams.set('session_id', sessionId);

                return requestJson(url.toString(), {
                    method: 'GET',
                });
            };

            const refreshProgress = async () => {
                if (!state.sessionIds.length) {
                    return null;
                }

                const payload = await getProgress(state.sessionIds[0]);
                state.lastProgress = payload;
                updateProgress(payload.processed, payload.total, payload.progress, payload.status);
                renderErrors(payload.errors || [], payload.log_file || null);

                if (payload.cancelled) {
                    state.active = false;
                    state.finished = true;
                    setRuntimeNote('warning', 'Import đã được hủy.');
                } else if (payload.status === 'error') {
                    state.active = false;
                    state.finished = true;
                    setRuntimeNote('error', escapeHtml(payload.error || 'Import thất bại.'));
                } else if (payload.completed) {
                    state.active = false;
                    state.finished = true;

                    if ((payload.errors_count || 0) > 0) {
                        setRuntimeNote(
                            'warning',
                            `Import hoàn thành nhưng có ${numberFormatter.format(payload.errors_count)} lỗi. Kiểm tra preview bên dưới hoặc file log.`
                        );
                    } else {
                        setRuntimeNote('success', 'Import hoàn thành. Dữ liệu đã được đồng bộ.');
                    }
                }

                syncButtons();
                return payload;
            };

            const progressLoop = async () => {
                while (state.active && !state.finished) {
                    try {
                        await refreshProgress();
                    } catch (error) {
                        if (!state.cancelRequested) {
                            setRuntimeNote('error', escapeHtml(error.message || 'Không lấy được tiến độ import.'));
                        }
                    }

                    if (!state.active || state.finished) {
                        break;
                    }

                    await delay(1200);
                }
            };

            const waitUntilFinished = async () => {
                for (let attempt = 0; attempt < 180; attempt += 1) {
                    if (state.finished || !state.active) {
                        return state.lastProgress;
                    }

                    await delay(1000);
                }

                throw new Error('Import đang xử lý quá lâu. Kiểm tra lại queue request hoặc thử giảm worker/chunk size.');
            };

            const runWorker = async (sessionId) => {
                let chunk = 0;

                while (!state.cancelRequested) {
                    let payload;

                    try {
                        payload = await postParams(routes.processChunk, {
                            session_id: sessionId,
                            chunk,
                            chunk_size: state.chunkSize,
                        });
                    } catch (error) {
                        if (state.cancelRequested) {
                            return null;
                        }

                        throw error;
                    }

                    state.workerStats[sessionId] = {
                        processed: payload.processed || 0,
                        total: payload.total || 0,
                        chunk,
                        status: payload.completed ? 'Hoàn thành' : 'Đang chạy',
                    };

                    renderWorkerStats();

                    if (payload.completed) {
                        return payload;
                    }

                    chunk += 1;
                }

                state.workerStats[sessionId] = {
                    ...(state.workerStats[sessionId] || {}),
                    status: 'Đã hủy',
                };
                renderWorkerStats();
                return null;
            };

            const resetState = () => {
                state.active = false;
                state.finished = false;
                state.cancelRequested = false;
                state.groupId = '';
                state.sessionIds = [];
                state.totalRows = 0;
                state.workers = 0;
                state.chunkSize = 50;
                state.fileName = '';
                state.workerStats = {};
                state.lastProgress = null;
            };

            fileInput.addEventListener('change', function () {
                const file = this.files && this.files[0] ? this.files[0] : null;
                if (!file) {
                    return;
                }

                if (file.size > maxBytes) {
                    window.alert(`File quá lớn. Vui lòng chọn file nhỏ hơn ${maxSizeMb}MB.`);
                    this.value = '';
                }
            });

            cancelButton.addEventListener('click', async function () {
                if (!state.groupId || state.cancelRequested) {
                    return;
                }

                state.cancelRequested = true;

                try {
                    await postParams(routes.cancel, {
                        group_id: state.groupId,
                    });

                    state.active = false;
                    state.finished = true;
                    updateProgress(0, state.totalRows, 0, 'cancelled');
                    setRuntimeNote('warning', 'Import đã được hủy.');
                    renderWorkerStats();
                } catch (error) {
                    state.cancelRequested = false;
                    setRuntimeNote('error', escapeHtml(error.message || 'Không thể hủy import.'));
                }

                syncButtons();
            });

            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                if (!file) {
                    return;
                }

                if (file.size > maxBytes) {
                    window.alert(`File quá lớn. Vui lòng chọn file nhỏ hơn ${maxSizeMb}MB.`);
                    fileInput.value = '';
                    return;
                }

                const workers = Math.max(1, Math.min(10, Number(workersInput.value || 4)));
                const chunkSize = Math.max(1, Math.min(500, Number(chunkSizeInput.value || 50)));
                const formData = new FormData();

                formData.append('_token', csrfToken);
                formData.append('excel_file', file);
                formData.append('workers', workers);

                resetState();
                state.active = true;
                state.chunkSize = chunkSize;
                state.fileName = file.name;

                progressCard.hidden = false;
                fileNameText.textContent = file.name;
                workersUsedText.textContent = numberFormatter.format(workers);
                updateProgress(0, 0, 0, 'Đang khởi tạo');
                workerList.innerHTML = '';
                errorPreview.hidden = true;
                errorPreview.innerHTML = '';
                clearRuntimeNote();
                syncButtons();

                try {
                    const startPayload = await postForm(routes.start, formData);

                    state.groupId = startPayload.group_id || '';
                    state.sessionIds = Array.isArray(startPayload.session_ids) ? startPayload.session_ids : [];
                    state.totalRows = Number(startPayload.total_rows || 0);
                    state.workers = Number(startPayload.workers || workers);

                    if (!state.sessionIds.length) {
                        throw new Error('Server không trả về session worker để tiếp tục import.');
                    }

                    state.sessionIds.forEach((sessionId) => {
                        state.workerStats[sessionId] = {
                            processed: 0,
                            total: 0,
                            chunk: 0,
                            status: 'Đang chờ',
                        };
                    });

                    updateProgress(0, state.totalRows, 0, 'Đang xử lý');
                    renderWorkerStats();
                    syncButtons();

                    const workersPromise = Promise.allSettled(
                        state.sessionIds.map((sessionId) => runWorker(sessionId))
                    );

                    progressLoop();

                    const workerResults = await workersPromise;
                    const failedWorker = workerResults.find((result) => result.status === 'rejected');

                    if (failedWorker?.reason) {
                        throw failedWorker.reason;
                    }

                    await waitUntilFinished();
                    await refreshProgress();
                } catch (error) {
                    state.active = false;
                    state.finished = true;
                    setRuntimeNote('error', escapeHtml(error.message || 'Import thất bại.'));
                    syncButtons();
                }
            });
        });
    </script>
@endpush
