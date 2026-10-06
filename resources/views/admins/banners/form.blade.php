@php
    $isEdit = $banner->exists;
    $desktopUrl = $isEdit && $banner->image_desktop ? asset('clients/assets/img/banners/' . basename($banner->image_desktop)) : null;
    $mobileUrl = $isEdit && $banner->image_mobile ? asset('clients/assets/img/banners/' . basename($banner->image_mobile)) : null;
@endphp

@push('styles')
<style>
    .nobi-form-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 24px;
    }
    .nobi-form-title h2 {
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .nobi-form-title p {
        margin: 4px 0 0;
        font-size: 13.5px;
        color: #64748b;
    }

    .nobi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 22px 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }
    .nobi-card-header-line {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 14px;
        margin-bottom: 20px;
        border-bottom: 1px solid #f1f5f9;
    }
    .nobi-card-header-line h4 {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .nobi-card-header-line h4 i {
        color: #2563eb;
    }

    .nobi-label {
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
        display: block;
    }
    .nobi-label .required {
        color: #ef4444;
    }

    .nobi-input, .nobi-textarea, .nobi-form-select {
        width: 100%;
        padding: 9px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        font-size: 13.5px;
        color: #1e293b;
        background-color: #ffffff;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .nobi-input:focus, .nobi-textarea:focus, .nobi-form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        outline: none;
    }
    .nobi-help-text {
        font-size: 12px;
        color: #64748b;
        margin-top: 5px;
        display: block;
    }

    /* Modern Dropzone & Preview */
    .nobi-dropzone-box {
        position: relative;
        border: 2px dashed #cbd5e1;
        border-radius: 14px;
        background: #f8fafc;
        padding: 24px 16px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        overflow: hidden;
    }
    .nobi-dropzone-box:hover, .nobi-dropzone-box.dragover {
        border-color: #2563eb;
        background: #eff6ff;
    }
    .nobi-dropzone-icon {
        font-size: 36px;
        color: #94a3b8;
        margin-bottom: 10px;
        transition: color 0.2s ease;
    }
    .nobi-dropzone-box:hover .nobi-dropzone-icon {
        color: #2563eb;
    }
    .nobi-dropzone-text {
        font-size: 13.5px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 4px;
    }
    .nobi-dropzone-sub {
        font-size: 11.5px;
        color: #64748b;
    }
    .nobi-dropzone-input {
        position: absolute;
        inset: 0;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
        z-index: 5;
    }

    /* Live Preview Container */
    .nobi-preview-container {
        margin-top: 14px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #0f172a;
        overflow: hidden;
        position: relative;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
    }
    .nobi-preview-container img {
        width: 100%;
        display: block;
        object-fit: cover;
    }
    .nobi-preview-badge-ratio {
        position: absolute;
        top: 10px;
        left: 10px;
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(6px);
        color: #ffffff;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 6px;
        border: 1px solid rgba(255, 255, 255, 0.15);
        z-index: 2;
    }
    .nobi-preview-remove-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(239, 68, 68, 0.9);
        color: #ffffff;
        border: none;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        cursor: pointer;
        transition: transform 0.15s ease, background-color 0.15s ease;
        z-index: 2;
    }
    .nobi-preview-remove-btn:hover {
        background: #dc2626;
        transform: scale(1.1);
    }

    /* Big Switch */
    .nobi-switch-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        user-select: none;
        margin-bottom: 16px;
    }
    .nobi-switch-info h5 {
        font-size: 14px;
        font-weight: 700;
        margin: 0;
        color: #0f172a;
    }
    .nobi-switch-info p {
        font-size: 12px;
        margin: 2px 0 0;
        color: #64748b;
    }

    /* Sticky Footer Action Bar */
    .nobi-sticky-footer {
        position: sticky;
        bottom: 20px;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(14px);
        border: 1px solid #cbd5e1;
        border-radius: 16px;
        padding: 14px 28px;
        margin-top: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        z-index: 100;
        gap: 16px;
    }
</style>
@endpush

