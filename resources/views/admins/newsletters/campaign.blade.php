@extends('admins.layouts.master')

@section('title', 'Gửi chiến dịch Newsletter')
@section('page-title', '📧 Gửi chiến dịch Email')

@push('head')
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/newsletter-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .campaign-form {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 24px;
            box-shadow: 0 12px 30px rgba(15,23,42,0.05);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #475569;
            font-size: 13px;
            margin-bottom: 8px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            border: 1px solid #cbd5f5;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
            background: #f8fafc;
        }
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        .filter-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            margin-top: 20px;
        }
        .filter-section h4 {
            margin: 0 0 16px;
            font-size: 14px;
            color: #0f172a;
        }
        .filter-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }
        .stat-info {
            background: #e0f2fe;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #0369a1;
        }
    </style>
@endpush

@section('content')
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Back button -->
        <a href="{{ route('admin.newsletters.index') }}" style="display: inline-flex; align-items: center; gap: 8px; color: #3b82f6; text-decoration: none; font-size: 14px;">
            <i class="fas fa-arrow-left"></i> Quay lại danh sách
        </a>

        <!-- Stats -->
        <div class="stat-info">
            <strong>📊 Thống kê:</strong> Hiện có <strong>{{ number_format($stats['subscribed']) }}</strong> người đã đăng ký và sẵn sàng nhận email.
        </div>

        <!-- Campaign Form -->
        <div class="campaign-form">
            <h2 style="margin: 0 0 24px; font-size: 20px; color: #0f172a;">Gửi email hàng loạt</h2>
            
            <form id="campaign-form" method="POST" action="{{ route('admin.newsletters.send') }}">
                @csrf
                
                <!-- Email From -->
                <div class="form-group">
                    <label>Gửi từ email <span style="color: red;">*</span></label>
                    <select name="email_account_id" required>
                        <option value="">-- Chọn email gửi đi --</option>
                        @foreach(\App\Helpers\EmailHelper::getEmailOptions() as $id => $label)
                            <option value="{{ $id }}" {{ old('email_account_id') == $id ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <small style="display: block; margin-top: 4px; color: #64748b; font-size: 12px;">
                        Chọn email sẽ hiển thị trong phần "From" của email gửi đi
                    </small>
                </div>

                <!-- Email Content -->
                <div class="form-group">
                    <label>Tiêu đề email <span style="color: red;">*</span></label>
                    <input type="text" name="subject" required placeholder="Ví dụ: Khuyến mãi đặc biệt tháng này!">
                </div>
                
                <div class="form-group">
                    <label>Template <span style="color: red;">*</span></label>
                    <select name="template" required>
                        <option value="marketing">Marketing Email (Mặc định)</option>
                    </select>
                    <small style="color: #64748b; font-size: 12px; margin-top: 4px; display: block;">
                        Template email sẽ được sử dụng để gửi
                    </small>
                </div>
                
                <!-- Filters -->
                <div class="filter-section">
                    <h4>🔍 Bộ lọc người nhận</h4>
                    
                    <div class="filter-grid">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Trạng thái</label>
                            <select name="filter_status">
                                <option value="all">Tất cả</option>
                                <option value="subscribed" selected>Chỉ đã đăng ký</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Nguồn</label>
                            <select name="filter_source">
                                <option value="">Tất cả nguồn</option>
                                @foreach($sources as $source)
                                    <option value="{{ $source }}">{{ $source }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Từ ngày đăng ký</label>
                            <input type="date" name="filter_date_from">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Đến ngày đăng ký</label>
                            <input type="date" name="filter_date_to">
                        </div>
                    </div>
                </div>
                
                <!-- Additional Data (for template) -->
                <div class="form-group">
                    <label>Nội dung email (HTML/Markdown)</label>
                    <textarea name="content" placeholder="Nhập nội dung email..."></textarea>
                    <small style="color: #64748b; font-size: 12px; margin-top: 4px; display: block;">
                        Nội dung này sẽ được truyền vào template
                    </small>
                </div>
                
                <div class="form-group">
                    <label>URL nút CTA (tùy chọn)</label>
                    <input type="url" name="cta_url" placeholder="https://example.com">
                </div>
                
                <div class="form-group">
                    <label>Text nút CTA (tùy chọn)</label>
                    <input type="text" name="cta_text" placeholder="Xem ngay">
                </div>
                
                <div class="form-group">
                    <label>Footer (tùy chọn)</label>
                    <textarea name="footer" placeholder="Thông tin footer..."></textarea>
                </div>
                
                <!-- Submit -->
                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button type="submit" style="padding: 12px 24px; background: #10b981; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 14px;">
                        <i class="fas fa-paper-plane"></i> Gửi chiến dịch
                    </button>
                    <a href="{{ route('admin.newsletters.index') }}" style="padding: 12px 24px; background: #e2e8f0; color: #475569; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center;">
                        Hủy
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('campaign-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!confirm('Bạn có chắc chắn muốn gửi chiến dịch email này? Hành động này không thể hoàn tác.')) {
                return;
            }
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
            
            fetch(this.action, {
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
                if (data.success) {
                    window.location.href = '{{ route('admin.newsletters.index') }}';
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error(error);
                alert('Có lỗi xảy ra khi gửi chiến dịch.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    </script>
@endpush

