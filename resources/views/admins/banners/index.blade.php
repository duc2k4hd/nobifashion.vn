@extends('admins.layouts.master')

@section('title', 'Quản lý banner')
@section('page-title', '🖼️ Banners')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/banners-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .filters {
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            margin-bottom:16px;
        }
        .filters input,
        .filters select {
            padding:7px 10px;
            border:1px solid #cbd5f5;
            border-radius:6px;
            font-size:13px;
        }
        .banner-card {
            background:#fff;
            border-radius:12px;
            padding:16px;
            box-shadow:0 2px 10px rgba(15,23,42,0.08);
            display:flex;
            gap:16px;
            margin-bottom:16px;
        }
        .banner-card img {
            width:200px;
            height:110px;
            object-fit:cover;
            border-radius:8px;
            border:1px solid #e2e8f0;
        }
        .badge {
            padding:3px 8px;
            border-radius:999px;
            font-size:11px;
            font-weight:600;
            display: flex; align-items: center;
            justify-content: center;
        }
        .badge-active { background:#dcfce7;color:#15803d; }
        .badge-inactive { background:#fee2e2;color:#b91c1c; }
    </style>
@endpush

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h2 style="margin:0;">Danh sách banner</h2>
            <p style="margin:4px 0 0;color:#94a3b8;">Quản lý ảnh hero/homepage.</p>
        </div>
        <a href="{{ route('admin.banners.create') }}" class="btn btn-primary">➕ Thêm banner</a>
    </div>

    <form method="GET" class="filters">
        <input type="text" name="keyword" placeholder="Tìm theo tiêu đề..." value="{{ request('keyword') }}">
        <select name="position">
            <option value="">-- Vị trí --</option>
            @foreach($positions ?? config('banners.positions', []) as $key => $label)
                <option value="{{ $key }}" {{ request('position') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status">
            <option value="">-- Trạng thái --</option>
            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Đang hiển thị</option>
            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Tạm tắt</option>
        </select>
        <button type="submit" class="btn btn-secondary">Lọc</button>
    </form>

    @forelse($banners as $banner)
        <div class="banner-card">
            <img src="{{ asset('clients/assets/img/banners/' . $banner->image_desktop) }}" alt="{{ $banner->title }}">
            <div style="flex:1;">
                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                    <h4 style="margin:0;">{{ $banner->title }}</h4>
                    @php
                        $allPositions = $positions ?? config('banners.positions', []);
                        $allBadges = $positionBadges ?? config('banners.position_badges', []);
                        
                        // Lấy label từ config
                        $positionText = $allPositions[$banner->position] ?? 'Vị trí không hợp lệ';
                        
                        // Lấy màu từ config
                        $badgeConfig = $allBadges[$banner->position] ?? ['bg' => '#e2e8f0', 'text' => '#64748b'];
                        
                        // Trạng thái active/inactive
                        $statusClass = $banner->is_active ? 'badge-active' : 'badge-inactive';
                        $statusText  = $banner->is_active ? 'Đang hiển thị' : 'Đã tắt';
                    @endphp

                    <span class="badge" style="background: {{ $badgeConfig['bg'] }}; color: {{ $badgeConfig['text'] }};">{{ $positionText }}</span>
                    <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                    <span class="badge" style="background:#f1f5f9;color:#475569;">Thứ tự: {{ $banner->order ?? 0 }}</span>
                </div>
                <p style="margin:6px 0;color:#475569;">{{ $banner->description }}</p>
                <div style="font-size:13px;color:#94a3b8;">
                    Hiển thị: {{ $banner->start_at?->format('d/m/Y H:i') ?? 'Ngay lập tức' }}
                    -
                    {{ $banner->end_at?->format('d/m/Y H:i') ?? 'Không giới hạn' }}
                </div>
                <div style="margin-top:10px;display:flex;gap:8px;">
                    <a href="{{ route('admin.banners.edit', $banner) }}" class="btn btn-secondary btn-sm">Sửa</a>
                    <form action="{{ route('admin.banners.toggle', $banner) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-primary btn-sm">
                            {{ $banner->is_active ? 'Tắt' : 'Bật' }}
                        </button>
                    </form>
                    <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST"
                          onsubmit="return confirm('Xoá banner này?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Xoá</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div style="text-align:center;padding:30px;background:#fff;border-radius:12px;">
            Chưa có banner nào.
        </div>
    @endforelse

    <div style="margin-top:16px;">
        {{ $banners->links() }}
    </div>
@endsection


