@extends('clients.layouts.master')

@section('title', 'Địa chỉ giao hàng của tôi')

@push('styles')
<style>
    .address-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        background: #fff;
    }
    .address-default {
        border-color: #0ea5e9;
        box-shadow: 0 10px 30px rgba(14,165,233,0.15);
    }
    .address-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit,minmax(260px,1fr));
        gap: 16px;
    }
</style>
@endpush

@section('content')
<div class="container py-4">
    <h2 class="mb-3">Địa chỉ giao hàng</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#createAddressForm">+ Thêm địa chỉ mới</button>
        <div class="collapse mt-3" id="createAddressForm">
            <div class="card card-body">
                <form method="POST" action="{{ route('client.addresses.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Họ tên</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" name="phone_number" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Địa chỉ chi tiết</label>
                            <input type="text" name="detail_address" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phường/Xã</label>
                            <input type="text" name="ward" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quận/Huyện</label>
                            <input type="text" name="district" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tỉnh/Thành</label>
                            <input type="text" name="province" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="postal_code" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Loại</label>
                            <select name="address_type" class="form-select">
                                <option value="home">Nhà riêng</option>
                                <option value="work">Công việc</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Ghi chú</label>
                            <textarea name="notes" rows="2" class="form-control"></textarea>
                        </div>
                        <div class="col-md-12 form-check">
                            <input type="checkbox" name="is_default" value="1" id="createDefault" class="form-check-input">
                            <label class="form-check-label" for="createDefault">Đặt làm địa chỉ mặc định</label>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary">Lưu địa chỉ</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="address-grid">
        @forelse($addresses as $address)
            <div class="address-card {{ $address->is_default ? 'address-default' : '' }}">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>{{ $address->full_name }}</h5>
                        <div class="text-muted">{{ $address->phone_number }}</div>
                    </div>
                    <span class="badge bg-{{ $address->address_type === 'work' ? 'warning' : 'success' }}">{{ $address->address_type }}</span>
                </div>
                <div class="mt-2">
                    {{ $address->detail_address }}<br>
                    {{ $address->ward ? $address->ward . ', ' : '' }}{{ $address->district }}, {{ $address->province }}<br>
                    <small class="text-muted">Postal: {{ $address->postal_code }}</small>
                </div>
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    @if(!$address->is_default)
                        <form method="POST" action="{{ route('client.addresses.set-default', $address->id) }}">
                            @csrf
                            <button class="btn btn-outline-primary btn-sm">Đặt mặc định</button>
                        </form>
                    @else
                        <span class="badge bg-info text-dark">Mặc định</span>
                    @endif
                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#editAddress{{ $address->id }}">Chỉnh sửa</button>
                    <form method="POST" action="{{ route('client.addresses.destroy', $address->id) }}" onsubmit="return confirm('Xóa địa chỉ này?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm">Xóa</button>
                    </form>
                </div>
                <div class="collapse mt-3" id="editAddress{{ $address->id }}">
                    <form method="POST" action="{{ route('client.addresses.update', $address->id) }}">
                        @csrf
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="text" name="full_name" value="{{ $address->full_name }}" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <input type="text" name="phone_number" value="{{ $address->phone_number }}" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <input type="text" name="detail_address" value="{{ $address->detail_address }}" class="form-control" required>
                            </div>
                            <div class="col-4">
                                <input type="text" name="ward" value="{{ $address->ward }}" class="form-control">
                            </div>
                            <div class="col-4">
                                <input type="text" name="district" value="{{ $address->district }}" class="form-control" required>
                            </div>
                            <div class="col-4">
                                <input type="text" name="province" value="{{ $address->province }}" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <input type="text" name="postal_code" value="{{ $address->postal_code }}" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <select name="address_type" class="form-select">
                                    <option value="home" {{ $address->address_type === 'home' ? 'selected' : '' }}>Nhà riêng</option>
                                    <option value="work" {{ $address->address_type === 'work' ? 'selected' : '' }}>Công việc</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <textarea name="notes" class="form-control" rows="2">{{ $address->notes }}</textarea>
                            </div>
                            <div class="col-12 form-check">
                                <input type="checkbox" name="is_default" value="1" id="editDefault{{ $address->id }}" class="form-check-input" {{ $address->is_default ? 'checked' : '' }}>
                                <label class="form-check-label" for="editDefault{{ $address->id }}">Đặt làm mặc định</label>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary btn-sm">Lưu</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <p>Bạn chưa có địa chỉ nào.</p>
        @endforelse
    </div>
</div>
@endsection

