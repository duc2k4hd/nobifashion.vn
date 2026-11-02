@extends('admins.layouts.master')

@section('title', 'Chi tiết Video Reel: ' . $reel->title)

@push('styles')
<link rel="stylesheet" href="{{ asset('admins/css/reels_admin.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="nobifashion_admin_reels_container">
    <div class="nobifashion_admin_reels_header">
        <div>
            <h1 class="nobifashion_admin_reels_title">
                <i class="fa-solid fa-circle-info text-primary"></i> Chi tiết Reel #{{ $reel->id }}
            </h1>
            <p class="nobifashion_admin_reels_subtitle">
                {{ $reel->title }} • Đăng bởi {{ $reel->author_name }} ({{ $reel->author_handle }})
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.reels.videos.index') }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline">
                <i class="fa-solid fa-arrow-left"></i> Danh sách
            </a>
            <a href="{{ route('admin.reels.videos.edit', $reel->id) }}" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_primary">
                <i class="fa-solid fa-pen-to-square"></i> Chỉnh sửa
            </a>
            <a href="/reels" target="_blank" class="nobifashion_admin_reels_btn nobifashion_admin_reels_btn_outline">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Mở trên Web
            </a>
        </div>
    </div>

    @include('admins.reels.partials.subnav')

    <div class="row g-4">
        <!-- Left: Video Player & Overview (5 cols) -->
        <div class="col-lg-5">
            <div class="nobifashion_admin_reels_card">
                <div class="nobifashion_admin_reels_card_header">
                    <h2 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-play text-primary"></i> Video Playback (9:16)
                    </h2>
                    <span class="nobifashion_admin_reels_badge nobifashion_admin_reels_badge_{{ $reel->status }}">
                        {{ $reel->status }}
                    </span>
                </div>
                <div class="nobifashion_admin_reels_card_body text-center">
                    <div class="nobifashion_admin_reels_preview_frame" style="width: 260px; height: 462px;">
                        <video src="{{ $reel->video_url }}" controls poster="{{ $reel->poster_url ?: $reel->thumbnail_url }}" class="nobifashion_admin_reels_preview_video"></video>
                    </div>

                    <div class="mt-3 text-start bg-light p-3 rounded" style="font-size: 13px;">
                        <div class="mb-1"><strong>Mã nguồn R2:</strong> <code>{{ $reel->external_id ?: 'N/A' }}</code></div>
                        <div class="mb-1"><strong>Thời lượng:</strong> {{ $reel->duration }} giây</div>
                        <div class="mb-1"><strong>Độ phân giải:</strong> {{ $reel->width }}x{{ $reel->height }} ({{ $reel->aspect_ratio }})</div>
                        <div class="mb-1"><strong>MIME Type:</strong> {{ $reel->mime_type }}</div>
                        <div class="mb-1 text-truncate"><strong>URL Video:</strong> <a href="{{ $reel->video_url }}" target="_blank">{{ $reel->video_url }}</a></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Metadata & Stats (7 cols) -->
        <div class="col-lg-7">
            <!-- Stats Grid -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-white border rounded text-center">
                        <div class="text-muted" style="font-size: 11px; text-transform: uppercase;">Lượt xem</div>
                        <div class="fw-bold mt-1" style="font-size: 20px;">{{ number_format($reel->view_count) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-white border rounded text-center">
                        <div class="text-muted" style="font-size: 11px; text-transform: uppercase;">Lượt thả tim</div>
                        <div class="fw-bold text-danger mt-1" style="font-size: 20px;">{{ number_format($reel->like_count) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-white border rounded text-center">
                        <div class="text-muted" style="font-size: 11px; text-transform: uppercase;">Bình luận</div>
                        <div class="fw-bold text-info mt-1" style="font-size: 20px;">{{ number_format($reel->comment_count) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-white border rounded text-center">
                        <div class="text-muted" style="font-size: 11px; text-transform: uppercase;">Lượt lưu</div>
                        <div class="fw-bold text-warning mt-1" style="font-size: 20px;">{{ number_format($reel->save_count) }}</div>
                    </div>
                </div>
            </div>

            <!-- Watch Logs Analytics -->
            @if($watchStats && $watchStats->total_watches > 0)
            <div class="nobifashion_admin_reels_card mb-4">
                <div class="nobifashion_admin_reels_card_header">
                    <h2 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-chart-line text-success"></i> Phân tích Hành vi Xem (Watch Logs)
                    </h2>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    <div class="row g-3" style="font-size: 13.5px;">
                        <div class="col-md-4">
                            <span class="text-muted">Tổng phiên xem:</span>
                            <strong>{{ number_format($watchStats->total_watches) }}</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted">Xem hết (Completed):</span>
                            <strong>{{ number_format($watchStats->completed_watches) }}</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted">Lượt Replay Loops:</span>
                            <strong>{{ number_format($watchStats->total_loops) }}</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted">Thời gian xem TB:</span>
                            <strong>{{ $watchStats->avg_watch_time }}s</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted">Tỷ lệ xem TB:</span>
                            <strong>{{ $watchStats->avg_completion_rate }}%</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted">Click xem sản phẩm:</span>
                            <strong>{{ number_format($watchStats->logs_product_clicks ?? 0) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Metadata Info -->
            <div class="nobifashion_admin_reels_card mb-4">
                <div class="nobifashion_admin_reels_card_header">
                    <h2 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-tags text-primary"></i> Thông tin Phân loại & Thương hiệu
                    </h2>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    <div class="row g-3" style="font-size: 13px;">
                        <div class="col-md-6">
                            <strong>Danh mục:</strong> <span class="badge bg-light text-dark border">{{ $reel->category }}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Thương hiệu:</strong>
                            @if($reel->brand)
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $reel->brand->name }}</span>
                            @else
                                <span class="text-muted">Nobi Fashion (Mặc định)</span>
                            @endif
                        </div>
                        <div class="col-12">
                            <strong>Hashtags:</strong>
                            @if($reel->hashtags)
                                @foreach((array) $reel->hashtags as $tag)
                                    <span class="badge bg-secondary-subtle text-secondary me-1">{{ $tag }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">Không có</span>
                            @endif
                        </div>
                        <div class="col-12">
                            <strong>Caption:</strong>
                            <div class="p-2 bg-light rounded mt-1">{{ $reel->caption ?: 'Không có mô tả.' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tagged Products -->
            <div class="nobifashion_admin_reels_card">
                <div class="nobifashion_admin_reels_card_header">
                    <h2 class="nobifashion_admin_reels_card_title">
                        <i class="fa-solid fa-bag-shopping text-warning"></i> Sản phẩm gắn trong Video ({{ $reel->products->count() }})
                    </h2>
                </div>
                <div class="nobifashion_admin_reels_card_body">
                    @forelse($reel->products as $product)
                        @php $img = $product->primaryImage ? $product->primaryImage->file_path : null; @endphp
                        <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width: 36px; height: 36px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                                    @if($img)
                                        <img src="{{ str_starts_with($img, 'http') ? $img : '/' . ltrim($img, '/') }}" style="width:100%; height:100%; object-fit:cover;">
                                    @endif
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size:13px;">{{ $product->name }}</div>
                                    <div class="text-muted" style="font-size:11.5px;">SKU: {{ $product->sku ?: 'N/A' }} | Giá: {{ number_format($product->price) }}đ</div>
                                </div>
                            </div>
                            <a href="{{ route('admin.products.edit', $product->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary py-0" style="font-size: 11px;">
                                Xem SP <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </div>
                    @empty
                        <div class="text-muted text-center py-3" style="font-size: 13px;">Video này chưa gắn sản phẩm nào.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

