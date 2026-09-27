@extends('admins.layouts.master')

@section('page-title', 'Quản lý chuyển hướng 301')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/posts-icon.png') }}" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@push('styles')
    <style>
        .badge-status {
            font-size: 0.8rem;
            padding: 0.35rem 0.65rem;
            border-radius: 50rem;
        }
        .code-pill {
            font-size: 0.75rem;
            font-family: monospace;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
        }
        .url-truncate {
            max-width: 320px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            vertical-align: middle;
        }
        .table th {
            font-weight: 600;
            font-size: 0.82rem;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
        }
        .preview-table {
            font-size: 0.82rem;
        }
        .preview-table th {
            background: #f1f5f9;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 3 21 3 21 8"></polyline><line x1="4" y1="20" x2="21" y2="3"></line><polyline points="21 16 21 21 16 21"></polyline><line x1="15" y1="15" x2="21" y2="21"></line><line x1="4" y1="4" x2="9" y2="9"></line></svg>
                <span>Quản lý chuyển hướng 301</span>
            </h2>
            <p class="text-muted mb-0">Hệ thống chuyển hướng URL chuẩn SEO, tự động chống vòng lặp và link 404, xử lý nhanh chóng.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-success d-inline-flex align-items-center gap-1 fw-semibold" data-bs-toggle="modal" data-bs-target="#exportModal">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span>Xuất CSV/Excel</span>
            </button>
            <button type="button" class="btn btn-outline-info d-inline-flex align-items-center gap-1 fw-semibold" data-bs-toggle="modal" data-bs-target="#importModal">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                <span>Nhập CSV/Excel</span>
            </button>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1 fw-semibold" data-bs-toggle="modal" data-bs-target="#createModal">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Thêm chuyển hướng</span>
            </button>
        </div>
    </div>

    {{-- Thống kê nhanh --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary me-3 flex-shrink-0" style="width: 54px; height: 54px;">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Tổng chuyển hướng</div>
                        <div class="h3 fw-bold mb-0 text-dark">{{ number_format($stats['total'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center text-success me-3 flex-shrink-0" style="width: 54px; height: 54px;">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Đang hoạt động</div>
                        <div class="h3 fw-bold mb-0 text-success">{{ number_format($stats['active'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center text-info me-3 flex-shrink-0" style="width: 54px; height: 54px;">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Lượt kích hoạt (Hits)</div>
                        <div class="h3 fw-bold mb-0 text-info">{{ number_format($stats['total_hits'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bộ lọc & Tìm kiếm --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body">
            <form action="{{ route('admin.redirects.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small text-muted text-uppercase fw-semibold">Tìm kiếm</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Tìm theo ID, link cũ, link mới, ghi chú..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted text-uppercase fw-semibold">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Đang hoạt động</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Đang tạm dừng</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted text-uppercase fw-semibold">Sắp xếp</label>
                    <select name="sort" class="form-select">
                        <option value="latest" @selected(($filters['sort'] ?? '') === 'latest')>Mới nhất</option>
                        <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Cũ nhất</option>
                        <option value="hits_desc" @selected(($filters['sort'] ?? '') === 'hits_desc')>Lượt kích hoạt (Cao → Thấp)</option>
                        <option value="hits_asc" @selected(($filters['sort'] ?? '') === 'hits_asc')>Lượt kích hoạt (Thấp → Cao)</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small text-muted text-uppercase fw-semibold">Hiển thị</label>
                    <select name="limit" class="form-select">
                        <option value="50" @selected(($filters['limit'] ?? 50) == 50)>50</option>
                        <option value="100" @selected(($filters['limit'] ?? 50) == 100)>100</option>
                        <option value="300" @selected(($filters['limit'] ?? 50) == 300)>300</option>
                        <option value="1000" @selected(($filters['limit'] ?? 50) == 1000)>1000</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button type="submit" class="btn btn-dark me-1 d-inline-flex align-items-center gap-1">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                        <span>Lọc</span>
                    </button>
                    <a href="{{ route('admin.redirects.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Bảng danh sách --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1" id="btnBulkDelete" disabled onclick="handleBulkDelete()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        <span>Xóa mục đã chọn</span>
                    </button>
                    <span id="selectedCountBadge" class="badge bg-secondary d-none">0 mục được chọn</span>
                </div>
                <div class="text-muted small">
                    Hiển thị <strong>{{ $redirects->firstItem() ?? 0 }} - {{ $redirects->lastItem() ?? 0 }}</strong> trên tổng <strong>{{ $redirects->total() }}</strong> chuyển hướng
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 40px;" class="text-center">
                                <input class="form-check-input" type="checkbox" id="checkAll">
                            </th>
                            <th style="width: 65px;">ID</th>
                            <th>Link cũ (Gốc / Slug)</th>
                            <th>Link mới (Đích đến)</th>
                            <th style="width: 85px;" class="text-center">Mã HTTP</th>
                            <th style="width: 100px;" class="text-center">Lượt Hits</th>
                            <th style="width: 135px;">Lần cuối kích hoạt</th>
                            <th style="width: 140px;" class="text-center">Trạng thái</th>
                            <th style="width: 180px;" class="text-end pe-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($redirects as $item)
                            <tr id="redirect-row-{{ $item->id }}">
                                <td class="text-center">
                                    <input class="form-check-input item-check" type="checkbox" value="{{ $item->id }}">
                                </td>
                                <td class="fw-bold text-muted">#{{ $item->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <svg width="13" height="13" class="text-danger me-1 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                        <span class="url-truncate text-danger fw-semibold" title="{{ $item->old_url }}">{{ $item->old_url }}</span>
                                        <button type="button" class="btn btn-link text-muted p-0 ms-1 btn-copy-url" data-url="{{ $item->old_url }}" title="Sao chép link cũ">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="13" height="13" x="9" y="9" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                        </button>
                                    </div>
                                    @if($item->note)
                                        <div class="text-muted small fst-italic mt-1" style="font-size: 0.75rem;">
                                            <svg width="11" height="11" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>{{ $item->note }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <svg width="13" height="13" class="text-success me-1 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        <a href="{{ url($item->new_url) }}" target="_blank" class="url-truncate text-success text-decoration-none fw-semibold" title="{{ $item->new_url }}">
                                            {{ $item->new_url }}
                                        </a>
                                        <button type="button" class="btn btn-link text-muted p-0 ms-1 btn-copy-url" data-url="{{ url($item->new_url) }}" title="Sao chép link mới">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="13" height="13" x="9" y="9" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $item->status_code === 301 ? 'bg-success' : 'bg-warning text-dark' }} px-2 py-1 fw-bold">
                                        {{ $item->status_code }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary bg-opacity-10 text-primary border fw-bold px-2 py-1">
                                        {{ number_format($item->hits) }}
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    {{ $item->last_accessed_at ? $item->last_accessed_at->format('d/m/Y H:i') : 'Chưa có' }}
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input toggle-status" type="checkbox" role="switch"
                                                   data-id="{{ $item->id }}"
                                                   data-url="{{ route('admin.redirects.toggle', $item) }}"
                                                   {{ $item->is_active ? 'checked' : '' }} style="cursor: pointer;">
                                        </div>
                                        <span class="badge {{ $item->is_active ? 'bg-success' : 'bg-secondary' }} status-badge-{{ $item->id }}" style="font-size: 0.72rem; min-width: 50px;">
                                            {{ $item->is_active ? 'Đang bật' : 'Tạm dừng' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <a href="{{ url($item->old_url) }}" target="_blank" class="btn btn-sm btn-outline-secondary px-2 py-1 d-inline-flex align-items-center gap-1" title="Thử chuyển hướng link này">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                            <span>Thử</span>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-primary px-2 py-1 btn-edit d-inline-flex align-items-center gap-1"
                                                data-id="{{ $item->id }}"
                                                data-old-url="{{ $item->old_url }}"
                                                data-new-url="{{ $item->new_url }}"
                                                data-status-code="{{ $item->status_code }}"
                                                data-is-active="{{ $item->is_active ? '1' : '0' }}"
                                                data-note="{{ $item->note }}"
                                                data-update-url="{{ route('admin.redirects.update', $item) }}"
                                                title="Sửa chuyển hướng">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                            <span>Sửa</span>
                                        </button>
                                        <form action="{{ route('admin.redirects.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa chuyển hướng này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1 d-inline-flex align-items-center gap-1" title="Xóa chuyển hướng">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                <span>Xóa</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-random fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-0">Chưa có liên kết chuyển hướng nào khớp với bộ lọc.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    Trang {{ $redirects->currentPage() }} / {{ $redirects->lastPage() ?: 1 }}
                </div>
                <div>
                    {{ $redirects->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 1: THÊM MỚI CHUYỂN HƯỚNG --}}
    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle text-primary me-2"></i>Thêm chuyển hướng 301</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.redirects.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Link cũ hoặc Slug cũ <span class="text-danger">*</span></label>
                            <input type="text" name="old_url" class="form-control" placeholder="Ví dụ: /blog/bai-viet-cu hoặc bai-viet-cu" required>
                            <div class="form-text small">Hệ thống tự động chuẩn hóa dấu gạch chéo và loại bỏ domain khi lưu.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Link mới đích đến <span class="text-danger">*</span></label>
                            <input type="text" name="new_url" class="form-control" placeholder="Ví dụ: /blog/bai-viet-moi hoặc URL đầy đủ" required>
                            <div class="form-text small text-info">Hệ thống sẽ kiểm tra tự động: nếu bài viết đích không tồn tại, sẽ chặn redirect và trả 404 để bảo vệ điểm SEO.</div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Mã HTTP Redirect</label>
                                <select name="status_code" class="form-select">
                                    <option value="301" selected>301 (Moved Permanently - Khuyên dùng)</option>
                                    <option value="302">302 (Found / Temporary)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Trạng thái</label>
                                <select name="is_active" class="form-select">
                                    <option value="1" selected>Kích hoạt ngay</option>
                                    <option value="0">Tạm dừng</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Ghi chú (Tùy chọn)</label>
                            <input type="text" name="note" class="form-control" placeholder="Lý do chuyển hướng, chiến dịch...">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 pb-4 px-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4">Tạo chuyển hướng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL 2: SỬA CHUYỂN HƯỚNG --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit text-warning me-2"></i>Sửa chuyển hướng #<span id="editModalId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Link cũ hoặc Slug cũ <span class="text-danger">*</span></label>
                            <input type="text" name="old_url" id="editOldUrl" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Link mới đích đến <span class="text-danger">*</span></label>
                            <input type="text" name="new_url" id="editNewUrl" class="form-control" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Mã HTTP</label>
                                <select name="status_code" id="editStatusCode" class="form-select">
                                    <option value="301">301 (Moved Permanently)</option>
                                    <option value="302">302 (Temporary)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Trạng thái</label>
                                <select name="is_active" id="editIsActive" class="form-select">
                                    <option value="1">Kích hoạt</option>
                                    <option value="0">Tạm dừng</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Ghi chú</label>
                            <input type="text" name="note" id="editNote" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 pb-4 px-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL 3: XUẤT CSV STREAMING (HỖ TRỢ CHỌN CỘT) --}}
    <div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-file-download text-success me-2"></i>Xuất dữ liệu chuyển hướng (CSV/Excel)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.redirects.export') }}" method="GET">
                    {{-- Giữ nguyên bộ lọc hiện tại --}}
                    @if(!empty($filters['search']))
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                    @endif
                    @if(!empty($filters['status']))
                        <input type="hidden" name="status" value="{{ $filters['status'] }}">
                    @endif

                    <div class="modal-body p-4">
                        <p class="text-muted small">Xuất dữ liệu chuẩn định dạng Microsoft Excel hoặc CSV, hỗ trợ hàng triệu dòng mà không tiêu tốn RAM server.</p>
                        
                        {{-- Chọn định dạng file --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-uppercase text-muted">Định dạng file xuất:</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="p-2 border rounded-3 bg-light d-flex align-items-center">
                                        <input class="form-check-input me-2" type="radio" name="format" id="formatXlsx" value="xlsx" checked>
                                        <label class="form-check-label small fw-bold text-success cursor-pointer" for="formatXlsx">
                                            <i class="fas fa-file-excel me-1"></i> File Excel (.xlsx)
                                            <span class="d-block text-muted fw-normal" style="font-size: 0.72rem;">Mở trực tiếp bằng Microsoft Excel</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 border rounded-3 bg-light d-flex align-items-center">
                                        <input class="form-check-input me-2" type="radio" name="format" id="formatCsv" value="csv">
                                        <label class="form-check-label small fw-bold text-primary cursor-pointer" for="formatCsv">
                                            <i class="fas fa-file-csv me-1"></i> File CSV (.csv)
                                            <span class="d-block text-muted fw-normal" style="font-size: 0.72rem;">Văn bản UTF-8 BOM nhẹ nhất</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <label class="form-label fw-semibold small text-uppercase text-muted">Chọn các cột cần xuất:</label>
                        <div class="row g-2 p-3 bg-light rounded-3 border mb-3">
                            @php
                                $availableCols = [
                                    'ID' => true,
                                    'Link cũ' => true,
                                    'Link mới' => true,
                                    'Mã HTTP' => false,
                                    'Trạng thái' => false,
                                    'Lượt kích hoạt' => true,
                                    'Lần truy cập cuối' => false,
                                    'Ghi chú' => false,
                                    'Ngày tạo' => false,
                                ];
                            @endphp
                            @foreach($availableCols as $col => $checked)
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-col-cb" type="checkbox" name="columns[]" value="{{ $col }}" id="col_{{ $loop->index }}" {{ $checked ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="col_{{ $loop->index }}">{{ $col }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0" onclick="document.querySelectorAll('.export-col-cb').forEach(c => c.checked = true);">Chọn tất cả</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0" onclick="document.querySelectorAll('.export-col-cb').forEach(c => c.checked = false);">Bỏ tất cả</button>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 pb-4 px-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-success fw-bold px-4" id="btnSubmitExport">
                            <i class="fas fa-file-excel me-1"></i> Tải xuống file Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL 4: NHẬP CSV/EXCEL (POPUP PREVIEW, CHỌN CỘT, QUY TẮC ID, BATCHING ULTRA FAST) --}}
    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-file-upload text-info me-2"></i>Nhập dữ liệu chuyển hướng (Batch Ultra-Fast)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    {{-- Bước 1: Chọn file & thông tin quy tắc --}}
                    <div class="row g-4 mb-4">
                        <div class="col-lg-6">
                            <label class="form-label fw-bold text-uppercase small text-muted">1. Chọn file Excel (.xlsx, .xls) hoặc .csv</label>
                            <div id="importDropZone" class="border-2 border-dashed rounded-3 p-4 text-center" style="background: #f8fafc; border-color: #cbd5e1; cursor: pointer;">
                                <input type="file" id="importFileInput" class="d-none" accept=".csv, .xlsx, .xls">
                                <i class="fas fa-cloud-upload-alt text-primary fa-3x mb-2"></i>
                                <p class="mb-1 fw-bold text-dark">Kéo thả file vào đây hoặc bấm để chọn tệp</p>
                                <span class="text-muted small">Cột A: ID | Cột B: Link cũ | Cột C: Link mới</span>
                            </div>
                            <div id="importFileMeta" class="alert alert-success mt-2 py-2 px-3 small d-none">
                                <div class="fw-bold text-truncate" id="importFileName">file.csv</div>
                                <div class="text-muted" id="importFileCount">Tìm thấy: 0 dòng</div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="alert alert-info py-3 px-3 border-0 rounded-3 mb-3" style="background: #f0f9ff; border-left: 4px solid #0284c7 !important;">
                                <div class="fw-bold text-primary mb-1"><i class="fas fa-shield-alt me-1"></i>Quy tắc nhập dữ liệu chuẩn xác:</div>
                                <ul class="mb-0 ps-3 small text-dark">
                                    <li><strong>Cột A (ID):</strong> Nếu có ID hợp lệ $\rightarrow$ Cập nhật bản ghi chuyển hướng có ID đó.</li>
                                    <li><strong>Để trống ID / Không có cột ID:</strong> Hệ thống tự động <strong>Tạo mới</strong>. Nếu link cũ đã có thì tự động cập nhật.</li>
                                    <li><strong>An toàn tuyệt đối:</strong> Xử lý theo lô (batch 500-1000 dòng), $O(1)$ RAM, không làm treo server.</li>
                                </ul>
                            </div>

                            {{-- Chọn mapping cột --}}
                            <div id="columnMappingArea" class="d-none">
                                <label class="form-label fw-bold text-uppercase small text-muted">2. Khớp cột dữ liệu từ file của bạn</label>
                                <div class="row g-2 bg-light p-3 rounded-3 border">
                                    <div class="col-4">
                                        <label class="small fw-semibold">Cột ID (Tùy chọn):</label>
                                        <select id="mapIdCol" class="form-select form-select-sm"></select>
                                    </div>
                                    <div class="col-4">
                                        <label class="small fw-semibold text-danger">Cột Link cũ (*):</label>
                                        <select id="mapOldUrlCol" class="form-select form-select-sm"></select>
                                    </div>
                                    <div class="col-4">
                                        <label class="small fw-semibold text-success">Cột Link mới (*):</label>
                                        <select id="mapNewUrlCol" class="form-select form-select-sm"></select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- POPUP REVIEW BẢNG XEM TRƯỚC (5-10 dòng) --}}
                    <div id="previewArea" class="d-none mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold text-uppercase small text-muted mb-0">
                                <i class="fas fa-eye me-1"></i>3. Xem trước 5 dòng đầu tiên từ file
                            </label>
                            <span class="badge bg-primary" id="previewTotalBadge">Tổng cộng: 0 dòng</span>
                        </div>
                        <div class="table-responsive border rounded-3" style="max-height: 220px;">
                            <table class="table table-sm table-hover align-middle mb-0 preview-table" id="previewTable">
                                <thead id="previewThead"></thead>
                                <tbody id="previewTbody"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Tiến trình Progress Bar --}}
                    <div id="importProgressArea" class="d-none mb-3">
                        <div class="d-flex justify-content-between mb-1 small fw-bold">
                            <span id="importProgressText">Đang xử lý: 0/0 dòng</span>
                            <span id="importPercentText" class="text-primary">0%</span>
                        </div>
                        <div class="progress rounded-pill" style="height: 12px;">
                            <div id="importProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div class="d-flex gap-3 mt-2 small">
                            <span class="text-success"><i class="fas fa-check-circle me-1"></i>Tạo mới/Cập nhật: <strong id="statInserted">0</strong></span>
                            <span class="text-warning"><i class="fas fa-edit me-1"></i>Theo ID: <strong id="statUpdated">0</strong></span>
                            <span class="text-danger"><i class="fas fa-times-circle me-1"></i>Bỏ qua/Lỗi: <strong id="statSkipped">0</strong></span>
                        </div>
                    </div>

                    {{-- Activity Log --}}
                    <div id="importLogContainer" class="rounded-3 bg-dark p-2 text-white-50 font-monospace small d-none" style="height: 120px; overflow-y: auto; font-size: 0.75rem;">
                        <div id="importLogContent">> Sẵn sàng nhập dữ liệu...</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary fw-bold px-4" id="btnStartImport" disabled>
                        <i class="fas fa-play me-1"></i> Bắt đầu Nhập Dữ Liệu
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- SheetJS để phân tích Excel/CSV trực tiếp trên trình duyệt siêu mượt --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Quản lý Checkbox Bulk Action
            const checkAll = document.getElementById('checkAll');
            const itemChecks = document.querySelectorAll('.item-check');
            const btnBulkDelete = document.getElementById('btnBulkDelete');
            const selectedCountBadge = document.getElementById('selectedCountBadge');

            function updateBulkStatus() {
                const checked = Array.from(itemChecks).filter(cb => cb.checked);
                const count = checked.length;
                if (count > 0) {
                    btnBulkDelete.disabled = false;
                    selectedCountBadge.classList.remove('d-none');
                    selectedCountBadge.innerText = `${count} mục được chọn`;
                } else {
                    btnBulkDelete.disabled = true;
                    selectedCountBadge.classList.add('d-none');
                }
            }

            if (checkAll) {
                checkAll.addEventListener('change', function () {
                    itemChecks.forEach(cb => cb.checked = this.checked);
                    updateBulkStatus();
                });
            }

            itemChecks.forEach(cb => {
                cb.addEventListener('change', updateBulkStatus);
            });

            // 2. Xóa hàng loạt
            window.handleBulkDelete = async function () {
                const checked = Array.from(itemChecks).filter(cb => cb.checked);
                const ids = checked.map(cb => cb.value);

                if (!ids.length) return;
                if (!confirm(`Bạn có chắc chắn muốn xóa vĩnh viễn ${ids.length} chuyển hướng đã chọn?`)) return;

                btnBulkDelete.disabled = true;
                btnBulkDelete.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang xóa...';

                try {
                    const res = await fetch("{{ route('admin.redirects.bulk-destroy') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ ids: ids })
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert(data.message || 'Đã xóa thành công!');
                        window.location.reload();
                    } else {
                        alert(data.message || 'Lỗi khi xóa!');
                    }
                } catch (err) {
                    alert('Lỗi kết nối: ' + err.message);
                } finally {
                    btnBulkDelete.disabled = false;
                    btnBulkDelete.innerHTML = '<i class="fas fa-trash-alt me-1"></i> Xóa mục đã chọn';
                }
            };

            // 3. Toggle trạng thái Active qua AJAX
            document.querySelectorAll('.toggle-status').forEach(toggle => {
                toggle.addEventListener('change', async function () {
                    const url = this.dataset.url;
                    const id = this.dataset.id;
                    const badge = document.querySelector(`.status-badge-${id}`);
                    try {
                        const res = await fetch(url, {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            }
                        });
                        const data = await res.json();
                        if (data.success) {
                            if (badge) {
                                badge.className = data.is_active ? `badge bg-success status-badge-${id}` : `badge bg-secondary status-badge-${id}`;
                                badge.textContent = data.is_active ? 'Đang bật' : 'Tạm dừng';
                            }
                        } else {
                            alert('Không thể thay đổi trạng thái!');
                            this.checked = !this.checked;
                        }
                    } catch (err) {
                        alert('Lỗi: ' + err.message);
                        this.checked = !this.checked;
                    }
                });
            });

            // 3.1. Copy URL Helper
            document.querySelectorAll('.btn-copy-url').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const text = this.dataset.url;
                    if (!text) return;
                    navigator.clipboard.writeText(text).then(() => {
                        const originalHtml = this.innerHTML;
                        this.innerHTML = '<span class="text-success small fw-bold" style="font-size:0.7rem;">Đã chép!</span>';
                        setTimeout(() => {
                            this.innerHTML = originalHtml;
                        }, 1400);
                    }).catch(() => {
                        prompt('Sao chép link:', text);
                    });
                });
            });

            // 4. Modal Sửa - Điền sẵn dữ liệu
            const editModalEl = document.getElementById('editModal');
            const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;
            const editForm = document.getElementById('editForm');
            const editModalId = document.getElementById('editModalId');
            const editOldUrl = document.getElementById('editOldUrl');
            const editNewUrl = document.getElementById('editNewUrl');
            const editStatusCode = document.getElementById('editStatusCode');
            const editIsActive = document.getElementById('editIsActive');
            const editNote = document.getElementById('editNote');

            document.querySelectorAll('.btn-edit').forEach(btn => {
                btn.addEventListener('click', function () {
                    const ds = this.dataset;
                    editModalId.innerText = ds.id;
                    editOldUrl.value = ds.oldUrl;
                    editNewUrl.value = ds.newUrl;
                    editStatusCode.value = ds.statusCode;
                    editIsActive.value = ds.isActive;
                    editNote.value = ds.note || '';
                    editForm.action = ds.updateUrl;
                    editModal.show();
                });
            });

            // 5. IMPORT EXCEL / CSV BATCH & PREVIEW
            const importDropZone = document.getElementById('importDropZone');
            const importFileInput = document.getElementById('importFileInput');
            const importFileMeta = document.getElementById('importFileMeta');
            const importFileName = document.getElementById('importFileName');
            const importFileCount = document.getElementById('importFileCount');
            const columnMappingArea = document.getElementById('columnMappingArea');
            const mapIdCol = document.getElementById('mapIdCol');
            const mapOldUrlCol = document.getElementById('mapOldUrlCol');
            const mapNewUrlCol = document.getElementById('mapNewUrlCol');
            const previewArea = document.getElementById('previewArea');
            const previewThead = document.getElementById('previewThead');
            const previewTbody = document.getElementById('previewTbody');
            const previewTotalBadge = document.getElementById('previewTotalBadge');
            const btnStartImport = document.getElementById('btnStartImport');
            const importProgressArea = document.getElementById('importProgressArea');
            const importProgressBar = document.getElementById('importProgressBar');
            const importProgressText = document.getElementById('importProgressText');
            const importPercentText = document.getElementById('importPercentText');
            const statInserted = document.getElementById('statInserted');
            const statUpdated = document.getElementById('statUpdated');
            const statSkipped = document.getElementById('statSkipped');
            const importLogContainer = document.getElementById('importLogContainer');
            const importLogContent = document.getElementById('importLogContent');

            let parsedRows = [];
            let detectedHeaders = [];

            if (importDropZone && importFileInput) {
                importDropZone.addEventListener('click', () => importFileInput.click());
                importDropZone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    importDropZone.style.borderColor = '#0284c7';
                    importDropZone.style.background = '#f0f9ff';
                });
                importDropZone.addEventListener('dragleave', () => {
                    importDropZone.style.borderColor = '#cbd5e1';
                    importDropZone.style.background = '#f8fafc';
                });
                importDropZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    importDropZone.style.borderColor = '#cbd5e1';
                    importDropZone.style.background = '#f8fafc';
                    if (e.dataTransfer.files.length) {
                        handleFile(e.dataTransfer.files[0]);
                    }
                });
                importFileInput.addEventListener('change', (e) => {
                    if (e.target.files.length) {
                        handleFile(e.target.files[0]);
                    }
                });
            }

            function logMessage(msg, type = 'info') {
                importLogContainer.classList.remove('d-none');
                const time = new Date().toLocaleTimeString();
                const div = document.createElement('div');
                div.innerHTML = `[${time}] ${msg}`;
                if (type === 'success') div.style.color = '#34d399';
                if (type === 'error') div.style.color = '#f87171';
                importLogContent.appendChild(div);
                importLogContainer.scrollTop = importLogContainer.scrollHeight;
            }

            function handleFile(file) {
                importFileName.innerText = `${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
                importFileMeta.classList.remove('d-none');
                logMessage(`Đang tải file: ${file.name}...`);

                const reader = new FileReader();
                reader.onload = function (e) {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheet = workbook.SheetNames[0];
                        const sheet = workbook.Sheets[firstSheet];

                        // Đọc header và rows
                        const rawHeaderRows = XLSX.utils.sheet_to_json(sheet, { header: 1 });
                        detectedHeaders = (rawHeaderRows && rawHeaderRows.length > 0) ? rawHeaderRows[0] : [];
                        parsedRows = XLSX.utils.sheet_to_json(sheet);

                        if (!parsedRows.length) {
                            alert('File không có dòng dữ liệu nào!');
                            return;
                        }

                        importFileCount.innerText = `Tìm thấy: ${parsedRows.length.toLocaleString()} dòng dữ liệu`;
                        previewTotalBadge.innerText = `Tổng cộng: ${parsedRows.length.toLocaleString()} dòng`;

                        // Populate mapping selects
                        populateMappingOptions();

                        // Render Preview bảng 5 dòng đầu
                        renderPreviewTable();

                        columnMappingArea.classList.remove('d-none');
                        previewArea.classList.remove('d-none');
                        btnStartImport.disabled = false;
                        logMessage(`Đọc thành công ${parsedRows.length} dòng. Vui lòng kiểm tra bản xem trước và chọn cột.`, 'success');
                    } catch (err) {
                        console.error(err);
                        alert('Lỗi đọc file: ' + err.message);
                        logMessage('Lỗi đọc file: ' + err.message, 'error');
                    }
                };
                reader.readAsArrayBuffer(file);
            }

            function populateMappingOptions() {
                const makeOptions = (defaultField) => {
                    let html = '<option value="">-- Bỏ qua / Không có --</option>';
                    detectedHeaders.forEach((h, idx) => {
                        if (!h) return;
                        const headerStr = String(h).trim();
                        const isMatch = checkHeaderMatch(headerStr, defaultField, idx);
                        html += `<option value="${headerStr}" ${isMatch ? 'selected' : ''}>Cột ${String.fromCharCode(65 + idx)}: ${headerStr}</option>`;
                    });
                    return html;
                };

                mapIdCol.innerHTML = makeOptions('id');
                mapOldUrlCol.innerHTML = makeOptions('old_url');
                mapNewUrlCol.innerHTML = makeOptions('new_url');
            }

            function checkHeaderMatch(header, field, colIndex) {
                const lower = header.toLowerCase();
                if (field === 'id') {
                    return lower === 'id' || colIndex === 0;
                }
                if (field === 'old_url') {
                    return lower.includes('cũ') || lower.includes('old') || lower.includes('gốc') || colIndex === 1;
                }
                if (field === 'new_url') {
                    return lower.includes('mới') || lower.includes('new') || lower.includes('đích') || colIndex === 2;
                }
                return false;
            }

            function renderPreviewTable() {
                // Header
                let thHtml = '<tr><th style="width: 50px;">#</th>';
                detectedHeaders.forEach((h, idx) => {
                    thHtml += `<th>Cột ${String.fromCharCode(65 + idx)}: ${h || 'Trống'}</th>`;
                });
                thHtml += '</tr>';
                previewThead.innerHTML = thHtml;

                // 5 Dòng đầu
                let tbHtml = '';
                const previewRows = parsedRows.slice(0, 5);
                previewRows.forEach((row, rIdx) => {
                    tbHtml += `<tr><td class="text-muted fw-bold">${rIdx + 1}</td>`;
                    detectedHeaders.forEach(h => {
                        const val = row[h] !== undefined ? row[h] : '';
                        tbHtml += `<td class="text-truncate" style="max-width: 250px;">${val}</td>`;
                    });
                    tbHtml += '</tr>';
                });
                previewTbody.innerHTML = tbHtml;
            }

            // BẮT ĐẦU NHẬP THEO BATCH
            if (btnStartImport) {
                btnStartImport.addEventListener('click', async function () {
                    const oldUrlCol = mapOldUrlCol.value;
                    const newUrlCol = mapNewUrlCol.value;
                    const idCol = mapIdCol.value;

                    if (!oldUrlCol || !newUrlCol) {
                        alert('Vui lòng chọn Cột Link cũ và Cột Link mới để khớp dữ liệu.');
                        return;
                    }

                    if (!confirm(`Bắt đầu nhập ${parsedRows.length.toLocaleString()} dòng dữ liệu? Quá trình sẽ chạy mượt mà theo lô mà không làm đơ máy.`)) {
                        return;
                    }

                    btnStartImport.disabled = true;
                    importProgressArea.classList.remove('d-none');
                    logMessage(`Bắt đầu tiến trình nhập ${parsedRows.length} dòng...`);

                    const BATCH_SIZE = 500; // Mỗi lô 500 dòng
                    const totalRows = parsedRows.length;
                    let totalInserted = 0;
                    let totalUpdated = 0;
                    let totalSkipped = 0;
                    let processed = 0;

                    for (let i = 0; i < totalRows; i += BATCH_SIZE) {
                        const chunk = parsedRows.slice(i, i + BATCH_SIZE);
                        const chunkNum = Math.floor(i / BATCH_SIZE) + 1;
                        const totalChunks = Math.ceil(totalRows / BATCH_SIZE);

                        try {
                            const response = await fetch("{{ route('admin.redirects.import-batch') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    rows: chunk,
                                    mapping: {
                                        id: idCol,
                                        old_url: oldUrlCol,
                                        new_url: newUrlCol,
                                    }
                                })
                            });

                            const resData = await response.json();
                            if (resData.success) {
                                totalInserted += resData.inserted || 0;
                                totalUpdated += resData.updated || 0;
                                totalSkipped += resData.skipped || 0;
                            } else {
                                totalSkipped += chunk.length;
                                logMessage(`Lỗi tại lô ${chunkNum}: ${resData.message}`, 'error');
                            }
                        } catch (err) {
                            totalSkipped += chunk.length;
                            logMessage(`Lỗi mạng tại lô ${chunkNum}: ${err.message}`, 'error');
                        }

                        processed += chunk.length;
                        const percent = Math.min(100, Math.round((processed / totalRows) * 100));

                        importProgressBar.style.width = `${percent}%`;
                        importPercentText.innerText = `${percent}%`;
                        importProgressText.innerText = `Đang xử lý: ${processed.toLocaleString()}/${totalRows.toLocaleString()} dòng (Lô ${chunkNum}/${totalChunks})`;
                        statInserted.innerText = totalInserted.toLocaleString();
                        statUpdated.innerText = totalUpdated.toLocaleString();
                        statSkipped.innerText = totalSkipped.toLocaleString();
                    }

                    importProgressBar.classList.remove('progress-bar-animated');
                    logMessage(`Hoàn tất nhập dữ liệu! Tạo mới/Cập nhật: ${totalInserted}, Theo ID: ${totalUpdated}, Bỏ qua/Lỗi: ${totalSkipped}`, 'success');
                    alert(`Đã hoàn tất nhập dữ liệu!\n- Tạo mới/Cập nhật link: ${totalInserted}\n- Cập nhật theo ID: ${totalUpdated}\n- Bỏ qua: ${totalSkipped}`);
                    window.location.reload();
                });
            }
        });
    </script>
@endpush
