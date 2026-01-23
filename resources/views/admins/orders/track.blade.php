@extends('admins.layouts.master')

@section('title', 'Tra cứu vận đơn GHN')
@section('page-title', '🔍 Tra cứu vận đơn GHN')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/tracking-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
    <div class="card mb-4">
        <h3>Tra cứu vận đơn GHN</h3>
        <form action="{{ route('admin.orders.track.lookup') }}" method="POST" class="row g-3">
            @csrf
            <div class="col-md-6">
                <label class="form-label fw-semibold">Mã vận đơn GHN <span class="text-danger">*</span></label>
                <input type="text" name="tracking_code" class="form-control @error('tracking_code') is-invalid @enderror"
                       value="{{ old('tracking_code', $trackingCode) }}" placeholder="Ví dụ: GHN1234567890">
                @error('tracking_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Tra cứu</button>
            </div>
        </form>
    </div>

    @if($result)
        <div class="card">
            <h3>Kết quả tra cứu</h3>

            @if(!empty($result['success']))
                @php $info = $result['data'][0] ?? null; @endphp
                @if($info)
                    <div class="mb-4">
                        <h5>Thông tin chung</h5>
                        <div class="row">
                            <div class="col-md-4 mb-2"><strong>Mã GHN:</strong> {{ $info['order_code'] ?? '...' }}</div>
                            <div class="col-md-4 mb-2"><strong>Khách hàng:</strong> {{ $info['to_name'] ?? '...' }}</div>
                            <div class="col-md-4 mb-2"><strong>Điện thoại:</strong> {{ $info['to_phone'] ?? '...' }}</div>
                            <div class="col-md-4 mb-2"><strong>Trạng thái:</strong> {{ $info['status'] ?? '...' }}</div>
                            <div class="col-md-4 mb-2"><strong>Trạng thái thanh toán:</strong> {{ data_get($info, 'is_cod_collected') ? 'Đã thu COD' : 'Chưa thu COD' }}</div>
                            <div class="col-md-4 mb-2"><strong>Ngày tạo:</strong> {{ isset($info['order_date']) ? \Carbon\Carbon::parse($info['order_date'])->format('d/m/Y H:i') : '---' }}</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h5>Người gửi</h5>
                            <ul class="list-unstyled small">
                                <li><strong>Họ tên:</strong> {{ $info['from_name'] ?? '---' }}</li>
                                <li><strong>SĐT:</strong> {{ $info['from_phone'] ?? '---' }}</li>
                                <li><strong>Địa chỉ:</strong> {{ $info['from_address'] ?? '---' }}</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Người nhận</h5>
                            <ul class="list-unstyled small">
                                <li><strong>Họ tên:</strong> {{ $info['to_name'] ?? '---' }}</li>
                                <li><strong>SĐT:</strong> {{ $info['to_phone'] ?? '---' }}</li>
                                <li><strong>Địa chỉ:</strong> {{ $info['to_address'] ?? '---' }}</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5>Lịch sử trạng thái</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Thời gian</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse(($info['log'] ?? []) as $log)
                                    <tr>
                                        <td>{{ isset($log['updated_date']) ? \Carbon\Carbon::parse($log['updated_date'])->format('d/m/Y H:i') : '---' }}</td>
                                        <td>{{ $log['status'] ?? '---' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center">Chưa có dữ liệu log</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning">Không tìm thấy dữ liệu cho mã vận đơn này.</div>
                @endif
            @else
                <div class="alert alert-danger">
                    {{ $result['error'] ?? 'Không thể truy vấn vận đơn vào lúc này. Vui lòng thử lại sau.' }}
                </div>
            @endif
        </div>
    @endif
@endsection

