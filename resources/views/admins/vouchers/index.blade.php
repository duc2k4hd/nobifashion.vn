@extends('admins.layouts.master')

@section('title', 'Quản lý Voucher')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/vouchers-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Voucher / Mã giảm giá</h1>
            <p class="text-muted mb-0">Quản lý, lọc và thao tác nhanh trên toàn bộ voucher của cửa hàng.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.vouchers.analytics') }}" class="btn btn-info">
                <i class="bi bi-graph-up me-1"></i> Analytics
            </a>
            <a href="{{ route('admin.vouchers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Tạo voucher
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            'success' => ['label' => 'Đang hoạt động', 'value' => $stats['active'] ?? 0],
            'info' => ['label' => 'Lên lịch', 'value' => $stats['scheduled'] ?? 0],
            'secondary' => ['label' => 'Hết hạn', 'value' => $stats['expired'] ?? 0],
            'warning' => ['label' => 'Tắt', 'value' => $stats['disabled'] ?? 0],
        ] as $variant => $stat)
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">{{ $stat['label'] }}</p>
                        <h3 class="fw-semibold text-{{ $variant }}">{{ number_format($stat['value']) }}</h3>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <form method="GET" class="card mb-4 shadow-sm border-0">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control"
                           placeholder="Nhập mã hoặc tên voucher">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ([\App\Models\Voucher::STATUS_ACTIVE => 'Hoạt động', \App\Models\Voucher::STATUS_SCHEDULED => 'Lên lịch', \App\Models\Voucher::STATUS_EXPIRED => 'Hết hạn', \App\Models\Voucher::STATUS_DISABLED => 'Tắt'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Loại voucher</label>
                    <select name="type" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ([
                            \App\Models\Voucher::TYPE_FIXED_AMOUNT => 'Giảm tiền',
                            \App\Models\Voucher::TYPE_PERCENTAGE => 'Giảm %',
                            \App\Models\Voucher::TYPE_FREE_SHIPPING => 'Free ship',
                            \App\Models\Voucher::TYPE_SHIPPING_PERCENTAGE => '% phí ship',
                            \App\Models\Voucher::TYPE_SHIPPING_FIXED => 'Giảm phí ship',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Phạm vi</label>
                    <select name="applicable_to" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ([
                            \App\Models\Voucher::APPLICABLE_ALL => 'Toàn bộ',
                            \App\Models\Voucher::APPLICABLE_PRODUCTS => 'Sản phẩm',
                            \App\Models\Voucher::APPLICABLE_CATEGORIES => 'Danh mục',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['applicable_to'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-outline-primary flex-grow-1" type="submit">
                        <i class="bi bi-funnel me-1"></i> Lọc
                    </button>
                    <a href="{{ route('admin.vouchers.index') }}" class="btn btn-light">Reset</a>
                </div>
            </div>
        </div>
    </form>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Mã</th>
                    <th>Tên voucher</th>
                    <th>Loại</th>
                    <th>Giá trị</th>
                    <th>Hiệu lực</th>
                    <th>Lượt dùng</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
                </thead>
                <tbody>
                @forelse($vouchers as $voucher)
                    <tr>
                        <td class="fw-semibold">{{ $voucher->code }}</td>
                        <td>
                            <div class="fw-semibold">{{ $voucher->name }}</div>
                            <small class="text-muted">Tạo bởi: {{ $voucher->account?->name ?? 'Hệ thống' }}</small>
                        </td>
                        <td>{{ $voucher->type_label }}</td>
                        <td>{{ $voucher->value_label }}</td>
                        <td>
                            <small class="text-muted d-block">Bắt đầu: {{ optional($voucher->start_at)->format('d/m/Y H:i') ?? 'Ngay lập tức' }}</small>
                            <small class="text-muted d-block">Kết thúc: {{ optional($voucher->end_at)->format('d/m/Y H:i') ?? 'Không giới hạn' }}</small>
                        </td>
                        <td>{{ $voucher->usage_count }} / {{ $voucher->usage_limit ?? '∞' }}</td>
                        <td>
                            <span class="badge bg-{{ $voucher->status_badge }}">{{ ucfirst($voucher->status) }}</span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="{{ route('admin.vouchers.show', $voucher) }}" class="btn btn-sm btn-outline-info">Xem</a>
                                <a href="{{ route('admin.vouchers.edit', $voucher) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                                <form action="{{ route('admin.vouchers.toggle', $voucher) }}" method="POST" onsubmit="return confirm('Xác nhận thay đổi trạng thái voucher?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        {{ $voucher->status === \App\Models\Voucher::STATUS_DISABLED ? 'Bật' : 'Tắt' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.vouchers.destroy', $voucher) }}" method="POST" onsubmit="return confirm('Chắc chắn xóa voucher này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                </form>
                            </div>
                            <form action="{{ route('admin.vouchers.duplicate', $voucher) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-link btn-sm text-decoration-none">Nhân bản</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Chưa có voucher nào.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $vouchers->links() }}
        </div>
    </div>
@endsection

