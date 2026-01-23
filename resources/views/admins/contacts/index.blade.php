@extends('admins.layouts.master')

@section('title', 'Quản lý liên hệ')
@section('page-title', '📧 Quản lý liên hệ')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/newsletter-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .contact-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .contact-table th, .contact-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #eef2f7;
            text-align: left;
            font-size: 13px;
        }
        .contact-table th {
            background: #f8fafc;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            font-weight: 600;
        }
        .contact-table tr:hover td {
            background: #f1f5f9;
        }
        .contact-table tr.status-new {
            background: #fef3c7;
        }
        .contact-table tr.status-processing {
            background: #dbeafe;
        }
        .contact-table tr.status-done {
            background: #d1fae5;
        }
        .contact-table tr.status-spam {
            background: #fee2e2;
        }
        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
            padding: 16px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .filter-bar input,
        .filter-bar select {
            padding: 6px 10px;
            border: 1px solid #cbd5f5;
            border-radius: 6px;
            font-size: 13px;
        }
        .badge-new {
            background: #f59e0b;
            color: #fff;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-processing {
            background: #3b82f6;
            color: #fff;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-done {
            background: #10b981;
            color: #fff;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-spam {
            background: #ef4444;
            color: #fff;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        .stats-card {
            background: #fff;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stats-card h3 {
            margin: 0;
            font-size: 24px;
            color: #0f172a;
        }
        .stats-card p {
            margin: 4px 0 0;
            font-size: 12px;
            color: #64748b;
        }
        .bulk-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 16px;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Statistics -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
            <div class="stats-card">
                <h3>{{ number_format($stats['total']) }}</h3>
                <p>Tổng liên hệ</p>
            </div>
            <div class="stats-card" style="border-left:3px solid #f59e0b;">
                <h3>{{ number_format($stats['new']) }}</h3>
                <p>Mới</p>
            </div>
            <div class="stats-card" style="border-left:3px solid #3b82f6;">
                <h3>{{ number_format($stats['processing']) }}</h3>
                <p>Đang xử lý</p>
            </div>
            <div class="stats-card" style="border-left:3px solid #10b981;">
                <h3>{{ number_format($stats['done']) }}</h3>
                <p>Đã xử lý</p>
            </div>
            <div class="stats-card" style="border-left:3px solid #ef4444;">
                <h3>{{ number_format($stats['spam']) }}</h3>
                <p>Spam</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="card">
            <h3>Bộ lọc</h3>
            <form method="GET" action="{{ route('admin.contacts.index') }}" class="filter-bar">
                <input type="text" name="search" placeholder="Tìm kiếm (tên, email, phone, subject)..." value="{{ $filters['search'] ?? '' }}" style="flex:1;min-width:200px;">
                
                <select name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="new" {{ ($filters['status'] ?? '') === 'new' ? 'selected' : '' }}>Mới</option>
                    <option value="processing" {{ ($filters['status'] ?? '') === 'processing' ? 'selected' : '' }}>Đang xử lý</option>
                    <option value="done" {{ ($filters['status'] ?? '') === 'done' ? 'selected' : '' }}>Đã xử lý</option>
                    <option value="spam" {{ ($filters['status'] ?? '') === 'spam' ? 'selected' : '' }}>Spam</option>
                </select>

                <select name="source">
                    <option value="">Tất cả nguồn</option>
                    <option value="web" {{ ($filters['source'] ?? '') === 'web' ? 'selected' : '' }}>Web</option>
                    <option value="landingpage" {{ ($filters['source'] ?? '') === 'landingpage' ? 'selected' : '' }}>Landing Page</option>
                    <option value="mobile" {{ ($filters['source'] ?? '') === 'mobile' ? 'selected' : '' }}>Mobile App</option>
                </select>

                <input type="date" name="date_from" placeholder="Từ ngày" value="{{ $filters['date_from'] ?? '' }}">
                <input type="date" name="date_to" placeholder="Đến ngày" value="{{ $filters['date_to'] ?? '' }}">

                <select name="sort">
                    <option value="newest" {{ ($filters['sort'] ?? 'newest') === 'newest' ? 'selected' : '' }}>Mới nhất</option>
                    <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                    <option value="status" {{ ($filters['sort'] ?? '') === 'status' ? 'selected' : '' }}>Theo trạng thái</option>
                </select>

                <button type="submit" class="btn btn-primary">🔍 Lọc</button>
                <a href="{{ route('admin.contacts.index') }}" class="btn btn-secondary">🔄 Reset</a>
            </form>
        </div>

        <!-- Bulk Actions -->
        <form id="bulk-action-form" method="POST" action="{{ route('admin.contacts.bulk-action') }}" style="display:none;">
            @csrf
            <input type="hidden" name="action" id="bulk-action-type">
            <input type="hidden" name="contact_ids" id="bulk-contact-ids">
        </form>

        <div class="bulk-actions">
            <input type="checkbox" id="select-all" style="margin-right:8px;">
            <label for="select-all" style="margin-right:16px;cursor:pointer;">Chọn tất cả</label>
            <button type="button" onclick="bulkAction('mark_spam')" class="btn btn-sm btn-danger">🚫 Đánh dấu spam</button>
            <button type="button" onclick="bulkAction('mark_processing')" class="btn btn-sm btn-info">⚙️ Đang xử lý</button>
            <button type="button" onclick="bulkAction('mark_done')" class="btn btn-sm btn-success">✅ Đã xử lý</button>
            <button type="button" onclick="bulkAction('delete')" class="btn btn-sm btn-warning" onclick="return confirm('Bạn có chắc muốn xóa các liên hệ đã chọn?');">🗑️ Xóa</button>
        </div>

        <!-- Contacts Table -->
        <div class="card">
            <table class="contact-table">
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="select-all-table"></th>
                        <th style="width:80px;">ID</th>
                        <th>Người gửi</th>
                        <th>Chủ đề</th>
                        <th style="width:120px;">Trạng thái</th>
                        <th style="width:100px;">Nguồn</th>
                        <th style="width:150px;">Ngày gửi</th>
                        <th style="width:120px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contacts as $contact)
                        <tr class="status-{{ $contact->status }}">
                            <td>
                                <input type="checkbox" class="contact-checkbox" value="{{ $contact->id }}">
                            </td>
                            <td>#{{ $contact->id }}</td>
                            <td>
                                <div style="font-weight:600;color:#0f172a;">{{ $contact->name ?? 'N/A' }}</div>
                                <div style="font-size:11px;color:#64748b;">{{ $contact->email ?? 'N/A' }}</div>
                                @if($contact->phone)
                                    <div style="font-size:11px;color:#64748b;">{{ $contact->phone }}</div>
                                @endif
                            </td>
                            <td>
                                <div style="font-weight:500;color:#0f172a;">{{ Str::limit($contact->subject ?? 'Không có tiêu đề', 50) }}</div>
                                <div style="font-size:11px;color:#64748b;margin-top:2px;">{{ Str::limit($contact->message ?? '', 60) }}</div>
                            </td>
                            <td>
                                <span class="badge-{{ $contact->status }}">{{ $contact->status_label }}</span>
                            </td>
                            <td>
                                <span style="font-size:11px;color:#64748b;">{{ $contact->source ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <div style="font-size:12px;color:#0f172a;">{{ $contact->created_at->format('d/m/Y') }}</div>
                                <div style="font-size:11px;color:#64748b;">{{ $contact->created_at->format('H:i') }}</div>
                            </td>
                            <td>
                                <div style="display:flex;gap:4px;">
                                    <a href="{{ route('admin.contacts.show', $contact) }}" class="btn btn-sm btn-primary" title="Xem chi tiết">👁️</a>
                                    <form action="{{ route('admin.contacts.destroy', $contact) }}" method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa liên hệ này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Xóa">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:40px;color:#64748b;">
                                Không tìm thấy liên hệ nào.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            @if($contacts->hasPages())
                <div style="margin-top:16px;display:flex;justify-content:center;">
                    {{ $contacts->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Select all functionality
    document.getElementById('select-all')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.contact-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });

    document.getElementById('select-all-table')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.contact-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
        document.getElementById('select-all').checked = this.checked;
    });

    // Bulk action
    function bulkAction(action) {
        const checked = Array.from(document.querySelectorAll('.contact-checkbox:checked')).map(cb => cb.value);
        if (checked.length === 0) {
            alert('Vui lòng chọn ít nhất một liên hệ.');
            return;
        }

        if (action === 'delete' && !confirm('Bạn có chắc muốn xóa ' + checked.length + ' liên hệ đã chọn?')) {
            return;
        }

        document.getElementById('bulk-action-type').value = action;
        document.getElementById('bulk-contact-ids').value = JSON.stringify(checked);
        document.getElementById('bulk-action-form').submit();
    }
</script>
@endpush

