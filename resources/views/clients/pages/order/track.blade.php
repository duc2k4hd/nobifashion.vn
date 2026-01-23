@extends('clients.layouts.master')

@section('title', 'Tra cứu vận đơn GHN | ' . renderMeta($settings->site_name ?? ($settings->subname ?? 'NOBI FASHION')))

@section('head')
    <meta name="robots" content="follow, noindex"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
@endsection

@section('content')
<div class="container py-5">
    <h1 class="mb-4 text-center">Tra cứu vận đơn GHN</h1>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('client.order.track.lookup') }}" method="POST" class="row gy-3">
                @csrf
                <div class="col-md-8 mx-auto">
                    <label class="form-label fw-semibold">Nhập mã vận đơn GHN <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg">
                        <input type="text" name="tracking_code" class="form-control @error('tracking_code') is-invalid @enderror"
                               placeholder="VD: GHN123456789" value="{{ old('tracking_code', $trackingCode) }}">
                        <button class="btn btn-primary px-4" type="submit">Tra cứu</button>
                    </div>
                    @error('tracking_code')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </form>
        </div>
    </div>

    @if($result)
        <div class="card shadow-sm">
            <div class="card-body">
                @if(!empty($result['success']))
                    @php $info = $result['data'][0] ?? null; @endphp
                    @if($info)
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <h5>Mã vận đơn</h5>
                                <p class="fs-4 fw-bold text-primary">{{ $info['order_code'] ?? '---' }}</p>
                                <span class="badge bg-success">{{ strtoupper($info['status'] ?? 'unknown') }}</span>
                            </div>
                            <div class="col-md-4">
                                <h6>Người nhận</h6>
                                <p class="mb-1">{{ $info['to_name'] ?? '---' }}</p>
                                <small>{{ $info['to_phone'] ?? '---' }}</small><br>
                                <small>{{ $info['to_address'] ?? '---' }}</small>
                            </div>
                            <div class="col-md-4">
                                <h6>Thông tin khác</h6>
                                <ul class="list-unstyled small">
                                    <li>Thu COD: {{ data_get($info, 'cod_amount') ? number_format($info['cod_amount'], 0, ',', '.') . ' đ' : 'Không' }}</li>
                                    <li>Dịch vụ: {{ $info['service_id'] ?? '---' }}</li>
                                    <li>Khối lượng: {{ $info['weight'] ?? 0 }} g</li>
                                </ul>
                            </div>
                        </div>

                        <h5>Lịch trình vận chuyển</h5>
                        <div class="timeline">
                            @forelse(($info['log'] ?? []) as $log)
                                <div class="d-flex mb-3">
                                    <div class="me-3 text-muted" style="min-width:180px;">
                                        {{ isset($log['updated_date']) ? \Carbon\Carbon::parse($log['updated_date'])->format('d/m/Y H:i') : '---' }}
                                    </div>
                                    <div>
                                        <span class="badge bg-info text-dark">{{ strtoupper($log['status'] ?? '---') }}</span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted">Chưa có dữ liệu lịch trình.</p>
                            @endforelse
                        </div>
                    @else
                        <div class="alert alert-warning">Không tìm thấy dữ liệu cho mã vận đơn này.</div>
                    @endif
                @else
                    <div class="alert alert-danger">{{ $result['error'] ?? 'Không thể tra cứu vận đơn.' }}</div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

