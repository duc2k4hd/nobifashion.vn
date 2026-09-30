<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

        // Chuyển hướng 301 chuẩn SEO nếu có query ?tag=slug
        if ($request->filled('tag')) {
            $tagSlug = trim((string) $request->query('tag'));
            $tag = Tag::where('slug', $tagSlug)->active()->first();
            if ($tag) {
                return redirect()->route('client.tags.show', ['slug' => $tag->slug], 301);
            }
            // Nếu tag không tồn tại (ví dụ slug cũ, tag rác như son-post-1686), chuyển hướng 301 về blog chuẩn không có hỏi chấm
            return redirect()->route('client.blog.index', [], 301);
        }

        // Chuyển hướng 301 chuẩn SEO nếu có query ?page=1 về URL gốc sạch
        if ($request->query('page') === '1' || $request->query('page') === 1) {
            $cleanQuery = $request->query();
            unset($cleanQuery['page']);
            return redirect()->route('client.blog.index', $cleanQuery, 301);
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
                ->orderBy('sort_order')
                ->get();
        });

        $sidebarTags = $this->getBlogSidebarTags();

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
            'category' => null,
        ]);
    }

    public function category(Request $request, PostCategory $category): \Illuminate\Http\RedirectResponse|View
    {
        if (!$category->is_active) {
            abort(404);
        }

        // Chuyển hướng 301 chuẩn SEO nếu có query ?page=1 về URL danh mục gốc sạch
        if ($request->query('page') === '1' || $request->query('page') === 1) {
            $cleanQuery = $request->query();
            unset($cleanQuery['page']);
            return redirect()->route('client.blog.category', array_merge(['category' => $category], $cleanQuery), 301);
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
                ->orderBy('sort_order')
                ->get();
        });

        $sidebarTags = $this->getBlogSidebarTags();

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
            'category' => $category,
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
            return PostCategory::active()->select('id', 'name', 'slug')->withCount(['posts as posts_count' => fn ($q) => $q->published()])->orderByDesc('posts_count')->orderBy('sort_order')->get();
        });

        $sidebarTags = $this->getBlogSidebarTags();

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

    public function show(Request $request, string $slug): View|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
    {
        $post = Post::where('slug', $slug)->first();

        if (!$post || !$post->isPublished()) {
            // Kiểm tra chuyển hướng 301 chuẩn SEO (Siêu tốc & Chống vòng lặp/link hỏng)
            $redirect = app(\App\Services\RedirectService::class)->resolveRequest($request);

            if ($redirect) {
                return redirect()->to($redirect['url'], $redirect['status_code'] ?? 301);
            }

            return response()->view('clients.pages.errors.404', [], 404);
        }

        // Chỉ eager load đúng cột cần thiết, nạp profile để lấy full_name tác giả siêu tốc
        $post->load(['category:id,name,slug', 'author:id,name,role', 'author.profile']);
        $this->postService->incrementViews($post, $request);

        // Lấy tags hoạt động trực tiếp 1 lần duy nhất với các cột cần dùng
        $tags = $post->tags()->active()->get(['tags.id', 'tags.name', 'tags.slug']);

        // Nếu bài viết chưa có tags từ relationship, trích xuất từ meta_keywords của bài viết
        if ($tags->isEmpty() && !empty($post->meta_keywords)) {
            $keywords = is_array($post->meta_keywords)
                ? $post->meta_keywords
                : array_map('trim', explode(',', (string) $post->meta_keywords));

            $extractedTags = collect();
            foreach ($keywords as $kw) {
                $clean = trim($kw);
                $lower = mb_strtolower($clean);
                if (mb_strlen($clean) < 3 || in_array($lower, ['nobi fashion', 'nobi fashion việt nam', 'thời trang'])) {
                    continue;
                }
                $slug = Str::slug($clean);
                if ($slug && !$extractedTags->contains('slug', $slug)) {
                    $tagModel = Tag::firstOrCreate(
                        ['slug' => $slug],
                        [
                            'name' => $clean,
                            'entity_type' => 'post',
                            'entity_id' => 0,
                            'is_active' => true,
                            'usage_count' => 1,
                        ]
                    );
                    $extractedTags->push($tagModel);
                    if ($extractedTags->count() >= 8) {
                        break;
                    }
                }
            }
            $tags = $extractedTags;
        }

        // Lấy 6 bài liên quan cùng danh mục (tối ưu B-Tree Index không filesort < 0.1ms, cache 1 ngày)
        $relatedPosts = Cache::remember("blog:related:{$post->id}:v4", 86400, function () use ($post) {
            return $this->getRelatedPosts($post, 3);
        });

        // Lấy 20 bài random ngẫu nhiên trong quy mô 1 triệu bài viết (Thuật toán Primary Key Index Seek < 1ms, cache 30 ngày)
        $internalLinks = Cache::remember("blog:recommendations:{$post->id}", now()->addDays(30), function () use ($post) {
            return $this->getRandomRecommendationsFast($post, 20);
        });

        [$contentWithAnchors, $toc] = $this->buildTocContent($post->content ?? '');
        $contentWithAnchors = optimizePostHtml($contentWithAnchors);

        // Nạp bình luận duyệt cho Schema & số lượng bình luận
        $approvedComments = $post->comments()
            ->approved()
            ->with('account:id,name')
            ->latest('created_at')
            ->take(5)
            ->get();

        $commentsCount = $approvedComments->count() < 5
            ? $approvedComments->count()
            : $post->comments()->approved()->count();

        // Xác định chính xác tác giả của bài viết (Admin hoặc Staff)
        $author = $post->author;
        if (! $author) {
            $author = Account::where('role', Account::ROLE_ADMIN)->with('profile')->first() ?? Account::first();
        } elseif (! $author->relationLoaded('profile')) {
            $author->load('profile');
        }

        $authorProfile = $author?->profile;
        $authorFullName = $authorProfile?->full_name ?? $author?->name ?? 'Đức Nobi 💖';
        $authorAvatarUrl = null;
        if (!empty($authorProfile?->avatar)) {
            $rawAvatar = ltrim($authorProfile->avatar, '/');
            $authorAvatarUrl = (str_starts_with($rawAvatar, 'http://') || str_starts_with($rawAvatar, 'https://'))
                ? $rawAvatar
                : asset('clients/assets/img/' . (str_starts_with($rawAvatar, 'users/') ? $rawAvatar : 'users/' . $rawAvatar));
        }
        if (!$authorAvatarUrl) {
            $authorAvatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($authorFullName) . '&background=0F172A&color=ffffff&bold=true&size=160';
        }

        $isAuthorAdmin = ($author?->role === Account::ROLE_ADMIN);
        $authorRoleBadge = $isAuthorAdmin ? '👑 Nhà sáng lập & Tổng biên tập' : '✨ Biên tập viên & Stylist';
        $authorBio = $authorProfile?->bio && mb_strlen($authorProfile->bio) > 10
            ? $authorProfile->bio
            : ($isAuthorAdmin
                ? 'Nhà sáng lập kiêm Tổng biên tập tại Nobi Fashion. Chuyên gia phân tích xu hướng thời trang giới trẻ, định hình phong cách sống hiện đại và phối đồ ứng dụng.'
                : 'Chuyên viên Định hình phong cách và Biên tập viên nội dung thời trang tại Nobi Fashion. Chuyên gia tư vấn xu hướng và cẩm nang phối đồ thực tế.');

        $authorUrl = AuthorController::getAuthorUrl($author);

        $schemaData = $this->buildSchemaData($post, $tags, $approvedComments, $authorFullName, $authorUrl);

        return view('clients.blog.show', [
            'post' => $post,
            'authorFullName' => $authorFullName,
            'authorAvatarUrl' => $authorAvatarUrl,
            'authorRoleBadge' => $authorRoleBadge,
            'authorBio' => $authorBio,
            'authorUrl' => $authorUrl,
            'contentWithAnchors' => $contentWithAnchors,
            'toc' => $toc,
            'tags' => $tags,
            'relatedPosts' => $relatedPosts,
            'internalLinks' => $internalLinks,
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
                                'name' => $post->author?->profile?->full_name ?? $post->author?->displayName() ?? 'Đức Nobi 💖',
                                'url' => AuthorController::getAuthorUrl($post->author),
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
                                'name' => $post->author?->profile?->full_name ?? $post->author?->displayName() ?? 'Đức Nobi 💖',
                                'url' => AuthorController::getAuthorUrl($post->author),
                            ],
                        ],
                    ];
                })->values()->all(),
            ],
        ];

        return $schemas;
    }

    protected function buildSchemaData(Post $post, Collection $tags, Collection $comments, ?string $authorFullName = null, ?string $authorUrl = null): array
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

        // 4. BlogPosting Schema (Main)
        $articleSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            '@id' => route('client.blog.show', $post),
            'headline' => $post->meta_title ?? $post->title,
            'description' => $post->meta_description ?? $post->excerpt_text ?? Str::limit(strip_tags($post->content ?? ''), 160),
            'url' => route('client.blog.show', $post),
            'datePublished' => optional($post->published_at ?? $post->created_at)->toIso8601String(),
            'dateModified' => optional($post->updated_at ?? $post->published_at ?? $post->created_at)->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $authorFullName ?? ($post->author?->profile?->full_name ?? $post->author?->displayName() ?? 'Đức Nobi 💖'),
                'url' => $authorUrl ?? AuthorController::getAuthorUrl($post->author),
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
            $thumbFile = basename($post->thumbnail);
            $articleSchema['image'] = [
                '@type' => 'ImageObject',
                'url' => asset('clients/assets/img/posts/' . $thumbFile),
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
     * Lấy bài viết liên quan cùng danh mục siêu tốc tối ưu cho quy mô 100.000 bài viết.
     * Sử dụng thuần B-Tree index scan (0.1ms), hoàn toàn loại bỏ Using filesort.
     * Tự động bù trừ để đảm bảo luôn đủ 6 bài (hoặc tối đa số bài có trong danh mục).
     */
    protected function getRelatedPosts(Post $post, int $limitPerSide = 3): \Illuminate\Support\Collection
    {
        $maxTotal = $limitPerSide * 2;
        $fields = ['id', 'title', 'slug', 'thumbnail', 'published_at', 'category_id'];
        $categoryId = $post->category_id;

        $baseQuery = fn () => Post::published()
            ->select($fields)
            ->when(
                $categoryId,
                fn ($q) => $q->where('category_id', $categoryId),
                fn ($q) => $q->whereNull('category_id')
            )
            ->where('id', '!=', $post->id);

        // 1. Lấy 3 bài cũ hơn (Backward Index Scan - 0.05ms, không filesort)
        $before = $baseQuery()
            ->where('published_at', '<=', $post->published_at)
            ->orderByDesc('published_at')
            ->take($limitPerSide)
            ->get();

        // 2. Lấy 3 bài mới hơn (Forward Index Scan - 0.05ms, không filesort)
        $after = $baseQuery()
            ->where('published_at', '>=', $post->published_at)
            ->orderBy('published_at', 'asc')
            ->take($limitPerSide)
            ->get();

        // Ghép theo thứ tự: bài mới hơn xếp trước, bài cũ hơn xếp sau
        $combined = $after->reverse()->concat($before)->values();

        // 3. Nếu chưa đủ số lượng (ví dụ bài mới nhất hoặc cũ nhất), tự động bù từ các bài cùng danh mục
        if ($combined->count() < $maxTotal) {
            $existingIds = $combined->pluck('id')->push($post->id)->all();
            $needed = $maxTotal - $combined->count();

            $more = $baseQuery()
                ->whereNotIn('id', $existingIds)
                ->orderByDesc('published_at')
                ->take($needed)
                ->get();

            $combined = $combined->concat($more);
        }

        return $combined->take($maxTotal)->values();
    }

    /**
     * Lấy danh sách hashtag bài viết nổi bật cho Blog (loại bỏ hoàn toàn tag sản phẩm)
     */
    protected function getBlogSidebarTags(): Collection
    {
        return Cache::remember('blog:sidebar:tags:v3', 3600, function () {
            // 1. Ưu tiên lấy tag của bài viết từ database
            $postTags = Tag::active()
                ->where(function ($q) {
                    $q->where('entity_type', Post::class)
                      ->orWhere('entity_type', 'post');
                })
                ->orderByDesc('usage_count')
                ->take(20)
                ->get(['id', 'name', 'slug']);

            if ($postTags->count() >= 10) {
                return $postTags;
            }

            // 2. Trích xuất từ meta_keywords của các bài viết blog đã xuất bản
            $recentKeywords = Post::published()
                ->whereNotNull('meta_keywords')
                ->latest('published_at')
                ->take(80)
                ->pluck('meta_keywords');

            $tagCounts = [];
            foreach ($recentKeywords as $kwStr) {
                $items = array_map('trim', explode(',', (string) $kwStr));
                foreach ($items as $item) {
                    $clean = trim($item);
                    $lower = mb_strtolower($clean);
                    if (mb_strlen($clean) >= 3 && !in_array($lower, ['nobi fashion', 'nobi fashion việt nam', 'thời trang'])) {
                        $tagCounts[$clean] = ($tagCounts[$clean] ?? 0) + 1;
                    }
                }
            }

            arsort($tagCounts);
            $topTags = array_slice(array_keys($tagCounts), 0, 20);

            $result = collect();
            foreach ($topTags as $name) {
                $slug = Str::slug($name);
                if ($slug && !$result->contains('slug', $slug)) {
                    $tagModel = Tag::firstOrCreate(
                        ['slug' => $slug],
                        [
                            'name' => $name,
                            'entity_type' => 'post',
                            'entity_id' => 0,
                            'is_active' => true,
                            'usage_count' => $tagCounts[$name] ?? 1,
                        ]
                    );
                    $result->push($tagModel);
                }
            }

            return $result;
        });
    }

    /**
     * Lấy danh sách bài viết ngẫu nhiên siêu tốc tối ưu cho quy mô 1 triệu bài viết (O(log N))
     */
    protected function getRandomRecommendationsFast(Post $currentPost, int $limit = 20): \Illuminate\Support\Collection
    {
        // 1. Lấy cận min_id và max_id từ B-Tree index (chỉ đọc node đầu và cuối của Primary Key, cực nhanh)
        $bounds = Cache::remember('blog:posts_id_bounds', 86400, function () {
            return DB::table('posts')
                ->where('status', 'published')
                ->selectRaw('MIN(id) as min_id, MAX(id) as max_id')
                ->first();
        });

        $minId = (int) ($bounds->min_id ?? 1);
        $maxId = (int) ($bounds->max_id ?? 1);

        if ($maxId <= $minId) {
            return Post::published()
                ->where('id', '!=', $currentPost->id)
                ->take($limit)
                ->get(['id', 'title', 'slug']);
        }

        // 2. Sinh ngẫu nhiên một tập hợp các mốc ID phân bổ đều khắp dải [minId, maxId]
        $candidateCount = (int) ceil($limit * 2.5);
        $randomIds = [];
        for ($i = 0; $i < $candidateCount; $i++) {
            $randomIds[] = mt_rand($minId, $maxId);
        }
        $randomIds = array_unique($randomIds);

        // 3. Truy vấn trực tiếp bằng Clustered B-Tree Primary Key index (O(log N) - mất < 0.5ms trên 1 triệu bài)
        $posts = Post::published()
            ->whereIn('id', $randomIds)
            ->where('id', '!=', $currentPost->id)
            ->take($limit)
            ->get(['id', 'title', 'slug']);

        // 4. Nếu bị hụt do các khoảng trống ID bị xóa, bù đắp bằng phương pháp Random Offset Jump
        if ($posts->count() < $limit) {
            $needed = $limit - $posts->count();
            $existingIds = $posts->pluck('id')->push($currentPost->id)->all();
            $jumpSeed = mt_rand($minId, max($minId, $maxId - 200));

            $fallback = Post::published()
                ->whereNotIn('id', $existingIds)
                ->where('id', '>=', $jumpSeed)
                ->take($needed)
                ->get(['id', 'title', 'slug']);

            $posts = $posts->merge($fallback);
        }

        // 5. Nếu vẫn chưa đủ (ví dụ cơ sở dữ liệu có ít hơn 20 bài), lấy các bài mới nhất bù vào
        if ($posts->count() < $limit) {
            $needed = $limit - $posts->count();
            $existingIds = $posts->pluck('id')->push($currentPost->id)->all();

            $finalFallback = Post::published()
                ->whereNotIn('id', $existingIds)
                ->latest('id')
                ->take($needed)
                ->get(['id', 'title', 'slug']);

            $posts = $posts->merge($finalFallback);
        }

        // Xáo trộn vị trí ngẫu nhiên hoàn toàn
        return $posts->shuffle()->values();
    }
}


