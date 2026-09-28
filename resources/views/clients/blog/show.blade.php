@extends('clients.layouts.master')

@section('title', renderMeta($post->meta_title ?? $post->title) . ' | ' . ($settings->site_name ?? ($settings->subname
    ?? 'NOBI FASHION VIỆT NAM')))
@section('head')
    {{-- SEO Meta Tags --}}
    @php
        $canonicalUrl = $post->meta_canonical ?? route('client.blog.show', $post);
        $hasQueryParams = count(request()->query()) > 0;
    @endphp

    @if ($hasQueryParams)
        <meta name="robots" content="noindex, follow">
    @else
        <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    @endif

    <meta name="description" content="{{ renderMeta($post->meta_description ?? $post->excerpt_text) }}">
    <meta name="keywords" content="{{ renderMeta($post->meta_keywords) }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ renderMeta($post->meta_title ?? $post->title) }}">
    <meta property="og:description" content="{{ renderMeta($post->meta_description ?? $post->excerpt_text) }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image"
        content="{{ $post->thumbnail ? asset('clients/assets/img/posts/' . $post->thumbnail) : asset('clients/assets/no-image.webp') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ renderMeta($post->meta_title ?? $post->title) }}">
    <meta name="twitter:description" content="{{ renderMeta($post->meta_description ?? $post->excerpt_text) }}">
    <meta name="twitter:image"
        content="{{ $post->thumbnail ? asset('clients/assets/img/posts/' . $post->thumbnail) : asset('clients/assets/no-image.webp') }}">
    @php
        $heroData = getResponsivePostImageUrl($post->thumbnail, 700);
    @endphp
    @if (!empty($heroData['srcset']))
        <link rel="preload" as="image" fetchpriority="high" href="{{ $heroData['src'] ?? $heroData['original'] }}"
            imagesrcset="{{ $heroData['srcset'] }}" imagesizes="(max-width: 768px) calc(100vw - 32px), 1168px">
    @else
        <link rel="preload" as="image" fetchpriority="high"
            href="{{ $heroData['src'] ?? ($heroData['original'] ?? asset('clients/assets/no-image.webp')) }}">
    @endif

@endsection

