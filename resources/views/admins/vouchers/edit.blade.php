@extends('admins.layouts.master')

@section('title', 'Chỉnh sửa voucher: ' . $voucher->code)

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/vouchers-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Chỉnh sửa voucher</h1>
            <p class="text-muted mb-0">Quản lý chi tiết voucher {{ $voucher->code }}.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.vouchers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Danh sách
            </a>
            <form action="{{ route('admin.vouchers.duplicate', $voucher) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-info">
                    <i class="bi bi-copy me-1"></i> Nhân bản
                </button>
            </form>
            <form action="{{ route('admin.vouchers.toggle', $voucher) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-warning">
                    {{ $voucher->status === \App\Models\Voucher::STATUS_DISABLED ? 'Bật lại' : 'Tạm tắt' }}
                </button>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.vouchers.update', $voucher) }}" autocomplete="off" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admins.vouchers.partials.form')
    </form>

    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-semibold">Lịch sử thay đổi gần đây</h5>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                @forelse($histories as $history)
                    <div class="list-group-item d-flex justify-content-between">
                        <div>
                            <div class="fw-semibold">{{ ucfirst($history->action) }}</div>
                            <small class="text-muted">
                                {{ $history->account?->name ?? 'Hệ thống' }} • {{ $history->created_at->diffForHumans() }}
                            </small>
                            @if($history->note)
                                <div class="text-muted small mt-1">{{ $history->note }}</div>
                            @endif
                        </div>
                        <span class="badge bg-light text-dark">{{ $history->created_at->format('d/m H:i') }}</span>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-4">
                        Chưa có lịch sử thay đổi nào.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection

