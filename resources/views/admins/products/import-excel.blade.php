@extends('admins.layouts.master')

@section('title', 'Import Sản Phẩm từ Excel')
@section('page-title', '📥 Import Excel')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/imports-excel.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 6px;
            background: #fafafa;
            cursor: pointer;
        }
        input[type="file"]:hover {
            border-color: #007bff;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .info-box h3 {
            margin-bottom: 10px;
            color: #007bff;
        }
        .info-box ul {
            margin-left: 20px;
            color: #555;
        }
        .info-box li {
            margin-bottom: 5px;
        }
        .sheet-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .sheet-info h4 {
            color: #333;
            margin-bottom: 8px;
        }
        .sheet-info code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 13px;
        }
    </style>
@endpush

@section('content')
    <div>
        <div style="display:flex; justify-content: space-between; align-items:center; gap:15px;">
            <div>
                <h1>📥 Import Sản Phẩm từ Excel</h1>
                <p class="subtitle">Upload file Excel để import sản phẩm, hình ảnh, biến thể, FAQs và hướng dẫn</p>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
                <a href="{{ route('admin.products.index') }}"
                   style="
                       display:inline-block;
                       padding:10px 18px;
                       background:#475569;
                       color:#fff;
                       border-radius:8px;
                       text-decoration:none;
                       font-weight:600;
                   ">
                    ↩️ Quản lý sản phẩm
                </a>
                <a href="{{ route('admin.products.export-excel') }}"
                   style="
                       display: inline-block;
                       padding: 10px 18px;
                       background: linear-gradient(135deg, #0f766e, #0ea5e9);
                       color: #fff;
                       border-radius: 8px;
                       text-decoration: none;
                       font-size: 14px;
                       font-weight: 600;
                       box-shadow: 0 3px 8px rgba(0,0,0,0.15);
                       transition: 0.25s ease;
                   "
                   onmouseover="this.style.background='linear-gradient(135deg,#2a5298,#1e3c72)'"
                   onmouseout="this.style.background='linear-gradient(135deg,#1e3c72,#2a5298)'"
                >
                    ⬇️ Export toàn bộ sản phẩm
                </a>
            </div>
        </div>
        

        @if(session('success'))
            <div class="alert alert-success">
                ✅ {{ session('success') }}
                @if(session('log_file'))
                    <br><br>
                    <strong>📄 File log lỗi:</strong> 
                    <code>{{ session('log_file') }}</code><br>
                    <small>Đường dẫn: <code>storage/logs/imports/{{ session('log_file') }}</code></small>
                @endif
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                ❌ {{ session('error') }}
                @if(session('log_file'))
                    <br><br>
                    <strong>📄 File log lỗi:</strong> 
                    <code>{{ session('log_file') }}</code><br>
                    <small>Đường dẫn: <code>storage/logs/imports/{{ session('log_file') }}</code></small>
                @endif
            </div>
        @endif

        <div class="info-box">
            <h3>📋 Cấu trúc file Excel yêu cầu:</h3>
            <div class="sheet-info">
                <h4>Sheet 1: <code>products</code></h4>
                <p>sku | name | slug | description | short_description | price | sale_price | cost_price | stock_quantity | meta_title | meta_description | meta_keywords | meta_canonical | primary_category_slug | category_slugs | tag_slugs | is_featured | has_variants | created_by | is_active</p>
                <p style="margin-top: 8px; color:#666; font-size:13px;">
                    <strong>📝 Tag gợi ý:</strong> Điền <strong>tên tag</strong> (không phải slug) và cách nhau bởi dấu phẩy.
                    Hệ thống sẽ tự tạo slug, thêm tag mới nếu chưa có và map sang <code>tag_ids</code>.
                </p>
            </div>
            <div class="sheet-info">
                <h4>Sheet 2: <code>images</code> (tùy chọn)</h4>
                <p>sku | image_key | local_path | title | notes | alt | is_primary | order</p>
                <p style="margin-top: 8px; color: #666; font-size: 13px;">
                    <strong>📝 Lưu ý cột <code>local_path</code>:</strong><br>
                    • <strong>Cách 1 (Khuyến nghị):</strong> Chỉ tên file, ví dụ: <code>cam-ket-chinh-hang-09fuv9e0rug.jpg</code><br>
                    • <strong>Cách 2:</strong> Đường dẫn tương đối, ví dụ: <code>imports/cam-ket-chinh-hang-09fuv9e0rug.jpg</code><br>
                    • <strong>Cách 3:</strong> Đường dẫn tuyệt đối đầy đủ<br>
                    <small>→ Ảnh sẽ được tìm trong folder <code>public/clients/assets/img/imports/</code> và copy sang <code>clothes/</code></small>
                </p>
            </div>
            <div class="sheet-info">
                <h4>Sheet 3: <code>product_variants</code> (tùy chọn)</h4>
                <p>sku | price | stock_quantity | attributes_color | attributes_size | image_key</p>
            </div>
            <div class="sheet-info">
                <h4>Sheet 4: <code>product_faqs</code> (tùy chọn)</h4>
                <p>sku | question | answer | order</p>
            </div>
            <div class="sheet-info">
                <h4>Sheet 5: <code>product_how_tos</code> (tùy chọn)</h4>
                <p>sku | title | description | steps | supplies</p>
            </div>
        </div>
        <div class="info-box" style="background: #fff3cd; border-left-color: #ffc107; margin-top: 20px;">
            <h3>⚠️ Lưu ý quan trọng:</h3>
            <p style="margin: 0; color: #856404;">
                <strong>Danh mục (Categories):</strong> Phải được tạo trước trong hệ thống. Sau đó lấy <code>slug</code> của danh mục để điền vào cột <code>primary_category_slug</code> và <code>category_slugs</code> trong Sheet 1 (products).<br>
                <small>→ Nếu slug không tồn tại, hệ thống sẽ bỏ qua và ghi vào log lỗi.</small>
            </p>
        </div>

        <form action="{{ route('admin.products.import-excel.process') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="excel_file">Chọn file Excel (.xlsx, .xls)</label>
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls" required>
                @error('excel_file')
                    <div style="color: #dc3545; margin-top: 5px;">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary">🚀 Bắt đầu Import</button>
        </form>
    </div>
@endsection

