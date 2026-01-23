@extends('clients.layouts.master')

@section('title', $seoTitle)

@section('head')
    {{-- SEO Meta Tags --}}
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
                    "url": "{{ $tag->entity_type === \App\Models\Product::class || $tag->entity_type === 'product' ? route('client.product.show', $item->slug ?? $item->id) : route('client.blog.show', $item->slug ?? $item->id) }}"
                }
            }{{ !$loop->last ? ',' : '' }}
            @endforeach
        ]
    }
    </script>
@endsection

@section('content')
    <div class="container py-5">
        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('client.home.index') }}">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('client.blog.index') }}">Blog</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tag: {{ $tag->name }}</li>
            </ol>
        </nav>

        {{-- Header --}}
        <div class="mb-4">
            <h1 class="h2 mb-2">Tag: {{ $tag->name }}</h1>
            @if($tag->description)
                <p class="text-muted">{{ $tag->description }}</p>
            @endif
            <p class="small text-muted">
                Tìm thấy <strong>{{ $items->total() }}</strong> 
                {{ $tag->entity_type === \App\Models\Product::class || $tag->entity_type === 'product' ? 'sản phẩm' : 'bài viết' }}
            </p>
        </div>

        {{-- Items List --}}
        @if($items->count() > 0)
            <div class="row">
                @foreach($items as $item)
                    <div class="col-md-4 mb-4">
                        @if($tag->entity_type === \App\Models\Product::class || $tag->entity_type === 'product')
                            {{-- Product Card --}}
                            <div class="card h-100 shadow-sm">
                                @if($item->thumbnail)
                                    <img src="{{ asset($item->thumbnail) }}" 
                                         class="card-img-top" 
                                         alt="{{ $item->name }}"
                                         style="height:200px;object-fit:cover;">
                                @endif
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="{{ route('client.product.show', $item->slug ?? $item->id) }}" 
                                           class="text-decoration-none">
                                            {{ $item->name }}
                                        </a>
                                    </h5>
                                    <p class="card-text text-muted small">
                                        {{ Str::limit($item->description ?? '', 100) }}
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-primary fw-bold">
                                            {{ number_format($item->price) }}đ
                                        </span>
                                        <a href="{{ route('client.product.show', $item->slug ?? $item->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            Xem chi tiết
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Post Card --}}
                            <div class="card h-100 shadow-sm">
                                @if($item->thumbnail)
                                    <img src="{{ asset($item->thumbnail) }}" 
                                         class="card-img-top" 
                                         alt="{{ renderMeta($item->title) }}"
                                         style="height:200px;object-fit:cover;">
                                @endif
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="{{ route('client.blog.show', $item->slug ?? $item->id) }}" 
                                           class="text-decoration-none">
                                            {{ renderMeta($item->title) }}
                                        </a>
                                    </h5>
                                    <p class="card-text text-muted small">
                                        {{ renderMeta(Str::limit($item->excerpt_text ?? '', 100)) }}
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            {{ $item->published_at->format('d/m/Y') }}
                                        </small>
                                        <a href="{{ route('client.blog.show', $item->slug ?? $item->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            Đọc tiếp
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $items->links() }}
            </div>
        @else
            <div class="alert alert-info">
                Không tìm thấy nội dung nào với tag này.
            </div>
        @endif
    </div>
@endsection

