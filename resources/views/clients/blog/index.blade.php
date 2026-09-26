@extends('clients.layouts.master')

@section('title', (isset($currentCategory) && $currentCategory ? ($currentCategory->meta_title ?: $currentCategory->name
    . ' - Xu hướng & Phong cách') : 'Nobi Blog, Xu hướng & Phong cách sống') . ' | ' . config('app.name'))

@section('head')
    <meta name="description"
        content="{{ isset($currentCategory) && $currentCategory ? ($currentCategory->meta_description ?: ($currentCategory->description ?: 'Tổng hợp các bài viết và thông tin mới nhất về ' . $currentCategory->name . ' tại ' . config('app.name') . '.')) : 'Cập nhật xu hướng thời trang mới nhất, cẩm nang phối đồ và chia sẻ hữu ích về phong cách sống tại ' . config('app.name') . '.' }}">
    <link rel="canonical"
        href="{{ isset($currentCategory) && $currentCategory ? route('client.blog.category', $currentCategory) : route('client.blog.index') }}">
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/blog.css?v=' . env('APP_VERSION')) }}">
@endpush

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
    <section class="nobifashion_blog_container">
        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="nobifashion_blog_breadcrumb">
            <ol class="nobifashion_blog_breadcrumb_list">
                <li class="nobifashion_blog_breadcrumb_item">
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
                    <li class="nobifashion_blog_breadcrumb_separator">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </li>
                    <li class="nobifashion_blog_breadcrumb_item">
                        <a href="{{ route('client.blog.index') }}">Nobi Blog</a>
                    </li>
                    <li class="nobifashion_blog_breadcrumb_separator">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </li>
                    <li class="nobifashion_blog_breadcrumb_item active" aria-current="page">
                        <span>{{ $currentCategory->name }}</span>
                    </li>
                @else
                    <li class="nobifashion_blog_breadcrumb_separator">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </li>
                    <li class="nobifashion_blog_breadcrumb_item active" aria-current="page">
                        <span>Nobi Blog</span>
                    </li>
                @endif
            </ol>
        </nav>

        <!-- HERO -->
        <div class="nobifashion_blog_hero">
            <div class="nobifashion_blog_hero_inner">
                <div class="nobifashion_blog_hero_main">
                    @if (isset($currentCategory) && $currentCategory)
                        <p class="nobifashion_blog_hero_tag">CHUYÊN MỤC</p>
                        <h1 class="nobifashion_blog_hero_title">{{ $currentCategory->name }}</h1>
                        <p class="nobifashion_blog_hero_desc">
                            {{ $currentCategory->description ?: 'Tổng hợp các bài viết và thông tin mới nhất thuộc chuyên mục ' . $currentCategory->name . '.' }}
                        </p>
                    @else
                        <p class="nobifashion_blog_hero_tag muted">CHUYÊN TRANG PHONG CÁCH</p>
                        <h1 class="nobifashion_blog_hero_title">Định hình phong cách & Cảm hứng sống hiện đại</h1>
                        <p class="nobifashion_blog_hero_desc">
                            Khám phá những xu hướng thời trang đương đại, cẩm nang phối đồ tinh tế và câu chuyện phong cách
                            sống được tuyển chọn bởi {{ config('app.name') }}.
                        </p>
                    @endif
                </div>

                <div class="nobifashion_blog_hero_stats_col">
                    <div class="nobifashion_blog_hero_stats">
                        <div>
                            <div class="nobifashion_blog_stat_number">{{ number_format($featuredPosts->count()) }}</div>
                            <span class="nobifashion_blog_stat_label">Tuyển chọn</span>
                        </div>
                        <div>
                            <div class="nobifashion_blog_stat_number">{{ number_format($posts->total()) }}</div>
                            <span class="nobifashion_blog_stat_label">Bài viết</span>
                        </div>
                        <div>
                            <div class="nobifashion_blog_stat_number">{{ number_format($sidebarCategories->count()) }}</div>
                            <span class="nobifashion_blog_stat_label">Chuyên mục</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="nobifashion_blog_layout">
            <!-- MAIN POSTS -->
            <div class="nobifashion_blog_content_area">
                @if (isset($searchKeyword) && $searchKeyword !== '')
                    <h2 class="nobifashion_blog_search_title">
                        Kết quả tìm kiếm cho: <strong>"{{ $searchKeyword }}"</strong> ({{ $posts->total() }} bài viết)
                    </h2>
                @endif

                <div class="nobifashion_blog_grid">
                    @forelse($posts as $post)
                        <article class="nobifashion_blog_card">
                            <div class="nobifashion_blog_card_thumb_wrap">
                                <img src="{{ $post->thumbnail ? asset('clients/assets/img/posts/' . $post->thumbnail) : asset('clients/assets/img/clothes/no-image.webp') }}"
                                    alt="{{ renderMeta($post->thumbnail_alt_text ?? $post->title) }}"
                                    class="nobifashion_blog_card_thumb"
                                    width="400" height="230"
                                    loading="lazy"
                                    onerror="this.onerror=null; this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}';">
                            </div>

                            <div class="nobifashion_blog_card_body">
                                <div class="nobifashion_blog_card_meta">
                                    @if ($post->category)
                                        <a href="{{ route('client.blog.category', $post->category) }}"
                                            class="nobifashion_blog_card_cat">
                                            {{ $post->category->name }}
                                        </a>
                                    @else
                                        <span>Nobi Blog</span>
                                    @endif
                                    <span>•</span>
                                    <span>{{ optional($post->published_at)->format('d/m/Y') }}</span>
                                </div>

                                <h3 class="nobifashion_blog_card_title">
                                    <a href="{{ route('client.blog.show', $post) }}">
                                        {{ renderMeta($post->title) }}
                                    </a>
                                </h3>

                                <p class="nobifashion_blog_card_excerpt">{{ renderMeta($post->excerpt_text) }}</p>

                                <div class="nobifashion_blog_card_footer">
                                    <span>{{ number_format($post->views) }} xem</span>
                                    <a href="{{ route('client.blog.show', $post) }}"
                                        class="nobifashion_blog_card_btn">
                                        Đọc tiếp
                                    </a>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="nobifashion_blog_empty_alert">
                            Chưa có bài viết nào.
                        </div>
                    @endforelse
                </div>

                <div class="nobifashion_blog_pagination_wrapper">
                    {{ $posts->onEachSide(1)->links('pagination.blog') }}
                </div>

                <div class="nobifashion_blog_editorial_note">
                    <strong>Về nội dung trong chuyên mục</strong>
                    <p>
                        Các bài viết tại Nobi Fashion được xây dựng thông qua quá trình tìm hiểu,
                        tổng hợp, tham khảo, đối chiếu và biên tập từ nhiều nguồn thông tin khác nhau.
                        Chúng tôi luôn cố gắng cung cấp nội dung hữu ích và cập nhật cho người đọc.
                        Nếu bạn phát hiện thông tin chưa chính xác hoặc cần được bổ sung, vui lòng
                        liên hệ với Nobi Fashion để chúng tôi kiểm tra và cập nhật.
                        <a href="{{ route('client.policy.editorial') }}">
                            Xem nguyên tắc biên tập và chính sách đính chính
                        </a>.
                    </p>
                </div>
            </div>

            <!-- SIDEBAR -->
            <aside class="nobifashion_blog_sidebar">
                <!-- Categories -->
                <div class="nobifashion_blog_sidebar_widget">
                    <h3 class="nobifashion_blog_widget_title">Danh mục nổi bật</h3>
                    <ul class="nobifashion_blog_category_list">
                        @foreach ($sidebarCategories as $category)
                            <li class="nobifashion_blog_category_item">
                                <a href="{{ route('client.blog.category', $category) }}"
                                    class="nobifashion_blog_category_link {{ isset($currentCategory) && $currentCategory && $currentCategory->id === $category->id ? 'active' : '' }}">
                                    {{ $category->name }}
                                </a>
                                <span class="nobifashion_blog_category_count">
                                    {{ number_format($category->posts_count) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Tags -->
                @if ($sidebarTags->isNotEmpty())
                    <div class="nobifashion_blog_sidebar_widget">
                        <h3 class="nobifashion_blog_widget_title">Hashtag nổi bật</h3>
                        <div class="nobifashion_blog_tag_cloud">
                            @foreach ($sidebarTags as $tag)
                                <a href="{{ route('client.blog.index', ['tag' => $tag->slug]) }}"
                                    class="nobifashion_blog_tag">
                                    #{{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Recent Posts -->
                @if ($recentPosts->isNotEmpty())
                    <div class="nobifashion_blog_sidebar_widget">
                        <h3 class="nobifashion_blog_widget_title">Bài viết mới</h3>
                        <ul class="nobifashion_blog_post_mini_list">
                            @foreach ($recentPosts as $recent)
                                <li class="nobifashion_blog_post_mini_item">
                                    <a href="{{ route('client.blog.show', $recent) }}"
                                        class="nobifashion_blog_post_mini_link">
                                        {{ renderMeta($recent->title) }}
                                    </a>
                                    <div class="nobifashion_blog_post_mini_meta">
                                        {{ optional($recent->published_at)->format('d/m') }}
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Popular -->
                @if ($popularPosts->isNotEmpty())
                    <div class="nobifashion_blog_sidebar_widget">
                        <h3 class="nobifashion_blog_widget_title">Heatmap lượt xem</h3>
                        <ul class="nobifashion_blog_post_mini_list">
                            @foreach ($popularPosts as $popular)
                                <li class="nobifashion_blog_post_mini_item">
                                    <a href="{{ route('client.blog.show', $popular) }}"
                                        class="nobifashion_blog_post_mini_link">
                                        {{ renderMeta($popular->title) }}
                                    </a>
                                    <div class="nobifashion_blog_post_mini_meta">
                                        {{ number_format($popular->views) }} views
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    </section>
@endsection