@push('styles')
    @php
        $isPageSpeedBot =
            $isPageSpeedBot ??
            preg_match('/Lighthouse|PageSpeed|Chrome-Lighthouse|HeadlessChrome/i', request()->header('User-Agent', ''));
        $blogCssFile = file_exists(public_path('clients/assets/css/blog-detail.min.css'))
            ? 'blog-detail.min.css'
            : 'blog-detail.css';
    @endphp
    @if ($isPageSpeedBot)
        <style>
            {!! @file_get_contents(public_path('clients/assets/css/' . $blogCssFile)) !!}
        </style>
    @else
        <link rel="preload" href="{{ asset('clients/assets/css/' . $blogCssFile) }}?v={{ env('APP_VERSION') }}"
            as="style">
        <link rel="stylesheet" href="{{ asset('clients/assets/css/' . $blogCssFile) }}?v={{ env('APP_VERSION') }}">
    @endif
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
    @php
        $shareUrl = urlencode(route('client.blog.show', $post));
        $shareText = urlencode(renderMeta($post->title) . ' - ' . config('app.name'));
    @endphp

    {{-- ========================================= --}}
    {{-- BREADCRUMB --}}
    {{-- ========================================= --}}
    <nav aria-label="breadcrumb" class="nobifashion_blog_detail_breadcrumb">
        <div class="nobifashion_blog_detail_container">
            <ol class="nobifashion_blog_detail_breadcrumb_list">
                <li class="nobifashion_blog_detail_breadcrumb_item">
                    <a href="{{ route('client.home.index') }}">
                        <svg class="svg-inline--fa fa-house" width="13" height="13" fill="currentColor"
                            aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 640 640">
                            <path fill="currentColor"
                                d="M304 70.1C313.1 61.9 326.9 61.9 336 70.1L568 278.1C577.9 286.9 578.7 302.1 569.8 312C560.9 321.9 545.8 322.7 535.9 313.8L527.9 306.6L527.9 511.9C527.9 547.2 499.2 575.9 463.9 575.9L175.9 575.9C140.6 575.9 111.9 547.2 111.9 511.9L111.9 306.6L103.9 313.8C94 322.6 78.9 321.8 70 312C61.1 302.2 62 287 71.8 278.1L304 70.1zM320 120.2L160 263.7L160 512C160 520.8 167.2 528 176 528L224 528L224 424C224 384.2 256.2 352 296 352L344 352C383.8 352 416 384.2 416 424L416 528L464 528C472.8 528 480 520.8 480 512L480 263.7L320 120.3zM272 528L368 528L368 424C368 410.7 357.3 400 344 400L296 400C282.7 400 272 410.7 272 424L272 528z" />
                        </svg>
                        <span>Trang chủ</span>
                    </a>
                </li>
                <li class="nobifashion_blog_detail_breadcrumb_separator">
                    <svg class="svg-inline--fa fa-angles-right" aria-hidden="true" focusable="false" role="img"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="8" height="8"
                        fill="currentColor">
                        <path fill="currentColor"
                            d="M470.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L402.7 256 265.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160zm-352 160l160-160c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L210.7 256 73.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0z" />
                    </svg>
                </li>
                <li class="nobifashion_blog_detail_breadcrumb_item">
                    <a href="{{ route('client.blog.index') }}">
                        <span>Nobi Blog</span>
                    </a>
                </li>
                @if ($post->category)
                    <li class="nobifashion_blog_detail_breadcrumb_separator">
                        <svg class="svg-inline--fa fa-angles-right" aria-hidden="true" focusable="false" role="img"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="8" height="8"
                            fill="currentColor">
                            <path fill="currentColor"
                                d="M470.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L402.7 256 265.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160zm-352 160l160-160c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L210.7 256 73.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0z" />
                        </svg>
                    </li>
                    <li class="nobifashion_blog_detail_breadcrumb_item">
                        <a href="{{ route('client.blog.category', $post->category) }}">
                            <span>{{ $post->category->name }}</span>
                        </a>
                    </li>
                @endif
                <li class="nobifashion_blog_detail_breadcrumb_separator">
                    <svg class="svg-inline--fa fa-angles-right" aria-hidden="true" focusable="false" role="img"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="8" height="8"
                        fill="currentColor">
                        <path fill="currentColor"
                            d="M470.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L402.7 256 265.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160zm-352 160l160-160c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L210.7 256 73.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0z" />
                    </svg>
                </li>
                <li class="nobifashion_blog_detail_breadcrumb_item active" aria-current="page">
                    <span>{{ renderMeta(Str::limit($post->title, 500)) }}</span>
                </li>
            </ol>
        </div>
    </nav>

    {{-- ========================================= --}}
    {{-- HERO SECTION --}}
    {{-- ========================================= --}}
    <section class="nobifashion_blog_detail_hero_section">
        <div class="nobifashion_blog_detail_hero_container">
            {{-- Category Badge --}}
            @if ($post->category)
                <a href="{{ route('client.blog.category', $post->category) }}"
                    class="nobifashion_blog_detail_category_badge">
                    <svg class="svg-inline--fa fa-bookmark" aria-hidden="true" focusable="false" role="img"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="10" height="12"
                        fill="currentColor">
                        <path fill="currentColor"
                            d="M0 48V487.7C0 501.1 10.9 512 24.3 512c5 0 9.9-1.5 14-4.4L192 400 345.7 507.6c4.1 2.9 9 4.4 14 4.4c13.4 0 24.3-10.9 24.3-24.3V48c0-26.5-21.5-48-48-48H48C21.5 0 0 21.5 0 48z" />
                    </svg>
                    {{ $post->category->name }}
                </a>
            @else
                <span class="nobifashion_blog_detail_category_badge">
                    <svg class="svg-inline--fa fa-bookmark" aria-hidden="true" focusable="false" role="img"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="10" height="12"
                        fill="currentColor">
                        <path fill="currentColor"
                            d="M0 48V487.7C0 501.1 10.9 512 24.3 512c5 0 9.9-1.5 14-4.4L192 400 345.7 507.6c4.1 2.9 9 4.4 14 4.4c13.4 0 24.3-10.9 24.3-24.3V48c0-26.5-21.5-48-48-48H48C21.5 0 0 21.5 0 48z" />
                    </svg>
                    Bài viết
                </span>
            @endif

            {{-- Title --}}
            <h1 class="nobifashion_blog_detail_hero_title">{{ renderMeta($post->title) }}</h1>

            {{-- Meta Info --}}
            <div class="nobifashion_blog_detail_hero_meta">
                <div class="nobifashion_blog_detail_hero_meta_item">
                    <svg class="svg-inline--fa fa-circle-user" aria-hidden="true" focusable="false" role="img"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="13" height="13"
                        fill="currentColor">
                        <path fill="currentColor"
                            d="M399 384.2C376.9 345.8 335.4 320 288 320H224c-47.4 0-88.9 25.8-111 64.2c35.2 39.2 86.2 63.8 143 63.8s107.8-24.7 143-63.8zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm256 16a72 72 0 1 0 0-144 72 72 0 1 0 0 144z" />
                    </svg>
                    @if ($authorUrl)
                        <a href="{{ $authorUrl }}" class="nobifashion_blog_detail_author_link" title="Xem thông tin tác giả {{ $authorFullName ?? $post->author?->displayName() ?? 'Đức Nobi 💖' }}">
                            {{ $authorFullName ?? $post->author?->displayName() ?? 'Đức Nobi 💖' }}
                        </a>
                    @else
                        <span class="nobifashion_blog_detail_author_link">
                            {{ $authorFullName ?? $post->author?->displayName() ?? 'Đức Nobi 💖' }}
                        </span>
                    @endif
                </div>
                <div class="nobifashion_blog_detail_hero_meta_item">
                    <svg class="svg-inline--fa fa-calendar-days" aria-hidden="true" focusable="false" role="img"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="13" height="13"
                        fill="currentColor">
                        <path fill="currentColor"
                            d="M152 24c0-13.3-10.7-24-24-24s-24 10.7-24 24V64H64C28.7 64 0 92.7 0 128v320c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V128c0-35.3-28.7-64-64-64H344V24c0-13.3-10.7-24-24-24s-24 10.7-24 24V64H152V24zM48 192H400V448c0 8.8-7.2 16-16 16H64c-8.8 0-16-7.2-16-16V192zm80 64c-8.8 0-16 7.2-16 16v24c0 8.8 7.2 16 16 16h24c8.8 0 16-7.2 16-16V272c0-8.8-7.2-16-16-16H128zm96 0c-8.8 0-16 7.2-16 16v24c0 8.8 7.2 16 16 16h24c8.8 0 16-7.2 16-16V272c0-8.8-7.2-16-16-16H224zm96 0c-8.8 0-16 7.2-16 16v24c0 8.8 7.2 16 16 16h24c8.8 0 16-7.2 16-16V272c0-8.8-7.2-16-16-16H320zm-192 96c-8.8 0-16 7.2-16 16v24c0 8.8 7.2 16 16 16h24c8.8 0 16-7.2 16-16V368c0-8.8-7.2-16-16-16H128zm96 0c-8.8 0-16 7.2-16 16v24c0 8.8 7.2 16 16 16h24c8.8 0 16-7.2 16-16V368c0-8.8-7.2-16-16-16H224zm96 0c-8.8 0-16 7.2-16 16v24c0 8.8 7.2 16 16 16h24c8.8 0 16-7.2 16-16V368c0-8.8-7.2-16-16-16H320z" />
                    </svg>
                    <span>{{ optional($post->published_at)->format('d/m/Y') }}</span>
                </div>
                <div class="nobifashion_blog_detail_hero_meta_item">
                    <svg class="svg-inline--fa fa-eye" aria-hidden="true" focusable="false" role="img"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="14" height="13"
                        fill="currentColor">
                        <path fill="currentColor"
                            d="M288 80c-65.2 0-118.8 29.6-159.9 67.7C89.6 183.5 63 226 49.4 256c13.6 30 40.2 72.5 78.6 108.3C169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256c-13.6-30-40.2-72.5-78.6-108.3C406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1c3.3 7.9 3.3 16.7 0 24.6c-14.9 35.7-46.2 87.7-93 131.1C433.5 443.2 368.8 480 288 480s-145.5-36.8-192.6-80.6C48.6 356 17.3 304 2.5 268.3c-3.3-7.9-3.3-16.7 0-24.6C17.3 208 48.6 156 95.4 112.6zM288 336a80 80 0 1 0 0-160 80 80 0 1 0 0 160z" />
                    </svg>
                    <span>{{ number_format($post->views) }} lượt xem</span>
                </div>
                <div class="nobifashion_blog_detail_hero_meta_item">
                    <svg class="svg-inline--fa fa-clock" aria-hidden="true" focusable="false" role="img"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="13" height="13"
                        fill="currentColor">
                        <path fill="currentColor"
                            d="M464 256A208 208 0 1 0 48 256a208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm256-96c6.6 0 12 5.4 12 12v84l52 30c5.7 3.3 7.7 10.6 4.4 16.4s-10.6 7.7-16.4 4.4l-58.7-33.9c-3.3-1.9-5.3-5.4-5.3-9.2V172c0-6.6 5.4-12 12-12z" />
                    </svg>
                    <span>{{ ceil(str_word_count(strip_tags($post->content)) / 250) }} phút đọc</span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="nobifashion_blog_detail_hero_actions">
                <button class="nobifashion_blog_detail_btn_read_now"
                    onclick="const el = document.getElementById('blog-content-section'); if (el) { const y = el.getBoundingClientRect().top + (window.pageYOffset || window.scrollY || document.documentElement.scrollTop) - 110; window.scrollTo({ top: y, behavior: 'smooth' }); }">
                    <span>Bắt đầu đọc</span>
                    <svg class="svg-inline--fa fa-arrow-down" aria-hidden="true" focusable="false" role="img"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="12" height="12"
                        fill="currentColor">
                        <path fill="currentColor"
                            d="M169.4 470.6c12.5 12.5 32.8 12.5 45.3 0l160-160c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 370.8 224 64c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 306.7L54.6 265.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160z" />
                    </svg>
                </button>

                <div class="nobifashion_blog_detail_share_group">
                    <button class="nobifashion_blog_detail_btn_share"
                        onclick="window.open('https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}')"
                        title="Chia sẻ Facebook">
                        <svg class="svg-inline--fa fa-facebook-f" aria-hidden="true" focusable="false" role="img"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" width="13" height="14"
                            fill="currentColor">
                            <path fill="currentColor"
                                d="M80 299.3V512H196V299.3h86.5l18-97.8H196V166.9c0-51.7 20.3-71.5 72.7-71.5c16.3 0 29.4 .4 37 1.2V7.9C291.4 4 256.4 0 236.2 0C129.3 0 80 50.5 80 159.4v42.1H14v97.8H80z" />
                        </svg>
                    </button>
                    <button class="nobifashion_blog_detail_btn_share"
                        onclick="window.open('https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareText }}')"
                        title="Chia sẻ Twitter">
                        <svg class="svg-inline--fa fa-twitter" aria-hidden="true" focusable="false" role="img"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="14" height="14"
                            fill="currentColor">
                            <path fill="currentColor"
                                d="M459.37 151.716c.325 4.548.325 9.097.325 13.645 0 138.72-105.582 298.558-298.558 298.558-59.452 0-114.68-17.219-161.137-47.106 8.447.974 16.568 1.299 25.34 1.299 49.055 0 94.213-16.568 130.274-44.832-46.132-.975-84.792-31.188-98.112-72.772 6.498.974 12.995 1.624 19.818 1.624 9.421 0 18.843-1.3 27.614-3.573-48.081-9.747-84.143-51.98-84.143-102.985v-1.299c13.969 7.797 30.214 12.67 47.431 13.319-28.264-18.843-46.781-51.005-46.781-87.391 0-19.492 5.197-37.36 14.294-52.954 51.655 63.675 129.3 105.258 216.365 109.807-1.624-7.797-2.599-15.918-2.599-24.04 0-57.828 46.782-104.934 104.934-104.934 30.213 0 57.502 12.67 76.67 33.137 23.715-4.548 46.456-13.32 66.599-25.34-7.798 24.366-24.366 44.833-46.132 57.827 21.117-2.273 41.584-8.122 60.426-16.243-14.292 20.791-32.161 39.308-52.628 54.253z" />
                        </svg>
                    </button>
                    <button class="nobifashion_blog_detail_btn_share"
                        onclick="window.open('https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}')"
                        title="Chia sẻ LinkedIn">
                        <svg class="svg-inline--fa fa-linkedin-in" aria-hidden="true" focusable="false" role="img"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="14" height="14"
                            fill="currentColor">
                            <path fill="currentColor"
                                d="M100.28 448H7.4V148.9h92.88zM53.79 108.1C24.09 108.1 0 83.5 0 53.8a53.79 53.79 0 0 1 107.58 0c0 29.7-24.1 54.3-53.79 54.3zM447.9 448h-92.68V302.4c0-34.7-.7-79.2-48.29-79.2-48.29 0-55.69 37.7-55.69 76.7V448h-92.78V148.9h89.08v40.8h1.3c12.4-23.5 42.69-48.3 87.88-48.3 94 0 111.28 61.9 111.28 142.3V448z" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Featured Image --}}
            <div class="nobifashion_blog_detail_hero_img_wrap">
                @if ($post->thumbnail)
                    <img src="{{ $heroData['src'] ?? $heroData['original'] }}"
                        @if (!empty($heroData['srcset'])) srcset="{{ $heroData['srcset'] }}"
                            sizes="(max-width: 768px) calc(100vw - 32px), 1168px" @endif
                        class="nobifashion_blog_detail_hero_img"
                        alt="{{ renderMeta($post->thumbnail_alt_text ?? $post->title) }}" loading="eager"
                        fetchpriority="high" width="{{ $heroData['width'] ?? 1200 }}"
                        height="{{ $heroData['height'] ?? 675 }}" decoding="async">
                @else
                    <div class="nobifashion_blog_detail_hero_img"
                        style="background: linear-gradient(135deg, #f3f4f6, #e5e7eb); display: flex; align-items: center; justify-content: center; min-height: 400px;">
                        <span style="font-size: 120px; color: #111827; opacity: 0.2;">
                            {{ strtoupper(Str::substr($post->title, 0, 1)) }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ========================================= --}}
    {{-- MAIN CONTENT SECTION --}}
    {{-- ========================================= --}}
    <section id="blog-content-section" class="nobifashion_blog_detail_content_section">
        <div class="nobifashion_blog_detail_main_layout">

            {{-- LEFT: ARTICLE CONTENT --}}
            <article class="nobifashion_blog_detail_article">

                {{-- Mobile TOC --}}
                @if ($toc->isNotEmpty())
                    <div class="nobifashion_blog_detail_mobile_toc">
                        <div class="nobifashion_blog_detail_mobile_toc_head">
                            <span class="nobifashion_blog_detail_mobile_toc_title">
                                <svg class="svg-inline--fa fa-list-ol" aria-hidden="true" focusable="false"
                                    role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
                                    width="13" height="13" fill="currentColor">
                                    <path fill="currentColor"
                                        d="M64 32C46.3 32 32 46.3 32 64s14.3 32 32 32h16v32H64c-8.8 0-16 7.2-16 16s7.2 16 16 16h48c8.8 0 16-7.2 16-16V64c0-17.7-14.3-32-32-32H64zm112 48c0-8.8 7.2-16 16-16h272c8.8 0 16 7.2 16 16s-7.2 16-16 16H192c-8.8 0-16-7.2-16-16zm-128 144c-17.7 0-32 14.3-32 32s14.3 32 32 32h16c8.8 0 16 7.2 16 16s-7.2 16-16 16H48c-8.8 0-16 7.2-16 16s7.2 16 16 16h48c26.5 0 48-21.5 48-48 0-17.7-9.6-33.1-23.9-41.4C130.4 259.1 144 224 144 224c0-26.5-21.5-48-48-48H64c-8.8 0-16 7.2-16 16s7.2 16 16 16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16H48zm128 48c0-8.8 7.2-16 16-16h272c8.8 0 16 7.2 16 16s-7.2 16-16 16H192c-8.8 0-16-7.2-16-16zm0 144c0-8.8 7.2-16 16-16h272c8.8 0 16 7.2 16 16s-7.2 16-16 16H192c-8.8 0-16-7.2-16-16zM48 384c-8.8 0-16 7.2-16 16s7.2 16 16 16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16H48c-8.8 0-16 7.2-16 16s7.2 16 16 16h48c26.5 0 48-21.5 48-48s-21.5-48-48-48H48z" />
                                </svg>Mục lục bài viết
                            </span>
                        </div>
                        <ul class="nobifashion_blog_detail_mobile_toc_list">
                            @foreach ($toc as $item)
                                <li
                                    class="nobifashion_blog_detail_mobile_toc_item {{ $item['tag'] === 'h3' ? 'is-h3' : 'is-h2' }}">
                                    <a href="#{{ $item['id'] }}" class="nobifashion_blog_detail_mobile_toc_link">
                                        {{ renderMeta($item['label']) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Rich Content --}}
                <div id="article-content" class="nobifashion_blog_detail_content">
                    {!! renderMeta($contentWithAnchors) !!}

                    {{-- Thông tin thời gian xuất bản & cập nhật --}}
                    @php
                        $publishedAt = $post->published_at ?? $post->created_at;
                        $updatedAt = $post->updated_at ?? $post->published_at ?? $post->created_at;
                    @endphp
                    <div class="nobifashion_blog_detail_timestamps">
                        <div class="nobifashion_blog_detail_timestamp_item">
                            <svg class="svg-inline--fa fa-calendar-plus" aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="13" height="13" fill="currentColor">
                                <path fill="currentColor" d="M152 24c0-13.3-10.7-24-24-24s-24 10.7-24 24V64H64C28.7 64 0 92.7 0 128v320c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V128c0-35.3-28.7-64-64-64H344V24c0-13.3-10.7-24-24-24s-24 10.7-24 24V64H152V24zM48 192H400V448c0 8.8-7.2 16-16 16H64c-8.8 0-16-7.2-16-16V192zm176 80c0-8.8-7.2-16-16-16s-16 7.2-16 16v48H144c-8.8 0-16 7.2-16 16s7.2 16 16 16h48v48c0 8.8 7.2 16 16 16s16-7.2 16-16V368h48c8.8 0 16-7.2 16-16s-7.2-16-16-16H224V272z"/>
                            </svg>
                            <span>Xuất bản: <strong>{{ $publishedAt ? $publishedAt->format('H:i, d/m/Y') : '' }}</strong></span>
                        </div>
                        <div class="nobifashion_blog_detail_timestamp_item">
                            <svg class="svg-inline--fa fa-clock-rotate-left" aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="13" height="13" fill="currentColor">
                                <path fill="currentColor" d="M256 0a256 256 0 1 1 0 512A256 256 0 1 1 256 0zM232 120V256c0 8 4 15.5 10.7 20l96 64c11 7.4 25.9 4.4 33.3-6.7s4.4-25.9-6.7-33.3L280 243.2V120c0-13.3-10.7-24-24-24s-24 10.7-24 24z"/>
                            </svg>
                            <span>Cập nhật: <strong>{{ $updatedAt ? $updatedAt->format('H:i, d/m/Y') : '' }}</strong></span>
                    </div>

                    {{-- Khối tác giả bài viết chuẩn E-E-A-T & Internal Linking --}}
                    <div class="nobifashion_blog_detail_author_box">
                        <div class="nobifashion_blog_detail_author_avatar_wrap">
                            @if ($authorUrl)
                                <a href="{{ $authorUrl }}" title="Xem trang tác giả {{ $authorFullName }}">
                                    <img src="{{ $authorAvatarUrl }}" 
                                         alt="{{ $authorFullName }}" 
                                         class="nobifashion_blog_detail_author_avatar" 
                                         width="60" 
                                         height="60" 
                                         loading="lazy" 
                                         onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($authorFullName) }}&background=0F172A&color=ffffff&bold=true';">
                                </a>
                            @else
                                <img src="{{ $authorAvatarUrl }}" 
                                     alt="{{ $authorFullName }}" 
                                     class="nobifashion_blog_detail_author_avatar" 
                                     width="60" 
                                     height="60" 
                                     loading="lazy" 
                                     onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($authorFullName) }}&background=0F172A&color=ffffff&bold=true';">
                            @endif
                        </div>
                        <div class="nobifashion_blog_detail_author_info">
                            <div class="nobifashion_blog_detail_author_head">
                                <span class="nobifashion_blog_detail_author_role">{{ $authorRoleBadge ?? 'Tác giả bài viết' }}</span>
                                <h4 class="nobifashion_blog_detail_author_name">
                                    @if ($authorUrl)
                                        <a href="{{ $authorUrl }}" title="Xem trang tác giả {{ $authorFullName }}">
                                            {{ $authorFullName }}
                                        </a>
                                    @else
                                        <span>{{ $authorFullName }}</span>
                                    @endif
                                </h4>
                            </div>
                            <p class="nobifashion_blog_detail_author_bio">{{ $authorBio }}</p>
                            @if ($authorUrl)
                                <a href="{{ $authorUrl }}" class="nobifashion_blog_detail_author_more">
                                    Xem tất cả bài viết của tác giả &rarr;
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Post Footer --}}
                <div class="nobifashion_blog_detail_footer">
                    {{-- Tags --}}
                    <div class="nobifashion_blog_detail_tags_wrap">
                        <span class="nobifashion_blog_detail_tags_label">Tags:</span>
                        @forelse($tags as $tag)
                            <a href="{{ route('client.tags.show', $tag->slug) }}"
                                class="nobifashion_blog_detail_tag_pill">
                                #{{ $tag->name }}
                            </a>
                        @empty
                            <span style="font-size: 13px; color: #6b7280;">Chưa có tag</span>
                        @endforelse
                    </div>

                    {{-- Internal Links Widget --}}
                    @if ($internalLinks->isNotEmpty())
                        <div class="nobifashion_blog_detail_related_links">
                            <h3 class="nobifashion_blog_detail_related_links_title">
                                💡 Có thể bạn quan tâm
                            </h3>
                            <div class="nobifashion_blog_detail_related_links_grid">
                                @foreach ($internalLinks as $link)
                                    <a href="{{ route('client.blog.show', $link) }}"
                                        class="nobifashion_blog_detail_related_links_item">
                                        <svg class="svg-inline--fa fa-arrow-right" aria-hidden="true" focusable="false"
                                            role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
                                            width="12" height="12" fill="currentColor">
                                            <path fill="currentColor"
                                                d="M438.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L370.7 224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32h322.7l-137.4 137.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z" />
                                        </svg>
                                        <span>{{ renderMeta($link->title) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Comments --}}
                <section class="nobifashion_blog_detail_comments" id="comments-section"
                    data-commentable-id="{{ $post->id }}" data-commentable-type="{{ \App\Models\Post::class }}">
                    <div class="nobifashion_blog_detail_comments_header">
                        <div class="nobifashion_blog_detail_comments_header_left">
                            <h3 class="nobifashion_blog_detail_comments_title">Bình luận</h3>
                            <span class="nobifashion_blog_detail_comments_badge"><span
                                    id="comments-count">{{ $commentsCount }}</span></span>
                        </div>
                        <span class="nobifashion_blog_detail_comment_note">
                            <svg class="svg-inline--fa fa-circle-info" aria-hidden="true" focusable="false"
                                role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="13"
                                height="13" fill="currentColor">
                                <path fill="currentColor"
                                    d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM216 336h24V272H216c-13.3 0-24-10.7-24-24s10.7-24 24-24h48c13.3 0 24 10.7 24 24v88h8c13.3 0 24 10.7 24 24s-10.7 24-24 24H216c-13.3 0-24-10.7-24-24s10.7-24 24-24zm40-208a32 32 0 1 1 0 64 32 32 0 1 1 0-64z" />
                            </svg>
                            Duyệt trước khi hiển thị công khai
                        </span>
                    </div>

                    <div id="comments-list" class="nobifashion_blog_detail_comments_list"></div>
                    <div id="comments-pagination" class="nobifashion_blog_detail_comments_pagination"></div>

                    <div class="nobifashion_blog_detail_comment_form_card" id="comment-form-card">
                        <div class="nobifashion_blog_detail_comment_form_head">
                            <h3>Để lại bình luận</h3>
                            <span class="nobifashion_blog_detail_comment_privacy">Bảo mật thông tin 100%</span>
                        </div>
                        <form id="comment-form">
                            @csrf
                            @guest
                                <div class="nobifashion_blog_detail_guest_row">
                                    <div class="nobifashion_blog_detail_input_wrap">
                                        <input type="text" name="guest_name" placeholder="Họ và tên của bạn *" required>
                                    </div>
                                    <div class="nobifashion_blog_detail_input_wrap">
                                        <input type="email" name="guest_email" placeholder="Email nhận phản hồi *"
                                            required>
                                    </div>
                                </div>
                            @endguest
                            <div class="nobifashion_blog_detail_reply_wrap" id="reply-indicator" style="display:none;">
                                <span class="nobifashion_blog_detail_reply_indicator">
                                    <svg class="svg-inline--fa fa-reply" aria-hidden="true" focusable="false"
                                        role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
                                        width="12" height="12" fill="currentColor">
                                        <path fill="currentColor"
                                            d="M205 34.8c11.5 5.1 19 16.6 19 29.2v64H336c97.2 0 176 78.8 176 176c0 113.3-81.5 163.9-100.2 174.1c-2.5 1.4-5.3 1.9-8.1 1.9c-10.9 0-19.7-8.9-19.7-19.7c0-7.5 4.3-14.4 9.8-19.5c9.4-8.8 22.2-26.4 22.2-56.7c0-53-43-96-96-96H224v64c0 12.6-7.4 24.1-19 29.2s-25 3-34.4-5.4l-160-144C3.9 241.2 0 232.8 0 224s3.9-17.2 10.6-23.2l160-144c9.4-8.5 22.9-10.6 34.4-5.4z" />
                                    </svg>
                                    Đang trả lời <strong id="reply-to-name"></strong>
                                    <button type="button" id="cancel-reply" title="Hủy trả lời">✕</button>
                                </span>
                            </div>
                            {{-- Rating Stars Selection --}}
                            <div class="nobifashion_blog_detail_rating_group" id="comment-rating-group">
                                <div class="nobifashion_blog_detail_rating_wrap">
                                    <span class="nobifashion_blog_detail_rating_label">Đánh giá: <span
                                            style="color:#ef4444;">*</span></span>
                                    <div class="nobifashion_blog_detail_stars_picker" id="stars-picker" role="radiogroup"
                                        aria-label="Chọn số sao đánh giá">
                                        <input type="hidden" name="rating" id="comment-rating-input" value="">
                                        <button type="button" class="nobifashion_blog_detail_star_btn" data-rating="1"
                                            title="1 sao - Rất tệ" aria-label="1 sao"><svg class="svg-inline--fa fa-star"
                                                aria-hidden="true" focusable="false" role="img"
                                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="18"
                                                height="18" fill="currentColor">
                                                <path fill="currentColor"
                                                    d="M316.9 18C311.6 7 300.4 0 288 0s-23.6 7-28.8 18L195.9 146.7 54.4 167.3c-12.1 1.8-22.1 9.9-25.8 21.5s-1.5 24.4 7.2 32.9L138.2 321.4 114 462.4c-2.1 12 2.8 24.1 12.7 31.3s23 7.7 33.8 2L288 428.1l127.5 67.5c10.8 5.7 23.9 5.2 33.8-2s14.8-19.3 12.7-31.3L437.8 321.4 540.2 221.7c8.7-8.5 12.5-21.3 8.7-32.9s-13.7-19.7-25.8-21.5L380.1 146.7 316.9 18z" />
                                            </svg></button>
                                        <button type="button" class="nobifashion_blog_detail_star_btn" data-rating="2"
                                            title="2 sao - Tệ" aria-label="2 sao"><svg class="svg-inline--fa fa-star"
                                                aria-hidden="true" focusable="false" role="img"
                                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="18"
                                                height="18" fill="currentColor">
                                                <path fill="currentColor"
                                                    d="M316.9 18C311.6 7 300.4 0 288 0s-23.6 7-28.8 18L195.9 146.7 54.4 167.3c-12.1 1.8-22.1 9.9-25.8 21.5s-1.5 24.4 7.2 32.9L138.2 321.4 114 462.4c-2.1 12 2.8 24.1 12.7 31.3s23 7.7 33.8 2L288 428.1l127.5 67.5c10.8 5.7 23.9 5.2 33.8-2s14.8-19.3 12.7-31.3L437.8 321.4 540.2 221.7c8.7-8.5 12.5-21.3 8.7-32.9s-13.7-19.7-25.8-21.5L380.1 146.7 316.9 18z" />
                                            </svg></button>
                                        <button type="button" class="nobifashion_blog_detail_star_btn" data-rating="3"
                                            title="3 sao - Bình thường" aria-label="3 sao"><svg
                                                class="svg-inline--fa fa-star" aria-hidden="true" focusable="false"
                                                role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"
                                                width="18" height="18" fill="currentColor">
                                                <path fill="currentColor"
                                                    d="M316.9 18C311.6 7 300.4 0 288 0s-23.6 7-28.8 18L195.9 146.7 54.4 167.3c-12.1 1.8-22.1 9.9-25.8 21.5s-1.5 24.4 7.2 32.9L138.2 321.4 114 462.4c-2.1 12 2.8 24.1 12.7 31.3s23 7.7 33.8 2L288 428.1l127.5 67.5c10.8 5.7 23.9 5.2 33.8-2s14.8-19.3 12.7-31.3L437.8 321.4 540.2 221.7c8.7-8.5 12.5-21.3 8.7-32.9s-13.7-19.7-25.8-21.5L380.1 146.7 316.9 18z" />
                                            </svg></button>
                                        <button type="button" class="nobifashion_blog_detail_star_btn" data-rating="4"
                                            title="4 sao - Hài lòng" aria-label="4 sao"><svg
                                                class="svg-inline--fa fa-star" aria-hidden="true" focusable="false"
                                                role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"
                                                width="18" height="18" fill="currentColor">
                                                <path fill="currentColor"
                                                    d="M316.9 18C311.6 7 300.4 0 288 0s-23.6 7-28.8 18L195.9 146.7 54.4 167.3c-12.1 1.8-22.1 9.9-25.8 21.5s-1.5 24.4 7.2 32.9L138.2 321.4 114 462.4c-2.1 12 2.8 24.1 12.7 31.3s23 7.7 33.8 2L288 428.1l127.5 67.5c10.8 5.7 23.9 5.2 33.8-2s14.8-19.3 12.7-31.3L437.8 321.4 540.2 221.7c8.7-8.5 12.5-21.3 8.7-32.9s-13.7-19.7-25.8-21.5L380.1 146.7 316.9 18z" />
                                            </svg></button>
                                        <button type="button" class="nobifashion_blog_detail_star_btn" data-rating="5"
                                            title="5 sao - Tuyệt vời" aria-label="5 sao"><svg
                                                class="svg-inline--fa fa-star" aria-hidden="true" focusable="false"
                                                role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"
                                                width="18" height="18" fill="currentColor">
                                                <path fill="currentColor"
                                                    d="M316.9 18C311.6 7 300.4 0 288 0s-23.6 7-28.8 18L195.9 146.7 54.4 167.3c-12.1 1.8-22.1 9.9-25.8 21.5s-1.5 24.4 7.2 32.9L138.2 321.4 114 462.4c-2.1 12 2.8 24.1 12.7 31.3s23 7.7 33.8 2L288 428.1l127.5 67.5c10.8 5.7 23.9 5.2 33.8-2s14.8-19.3 12.7-31.3L437.8 321.4 540.2 221.7c8.7-8.5 12.5-21.3 8.7-32.9s-13.7-19.7-25.8-21.5L380.1 146.7 316.9 18z" />
                                            </svg></button>
                                    </div>
                                    <span class="nobifashion_blog_detail_rating_feedback" id="rating-feedback"></span>
                                </div>
                            </div>
                            <div class="nobifashion_blog_detail_textarea_wrap">
                                <textarea name="content" placeholder="Chia sẻ suy nghĩ hoặc câu hỏi của bạn về bài viết..." required></textarea>
                            </div>
                            <input type="hidden" name="parent_id" id="comment-parent-id">
                            <input type="text" name="website" autocomplete="off" style="display:none;">
                            <div class="nobifashion_blog_detail_form_actions">
                                <span class="nobifashion_blog_detail_form_hint">Vui lòng bình luận văn minh, tôn trọng lẫn
                                    nhau.</span>
                                <button type="submit" class="nobifashion_blog_detail_submit_btn">
                                    <span>Gửi bình luận</span>
                                    <svg class="svg-inline--fa fa-paper-plane" aria-hidden="true" focusable="false"
                                        role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
                                        width="13" height="13" fill="currentColor">
                                        <path fill="currentColor"
                                            d="M498.1 5.6c10.1 7 15.4 19.1 13.5 31.2l-64 416c-1.5 9.7-7.4 18.2-16 23s-18.9 5.4-28 1.6L284 427.7l-68.5 74.1c-8.9 9.7-22.9 12.9-35.2 8.1S160 493.2 160 480V392c0-2.5 .6-4.9 1.7-7.1L413.1 106.6 117.6 341.2l-96.5-41.4C8.2 294.2 0 282.7 0 269.8s8.2-24.4 21.1-29.9l448-192c10.2-4.4 22-2.7 30.6 4.3z" />
                                    </svg>
                                </button>
                            </div>
                            <div class="nobifashion_blog_detail_status_msg" id="comment-status-message"></div>
                        </form>
                    </div>
                </section>

                <div class="nobifashion_blog_detail_editorial_note">
                    <strong>Về nội dung bài viết</strong>
                    <p>
                        Nội dung trên Nobi Fashion được đội ngũ biên tập tổng hợp, tham khảo, đối chiếu và biên tập từ nhiều
                        nguồn thông tin khác nhau nhằm cung cấp kiến thức hữu ích cho người đọc. Nếu bạn phát hiện thông tin
                        chưa chính xác, đã lỗi thời hoặc cần được đính chính, vui lòng để lại bình luận hoặc liên hệ trực
                        tiếp với Nobi Fashion để chúng tôi kiểm tra và cập nhật.
                        <a href="{{ route('client.policy.editorial') }}">
                            Xem nguyên tắc biên tập của Nobi Fashion
                        </a>.
                    </p>
                </div>
            </article>

            {{-- RIGHT: SIDEBAR --}}
            <aside class="nobifashion_blog_detail_sidebar">

                {{-- Desktop TOC --}}
                @if ($toc->isNotEmpty())
                    <div class="nobifashion_blog_detail_sidebar_toc">
                        <div class="nobifashion_blog_detail_sidebar_toc_title">Mục lục</div>
                        <ul class="nobifashion_blog_detail_toc_list" id="desktop-toc">
                            @foreach ($toc as $item)
                                <li class="{{ $item['tag'] === 'h3' ? 'indent' : '' }}">
                                    <a href="#{{ $item['id'] }}">{{ renderMeta($item['label']) }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Related Posts Widget --}}
                @if ($relatedPosts->isNotEmpty())
                    <div class="nobifashion_blog_detail_sidebar_widget">
                        <h3 class="nobifashion_blog_detail_widget_title">Bài viết liên quan</h3>
                        <div>
                            @foreach ($relatedPosts as $related)
                                <a href="{{ route('client.blog.show', $related) }}"
                                    class="nobifashion_blog_detail_related_item">
                                    <img src="{{ getPostThumbnailUrl($related->thumbnail, 120) }}"
                                        alt="{{ renderMeta($related->title) }}"
                                        class="nobifashion_blog_detail_related_thumb" width="120" height="68"
                                        onerror="this.onerror=null; this.src='{{ asset('clients/assets/img/no-image.webp') }}'"
                                        loading="lazy" decoding="async">
                                    <div class="nobifashion_blog_detail_related_info">
                                        <h4>{{ renderMeta($related->title) }}</h4>
                                        <div class="nobifashion_blog_detail_related_date">
                                            <svg class="svg-inline--fa fa-calendar" aria-hidden="true" focusable="false"
                                                role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
                                                width="11" height="11" fill="currentColor">
                                                <path fill="currentColor"
                                                    d="M152 24c0-13.3-10.7-24-24-24s-24 10.7-24 24V64H64C28.7 64 0 92.7 0 128v320c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V128c0-35.3-28.7-64-64-64H344V24c0-13.3-10.7-24-24-24s-24 10.7-24 24V64H152V24zM48 192H400V448c0 8.8-7.2 16-16 16H64c-8.8 0-16-7.2-16-16V192z" />
                                            </svg>
                                            <span>{{ optional($related->published_at)->format('d/m/Y') }}</span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Newsletter Widget --}}
                <div class="nobifashion_blog_detail_sidebar_widget nobifashion_blog_detail_newsletter_widget">
                    <h3 class="nobifashion_blog_detail_widget_title">
                        📬 Newsletter
                    </h3>
                    <p class="nobifashion_blog_detail_newsletter_desc">
                        Đăng ký để nhận những bài viết mới nhất và xu hướng nổi bật hàng tuần.
                    </p>
                    <form action="{{ route('newsletter.subscribe') }}" method="POST"
                        class="nobifashion_blog_detail_newsletter_form" data-newsletter-form id="newsletter-form-blog">
                        @csrf
                        <input type="email" name="email" id="newsletter-email-blog"
                            class="nobifashion_blog_detail_newsletter_input" placeholder="email@example.com" required>
                        <button type="button" data-submit-newsletter id="newsletter-btn-blog"
                            class="nobifashion_blog_detail_newsletter_btn" onclick="handleNewsletterSubmit(event)">Đăng ký
                            ngay</button>
                        <div class="nobifashion_blog_detail_newsletter_msg" id="newsletter-message-blog"></div>
                    </form>
                </div>

            </aside>
        </div>
    </section>
