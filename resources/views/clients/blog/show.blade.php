@extends('clients.layouts.master')

@section('title', renderMeta($post->meta_title ?? $post->title) . ' | ' . ($settings->site_name ?? ($settings->subname
    ?? 'NOBI FASHION VIỆT NAM')))
@section('head')
    {{-- SEO Meta Tags --}}
    <meta name="description" content="{{ renderMeta($post->meta_description ?? $post->excerpt_text) }}">
    <meta name="keywords" content="{{ renderMeta($post->meta_keywords) }}">
    <link rel="canonical" href="{{ $post->meta_canonical ?? route('client.blog.show', $post) }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ renderMeta($post->meta_title ?? $post->title) }}">
    <meta property="og:description" content="{{ renderMeta($post->meta_description ?? $post->excerpt_text) }}">
    <meta property="og:url" content="{{ route('client.blog.show', $post) }}">
    <meta property="og:image"
        content="{{ $post->thumbnail ? asset('clients/assets/img/posts/' . $post->thumbnail) : asset('clients/assets/no-image.webp') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ renderMeta($post->meta_title ?? $post->title) }}">
    <meta name="twitter:description" content="{{ renderMeta($post->meta_description ?? $post->excerpt_text) }}">
    <meta name="twitter:image"
        content="{{ $post->thumbnail ? asset('clients/assets/img/posts/' . $post->thumbnail) : asset('clients/assets/no-image.webp') }}">
    <link rel="preload" as="image" fetchpriority="high"
        href="{{ $post->thumbnail ? asset('clients/assets/img/posts/' . $post->thumbnail) : asset('clients/assets/no-image.webp') }}"
        imagesizes="(max-width: 768px) 100vw, 1200px">
    {{-- Preconnect to External Resources --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Crimson+Pro:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" media="print" onload="this.media='all'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    </noscript>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/blog-detail.css?v=' . env('APP_VERSION')) }}">
@endpush

@section('schema')
    @if (isset($schemaData) && is_array($schemaData))
        @foreach ($schemaData as $schema)
            <script type="application/ld+json">
                {!! json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) !!}
            </script>
        @endforeach
    @endif

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
            const starBtns = starsPicker ? starsPicker.querySelectorAll('.nobifashion_blog_detail_star_btn, .star-btn') : [];

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
                        ${Array.from({ length: 5 }).map((_, i) => `<span class="star ${i < rating ? 'filled' : ''}"><i class="fa-solid fa-star"></i></span>`).join('')}
                        <span class="rating-text">${rating}/5</span>
                    </div>` : '';
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
                    pages.push(`<button class="nobifashion_blog_detail_comment_page_btn" data-page="1">1</button>`);
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
                    pages.push(`<button class="nobifashion_blog_detail_comment_page_btn" data-page="${last}">${last}</button>`);
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
                paginationEl.querySelectorAll('.nobifashion_blog_detail_comment_page_btn:not(.disabled)').forEach(btn => {
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
                listEl.innerHTML = '<div class="nobifashion_blog_detail_comments_loading">Đang tải bình luận...</div>';
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
                        <i class="fas fa-home"></i>
                        <span>Trang chủ</span>
                    </a>
                </li>
                <li class="nobifashion_blog_detail_breadcrumb_separator">
                    <i class="fa-solid fa-angles-right"></i>
                </li>
                <li class="nobifashion_blog_detail_breadcrumb_item">
                    <a href="{{ route('client.blog.index') }}">
                        <span>Nobi Blog</span>
                    </a>
                </li>
                @if ($post->category)
                    <li class="nobifashion_blog_detail_breadcrumb_separator">
                        <i class="fa-solid fa-angles-right"></i>
                    </li>
                    <li class="nobifashion_blog_detail_breadcrumb_item">
                        <a href="{{ route('client.blog.category', $post->category) }}">
                            <span>{{ $post->category->name }}</span>
                        </a>
                    </li>
                @endif
                <li class="nobifashion_blog_detail_breadcrumb_separator">
                    <i class="fa-solid fa-angles-right"></i>
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
                <a href="{{ route('client.blog.category', $post->category) }}" class="nobifashion_blog_detail_category_badge">
                    <i class="fas fa-bookmark"></i>
                    {{ $post->category->name }}
                </a>
            @else
                <span class="nobifashion_blog_detail_category_badge">
                    <i class="fas fa-bookmark"></i>
                    Bài viết
                </span>
            @endif

            {{-- Title --}}
            <h1 class="nobifashion_blog_detail_hero_title">{{ renderMeta($post->title) }}</h1>

            {{-- Meta Info --}}
            <div class="nobifashion_blog_detail_hero_meta">
                <div class="nobifashion_blog_detail_hero_meta_item">
                    <i class="fas fa-user-circle"></i>
                    <span>{{ $post->author?->displayName() ?? 'Team Nobi Fashion' }}</span>
                </div>
                <div class="nobifashion_blog_detail_hero_meta_item">
                    <i class="far fa-calendar-alt"></i>
                    <span>{{ optional($post->published_at)->format('d/m/Y') }}</span>
                </div>
                <div class="nobifashion_blog_detail_hero_meta_item">
                    <i class="far fa-eye"></i>
                    <span>{{ number_format($post->views) }} lượt xem</span>
                </div>
                <div class="nobifashion_blog_detail_hero_meta_item">
                    <i class="far fa-clock"></i>
                    <span>{{ ceil(str_word_count(strip_tags($post->content)) / 250) }} phút đọc</span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="nobifashion_blog_detail_hero_actions">
                <button class="nobifashion_blog_detail_btn_read_now"
                    onclick="const el = document.getElementById('blog-content-section'); if (el) { const y = el.getBoundingClientRect().top + (window.pageYOffset || window.scrollY || document.documentElement.scrollTop) - 110; window.scrollTo({ top: y, behavior: 'smooth' }); }">
                    <span>Bắt đầu đọc</span>
                    <i class="fas fa-arrow-down"></i>
                </button>

                <div class="nobifashion_blog_detail_share_group">
                    <button class="nobifashion_blog_detail_btn_share"
                        onclick="window.open('https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}')"
                        title="Chia sẻ Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </button>
                    <button class="nobifashion_blog_detail_btn_share"
                        onclick="window.open('https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareText }}')"
                        title="Chia sẻ Twitter">
                        <i class="fab fa-twitter"></i>
                    </button>
                    <button class="nobifashion_blog_detail_btn_share"
                        onclick="window.open('https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}')"
                        title="Chia sẻ LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </button>
                </div>
            </div>

            {{-- Featured Image --}}
            <div class="nobifashion_blog_detail_hero_img_wrap">
                @if ($post->thumbnail)
                    <img src="{{ asset('clients/assets/img/posts/' . $post->thumbnail) }}" class="nobifashion_blog_detail_hero_img"
                        alt="{{ renderMeta($post->thumbnail_alt_text ?? $post->title) }}" loading="eager" fetchpriority="high"
                        width="1200" height="675" decoding="async"
                        sizes="(max-width: 768px) 100vw, 1200px">
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
                                <i class="fas fa-list-ol"></i>Mục lục bài viết
                            </span>
                        </div>
                        <ul class="nobifashion_blog_detail_mobile_toc_list">
                            @foreach ($toc as $item)
                                <li class="nobifashion_blog_detail_mobile_toc_item {{ $item['tag'] === 'h3' ? 'is-h3' : 'is-h2' }}">
                                    <a href="#{{ $item['id'] }}" class="nobifashion_blog_detail_mobile_toc_link">
                                        {{ renderMeta($item['label']) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Rich Content --}}
                <div id="article-content" class="nobifashion_blog_detail_content nobifashion_blog_detail_article">
                    {!! renderMeta($contentWithAnchors) !!}
                </div>

                {{-- Post Footer --}}
                <div class="nobifashion_blog_detail_footer">
                    {{-- Tags --}}
                    <div class="nobifashion_blog_detail_tags_wrap">
                        <span class="nobifashion_blog_detail_tags_label">Tags:</span>
                        @forelse($tags as $tag)
                            <a href="{{ route('client.blog.index', ['tag' => $tag->slug]) }}" class="nobifashion_blog_detail_tag_pill">
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
                                    <a href="{{ route('client.blog.show', $link) }}" class="nobifashion_blog_detail_related_links_item">
                                        <i class="fa-solid fa-arrow-right"></i>
                                        <span>{{ renderMeta($link->title) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Comments --}}
                <section class="nobifashion_blog_detail_comments" id="comments-section" data-commentable-id="{{ $post->id }}"
                    data-commentable-type="{{ \App\Models\Post::class }}">
                    <div class="nobifashion_blog_detail_comments_header">
                        <div class="nobifashion_blog_detail_comments_header_left">
                            <h3 class="nobifashion_blog_detail_comments_title">Bình luận</h3>
                            <span class="nobifashion_blog_detail_comments_badge"><span id="comments-count">{{ $commentsCount }}</span></span>
                        </div>
                        <span class="nobifashion_blog_detail_comment_note">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
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
                                        <input type="email" name="guest_email" placeholder="Email nhận phản hồi *" required>
                                    </div>
                                </div>
                            @endguest
                            <div class="nobifashion_blog_detail_reply_wrap" id="reply-indicator" style="display:none;">
                                <span class="nobifashion_blog_detail_reply_indicator">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 14 4 9 9 4"></polyline><path d="M20 20v-7a4 4 0 0 0-4-4H4"></path></svg>
                                    Đang trả lời <strong id="reply-to-name"></strong>
                                    <button type="button" id="cancel-reply" title="Hủy trả lời">✕</button>
                                </span>
                            </div>
                            {{-- Rating Stars Selection --}}
                            <div class="nobifashion_blog_detail_rating_group" id="comment-rating-group">
                                <div class="nobifashion_blog_detail_rating_wrap">
                                    <span class="nobifashion_blog_detail_rating_label">Đánh giá: <span style="color:#ef4444;">*</span></span>
                                    <div class="nobifashion_blog_detail_stars_picker" id="stars-picker" role="radiogroup" aria-label="Chọn số sao đánh giá">
                                        <input type="hidden" name="rating" id="comment-rating-input" value="">
                                        <button type="button" class="nobifashion_blog_detail_star_btn" data-rating="1" title="1 sao - Rất tệ" aria-label="1 sao"><i class="fa-solid fa-star"></i></button>
                                        <button type="button" class="nobifashion_blog_detail_star_btn" data-rating="2" title="2 sao - Tệ" aria-label="2 sao"><i class="fa-solid fa-star"></i></button>
                                        <button type="button" class="nobifashion_blog_detail_star_btn" data-rating="3" title="3 sao - Bình thường" aria-label="3 sao"><i class="fa-solid fa-star"></i></button>
                                        <button type="button" class="nobifashion_blog_detail_star_btn" data-rating="4" title="4 sao - Hài lòng" aria-label="4 sao"><i class="fa-solid fa-star"></i></button>
                                        <button type="button" class="nobifashion_blog_detail_star_btn" data-rating="5" title="5 sao - Tuyệt vời" aria-label="5 sao"><i class="fa-solid fa-star"></i></button>
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
                                <span class="nobifashion_blog_detail_form_hint">Vui lòng bình luận văn minh, tôn trọng lẫn nhau.</span>
                                <button type="submit" class="nobifashion_blog_detail_submit_btn">
                                    <span>Gửi bình luận</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
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
                                <a href="{{ route('client.blog.show', $related) }}" class="nobifashion_blog_detail_related_item">
                                    <img src="{{ $related->thumbnail ? asset('clients/assets/img/posts/' . $related->thumbnail) : asset('clients/assets/img/clothes/no-image.webp') }}"
                                        alt="{{ renderMeta($related->title) }}" class="nobifashion_blog_detail_related_thumb"
                                        width="80" height="80"
                                        onerror="this.onerror=null; this.src='{{ asset('clients/assets/img/no-image.webp') }}'"
                                        loading="lazy">
                                    <div class="nobifashion_blog_detail_related_info">
                                        <h4>{{ renderMeta($related->title) }}</h4>
                                        <div class="nobifashion_blog_detail_related_date">
                                            <i class="far fa-calendar"></i>
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
                    <form action="{{ route('newsletter.subscribe') }}" method="POST" class="nobifashion_blog_detail_newsletter_form"
                        data-newsletter-form id="newsletter-form-blog">
                        @csrf
                        <input type="email" name="email" id="newsletter-email-blog" class="nobifashion_blog_detail_newsletter_input" placeholder="email@example.com"
                            required>
                        <button type="button" data-submit-newsletter id="newsletter-btn-blog" class="nobifashion_blog_detail_newsletter_btn"
                            onclick="handleNewsletterSubmit(event)">Đăng ký ngay</button>
                        <div class="nobifashion_blog_detail_newsletter_msg" id="newsletter-message-blog"></div>
                    </form>
                </div>

            </aside>
        </div>
    </section>
@endsection

@section('foot')
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
            document.querySelectorAll('.nobifashion_blog_detail_toc_list a, .nobifashion_blog_detail_mobile_toc_list a, .toc-list a, .mobile-toc a').forEach(link => {
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
            const fallbackImage = '{{ asset('clients/assets/img/clothes/no-image.webp') }}';

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

    {{-- FontAwesome Icons (deferred to eliminate bandwidth congestion on slow 4G) --}}
    <script>
        window.addEventListener('load', function() {
            var fa = document.createElement('link');
            fa.rel = 'stylesheet';
            fa.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
            fa.integrity = 'sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==';
            fa.crossOrigin = 'anonymous';
            fa.referrerPolicy = 'no-referrer';
            document.head.appendChild(fa);
        });
    </script>
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
            integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
            crossorigin="anonymous" referrerpolicy="no-referrer" />
    </noscript>
@endsection
