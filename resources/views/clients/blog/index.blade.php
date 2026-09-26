@extends('clients.layouts.master')

@section('title', (isset($currentCategory) && $currentCategory ? ($currentCategory->meta_title ?: $currentCategory->name
    . ' - Xu hướng & Phong cách') : 'Tin tức, Xu hướng & Phong cách sống') . ' | ' . config('app.name'))

@section('head')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <meta name="description"
        content="{{ isset($currentCategory) && $currentCategory ? ($currentCategory->meta_description ?: ($currentCategory->description ?: 'Tổng hợp các bài viết và thông tin mới nhất về ' . $currentCategory->name . ' tại ' . config('app.name') . '.')) : 'Cập nhật xu hướng thời trang mới nhất, cẩm nang phối đồ và chia sẻ hữu ích về phong cách sống tại ' . config('app.name') . '.' }}">
    <link rel="canonical"
        href="{{ isset($currentCategory) && $currentCategory ? route('client.blog.category', $currentCategory) : route('client.blog.index') }}">

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
            height: 230px;
            width: 100%;
        }

        .blog-card .card-body {
            padding: 16px 18px;
        }

        /* Sidebar */
        .blog-sidebar {
            position: static;
        }

        @media (min-width: 992px) {
            .blog-sidebar {
                position: sticky;
                top: 90px;
                align-self: flex-start;
                z-index: 10;
            }
        }

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
            margin-bottom: 20px;
            background: #fdfdfd;
            padding: 10px 20px;
            border-radius: 50px;
            display: inline-block;
            border: 1px solid #eee;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        }

        .breadcrumb-list {
            display: flex;
            align-items: center;
            gap: 10px;
            list-style: none;
            padding: 0;
            margin: 0;
            flex-wrap: wrap;
        }

        .breadcrumb-item {
            display: flex;
            align-items: center;
        }

        .breadcrumb-item a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .breadcrumb-item a:hover {
            color: var(--primary-color, #ff3366);
        }

        .breadcrumb-item a svg {
            width: 16px;
            height: 16px;
            stroke-width: 2px;
        }

        .breadcrumb-item.active span {
            color: var(--primary-color, #ff3366);
            font-size: 14px;
            font-weight: 600;
        }

        .breadcrumb-separator {
            color: #ccc;
            display: flex;
            align-items: center;
        }

        /* Blog Pagination */
        .blog-pagination-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 36px;
            margin-bottom: 24px;
        }

        .blog-pagination-nav {
            display: inline-block;
        }

        .blog-pagination-list {
            display: flex;
            align-items: center;
            gap: 6px;
            list-style: none;
            padding: 0;
            margin: 0;
            flex-wrap: wrap;
            justify-content: center;
        }

        .blog-pagination-list .page-item {
            display: inline-flex;
        }

        .blog-pagination-list .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 38px;
            height: 38px;
            padding: 0 12px;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            user-select: none;
        }

        .blog-pagination-list .page-link:hover {
            color: #111827;
            background: #f9fafb;
            border-color: #d1d5db;
        }

        .blog-pagination-list .page-item.active .page-link {
            color: #ffffff;
            background: #111827;
            border-color: #111827;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        }

        .blog-pagination-list .page-item.disabled .page-link {
            color: #d1d5db;
            background: #fdfdfd;
            border-color: #f3f4f6;
            cursor: not-allowed;
            pointer-events: none;
        }

        .blog-pagination-list .page-item.dots .page-link {
            border: none;
            background: transparent;
            min-width: 24px;
            padding: 0 4px;
            color: #9ca3af;
            font-weight: 600;
        }

        @media (max-width: 576px) {
            .blog-pagination-wrapper {
                margin-top: 24px;
                margin-bottom: 16px;
            }

            .blog-pagination-list {
                gap: 4px;
            }

            .blog-pagination-list .page-link {
                min-width: 32px;
                height: 32px;
                padding: 0 8px;
                font-size: 13px;
                border-radius: 6px;
            }
        }
    </style>
@endsection

@section('schema')
    @if (isset($schemaData) && is_array($schemaData))
        @foreach ($schemaData as $schema)
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
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        <span>Trang chủ</span>
                    </a>
                </li>
                @if (isset($currentCategory) && $currentCategory)
                    <li class="breadcrumb-separator">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('client.blog.index') }}">Tin tức</a>
                    </li>
                    <li class="breadcrumb-separator">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <span>{{ $currentCategory->name }}</span>
                    </li>
                @else
                    <li class="breadcrumb-separator">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <span>Tin tức</span>
                    </li>
                @endif
            </ol>
        </nav>

        <!-- HERO -->
        <div class="blog-hero">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    @if (isset($currentCategory) && $currentCategory)
                        <p class="text-uppercase text-primary fw-semibold small mb-1">CHUYÊN MỤC</p>
                        <h1 class="h3 fw-bold mb-2">{{ $currentCategory->name }}</h1>
                        <p class="text-muted small mb-0">
                            {{ $currentCategory->description ?: 'Tổng hợp các bài viết và thông tin mới nhất thuộc chuyên mục ' . $currentCategory->name . '.' }}
                        </p>
                    @else
                        <p class="text-uppercase text-muted small mb-1">CHUYÊN TRANG PHONG CÁCH</p>
                        <h1 class="h3 fw-bold mb-2">Định hình phong cách & Cảm hứng sống hiện đại</h1>
                        <p class="text-muted small mb-0">
                            Khám phá những xu hướng thời trang đương đại, cẩm nang phối đồ tinh tế và câu chuyện phong cách
                            sống được tuyển chọn bởi {{ config('app.name') }}.
                        </p>
                    @endif
                </div>

                <div class="col-lg-4 mt-3 mt-lg-0">
                    <div class="d-flex justify-content-lg-end gap-3">
                        <div>
                            <div class="fw-bold">{{ number_format($featuredPosts->count()) }}</div>
                            <span class="text-muted tiny">Tuyển chọn</span>
                        </div>
                        <div>
                            <div class="fw-bold">{{ number_format($posts->total()) }}</div>
                            <span class="text-muted tiny">Bài viết</span>
                        </div>
                        <div>
                            <div class="fw-bold">{{ number_format($sidebarCategories->count()) }}</div>
                            <span class="text-muted tiny">Chuyên mục</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FEATURED -->
        {{-- @if ($featuredPosts->isNotEmpty())
        <section class="blog-featured mb-4">
            <h2 class="h5 fw-bold mb-3">Bài viết nổi bật</h2>
            <div class="row g-3">
                @foreach ($featuredPosts as $featured)
                    <div class="col-md-4">
                        <article class="card h-100">
                            @if ($featured->thumbnail)
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
                @if (isset($searchKeyword) && $searchKeyword !== '')
                    <h4 class="mb-4">Kết quả tìm kiếm cho: <strong>"{{ $searchKeyword }}"</strong>
                        ({{ $posts->total() }}
                        bài viết)</h4>
                @endif
                <div class="row g-3">
                    @forelse($posts as $post)
                        <div class="col-md-6">
                            <article class="blog-card h-100">
                                <img src="{{ $post->thumbnail ? asset('clients/assets/img/posts/' . $post->thumbnail) : asset('clients/assets/img/clothes/no-image.webp') }}"
                                    alt="{{ renderMeta($post->thumbnail_alt_text ?? $post->title) }}" loading="lazy"
                                    onerror="this.onerror=null; this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}';">

                                <div class="card-body">
                                    <div class="d-flex gap-2 tiny text-muted mb-1 align-items-center">
                                        @if ($post->category)
                                            <a href="{{ route('client.blog.category', $post->category) }}"
                                                class="text-decoration-none text-primary fw-semibold">
                                                {{ $post->category->name }}
                                            </a>
                                        @else
                                            <span>Tin tức</span>
                                        @endif •
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
                                        <a href="{{ route('client.blog.show', $post) }}"
                                            class="btn btn-sm btn-outline-dark">
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

                <div class="blog-pagination-wrapper">
                    {{ $posts->onEachSide(1)->links('pagination.blog') }}
                </div>

                <div class="blog-category-editorial-note"
                    style="margin-top: 35px; padding: 18px 20px; background: #f8f8f8; border-left: 3px solid #222; font-size: 14px; line-height: 1.7; color: #555;">
                    <strong style="display: block; margin-bottom: 6px; color: #222;">
                        Về nội dung trong chuyên mục
                    </strong>

                    <p style="margin: 0;">
                        Các bài viết tại Nobi Fashion được xây dựng thông qua quá trình tìm hiểu,
                        tổng hợp, tham khảo, đối chiếu và biên tập từ nhiều nguồn thông tin khác nhau.
                        Chúng tôi luôn cố gắng cung cấp nội dung hữu ích và cập nhật cho người đọc.
                        Nếu bạn phát hiện thông tin chưa chính xác hoặc cần được bổ sung, vui lòng
                        liên hệ với Nobi Fashion để chúng tôi kiểm tra và cập nhật.
                        <a href="{{ route('client.policy.editorial') }}"
                            style="color: #222; font-weight: 600; text-decoration: underline;">
                            Xem nguyên tắc biên tập và chính sách đính chính
                        </a>.
                    </p>
                </div>
            </div>

            <!-- SIDEBAR -->
            <aside class="col-lg-4 blog-sidebar">

                <!-- Categories -->
                <div class="card mb-3">
                    <h5 class="fw-bold mb-2">Danh mục nổi bật</h5>
                    <ul class="list-unstyled mb-0">
                        @foreach ($sidebarCategories as $category)
                            <li class="d-flex justify-content-between small align-items-center">
                                <a href="{{ route('client.blog.category', $category) }}"
                                    class="text-decoration-none text-dark hover-primary {{ isset($currentCategory) && $currentCategory && $currentCategory->id === $category->id ? 'fw-bold text-primary' : '' }}">
                                    {{ $category->name }}
                                </a>
                                <span
                                    class="badge bg-light text-secondary rounded-pill">{{ number_format($category->posts_count) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Tags -->
                <div class="card mb-3">
                    <h5 class="fw-bold mb-2">Hashtag nổi bật</h5>
                    @foreach ($sidebarTags as $tag)
                        <span class="blog-tag">#{{ $tag->name }}</span>
                    @endforeach
                </div>

                <!-- Recent Posts -->
                <div class="card mb-3">
                    <h5 class="fw-bold mb-2">Bài viết mới</h5>
                    <ul class="list-unstyled mb-0">
                        @foreach ($recentPosts as $recent)
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
                        @foreach ($popularPosts as $popular)
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
