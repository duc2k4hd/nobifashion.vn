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
            <form id="product-import-form" action="{{ route('admin.products.import-excel.process') }}" method="POST" enctype="multipart/form-data" class="upload-form">
                @csrf

                <div>
                    <label for="excel_file">Chọn file Excel (.xlsx, .xls)</label>
                    <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls" required data-max-size-mb="50">
                    @error('excel_file')
                        <div style="color:#dc2626; margin-top:6px;">{{ $message }}</div>
                    @enderror
                    <div class="muted" style="margin-top:8px;">
                        Dung lượng tối đa hiện tại: 50MB. Nên dùng đúng file export/template của hệ thống.
                    </div>
                </div>

                <button type="submit" class="upload-submit">Bắt đầu import</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('product-import-form');
            const fileInput = document.getElementById('excel_file');

            if (!form || !fileInput) {
                return;
            }

            const maxSizeMb = Number(fileInput.dataset.maxSizeMb || 50);
            const maxBytes = maxSizeMb * 1024 * 1024;

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

            form.addEventListener('submit', function (event) {
                const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                if (!file) {
                    return;
                }

                if (file.size > maxBytes) {
                    event.preventDefault();
                    window.alert(`File quá lớn. Vui lòng chọn file nhỏ hơn ${maxSizeMb}MB.`);
                    fileInput.value = '';
                }
            });
        });
    </script>
@endpush
