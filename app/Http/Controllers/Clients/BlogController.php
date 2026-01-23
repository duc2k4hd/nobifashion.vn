<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(protected PostService $postService)
    {
    }

    public function index(Request $request): View
    {
        $posts = Post::published()
            ->with(['author', 'category'])
            ->orderByDesc('published_at')
            ->paginate(14)
            ->withQueryString();

        $featuredPosts = Cache::remember('blog:featured', 600, function () {
            return Post::published()
                ->featured()
                ->with(['author', 'category'])
                ->latest('published_at')
                ->take(3)
                ->get();
        });

        $sidebarCategories = Cache::remember('blog:sidebar:categories', 600, function () {
            return Category::select('id', 'name', 'slug')
                ->withCount(['posts as posts_count' => fn ($q) => $q->published()])
                ->orderByDesc('posts_count')
                ->take(10)
                ->get();
        });

        $sidebarTags = Cache::remember('blog:sidebar:tags', 600, fn () => Tag::orderBy('name')->take(20)->get());

        $recentPosts = Cache::remember('blog:recent', 600, function () {
            return Post::published()
                ->latest('published_at')
                ->take(5)
                ->get(['id', 'title', 'slug', 'published_at']);
        });

        $popularPosts = Cache::remember('blog:popular', 600, function () {
            return Post::published()
                ->orderByDesc('views')
                ->take(5)
                ->get(['id', 'title', 'slug', 'views']);
        });

        $schemaData = $this->buildIndexSchemaData($posts, $featuredPosts, $sidebarCategories);

        return view('clients.blog.index', [
            'posts' => $posts,
            'featuredPosts' => $featuredPosts,
            'sidebarCategories' => $sidebarCategories,
            'sidebarTags' => $sidebarTags,
            'recentPosts' => $recentPosts,
            'popularPosts' => $popularPosts,
            'schemaData' => $schemaData,
        ]);
    }

    public function show(Request $request, Post $post): View
    {
        if(!$post->isPublished()) {
            return view('clients.pages.errors.404');
        }

        $post->load(['author', 'category', 'tags']);
        $this->postService->incrementViews($post, $request);

        // Lấy tags từ polymorphic relationship
        $tags = $post->tags()->active()->get();

        // Lấy related posts dựa trên tags chung
        $relatedPosts = Cache::remember("blog:related:{$post->id}", 600, function () use ($post, $tags) {
            $query = Post::published()
                ->where('id', '!=', $post->id)
                ->with(['author', 'category']);

            if ($tags->isNotEmpty()) {
                // Lấy các post có chung ít nhất 1 tag
                $tagIds = $tags->pluck('id')->toArray();
                $postIds = Tag::whereIn('id', $tagIds)
                    ->where('entity_type', Post::class)
                    ->orWhere('entity_type', 'post')
                    ->pluck('entity_id')
                    ->unique()
                    ->filter(fn($id) => $id != $post->id)
                    ->toArray();

                if (!empty($postIds)) {
                    $query->whereIn('id', $postIds);
                } elseif ($post->category_id) {
                    $query->where('category_id', $post->category_id);
                }
            } elseif ($post->category_id) {
                $query->where('category_id', $post->category_id);
            }

            return $query->latest('published_at')->take(4)->get();
        });

        $internalLinks = Cache::remember('blog:internal-links', 600, function () {
            return Post::published()
                ->orderByDesc('views')
                ->take(8)
                ->get(['id', 'title', 'slug']);
        });

        [$contentWithAnchors, $toc] = $this->buildTocContent($post->content ?? '');

        $sidebarCategories = Cache::remember('blog:sidebar:categories', 600, function () {
            return Category::select('id', 'name', 'slug')
                ->withCount(['posts as posts_count' => fn ($q) => $q->published()])
                ->orderByDesc('posts_count')
                ->take(10)
                ->get();
        });

        $sidebarTags = Cache::remember('blog:sidebar:tags', 600, fn () => Tag::orderBy('name')->take(20)->get());

        $schemaComments = $post->comments()
            ->approved()
            ->with('account:id,name')
            ->latest('created_at')
            ->take(5)
            ->get();

        $schemaData = $this->buildSchemaData($post, $tags, $schemaComments);

        $commentsCount = $post->comments()->approved()->count();

        return view('clients.blog.show', [
            'post' => $post,
            'contentWithAnchors' => $contentWithAnchors,
            'toc' => $toc,
            'tags' => $tags,
            'relatedPosts' => $relatedPosts,
            'internalLinks' => $internalLinks,
            'sidebarCategories' => $sidebarCategories,
            'sidebarTags' => $sidebarTags,
            'schemaData' => $schemaData,
            'commentsCount' => $commentsCount,
        ]);
    }

    protected function buildTocContent(string $html): array
    {
        if (empty($html)) {
            return [$html, collect()];
        }

        $tocItems = collect();
        $index = 0;

        $content = preg_replace_callback('/<(h[2-3])(.*?)>(.*?)<\/\1>/i', function ($matches) use (&$tocItems, &$index) {
            $tag = $matches[1];
            $attrs = $matches[2];
            $text = strip_tags($matches[3]);
            $id = Str::slug($text);
            if (empty($id)) {
                $id = 'heading-' . (++$index);
            }
            $id .= '-' . (++$index);

            $tocItems->push([
                'label' => $text,
                'id' => $id,
                'tag' => $tag,
            ]);

            return sprintf('<%s%s id="%s">%s</%s>', $tag, $attrs, $id, $matches[3], $tag);
        }, $html);

        return [$content ?? $html, $tocItems];
    }

    protected function buildIndexSchemaData($posts, $featuredPosts, $sidebarCategories): array
    {
        $settings = \Illuminate\Support\Facades\View::shared('settings') ?? \App\Models\Setting::first();
        $siteUrl = $settings->site_url ?? config('app.url');
        $siteName = $settings->site_name ?? config('app.name');
        $siteLogo = $settings->site_logo ? asset('clients/assets/img/business/' . $settings->site_logo) : ($settings->site_logo ?? asset('clients/assets/img/business/logo.png'));

        $schemas = [];

        // 1. Organization Schema
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $siteName,
            'url' => $siteUrl,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $siteLogo,
                'width' => 180,
                'height' => 55,
            ],
            'sameAs' => array_filter([
                $settings->facebook_url ?? null,
                $settings->twitter_url ?? null,
                $settings->instagram_url ?? null,
                $settings->youtube_url ?? null,
            ]),
        ];

        // 2. WebSite Schema
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteName,
            'url' => $siteUrl,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $siteUrl . '/shop/search?keyword={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        // 3. BreadcrumbList Schema
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Trang chủ',
                    'item' => $siteUrl,
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Blog',
                    'item' => route('client.blog.index'),
                ],
            ],
        ];

        // 4. CollectionPage Schema
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => 'Blog & Tin tức thời trang',
            'description' => 'Chia sẻ kinh nghiệm phối đồ, xu hướng thời trang và các câu chuyện thương hiệu',
            'url' => route('client.blog.index'),
            'mainEntity' => [
                '@type' => 'ItemList',
                'numberOfItems' => $posts->total(),
                'itemListElement' => $posts->map(function ($post, $index) use ($siteName, $siteLogo) {
                    return [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'item' => [
                            '@type' => 'BlogPosting',
                            '@id' => route('client.blog.show', $post),
                            'headline' => $post->title,
                            'url' => route('client.blog.show', $post),
                            'image' => $post->thumbnail ? asset($post->thumbnail) : null,
                            'datePublished' => optional($post->published_at)->toIso8601String(),
                            'dateModified' => optional($post->updated_at)->toIso8601String(),
                            'author' => [
                                '@type' => 'Person',
                                'name' => $post->author?->name ?? $siteName,
                            ],
                            'publisher' => [
                                '@type' => 'Organization',
                                'name' => $siteName,
                                'logo' => [
                                    '@type' => 'ImageObject',
                                    'url' => $siteLogo,
                                ],
                            ],
                        ],
                    ];
                })->values()->all(),
            ],
        ];

        // 5. ItemList Schema cho Featured Posts
        if ($featuredPosts->isNotEmpty()) {
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'name' => 'Bài viết nổi bật',
                'itemListElement' => $featuredPosts->map(function ($post, $index) {
                    return [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'item' => [
                            '@type' => 'BlogPosting',
                            '@id' => route('client.blog.show', $post),
                            'headline' => $post->title,
                            'url' => route('client.blog.show', $post),
                            'image' => $post->thumbnail ? asset($post->thumbnail) : null,
                            'datePublished' => optional($post->published_at)->toIso8601String(),
                        ],
                    ];
                })->values()->all(),
            ];
        }

        return $schemas;
    }

    protected function buildSchemaData(Post $post, Collection $tags, Collection $comments): array
    {
        $settings = \Illuminate\Support\Facades\View::shared('settings') ?? \App\Models\Setting::first();
        $siteUrl = $settings->site_url ?? config('app.url');
        $siteName = $settings->site_name ?? config('app.name');
        $siteLogo = $settings->site_logo ? asset('clients/assets/img/business/' . $settings->site_logo) : ($settings->site_logo ?? asset('clients/assets/img/business/logo.png'));

        $schemas = [];

        // 1. Organization Schema
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $siteName,
            'url' => $siteUrl,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $siteLogo,
                'width' => 180,
                'height' => 55,
            ],
            'sameAs' => array_filter([
                $settings->facebook_url ?? null,
                $settings->twitter_url ?? null,
                $settings->instagram_url ?? null,
                $settings->youtube_url ?? null,
            ]),
        ];

        // 2. WebSite Schema
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteName,
            'url' => $siteUrl,
        ];

        // 3. BreadcrumbList Schema
        $breadcrumbs = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Trang chủ',
                'item' => $siteUrl,
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'Blog',
                'item' => route('client.blog.index'),
            ],
        ];

        if ($post->category) {
            $breadcrumbs[] = [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $post->category->name,
                'item' => route('client.blog.index', ['category' => $post->category->slug]),
            ];
        }

        $breadcrumbs[] = [
            '@type' => 'ListItem',
            'position' => count($breadcrumbs) + 1,
            'name' => $post->title,
            'item' => route('client.blog.show', $post),
        ];

        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbs,
        ];

        // 4. Article/BlogPosting Schema (Main)
        $articleSchema = [
            '@context' => 'https://schema.org',
            '@type' => ['Article', 'BlogPosting'],
            '@id' => route('client.blog.show', $post),
            'headline' => $post->meta_title ?? $post->title,
            'description' => $post->meta_description ?? $post->excerpt_text ?? Str::limit(strip_tags($post->content ?? ''), 160),
            'url' => route('client.blog.show', $post),
            'datePublished' => optional($post->published_at)->toIso8601String(),
            'dateModified' => optional($post->updated_at)->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $post->author?->name ?? $siteName,
                'url' => $siteUrl,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $siteName,
                'url' => $siteUrl,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $siteLogo,
                    'width' => 180,
                    'height' => 55,
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => route('client.blog.show', $post),
            ],
            'articleSection' => $post->category?->name ?? 'Tin tức',
            'inLanguage' => 'vi-VN',
        ];

        // Image
        if ($post->thumbnail) {
            $articleSchema['image'] = [
                '@type' => 'ImageObject',
                'url' => asset($post->thumbnail),
                'width' => 1200,
                'height' => 630,
            ];
        }

        // Keywords
        if ($tags->isNotEmpty()) {
            $articleSchema['keywords'] = $tags->pluck('name')->implode(', ');
        }

        // Word count & reading time
        $wordCount = str_word_count(strip_tags($post->content ?? ''));
        if ($wordCount > 0) {
            $articleSchema['wordCount'] = $wordCount;
            $articleSchema['timeRequired'] = 'PT' . ceil($wordCount / 250) . 'M';
        }

        // Reviews & Ratings
        $reviews = $comments->map(function (Comment $comment) {
            $review = [
                '@type' => 'Review',
                'author' => [
                    '@type' => 'Person',
                    'name' => $comment->account?->name ?? $comment->guest_name ?? 'Khách',
                ],
                'datePublished' => optional($comment->created_at)->toIso8601String(),
                'reviewBody' => Str::limit(strip_tags((string) $comment->content), 1000),
            ];

            if ($comment->rating) {
                $review['reviewRating'] = [
                    '@type' => 'Rating',
                    'ratingValue' => (int) $comment->rating,
                    'bestRating' => 5,
                    'worstRating' => 1,
                ];
            }

            return $review;
        })->filter(fn ($review) => !empty($review['reviewBody']))->values();

        if ($reviews->isNotEmpty()) {
            $articleSchema['review'] = $reviews->all();
        }

        $ratings = $comments->pluck('rating')->filter();
        if ($ratings->isNotEmpty()) {
            $articleSchema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round($ratings->avg(), 1),
                'reviewCount' => $ratings->count(),
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        $schemas[] = $articleSchema;

        return $schemas;
    }
}


