@extends('admins.layouts.master')

@section('title', 'Chi tiết địa chỉ #'.$address->id)
@section('page-title', '📍 Địa chỉ #' . $address->id)

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-XQoYMqMTK8LvdxXYG3nZLZ1Q0p3p3l6NQ6YCM2BPe3Y=" crossorigin=""/>
<style>
    #map {
        width: 100%;
        height: 320px;
        border-radius: 12px;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.12);
    }
    .info-block {
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
        gap:16px;
    }
    .timeline {
        max-height: 320px;
        overflow-y: auto;
    }
    .timeline-item {
        padding: 12px;
        border-left: 3px solid #c7d2fe;
        margin-bottom: 12px;
        background: #f8fafc;
        border-radius: 8px;
    }
</style>
@endpush

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.addresses.index') }}" class="btn btn-secondary">← Quay lại</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4>{{ $address->full_name }}</h4>
                    <div class="text-muted">{{ $address->phone_number }}</div>
                </div>
                <div>
                    <span class="badge {{ $address->address_type === 'work' ? 'badge type badge-type-work' : 'badge-type-home' }}">
                        {{ $address->address_type === 'work' ? 'Work' : 'Home' }}
                    </span>
                    @if($address->is_default)
                        <span class="badge bg-info text-dark">Default</span>
                    @endif
                </div>
            </div>
            <div class="info-block">
                <div>
                    <div class="text-muted text-uppercase small mb-1">Địa chỉ</div>
                    <strong>{{ $address->detail_address }}</strong>
                    <div>{{ $address->ward ? $address->ward . ', ' : '' }}{{ $address->district }}, {{ $address->province }}</div>
                    <div class="text-muted">Postal: {{ $address->postal_code }} - {{ $address->country }}</div>
                </div>
                <div>
                    <div class="text-muted text-uppercase small mb-1">Tài khoản</div>
                    <a href="{{ route('admin.accounts.edit', $address->account) }}">
                        {{ $address->account?->displayName() }}
                    </a>
                    <div class="text-muted">{{ $address->account?->email }}</div>
                </div>
                @if($address->notes)
                    <div>
                        <div class="text-muted text-uppercase small mb-1">Ghi chú</div>
                        <p>{{ $address->notes }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Vị trí bản đồ</h5>
                    <div id="map"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Lịch sử hoạt động</h5>
                    <div class="timeline">
                        @forelse($audits as $audit)
                            <div class="timeline-item">
                                <strong>{{ $audit->action }}</strong>
                                <div>{{ $audit->description }}</div>
                                <div class="small text-muted">
                                    {{ $audit->created_at->format('d/m/Y H:i') }}
                                    @if($audit->actor)
                                        • {{ $audit->actor->displayName() }}
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">Chưa có log.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-QV+0G7S4Zb00mDuD0w9Vyhi3F7S3w7Dnk3XGtk0uSik=" crossorigin=""></script>
<script>
    const lat = {{ $address->latitude ? (float) $address->latitude : 'null' }};
    const lng = {{ $address->longitude ? (float) $address->longitude : 'null' }};

    if (lat && lng) {
        const map = L.map('map').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map);
        L.marker([lat, lng]).addTo(map)
            .bindPopup(`{{ $address->shortLabel() }}`)
            .openPopup();
    } else {
        document.getElementById('map').innerHTML = '<div class="text-muted text-center pt-5">Chưa có tọa độ GPS.</div>';
    }
</script>
@endpush

