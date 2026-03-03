@extends('admins.layouts.master')

@php
$isEdit = $product->exists;
$pageTitle = $isEdit ? 'Chỉnh sửa sản phẩm' : 'Tạo sản phẩm mới';

// Lấy selected tag IDs từ relationship
$selectedTagIds = old('tag_ids', []);
if (empty($selectedTagIds) && $product->exists) {
$selectedTagIds = $product->tags()->pluck('id')->toArray();
}
// Nếu vẫn không có, thử lấy từ tag_ids JSON (backward compatibility)
if (empty($selectedTagIds) && $product->exists && !empty($product->tag_ids)) {
$selectedTagIds = is_array($product->tag_ids) ? $product->tag_ids : [];
}

// Lấy selected tag names để hiển thị
$selectedTagNames = [];
if (!empty($selectedTagIds)) {
$selectedTags = \App\Models\Tag::whereIn('id', $selectedTagIds)->get();
$selectedTagNames = $selectedTags->pluck('name')->toArray();
}

// Xử lý tag_names từ old input (nếu có)
$tagNamesInput = old('tag_names', '');
@endphp

@section('title', $pageTitle)
@section('page-title', $pageTitle)

@push('head')
@if($isEdit)
<link rel="shortcut icon" href="{{ asset('admins/img/icons/edit-product-icon.png') }}" type="image/x-icon">
@else
<link rel="shortcut icon" href="{{ asset('admins/img/icons/create-product-icon.png') }}" type="image/x-icon">
@endif

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
@endpush

@push('styles')
<style>
    /* FORCE 2 COLUMN LAYOUT - WordPress Style */
    #product-form .row.g-4 {
        display: flex !important;
        flex-wrap: wrap !important;
        margin-left: -15px !important;
        margin-right: -15px !important;
        align-items: flex-start !important;
    }

    #product-form .row.g-4>.col-lg-8,
    #product-form .row.g-4>.col-lg-4 {
        padding-left: 15px !important;
        padding-right: 15px !important;
        box-sizing: border-box !important;
    }

    #product-form .col-lg-8 {
        width: 66.66666667% !important;
        flex: 0 0 66.66666667% !important;
        max-width: 66.66666667% !important;
    }

    #product-form .col-lg-4 {
        width: 33.33333333% !important;
        flex: 0 0 33.33333333% !important;
        max-width: 33.33333333% !important;
        display: block !important;
    }

    /* Sticky sidebar - Giữ cột phải luôn hiển thị khi scroll */
    @media (min-width: 992px) {
        #product-form .col-lg-4 {
            position: sticky !important;
            top: 20px !important;
            align-self: flex-start !important;
            max-height: calc(100vh - 40px) !important;
            overflow-y: auto !important;
        }
    }

    @media (max-width: 991.98px) {

        #product-form .col-lg-8,
        #product-form .col-lg-4 {
            width: 100% !important;
            flex: 0 0 100% !important;
            max-width: 100% !important;
            position: relative !important;
        }
    }

    .card {
        background: #fff;
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 6px rgba(15, 23, 42, 0.06);
        margin-bottom: 16px;
    }

    .card>h3 {
        margin: 0 0 8px;
        font-size: 16px;
        font-weight: 600;
        color: #0f172a;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 12px 16px;
    }

    .grid-3 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 10px 14px;
    }

    .form-control,
    textarea,
    select {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #cbd5f5;
        border-radius: 6px;
        font-size: 13px;
    }

    label {
        display: block;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 4px;
        color: #111827;
    }

    .repeater-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .repeater-item {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 12px;
        background: #f9fafb;
    }

    .repeater-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
    }

    .btn-link {
        background: none;
        border: none;
        color: #2563eb;
        cursor: pointer;
    }

    .image-library {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
        max-height: 200px;
        overflow-y: auto;
    }

    .image-library button {
        border: 1px solid #cbd5f5;
        border-radius: 8px;
        padding: 0;
        background: #fff;
        cursor: pointer;
    }

    .image-library img {
        width: 62px;
        height: 62px;
        object-fit: cover;
        border-radius: 6px;
        display: block;
    }

    .image-preview {
        margin-top: 10px;
    }

    .image-preview img {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }

    /* CKEditor 5 Styles */
    .ck-editor__editable {
        min-height: 500px;
    }
    
    .ck-editor__editable_inline {
        min-height: 500px;
    }

    .attribute-row {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 10px;
        align-items: center;
        margin-bottom: 10px;
    }

    .attribute-row input {
        padding: 8px 10px;
    }

    .attribute-row button {
        border: none;
        background: none;
        color: #ef4444;
        font-size: 18px;
        cursor: pointer;
    }

    .attribute-actions {
        margin-top: 10px;
    }

