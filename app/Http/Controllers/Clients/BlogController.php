<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostCategory;
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

    public function index(Request $request): \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|View
    {
        // Chuyển hướng 301 chuẩn SEO nếu có query ?category=slug sang URL danh mục chuyên biệt
        if ($request->filled('category')) {
            return redirect()->route('client.blog.category', ['category' => $request->query('category')], 301);
        }

        $posts = Post::published()
            ->with(['author:id,name', 'category:id,name,slug'])
            ->orderByDesc('published_at')
            ->paginate(14)
            ->withQueryString();

        $featuredPosts = Cache::remember('blog:featured', 600, function () {
            return Post::published()
                ->featured()
                ->with(['author:id,name', 'category:id,name,slug'])
                ->latest('published_at')
                ->take(3)
                ->get();
        });

        $sidebarCategories = Cache::remember('blog:sidebar:categories', 600, function () {
            return PostCategory::active()
                ->select('id', 'name', 'slug')
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
            'currentCategory' => null,
        ]);
    }

    public function category(Request $request, PostCategory $category): View
    {
        if (!$category->is_active) {
            abort(404);
        }

        // Tối ưu trực tiếp bằng B-Tree index posts_category_status_published_idx cực nhanh
        $posts = Post::published()
            ->where('category_id', $category->id)
            ->with(['author:id,name', 'category:id,name,slug'])
            ->orderByDesc('published_at')
            ->paginate(14)
            ->withQueryString();

        $featuredPosts = Cache::remember("blog:category:featured:{$category->id}", 600, function () use ($category) {
            return Post::published()
                ->where('category_id', $category->id)
                ->featured()
                ->with(['author:id,name', 'category:id,name,slug'])
                ->latest('published_at')
                ->take(3)
                ->get();
        });

        // Nếu category chưa có bài featured riêng thì fallback bài featured chung
        if ($featuredPosts->isEmpty()) {
            $featuredPosts = Cache::remember('blog:featured', 600, function () {
                return Post::published()
                    ->featured()
                    ->with(['author:id,name', 'category:id,name,slug'])
                    ->latest('published_at')
                    ->take(3)
                    ->get();
            });
        }

        $sidebarCategories = Cache::remember('blog:sidebar:categories', 600, function () {
            return PostCategory::active()
                ->select('id', 'name', 'slug')
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

        $schemaData = $this->buildCategorySchemaData($category, $posts, $featuredPosts, $sidebarCategories);

        return view('clients.blog.index', [
            'posts' => $posts,
            'featuredPosts' => $featuredPosts,
            'sidebarCategories' => $sidebarCategories,
            'sidebarTags' => $sidebarTags,
            'recentPosts' => $recentPosts,
            'popularPosts' => $popularPosts,
            'schemaData' => $schemaData,
            'currentCategory' => $category,
        ]);
    }



    public function searchApi(Request $request): \Illuminate\Http\JsonResponse
    {
        $keyword = trim($request->input('keyword', ''));

        if ($keyword === '') {
            return response()->json([]);
        }

        $words = array_filter(explode(' ', $keyword));

        $posts = \App\Models\Post::published()
            ->select('id', 'title', 'slug', 'thumbnail', 'category_id', 'published_at')
            ->with(['category:id,name,slug'])
            ->where(function ($q) use ($keyword, $words) {
                // Ưu tiên cụm từ chính xác trước
                $q->where('title', 'LIKE', "%{$keyword}%")
                  ->orWhere('meta_title', 'LIKE', "%{$keyword}%")
                  ->orWhere('meta_description', 'LIKE', "%{$keyword}%");

                // Tìm theo từng từ khóa nhỏ
                if (count($words) > 1) {
                    $q->orWhere(function ($sub) use ($words) {
                        foreach ($words as $word) {
                            $sub->where('title', 'LIKE', "%{$word}%")
                               ->orWhere('meta_title', 'LIKE', "%{$word}%")
                               ->orWhere('meta_description', 'LIKE', "%{$word}%");
                        }
                    });
                }
            })
            ->orderByRaw("CASE 
                WHEN title LIKE ? OR title LIKE ? OR title LIKE ? OR title = ? THEN 1
                WHEN title LIKE ? THEN 2
                WHEN meta_title LIKE ? OR meta_description LIKE ? THEN 3
                ELSE 4 
            END ASC", [
                "% {$keyword} %", "{$keyword} %", "% {$keyword}", "{$keyword}",
                "%{$keyword}%", 
                "%{$keyword}%", "%{$keyword}%"
            ])
            ->orderByDesc('published_at')
            ->limit(10)
            ->get();

        $posts->transform(function ($post) {
            $thumbnailUrl = '/clients/assets/img/clothes/no-image.webp';
            if ($post->thumbnail) {
                $thumbnailUrl = str_starts_with($post->thumbnail, 'http')
                    ? $post->thumbnail
                    : (str_starts_with($post->thumbnail, 'clients/') ? '/' . ltrim($post->thumbnail, '/') : '/clients/assets/img/posts/' . ltrim($post->thumbnail, '/'));
            }
            return [
                'id' => $post->id,
                'title' => renderMeta($post->title),
                'name' => renderMeta($post->title),
                'slug' => $post->slug,
                'url' => route('client.blog.show', $post->slug),
                'thumbnail_url' => $thumbnailUrl,
                'category_name' => $post->category ? $post->category->name : 'Blog',
                'published_at' => $post->published_at ? $post->published_at->format('d/m/Y') : '',
            ];
        });

        return response()->json($posts);
    }

    public function searchKeyword(Request $request): View
    {
        $keyword = trim($request->input('keyword', ''));
        $words = array_filter(explode(' ', $keyword));

        $postsQuery = \App\Models\Post::published()
            ->with(['author:id,name', 'category:id,name,slug']);
            
        if ($keyword !== '') {
            $postsQuery->where(function ($q) use ($keyword, $words) {
                $q->where('title', 'LIKE', "%{$keyword}%")
                  ->orWhere('meta_title', 'LIKE', "%{$keyword}%")
                  ->orWhere('meta_description', 'LIKE', "%{$keyword}%");

                if (count($words) > 1) {
                    $q->orWhere(function ($sub) use ($words) {
                        foreach ($words as $word) {
                            $sub->where('title', 'LIKE', "%{$word}%")
                               ->orWhere('meta_title', 'LIKE', "%{$word}%")
                               ->orWhere('meta_description', 'LIKE', "%{$word}%");
                        }
                    });
                }
            })
            ->orderByRaw("CASE 
                WHEN title LIKE ? OR title LIKE ? OR title LIKE ? OR title = ? THEN 1
                WHEN title LIKE ? THEN 2
                WHEN meta_title LIKE ? OR meta_description LIKE ? THEN 3
                ELSE 4 
            END ASC", [
                "% {$keyword} %", "{$keyword} %", "% {$keyword}", "{$keyword}",
                "%{$keyword}%", 
                "%{$keyword}%", "%{$keyword}%"
            ]);
        }
        
        $posts = $postsQuery->orderByDesc('published_at')
            ->paginate(14)
            ->withQueryString();

        $featuredPosts = \Illuminate\Support\Facades\Cache::remember('blog:featured', 600, function () {
            return \App\Models\Post::published()->featured()->with(['author:id,name', 'category:id,name,slug'])->latest('published_at')->take(3)->get();
        });

        $sidebarCategories = \Illuminate\Support\Facades\Cache::remember('blog:sidebar:categories', 600, function () {
            return PostCategory::active()->select('id', 'name', 'slug')->withCount(['posts as posts_count' => fn ($q) => $q->published()])->orderByDesc('posts_count')->take(10)->get();
        });

        $sidebarTags = \Illuminate\Support\Facades\Cache::remember('blog:sidebar:tags', 600, fn () => \App\Models\Tag::orderBy('name')->take(20)->get());

        $recentPosts = \Illuminate\Support\Facades\Cache::remember('blog:recent', 600, function () {
            return \App\Models\Post::published()->latest('published_at')->take(5)->get(['id', 'title', 'slug', 'published_at']);
        });

        $popularPosts = \Illuminate\Support\Facades\Cache::remember('blog:popular', 600, function () {
            return \App\Models\Post::published()->orderByDesc('views')->take(5)->get(['id', 'title', 'slug', 'published_at', 'views']);
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
            'searchKeyword' => $keyword
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $post = Post::where('slug', $slug)->first();

        if (!$post || !$post->isPublished()) {
            return view('clients.pages.errors.404');
        }

        $post->load(['author.profile', 'category', 'tags']);
        $this->postService->incrementViews($post, $request);

        // Lấy tags từ polymorphic relationship
        $tags = $post->tags()->active()->get();

        // Lấy 3 bài trước và 3 bài sau cùng danh mục (tự động bù bài nếu một bên thiếu, tối ưu index cực nhanh)
        $relatedPosts = Cache::remember("blog:related:{$post->id}:v3", 3600, function () use ($post) {
            return $this->getRelatedPosts($post, 3);
        });

        // 20 bài random và cache mỗi bài
        $internalLinks = Cache::remember("blog:recommendations:{$post->id}", 3600, function () use ($post) {
            return Post::published()
                ->where('id', '!=', $post->id)
                ->inRandomOrder()
                ->take(20)
                ->get(['id', 'title', 'slug']);
        });

        [$contentWithAnchors, $toc] = $this->buildTocContent($post->content ?? '');

        $sidebarCategories = Cache::remember('blog:sidebar:categories', 600, function () {
            return PostCategory::active()
                ->select('id', 'name', 'slug')
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
            'name' => 'Nobi Blog - Xu hướng thời trang & Phong cách sống',
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

    protected function buildCategorySchemaData(PostCategory $category, $posts, $featuredPosts, $sidebarCategories): array
    {
        $settings = \Illuminate\Support\Facades\View::shared('settings') ?? \App\Models\Setting::first();
        $siteUrl = $settings->site_url ?? config('app.url');
        $siteName = $settings->site_name ?? config('app.name');
        $siteLogo = $settings->site_logo ? asset('clients/assets/img/business/' . $settings->site_logo) : ($settings->site_logo ?? asset('clients/assets/img/business/logo.png'));
        $catUrl = route('client.blog.category', $category);

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
        ];

        // 2. BreadcrumbList Schema
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
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $category->name,
                    'item' => $catUrl,
                ],
            ],
        ];

        // 3. CollectionPage Schema
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $category->meta_title ?: ($category->name . ' - Blog ' . $siteName),
            'description' => $category->meta_description ?: ($category->description ?: 'Tổng hợp bài viết chủ đề ' . $category->name),
            'url' => $catUrl,
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
                        ],
                    ];
                })->values()->all(),
            ],
        ];

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
                'item' => route('client.blog.category', $post->category),
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
                'name' => $post->author?->displayName() ?? $siteName,
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
            'articleSection' => $post->category?->name ?? 'Nobi Blog',
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

    /**
     * Lấy bài viết liên quan cùng danh mục: 3 bài trước và 3 bài sau của bài viết hiện tại.
     * Nếu không đủ trước hoặc sau thì tự động bù từ phía còn lại để đạt tối đa 6 bài.
     * Tối ưu cực nhanh: Chỉ SELECT đúng cột cần dùng, tận dụng 100% composite index (category_id, status, published_at).
     */
    protected function getRelatedPosts(Post $post, int $limitPerSide = 3): \Illuminate\Support\Collection
    {
        $maxTotal = $limitPerSide * 2;
        $fields = ['id', 'title', 'slug', 'thumbnail', 'published_at', 'category_id'];
        $categoryId = $post->category_id;

        // Base query tối ưu trên index posts_category_status_published_idx
        $baseQuery = fn () => Post::published()
            ->select($fields)
            ->when(
                $categoryId,
                fn ($q) => $q->where('category_id', $categoryId),
                fn ($q) => $q->whereNull('category_id')
            );

        // Lấy tối đa $maxTotal bài cũ hơn (trước bài hiện tại)
        $before = $baseQuery()
            ->where(function ($q) use ($post) {
                $q->where('published_at', '<', $post->published_at)
                  ->orWhere(function ($sub) use ($post) {
                      $sub->where('published_at', '=', $post->published_at)
                          ->where('id', '<', $post->id);
                  });
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->take($maxTotal)
            ->get();

        // Lấy tối đa $maxTotal bài mới hơn (sau bài hiện tại)
        $after = $baseQuery()
            ->where(function ($q) use ($post) {
                $q->where('published_at', '>', $post->published_at)
                  ->orWhere(function ($sub) use ($post) {
                      $sub->where('published_at', '=', $post->published_at)
                          ->where('id', '>', $post->id);
                  });
            })
            ->orderBy('published_at', 'asc')
            ->orderBy('id', 'asc')
            ->take($maxTotal)
            ->get();

        $beforeCount = $before->count();
        $afterCount = $after->count();

        $takeAfter = $limitPerSide;
        $takeBefore = $limitPerSide;

        // Thuật toán bù trừ thông minh nếu một trong hai phía thiếu bài
        if ($beforeCount < $limitPerSide) {
            $takeAfter = min($afterCount, $maxTotal - $beforeCount);
        } elseif ($afterCount < $limitPerSide) {
            $takeBefore = min($beforeCount, $maxTotal - $afterCount);
        }

        $selectedAfter = $after->take($takeAfter);
        $selectedBefore = $before->take($takeBefore);

        // Ghép thứ tự thời gian chuẩn: Bài mới hơn xếp trên (đảo ngược asc thành desc) + bài cũ hơn
        return $selectedAfter->reverse()->concat($selectedBefore)->values();
    }
}


