@extends('clients.layouts.master')

@section('title', $seoTitle)

@section('head')
    {{-- SEO Meta Tags: Mọi trang /author/... đều được index nếu không có ?, nếu có ? hoặc tham số đằng sau thì noindex --}}
    @php
        $rawUri = (string) (request()->server('REQUEST_URI') ?? ($_SERVER['REQUEST_URI'] ?? ''));
        $rawQuery = (string) (request()->server('QUERY_STRING') ?? ($_SERVER['QUERY_STRING'] ?? ''));
        $hasAnyQuery = ! empty(request()->query())
            || trim($rawQuery) !== ''
            || str_contains($rawUri, '?')
            || str_contains(request()->getRequestUri(), '?');
        $currentRobots = ! $hasAnyQuery
            ? ($robotsMeta ?? 'follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large')
            : 'noindex, follow';
    @endphp
    <meta name="robots" content="{{ $currentRobots }}" />
    <meta name="description" content="{{ $seoDescription }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    
    {{-- Open Graph --}}
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $avatarUrl }}">
    
    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $avatarUrl }}">
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/author.min.css') }}?v={{ env('APP_VERSION', '1.0') }}.{{ @filemtime(public_path('clients/assets/css/author.min.css')) }}">
@endpush

@section('schema')
    <script type="application/ld+json">
    {!! json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endsection

@section('content')
    <div class="nobifashion_author_page">
        <div class="nobifashion_author_container">
            {{-- Breadcrumb --}}
            <nav aria-label="breadcrumb" class="nobifashion_author_breadcrumb">
                <ol class="nobifashion_author_breadcrumb_list">
                    <li class="nobifashion_author_breadcrumb_item">
                        <a href="{{ route('client.home.index') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            <span>Trang chủ</span>
                        </a>
                    </li>
                    <li class="nobifashion_author_breadcrumb_separator">/</li>
                    <li class="nobifashion_author_breadcrumb_item">
                        <a href="{{ $authorIndexUrl ?? route('client.author.index') }}">
                            <span>{{ $breadcrumbRole ?? ($isAdmin ? 'Admin' : 'Staff') }}</span>
                        </a>
                    </li>
                    <li class="nobifashion_author_breadcrumb_separator">/</li>
                    <li class="nobifashion_author_breadcrumb_item active" aria-current="page">
                        <span>{{ $fullName }}</span>
                    </li>
                </ol>
            </nav>

            {{-- Author Profile Hero Card (Minimalism, Sang trọng, Phân biệt Admin & Staff) --}}
            <header class="nobifashion_author_card {{ $isAdmin ? 'is-admin' : 'is-staff' }} {{ !empty($coverUrl) ? 'has-cover' : '' }}">
                @if (!empty($coverUrl))
                    <div class="nobifashion_author_cover_wrap">
                        <img src="{{ $coverUrl }}" 
                             alt="Ảnh bìa {{ $fullName }}" 
                             class="nobifashion_author_cover_img"
                             loading="eager"
                             onerror="this.parentElement.style.display='none';">
                    </div>
                @endif

                <div class="nobifashion_author_card_content">
                    <div class="nobifashion_author_card_inner">
                        <div class="nobifashion_author_avatar_wrap">
                            <img src="{{ $avatarUrl }}" 
                                 alt="{{ $fullName }}" 
                                 class="nobifashion_author_avatar"
                                 width="115" 
                                 height="115"
                                 loading="eager"
                                 onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($fullName) }}&background=0F172A&color=ffffff&bold=true&size=200';">
                            <span class="nobifashion_author_verified_badge" title="Tác giả được xác thực chính thức tại Nobi Fashion">
                                <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                </svg>
                            </span>
                        </div>

                        <div class="nobifashion_author_main_info">
                            <div class="nobifashion_author_badges">
                                <span class="nobifashion_author_badge {{ $isAdmin ? 'badge-admin' : 'badge-staff' }}">
                                    {{ $roleBadge }}
                                </span>
                                @if (!empty($location))
                                    <span class="nobifashion_author_location">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        {{ $location }}
                                    </span>
                                @endif
                            </div>

                            <h1 class="nobifashion_author_name">{{ $fullName }}</h1>
                            <div class="nobifashion_author_role_subtitle">{{ $roleTitle }}</div>

                            {{-- Content tiểu sử chuẩn SEO & E-E-A-T --}}
                            <div class="nobifashion_author_bio_content">
                                <p>{{ $bio }}</p>
                            </div>

                            {{-- Quote triết lý phong cách của tác giả --}}
                            @if (!empty($quote))
                                <div class="nobifashion_author_quote_box">
                                    <svg class="nobifashion_author_quote_icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                                        <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                                    </svg>
                                    <p class="nobifashion_author_quote_text">“{{ $quote }}”</p>
                                </div>
                            @endif

                            {{-- Chuyên môn & Lĩnh vực phụ trách --}}
                            <div class="nobifashion_author_topics">
                                @foreach ($expertiseTags as $tag)
                                    <span class="nobifashion_author_topic_tag">{{ $tag }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Author Stats Summary --}}
                    <div class="nobifashion_author_stats_bar">
                        <div class="nobifashion_author_stat_item">
                            <span class="nobifashion_author_stat_value">{{ number_format($totalPosts) }}</span>
                            <span class="nobifashion_author_stat_label">Bài viết đã xuất bản</span>
                        </div>
                        <div class="nobifashion_author_stat_divider"></div>
                        <div class="nobifashion_author_stat_item">
                            <span class="nobifashion_author_stat_value">{{ number_format($totalViews) }}</span>
                            <span class="nobifashion_author_stat_label">Lượt xem tích lũy</span>
                        </div>
                        <div class="nobifashion_author_stat_divider"></div>
                        <div class="nobifashion_author_stat_item">
                            <span class="nobifashion_author_stat_value">{{ count($categoryShowcase) }}</span>
                            <span class="nobifashion_author_stat_label">Chuyên mục phụ trách</span>
                        </div>
                        <div class="nobifashion_author_stat_divider"></div>
                        <div class="nobifashion_author_stat_item">
                            <span class="nobifashion_author_stat_value">100%</span>
                            <span class="nobifashion_author_stat_label">Được kiểm duyệt</span>
                        </div>
                    </div>
                </div>
            </header>

            {{-- 1. TOP VIEW POSTS: Bài viết nổi bật & Đọc nhiều nhất --}}
            @if ($topViewPosts->isNotEmpty())
                <section class="nobifashion_author_section nobifashion_author_top_views_section">
                    <div class="nobifashion_author_section_head">
                        <div class="nobifashion_author_section_title_wrap">
                            <span class="nobifashion_author_section_pill">NỔI BẬT</span>
                            <h2 class="nobifashion_author_section_title">
                                Bài viết đọc nhiều nhất
                            </h2>
                        </div>
                        <p class="nobifashion_author_section_subtitle">
                            Những bài viết thu hút đông đảo bạn đọc và có lượt quan tâm cao nhất của tác giả {{ $fullName }}.
                        </p>
                    </div>

                    <div class="nobifashion_author_top_grid">
                        @foreach ($topViewPosts as $index => $post)
                            @php
                                $thumbFile = $post->thumbnail ? basename($post->thumbnail) : 'no-image.webp';
                                $thumbUrl = asset('clients/assets/img/posts/' . $thumbFile);
                            @endphp
                            <article class="nobifashion_author_top_card">
                                <a href="{{ route('client.blog.show', $post->slug) }}" class="nobifashion_author_top_thumb_link" title="{{ $post->title }}">
                                    <div class="nobifashion_author_top_thumb_wrap">
                                        <img src="{{ $thumbUrl }}" 
                                             alt="{{ $post->thumbnail_alt_text ?? $post->title }}"
                                             class="nobifashion_author_top_thumb"
                                             width="380"
                                             height="215"
                                             loading="lazy"
                                             onerror="this.onerror=null; this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}';">
                                        
                                        <span class="nobifashion_author_top_rank_badge">#{{ $index + 1 }} Top View</span>
                                        
                                        @if ($post->category)
                                            <span class="nobifashion_author_post_cat_badge">
                                                {{ $post->category->name }}
                                            </span>
                                        @endif
                                    </div>
                                </a>

                                <div class="nobifashion_author_top_content">
                                    <div class="nobifashion_author_post_meta">
                                        <span class="nobifashion_author_post_date">
                                            {{ optional($post->published_at ?? $post->created_at)->format('d/m/Y') }}
                                        </span>
                                        <span class="nobifashion_author_post_dot">•</span>
                                        <span class="nobifashion_author_top_views_tag">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="12" height="12">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                            {{ number_format($post->views) }} lượt đọc
                                        </span>
                                    </div>

                                    <h3 class="nobifashion_author_post_title">
                                        <a href="{{ route('client.blog.show', $post->slug) }}" title="{{ $post->title }}">
                                            {{ renderMeta($post->title) }}
                                        </a>
                                    </h3>

                                    <p class="nobifashion_author_post_excerpt">
                                        {{ renderMeta(Str::limit($post->excerpt_text ?? strip_tags($post->content ?? ''), 110)) }}
                                    </p>

                                    <div class="nobifashion_author_post_footer">
                                        <a href="{{ route('client.blog.show', $post->slug) }}" class="nobifashion_author_post_readmore">
                                            <span>Khám phá ngay</span>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13">
                                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                                <polyline points="12 5 19 12 12 19"></polyline>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- 2. CATEGORY SHOWCASE: Phân bài viết view cao theo từng danh mục --}}
            @if (!empty($categoryShowcase))
                <section class="nobifashion_author_section nobifashion_author_categories_section">
                    <div class="nobifashion_author_section_head">
                        <div class="nobifashion_author_section_title_wrap">
                            <span class="nobifashion_author_section_pill">CHUYÊN ĐỀ</span>
                            <h2 class="nobifashion_author_section_title">
                                Tuyển tập bài viết theo chuyên mục
                            </h2>
                        </div>
                        <p class="nobifashion_author_section_subtitle">
                            Các bài viết được chọn lọc kỹ lưỡng, chia theo từng chủ đề thời trang ứng dụng thực tế.
                        </p>
                    </div>

                    @foreach ($categoryShowcase as $group)
                        <div class="nobifashion_author_cat_group">
                            <div class="nobifashion_author_cat_group_header">
                                <div class="nobifashion_author_cat_group_title">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                                        <polyline points="2 17 12 22 22 17"></polyline>
                                        <polyline points="2 12 12 17 22 12"></polyline>
                                    </svg>
                                    <h3>{{ $group['category']->name }}</h3>
                                    <span class="nobifashion_author_cat_count">({{ $group['total'] }} bài viết)</span>
                                </div>
                                <a href="{{ route('client.blog.category', $group['category']) }}" class="nobifashion_author_cat_group_link">
                                    Xem tất cả trong danh mục &rarr;
                                </a>
                            </div>

                            <div class="nobifashion_author_posts_grid">
                                @foreach ($group['posts'] as $post)
                                    @php
                                        $thumbFile = $post->thumbnail ? basename($post->thumbnail) : 'no-image.webp';
                                        $thumbUrl = asset('clients/assets/img/posts/' . $thumbFile);
                                    @endphp
                                    <article class="nobifashion_author_post_card">
                                        <a href="{{ route('client.blog.show', $post->slug) }}" class="nobifashion_author_post_thumb_link" title="{{ $post->title }}">
                                            <div class="nobifashion_author_post_thumb_wrap">
                                                <img src="{{ $thumbUrl }}" 
                                                     alt="{{ $post->thumbnail_alt_text ?? $post->title }}"
                                                     class="nobifashion_author_post_thumb"
                                                     width="380"
                                                     height="215"
                                                     loading="lazy"
                                                     decoding="async"
                                                     onerror="this.onerror=null; this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}';">
                                            </div>
                                        </a>

                                        <div class="nobifashion_author_post_content">
                                            <div class="nobifashion_author_post_meta">
                                                <span class="nobifashion_author_post_date">
                                                    {{ optional($post->published_at ?? $post->created_at)->format('d/m/Y') }}
                                                </span>
                                                <span class="nobifashion_author_post_dot">•</span>
                                                <span class="nobifashion_author_post_views">
                                                    {{ number_format($post->views) }} xem
                                                </span>
                                            </div>

                                            <h4 class="nobifashion_author_post_title">
                                                <a href="{{ route('client.blog.show', $post->slug) }}" title="{{ $post->title }}">
                                                    {{ renderMeta($post->title) }}
                                                </a>
                                            </h4>

                                            <p class="nobifashion_author_post_excerpt">
                                                {{ renderMeta(Str::limit($post->excerpt_text ?? strip_tags($post->content ?? ''), 105)) }}
                                            </p>

                                            <div class="nobifashion_author_post_footer">
                                                <a href="{{ route('client.blog.show', $post->slug) }}" class="nobifashion_author_post_readmore">
                                                    <span>Đọc bài</span>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="12" height="12">
                                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                                        <polyline points="12 5 19 12 12 19"></polyline>
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </section>
            @endif

            {{-- 3. EDITORIAL PRINCIPLES & STANDARDS (Cam kết chuẩn E-E-A-T) --}}
            <section class="nobifashion_author_principles_card">
                <div class="nobifashion_author_principles_head">
                    <span class="nobifashion_author_section_pill">TIÊU CHUẨN XUẤT BẢN</span>
                    <h3 class="nobifashion_author_principles_title">Tôn chỉ biên tập & Cam kết nội dung</h3>
                    <p class="nobifashion_author_principles_desc">
                        Tại Nobi Fashion, mọi nội dung của {{ $fullName }} ({{ $roleTitle }}) đều tuân thủ các nguyên tắc nghiêm ngặt:
                    </p>
                </div>
                <div class="nobifashion_author_principles_grid">
                    <div class="nobifashion_author_principle_item">
                        <div class="nobifashion_author_principle_icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <h4>Nội dung được biên tập & kiểm duyệt</h4>
                        <p>Các gợi ý phối đồ, đánh giá phom dáng và chất vải đều dựa trên quá trình mặc thử và kiểm chứng trực tiếp.</p>
                    </div>

                    <div class="nobifashion_author_principle_item">
                        <div class="nobifashion_author_principle_icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 14 14"></polyline>
                            </svg>
                        </div>
                        <h4>Cập nhật liên tục</h4>
                        <p>Bắt nhịp xu hướng thịnh hành thời trang theo từng mùa, không bỏ lỡ các trào lưu mới nhất của giới trẻ.</p>
                    </div>

                    <div class="nobifashion_author_principle_item">
                        <div class="nobifashion_author_principle_icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                        </div>
                        <h4>Khách quan & Trung thực</h4>
                        <p>Nêu rõ ưu và nhược điểm của từng kiểu trang phục, tôn trọng mọi vóc dáng và phong cách cá nhân.</p>
                    </div>
                </div>
            </section>

            {{-- 4. ALL POSTS SECTION: Toàn bộ bài viết đã xuất bản --}}
            <section class="nobifashion_author_section nobifashion_author_all_posts_section">
                <div class="nobifashion_author_posts_header">
                    <div class="nobifashion_author_section_title_wrap">
                        <h2 class="nobifashion_author_posts_title">
                            Tất cả bài viết đã xuất bản
                            <span class="nobifashion_author_posts_count">({{ $posts->total() }})</span>
                        </h2>
                    </div>
                </div>

                @if ($posts->isEmpty())
                    <div class="nobifashion_author_empty_state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="48" height="48">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <p>Tác giả hiện chưa có bài viết nào được xuất bản.</p>
                        <a href="{{ route('client.blog.index') }}" class="nobifashion_author_back_btn">Khám phá Blog Nobi Fashion</a>
                    </div>
                @else
                    <div class="nobifashion_author_posts_grid">
                        @foreach ($posts as $post)
                            @php
                                $thumbFile = $post->thumbnail ? basename($post->thumbnail) : 'no-image.webp';
                                $thumbUrl = asset('clients/assets/img/posts/' . $thumbFile);
                            @endphp
                            <article class="nobifashion_author_post_card">
                                <a href="{{ route('client.blog.show', $post->slug) }}" class="nobifashion_author_post_thumb_link" title="{{ $post->title }}">
                                    <div class="nobifashion_author_post_thumb_wrap">
                                        <img src="{{ $thumbUrl }}" 
                                             alt="{{ $post->thumbnail_alt_text ?? $post->title }}"
                                             class="nobifashion_author_post_thumb"
                                             width="380"
                                             height="215"
                                             loading="lazy"
                                             decoding="async"
                                             onerror="this.onerror=null; this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}';">
                                        @if ($post->category)
                                            <span class="nobifashion_author_post_cat_badge">
                                                {{ $post->category->name }}
                                            </span>
                                        @endif
                                    </div>
                                </a>

                                <div class="nobifashion_author_post_content">
                                    <div class="nobifashion_author_post_meta">
                                        <span class="nobifashion_author_post_date">
                                            {{ optional($post->published_at ?? $post->created_at)->format('d/m/Y') }}
                                        </span>
                                        <span class="nobifashion_author_post_dot">•</span>
                                        <span class="nobifashion_author_post_views">
                                            {{ number_format($post->views) }} xem
                                        </span>
                                    </div>

                                    <h3 class="nobifashion_author_post_title">
                                        <a href="{{ route('client.blog.show', $post->slug) }}" title="{{ $post->title }}">
                                            {{ renderMeta($post->title) }}
                                        </a>
                                    </h3>

                                    <p class="nobifashion_author_post_excerpt">
                                        {{ renderMeta(Str::limit($post->excerpt_text ?? strip_tags($post->content ?? ''), 115)) }}
                                    </p>

                                    <div class="nobifashion_author_post_footer">
                                        <a href="{{ route('client.blog.show', $post->slug) }}" class="nobifashion_author_post_readmore">
                                            <span>Đọc tiếp</span>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13">
                                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                                <polyline points="12 5 19 12 12 19"></polyline>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if ($posts->hasPages())
                        <div class="nobifashion_author_pagination_wrap">
                            {{ $posts->links('pagination.compact') }}
                        </div>
                    @endif
                @endif
            </section>
        </div>
    </div>
@endsection
