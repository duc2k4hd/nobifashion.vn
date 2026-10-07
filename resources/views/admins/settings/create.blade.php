@extends('admins.layouts.master')

@section('title', 'Tạo cài đặt mới')
@section('page-title', 'Tạo cài đặt mới')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/settings-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .create-setting-container {
            margin: 0 auto;
        }

        /* Header */
        .page-header-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        .header-title-wrap h2 {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header-title-wrap p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        /* Two Column Layout */
        .setting-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 20px;
            align-items: start;
        }
        @media (max-width: 992px) {
            .setting-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Clean Card Design */
        .setting-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .setting-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .setting-card-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Form elements */
        .form-group {
            margin-bottom: 16px;
        }
        .form-group:last-child {
            margin-bottom: 0;
        }
        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
            display: block;
        }
        .form-label .required {
            color: #ef4444;
        }
        .form-hint {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
            display: block;
            line-height: 1.4;
        }
        .clean-input,
        .clean-select,
        .clean-textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 13px;
            color: #1e293b;
            background-color: #ffffff;
            transition: all 0.15s ease;
        }
        .clean-input:focus,
        .clean-select:focus,
        .clean-textarea:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
        }

        .category-picker-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
        }

        /* App Alerts */
        .app-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 18px;
        }
        .app-alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
    </style>
@endpush

