@extends('admins.layouts.master')

@section('title', 'Chỉnh sửa địa chỉ #' . $address->id)
@section('page-title', '✏️ Chỉnh sửa địa chỉ')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.addresses.show', $address) }}" class="btn btn-secondary">← Quay lại</a>
    </div>

    <form method="POST" action="{{ route('admin.addresses.update', $address) }}">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Họ tên</label>
                        <input type="text" name="full_name" value="{{ old('full_name', $address->full_name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number', $address->phone_number) }}" class="form-control" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Địa chỉ chi tiết</label>
                        <input type="text" name="detail_address" value="{{ old('detail_address', $address->detail_address) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phường/Xã</label>
                        <input type="text" name="ward" value="{{ old('ward', $address->ward) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Quận/Huyện</label>
                        <input type="text" name="district" value="{{ old('district', $address->district) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tỉnh/Thành</label>
                        <input type="text" name="province" value="{{ old('province', $address->province) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Postal code</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code', $address->postal_code) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Quốc gia</label>
                        <input type="text" name="country" value="{{ old('country', $address->country) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Loại địa chỉ</label>
                        <select name="address_type" class="form-select">
                            <option value="home" {{ old('address_type', $address->address_type) === 'home' ? 'selected' : '' }}>Nhà riêng</option>
                            <option value="work" {{ old('address_type', $address->address_type) === 'work' ? 'selected' : '' }}>Công việc</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Latitude</label>
                        <input type="text" name="latitude" value="{{ old('latitude', $address->latitude) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Longitude</label>
                        <input type="text" name="longitude" value="{{ old('longitude', $address->longitude) }}" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Ghi chú</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $address->notes) }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_default" value="1" {{ old('is_default', $address->is_default) ? 'checked' : '' }}>
                            <label class="form-check-label">Đặt làm địa chỉ mặc định</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button class="btn btn-primary">💾 Lưu thay đổi</button>
        </div>
    </form>
@endsection

