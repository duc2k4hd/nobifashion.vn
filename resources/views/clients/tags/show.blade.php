@extends('clients.layouts.master')

@section('title', $seoTitle)

@section('head')
    {{-- SEO Meta Tags --}}
    <meta name="robots" content="noindex, follow">
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="keywords" content="{{ $seoKeywords }}">
    <link rel="canonical" href="{{ route('client.tags.show', $tag->slug) }}">
    
    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ route('client.tags.show', $tag->slug) }}">
    
    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/tag.css') }}?v={{ env('APP_VERSION', '1.0') }}">
@endpush

@section('schema')
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ItemList",
        "name": "{{ $tag->name }}",
        "description": "{{ $seoDescription }}",
        "itemListElement": [
            @foreach($items as $index => $item)
            {
                "@type": "ListItem",
                "position": {{ $index + 1 }},
                "item": {
                    "@type": "{{ $tag->entity_type === \App\Models\Product::class || $tag->entity_type === 'product' ? 'Product' : 'Article' }}",
                    "name": "{{ $item->name ?? $item->title }}",
                    "url": "{{ ($tag->entity_type === \App\Models\Product::class || $tag->entity_type === 'product') ? route('client.product.detail', ['slug' => $item->slug ?? $item->id]) : route('client.blog.show', ['slug' => $item->slug ?? $item->id]) }}"
                }
            }{{ !$loop->last ? ',' : '' }}
            @endforeach
        ]
    }
    </script>
@endsection