</style>
@endpush

@push('scripts')
@include('admins.partials.media-library-modal')
<script>
    console.log('=== PRODUCTS FORM SCRIPTS LOADING ===');
    console.log('Loading media-library.js...');

</script>
<script src="{{ asset('admins/js/media-library.js?v=' . time()) }}"></script>
<script>
    console.log('media-library.js loaded, checking window.mediaLibrary:', typeof window.mediaLibrary);
    console.log('window.mediaLibrary:', window.mediaLibrary);
    document.addEventListener('DOMContentLoaded', () => {
        const counters = {};
        const attributeCounters = {};
        let isDirty = false;
        const markDirty = () => {
            isDirty = true;
        };
        // CKEditor 5 sẽ tự động khởi tạo cho .tinymce-editor
        // Đợi editor khởi tạo xong để setup markDirty
        const initCKEditors = () => {
            setTimeout(() => {
                document.querySelectorAll('.tinymce-editor').forEach(textarea => {
                    const editorId = textarea.id;
                    if (editorId) {
                        const checkEditor = setInterval(() => {
                            const editor = window.CKEditor5API && window.CKEditor5API.get(editorId);
                            if (editor) {
                                clearInterval(checkEditor);
                                // Setup markDirty khi có thay đổi
                                editor.model.document.on('change:data', () => {
                                    markDirty();
                                });
                            }
                        }, 100);
                        setTimeout(() => clearInterval(checkEditor), 5000);
                    }
                });
            }, 300);
        };


        document.querySelectorAll('[data-add]').forEach(btn => {
            const targetSelector = btn.dataset.add;
            const templateSelector = btn.dataset.template;
            counters[targetSelector] = document.querySelectorAll(`${targetSelector} .repeater-item`).length;

            btn.addEventListener('click', () => {
                const target = document.querySelector(targetSelector);
                const template = document.querySelector(templateSelector);
                if (!target || !template) return;

                let html = template.innerHTML.replace(/__INDEX__/g, counters[targetSelector]++);
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();
                const newBlock = wrapper.firstElementChild;
                target.appendChild(newBlock);
                if (targetSelector === '#variant-list') {
                    registerVariantAttributes(newBlock);
                }
                initCKEditors();
                markDirty();
            });
        });

        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-remove]')) {
                e.target.closest('.repeater-item')?.remove();
                markDirty();
            }

            if (e.target.matches('[data-select-image]')) {
                const path = e.target.dataset.path;
                const target = document.querySelector(e.target.dataset.target);
                const preview = document.querySelector(e.target.dataset.preview);
                if (target) {
                    target.value = path;
                }
                if (preview) {
                    preview.innerHTML = `<img src="${e.target.dataset.url}" alt="">`;
                }
            }
            if (e.target.matches('[data-remove-attribute]')) {
                e.target.closest('.attribute-row')?.remove();
                markDirty();
            }
        });

        document.addEventListener('change', (e) => {
            if (e.target.matches('.image-file-input')) {
                const preview = e.target.closest('.repeater-item')?.querySelector('.image-preview');
                if (!preview || !e.target.files?.length) return;
                const reader = new FileReader();
                reader.onload = (ev) => {
                    preview.innerHTML = `<img src="${ev.target.result}" alt="">`;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        if (document.querySelector('#primary-category')) {
            new TomSelect('#primary-category', {
                create: false
                , allowEmptyOption: true
            });
        }
        if (document.querySelector('#extra-categories')) {
            new TomSelect('#extra-categories', {
                plugins: ['remove_button']
                , persist: false
            });
        }
        // Khởi tạo TomSelect cho tag_ids (multiple select)
        if (document.querySelector('select[name="tag_ids[]"]')) {
            new TomSelect('select[name="tag_ids[]"]', {
                placeholder: 'Chọn tags từ danh sách...'
                , plugins: ['remove_button']
                , maxItems: null
                , create: false
                , sortField: {
                    field: 'text'
                    , direction: 'asc'
                }
            });
        }

        window.mediaImages = {!! json_encode($mediaImages ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!};

        initCKEditors();

        const registerVariantAttributes = (variantElement) => {
            const attrContainer = variantElement.querySelector('.variant-attributes');
            if (!attrContainer) return;
            const variantIndex = attrContainer.dataset.variantIndex;
            attributeCounters[variantIndex] = attrContainer.querySelectorAll('.attribute-row').length || 0;
        };

        document.querySelectorAll('#variant-list .repeater-item').forEach(registerVariantAttributes);

        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-add-attribute]')) {
                const container = document.querySelector(e.target.dataset.target);
                if (!container) return;
                const template = document.querySelector('#variant-attribute-template');
                if (!template) return;
                const variantIndex = container.dataset.variantIndex;
                if (typeof attributeCounters[variantIndex] === 'undefined') {
                    attributeCounters[variantIndex] = container.querySelectorAll('.attribute-row').length || 0;
                }
                const attrIndex = attributeCounters[variantIndex]++;
                let html = template.innerHTML
                    .replace(/__VARIANT_INDEX__/g, variantIndex)
                    .replace(/__ATTR_INDEX__/g, attrIndex);
                const row = document.createElement('div');
                row.innerHTML = html.trim();
                container.appendChild(row.firstElementChild);
                markDirty();
            }
        });

        const form = document.querySelector('#product-form');
        if (form) {
            form.addEventListener('input', markDirty, true);
            form.addEventListener('change', markDirty, true);

            window.addEventListener('beforeunload', (event) => {
                if (!isDirty) {
                    // Release lock khi đóng trang (nếu không có thay đổi)
                    @if($isEdit)
                    const productId = {{ $product->id }};
                    // Sử dụng sendBeacon để đảm bảo request được gửi ngay cả khi đóng trang
                    if (navigator.sendBeacon) {
                        const formData = new FormData();
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                        navigator.sendBeacon('{{ route("admin.products.release-lock", $product) }}', formData);
                    } else {
                        // Fallback: sync request (có thể block nhưng đảm bảo release lock)
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', '{{ route("admin.products.release-lock", $product) }}', false);
                        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.content || '');
                        xhr.send();
                    }
                    @endif
                    return;
                }
                event.preventDefault();
                event.returnValue = '';
            });

            // Release lock khi submit thành công
            form.addEventListener('submit', () => {
                isDirty = false;
                @if($isEdit)
                // Lock sẽ được release trong controller sau khi update thành công
                @endif
            });

            // Release lock khi visibility change (tab bị ẩn, đóng, etc.)
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    @if($isEdit)
                    // Chỉ release nếu không có thay đổi
                    if (!isDirty) {
                        fetch('{{ route("admin.products.release-lock", $product) }}', {
                            method: 'POST'
                            , headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                , 'Content-Type': 'application/json'
                            , }
                        , }).catch(() => {
                            // Ignore errors khi release lock
                        });
                    }
                    @endif
                }
            });

            document.addEventListener('click', (e) => {
                if (e.target.matches('[data-add], [data-remove]')) {
                    markDirty();
                }
            });
        }
    });

