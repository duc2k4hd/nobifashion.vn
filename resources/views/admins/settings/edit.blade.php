@extends('admins.layouts.master')

@section('title', 'Chỉnh sửa cài đặt: ' . ($setting->label ?: $setting->key))
@section('page-title', 'Chỉnh sửa cài đặt')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/settings-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .edit-setting-container {
            margin: 0 auto;
        }

        /* Header & Breadcrumb */
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
        .header-key-badge {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            background: #f1f5f9;
            color: #334155;
            padding: 3px 8px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            font-weight: 500;
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
        .clean-input[readonly],
        .clean-input:disabled {
            background-color: #f8fafc;
            color: #64748b;
            cursor: not-allowed;
            border-color: #e2e8f0;
        }

        .code-textarea {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12.5px;
            line-height: 1.5;
            background-color: #fafbfc;
        }

        /* System Protection Banner */
        .system-banner {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #3b82f6;
            border-radius: 6px;
            padding: 12px 16px;
            font-size: 13px;
            color: #334155;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
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
        .app-alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .app-alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Category Select Box */
        .category-picker-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
        }
    </style>
@endpush

@section('content')
<div class="edit-setting-container">

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="app-alert app-alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="app-alert app-alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

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

    @php
        $isProtected = in_array($setting->key, $protectedKeys, true);
        $canDelete = !$isProtected || $isSuperAdmin;
    @endphp

    {{-- System Protected Banner --}}
    @if($isProtected)
        <div class="system-banner">
            <i class="fa-solid fa-shield-halved text-primary fs-5"></i>
            <div>
                <strong>Cài đặt hệ thống cốt lõi:</strong> Mã key được cố định để đảm bảo hệ thống vận hành ổn định.
                @if(!$isSuperAdmin)
                    <span class="text-danger ms-1">(Không được phép xoá — Chỉ tài khoản <code>admin@gmail.com</code> mới có quyền xoá).</span>
                @else
                    <span class="text-primary ms-1">(Bạn đang đăng nhập với quyền <code>admin@gmail.com</code>).</span>
                @endif
            </div>
        </div>
    @endif

    <form action="{{ route('admin.settings.update', $setting) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Page Header & Actions --}}
        <div class="page-header-box">
            <div class="header-title-wrap">
                <h2>
                    <i class="fa-solid fa-sliders text-secondary"></i>
                    <span>{{ $setting->label ?: $setting->key }}</span>
                    <span class="header-key-badge">{{ $setting->key }}</span>
                </h2>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại
                </a>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i class="fa-solid fa-check"></i> Lưu thay đổi
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
                        <span class="badge bg-light text-secondary border">Kiểu: {{ ucfirst($setting->type) }}</span>
                    </div>

                    {{-- Cấu hình chuyên biệt: product_recommen --}}
                    @if($setting->key === 'product_recommen')
                        <div class="category-picker-card">
                            <label class="form-label" for="category-recommend-select">
                                <i class="fa-solid fa-folder-tree text-primary me-1"></i>
                                Chọn danh mục hiển thị sản phẩm gợi ý
                            </label>
                            <select id="category-recommend-select" name="value" class="clean-select" style="font-size: 13.5px; padding: 10px 12px;">
                                <option value="">-- Mặc định (Tự động cân bằng 50% Thời trang nam & 50% Thời trang nữ) --</option>
                                @if(isset($categories))
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ (string)old('value', $setting->value) === (string)$cat->id ? 'selected' : '' }}>
                                            {{ $cat->parent_id ? '　↳ ' : '📁 ' }}{{ $cat->name }} (ID: {{ $cat->id }} | Slug: {{ $cat->slug }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="form-hint mt-2">
                                <strong>Nguyên tắc hoạt động:</strong> Hệ thống lưu <strong>ID danh mục</strong> và sẽ hiển thị các sản phẩm thuộc danh mục này cùng toàn bộ danh mục con của nó trên các trang Giỏ hàng, Giới thiệu, Liên hệ... Nếu không chọn (để mặc định) hoặc danh mục không hợp lệ, hệ thống tự động tải <strong>50% thời trang nam</strong> và <strong>50% thời trang nữ</strong> với tốc độ siêu tốc.
                            </div>
                        </div>

                    {{-- Cấu hình kiểu boolean --}}
                    @elseif($setting->type === 'boolean' || $setting->key === 'maintenance_mode')
                        <div class="form-group">
                            <label class="form-label">Trạng thái kích hoạt</label>
                            <div class="d-flex gap-3 mt-2">
                                <label class="d-flex align-items-center gap-2 p-2 px-3 border rounded cursor-pointer bg-white">
                                    <input type="radio" name="value" value="1" {{ old('value', $setting->value) == '1' || old('value', $setting->value) === true ? 'checked' : '' }}>
                                    <span class="text-success fw-semibold"><i class="fa-solid fa-check me-1"></i> Bật / Kích hoạt</span>
                                </label>
                                <label class="d-flex align-items-center gap-2 p-2 px-3 border rounded cursor-pointer bg-white">
                                    <input type="radio" name="value" value="0" {{ old('value', $setting->value) == '0' || old('value', $setting->value) === false || empty($setting->value) ? 'checked' : '' }}>
                                    <span class="text-secondary fw-semibold"><i class="fa-solid fa-xmark me-1"></i> Tắt / Vô hiệu hóa</span>
                                </label>
                            </div>
                            <span class="form-hint">Chọn trạng thái bật hoặc tắt cho cấu hình này.</span>
                        </div>

                    {{-- Cấu hình kiểu hình ảnh --}}
                    @elseif($setting->type === 'image' || in_array($setting->key, ['site_logo', 'site_favicon', 'site_banner', 'dmca_logo']))
                        <div class="form-group">
                            <label class="form-label" for="setting-value-img">Đường dẫn hình ảnh (URL hoặc đường dẫn tương đối)</label>
                            <input type="text" id="setting-value-img" name="value" class="clean-input"
                                   placeholder="Ví dụ: /clients/assets/img/logo.png hoặc https://..."
                                   value="{{ old('value', $setting->value) }}"
                                   oninput="updateImagePreview(this.value)">
                            <span class="form-hint">Nhập đường dẫn ảnh từ thư mục public hoặc link ảnh ngoài.</span>

                            {{-- Image Preview Box --}}
                            <div class="mt-3 p-3 border rounded bg-light" style="max-width: 360px;">
                                <div class="text-muted small mb-2 fw-semibold">Xem trước ảnh:</div>
                                <div style="min-height: 80px; display: flex; align-items: center; justify-content: center; background: #fff; border: 1px dashed #cbd5e1; border-radius: 6px; padding: 10px;">
                                    <img id="image-preview" src="{{ $setting->value ? asset($setting->value) : '' }}"
                                         alt="Preview" style="max-height: 70px; max-width: 100%; object-fit: contain; {{ empty($setting->value) ? 'display:none;' : '' }}">
                                    <span id="image-preview-empty" class="text-muted small" style="{{ !empty($setting->value) ? 'display:none;' : '' }}">
                                        Chưa có ảnh xem trước
                                    </span>
                                </div>
                            </div>
                        </div>

                    {{-- Cấu hình mã script / Textarea nhiều dòng --}}
                    @elseif(in_array($setting->type, ['text', 'textarea', 'json']) || in_array($setting->key, ['google_analytics', 'google_search_console', 'google_tag_header', 'google_tag_body', 'custom_css', 'custom_js', 'site_description']))
                        <div class="form-group">
                            <label class="form-label" for="setting-value-text">Nội dung giá trị</label>
                            <textarea id="setting-value-text" name="value" rows="8"
                                      class="clean-textarea {{ in_array($setting->key, ['google_analytics', 'google_search_console', 'google_tag_header', 'google_tag_body', 'custom_css', 'custom_js']) ? 'code-textarea' : '' }}"
                                      placeholder="Nhập nội dung cấu hình...">{{ old('value', $setting->value) }}</textarea>
                            <span class="form-hint">
                                @if(in_array($setting->key, ['google_analytics', 'google_search_console', 'google_tag_header', 'google_tag_body']))
                                    Mã code/script sẽ được chèn trực tiếp vào phần thẻ tương ứng trong trang web.
                                @else
                                    Nhập nội dung tương ứng theo đúng định dạng.
                                @endif
                            </span>
                        </div>

                    {{-- Cấu hình dạng chuỗi / số thông thường --}}
                    @else
                        <div class="form-group">
                            <label class="form-label" for="setting-value-generic">Giá trị cấu hình</label>
                            <input type="{{ in_array($setting->type, ['integer', 'number']) ? 'number' : ($setting->type === 'email' ? 'email' : 'text') }}"
                                   id="setting-value-generic" name="value" class="clean-input"
                                   value="{{ old('value', $setting->value) }}"
                                   placeholder="Nhập giá trị...">
                            <span class="form-hint">Kiểu dữ liệu dự kiến: {{ ucfirst($setting->type) }}</span>
                        </div>
                    @endif
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
                               placeholder="Ví dụ: Tên website, Địa chỉ liên hệ..."
                               value="{{ old('label', $setting->label) }}">
                        <span class="form-hint">Tên gợi nhớ thân thiện hiển thị trên giao diện quản trị.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="setting-description">Mô tả chi tiết</label>
                        <textarea id="setting-description" name="description" rows="3" class="clean-textarea"
                                  placeholder="Ghi chú mục đích sử dụng hoặc vị trí hiển thị của cài đặt này...">{{ old('description', $setting->description) }}</textarea>
                        <span class="form-hint">Mô tả giúp các quản trị viên khác hiểu rõ ý nghĩa của cấu hình này.</span>
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

                    {{-- Key (Mã định danh) --}}
                    <div class="form-group">
                        <label class="form-label" for="setting-key">
                            Mã Key <span class="required">*</span>
                        </label>
                        @if($isProtected)
                            <input type="text" class="clean-input" value="{{ $setting->key }}" disabled readonly>
                            <input type="hidden" name="key" value="{{ $setting->key }}">
                            <span class="form-hint text-primary">
                                <i class="fa-solid fa-lock me-1"></i> Mã key hệ thống được bảo vệ cố định.
                            </span>
                        @else
                            <input type="text" id="setting-key" name="key" class="clean-input"
                                   value="{{ old('key', $setting->key) }}" required>
                            <span class="form-hint">Duy nhất, chữ thường, không dấu, dùng dấu gạch dưới.</span>
                        @endif
                    </div>

                    {{-- Nhóm cấu hình --}}
                    <div class="form-group">
                        <label class="form-label" for="setting-group">Nhóm cấu hình</label>
                        <input type="text" id="setting-group" name="group" list="setting-groups" class="clean-input"
                               value="{{ old('group', $setting->group ?: 'general') }}">
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
                        <span class="form-hint">Phân loại cấu hình để dễ tìm kiếm và lọc.</span>
                    </div>

                    {{-- Kiểu dữ liệu --}}
                    <div class="form-group">
                        <label class="form-label" for="setting-type">
                            Kiểu dữ liệu <span class="required">*</span>
                        </label>
                        <select id="setting-type" name="type" class="clean-select" required>
                            @foreach($types as $type)
                                <option value="{{ $type }}" {{ old('type', $setting->type) === $type ? 'selected' : '' }}>
                                    {{ ucfirst($type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Phạm vi (Public / Private) --}}
                    <div class="form-group">
                        <label class="form-label" for="setting-is-public">Phạm vi hiển thị</label>
                        <select id="setting-is-public" name="is_public" class="clean-select">
                            <option value="1" {{ old('is_public', $setting->is_public) ? 'selected' : '' }}>
                                Công khai (Frontend có thể đọc)
                            </option>
                            <option value="0" {{ old('is_public', $setting->is_public) ? '' : 'selected' }}>
                                Nội bộ (Chỉ dùng Backend)
                            </option>
                        </select>
                    </div>

                    {{-- Bắt buộc --}}
                    <div class="form-group">
                        <label class="form-label" for="setting-is-required">Yêu cầu bắt buộc</label>
                        <select id="setting-is-required" name="is_required" class="clean-select">
                            <option value="0" {{ old('is_required', $setting->is_required) ? '' : 'selected' }}>Không</option>
                            <option value="1" {{ old('is_required', $setting->is_required) ? 'selected' : '' }}>Bắt buộc</option>
                        </select>
                    </div>
                </div>

                {{-- Card: Thông tin Metadata --}}
                <div class="setting-card" style="font-size: 12.5px; color: #64748b;">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Ngày khởi tạo:</span>
                        <strong class="text-dark">{{ $setting->created_at ? $setting->created_at->format('d/m/Y H:i') : '—' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Cập nhật gần nhất:</span>
                        <strong class="text-dark">{{ $setting->updated_at ? $setting->updated_at->format('d/m/Y H:i') : '—' }}</strong>
                    </div>
                </div>

                {{-- Save Button --}}
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2 py-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Lưu thay đổi</span>
                    </button>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-1 py-2">
                        <i class="fa-solid fa-arrow-left"></i> Quay lại danh sách
                    </a>
                </div>
            </div>
        </div>
    </form>

    {{-- Delete action section if allowed --}}
    @if($canDelete)
        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                @if($isProtected && $isSuperAdmin)
                    <span class="text-danger fw-semibold">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        Cảnh báo: Bạn đang thao tác với quyền admin@gmail.com trên cấu hình hệ thống cốt lõi.
                    </span>
                @else
                    Không cần dùng cấu hình này nữa?
                @endif
            </div>
            <form action="{{ route('admin.settings.destroy', $setting) }}" method="POST"
                  onsubmit="return confirm('{{ $isProtected ? 'CẢNH BÁO QUAN TRỌNG: Đây là setting hệ thống cốt lõi! Bạn có chắc chắn muốn xóa không?' : 'Bạn có chắc chắn muốn xóa cài đặt này không?' }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1">
                    <i class="fa-regular fa-trash-can"></i> Xoá cài đặt này
                </button>
            </form>
        </div>
    @endif

</div>

<script>
    function updateImagePreview(url) {
        const img = document.getElementById('image-preview');
        const emptyText = document.getElementById('image-preview-empty');
        if (!img || !emptyText) return;

        if (url && url.trim().length > 0) {
            img.src = url.startsWith('http') || url.startsWith('/') ? url : '/' + url;
            img.style.display = 'block';
            emptyText.style.display = 'none';
            img.onerror = function() {
                img.style.display = 'none';
                emptyText.style.display = 'block';
                emptyText.textContent = 'Ảnh không khả dụng hoặc link sai';
            };
        } else {
            img.style.display = 'none';
            emptyText.style.display = 'block';
            emptyText.textContent = 'Chưa có ảnh xem trước';
        }
    }
</script>
@endsection
