@extends('admins.layouts.master')

@section('title', 'Tạo voucher mới')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/vouchers-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Tạo Voucher mới</h1>
            <p class="text-muted mb-0">Định nghĩa đầy đủ voucher giảm giá cho chiến dịch của bạn.</p>
        </div>
        <a href="{{ route('admin.vouchers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách
        </a>
    </div>

    <form method="POST" action="{{ route('admin.vouchers.store') }}" autocomplete="off" enctype="multipart/form-data">
        @csrf
        @include('admins.vouchers.partials.form')
    </form>
@endsection

