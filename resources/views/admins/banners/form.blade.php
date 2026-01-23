@php
    $isEdit = $banner->exists;
@endphp

<form action="{{ $isEdit ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:16px;">
        <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary">↩️ Quay lại danh sách</a>
        <button type="submit" class="btn btn-primary">💾 Lưu banner</button>
    </div>

    <div class="card">
        <h3>Thông tin cơ bản</h3>
        <div class="grid-3">
            <div>
                <label>Tiêu đề</label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $banner->title) }}" required>
            </div>
            <div>
                <label>Liên kết</label>
                <input type="url" name="link" class="form-control" value="{{ old('link', $banner->link) }}" placeholder="https://...">
            </div>
            <div>
                <label>Vị trí</label>
                <select name="position" class="form-control" required>
                    <option value="">-- Chọn vị trí --</option>
                    @foreach($positions ?? config('banners.positions', []) as $key => $label)
                        <option value="{{ $key }}" {{ old('position', $banner->position) === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Target</label>
                <select name="taget" class="form-control">
                    <option value="_blank" {{ old('taget', $banner->taget ?? '_blank') === '_blank' ? 'selected' : '' }}>Mở tab mới</option>
                    <option value="_self" {{ old('taget', $banner->taget ?? '_blank') === '_self' ? 'selected' : '' }}>Cùng trang</option>
                </select>
            </div>
            <div>
                <label>Bắt đầu hiển thị</label>
                <input type="datetime-local" name="start_at" class="form-control"
                       value="{{ old('start_at', optional($banner->start_at)->format('Y-m-d\TH:i')) }}">
            </div>
            <div>
                <label>Kết thúc</label>
                <input type="datetime-local" name="end_at" class="form-control"
                       value="{{ old('end_at', optional($banner->end_at)->format('Y-m-d\TH:i')) }}">
            </div>
            <div>
                <label>Trạng thái</label>
                <select name="is_active" class="form-control">
                    <option value="1" {{ old('is_active', $banner->is_active ?? true) ? 'selected' : '' }}>Đang bật</option>
                    <option value="0" {{ old('is_active', $banner->is_active ?? true) ? '' : 'selected' }}>Tắt</option>
                </select>
            </div>
            <div>
                <label>Thứ tự hiển thị</label>
                <input type="number" name="order" class="form-control" 
                       value="{{ old('order', $banner->order ?? ($isEdit ? $banner->order : '')) }}" 
                       min="0" 
                       placeholder="Tự động (để trống)">
                <small style="color:#94a3b8;">Số nhỏ hơn sẽ hiển thị trước. Để trống sẽ tự động đặt cuối cùng.</small>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Hình ảnh</h3>
        <div class="grid-3">
            <div>
                <label>Ảnh desktop</label>
                <input type="file" name="image_desktop" class="form-control" accept="image/*">
                @if($isEdit && $banner->image_desktop)
                    <small style="color:#94a3b8;">Ảnh hiện tại: {{ $banner->image_desktop }}</small>
                @endif
            </div>
            @if($isEdit)
                <div style="display:flex;gap:20px;margin-top:12px;">
                    <div>
                        <small>Preview desktop</small>
                        <img src="{{ asset('clients/assets/img/banners/' . $banner->image_desktop) }}" style="width:200px;border-radius:8px;border:1px solid #e2e8f0;">
                    </div>
                </div>
            @endif
            <div>
                <label>Ảnh mobile</label>
                <input type="file" name="image_mobile" class="form-control" accept="image/*">
                @if($isEdit && $banner->image_mobile)
                    <small style="color:#94a3b8;">Ảnh hiện tại: {{ $banner->image_mobile }}</small>
                @endif
            </div>
            @if($isEdit)
                <div style="display:flex;gap:20px;margin-top:12px;">
                    <div>
                        <small>Preview mobile</small>
                        <img src="{{ asset('clients/assets/img/banners/' . $banner->image_mobile) }}" style="width:120px;border-radius:8px;border:1px solid #e2e8f0;">
                    </div>
                </div>
            @endif
            <div>
                <label>Mô tả</label>
                <textarea name="description" rows="3" class="form-control">{{ old('description', $banner->description) }}</textarea>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:16px;">
        <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary">↩️ Quay lại danh sách</a>
        <button type="submit" class="btn btn-primary">💾 Lưu banner</button>
    </div>
</form>

