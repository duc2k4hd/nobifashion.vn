@extends('admins.layouts.master')

@section('title', 'Quản lý địa chỉ khách hàng')
@section('page-title', '📍 Danh sách địa chỉ khách hàng')

@push('styles')
    <style>
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }
        .address-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.06);
        }
        .address-table th, .address-table td {
            padding: 14px;
            border-bottom: 1px solid #eef2ff;
            text-align: left;
            font-size: 13px;
        }
        .address-table th {
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.05em;
            background: #f8fafc;
            color: #475569;
        }
        .badge-default {
            background: #0ea5e9;
            color: #fff;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-type-home {
            background: #10b981;
            color: #fff;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-type-work {
            background: #f97316;
            color: #fff;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        .table-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
    </style>
@endpush

@section('content')
    <div class="card" style="margin-bottom:20px;">
        <div class="card-body">
            <form method="GET" class="filter-grid">
                <div>
                    <label class="form-label text-muted text-uppercase small">Tài khoản</label>
                    <select name="account_id" class="form-select">
                        <option value="">-- Tất cả --</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ ($filters['account_id'] ?? null) == $account->id ? 'selected' : '' }}>
                                {{ $account->name ?? $account->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label text-muted text-uppercase small">Tỉnh/Thành</label>
                    <input type="text" name="province" value="{{ $filters['province'] ?? '' }}" class="form-control" placeholder="VD: Hải Phòng">
                </div>
                <div>
                    <label class="form-label text-muted text-uppercase small">Quận/Huyện</label>
                    <input type="text" name="district" value="{{ $filters['district'] ?? '' }}" class="form-control" placeholder="VD: Lê Chân">
                </div>
                <div>
                    <label class="form-label text-muted text-uppercase small">Loại địa chỉ</label>
                    <select name="address_type" class="form-select">
                        <option value="">-- Tất cả --</option>
                        <option value="home" {{ ($filters['address_type'] ?? '') === 'home' ? 'selected' : '' }}>Nhà riêng</option>
                        <option value="work" {{ ($filters['address_type'] ?? '') === 'work' ? 'selected' : '' }}>Công việc</option>
                    </select>
                </div>
                <div>
                    <label class="form-label text-muted text-uppercase small">Mặc định</label>
                    <select name="is_default" class="form-select">
                        <option value="">-- Tất cả --</option>
                        <option value="1" {{ ($filters['is_default'] ?? '') === '1' ? 'selected' : '' }}>Địa chỉ mặc định</option>
                        <option value="0" {{ ($filters['is_default'] ?? '') === '0' ? 'selected' : '' }}>Khác</option>
                    </select>
                </div>
                <div style="display:flex;align-items:flex-end;gap:8px;">
                    <button type="submit" class="btn btn-primary">🔎 Lọc</button>
                    <a href="{{ route('admin.addresses.index') }}" class="btn btn-light">↺ Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="address-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Khách hàng</th>
                    <th>Thông tin</th>
                    <th>Khu vực</th>
                    <th>Loại</th>
                    <th>Mặc định</th>
                    <th>Ngày cập nhật</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($addresses as $address)
                    <tr>
                        <td>{{ $address->id }}</td>
                        <td>
                            <strong>{{ $address->account?->displayName() }}</strong><br>
                            <small class="text-muted">{{ $address->account?->email }}</small>
                        </td>
                        <td>
                            <div>{{ $address->full_name }}</div>
                            <div class="text-muted">{{ $address->phone_number }}</div>
                            <div>{{ $address->detail_address }}</div>
                        </td>
                        <td>
                            {{ $address->ward ? $address->ward . ', ' : '' }}{{ $address->district }}, {{ $address->province }}<br>
                            <small class="text-muted">Postal: {{ $address->postal_code }}</small>
                        </td>
                        <td>
                            <span class="badge {{ $address->address_type === 'work' ? 'badge-type-work' : 'badge-type-home' }}">
                                {{ $address->address_type === 'work' ? 'Work' : 'Home' }}
                            </span>
                        </td>
                        <td>
                            @if($address->is_default)
                                <span class="badge-default">DEFAULT</span>
                            @else
                                <form action="{{ route('admin.addresses.set-default', $address) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Đặt mặc định</button>
                                </form>
                            @endif
                        </td>
                        <td>{{ $address->updated_at?->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="table-actions">
                                <a href="{{ route('admin.addresses.show', $address) }}" class="btn btn-sm btn-light">👁️</a>
                                <a href="{{ route('admin.addresses.edit', $address) }}" class="btn btn-sm btn-info">✏️</a>
                                <form method="POST" action="{{ route('admin.addresses.destroy', $address) }}" onsubmit="return confirm('Xóa địa chỉ này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Chưa có địa chỉ nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $addresses->links() }}
    </div>
@endsection

