@extends('admins.layouts.master')

@section('title', 'Quản lý Flash Sale⚡')
@section('page-title', '⚡ Flash Sale')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/flash-sale-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .flash-sale-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .flash-sale-table th, .flash-sale-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #eef2f7;
            text-align: left;
        }
        .flash-sale-table th {
            background: #f8fafc;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
        }
        .flash-sale-table tr:hover td {
            background: #f1f5f9;
        }
        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .filter-bar input,
        .filter-bar select {
            padding: 8px 12px;
            border: 1px solid #cbd5f5;
            border-radius: 6px;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-draft {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-scheduled {
            background: #dbeafe;
            color: #1e40af;
        }
        .badge-running {
            background: #dcfce7;
            color: #15803d;
        }
        .badge-ended {
            background: #e5e7eb;
            color: #374151;
        }
        .badge-active {
            background: #dcfce7;
            color: #15803d;
        }
        .badge-inactive {
            background: #fee2e2;
            color: #b91c1c;
        }
        .btn-warning {
            background: #f59e0b;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-warning:hover {
            background: #d97706;
        }
        .actions {
            display: flex;
            gap: 8px;
        }
        .countdown {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
        }
    </style>
@endpush

@section('content')
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h2 style="margin:0;">Danh sách Flash Sale</h2>
            <a href="{{ route('admin.flash-sales.create') }}" class="btn btn-primary">➕ Tạo Flash Sale</a>
        </div>

        <form class="filter-bar" method="GET">
            <input type="text" name="search" placeholder="Tìm theo tên hoặc tag..."
                   value="{{ request('search') }}">
            <select name="status">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                <option value="running" {{ request('status') === 'running' ? 'selected' : '' }}>Running</option>
                <option value="ended" {{ request('status') === 'ended' ? 'selected' : '' }}>Ended</option>
            </select>
            <input type="date" name="from_date" placeholder="Từ ngày" value="{{ request('from_date') }}">
            <input type="date" name="to_date" placeholder="Đến ngày" value="{{ request('to_date') }}">
            <button type="submit" class="btn btn-primary">Lọc</button>
            <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-secondary">Xóa bộ lọc</a>
        </form>

        @if(session('success'))
            <div class="alert alert-success" style="margin-bottom:20px;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" style="margin-bottom:20px;">
                {{ session('error') }}
            </div>
        @endif

        <div class="table-responsive">
            <table class="flash-sale-table">
                <thead>
                <tr>
                    <th>Tên chương trình</th>
                    <th>Thời gian</th>
                    <th>Trạng thái</th>
                    <th>Số sản phẩm</th>
                    <th>Lượt xem</th>
                    <th>Người tạo</th>
                    <th>Thao tác</th>
                </tr>
                </thead>
                <tbody>
                @forelse($flashSales as $flashSale)
                    <tr>
                        <td>
                            <strong>{{ $flashSale->title }}</strong>
                            @if($flashSale->tag)
                                <br><small style="color:#64748b;">Tag: {{ $flashSale->tag }}</small>
                            @endif
                        </td>
                        <td>
                            <div>
                                <small><strong>Bắt đầu:</strong> {{ $flashSale->start_time->format('d/m/Y H:i') }}</small><br>
                                <small><strong>Kết thúc:</strong> {{ $flashSale->end_time->format('d/m/Y H:i') }}</small>
                            </div>
                            @if($flashSale->isActive())
                                <div class="countdown" id="countdown-{{ $flashSale->id }}"></div>
                            @endif
                        </td>
                        <td>
                            @if($flashSale->status === 'draft')
                                <span class="badge badge-draft">🟡 Draft</span>
                            @elseif($flashSale->status === 'active' && $flashSale->isUpcoming())
                                <span class="badge badge-scheduled">⏳ Scheduled</span>
                            @elseif($flashSale->status === 'active' && $flashSale->isActive())
                                <span class="badge badge-running">🟢 Running</span>
                            @else
                                <span class="badge badge-ended">🔴 Ended</span>
                            @endif
                            <br>
                            @if($flashSale->is_active)
                                <span class="badge badge-active" style="margin-top:4px;">Active</span>
                            @else
                                <span class="badge badge-inactive" style="margin-top:4px;">Inactive</span>
                            @endif
                            @if($flashSale->is_locked)
                                <br><small style="color:#f59e0b;">🔒 Locked</small>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $flashSale->items_count ?? 0 }}</strong> sản phẩm
                        </td>
                        <td>{{ number_format($flashSale->views ?? 0) }}</td>
                        <td>{{ $flashSale->creator->name ?? 'N/A' }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('admin.flash-sales.edit', $flashSale) }}" 
                                   class="btn btn-sm btn-secondary" 
                                   title="Sửa"
                                   @if($flashSale->isLocked()) onclick="alert('Flash Sale đang chạy, không thể chỉnh sửa!'); return false;" @endif>
                                    ✏️
                                </a>
                                <a href="{{ route('admin.flash-sales.items', $flashSale) }}" 
                                   class="btn btn-sm btn-info" 
                                   title="Xem sản phẩm">
                                    📦
                                </a>
                                <a href="{{ route('admin.flash-sales.preview', $flashSale) }}" 
                                   class="btn btn-sm btn-success" 
                                   title="Xem trước"
                                   target="_blank">
                                    👁️
                                </a>
                                <form action="{{ route('admin.flash-sales.toggle-active', $flashSale) }}"
                                      method="POST"
                                      style="display:inline;"
                                      onsubmit="return confirm('Bạn có chắc muốn {{ $flashSale->is_active ? 'tắt' : 'bật' }} Flash Sale này?');">
                                    @csrf
                                    <button type="submit" 
                                            class="btn btn-sm {{ $flashSale->is_active ? 'btn-warning' : 'btn-success' }}" 
                                            title="{{ $flashSale->is_active ? 'Tắt' : 'Bật' }}">
                                        {{ $flashSale->is_active ? '⏸️' : '▶️' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.flash-sales.duplicate', $flashSale) }}" 
                                      method="POST" 
                                      style="display:inline;"
                                      onsubmit="return confirm('Nhân bản Flash Sale này?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-secondary" title="Nhân bản">📋</button>
                                </form>
                                @if(!$flashSale->isActive())
                                    <form action="{{ route('admin.flash-sales.destroy', $flashSale) }}" 
                                          method="POST" 
                                          style="display:inline;"
                                          onsubmit="return confirm('Xóa Flash Sale này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Xóa">🗑️</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;">
                            Chưa có Flash Sale nào
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px;">
            {{ $flashSales->links() }}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Countdown timer cho các Flash Sale đang chạy
        document.addEventListener('DOMContentLoaded', () => {
            @foreach($flashSales as $flashSale)
                @if($flashSale->isActive())
                    (function() {
                        const endTime = new Date('{{ $flashSale->end_time->toIso8601String() }}').getTime();
                        const countdownEl = document.getElementById('countdown-{{ $flashSale->id }}');
                        if (!countdownEl) return;
                        
                        function updateCountdown() {
                            const now = new Date().getTime();
                            const distance = endTime - now;
                            
                            if (distance < 0) {
                                countdownEl.textContent = 'Đã kết thúc';
                                return;
                            }
                            
                            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                            
                            countdownEl.textContent = `Còn lại: ${days}d ${hours}h ${minutes}m ${seconds}s`;
                        }
                        
                        updateCountdown();
                        setInterval(updateCountdown, 1000);
                    })();
                @endif
            @endforeach
        });
    </script>
@endpush

