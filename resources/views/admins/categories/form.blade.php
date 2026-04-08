@extends('admins.layouts.master')

@php
    $isEdit = $category->exists;
    $pageTitle = $isEdit ? 'Chỉnh sửa danh mục' : 'Tạo danh mục mới';
    $metadata = $category->metadata ?? [];
    if (is_string($metadata)) {
        $metadata = json_decode($metadata, true) ?? [];
    }
@endphp

@section('title', $pageTitle)
@section('page-title', '🏷️ ' . $pageTitle)

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/category-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card > h3 {
            margin: 0 0 16px;
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
        }
        
        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-control,
        textarea,
        select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .form-control:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #1e293b;
        }
        
        .form-help {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        
        .image-preview {
            margin-top: 12px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        
        .image-preview img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }
        
        .image-preview-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .breadcrumb {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 13px;
        }
        
        .breadcrumb-item {
            color: #64748b;
        }
        
        .breadcrumb-item.active {
            color: #1e293b;
            font-weight: 600;
        }
        
        .breadcrumb-separator {
            color: #cbd5e1;
        }
        
        .category-form-layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
            align-items: flex-start;
        }
        
        .category-form-main {
            min-width: 0;
        }
        
        .category-form-sidebar {
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        
        .sidebar-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e5e7eb;
        }
        
        .sidebar-card h4 {
            margin: 0 0 12px;
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
            padding-bottom: 8px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .sidebar-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .sidebar-actions .btn {
            width: 100%;
            justify-content: center;
        }
        
        .sidebar-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 13px;
        }
        
        .sidebar-info-item:last-child {
            border-bottom: none;
        }
        
        .sidebar-info-label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .sidebar-info-value {
            color: #111827;
            font-weight: 600;
            max-width: 60%;
            text-align: right;
            word-break: break-word;
        }
        
        .sidebar-status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .sidebar-status-badge.active {
            background: #dcfce7;
            color: #15803d;
        }
        
        .sidebar-status-badge.inactive {
            background: #fee2e2;
            color: #b91c1c;
        }
        
        @media (max-width: 1200px) {
            .category-form-layout {
                grid-template-columns: 1fr;
            }
            
            .category-form-sidebar {
                position: static;
                max-height: none;
            }
        }
        
        /* CKEditor 5 Styles */
        .ck-editor__editable {
            min-height: 500px;
        }
        .ck-content {
            min-height: 500px;
        }
    </style>
@endpush

