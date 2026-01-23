@extends('admins.layouts.master')

@section('title', 'Chi tiết Newsletter')
@section('page-title', '📧 Chi tiết đăng ký')

@push('head')
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/newsletter-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .detail-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 12px 30px rgba(15,23,42,0.05);
        }
        .detail-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #475569;
            font-size: 13px;
        }
        .detail-value {
            color: #0f172a;
            font-size: 14px;
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
        .logs-table {
            width: 100%;
            border-collapse: collapse;
        }
        .logs-table th {
            background: #f8fafc;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
        }
        .logs-table td {
            padding: 12px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }
    </style>
@endpush

@section('content')
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Back button -->
        <a href="{{ route('admin.newsletters.index') }}" style="display: inline-flex; align-items: center; gap: 8px; color: #3b82f6; text-decoration: none; font-size: 14px;">
            <i class="fas fa-arrow-left"></i> Quay lại danh sách
        </a>

        <!-- Subscription Details -->
        <div class="detail-card">
            <h3 style="margin: 0 0 20px; font-size: 18px; color: #0f172a;">Thông tin đăng ký</h3>
            
            <div class="detail-row">
                <div class="detail-label">Email</div>
                <div class="detail-value">{{ $subscription->email }}</div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Trạng thái</div>
                <div class="detail-value">
                    <span class="badge badge-{{ $subscription->status }}">
                        {{ $subscription->status_label }}
                    </span>
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Nguồn</div>
                <div class="detail-value">{{ $subscription->source ?? 'N/A' }}</div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">IP Address</div>
                <div class="detail-value">{{ $subscription->ip_address ?? 'N/A' }}</div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">User Agent</div>
                <div class="detail-value" style="word-break: break-all;">{{ $subscription->user_agent ?? 'N/A' }}</div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Ngày đăng ký</div>
                <div class="detail-value">{{ $subscription->created_at->format('d/m/Y H:i:s') }}</div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Ngày xác nhận</div>
                <div class="detail-value">
                    {{ $subscription->verified_at ? $subscription->verified_at->format('d/m/Y H:i:s') : 'Chưa xác nhận' }}
                </div>
            </div>
            
            @if($subscription->note)
                <div class="detail-row">
                    <div class="detail-label">Ghi chú</div>
                    <div class="detail-value">{{ $subscription->note }}</div>
                </div>
            @endif
        </div>

        <!-- Actions -->
        <div class="detail-card">
            <h3 style="margin: 0 0 20px; font-size: 18px; color: #0f172a;">Thao tác</h3>
            
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                @if($subscription->status !== 'unsubscribed')
                    <button onclick="resendVerify({{ $subscription->id }})" style="padding: 10px 20px; background: #f59e0b; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-envelope"></i> Gửi lại email xác nhận
                    </button>
                @endif
                
                <button onclick="changeStatus({{ $subscription->id }}, '{{ $subscription->status }}')" style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-edit"></i> Đổi trạng thái
                </button>
                
                <button onclick="deleteSubscription({{ $subscription->id }})" style="padding: 10px 20px; background: #ef4444; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-trash"></i> Xóa
                </button>
            </div>
        </div>

        <!-- Related Logs -->
        @if($relatedLogs->count() > 0)
            <div class="detail-card">
                <h3 style="margin: 0 0 20px; font-size: 18px; color: #0f172a;">Lịch sử hoạt động</h3>
                
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Loại</th>
                            <th>Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($relatedLogs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                <td>
                                    <span style="padding: 4px 8px; background: #e0e7ff; color: #4338ca; border-radius: 6px; font-size: 11px;">
                                        {{ $log->type }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $payload = $log->payload ?? [];
                                        $meta = $payload['meta'] ?? [];
                                    @endphp
                                    @if(isset($meta['email']))
                                        Email: {{ $meta['email'] }}
                                    @endif
                                    @if(isset($meta['subject']))
                                        | Subject: {{ $meta['subject'] }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
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
            
            const note = prompt(`Đổi trạng thái sang "${newStatus}"?\nGhi chú (tùy chọn):`);
            if (note === null) return;
            
            const formData = new FormData();
            formData.append('status', newStatus);
            if (note) formData.append('note', note);
            
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
                if (data.success) {
                    window.location.href = '{{ route('admin.newsletters.index') }}';
                }
            });
        }
    </script>
@endpush

