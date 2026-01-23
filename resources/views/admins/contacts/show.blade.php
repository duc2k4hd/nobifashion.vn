@extends('admins.layouts.master')

@section('title', 'Chi tiết liên hệ')
@section('page-title', '📧 Chi tiết liên hệ')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/newsletter-icon.png') }}" type="image/x-icon">
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
            margin:0 0 12px;
            font-size:16px;
            color:#0f172a;
            font-weight:600;
        }
        .info-grid {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
            gap:16px;
        }
        .info-item {
            display:flex;
            flex-direction:column;
            gap:4px;
        }
        .info-label {
            font-size:12px;
            color:#64748b;
            font-weight:600;
            text-transform:uppercase;
            letter-spacing:0.05em;
        }
        .info-value {
            font-size:14px;
            color:#0f172a;
        }
        .badge-new {
            background: #f59e0b;
            color: #fff;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-processing {
            background: #3b82f6;
            color: #fff;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-done {
            background: #10b981;
            color: #fff;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-spam {
            background: #ef4444;
            color: #fff;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .message-box {
            background:#f8fafc;
            border-left:3px solid #3b82f6;
            padding:16px;
            border-radius:6px;
            white-space:pre-wrap;
            word-wrap:break-word;
            font-size:14px;
            line-height:1.6;
            color:#0f172a;
        }
        .timeline {
            margin-top:16px;
        }
        .timeline-item {
            padding:12px;
            border-left:2px solid #eef2f7;
            margin-bottom:12px;
            padding-left:16px;
            background:#f8fafc;
            border-radius:4px;
        }
        .timeline-item:last-child {
            margin-bottom:0;
        }
        .timeline-time {
            font-size:11px;
            color:#64748b;
            margin-top:4px;
        }
        .reply-form {
            background:#fef3c7;
            border:1px solid #f59e0b;
            padding:16px;
            border-radius:8px;
            margin-top:16px;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <a href="{{ route('admin.contacts.index') }}" class="btn btn-secondary">← Quay lại</a>
            <div style="display:flex;gap:8px;">
                @if($contact->trashed())
                    <form action="{{ route('admin.contacts.restore', $contact->id) }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-success">♻️ Khôi phục</button>
                    </form>
                @else
                    <form action="{{ route('admin.contacts.destroy', $contact) }}" method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa liên hệ này?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">🗑️ Xóa</button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Thông tin người gửi -->
        <div class="card">
            <h3>Thông tin người gửi</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Tên</div>
                    <div class="info-value">{{ $contact->name ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value">
                        @if($contact->email)
                            <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                        @else
                            N/A
                        @endif
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Số điện thoại</div>
                    <div class="info-value">
                        @if($contact->phone)
                            <a href="tel:{{ $contact->phone }}">{{ $contact->phone }}</a>
                        @else
                            N/A
                        @endif
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Trạng thái</div>
                    <div class="info-value">
                        <span class="badge-{{ $contact->status }}">{{ $contact->status_label }}</span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Nguồn gửi</div>
                    <div class="info-value">{{ $contact->source ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Ngày gửi</div>
                    <div class="info-value">{{ $contact->created_at->format('d/m/Y H:i:s') }}</div>
                </div>
                @if($contact->account)
                <div class="info-item">
                    <div class="info-label">Tài khoản</div>
                    <div class="info-value">
                        <a href="{{ route('admin.accounts.edit', $contact->account) }}">{{ $contact->account->name }}</a>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Nội dung liên hệ -->
        <div class="card">
            <h3>Nội dung liên hệ</h3>
            <div class="info-item" style="margin-bottom:12px;">
                <div class="info-label">Chủ đề</div>
                <div class="info-value" style="font-weight:600;font-size:16px;">{{ $contact->subject ?? 'Không có tiêu đề' }}</div>
            </div>
            <div class="message-box">
                {{ $contact->message ?? 'Không có nội dung' }}
            </div>
            @if($contact->hasAttachment())
                <div style="margin-top:12px;padding:12px;background:#f8fafc;border-radius:6px;">
                    <div class="info-label" style="margin-bottom:8px;">📎 File đính kèm</div>
                    <a href="{{ $contact->attachment_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                        📄 {{ $contact->attachment }}
                    </a>
                    <span style="font-size:11px;color:#64748b;margin-left:8px;">({{ strtoupper($contact->attachment_extension) }})</span>
                </div>
            @endif
        </div>

        <!-- Thông tin kỹ thuật -->
        <div class="card">
            <h3>Thông tin kỹ thuật</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">IP Address</div>
                    <div class="info-value">{{ $contact->ip_address ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">User Agent</div>
                    <div class="info-value" style="font-size:11px;word-break:break-all;">{{ Str::limit($contact->user_agent ?? 'N/A', 100) }}</div>
                </div>
            </div>
        </div>

        <!-- Cập nhật trạng thái -->
        <div class="card">
            <h3>Cập nhật trạng thái</h3>
            <form action="{{ route('admin.contacts.update-status', $contact) }}" method="POST">
                @csrf
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div>
                        <label for="status" style="font-size:13px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Trạng thái <span style="color:red;">*</span></label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="new" {{ $contact->status === 'new' ? 'selected' : '' }}>Mới</option>
                            <option value="processing" {{ $contact->status === 'processing' ? 'selected' : '' }}>Đang xử lý</option>
                            <option value="done" {{ $contact->status === 'done' ? 'selected' : '' }}>Đã xử lý</option>
                            <option value="spam" {{ $contact->status === 'spam' ? 'selected' : '' }}>Spam</option>
                        </select>
                    </div>
                    <div>
                        <label for="note" style="font-size:13px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Ghi chú (tùy chọn)</label>
                        <textarea name="note" id="note" rows="3" class="form-control" placeholder="Ghi chú nội bộ..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="align-self:flex-start;">💾 Cập nhật</button>
                </div>
            </form>
        </div>

        <!-- Ghi chú nội bộ -->
        <div class="card">
            <h3>Ghi chú nội bộ</h3>
            <form action="{{ route('admin.contacts.update-note', $contact) }}" method="POST">
                @csrf
                <textarea name="admin_note" rows="6" class="form-control" placeholder="Ghi chú nội bộ của admin...">{{ $contact->admin_note ?? '' }}</textarea>
                <button type="submit" class="btn btn-primary" style="margin-top:12px;">💾 Lưu ghi chú</button>
            </form>
        </div>

        <!-- Trả lời email -->
        @if($contact->canReply())
        <div class="card reply-form">
            <h3>📧 Trả lời email</h3>
            <form action="{{ route('admin.contacts.reply', $contact) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div>
                        <label for="reply-message" style="font-size:13px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Nội dung trả lời <span style="color:red;">*</span></label>
                        <textarea name="message" id="reply-message" rows="6" class="form-control" required placeholder="Nhập nội dung trả lời..."></textarea>
                        <small style="color:#64748b;font-size:11px;">Email sẽ được gửi tới: {{ $contact->email }}</small>
                    </div>
                    <div>
                        <label for="reply-attachment" style="font-size:13px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">File đính kèm (tùy chọn)</label>
                        <input type="file" name="attachment" id="reply-attachment" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx">
                        <small style="color:#64748b;font-size:11px;">Chấp nhận: jpg, jpeg, png, gif, pdf, doc, docx, xls, xlsx (tối đa 10MB)</small>
                    </div>
                    <button type="submit" class="btn btn-warning" style="align-self:flex-start;">📤 Gửi email trả lời</button>
                </div>
            </form>
        </div>
        @else
        <div class="card" style="background:#fee2e2;border:1px solid #ef4444;">
            <p style="margin:0;color:#991b1b;">⚠️ Không thể trả lời liên hệ này (thiếu email hoặc đã bị đánh dấu spam).</p>
        </div>
        @endif

        <!-- Timeline -->
        @if(!empty($contact->timeline) && count($contact->timeline) > 0)
        <div class="card">
            <h3>📋 Timeline xử lý</h3>
            <div class="timeline">
                @foreach(collect($contact->timeline)->sortByDesc('created_at') as $entry)
                    <div class="timeline-item">
                        <div style="font-weight:600;color:#0f172a;">{{ $entry['description'] ?? 'N/A' }}</div>
                        <div style="font-size:12px;color:#64748b;margin-top:4px;">
                            {{ $entry['user_name'] ?? 'System' }}
                            @if(!empty($entry['created_at']))
                                • {{ \Carbon\Carbon::parse($entry['created_at'])->format('d/m/Y H:i') }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
@endsection

