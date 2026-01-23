@extends('admins.layouts.master')

@section('title', 'Quản lý Newsletter')
@section('page-title', '📧 Newsletter')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/newsletter-icon.png') }}" type="image/x-icon">
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
@endpush

@push('styles')
    <style>
        .newsletter-page {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .stat-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        }
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 10px 40px rgba(15,23,42,0.08);
            border: 1px solid #e2e8f0;
        }
        .stat-card h6 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin: 0 0 6px;
        }
        .stat-card strong {
            font-size: 24px;
            color: #0f172a;
        }
        .filter-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 16px;
            box-shadow: 0 12px 30px rgba(15,23,42,0.05);
        }
        .filter-basic {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }
        .table-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(15,23,42,0.05);
        }
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-subscribed { background: #d1fae5; color: #065f46; }
        .badge-unsubscribed { background: #fee2e2; color: #991b1b; }
    </style>
@endpush

@section('content')
    <div class="newsletter-page">
        <!-- Stats -->
        <div class="stat-grid">
            <div class="stat-card">
                <h6>Tổng số</h6>
                <strong>{{ number_format($stats['total']) }}</strong>
            </div>
            <div class="stat-card">
                <h6>Đã đăng ký</h6>
                <strong style="color: #22c55e;">{{ number_format($stats['subscribed']) }}</strong>
            </div>
            <div class="stat-card">
                <h6>Chờ xác nhận</h6>
                <strong style="color: #f59e0b;">{{ number_format($stats['pending']) }}</strong>
            </div>
            <div class="stat-card">
                <h6>Đã hủy</h6>
                <strong style="color: #ef4444;">{{ number_format($stats['unsubscribed']) }}</strong>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <form method="GET" action="{{ route('admin.newsletters.index') }}" id="filter-form">
                <div class="filter-basic">
                    <div>
                        <label>Trạng thái</label>
                        <select name="status" class="form-select" onchange="document.getElementById('filter-form').submit();">
                            <option value="">Tất cả</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Chờ xác nhận</option>
                            <option value="subscribed" {{ request('status') === 'subscribed' ? 'selected' : '' }}>Đã đăng ký</option>
                            <option value="unsubscribed" {{ request('status') === 'unsubscribed' ? 'selected' : '' }}>Đã hủy</option>
                        </select>
                    </div>
                    <div>
                        <label>Nguồn</label>
                        <select name="source" class="form-select" onchange="document.getElementById('filter-form').submit();">
                            <option value="">Tất cả</option>
                            @foreach($sources as $source)
                                <option value="{{ $source }}" {{ request('source') === $source ? 'selected' : '' }}>{{ $source }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Từ ngày</label>
                        <input class="form-control" type="date" name="date_from" value="{{ request('date_from') }}" onchange="document.getElementById('filter-form').submit();">
                    </div>
                    <div>
                        <label>Đến ngày</label>
                        <input class="form-control" type="date" name="date_to" value="{{ request('date_to') }}" onchange="document.getElementById('filter-form').submit();">
                    </div>
                    <div>
                        <label>Tìm kiếm</label>
                        <input class="form-control" type="text" name="search" value="{{ request('search') }}" placeholder="Email, IP...">
                    </div>
                    <div style="display: flex; align-items: flex-end; gap: 8px;">
                        <button type="submit" style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer;">
                            Tìm kiếm
                        </button>
                        <a href="{{ route('admin.newsletters.index') }}" style="padding: 8px 16px; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; text-decoration: none; display: inline-block;">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Actions -->
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0;">Danh sách đăng ký</h2>
            <a href="{{ route('admin.newsletters.campaign') }}" style="padding: 10px 20px; background: #10b981; color: white; border-radius: 10px; text-decoration: none; font-weight: 600;">
                <i class="fas fa-paper-plane"></i> Gửi chiến dịch
            </a>
        </div>

        <!-- Table -->
        <div class="table-card">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 600; color: #475569;">Email</th>
                        <th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 600; color: #475569;">Trạng thái</th>
                        <th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 600; color: #475569;">Nguồn</th>
                        <th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 600; color: #475569;">IP</th>
                        <th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 600; color: #475569;">Ngày đăng ký</th>
                        <th style="padding: 12px; text-align: center; font-size: 12px; font-weight: 600; color: #475569;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscriptions as $subscription)
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 12px; font-size: 13px;">{{ $subscription->email }}</td>
                            <td style="padding: 12px;">
                                <span class="badge badge-{{ $subscription->status }}">
                                    {{ $subscription->status_label }}
                                </span>
                            </td>
                            <td style="padding: 12px; font-size: 13px; color: #64748b;">{{ $subscription->source ?? 'N/A' }}</td>
                            <td style="padding: 12px; font-size: 13px; color: #64748b;">{{ $subscription->ip_address ?? 'N/A' }}</td>
                            <td style="padding: 12px; font-size: 13px; color: #64748b;">{{ $subscription->created_at->format('d/m/Y H:i') }}</td>
                            <td style="padding: 12px; text-align: center;">
                                <div style="display: flex; gap: 6px; justify-content: center;">
                                    <a title="Xem chi tiết" href="{{ route('admin.newsletters.show', $subscription->id) }}" style="padding: 6px 12px; background: #3b82f6; color: white; border-radius: 6px; text-decoration: none; font-size: 11px;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($subscription->status !== 'unsubscribed')
                                        <button title="Không nhận thông báo" onclick="resendVerify({{ $subscription->id }})" style="padding: 6px 12px; background: #f59e0b; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 11px;">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                    @endif
                                    <button title="Thay đổi trạng thái" onclick="changeStatus({{ $subscription->id }}, '{{ $subscription->status }}')" style="padding: 6px 12px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 11px;">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button title="Xóa" onclick="deleteSubscription({{ $subscription->id }})" style="padding: 6px 12px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 11px;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 40px; text-align: center; color: #94a3b8;">
                                Không có dữ liệu
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div style="display: flex; justify-content: center;">
            {{ $subscriptions->links() }}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function resendVerify(id) {
            if (!confirm('Gửi lại email xác nhận?')) return;
            
            fetch(`/admin/newsletters/${id}/resend-verify`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function changeStatus(id, currentStatus) {
            const statuses = {
                'pending': 'subscribed',
                'subscribed': 'unsubscribed',
                'unsubscribed': 'pending'
            };
            const newStatus = statuses[currentStatus] || 'subscribed';
            
            if (!confirm(`Đổi trạng thái sang "${newStatus}"?`)) return;
            
            const formData = new FormData();
            formData.append('status', newStatus);
            
            fetch(`/admin/newsletters/${id}/change-status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function deleteSubscription(id) {
            if (!confirm('Xóa đăng ký này?')) return;
            
            fetch(`/admin/newsletters/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }
    </script>
@endpush

