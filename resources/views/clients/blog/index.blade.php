@extends('clients.layouts.master')

@section('title', 'Blog & Tin tức thời trang | ' . config('app.name'))

@section('head')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
<meta name="description"
    content="Chia sẻ kinh nghiệm phối đồ, xu hướng thời trang và các câu chuyện thương hiệu tại {{ config('app.name') }}.">
<link rel="canonical" href="{{ route('client.blog.index') }}">

<style>
    /* Tổng thể trang */
    .blog-page {
        max-width: 1200px;
    }

    /* Hero */
    .blog-hero {
        background: #fafafa;
        border-radius: 16px;
        padding: 28px 32px;
        margin-bottom: 24px;
        border: 1px solid #eee;
    }

    .nobifashion_header_main_nav_links {
        height: 20px !important;
    }

    /* Featured */
    .blog-featured .card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        transition: 0.2s ease;
    }

    .blog-featured .card:hover {
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        transform: translateY(-3px);
    }

    /* Bài viết */
    .blog-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        transition: 0.2s;
        background: #fff;
    }

    .blog-card:hover {
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.07);
        transform: translateY(-3px);
    }

    .blog-card img {
        height: 180px;
        object-fit: cover;
        width: 100%;
    }

    .blog-card .card-body {
        padding: 16px 18px;
    }

    /* Sidebar */
    .blog-sidebar .card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px 18px;
    }

    .blog-sidebar h5 {
        font-size: 16px;
        font-weight: 700;
    }

    /* Tag */
    .blog-tag {
        display: inline-block;
        background: #f3f4f6;
        color: #111827;
        padding: 3px 10px;
        margin: 4px 6px 0 0;
        border-radius: 999px;
        font-size: 12px;
    }

    /* List trong sidebar */
    .blog-sidebar ul li {
        padding: 6px 0;
        border-bottom: 1px solid #f1f1f1;
    }

    .blog-sidebar ul li:last-child {
        border-bottom: none;
    }

    .tiny {
        font-size: 11px;
    }

    /* Breadcrumb */
    .blog-breadcrumb {
        margin-bottom: 16px;
    }
    .breadcrumb-list {
        display: flex;
        align-items: center;
        gap: 8px;
        list-style: none;
        padding: 0;
        margin: 0;
        flex-wrap: wrap;
    }
    .breadcrumb-item {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .breadcrumb-item a {
        color: var(--text-muted, #6b7280);
        text-decoration: none;
        font-size: 13px;
        transition: color 0.2s;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .breadcrumb-item a:hover {
        color: var(--text-primary, #111827);
    }
    .breadcrumb-item a i {
        font-size: 12px;
    }
    .breadcrumb-item.active span {
        color: var(--text-primary, #111827);
        font-size: 13px;
        font-weight: 500;
    }
    .breadcrumb-separator {
        color: var(--text-muted, #9ca3af);
        font-size: 10px;
        display: flex;
        align-items: center;
    }
</style>
@endsection

@section('schema')
    @if(isset($schemaData) && is_array($schemaData))
        @foreach($schemaData as $schema)
            <script type="application/ld+json">
                {!! json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) !!}
            </script>
        @endforeach
    @endif
@endsection

@section('content')
<section class="container py-4 blog-page">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="blog-breadcrumb mb-3">
        <ol class="breadcrumb-list">
            <li class="breadcrumb-item">
                <a href="{{ route('client.home.index') }}">
                    <i class="fas fa-home"></i>
                    <span>Trang chủ</span>
                </a>
            </li>
            <li class="breadcrumb-separator">
                <i class="fas fa-chevron-right"></i>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span>Blog</span>
            </li>
        </ol>
    </nav>

    <!-- HERO -->
    <div class="blog-hero">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <p class="text-uppercase text-muted small mb-1">Blog thời trang</p>
                <h1 class="h3 fw-bold mb-2">Khám phá bí quyết phối đồ & phong cách sống hiện đại</h1>
                <p class="text-muted small mb-0">
                    Tổng hợp kiến thức SEO, cảm hứng thời trang và câu chuyện thương hiệu được đội ngũ
                    {{ config('app.name') }} biên tập mỗi ngày.
                </p>
            </div>

            <div class="col-lg-4 mt-3 mt-lg-0">
                <div class="d-flex justify-content-lg-end gap-3">
                    <div>
                        <div class="fw-bold">{{ number_format($featuredPosts->count()) }}</div>
                        <span class="text-muted tiny">Bài nổi bật</span>
                    </div>
                    <div>
                        <div class="fw-bold">{{ number_format($posts->total()) }}</div>
                        <span class="text-muted tiny">Bài viết</span>
                    </div>
                    <div>
                        <div class="fw-bold">{{ number_format($sidebarCategories->count()) }}</div>
                        <span class="text-muted tiny">Chủ đề</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FEATURED -->
    {{-- @if($featuredPosts->isNotEmpty())
        <section class="blog-featured mb-4">
            <h2 class="h5 fw-bold mb-3">Bài viết nổi bật</h2>
            <div class="row g-3">
                @foreach($featuredPosts as $featured)
                    <div class="col-md-4">
                        <article class="card h-100">
                            @if($featured->thumbnail)
                            <img src="{{ asset($featured->thumbnail) }}" alt="{{ $featured->thumbnail_alt_text ?? $featured->title }}"
                                loading="lazy">
                            @endif

                            <div class="card-body">
                                <span class="badge bg-light text-dark rounded-pill mb-2 small">
                                    {{ $featured->category?->name ?? 'Tin tức' }}
                                </span>

                                <h3 class="h6 fw-bold mb-2">
                                    <a href="{{ route('client.blog.show', $featured) }}"
                                        class="text-dark text-decoration-none">
                                        {{ renderMeta($featured->title) }}
                                    </a>
                                </h3>

                                <p class="text-muted tiny">{{ renderMeta($featured->excerpt_text) }}</p>

                                <div class="d-flex justify-content-between tiny text-muted">
                                    <span>{{ optional($featured->published_at)->format('d/m/Y') }}</span>
                                    <span>{{ number_format($featured->views) }} xem</span>
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        </section>
    @endif --}}

    <div class="row g-4">
        <!-- MAIN POSTS -->
        <div class="col-lg-8">
            <div class="row g-3">
                @forelse($posts as $post)
                    <div class="col-md-6">
                        <article class="blog-card h-100">
                            <img src="{{ asset($post->thumbnail ?? 'clients/assets/img/clothes/no-image.webp') }}" 
                                alt="{{ renderMeta($post->thumbnail_alt_text ?? $post->title) }}"
                                loading="lazy"
                                onerror="this.onerror=null; this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}';">

                            <div class="card-body">
                                <div class="d-flex gap-2 tiny text-muted mb-1">
                                    <span>{{ $post->category?->name ?? 'Tin tức' }}</span> •
                                    <span>{{ optional($post->published_at)->format('d/m/Y') }}</span>
                                </div>

                                <h3 class="h6 fw-bold mb-2">
                                    <a href="{{ route('client.blog.show', $post) }}"
                                        class="text-dark text-decoration-none">
                                        {{ renderMeta($post->title) }}
                                    </a>
                                </h3>

                                <p class="text-muted tiny mb-2">{{ renderMeta($post->excerpt_text) }}</p>

                                <div class="d-flex justify-content-between align-items-center tiny text-muted">
                                    <span>{{ number_format($post->views) }} xem</span>
                                    <a href="{{ route('client.blog.show', $post) }}" class="btn btn-sm btn-outline-dark">
                                        Đọc tiếp
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-light border text-center">
                            Chưa có bài viết nào.
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="mt-3">
                {{ $posts->links('pagination::bootstrap-5') }}
            </div>
        </div>

        <!-- SIDEBAR -->
        <aside class="col-lg-4 blog-sidebar">

            <!-- Categories -->
            <div class="card mb-3">
                <h5 class="fw-bold mb-2">Danh mục nổi bật</h5>
                <ul class="list-unstyled mb-0">
                    @foreach($sidebarCategories as $category)
                        <li class="d-flex justify-content-between small">
                            <span>{{ $category->name }}</span>
                            <span class="text-muted">{{ $category->posts_count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Tags -->
            <div class="card mb-3">
                <h5 class="fw-bold mb-2">Hashtag nổi bật</h5>
                @foreach($sidebarTags as $tag)
                    <span class="blog-tag">#{{ $tag->name }}</span>
                @endforeach
            </div>

            <!-- Recent Posts -->
            <div class="card mb-3">
                <h5 class="fw-bold mb-2">Bài viết mới</h5>
                <ul class="list-unstyled mb-0">
                    @foreach($recentPosts as $recent)
                        <li class="mb-2">
                            <a href="{{ route('client.blog.show', $recent) }}"
                                class="text-dark small text-decoration-none">
                                {{ renderMeta($recent->title) }}
                            </a>
                            <div class="text-muted tiny">{{ optional($recent->published_at)->format('d/m') }}</div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Popular -->
            <div class="card">
                <h5 class="fw-bold mb-2">Heatmap lượt xem</h5>
                <ul class="list-unstyled mb-0">
                    @foreach($popularPosts as $popular)
                        <li class="mb-2">
                            <a href="{{ route('client.blog.show', $popular) }}"
                                class="text-dark small text-decoration-none">
                                {{ renderMeta($popular->title) }}
                            </a>
                            <div class="text-muted tiny">{{ number_format($popular->views) }} views</div>
                        </li>
                    @endforeach
                </ul>
            </div>

        </aside>
    </div>

</section>
@endsection