@section('content')
    @if($isEdit && isset($breadcrumb))
        <div class="breadcrumb">
            @foreach($breadcrumb as $item)
                <span class="breadcrumb-item">{{ $item['name'] }}</span>
                @if(!$loop->last)
                    <span class="breadcrumb-separator">/</span>
                @endif
            @endforeach
        </div>
    @endif

    <form action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
          method="POST" enctype="multipart/form-data" id="categoryForm">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h2 style="margin:0;">{{ $pageTitle }}</h2>
        </div>

        <div class="category-form-layout">
            <div class="category-form-main">
        <div class="card">
            <h3>Thông tin cơ bản</h3>
            
            {{-- Parent Category Selection - Prominent Position --}}
            <div class="form-group" style="margin-bottom:24px;padding:16px;background:#f8fafc;border-radius:8px;border:2px solid #e2e8f0;">
                <label for="parent_id" style="font-weight:600;font-size:15px;color:#1e293b;margin-bottom:8px;display:block;">
                    📂 Danh mục cha <span style="color:#64748b;font-weight:400;font-size:13px;">(Tùy chọn - Chọn để tạo danh mục con)</span>
                </label>
                <select name="parent_id" id="parent_id" class="form-control" style="min-height:48px;font-size:14px;border:2px solid #cbd5e1;border-radius:6px;padding:10px 12px;">
                    @php
                        $currentParentId = old('parent_id', $category->parent_id);
                        // Normalize: empty string, 0, or null should be treated as null
                        if ($currentParentId === '' || $currentParentId === 0 || $currentParentId === null) {
                            $currentParentId = null;
                        } else {
                            $currentParentId = (int) $currentParentId;
                        }
                    @endphp
                    <option value="" {{ $currentParentId === null ? 'selected' : '' }} style="font-weight:600;color:#475569;">
                        🏠 Không có (Danh mục gốc)
                    </option>
                    @if(!empty($parentOptions))
                        @foreach($parentOptions as $option)
                            <option value="{{ $option['value'] }}"
                                {{ $currentParentId === $option['value'] ? 'selected' : '' }}>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    @else
                        <option value="" disabled>Chưa có danh mục nào</option>
                    @endif
                </select>
                <div class="form-help" style="margin-top:8px;font-size:13px;color:#64748b;">
                    @if($isEdit)
                        <span style="color:#3b82f6;font-weight:500;">📌 Hiện tại:</span> 
                        @if($category->parent_id)
                            <strong>{{ $category->parent?->name ?? 'Danh mục cha không tồn tại' }}</strong>
                        @else
                            <strong style="color:#059669;">Danh mục gốc (Root)</strong>
                        @endif
                        <br>
                        <span style="color:#64748b;">Bạn có thể thay đổi danh mục cha bằng cách chọn từ dropdown trên.</span>
                    @else
                        <span style="color:#3b82f6;">💡 Hướng dẫn:</span> Chọn một danh mục từ dropdown để tạo danh mục con, hoặc để mặc định "🏠 Không có" để tạo danh mục gốc.
                    @endif
                </div>
                @error('parent_id')
                    <div style="color:#ef4444;font-size:12px;margin-top:8px;padding:8px;background:#fef2f2;border-radius:4px;">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="grid-3">
                <div class="form-group">
                    <label for="name">Tên danh mục <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name" id="name" class="form-control"
                           value="{{ old('name', $category->name) }}" required minlength="2" maxlength="150">
                    <div class="form-help">Tên danh mục (2-150 ký tự)</div>
                    @error('name')
                        <div style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="slug">Slug</label>
                    <input type="text" name="slug" id="slug" class="form-control"
                           value="{{ old('slug', $category->slug) }}"
                           pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                           placeholder="Tự động tạo từ tên">
                    <div class="form-help">Slug sẽ tự động tạo nếu để trống (unique toàn bảng)</div>
                    @error('slug')
                        <div style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="order">Thứ tự</label>
                    <input type="number" name="order" id="order" class="form-control"
                           value="{{ old('order', $category->order ?? 0) }}" min="0">
                    <div class="form-help">Số càng nhỏ, hiển thị càng trước</div>
                    @error('order')
                        <div style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="is_active">Trạng thái</label>
                    @php
                        $isDefaultCategory = isset($category->id) && $category->id === 1;
                    @endphp
                    <select name="is_active" id="is_active" class="form-control" {{ $isDefaultCategory ? 'disabled' : '' }}>
                        <option value="1" {{ old('is_active', $category->is_active ?? true) ? 'selected' : '' }}>Hiển thị</option>
                        <option value="0" {{ old('is_active', $category->is_active ?? true) ? '' : 'selected' }}>Tạm ẩn</option>
                    </select>
                    @if($isDefaultCategory)
                        <input type="hidden" name="is_active" value="1">
                        <div class="form-help" style="color:#f59e0b;">
                            ⚠️ Đây là danh mục mặc định (ID: 1), không thể thay đổi trạng thái. Luôn ở trạng thái "Hiển thị".
                        </div>
                    @else
                        <div class="form-help">Chọn trạng thái hiển thị của danh mục</div>
                    @endif
                    @error('is_active')
                        <div style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Mô tả</h3>
            <div class="form-group">
                <label for="description">Mô tả danh mục</label>
                <textarea name="description" id="description" class="form-control tinymce-editor" rows="4">{{ old('description', $category->description) }}</textarea>
                <div class="form-help">Mô tả chi tiết về danh mục (hỗ trợ HTML, hình ảnh, định dạng văn bản)</div>
                @error('description')
                    <div style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="card">
            <h3>Ảnh đại diện</h3>
            <div class="form-group">
                <label for="image">Ảnh danh mục</label>
                <input type="file" name="image" id="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                <div class="form-help">Định dạng: JPG, PNG, WebP. Kích thước tối đa: 1MB</div>
                @error('image')
                    <div style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                @enderror
                
                @if($isEdit && $category->image)
                    <div class="image-preview">
                        <img src="{{ asset('clients/assets/img/categories/' . $category->image) }}" 
                             alt="{{ $category->name }}" 
                             id="imagePreview">
                        <div class="image-preview-actions">
                            <label style="margin:0;">
                                <input type="checkbox" name="delete_image" value="1">
                                Xóa ảnh hiện tại
                            </label>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <h3>SEO Meta (Metadata)</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label for="meta_title">Meta Title</label>
                    <input type="text" name="metadata[meta_title]" id="meta_title" class="form-control"
                           value="{{ old('metadata.meta_title', $metadata['meta_title'] ?? '') }}" maxlength="255">
                    <div class="form-help">Tiêu đề SEO (tối đa 255 ký tự)</div>
                </div>
                
                <div class="form-group">
                    <label for="meta_canonical">Meta Canonical URL</label>
                    <input type="url" name="metadata[meta_canonical]" id="meta_canonical" class="form-control"
                           value="{{ old('metadata.meta_canonical', $metadata['meta_canonical'] ?? '') }}"
                           placeholder="https://example.com/..." maxlength="500">
                    <div class="form-help">URL canonical cho SEO</div>
                </div>
                
                <div class="form-group">
                    <label for="meta_keywords">Meta Keywords</label>
                    <input type="text" name="metadata[meta_keywords]" id="meta_keywords" class="form-control"
                           value="{{ old('metadata.meta_keywords', $metadata['meta_keywords'] ?? '') }}"
                           placeholder="từ khóa 1, từ khóa 2" maxlength="255">
                    <div class="form-help">Từ khóa SEO (phân cách bằng dấu phẩy)</div>
                </div>
            </div>
            <div class="form-group">
                <label for="meta_description">Meta Description</label>
                <textarea name="metadata[meta_description]" id="meta_description" rows="3" class="form-control" maxlength="500">{{ old('metadata.meta_description', $metadata['meta_description'] ?? '') }}</textarea>
                <div class="form-help">Mô tả SEO (tối đa 500 ký tự)</div>
            </div>
        </div>
            </div> {{-- /.category-form-main --}}

            <div class="category-form-sidebar">
                <div class="sidebar-card">
                    <h4>Thao tác</h4>
                    <div class="sidebar-actions">
                        <button type="submit" form="categoryForm" class="btn btn-primary">💾 Lưu danh mục</button>
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">↩️ Quay lại danh sách</a>
                        @if($isEdit)
                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-outline-secondary">✏️ Mở lại form</a>
                        @endif
            </div>
        </div>

                @if($isEdit)
                    <div class="sidebar-card">
                        <h4>Thông tin nhanh</h4>
                        <div class="sidebar-info-item">
                            <span class="sidebar-info-label">ID:</span>
                            <span class="sidebar-info-value">{{ $category->id }}</span>
                        </div>
                        <div class="sidebar-info-item">
                            <span class="sidebar-info-label">Slug:</span>
                            <span class="sidebar-info-value">{{ $category->slug }}</span>
                        </div>
                        <div class="sidebar-info-item">
                            <span class="sidebar-info-label">Trạng thái:</span>
                            <span class="sidebar-info-value">
                                <span class="sidebar-status-badge {{ $category->is_active ? 'active' : 'inactive' }}">
                                    {{ $category->is_active ? 'Hiển thị' : 'Tạm ẩn' }}
                                </span>
                            </span>
                        </div>
                        <div class="sidebar-info-item">
                            <span class="sidebar-info-label">Danh mục cha:</span>
                            <span class="sidebar-info-value">
                                {{ $category->parent?->name ?? 'Root' }}
                            </span>
                        </div>
                        <div class="sidebar-info-item">
                            <span class="sidebar-info-label">Ngày tạo:</span>
                            <span class="sidebar-info-value">
                                {{ $category->created_at?->format('d/m/Y') ?? '-' }}
                            </span>
                        </div>
                        <div class="sidebar-info-item">
                            <span class="sidebar-info-label">Cập nhật:</span>
                            <span class="sidebar-info-value">
                                {{ $category->updated_at?->format('d/m/Y') ?? '-' }}
                            </span>
                        </div>
        </div>
                @endif
            </div> {{-- /.category-form-sidebar --}}
        </div> {{-- /.category-form-layout --}}
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Auto-generate slug from name
            const nameInput = document.getElementById('name');
            const slugInput = document.getElementById('slug');
            
            if (nameInput && slugInput) {
                let slugManuallyEdited = false;
                
                nameInput.addEventListener('input', () => {
                    if (!slugManuallyEdited && !slugInput.value) {
                        slugInput.value = nameInput.value
                            .toLowerCase()
                            .normalize('NFD')
                            .replace(/[\u0300-\u036f]/g, '')
                            .replace(/[^a-z0-9]+/g, '-')
                            .replace(/^-+|-+$/g, '');
                    }
                });
                
                slugInput.addEventListener('input', () => {
                    slugManuallyEdited = slugInput.value.length > 0;
                });
            }
            
            // Image preview
            const imageInput = document.getElementById('image');
            const imagePreview = document.getElementById('imagePreview');
            
            if (imageInput && imagePreview) {
                imageInput.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            imagePreview.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
            
            // Initialize CKEditor 5
            if (typeof window.initCKEditor5 === 'function') {
                window.initCKEditor5('#description', {
                    toolbar: {
                        items: [
                            'undo', 'redo', '|',
                            'heading', 'style', '|',
                            'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
                            'bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript', 'code', '|',
                            'alignment', '|',
                            'bulletedList', 'numberedList', 'todoList', 'outdent', 'indent', '|',
                            'link', 'mediaLibrary', 'insertTable', 'blockQuote', 'codeBlock', 'horizontalLine', '|',
                            'mediaEmbed', 'highlight', '|',
                            'sourceEditing', 'showBlocks', 'fullscreen'
                        ],
                        shouldNotGroupWhenFull: true
                }
                });
            }
        });
    </script>
@endpush
