<div class="nobifashion_admin_reels_subnav">
    <a href="{{ route('admin.reels.dashboard') }}" class="nobifashion_admin_reels_subnav_item {{ request()->routeIs('admin.reels.dashboard') || request()->routeIs('admin.reels.index') ? 'active' : '' }}">
        <i class="fa-solid fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('admin.reels.videos.index') }}" class="nobifashion_admin_reels_subnav_item {{ request()->routeIs('admin.reels.videos.index') || request()->routeIs('admin.reels.videos.edit') || request()->routeIs('admin.reels.videos.show') ? 'active' : '' }}">
        <i class="fa-solid fa-film"></i> Tất cả Video
    </a>
    <a href="{{ route('admin.reels.videos.create') }}" class="nobifashion_admin_reels_subnav_item {{ request()->routeIs('admin.reels.videos.create') ? 'active' : '' }}">
        <i class="fa-solid fa-plus-circle"></i> Thêm Video mới
    </a>
    <a href="{{ route('admin.reels.comments.index') }}" class="nobifashion_admin_reels_subnav_item {{ request()->routeIs('admin.reels.comments.*') ? 'active' : '' }}">
        <i class="fa-solid fa-comments"></i> Bình luận
    </a>
    <a href="{{ route('admin.reels.anonymous-users.index') }}" class="nobifashion_admin_reels_subnav_item {{ request()->routeIs('admin.reels.anonymous-users.*') ? 'active' : '' }}">
        <i class="fa-solid fa-user-secret"></i> Khách ẩn danh
    </a>
    <a href="{{ route('admin.reels.moderation.index') }}" class="nobifashion_admin_reels_subnav_item {{ request()->routeIs('admin.reels.moderation.*') ? 'active' : '' }}">
        <i class="fa-solid fa-shield-halved"></i> Kiểm duyệt
    </a>
    <a href="{{ route('admin.reels.stickers.index') }}" class="nobifashion_admin_reels_subnav_item {{ request()->routeIs('admin.reels.stickers.*') ? 'active' : '' }}">
        <i class="fa-solid fa-icons"></i> Sticker
    </a>
    <a href="{{ route('admin.reels.distribution.index') }}" class="nobifashion_admin_reels_subnav_item {{ request()->routeIs('admin.reels.distribution.*') ? 'active' : '' }}">
        <i class="fa-solid fa-shuffle"></i> Phân phối Feed
    </a>
    <a href="{{ route('admin.reels.analytics.index') }}" class="nobifashion_admin_reels_subnav_item {{ request()->routeIs('admin.reels.analytics.*') ? 'active' : '' }}">
        <i class="fa-solid fa-chart-pie"></i> Analytics & Hành vi
    </a>
    <a href="{{ route('admin.reels.import.index') }}" class="nobifashion_admin_reels_subnav_item {{ request()->routeIs('admin.reels.import.*') ? 'active' : '' }}">
        <i class="fa-solid fa-file-import"></i> Import Center
    </a>
    <a href="{{ route('admin.reels.cloudflare.index') }}" class="nobifashion_admin_reels_subnav_item {{ request()->routeIs('admin.reels.cloudflare.*') ? 'active' : '' }}">
        <i class="fa-brands fa-cloudflare text-warning"></i> Cloudflare R2
    </a>
    <a href="{{ route('admin.reels.settings.index') }}" class="nobifashion_admin_reels_subnav_item {{ request()->routeIs('admin.reels.settings.*') ? 'active' : '' }}">
        <i class="fa-solid fa-gear"></i> Cài đặt
    </a>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="border-radius:10px;">
        <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border-radius:10px;">
        <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('warning'))
    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert" style="border-radius:10px;">
        <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

