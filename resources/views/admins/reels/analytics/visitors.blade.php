@extends('admins.layouts.master')

@section('title', 'Nhật ký phiên khách truy cập Reels (Visitor Sessions)')

@push('styles')
<link rel="stylesheet" href="{{ asset('admins/css/reels_admin.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="nobifashion_admin_reels_container">
    <!-- Header -->
    <div class="nobifashion_admin_reels_page_header mb-3">
        <div>
            <h1 class="nobifashion_admin_reels_page_title">
                <i class="fa-solid fa-users-viewfinder text-primary me-2"></i>Nhật ký phiên khách truy cập
            </h1>
            <div class="nobifashion_admin_reels_breadcrumb">
                <a href="{{ route('admin.reels.dashboard') }}">Reels</a>
                <span>/</span>
                <a href="{{ route('admin.reels.analytics.index') }}">Analytics</a>
                <span>/</span>
                <span>Phiên khách truy cập</span>
            </div>
        </div>
        <div class="nobifashion_admin_reels_page_actions">
            <a href="{{ route('admin.reels.analytics.index') }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Quay lại báo cáo tổng quan
            </a>
        </div>
    </div>

    <!-- Subnav Tabs -->
    @include('admins.reels.partials.subnav')

    <!-- Privacy Notice -->
    <div class="alert alert-info py-2 d-flex align-items-center mb-3 small" role="alert">
        <i class="fa-solid fa-user-shield fs-5 me-2"></i>
        <div>
            <strong>Quyền riêng tư & An toàn dữ liệu:</strong> Địa chỉ IP của người xem đã được che một phần (IP Masking theo tiêu chuẩn GDPR / bảo vệ dữ liệu cá nhân) để đảm bảo an toàn thông tin khách hàng.
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="nobifashion_admin_reels_card mb-3">
        <div class="nobifashion_admin_reels_card_body py-2">
            <form action="{{ route('admin.reels.analytics.visitors') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Tìm kiếm theo IP, trình duyệt, hệ điều hành, UTM source..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="device" class="form-select form-select-sm">
                        <option value="all">Tất cả loại thiết bị</option>
                        <option value="mobile" {{ request('device') === 'mobile' ? 'selected' : '' }}>Mobile (Di động)</option>
                        <option value="desktop" {{ request('device') === 'desktop' ? 'selected' : '' }}>Desktop (Máy tính)</option>
                        <option value="tablet" {{ request('device') === 'tablet' ? 'selected' : '' }}>Tablet (Máy tính bảng)</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary btn-sm">
                        <i class="fa-solid fa-filter me-1"></i> Lọc
                    </button>
                    @if(request()->hasAny(['search', 'device']))
                        <a href="{{ route('admin.reels.analytics.visitors') }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_secondary btn-sm">
                            <i class="fa-solid fa-rotate-left"></i> Đặt lại
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Sessions Table -->
    <div class="nobifashion_admin_reels_card">
        <div class="nobifashion_admin_reels_card_header d-flex justify-content-between align-items-center">
            <h3 class="nobifashion_admin_reels_card_title">Danh sách phiên hoạt động gần nhất</h3>
            <span class="badge bg-light text-dark border">Tổng {{ number_format($sessions->total()) }} phiên</span>
        </div>
        <div class="nobifashion_admin_reels_card_body p-0">
            <div class="table-responsive">
                <table class="nobifashion_admin_reels_table">
                    <thead>
                        <tr>
                            <th>Phiên ID / Mã</th>
                            <th>Người dùng</th>
                            <th>Địa chỉ IP (Masked)</th>
                            <th>Môi trường thiết bị</th>
                            <th>Màn hình</th>
                            <th>Nguồn tiếp thị</th>
                            <th>Video xem</th>
                            <th>Thời gian xem</th>
                            <th>Hoạt động cuối</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sessions as $sess)
                            <tr>
                                <td>
                                    <code class="small text-dark">{{ substr($sess->session_token ?? 'session_' . $sess->id, 0, 12) }}...</code>
                                    <div class="text-muted" style="font-size: 10px;">ID: #{{ $sess->id }}</div>
                                </td>
                                <td>
                                    @if($sess->account)
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="fa-solid fa-user-check text-success"></i>
                                            <span class="fw-semibold text-dark">{{ $sess->account->fullname }}</span>
                                        </div>
                                        <div class="text-muted" style="font-size: 11px;">#{{ $sess->account_id }}</div>
                                    @elseif($sess->anonymousUser)
                                        <a href="{{ route('admin.reels.anonymous-users.show', $sess->anonymousUser->id) }}" class="text-decoration-none">
                                            <div class="d-flex align-items-center gap-1">
                                                <i class="fa-solid fa-user-secret text-secondary"></i>
                                                <span class="text-dark">{{ $sess->anonymousUser->display_name ?? 'Khách ẩn danh' }}</span>
                                            </div>
                                        </a>
                                        <div class="text-muted" style="font-size: 11px;">{{ substr($sess->anonymousUser->fingerprint ?? '', 0, 10) }}</div>
                                    @else
                                        <span class="text-muted small">Khách vãng lai</span>
                                    @endif
                                </td>
                                <td>
                                    <code>{{ $sess->masked_ip }}</code>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        @if($sess->device_type === 'mobile')
                                            <i class="fa-solid fa-mobile-screen-button text-primary"></i>
                                        @elseif($sess->device_type === 'tablet')
                                            <i class="fa-solid fa-tablet-screen-button text-warning"></i>
                                        @else
                                            <i class="fa-solid fa-desktop text-secondary"></i>
                                        @endif
                                        <span>{{ ucfirst($sess->device_type ?: 'Desktop') }}</span>
                                    </div>
                                    <div class="text-muted" style="font-size: 11px;">
                                        {{ $sess->browser }} • {{ $sess->platform }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border" style="font-size: 11px;">
                                        {{ $sess->screen_resolution ?: '1920x1080' }}
                                    </span>
                                </td>
                                <td>
                                    @if($sess->utm_source)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            {{ $sess->utm_source }}
                                        </span>
                                        @if($sess->utm_campaign)
                                            <div class="text-muted" style="font-size: 10px;">{{ $sess->utm_campaign }}</div>
                                        @endif
                                    @else
                                        <span class="text-muted small">Trực tiếp (Direct)</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-dark">{{ $sess->reels_viewed_count ?? 0 }} video</span>
                                </td>
                                <td>
                                    <strong class="text-dark">{{ $sess->total_watch_time_seconds ?? 0 }}s</strong>
                                </td>
                                <td class="small text-nowrap text-muted">
                                    {{ $sess->last_activity_at ? $sess->last_activity_at->diffForHumans() : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-users-slash fa-2x mb-2 d-block text-secondary"></i>
                                    Không tìm thấy phiên khách truy cập nào phù hợp với bộ lọc.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($sessions->hasPages())
                <div class="p-3 border-top">
                    {{ $sessions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