</script>
@endpush

@section('content')
<form id="product-form" data-dirty-guard="true" action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($isEdit)
    @method('PUT')
    @endif

    @if($isEdit && $product->locked_by === auth('web')->id())
    <div style="margin-bottom:15px;padding:12px 14px;border-radius:8px;background:#e0f2fe;color:#0f172a;">
        <strong>🔒 Đang chỉnh sửa:</strong>
        Bạn đang khóa sản phẩm này để chỉnh sửa. Hệ thống sẽ tự động mở khóa khi bạn lưu hoặc sau {{ config('app.editor_lock_minutes') }} phút không hoạt động.
    </div>
    @endif

    <div class="row g-4">
        <!-- Cột trái: Nội dung chính -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Thông tin cơ bản</h5>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên sản phẩm *</label>
                        <input type="text" class="form-control form-control-lg" name="name" value="{{ old('name', $product->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Slug</label>
                        <input type="text" class="form-control" name="slug" value="{{ old('slug', $product->slug) }}" placeholder="Tự tạo nếu để trống">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả ngắn</label>
                        <textarea class="form-control tinymce-editor" name="short_description" rows="3">{{ old('short_description', $product->short_description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả chi tiết</label>
                        <textarea class="form-control tinymce-editor" name="description" rows="6">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Gallery ảnh</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-add="#image-list" data-template="#image-template">+ Thêm ảnh</button>
                    </div>
                    <div class="repeater-list" id="image-list">
                        @foreach(old('images', $product->images->toArray() ?? []) as $index => $image)
                            <div class="repeater-item">
                                <div class="repeater-header">
                                    <strong>Ảnh #{{ $index + 1 }}</strong>
                                    <button type="button" class="btn-link" data-remove data-item=".repeater-item">Xóa</button>
                                </div>
                                <input type="hidden" name="images[{{ $index }}][id]" value="{{ $image['id'] ?? null }}">
                                <div class="grid-2">
                                    <div>
                                        <label>Title</label>
                                        <input type="text" class="form-control" name="images[{{ $index }}][title]" value="{{ $image['title'] ?? '' }}">
                                    </div>
                                    <div>
                                        <label>Ghi chú</label>
                                        <input type="text" class="form-control" name="images[{{ $index }}][notes]" value="{{ $image['notes'] ?? '' }}">
                                    </div>
                                    <div>
                                        <label>Alt</label>
                                        <input type="text" class="form-control" name="images[{{ $index }}][alt]" value="{{ $image['alt'] ?? '' }}">
                                    </div>
                                    <div>
                                        <label>Thứ tự</label>
                                        <input type="number" class="form-control" name="images[{{ $index }}][order]" value="{{ $image['order'] ?? $index }}">
                                    </div>
                                </div>
                                <div style="margin-top:10px;">
                                    <label>File ảnh</label>
                                    <input type="file" name="images[{{ $index }}][file]" class="form-control image-file-input">
                                    @php
                                        $storedUrl = $image['url'] ?? null;
                                        // Nếu là dữ liệu cũ (chỉ có tên file), accessor của Model sẽ tự xử lý khi load từ DB.
                                        // Nếu là old input, ta cần check xem nó có phải URL tuyệt đối không.
                                        if ($storedUrl && !str_starts_with($storedUrl, 'http')) {
                                            $storedUrl = asset($storedUrl);
                                        }
                                        $storedValue = $image['path'] ?? $image['url'] ?? null;
                                    @endphp
                                    <input type="hidden" id="image-path-{{ $index }}" name="images[{{ $index }}][existing_path]" value="{{ $storedValue }}">
                                    <div class="image-preview" id="image-preview-{{ $index }}">
                                        @if($storedUrl)
                                        <img src="{{ $storedUrl }}" alt="">
                                        @endif
                                    </div>
                                    <div class="image-library">
                                        @foreach($mediaImages as $media)
                                            <button type="button"
                                                    data-select-image
                                                    data-path="{{ basename($media['path']) }}"
                                        data-url="{{ $media['url'] }}"
                                        data-target="#image-path-{{ $index }}"
                                        data-preview="#image-preview-{{ $index }}">
                                        <img src="{{ $media['url'] }}" alt="{{ $media['name'] }}">
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top:10px;">
                                <label><input type="checkbox" name="images[{{ $index }}][is_primary]" value="1" {{ !empty($image['is_primary']) ? 'checked' : '' }}> Ảnh chính</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Biến thể & Thuộc tính</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-add="#variant-list" data-template="#variant-template">+ Thêm biến thể</button>
                    </div>
                    <div class="repeater-list" id="variant-list">
                        @foreach(old('variants', $product->variants->toArray() ?? []) as $index => $variant)
                        @php
                        $rawAttrs = $variant['attributes'] ?? [];
                        if (is_string($rawAttrs)) {
                        $decoded = json_decode($rawAttrs, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                        $rawAttrs = $decoded;
                        } else {
                        $rawAttrs = [];
                        }
                        }
                        $attributeList = [];
                        if (is_array($rawAttrs)) {
                        if (isset($rawAttrs[0]['key']) && isset($rawAttrs[0]['value'])) {
                        $attributeList = $rawAttrs;
                        } else {
                        foreach ($rawAttrs as $attrKey => $attrValue) {
                        $attributeList[] = ['key' => $attrKey, 'value' => $attrValue];
                        }
                        }
                        }
                        if (empty($attributeList)) {
                        $attributeList[] = ['key' => '', 'value' => ''];
                        }
                        @endphp
                        <div class="repeater-item">
                            <div class="repeater-header">
                                <strong>Variant #{{ $index + 1 }}</strong>
                                <button type="button" class="btn-link" data-remove data-item=".repeater-item">Xóa</button>
                            </div>
                            <div class="grid-2">
                                <div>
                                    <label>Giá</label>
                                    <input type="number" class="form-control" name="variants[{{ $index }}][price]" value="{{ $variant['price'] ?? '' }}">
                                </div>
                                <div>
                                    <label>Tồn kho</label>
                                    <input type="number" class="form-control" name="variants[{{ $index }}][stock_quantity]" value="{{ $variant['stock_quantity'] ?? '' }}">
                                </div>
                            </div>
                            <div style="margin-top:15px;">
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <label style="margin:0;">Thuộc tính</label>
                                    <button type="button" class="btn btn-secondary btn-sm" data-add-attribute data-target="#variant-attributes-{{ $index }}">+ Thuộc tính</button>
                                </div>
                                <div class="variant-attributes" id="variant-attributes-{{ $index }}" data-variant-index="{{ $index }}">
                                    @foreach($attributeList as $attrIndex => $attr)
                                    <div class="attribute-row">
                                        <input type="text" class="form-control" placeholder="Tên thuộc tính" name="variants[{{ $index }}][attributes][{{ $attrIndex }}][key]" value="{{ $attr['key'] }}">
                                        <input type="text" class="form-control" placeholder="Giá trị" name="variants[{{ $index }}][attributes][{{ $attrIndex }}][value]" value="{{ $attr['value'] }}">
                                        <button type="button" data-remove-attribute title="Xóa thuộc tính">&times;</button>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">FAQs</h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-add="#faq-list" data-template="#faq-template">+ Thêm FAQ</button>
                        </div>
                        <div class="repeater-list" id="faq-list">
                            @foreach(old('faqs', $product->faqs->toArray() ?? []) as $index => $faq)
                            <div class="repeater-item">
                                <div class="repeater-header">
                                    <strong>FAQ #{{ $index + 1 }}</strong>
                                    <button type="button" class="btn-link" data-remove data-item=".repeater-item">Xóa</button>
                                </div>
                                <input type="hidden" name="faqs[{{ $index }}][id]" value="{{ $faq['id'] ?? null }}">
                                <div>
                                    <label>Câu hỏi</label>
                                    <input type="text" class="form-control" name="faqs[{{ $index }}][question]" value="{{ $faq['question'] ?? '' }}">
                                </div>
                                <div style="margin-top:10px;">
                                    <label>Trả lời</label>
                                    <textarea class="form-control" name="faqs[{{ $index }}][answer]" rows="2">{{ $faq['answer'] ?? '' }}</textarea>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0">How-To</h5>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-add="#howto-list" data-template="#howto-template">+ Thêm hướng dẫn</button>
                            </div>
                            <div class="repeater-list" id="howto-list">
                                @foreach(old('how_tos', $product->howTos->toArray() ?? []) as $index => $howTo)
                                <div class="repeater-item">
                                    <div class="repeater-header">
                                        <strong>How-To #{{ $index + 1 }}</strong>
                                        <button type="button" class="btn-link" data-remove data-item=".repeater-item">Xóa</button>
                                    </div>
                                    <input type="hidden" name="how_tos[{{ $index }}][id]" value="{{ $howTo['id'] ?? null }}">
                                    <div class="grid-2">
                                        <div>
                                            <label>Tiêu đề</label>
                                            <input type="text" class="form-control" name="how_tos[{{ $index }}][title]" value="{{ $howTo['title'] ?? '' }}">
                                        </div>
                                        <div>
                                            <label>Hoạt động</label>
                                            <select class="form-control" name="how_tos[{{ $index }}][is_active]">
                                                <option value="1" {{ !empty($howTo['is_active']) ? 'selected' : '' }}>Hiển thị</option>
                                                <option value="0" {{ empty($howTo['is_active']) ? 'selected' : '' }}>Ẩn</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div style="margin-top:10px;">
                                        <label>Mô tả</label>
                                        <textarea class="form-control" name="how_tos[{{ $index }}][description]" rows="2">{{ $howTo['description'] ?? '' }}</textarea>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Cột phải: Thông tin quan trọng -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Thông tin quan trọng</h5>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">SKU *</label>
                        <input type="text" class="form-control" name="sku" value="{{ old('sku', $product->sku) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Giá bán</label>
                        <input type="number" class="form-control" name="price" value="{{ old('price', $product->price) }}" min="0" step="0.01" placeholder="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Giá khuyến mãi</label>
                        <input type="number" class="form-control" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" min="0" step="0.01" placeholder="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Giá nhập</label>
                        <input type="number" class="form-control" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" min="0" step="0.01" placeholder="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tồn kho</label>
                        <input type="number" class="form-control" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" min="0" placeholder="0">
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Trạng thái & Cài đặt</h5>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Trạng thái</label>
                        <select name="is_active" class="form-select">
                            <option value="1" {{ old('is_active', $product->is_active ?? true) ? 'selected' : '' }}>Đang bán</option>
                            <option value="0" {{ old('is_active', $product->is_active ?? true) ? '' : 'selected' }}>Tạm ẩn</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input type="hidden" name="is_featured" value="0">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="isFeaturedSwitch"
                               {{ old('is_featured', $product->is_featured ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isFeaturedSwitch">Sản phẩm nổi bật</label>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Danh mục & Tags</h5>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Danh mục chính</label>
                        <select class="form-select" id="primary-category" name="primary_category_id">
                            <option value="">-- Chọn danh mục --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('primary_category_id', $product->primary_category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Danh mục phụ</label>
                        <select class="form-select" id="extra-categories" name="category_ids[]" multiple>
                            @if(isset($categories) && is_iterable($categories))
                                @foreach($categories as $category)
                                    <option value="{{ $category->id ?? $category['id'] ?? '' }}"
                                        {{ in_array($category->id ?? $category['id'] ?? '', old('category_ids', $product->category_ids ?? [])) ? 'selected' : '' }}>
                                        {{ $category->name ?? $category['name'] ?? '' }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tags</label>
                        <div class="mb-2">
                            <label class="form-label small text-muted">Chọn từ danh sách có sẵn:</label>
                            <select name="tag_ids[]" id="tagSelect" class="form-select" multiple>
                                @if(isset($tags) && is_iterable($tags))
                                    @foreach($tags as $tag)
                                        <option value="{{ $tag->id ?? $tag['id'] ?? '' }}" @selected(in_array($tag->id ?? $tag['id'] ?? '', $selectedTagIds ?? []))>
                                            {{ $tag->name ?? $tag['name'] ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div>
                            <label class="form-label small text-muted">Hoặc thêm tags mới:</label>
                            <input type="text" 
                                   name="tag_names" 
                                   id="tagNamesInput" 
                                   class="form-control" 
                                   placeholder="Fashion, Style, Trend"
                                   value="{{ $tagNamesInput }}">
                            <small class="text-muted">Phân cách bằng dấu phẩy</small>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $metaKeywordsValue = old('meta_keywords');
                if (is_null($metaKeywordsValue)) {
                    $metaKeywordsValue = is_array($product->meta_keywords ?? null)
                        ? implode(', ', $product->meta_keywords)
                        : ($product->meta_keywords ?? '');
                }
            @endphp
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">SEO Meta</h5>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Meta Title</label>
                        <input type="text" class="form-control" name="meta_title"
                               value="{{ old('meta_title', $product->meta_title) }}"
                               placeholder="Tiêu đề hiển thị trên Google">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Meta Description</label>
                        <textarea class="form-control" rows="3" name="meta_description"
                                  placeholder="Mô tả ngắn hiển thị trên Google">{{ old('meta_description', $product->meta_description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Meta Keywords</label>
                        <input type="text" class="form-control" name="meta_keywords"
                               value="{{ $metaKeywordsValue }}"
                               placeholder="từ khóa 1, từ khóa 2">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Meta Canonical</label>
                        <input type="text" class="form-control" name="meta_canonical"
                               value="{{ old('meta_canonical', $product->meta_canonical) }}"
                               placeholder="https://example.com/san-pham/...">
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;margin-bottom:20px;">
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">↩️ Quay lại danh sách</a>
                <button type="submit" class="btn btn-primary">💾 Lưu sản phẩm</button>
            </div>
        </div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;margin-bottom:20px;">
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">↩️ Quay lại danh sách</a>
        <button type="submit" class="btn btn-primary">💾 Lưu sản phẩm</button>
    </div>
</form>

<template id="image-template">
    <div class="repeater-item">
        <div class="repeater-header">
            <strong>Ảnh mới</strong>
            <button type="button" class="btn-link" data-remove data-item=".repeater-item">Xóa</button>
        </div>
        <input type="hidden" name="images[__INDEX__][id]">
        <div class="grid-2">
            <div>
                <label>Title</label>
                <input type="text" class="form-control" name="images[__INDEX__][title]">
            </div>
            <div>
                <label>Ghi chú</label>
                <input type="text" class="form-control" name="images[__INDEX__][notes]">
            </div>
        </div>
        <div style="margin-top:10px;">
            <label>File ảnh</label>
            <input type="file" name="images[__INDEX__][file]" class="form-control image-file-input">
            <input type="hidden" id="image-path-__INDEX__" name="images[__INDEX__][existing_path]">
            <div class="image-preview" id="image-preview-__INDEX__"></div>
            <div class="image-library">
                @foreach($mediaImages as $media)
                <button type="button" data-select-image data-path="{{ basename($media['path']) }}" data-url="{{ $media['url'] }}" data-target="#image-path-__INDEX__" data-preview="#image-preview-__INDEX__">
                    <img src="{{ $media['url'] }}" alt="{{ $media['name'] }}">
                </button>
                @endforeach
            </div>
        </div>
    </div>
</template>
<template id="variant-template">
    <div class="repeater-item">
        <div class="repeater-header">
            <strong>Variant mới</strong>
            <button type="button" class="btn-link" data-remove data-item=".repeater-item">Xóa</button>
        </div>
        <div class="grid-2">
            <div>
                <label>Giá</label>
                <input type="number" class="form-control" name="variants[__INDEX__][price]">
            </div>
            <div>
                <label>Tồn kho</label>
                <input type="number" class="form-control" name="variants[__INDEX__][stock_quantity]">
            </div>
        </div>
        <div style="margin-top:15px;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <label style="margin:0;">Thuộc tính</label>
                <button type="button" class="btn btn-secondary btn-sm" data-add-attribute data-target="#variant-attributes-__INDEX__">+ Thuộc tính</button>
            </div>
            <div class="variant-attributes" id="variant-attributes-__INDEX__" data-variant-index="__INDEX__">
                <div class="attribute-row">
                    <input type="text" class="form-control" placeholder="Tên thuộc tính" name="variants[__INDEX__][attributes][0][key]">
                    <input type="text" class="form-control" placeholder="Giá trị" name="variants[__INDEX__][attributes][0][value]">
                    <button type="button" data-remove-attribute title="Xóa thuộc tính">&times;</button>
                </div>
            </div>
        </div>
    </div>
</template>
<template id="variant-attribute-template">
    <div class="attribute-row">
        <input type="text" class="form-control" placeholder="Tên thuộc tính" name="variants[__VARIANT_INDEX__][attributes][__ATTR_INDEX__][key]">
        <input type="text" class="form-control" placeholder="Giá trị" name="variants[__VARIANT_INDEX__][attributes][__ATTR_INDEX__][value]">
        <button type="button" data-remove-attribute title="Xóa thuộc tính">&times;</button>
    </div>
</template>
<template id="faq-template">
    <div class="repeater-item">
        <div class="repeater-header">
            <strong>FAQ mới</strong>
            <button type="button" class="btn-link" data-remove data-item=".repeater-item">Xóa</button>
        </div>
        <input type="hidden" name="faqs[__INDEX__][id]">
        <div>
            <label>Câu hỏi</label>
            <input type="text" class="form-control" name="faqs[__INDEX__][question]">
        </div>
        <div style="margin-top:10px;">
            <label>Trả lời</label>
            <textarea class="form-control tinymce-editor" name="faqs[__INDEX__][answer]" rows="2"></textarea>
        </div>
    </div>
</template>
<template id="howto-template">
    <div class="repeater-item">
        <div class="repeater-header">
            <strong>How-To mới</strong>
            <button type="button" class="btn-link" data-remove data-item=".repeater-item">Xóa</button>
        </div>
        <input type="hidden" name="how_tos[__INDEX__][id]">
        <div class="grid-2">
            <div>
                <label>Tiêu đề</label>
                <input type="text" class="form-control" name="how_tos[__INDEX__][title]">
            </div>
            <div>
                <label>Hoạt động</label>
                <select class="form-control" name="how_tos[__INDEX__][is_active]">
                    <option value="1" selected>Hiển thị</option>
                    <option value="0">Ẩn</option>
                </select>
            </div>
        </div>
        <div style="margin-top:10px;">
            <label>Mô tả</label>
            <textarea class="form-control tinymce-editor" name="how_tos[__INDEX__][description]" rows="2"></textarea>
        </div>
    </div>
</template>
@endsection
