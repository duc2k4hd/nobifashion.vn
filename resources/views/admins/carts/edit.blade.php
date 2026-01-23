@extends('admins.layouts.master')

@section('title', 'Chỉnh sửa giỏ hàng')
@section('page-title', '🛒 Chỉnh sửa giỏ hàng')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/cart-icon.webp') }}" type="image/x-icon">
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
        .form-control, select {
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
    <form action="{{ route('admin.carts.update', $cart) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:16px;">
            <a href="{{ route('admin.carts.show', $cart) }}" class="btn btn-secondary">↩️ Quay lại</a>
            <button type="submit" class="btn btn-primary">💾 Lưu</button>
        </div>

        <div class="card">
            <h3>Thông tin cơ bản</h3>
            <div class="grid-3">
                <div>
                    <label>Mã giỏ hàng</label>
                    <div class="readonly-field">{{ $cart->code ?? '—' }}</div>
                </div>
                <div>
                    <label>Trạng thái</label>
                    <select name="status" class="form-control" required>
                        <option value="active" {{ old('status', $cart->status) === 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                        <option value="ordered" {{ old('status', $cart->status) === 'ordered' ? 'selected' : '' }}>Đã đặt hàng</option>
                        <option value="abandoned" {{ old('status', $cart->status) === 'abandoned' ? 'selected' : '' }}>Bỏ quên</option>
                    </select>
                </div>
                <div>
                    <label>Người dùng</label>
                    <div class="readonly-field">
                        @if($cart->account)
                            {{ $cart->account->name ?? $cart->account->email }}
                        @else
                            Khách ({{ substr($cart->session_id, 0, 16) }}...)
                        @endif
                    </div>
                </div>
                <div>
                    <label>Tổng số lượng</label>
                    <div class="readonly-field">{{ number_format($cart->total_quantity) }}</div>
                </div>
                <div>
                    <label>Tổng tiền</label>
                    <div class="readonly-field"><strong>{{ number_format($cart->total_price) }} đ</strong></div>
                </div>
                <div>
                    <label>Ngày tạo</label>
                    <div class="readonly-field">{{ $cart->created_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:16px;">
            <a href="{{ route('admin.carts.show', $cart) }}" class="btn btn-secondary">↩️ Quay lại</a>
            <button type="submit" class="btn btn-primary">💾 Lưu</button>
        </div>
    </form>
@endsection