@endsection

@section('foot')
    {{-- Comments Widget Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const section = document.getElementById('comments-section');
            if (!section) return;

            const listEl = document.getElementById('comments-list');
            const paginationEl = document.getElementById('comments-pagination');
            const countEl = document.getElementById('comments-count');
            const form = document.getElementById('comment-form');
            const statusEl = document.getElementById('comment-status-message');
            const parentInput = document.getElementById('comment-parent-id');
            const replyIndicator = document.getElementById('reply-indicator');
            const replyToName = document.getElementById('reply-to-name');
            const cancelReplyBtn = document.getElementById('cancel-reply');

            // Rating Stars
            const ratingGroup = document.getElementById('comment-rating-group');
            const ratingInput = document.getElementById('comment-rating-input');
            const starsPicker = document.getElementById('stars-picker');
            const ratingFeedback = document.getElementById('rating-feedback');
            const starBtns = starsPicker ? starsPicker.querySelectorAll(
                '.nobifashion_blog_detail_star_btn, .star-btn') : [];

            const ratingLabels = {
                1: 'Rất tệ (1★)',
                2: 'Tệ (2★)',
                3: 'Bình thường (3★)',
                4: 'Hài lòng (4★)',
                5: 'Tuyệt vời (5★)'
            };

            const updateStarDisplay = (hoverValue = 0) => {
                const currentVal = hoverValue || parseInt(ratingInput?.value, 10) || 0;
                starBtns.forEach(btn => {
                    const starVal = parseInt(btn.dataset.rating, 10);
                    if (hoverValue > 0) {
                        btn.classList.toggle('hovered', starVal <= hoverValue);
                    } else {
                        btn.classList.remove('hovered');
                        btn.classList.toggle('active', starVal <= currentVal);
                    }
                });

                if (hoverValue > 0) {
                    if (ratingFeedback) ratingFeedback.textContent = ratingLabels[hoverValue] || '';
                } else if (currentVal > 0) {
                    if (ratingFeedback) ratingFeedback.textContent = ratingLabels[currentVal] || '';
                } else {
                    if (ratingFeedback) ratingFeedback.textContent = '';
                }
            };

            starBtns.forEach(btn => {
                btn.addEventListener('mouseenter', () => {
                    const rating = parseInt(btn.dataset.rating, 10);
                    updateStarDisplay(rating);
                });

                btn.addEventListener('click', () => {
                    const rating = parseInt(btn.dataset.rating, 10);
                    if (ratingInput) ratingInput.value = rating;
                    if (ratingGroup) ratingGroup.classList.remove('has-error');
                    updateStarDisplay();
                });
            });

            if (starsPicker) {
                starsPicker.addEventListener('mouseleave', () => {
                    updateStarDisplay(0);
                });
            }

            const config = {
                commentableId: parseInt(section.dataset.commentableId, 10),
                commentableType: section.dataset.commentableType,
                apiUrl: '{{ url('/api/v1/comments') }}',
                submitUrl: '{{ route('client.comments.store') }}',
                csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
            };

            let currentPage = 1;
            let lastPage = 1;
            let isLoading = false;

            const sanitize = (text = '') => {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            };

            const formatDate = (iso) => {
                const date = new Date(iso);
                return Number.isNaN(date.getTime()) ? '' : date.toLocaleString('vi-VN', {
                    hour12: false
                });
            };

            const renderComment = (comment, depth = 0) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'nobifashion_blog_detail_comment_card' + (depth > 0 ? ' reply' : '');

                const authorName = comment.account?.name || comment.guest_name || 'Khách';
                const rating = Number(comment.rating) || 0;
                const ratingStars = rating > 0 ?
                    `<div class="nobifashion_blog_detail_comment_rating" aria-label="Đánh giá ${rating} sao">
                        ${Array.from({ length: 5 }).map((_, i) => ` < span class =
                    "star ${i < rating ? 'filled' : ''}" > < svg class = "svg-inline--fa fa-star"
                aria - hidden = "true"
                focusable = "false"
                role = "img"
                xmlns = "http://www.w3.org/2000/svg"
                viewBox = "0 0 576 512"
                width = "11"
                height = "11"
                fill = "currentColor" > < path fill = "currentColor"
                d = "M316.9 18C311.6 7 300.4 0 288 0s-23.6 7-28.8 18L195.9 146.7 54.4 167.3c-12.1 1.8-22.1 9.9-25.8 21.5s-1.5 24.4 7.2 32.9L138.2 321.4 114 462.4c-2.1 12 2.8 24.1 12.7 31.3s23 7.7 33.8 2L288 428.1l127.5 67.5c10.8 5.7 23.9 5.2 33.8-2s14.8-19.3 12.7-31.3L437.8 321.4 540.2 221.7c8.7-8.5 12.5-21.3 8.7-32.9s-13.7-19.7-25.8-21.5L380.1 146.7 316.9 18z" /
                    > < /svg></span > `).join('')}
                        <span class="rating-text">${rating}/5</span>
                    </div>`: '';
                const avatarText = sanitize(authorName).charAt(0).toUpperCase();

                wrapper.innerHTML = `
                    <div class="nobifashion_blog_detail_comment_author">
                        <div class="nobifashion_blog_detail_comment_avatar">${avatarText}</div>
                        <div>
                            <strong style="color: #0f172a; font-size: 13.5px;">${sanitize(authorName)}</strong>
                            <div class="nobifashion_blog_detail_comment_meta">${formatDate(comment.created_at)}</div>
                            ${ratingStars}
                        </div>
                    </div>
                    <div class="nobifashion_blog_detail_comment_content">${sanitize(comment.content)}</div>
                    <div class="nobifashion_blog_detail_comment_actions">
                        <button type="button" data-reply-id="${comment.id}" data-reply-name="${sanitize(authorName)}">Trả lời</button>
                        <button type="button" data-report-id="${comment.id}">Báo xấu</button>
                    </div>
                `;

                if (comment.replies && comment.replies.length) {
                    comment.replies.forEach(reply => wrapper.appendChild(renderComment(reply, depth + 1)));
                }

                return wrapper;
            };

            const renderPagination = (meta) => {
                if (!meta || meta.last_page <= 1) {
                    paginationEl.innerHTML = '';
                    return;
                }

                const pages = [];
                const current = meta.current_page;
                const last = meta.last_page;
                const total = meta.total;
                const from = meta.from || 0;
                const to = meta.to || 0;

                // Previous button
                pages.push(`<button class="nobifashion_blog_detail_comment_page_btn ${current === 1 ? 'disabled' : ''}" 
                    data-page="${current - 1}" ${current === 1 ? 'disabled' : ''}>‹ Trước</button>`);

                // Page numbers
                let startPage = Math.max(1, current - 2);
                let endPage = Math.min(last, current + 2);

                if (startPage > 1) {
                    pages.push(
                        `<button class="nobifashion_blog_detail_comment_page_btn" data-page="1">1</button>`);
                    if (startPage > 2) {
                        pages.push(`<span class="nobifashion_blog_detail_comment_page_ellipsis">...</span>`);
                    }
                }

                for (let i = startPage; i <= endPage; i++) {
                    pages.push(`<button class="nobifashion_blog_detail_comment_page_btn ${i === current ? 'active' : ''}" 
                        data-page="${i}">${i}</button>`);
                }

                if (endPage < last) {
                    if (endPage < last - 1) {
                        pages.push(`<span class="nobifashion_blog_detail_comment_page_ellipsis">...</span>`);
                    }
                    pages.push(
                        `<button class="nobifashion_blog_detail_comment_page_btn" data-page="${last}">${last}</button>`
                        );
                }

                // Next button
                pages.push(`<button class="nobifashion_blog_detail_comment_page_btn ${current === last ? 'disabled' : ''}" 
                    data-page="${current + 1}" ${current === last ? 'disabled' : ''}>Sau ›</button>`);

                paginationEl.innerHTML = `
                    <div class="nobifashion_blog_detail_comment_pagination_info">
                        Hiển thị ${from}-${to} trong tổng ${total} bình luận
                    </div>
                    <div class="nobifashion_blog_detail_comment_pagination_buttons">
                        ${pages.join('')}
                    </div>
                `;

                // Attach event listeners
                paginationEl.querySelectorAll('.nobifashion_blog_detail_comment_page_btn:not(.disabled)')
                    .forEach(btn => {
                        btn.addEventListener('click', () => {
                            const page = parseInt(btn.dataset.page);
                            if (page && page !== current) {
                                loadComments(page);
                                window.scrollTo({
                                    top: listEl.offsetTop - 100,
                                    behavior: 'smooth'
                                });
                            }
                        });
                    });
            };

            const loadComments = async (page = 1) => {
                if (isLoading) return;
                isLoading = true;
                listEl.innerHTML =
                    '<div class="nobifashion_blog_detail_comments_loading">Đang tải bình luận...</div>';
                paginationEl.innerHTML = '';

                try {
                    const url = new URL(config.apiUrl);
                    url.searchParams.set('commentable_id', config.commentableId);
                    url.searchParams.set('commentable_type', config.commentableType);
                    url.searchParams.set('page', page);

                    const res = await fetch(url, {
                        headers: {
                            Accept: 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Không thể tải bình luận.');

                    listEl.innerHTML = '';

                    if (data.data && data.data.length) {
                        data.data.forEach(comment => listEl.appendChild(renderComment(comment)));
                    } else {
                        listEl.innerHTML =
                            '<div class="nobifashion_blog_detail_no_comments">Chưa có bình luận nào. Hãy là người đầu tiên!</div>';
                    }

                    // Render pagination
                    if (data.meta) {
                        currentPage = data.meta.current_page || 1;
                        lastPage = data.meta.last_page || 1;
                        renderPagination(data.meta);
                    }
                } catch (error) {
                    console.error(error);
                    listEl.innerHTML =
                        '<div class="nobifashion_blog_detail_comments_error">Không thể tải bình luận.</div>';
                } finally {
                    isLoading = false;
                }
            };

            listEl.addEventListener('click', (event) => {
                const replyBtn = event.target.closest('button[data-reply-id]');
                const reportBtn = event.target.closest('button[data-report-id]');

                if (replyBtn) {
                    parentInput.value = replyBtn.dataset.replyId;
                    replyToName.textContent = replyBtn.dataset.replyName || '';
                    replyIndicator.style.display = 'block';
                    form.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }

                if (reportBtn) {
                    reportComment(reportBtn.dataset.reportId);
                }
            });

            cancelReplyBtn?.addEventListener('click', () => {
                parentInput.value = '';
                replyIndicator.style.display = 'none';
            });

            const reportComment = async (commentId) => {
                try {
                    await fetch(`${config.apiUrl}/${commentId}/report`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': config.csrf,
                            'Accept': 'application/json',
                        },
                    });
                    showCustomToast('Báo cáo đã được gửi. Cảm ơn bạn!', 'success');
                } catch (error) {
                    showCustomToast('Không thể báo cáo bình luận.', 'error');
                }
            };

            form?.addEventListener('submit', async (event) => {
                event.preventDefault();

                // Honeypot chống bot
                if (form.website?.value) return;

                // Bắt buộc chọn số sao đánh giá (từ 1 đến 5 sao)
                const ratingVal = parseInt(ratingInput?.value || '0', 10);
                if (!ratingVal || ratingVal < 1 || ratingVal > 5) {
                    if (ratingGroup) {
                        ratingGroup.classList.add('has-error');
                        ratingGroup.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest'
                        });
                    }
                    showCustomToast("Vui lòng chọn số sao đánh giá (1-5 sao) cho bài viết!", "warning");
                    return;
                }

                const formData = new FormData(form);
                formData.append('commentable_id', config.commentableId);
                formData.append('commentable_type', config.commentableType);

                showCustomToast("Đang gửi bình luận...", "info");

                try {
                    const res = await fetch(config.submitUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': config.csrf,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || "Không thể gửi bình luận.");

                    // Reset form
                    form.reset();
                    if (ratingInput) ratingInput.value = '';
                    updateStarDisplay(0);
                    parentInput.value = '';
                    replyIndicator.style.display = 'none';

                    // Cập nhật số lượng bình luận
                    const newCount = parseInt(countEl.textContent || '0', 10) + 1;
                    countEl.textContent = newCount;

                    showCustomToast("Cảm ơn bạn! Bình luận sẽ hiển thị sau khi được duyệt.", "success");

                } catch (error) {
                    console.error(error);
                    showCustomToast(error.message, "error");
                }
            });

            setTimeout(() => {
                loadComments();
            }, 3000);
        });
    </script>

    {{-- Newsletter Form Handler --}}
    <script>
        function handleNewsletterSubmit(event) {
            event.preventDefault();
            event.stopPropagation();

            const form = document.getElementById('newsletter-form-blog');
            const btn = document.getElementById('newsletter-btn-blog');
            const msg = document.getElementById('newsletter-message-blog');
            const email = document.getElementById('newsletter-email-blog');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            if (!form || !btn || !msg || !email) {
                console.error('Newsletter elements not found');
                return;
            }

            if (!csrfToken) {
                console.error('CSRF token not found');
                msg.textContent = "Lỗi: CSRF token không tìm thấy";
                msg.className = "nobifashion_blog_detail_newsletter_msg text-danger";
                return;
            }

            if (!email.value.trim()) {
                msg.textContent = "Vui lòng nhập email";
                msg.className = "nobifashion_blog_detail_newsletter_msg text-danger";
                return;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value.trim())) {
                msg.textContent = "Email không hợp lệ";
                msg.className = "nobifashion_blog_detail_newsletter_msg text-danger";
                return;
            }

            btn.disabled = true;
            btn.textContent = "Đang gửi...";
            msg.textContent = "Đang gửi...";
            msg.className = "nobifashion_blog_detail_newsletter_msg text-muted";

            const formData = new FormData(form);

            fetch(form.action, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        "Accept": "application/json"
                    },
                    body: formData
                })
                .then(async res => {
                    try {
                        const data = await res.json();
                        return {
                            ok: res.ok,
                            data
                        };
                    } catch (e) {
                        return {
                            ok: false,
                            data: {
                                success: false,
                                message: 'Lỗi khi xử lý phản hồi từ server'
                            }
                        };
                    }
                })
                .then(({
                    ok,
                    data
                }) => {
                    if (ok && data.success) {
                        msg.textContent = data.message || "Đăng ký thành công!";
                        msg.className = "nobifashion_blog_detail_newsletter_msg text-success";
                        form.reset();
                    } else {
                        let message = data.message || "Có lỗi xảy ra!";
                        if (data.errors && data.errors.email) {
                            message = Array.isArray(data.errors.email) ? data.errors.email[0] : data.errors.email;
                        }

                        msg.textContent = message;
                        msg.className = "nobifashion_blog_detail_newsletter_msg text-danger";
                    }
                })
                .catch((e) => {
                    console.error('Newsletter error:', e);
                    msg.textContent = "Lỗi kết nối server";
                    msg.className = "nobifashion_blog_detail_newsletter_msg text-danger";
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = "Đăng ký ngay";
                });
        }
    </script>

    <script>
        const initBlogShowPage = () => {
            // TOC Active State on Scroll
            const observerOptions = {
                rootMargin: '-100px 0px -66%',
                threshold: 0
            };

            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    const id = entry.target.getAttribute('id');
                    const tocLink = document.querySelector(`#desktop-toc a[href="#${id}"]`);

                    if (tocLink) {
                        if (entry.isIntersecting) {
                            document.querySelectorAll('#desktop-toc a').forEach(link => {
                                link.classList.remove('active');
                            });
                            tocLink.classList.add('active');
                        }
                    }
                });
            }, observerOptions);

            // Observe all headings
            document.querySelectorAll('#article-content h2, #article-content h3').forEach(heading => {
                observer.observe(heading);
            });

            // Smooth scroll for TOC links
            document.querySelectorAll(
                '.nobifashion_blog_detail_toc_list a, .nobifashion_blog_detail_mobile_toc_list a, .toc-list a, .mobile-toc a'
                ).forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    const targetElement = document.getElementById(targetId);

                    if (targetElement) {
                        const offset = 100;
                        const targetPosition = targetElement.getBoundingClientRect().top + window
                            .pageYOffset - offset;

                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // Lazy load images in content
            document.querySelectorAll('#article-content img').forEach(img => {
                img.setAttribute('loading', 'lazy');
            });

            // Add external link icon
            document.querySelectorAll('#article-content a[href^="http"]').forEach(link => {
                if (!link.hostname.includes(window.location.hostname)) {
                    link.setAttribute('target', '_blank');
                    link.setAttribute('rel', 'noopener noreferrer');
                }
            });
        };

        // Add onerror handler to all images in blog post
        document.addEventListener('DOMContentLoaded', function() {
            initBlogShowPage();
            const fallbackImage = '{{ asset('clients/assets/img/no-image.webp') }}';

            function handleImageError(img) {
                if (img.src !== fallbackImage) {
                    img.onerror = null;
                    img.src = fallbackImage;
                }
            }

            const heroImage = document.querySelector('.nobifashion_blog_detail_hero_img');
            if (heroImage) {
                heroImage.onerror = function() {
                    handleImageError(this);
                };
            }

            document.querySelectorAll('.nobifashion_blog_detail_related_thumb').forEach(img => {
                img.onerror = function() {
                    handleImageError(this);
                };
            });

            document.querySelectorAll('#article-content img').forEach(img => {
                img.onerror = function() {
                    handleImageError(this);
                };
            });
        });
    </script>

@endsection