<form action="{{ $isEdit ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" 
      method="POST" 
      enctype="multipart/form-data" 
      id="bannerMainForm">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    {{-- Header --}}
    <div class="nobi-form-header">
        <div class="nobi-form-title">
            <h2>
                <i class="fa-solid {{ $isEdit ? 'fa-pen-to-square' : 'fa-plus' }} text-primary"></i>
                {{ $isEdit ? 'Chỉnh sửa banner: ' . $banner->title : 'Thêm mới banner' }}
            </h2>
            <p>{{ $isEdit ? 'Cập nhật lại thông tin, liên kết và ảnh đại diện của banner' : 'Tạo mới banner quảng cáo hoặc hero slide 3D cho website' }}</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.banners.index') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Quay lại
            </a>
            <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                <i class="fa-solid fa-floppy-disk me-1"></i> {{ $isEdit ? 'Lưu thay đổi' : 'Tạo banner' }}
            </button>
        </div>
    </div>

    {{-- Error Summary --}}
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            <h6 class="fw-bold mb-2 d-flex align-items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i> Có lỗi xảy ra, vui lòng kiểm tra lại:
            </h6>
            <ul class="mb-0 small ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        {{-- CỘT TRÁI (8 / 12) - THÔNG TIN & HÌNH ẢNH --}}
        <div class="col-lg-8">
            {{-- Card 1: Thông tin cơ bản --}}
            <div class="nobi-card">
                <div class="nobi-card-header-line">
                    <h4><i class="fa-solid fa-file-lines"></i> Thông tin banner</h4>
                    <span class="badge bg-light text-secondary border">Bắt buộc tiêu đề</span>
                </div>

                <div class="mb-3">
                    <label class="nobi-label">
                        Tiêu đề banner <span class="required">*</span>
                    </label>
                    <input type="text" 
                           name="title" 
                           class="nobi-input @error('title') is-invalid @enderror" 
                           value="{{ old('title', $banner->title) }}" 
                           placeholder="Ví dụ: MONO TALK, CANIFA Summer Drop, Flash Sale Tháng 10..." 
                           required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="nobi-label">Mô tả ngắn / Slogan</label>
                    <textarea name="description" 
                              rows="3" 
                              class="nobi-textarea @error('description') is-invalid @enderror" 
                              placeholder="Nhập câu mô tả ngắn gọn hoặc thông điệp quảng bá...">{{ old('description', $banner->description) }}</textarea>
                    <span class="nobi-help-text">Mô tả sẽ hiển thị dưới tiêu đề banner hoặc làm chú thích trên giao diện coverflow.</span>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="nobi-label">Đường dẫn liên kết (URL)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0">
                                <i class="fa-solid fa-link"></i>
                            </span>
                            <input type="url" 
                                   name="link" 
                                   class="nobi-input border-start-0 @error('link') is-invalid @enderror" 
                                   value="{{ old('link', $banner->link) }}" 
                                   placeholder="https://nobifashion.vn/san-pham/...">
                        </div>
                        <span class="nobi-help-text">Link điều hướng khi khách hàng bấm vào banner (để trống nếu không gắn link).</span>
                        @error('link')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="nobi-label">Cách mở trang</label>
                        <select name="taget" class="nobi-form-select">
                            <option value="_self" {{ old('taget', $banner->taget ?? '_self') === '_self' ? 'selected' : '' }}>
                                Mở cùng tab (_self)
                            </option>
                            <option value="_blank" {{ old('taget', $banner->taget ?? '_blank') === '_blank' ? 'selected' : '' }}>
                                Mở tab mới (_blank)
                            </option>
                        </select>
                        <span class="nobi-help-text">Hành vi trình duyệt khi nhấp vào.</span>
                    </div>
                </div>
            </div>

            {{-- Card 2: Tải lên hình ảnh --}}
            <div class="nobi-card">
                <div class="nobi-card-header-line">
                    <h4><i class="fa-solid fa-image"></i> Hình ảnh hiển thị</h4>
                    <span class="badge bg-light text-muted border">JPG, PNG, WEBP, AVIF (Tối đa 4MB)</span>
                </div>

                <div class="row g-4">
                    {{-- Ảnh Desktop --}}
                    <div class="col-md-7">
                        <label class="nobi-label">
                            <i class="fa-solid fa-desktop me-1 text-primary"></i> 
                            Ảnh Desktop <span class="required">{{ $isEdit ? '' : '*' }}</span>
                        </label>

                        <div class="nobi-dropzone-box" id="dropzoneDesktop">
                            <input type="file" 
                                   name="image_desktop" 
                                   id="inputImageDesktop" 
                                   class="nobi-dropzone-input" 
                                   accept="image/*" 
                                   {{ $isEdit ? '' : 'required' }}>
                            <div class="nobi-dropzone-icon">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </div>
                            <div class="nobi-dropzone-text">Kéo thả hoặc bấm để chọn ảnh Desktop</div>
                            <div class="nobi-dropzone-sub">
                                <strong>Khuyến nghị:</strong> 1920x800px hoặc 1440x600px (tỷ lệ 16:9 / 21:9)
                            </div>
                        </div>

                        {{-- Preview Desktop --}}
                        <div class="nobi-preview-container {{ $desktopUrl ? '' : 'd-none' }}" id="previewBoxDesktop">
                            <span class="nobi-preview-badge-ratio">Desktop View</span>
                            <img id="previewImgDesktop" src="{{ $desktopUrl ?: '#' }}" alt="Preview Desktop">
                            <button type="button" class="nobi-preview-remove-btn" title="Hủy ảnh vừa chọn" onclick="clearSelectedImage('Desktop')">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        @error('image_desktop')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Ảnh Mobile --}}
                    <div class="col-md-5">
                        <label class="nobi-label">
                            <i class="fa-solid fa-mobile-screen me-1 text-primary"></i> 
                            Ảnh Mobile <span class="badge bg-light text-muted border fw-normal ms-1">Tự động tạo nếu để trống</span>
                        </label>

                        <div class="nobi-dropzone-box" id="dropzoneMobile">
                            <input type="file" 
                                   name="image_mobile" 
                                   id="inputImageMobile" 
                                   class="nobi-dropzone-input" 
                                   accept="image/*">
                            <div class="nobi-dropzone-icon">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </div>
                            <div class="nobi-dropzone-text">Chọn ảnh Mobile riêng biệt (Tùy chọn)</div>
                            <div class="nobi-dropzone-sub">
                                Nếu để trống, hệ thống sẽ <strong>tự động thu nhỏ từ ảnh Desktop</strong>
                            </div>
                        </div>

                        {{-- Preview Mobile --}}
                        <div class="nobi-preview-container {{ $mobileUrl ? '' : 'd-none' }}" id="previewBoxMobile" style="max-width: 240px; margin-left: auto; margin-right: auto;">
                            <span class="nobi-preview-badge-ratio">Mobile View</span>
                            <img id="previewImgMobile" src="{{ $mobileUrl ?: '#' }}" alt="Preview Mobile">
                            <button type="button" class="nobi-preview-remove-btn" title="Hủy ảnh vừa chọn" onclick="clearSelectedImage('Mobile')">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        @error('image_mobile')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- CỘT PHẢI (4 / 12) - CÀI ĐẶT & XUẤT BẢN --}}
        <div class="col-lg-4">
            {{-- Card 3: Trạng thái & Vị trí --}}
            <div class="nobi-card">
                <div class="nobi-card-header-line">
                    <h4><i class="fa-solid fa-sliders"></i> Cài đặt hiển thị</h4>
                </div>

                {{-- Bật / Tắt hiển thị switch --}}
                <div class="nobi-switch-card" onclick="document.getElementById('switchIsActive').click()">
                    <div class="nobi-switch-info">
                        <h5>Trạng thái hiển thị</h5>
                        <p id="labelStatusText">{{ old('is_active', $banner->is_active ?? true) ? 'Đang bật hiển thị trên website' : 'Đang tạm ẩn' }}</p>
                    </div>
                    <div class="form-check form-switch mb-0 fs-5">
                        <input class="form-check-input" 
                               type="checkbox" 
                               role="switch" 
                               name="is_active" 
                               value="1" 
                               id="switchIsActive" 
                               {{ old('is_active', $banner->is_active ?? true) ? 'checked' : '' }}
                               onchange="updateStatusLabel(this.checked)">
                    </div>
                </div>

                {{-- Vị trí hiển thị --}}
                <div class="mb-3">
                    <label class="nobi-label">
                        Vị trí đặt banner <span class="required">*</span>
                    </label>
                    <select name="position" class="nobi-form-select @error('position') is-invalid @enderror" required>
                        <option value="">-- Chọn vị trí hiển thị --</option>
                        @foreach($positions ?? config('banners.positions', []) as $key => $label)
                            <option value="{{ $key }}" {{ old('position', $banner->position) === $key ? 'selected' : '' }}>
                                {{ $label }} ({{ $key }})
                            </option>
                        @endforeach
                    </select>
                    <span class="nobi-help-text">
                        <strong>Lưu ý:</strong> "Trang chủ" chính là bộ Coverflow 3D Hero Slider ở đầu trang chính.
                    </span>
                    @error('position')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Thứ tự hiển thị --}}
                <div class="mb-2">
                    <label class="nobi-label">Thứ tự ưu tiên (Order)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">#</span>
                        <input type="number" 
                               name="order" 
                               class="nobi-input border-start-0 @error('order') is-invalid @enderror" 
                               value="{{ old('order', $banner->order ?? ($isEdit ? $banner->order : '')) }}" 
                               min="0" 
                               placeholder="Tự động xếp cuối">
                    </div>
                    <span class="nobi-help-text">Số nhỏ hơn sẽ đứng trước (VD: 0, 1, 2...). Để trống sẽ tự tính số kế tiếp.</span>
                    @error('order')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Card 4: Lịch trình hiển thị --}}
            <div class="nobi-card">
                <div class="nobi-card-header-line">
                    <h4><i class="fa-regular fa-calendar-check"></i> Lịch hiển thị</h4>
                </div>

                {{-- Option Không giới hạn thời gian --}}
                <div class="form-check mb-3">
                    <input class="form-check-input" 
                           type="checkbox" 
                           id="checkPermanent" 
                           {{ (!$banner->end_at && !$banner->start_at) ? 'checked' : '' }}
                           onchange="toggleDateFields(this.checked)">
                    <label class="form-check-label fw-semibold text-dark small" for="checkPermanent">
                        Hiển thị vô thời hạn (Không hẹn giờ)
                    </label>
                </div>

                <div id="scheduleFieldsGroup">
                    <div class="mb-3">
                        <label class="nobi-label">Thời điểm bắt đầu</label>
                        <input type="datetime-local" 
                               name="start_at" 
                               id="inputStartAt" 
                               class="nobi-input @error('start_at') is-invalid @enderror"
                               value="{{ old('start_at', optional($banner->start_at)->format('Y-m-d\TH:i')) }}">
                        @error('start_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-2">
                        <label class="nobi-label">Thời điểm kết thúc</label>
                        <input type="datetime-local" 
                               name="end_at" 
                               id="inputEndAt" 
                               class="nobi-input @error('end_at') is-invalid @enderror"
                               value="{{ old('end_at', optional($banner->end_at)->format('Y-m-d\TH:i')) }}">
                        <span class="nobi-help-text">Hết giờ sẽ tự động ngưng hiển thị banner này.</span>
                        @error('end_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sticky Action Bar --}}
    <div class="nobi-sticky-footer">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-secondary border px-3 py-2">
                <i class="fa-solid fa-circle-info me-1"></i>
                {{ $isEdit ? 'Đang sửa Banner ID: #' . $banner->id : 'Đang tạo banner mới' }}
            </span>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.banners.index') }}" class="btn btn-outline-secondary px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Quay lại
            </a>
            <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                <i class="fa-solid fa-floppy-disk me-1"></i> {{ $isEdit ? 'Lưu thay đổi' : 'Tạo banner mới' }}
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
// 1. Live Instant Image Preview
function setupImageDropzone(inputId, dropzoneId, previewBoxId, previewImgId) {
    const input = document.getElementById(inputId);
    const dropzone = document.getElementById(dropzoneId);
    const previewBox = document.getElementById(previewBoxId);
    const previewImg = document.getElementById(previewImgId);

    if (!input || !dropzone || !previewBox || !previewImg) return;

    // Drag events
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        });
    });

    // File change
    input.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
                previewBox.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        }
    });
}