@section('content')
<div class="create-setting-container">

    @if($errors->any())
        <div class="app-alert app-alert-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong>Vui lòng kiểm tra lại dữ liệu:</strong>
                <ul class="mb-0 ps-3 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.settings.store') }}" method="POST">
        @csrf

        {{-- Page Header & Actions --}}
        <div class="page-header-box">
            <div class="header-title-wrap">
                <h2>
                    <i class="fa-solid fa-plus text-secondary"></i>
                    <span>Tạo cài đặt mới</span>
                </h2>
                <p>Khai báo cấu hình mới cho hệ thống hoặc tùy biến giao diện website.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại
                </a>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i class="fa-solid fa-check"></i> Lưu cài đặt
                </button>
            </div>
        </div>

        <div class="setting-grid">
            {{-- Main Column (Left) --}}
            <div>
                {{-- Card: Giá trị cấu hình --}}
                <div class="setting-card">
                    <div class="setting-card-header">
                        <h3 class="setting-card-title">
                            <i class="fa-solid fa-pen-nib text-secondary"></i>
                            Giá trị cài đặt
                        </h3>
                    </div>

                    {{-- Category Picker (Tự động hiển thị khi nhập key là product_recommen) --}}
                    <div id="category-select-wrapper" style="display: none;" class="category-picker-card mb-3">
                        <label class="form-label" for="category-select-input">
                            <i class="fa-solid fa-folder-tree text-primary me-1"></i>
                            Chọn danh mục hiển thị sản phẩm gợi ý
                        </label>
                        <select id="category-select-input" class="clean-select" style="font-size: 13.5px; padding: 10px 12px;">
                            <option value="">-- Mặc định (Tự động cân bằng 50% Thời trang nam & 50% Thời trang nữ) --</option>
                            @if(isset($categories))
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ (string)old('value') === (string)$cat->id ? 'selected' : '' }}>
                                        {{ $cat->parent_id ? '　↳ ' : '📁 ' }}{{ $cat->name }} (ID: {{ $cat->id }} | Slug: {{ $cat->slug }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <span class="form-hint mt-2">
                            Hệ thống sẽ lưu <strong>ID danh mục</strong> và tải sản phẩm gợi ý thuộc danh mục này cùng danh mục con.
                        </span>
                    </div>

                    {{-- Textarea giá trị thông thường --}}
                    <div class="form-group" id="generic-value-wrapper">
                        <label class="form-label" for="setting-value">Nội dung giá trị</label>
                        <textarea id="setting-value" name="value" rows="6" class="clean-textarea"
                                  placeholder="Nhập giá trị cài đặt...">{{ old('value') }}</textarea>
                        <span class="form-hint">Nhập giá trị phù hợp với kiểu dữ liệu bạn đã chọn.</span>
                    </div>
                </div>

                {{-- Card: Nhãn & Mô tả --}}
                <div class="setting-card">
                    <div class="setting-card-header">
                        <h3 class="setting-card-title">
                            <i class="fa-solid fa-circle-info text-secondary"></i>
                            Thông tin nhận diện
                        </h3>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="setting-label">Nhãn hiển thị (Label)</label>
                        <input type="text" id="setting-label" name="label" class="clean-input"
                               placeholder="Ví dụ: Tên website, Địa chỉ showroom..."
                               value="{{ old('label') }}">
                        <span class="form-hint">Tên gợi nhớ thân thiện hiển thị trên giao diện quản trị.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="setting-description">Mô tả chi tiết</label>
                        <textarea id="setting-description" name="description" rows="3" class="clean-textarea"
                                  placeholder="Ghi chú mục đích sử dụng cấu hình này...">{{ old('description') }}</textarea>
                        <span class="form-hint">Mô tả giúp phân biệt mục đích sử dụng của cấu hình.</span>
                    </div>
                </div>
            </div>

            {{-- Sidebar Column (Right) --}}
            <div>
                {{-- Card: Thuộc tính hệ thống --}}
                <div class="setting-card">
                    <div class="setting-card-header">
                        <h3 class="setting-card-title">
                            <i class="fa-solid fa-gear text-secondary"></i>
                            Thuộc tính
                        </h3>
                    </div>

                    {{-- Key --}}
                    <div class="form-group">
                        <label class="form-label" for="setting-key-input">
                            Mã Key <span class="required">*</span>
                        </label>
                        <input type="text" id="setting-key-input" name="key" class="clean-input"
                               placeholder="Ví dụ: site_phone, banner_home..."
                               value="{{ old('key') }}" required>
                        <span class="form-hint">Duy nhất, chữ thường, không dấu, dùng dấu gạch dưới (_).</span>
                    </div>

                    {{-- Nhóm cấu hình --}}
                    <div class="form-group">
                        <label class="form-label" for="setting-group-input">Nhóm cấu hình</label>
                        <input type="text" id="setting-group-input" name="group" list="setting-groups" class="clean-input"
                               placeholder="Chọn hoặc nhập nhóm..."
                               value="{{ old('group', 'general') }}">
                        <datalist id="setting-groups">
                            @foreach($groups as $grp)
                                <option value="{{ $grp }}">{{ ucfirst($grp) }}</option>
                            @endforeach
                            <option value="general">Chung (General)</option>
                            <option value="product">Sản phẩm (Product)</option>
                            <option value="contact">Liên hệ (Contact)</option>
                            <option value="seo">SEO & Marketing</option>
                            <option value="system">Hệ thống (System)</option>
                        </datalist>
                    </div>

                    {{-- Kiểu dữ liệu --}}
                    <div class="form-group">
                        <label class="form-label" for="setting-type-input">
                            Kiểu dữ liệu <span class="required">*</span>
                        </label>
                        <select id="setting-type-input" name="type" class="clean-select" required>
                            @foreach($types as $type)
                                <option value="{{ $type }}" {{ old('type', 'string') === $type ? 'selected' : '' }}>
                                    {{ ucfirst($type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Phạm vi (Public / Private) --}}
                    <div class="form-group">
                        <label class="form-label" for="setting-is-public-input">Phạm vi hiển thị</label>
                        <select id="setting-is-public-input" name="is_public" class="clean-select">
                            <option value="1" {{ old('is_public', true) ? 'selected' : '' }}>
                                Công khai (Frontend có thể đọc)
                            </option>
                            <option value="0" {{ old('is_public', true) ? '' : 'selected' }}>
                                Nội bộ (Chỉ dùng Backend)
                            </option>
                        </select>
                    </div>

                    {{-- Bắt buộc --}}
                    <div class="form-group">
                        <label class="form-label" for="setting-is-required-input">Yêu cầu bắt buộc</label>
                        <select id="setting-is-required-input" name="is_required" class="clean-select">
                            <option value="0" {{ old('is_required') ? '' : 'selected' }}>Không</option>
                            <option value="1" {{ old('is_required') ? 'selected' : '' }}>Bắt buộc</option>
                        </select>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2 py-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Lưu cài đặt</span>
                    </button>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-1 py-2">
                        <i class="fa-solid fa-arrow-left"></i> Quay lại danh sách
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const keyInput = document.getElementById('setting-key-input');
        const catWrap = document.getElementById('category-select-wrapper');
        const catSelect = document.getElementById('category-select-input');
        const valWrap = document.getElementById('generic-value-wrapper');
        const valTextarea = document.getElementById('setting-value');

        function checkKey() {
            if (keyInput && keyInput.value.trim().toLowerCase() === 'product_recommen') {
                catWrap.style.display = 'block';
                valWrap.style.display = 'none';
                catSelect.name = 'value';
                valTextarea.removeAttribute('name');
            } else {
                catWrap.style.display = 'none';
                valWrap.style.display = 'block';
                catSelect.removeAttribute('name');
                valTextarea.name = 'value';
            }
        }

        if (keyInput) {
            keyInput.addEventListener('input', checkKey);
            checkKey();
        }
    });
</script>
@endsection
