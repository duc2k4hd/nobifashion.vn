@extends('admins.layouts.master')

@section('title', 'Cập nhật vận đơn GHN')
@section('page-title', '🚚 Cập nhật vận đơn GHN')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/order-icon.png') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css">
@endpush

@push('styles')
    <style>
        .ghn-card {
            background:#fff;
            border-radius:10px;
            padding:20px;
            box-shadow:0 1px 6px rgba(15,23,42,0.08);
            margin-bottom:20px;
        }
        .ghn-grid {
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(260px,1fr));
            gap:16px;
        }
        .form-group label {
            font-weight:600;
            font-size:13px;
            color:#475569;
        }
        .form-control {
            border-radius:8px;
        }
    </style>
@endpush

@section('content')
    <div class="ghn-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div>
                <h3 style="margin:0;">Đơn hàng: {{ $order->code }}</h3>
                <p style="margin:4px 0 0;color:#64748b;">Mã vận đơn GHN: <strong>{{ $order->shipping_tracking_code }}</strong></p>
            </div>
            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary">↩️ Quay về chi tiết</a>
        </div>

        <form action="{{ route('admin.orders.update-ghn', $order) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="ghn-card">
                <h4>Thông tin người nhận</h4>
                <div class="ghn-grid">
                    <div class="form-group">
                        <label>Họ tên *</label>
                        <input type="text" name="to_name" class="form-control" value="{{ old('to_name', $order->receiver_name) }}" required>
                        @error('to_name')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại *</label>
                        <input type="text" name="to_phone" class="form-control" value="{{ old('to_phone', $order->receiver_phone) }}" required>
                        @error('to_phone')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>
                <div class="form-group">
                    <label>Địa chỉ chi tiết *</label>
                    <input type="text" name="to_address" class="form-control" value="{{ old('to_address', $order->shipping_address) }}" required>
                    @error('to_address')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="ghn-grid">
                    <div class="form-group">
                        <label>Tỉnh/Thành phố</label>
                        <select name="to_province_id" id="ghn-province-select" class="form-control">
                            <option value="">{{ old('to_province_id', $order->shipping_province_id) ? 'Đang tải...' : '-- Chọn tỉnh/thành --' }}</option>
                        </select>
                        @error('to_province_id')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Quận/Huyện *</label>
                        <select name="to_district_id" id="ghn-district-select" class="form-control" required disabled>
                            <option value="">-- Chọn quận/huyện --</option>
                        </select>
                        @error('to_district_id')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Phường/Xã *</label>
                        <select name="to_ward_code" id="ghn-ward-select" class="form-control" required disabled>
                            <option value="">-- Chọn phường/xã --</option>
                        </select>
                        @error('to_ward_code')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>
            </div>

            <div class="ghn-card">
                <h4>Thông tin vận đơn</h4>
                <div class="ghn-grid">
                    <div class="form-group">
                        <label>Người trả phí vận chuyển *</label>
                        <select name="payment_type_id" class="form-control" required>
                            <option value="1" {{ old('payment_type_id', $order->payment_method === 'cod' ? 2 : 1) == 1 ? 'selected' : '' }}>Người bán (Shop)</option>
                            <option value="2" {{ old('payment_type_id', $order->payment_method === 'cod' ? 2 : 1) == 2 ? 'selected' : '' }}>Người nhận</option>
                        </select>
                        @error('payment_type_id')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Ghi chú cho shipper</label>
                        <input type="text" name="note" class="form-control" value="{{ old('note', $order->admin_note) }}">
                        @error('note')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Yêu cầu khi giao *</label>
                        <select name="required_note" class="form-control" required>
                            @php
                                $requiredNote = old('required_note', 'KHONGCHOXEMHANG');
                            @endphp
                            <option value="KHONGCHOXEMHANG" {{ $requiredNote === 'KHONGCHOXEMHANG' ? 'selected' : '' }}>KHÔNG CHO XEM HÀNG</option>
                            <option value="CHOXEMHANGKHONGTHU" {{ $requiredNote === 'CHOXEMHANGKHONGTHU' ? 'selected' : '' }}>CHO XEM HÀNG (KHÔNG THỬ)</option>
                            <option value="CHOTHUHANG" {{ $requiredNote === 'CHOTHUHANG' ? 'selected' : '' }}>CHO THỬ HÀNG</option>
                        </select>
                        @error('required_note')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Thu hộ (COD)</label>
                        <input type="number" name="cod_amount" class="form-control" value="{{ old('cod_amount', $order->payment_method === 'cod' ? (int) $order->final_price : 0) }}">
                        @error('cod_amount')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>
                <div class="ghn-grid">
                    <div class="form-group">
                        <label>Khối lượng (gram)</label>
                        <input type="number" name="weight" class="form-control" value="{{ old('weight') }}">
                        @error('weight')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Chiều dài (cm)</label>
                        <input type="number" name="length" class="form-control" value="{{ old('length') }}">
                        @error('length')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Chiều rộng (cm)</label>
                        <input type="number" name="width" class="form-control" value="{{ old('width') }}">
                        @error('width')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Chiều cao (cm)</label>
                        <input type="number" name="height" class="form-control" value="{{ old('height') }}">
                        @error('height')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary">Hủy</a>
                <button type="submit" class="btn btn-primary">💾 Cập nhật GHN</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initGhnAddressSelectors({
                provinceSelectId: 'ghn-province-select',
                districtSelectId: 'ghn-district-select',
                wardSelectId: 'ghn-ward-select',
                oldProvince: '{{ old('to_province_id', $order->shipping_province_id) ?? '' }}',
                oldDistrict: '{{ old('to_district_id', $order->shipping_district_id) ?? '' }}',
                oldWard: '{{ old('to_ward_code', $order->shipping_ward_id) ?? '' }}'
            });
        });

        function initGhnAddressSelectors(config) {
            const { provinceSelectId, districtSelectId, wardSelectId, oldProvince, oldDistrict, oldWard } = config;
            const provinceEl = document.getElementById(provinceSelectId);
            const districtEl = document.getElementById(districtSelectId);
            const wardEl = document.getElementById(wardSelectId);
            if (!provinceEl || !districtEl || !wardEl) return;

            let provinceTS = null;
            let districtTS = null;
            let wardTS = null;

            const resetSelect = (select, placeholder, disable = true) => {
                select.innerHTML = `<option value="">${placeholder}</option>`;
                select.disabled = disable;
            };

            const recreateTomSelect = (instance, select, placeholder) => {
                if (instance) instance.destroy();
                return new TomSelect(select, {
                    placeholder,
                    allowEmptyOption: true,
                    searchField: ['text']
                });
            };

            const fetchJson = (url, options = {}) => {
                return fetch(url, options)
                    .then(res => res.json())
                    .then(json => json.data || []);
            };

            const loadProvinces = async (selectedValue) => {
                resetSelect(provinceEl, 'Đang tải tỉnh/thành...', true);
                try {
                    const data = await fetchJson('/api/v1/ghn/province');
                    provinceEl.innerHTML = '<option value="">-- Chọn tỉnh/thành --</option>';
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.provinceId;
                        option.textContent = item.provinceName;
                        provinceEl.appendChild(option);
                    });
                    provinceEl.disabled = false;
                    provinceTS = recreateTomSelect(provinceTS, provinceEl, 'Chọn tỉnh/thành');
                    provinceTS.on('change', value => loadDistricts(value));
                    if (selectedValue) {
                        provinceTS.setValue(String(selectedValue), true);
                        await loadDistricts(selectedValue, oldDistrict);
                    }
                } catch (error) {
                    console.error(error);
                    resetSelect(provinceEl, 'Không thể tải tỉnh/thành', true);
                }
            };

            const loadDistricts = async (provinceId, selectedValue = null) => {
                resetSelect(districtEl, '-- Chọn quận/huyện --', true);
                resetSelect(wardEl, '-- Chọn phường/xã --', true);
                if (!provinceId) {
                    if (districtTS) districtTS.destroy();
                    if (wardTS) wardTS.destroy();
                    return;
                }
                try {
                    const data = await fetchJson(`/api/v1/ghn/district/${provinceId}`, { method: 'POST' });
                    districtEl.innerHTML = '<option value="">-- Chọn quận/huyện --</option>';
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.districtID;
                        option.textContent = item.districtName;
                        districtEl.appendChild(option);
                    });
                    districtEl.disabled = false;
                    districtTS = recreateTomSelect(districtTS, districtEl, 'Chọn quận/huyện');
                    districtTS.on('change', value => loadWards(value));
                    if (selectedValue) {
                        districtTS.setValue(String(selectedValue), true);
                        await loadWards(selectedValue, oldWard);
                    }
                } catch (error) {
                    console.error(error);
                    resetSelect(districtEl, 'Không thể tải quận/huyện', true);
                }
            };

            const loadWards = async (districtId, selectedValue = null) => {
                resetSelect(wardEl, '-- Chọn phường/xã --', true);
                if (!districtId) {
                    if (wardTS) wardTS.destroy();
                    return;
                }
                try {
                    const data = await fetchJson(`/api/v1/ghn/ward/${districtId}`, { method: 'POST' });
                    wardEl.innerHTML = '<option value="">-- Chọn phường/xã --</option>';
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.wardCode;
                        option.textContent = item.wardName;
                        wardEl.appendChild(option);
                    });
                    wardEl.disabled = false;
                    wardTS = recreateTomSelect(wardTS, wardEl, 'Chọn phường/xã');
                    if (selectedValue) {
                        wardTS.setValue(String(selectedValue), true);
                    }
                } catch (error) {
                    console.error(error);
                    resetSelect(wardEl, 'Không thể tải phường/xã', true);
                }
            };

            loadProvinces(oldProvince || '{{ $order->shipping_province_id }}');
        }
    </script>
@endpush

