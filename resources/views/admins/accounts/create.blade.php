@extends('admins.layouts.master')

@section('title', 'Tạo tài khoản mới')
@section('page-title', '👤 Tạo tài khoản mới')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/account-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .card {
            background:#fff;
            border-radius:10px;
            padding:16px;
            box-shadow:0 1px 6px rgba(15,23,42,0.06);
            margin-bottom:16px;
        }
        .card > h3 {
            margin:0 0 8px;
            font-size:16px;
            font-weight:600;
            color:#0f172a;
        }
        .grid-3 {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
            gap:12px 16px;
        }
        .form-control, textarea, select {
            width:100%;
            padding:8px 10px;
            border:1px solid #cbd5f5;
            border-radius:6px;
            font-size:13px;
        }
        label {
            display:block;
            font-size:13px;
            font-weight:500;
            margin-bottom:4px;
            color:#111827;
        }
        .readonly-field {
            background:#f8fafc;
            border:1px dashed #cbd5f5;
            padding:8px 10px;
            border-radius:6px;
            font-size:13px;
        }
    </style>
@endpush

@section('content')
    <form action="{{ route('admin.accounts.store') }}" method="POST">
        @csrf

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:16px;">
            <a href="{{ route('admin.accounts.index') }}" class="btn btn-secondary">↩️ Quay lại danh sách</a>
            <button type="submit" class="btn btn-primary">💾 Lưu tài khoản</button>
        </div>

        <div class="card">
            <h3>Thông tin cơ bản</h3>
            <div class="grid-3">
                <div>
                    <label>Họ tên</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name') }}">
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email') }}" required>
                </div>
                <div>
                    <label>Vai trò</label>
                    <select name="role" class="form-control" required>
                        @foreach($roles as $role)
                            <option value="{{ $role }}" {{ old('role', 'user') === $role ? 'selected' : '' }}>
                                {{ ucfirst($role) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Trạng thái</label>
                    <select name="is_active" class="form-control">
                        <option value="1" {{ old('is_active', true) ? 'selected' : '' }}>Đang hoạt động</option>
                        <option value="0" {{ old('is_active', true) ? '' : 'selected' }}>Tạm khóa</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Bảo mật</h3>
            <div class="grid-3">
                <div>
                    <label>Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div>
                    <label>Nhập lại mật khẩu</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Ghi chú nội bộ</h3>
            <div>
                <label>Logs / Ghi chú</label>
                <textarea name="logs" rows="3" class="form-control">{{ old('logs') }}</textarea>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:16px;">
            <a href="{{ route('admin.accounts.index') }}" class="btn btn-secondary">↩️ Quay lại danh sách</a>
            <button type="submit" class="btn btn-primary">💾 Lưu tài khoản</button>
        </div>
    </form>
@endsection

