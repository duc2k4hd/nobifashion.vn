@extends('admins.layouts.master')

@section('title', 'Chỉnh sửa tài khoản Email: ' . $emailAccount->email . ' | Quản trị')
@section('page-title', '✏️ Sửa tài khoản Email')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/email-icon.webp') }}" type="image/x-icon">
@endpush

@section('content')
<div class="container-fluid px-0">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.email-accounts.index') }}" class="text-decoration-none text-muted">Tài khoản Email</a></li>
                    <li class="breadcrumb-item active text-truncate" aria-current="page" style="max-width: 250px;">{{ $emailAccount->email }}</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-dark mb-1">Chỉnh sửa tài khoản: {{ $emailAccount->email }}</h1>
            <p class="text-muted mb-0">Cập nhật thông tin hiển thị và các thông số kết nối SMTP riêng biệt.</p>
        </div>
        <div>
            <a href="{{ route('admin.email-accounts.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Quay lại danh sách
            </a>
        </div>
    </div>

    @include('admins.email-accounts.form', ['emailAccount' => $emailAccount])
</div>
@endsection