setupImageDropzone('inputImageDesktop', 'dropzoneDesktop', 'previewBoxDesktop', 'previewImgDesktop');
setupImageDropzone('inputImageMobile', 'dropzoneMobile', 'previewBoxMobile', 'previewImgMobile');

function clearSelectedImage(type) {
    const input = document.getElementById(`inputImage${type}`);
    const previewBox = document.getElementById(`previewBox${type}`);
    const previewImg = document.getElementById(`previewImg${type}`);

    if (input) input.value = '';
    // If not edit mode, hide preview box completely
    @if(!$isEdit)
        if (previewBox) previewBox.classList.add('d-none');
        if (previewImg) previewImg.src = '#';
    @else
        // In edit mode, restore original image if available
        const originalUrl = type === 'Desktop' ? '{{ $desktopUrl }}' : '{{ $mobileUrl }}';
        if (originalUrl) {
            if (previewImg) previewImg.src = originalUrl;
        } else {
            if (previewBox) previewBox.classList.add('d-none');
        }
    @endif
}

// 2. Status Label
function updateStatusLabel(isChecked) {
    const label = document.getElementById('labelStatusText');
    if (label) {
        label.textContent = isChecked ? 'Đang bật hiển thị trên website' : 'Đang tạm ẩn';
    }
}

// 3. Scheduling Toggle
function toggleDateFields(isPermanent) {
    const group = document.getElementById('scheduleFieldsGroup');
    const startInput = document.getElementById('inputStartAt');
    const endInput = document.getElementById('inputEndAt');

    if (isPermanent) {
        if (startInput) startInput.value = '';
        if (endInput) endInput.value = '';
        if (group) group.style.opacity = '0.5';
    } else {
        if (group) group.style.opacity = '1';
    }
}

// Initialize schedule fields state on load
document.addEventListener('DOMContentLoaded', function () {
    const checkPerm = document.getElementById('checkPermanent');
    if (checkPerm && checkPerm.checked) {
        toggleDateFields(true);
    }
});
</script>
@endpush
