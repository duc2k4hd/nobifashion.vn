@extends('admins.layouts.master')

@section('title', 'Quản lý Email')
@section('page-title', '📧 Quản lý Email')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/email-icon.webp') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
        }
        .filters input,
        .filters select {
            padding: 7px 10px;
            border: 1px solid #cbd5f5;
            border-radius: 6px;
            font-size: 13px;
        }
        .email-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 10px rgba(15,23,42,0.08);
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .email-info {
            flex: 1;
        }
        .email-address {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .email-name {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 4px;
        }
        .email-description {
            font-size: 12px;
            color: #94a3b8;
        }
        .email-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-default { background: #fef3c7; color: #92400e; }
        .badge-active { background: #dcfce7; color: #15803d; }
        .badge-inactive { background: #fee2e2; color: #b91c1c; }
    </style>
@endpush

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <a href="{{ route('admin.email-accounts.create') }}" class="btn btn-primary">➕ Thêm email mới</a>
    </div>

    <div class="filters">
        <input type="text" name="keyword" placeholder="Tìm kiếm email hoặc tên..." 
               value="{{ request('keyword') }}" 
               onkeypress="if(event.key==='Enter') this.form.submit()">
        <select name="status" onchange="this.form.submit()">
            <option value="">Tất cả trạng thái</option>
            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Đang hoạt động</option>
            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Không hoạt động</option>
        </select>
    </div>

    @if($emailAccounts->isEmpty())
        <div class="card" style="text-align: center; padding: 40px;">
            <p style="color: #64748b;">Chưa có email nào. Hãy thêm email đầu tiên!</p>
        </div>
    @else
        @foreach($emailAccounts as $emailAccount)
            <div class="email-card">
                <div class="email-info">
                    <div class="email-address">{{ $emailAccount->email }}</div>
                    <div class="email-name">{{ $emailAccount->name }}</div>
                    @if($emailAccount->description)
                        <div class="email-description">{{ $emailAccount->description }}</div>
                    @endif
                    <div style="margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap;">
                        @if($emailAccount->is_default)
                            <span class="badge badge-default">Mặc định</span>
                        @endif
                        @if($emailAccount->is_active)
                            <span class="badge badge-active">Đang hoạt động</span>
                        @else
                            <span class="badge badge-inactive">Không hoạt động</span>
                        @endif
                    </div>
                </div>
                <div class="email-actions">
                    @if(!$emailAccount->is_default)
                        <form action="{{ route('admin.email-accounts.set-default', $emailAccount) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-secondary" title="Đặt làm mặc định">⭐</button>
                        </form>
                    @endif
                    <a href="{{ route('admin.email-accounts.edit', $emailAccount) }}" class="btn btn-sm btn-primary">✏️ Sửa</a>
                    @if(!$emailAccount->is_default)
                        <form action="{{ route('admin.email-accounts.destroy', $emailAccount) }}" method="POST" style="display: inline;" 
                              onsubmit="return confirm('Bạn có chắc muốn xóa email này?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">🗑️ Xóa</button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach

        <div style="margin-top: 20px;">
            {{ $emailAccounts->links() }}
        </div>
    @endif
@endsection

