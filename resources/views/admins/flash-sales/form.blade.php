@extends('admins.layouts.master')

@php
    $isEdit = $flashSale->exists;
    $pageTitle = $isEdit ? 'Chỉnh sửa Flash Sale' : 'Tạo Flash Sale mới';
@endphp

@section('title', $pageTitle)
@section('page-title', $pageTitle)

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/flash-sale-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 1px 6px rgba(15,23,42,0.06);
            margin-bottom: 20px;
        }
        .card h3 {
            margin: 0 0 16px;
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
            color: #111827;
        }
        .form-control,
        textarea,
        select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5f5;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-control:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        .image-preview {
            margin-top: 10px;
        }
        .image-preview img {
            max-width: 300px;
            max-height: 200px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fbbf24;
        }
        .locked-notice {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
@endpush

@section('content')
    <div>
        @if($isEdit && $flashSale->isLocked())
            <div class="locked-notice">
                <strong>🔒 Flash Sale đang chạy</strong> - Không thể chỉnh sửa thông tin. Chỉ có thể bật/tắt.
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ $isEdit ? route('admin.flash-sales.update', $flashSale) : route('admin.flash-sales.store') }}" 
              method="POST" 
              enctype="multipart/form-data">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <!-- Thông tin cơ bản -->
            <div class="card">
                <h3>📋 Thông tin cơ bản</h3>
                
                <div class="form-group">
                    <label for="title">Tên chương trình Flash Sale <span style="color:red;">*</span></label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           class="form-control" 
                           value="{{ old('title', $flashSale->title) }}"
                           required
                           @if($flashSale->isLocked()) readonly @endif>
                    @error('title')
                        <small style="color:red;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="tag">Tag/Label (VD: 9.9, 11.11, Black Friday)</label>
                    <input type="text" 
                           id="tag" 
                           name="tag" 
                           class="form-control" 
                           value="{{ old('tag', $flashSale->tag) }}"
                           placeholder="9.9"
                           @if($flashSale->isLocked()) readonly @endif>
                    @error('tag')
                        <small style="color:red;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description">Mô tả</label>
                    <textarea id="description" 
                              name="description" 
                              class="form-control" 
                              rows="4"
                              @if($flashSale->isLocked()) readonly @endif>{{ old('description', $flashSale->description) }}</textarea>
                    @error('description')
                        <small style="color:red;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="banner">Banner chương trình</label>
                    <input type="file" 
                           id="banner" 
                           name="banner" 
                           class="form-control" 
                           accept="image/*"
                           @if($flashSale->isLocked()) disabled @endif>
                    @if($flashSale->banner)
                        <div class="image-preview">
                            <img src="{{ asset($flashSale->banner) }}" alt="Banner">
                        </div>
                    @endif
                    @error('banner')
                        <small style="color:red;">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <!-- Thời gian -->
            <div class="card">
                <h3>⏰ Thời gian</h3>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label for="start_time">Thời gian bắt đầu <span style="color:red;">*</span></label>
                        <input type="datetime-local" 
                               id="start_time" 
                               name="start_time" 
                               class="form-control" 
                               value="{{ old('start_time', $flashSale->start_time ? $flashSale->start_time->format('Y-m-d\TH:i') : '') }}"
                               required
                               @if($flashSale->isLocked()) readonly @endif>
                        @error('start_time')
                            <small style="color:red;">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="end_time">Thời gian kết thúc <span style="color:red;">*</span></label>
                        <input type="datetime-local" 
                               id="end_time" 
                               name="end_time" 
                               class="form-control" 
                               value="{{ old('end_time', $flashSale->end_time ? $flashSale->end_time->format('Y-m-d\TH:i') : '') }}"
                               required
                               @if($flashSale->isLocked()) readonly @endif>
                        @error('end_time')
                            <small style="color:red;">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Cấu hình -->
            <div class="card">
                <h3>⚙️ Cấu hình</h3>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label for="status">Trạng thái <span style="color:red;">*</span></label>
                        <select id="status" 
                                name="status" 
                                class="form-control"
                                required
                                @if($flashSale->isLocked()) disabled @endif>
                            <option value="draft" {{ old('status', $flashSale->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="active" {{ old('status', $flashSale->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="expired" {{ old('status', $flashSale->status) === 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                        @error('status')
                            <small style="color:red;">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="is_active">Bật/Tắt</label>
                        <select id="is_active" 
                                name="is_active" 
                                class="form-control">
                            <option value="1" {{ old('is_active', $flashSale->is_active) ? 'selected' : '' }}>Bật</option>
                            <option value="0" {{ !old('is_active', $flashSale->is_active) ? 'selected' : '' }}>Tắt</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="max_per_user">Giới hạn mua mỗi khách (Global)</label>
                        <input type="number" 
                               id="max_per_user" 
                               name="max_per_user" 
                               class="form-control" 
                               value="{{ old('max_per_user', $flashSale->max_per_user) }}"
                               min="1"
                               placeholder="Không giới hạn nếu để trống"
                               @if($flashSale->isLocked()) readonly @endif>
                        @error('max_per_user')
                            <small style="color:red;">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="display_limit">Số lượng hiển thị trên frontend</label>
                        <input type="number" 
                               id="display_limit" 
                               name="display_limit" 
                               class="form-control" 
                               value="{{ old('display_limit', $flashSale->display_limit ?? 20) }}"
                               min="1"
                               max="100"
                               @if($flashSale->isLocked()) readonly @endif>
                        @error('display_limit')
                            <small style="color:red;">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="product_add_mode">Chế độ thêm sản phẩm <span style="color:red;">*</span></label>
                    <select id="product_add_mode" 
                            name="product_add_mode" 
                            class="form-control"
                            required
                            @if($flashSale->isLocked() || ($isEdit && $flashSale->items()->count() > 0)) disabled @endif>
                        <option value="manual" {{ old('product_add_mode', $flashSale->product_add_mode ?? 'manual') === 'manual' ? 'selected' : '' }}>
                            Thêm thủ công từng sản phẩm
                        </option>
                        <option value="auto_by_category" {{ old('product_add_mode', $flashSale->product_add_mode) === 'auto_by_category' ? 'selected' : '' }}>
                            Tự động lấy 20 sản phẩm nổi bật từ mỗi danh mục
                        </option>
                    </select>
                    <small style="color:#64748b;display:block;margin-top:4px;">
                        ⚠️ Lưu ý: Chỉ được chọn 1 chế độ. Không thể thay đổi sau khi đã thêm sản phẩm.
                    </small>
                    @error('product_add_mode')
                        <small style="color:red;">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <!-- Actions -->
            <div style="display:flex;gap:10px;margin-top:20px;">
                <button type="submit" class="btn btn-primary">
                    @if($isEdit)
                        💾 Cập nhật
                    @else
                        ➕ Tạo mới
                    @endif
                </button>
                @if($isEdit && !$flashSale->isActive())
                    <a href="{{ route('admin.flash-sales.publish', $flashSale) }}" 
                       class="btn btn-success"
                       onclick="return confirm('Xuất bản Flash Sale này?')">
                        📢 Xuất bản
                    </a>
                @endif
                <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-secondary">Hủy</a>
                @if($isEdit)
                    <a href="{{ route('admin.flash-sales.items', $flashSale) }}" class="btn btn-info">
                        📦 Quản lý sản phẩm
                    </a>
                @endif
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        // Tính thời lượng tự động
        document.addEventListener('DOMContentLoaded', () => {
            const startTime = document.getElementById('start_time');
            const endTime = document.getElementById('end_time');
            
            function updateDuration() {
                if (startTime.value && endTime.value) {
                    const start = new Date(startTime.value);
                    const end = new Date(endTime.value);
                    const diff = end - start;
                    
                    if (diff > 0) {
                        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                        
                        let durationText = document.getElementById('duration-text');
                        if (!durationText) {
                            durationText = document.createElement('small');
                            durationText.id = 'duration-text';
                            durationText.style.color = '#64748b';
                            durationText.style.marginTop = '4px';
                            durationText.style.display = 'block';
                            endTime.parentElement.appendChild(durationText);
                        }
                        durationText.textContent = `Thời lượng: ${days} ngày ${hours} giờ ${minutes} phút`;
                    }
                }
            }
            
            if (startTime && endTime) {
                startTime.addEventListener('change', updateDuration);
                endTime.addEventListener('change', updateDuration);
                updateDuration();
            }
        });
    </script>
@endpush