@section('content')
    @php
        $isProduct = ($tag->entity_type === \App\Models\Product::class || $tag->entity_type === 'product');
    @endphp

    <div class="nobifashion_tag_page">
        <div class="nobifashion_tag_container">
            {{-- Breadcrumb --}}
            <nav aria-label="breadcrumb" class="nobifashion_tag_breadcrumb">
                <ol class="nobifashion_tag_breadcrumb_list">
                    <li class="nobifashion_tag_breadcrumb_item">
                        <a href="{{ route('client.home.index') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            <span>Trang chủ</span>
                        </a>
                    </li>
                    <li class="nobifashion_tag_breadcrumb_separator">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </li>
                    @if($isProduct)
                        <li class="nobifashion_tag_breadcrumb_item">
                            <a href="{{ route('client.product.shop.index') }}">Cửa hàng</a>
                        </li>
                    @else
                        <li class="nobifashion_tag_breadcrumb_item">
                            <a href="{{ route('client.blog.index') }}">Nobi Blog</a>
                        </li>
                    @endif
                    <li class="nobifashion_tag_breadcrumb_separator">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </li>
                    <li class="nobifashion_tag_breadcrumb_item active" aria-current="page">
                        <span>Tag: {{ $tag->name }}</span>
                    </li>
                </ol>
            </nav>

            {{-- Tag Header Banner --}}
            <header class="nobifashion_tag_header">
                <div class="nobifashion_tag_header_badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="9" x2="20" y2="9"></line>
                        <line x1="4" y1="15" x2="20" y2="15"></line>
                        <line x1="10" y1="3" x2="8" y2="21"></line>
                        <line x1="16" y1="3" x2="14" y2="21"></line>
                    </svg>
                    <span>Thẻ Tag</span>
                </div>
                <h1 class="nobifashion_tag_header_title">Tag: {{ $tag->name }}</h1>
                @if($tag->description)
                    <p class="nobifashion_tag_header_desc">{{ $tag->description }}</p>
                @endif
                <div class="nobifashion_tag_header_meta">
                    <span>Tìm thấy <strong>{{ $items->total() }}</strong> {{ $isProduct ? 'sản phẩm' : 'bài viết' }} liên quan</span>
                </div>
            </header>

            {{-- Items List --}}
            @if($items->count() > 0)
                @if($isProduct)
                    {{-- Product Grid --}}
                    <div class="nobifashion_tag_product_grid">
                        @foreach($items as $item)
                            <article class="nobifashion_tag_product_card">
                                @if($item->sale_price && $item->sale_price < $item->price)
                                    @php
                                        $percent = round((($item->price - $item->sale_price) / $item->price) * 100);
                                    @endphp
                                    <span class="nobifashion_tag_product_discount">-{{ $percent }}%</span>
                                @endif

                                <a href="{{ route('client.product.detail', ['slug' => $item->slug ?? $item->id]) }}" 
                                   class="nobifashion_tag_product_thumb"
                                   title="{{ $item->name }}">
                                    <img src="{{ asset('clients/assets/img/clothes/' . ($item->primaryImage?->url ?? 'no-image.webp')) }}" 
                                         alt="{{ $item->primaryImage?->alt ?? $item->name }}"
                                         title="{{ $item->primaryImage?->title ?? $item->name }}"
                                         loading="lazy"
                                         onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}';">
                                </a>

                                <div class="nobifashion_tag_product_info">
                                    <span class="nobifashion_tag_product_category">
                                        {{ $item->primaryCategory?->name ?? 'Nobi Fashion' }}
                                    </span>
                                    <h2 class="nobifashion_tag_product_name">
                                        <a href="{{ route('client.product.detail', ['slug' => $item->slug ?? $item->id]) }}"
                                           title="{{ $item->name }}">
                                            {{ $item->name }}
                                        </a>
                                    </h2>

                                    <div class="nobifashion_tag_product_rating">
                                        <span class="nobifashion_tag_product_stars">
                                            @for($i = 0; $i < 5; $i++)
                                                <svg viewBox="0 0 24 24">
                                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                                </svg>
                                            @endfor
                                        </span>
                                        <span class="nobifashion_tag_product_review_count">(5.0)</span>
                                    </div>

                                    <div class="nobifashion_tag_product_bottom">
                                        <div class="nobifashion_tag_product_pricing">
                                            @if($item->sale_price && $item->sale_price < $item->price)
                                                <span class="nobifashion_tag_product_price_sale">{{ number_format($item->sale_price, 0, ',', '.') }}đ</span>
                                                <span class="nobifashion_tag_product_price_original">{{ number_format($item->price, 0, ',', '.') }}đ</span>
                                            @else
                                                <span class="nobifashion_tag_product_price_sale">{{ number_format($item->price, 0, ',', '.') }}đ</span>
                                            @endif
                                        </div>
                                        <a href="{{ route('client.product.detail', ['slug' => $item->slug ?? $item->id]) }}" 
                                           class="nobifashion_tag_product_btn">
                                            Xem chi tiết
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    {{-- Post Grid --}}
                    <div class="nobifashion_tag_post_grid">
                        @foreach($items as $item)
                            @php
                                $thumbName = !empty($item->thumbnail) ? basename(parse_url($item->thumbnail, PHP_URL_PATH) ?? $item->thumbnail) : null;
                                $fallbackThumb = asset('clients/assets/img/no-image.webp');
                                $thumbUrl = $thumbName ? asset('clients/assets/img/posts/' . $thumbName) : $fallbackThumb;
                            @endphp
                            <article class="nobifashion_tag_post_card">
                                <a href="{{ route('client.blog.show', ['slug' => $item->slug ?? $item->id]) }}" 
                                   class="nobifashion_tag_post_thumb"
                                   title="{{ renderMeta($item->title) }}">
                                    <img src="{{ $thumbUrl }}" 
                                         alt="{{ renderMeta($item->title) }}"
                                         loading="lazy"
                                         onerror="this.onerror=null;this.src='{{ $fallbackThumb }}';">
                                </a>

                                <div class="nobifashion_tag_post_info">
                                    <div class="nobifashion_tag_post_meta">
                                        @if($item->category)
                                            <span class="nobifashion_tag_post_meta_category">
                                                {{ $item->category->name }}
                                            </span>
                                        @endif
                                        <span class="nobifashion_tag_post_date">
                                            {{ $item->published_at ? $item->published_at->format('d/m/Y') : $item->created_at->format('d/m/Y') }}
                                        </span>
                                    </div>

                                    <h2 class="nobifashion_tag_post_title">
                                        <a href="{{ route('client.blog.show', ['slug' => $item->slug ?? $item->id]) }}"
                                           title="{{ renderMeta($item->title) }}">
                                            {{ renderMeta($item->title) }}
                                        </a>
                                    </h2>

                                    <p class="nobifashion_tag_post_excerpt">
                                        {{ renderMeta(Str::limit($item->excerpt_text ?? strip_tags($item->excerpt ?? $item->content ?? ''), 120)) }}
                                    </p>

                                    <div class="nobifashion_tag_post_footer">
                                        <span class="nobifashion_tag_post_author">
                                            @php
                                                $authorItemUrl = \App\Http\Controllers\Clients\AuthorController::getAuthorUrl($item->author);
                                            @endphp
                                            @if ($authorItemUrl)
                                                <a href="{{ $authorItemUrl }}" title="{{ $item->author?->profile?->full_name ?? $item->author?->displayName() ?? 'Đức Nobi 💖' }}" style="color: inherit; text-decoration: none;">
                                                    {{ $item->author?->profile?->full_name ?? $item->author?->displayName() ?? 'Đức Nobi 💖' }}
                                                </a>
                                            @else
                                                {{ $item->author?->profile?->full_name ?? $item->author?->displayName() ?? 'Đức Nobi 💖' }}
                                            @endif
                                        </span>
                                        <a href="{{ route('client.blog.show', ['slug' => $item->slug ?? $item->id]) }}" 
                                           class="nobifashion_tag_post_readmore">
                                            Đọc tiếp
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                                <polyline points="12 5 19 12 12 19"></polyline>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif

                {{-- Pagination --}}
                @if($items->hasPages())
                    <div class="nobifashion_tag_pagination">
                        {{ $items->onEachSide(1)->links('pagination.blog') }}
                    </div>
                @endif
            @else
                {{-- Empty State --}}
                <div class="nobifashion_tag_empty">
                    <div class="nobifashion_tag_empty_icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                    <h3 class="nobifashion_tag_empty_title">Chưa có nội dung nào</h3>
                    <p class="nobifashion_tag_empty_text">Hiện chưa có {{ $isProduct ? 'sản phẩm' : 'bài viết' }} nào được gắn thẻ tag này.</p>
                    <a href="{{ $isProduct ? route('client.product.shop.index') : route('client.blog.index') }}" 
                       class="nobifashion_tag_empty_btn">
                        {{ $isProduct ? 'Khám phá cửa hàng' : 'Xem tin tức Blog' }}
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection


