@extends('admins.layouts.master')

@section('page-title', 'Công cụ hệ thống')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Công cụ Hệ thống</h2>
        <p class="text-muted mb-0">Các công cụ xử lý dọn dẹp và tối ưu hóa hệ thống.</p>
    </div>
</div>

<div class="row">
    <!-- Export Image Links Tool -->
    <div class="col-md-8 col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="fas fa-file-export text-success me-2"></i> Xuất danh sách URL Ảnh
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Công cụ này sẽ phân tích toàn bộ dữ liệu (kể cả các bài viết trong Thùng rác) và xuất ra file text (.txt) chứa tất cả đường dẫn ảnh đang được sử dụng.
                </p>
                <div class="text-end mt-4">
                    <a href="{{ route('admin.tools.export-post-images') }}" target="_blank" class="btn btn-success fw-bold">
                        <i class="fas fa-download me-1"></i> Tải danh sách (.txt)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
