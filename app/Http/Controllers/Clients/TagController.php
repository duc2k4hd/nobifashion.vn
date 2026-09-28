<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\Product;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    /**
     * Hiển thị danh sách bài viết/sản phẩm theo tag
     */
    public function show(string $slug, Request $request): View
    {
        $tag = Tag::where('slug', $slug)
            ->active()
            ->first();

        // Nếu chưa có tag trong database, tìm kiếm theo slug để tự tạo nếu có bài viết/sản phẩm khớp
        if (!$tag) {
            $normalizedName = trim(str_replace('-', ' ', $slug));
            $hasPost = Post::published()
                ->where(function ($q) use ($normalizedName) {
                    $q->where('meta_keywords', 'LIKE', "%{$normalizedName}%")
                      ->orWhere('title', 'LIKE', "%{$normalizedName}%");
                })
                ->exists();

            if ($hasPost) {
                $tag = Tag::create([
                    'name' => $normalizedName,
                    'slug' => $slug,
                    'entity_type' => 'post',
                    'entity_id' => 0,
                    'is_active' => true,
                    'usage_count' => 1,
                ]);
            } else {
                $hasProduct = Product::active()
                    ->where(function ($q) use ($normalizedName) {
                        $q->where('name', 'LIKE', "%{$normalizedName}%");
                    })
                    ->exists();

                if ($hasProduct) {
                    $tag = Tag::create([
                        'name' => $normalizedName,
                        'slug' => $slug,
                        'entity_type' => 'product',
                        'entity_id' => 0,
                        'is_active' => true,
                        'usage_count' => 1,
                    ]);
                } else {
                    abort(404);
                }
            }
        }

        $items = collect();
        $title = '';
        $description = '';

        if ($tag->entity_type === Product::class || $tag->entity_type === 'product') {
            $tagRecords = Tag::where('slug', $slug)
                ->active()
                ->where(function($q) {
                    $q->where('entity_type', Product::class)
                      ->orWhere('entity_type', 'product');
                })
                ->get(['id', 'entity_id']);

            $tagIds = $tagRecords->pluck('id')->all();
            $directProductIds = $tagRecords->where('entity_id', '>', 0)->pluck('entity_id')->all();

            $items = Product::active()
                ->with('primaryImage')
                ->where(function($q) use ($tagIds, $directProductIds) {
                    $hasCondition = false;
                    if (!empty($directProductIds)) {
                        $q->whereIn('id', $directProductIds);
                        $hasCondition = true;
                    }
                    foreach ($tagIds as $tId) {
                        if (!$hasCondition) {
                            $q->where(function($sub) use ($tId) {
                                $sub->whereJsonContains('tag_ids', (int) $tId)
                                    ->orWhereJsonContains('tag_ids', (string) $tId);
                            });
                            $hasCondition = true;
                        } else {
                            $q->orWhereJsonContains('tag_ids', (int) $tId)
                              ->orWhereJsonContains('tag_ids', (string) $tId);
                        }
                    }
                    if (!$hasCondition) {
                        $q->whereRaw('0 = 1');
                    }
                })
                ->orderByDesc('created_at')
                ->paginate(12)
                ->withQueryString();

            // Fallback: nếu sản phẩm không có nhưng lại có bài viết cùng tag, chuyển sang hiển thị bài viết
            if ($items->total() === 0) {
                $hasPost = Post::published()
                    ->where(function ($q) use ($tag) {
                        $q->where('meta_keywords', 'LIKE', "%{$tag->name}%")
                          ->orWhere('title', 'LIKE', "%{$tag->name}%");
                    })
                    ->exists();

                if ($hasPost) {
                    $tag->entity_type = 'post';
                }
            }

            $title = "Sản phẩm với tag: {$tag->name}";
            $description = $tag->description ?? "Danh sách sản phẩm được gắn tag {$tag->name}";
        }
        
        if ($tag->entity_type === Post::class || $tag->entity_type === 'post') {
            $tagRecords = Tag::where('slug', $slug)
                ->active()
                ->where(function($q) {
                    $q->where('entity_type', Post::class)
                      ->orWhere('entity_type', 'post');
                })
                ->get(['id', 'entity_id']);

            $tagIds = $tagRecords->pluck('id')->all();
            $directPostIds = $tagRecords->where('entity_id', '>', 0)->pluck('entity_id')->all();

            $items = Post::published()
                ->with(['author:id,name', 'author.profile', 'category:id,name,slug'])
                ->where(function($q) use ($tagIds, $directPostIds, $tag) {
                    $hasCondition = false;
                    if (!empty($directPostIds)) {
                        $q->whereIn('id', $directPostIds);
                        $hasCondition = true;
                    }
                    foreach ($tagIds as $tId) {
                        if (!$hasCondition) {
                            $q->where(function($sub) use ($tId) {
                                $sub->whereJsonContains('tag_ids', (int) $tId)
                                    ->orWhereJsonContains('tag_ids', (string) $tId);
                            });
                            $hasCondition = true;
                        } else {
                            $q->orWhereJsonContains('tag_ids', (int) $tId)
                              ->orWhereJsonContains('tag_ids', (string) $tId);
                        }
                    }
                    // Bổ sung tìm kiếm bài viết theo từ khóa trong meta_keywords và title
                    if (!empty($tag->name)) {
                        $keyword = trim($tag->name);
                        if (!$hasCondition) {
                            $q->where(function($sub) use ($keyword) {
                                $sub->where('meta_keywords', 'LIKE', "%{$keyword}%")
                                    ->orWhere('title', 'LIKE', "%{$keyword}%");
                            });
                            $hasCondition = true;
                        } else {
                            $q->orWhere('meta_keywords', 'LIKE', "%{$keyword}%")
                              ->orWhere('title', 'LIKE', "%{$keyword}%");
                        }
                    }
                    if (!$hasCondition) {
                        $q->whereRaw('0 = 1');
                    }
                })
                ->orderByDesc('published_at')
                ->paginate(12)
                ->withQueryString();

            $title = "Bài viết với tag: {$tag->name}";
            $description = $tag->description ?? "Danh sách bài viết được gắn tag {$tag->name}";
        }

        // SEO Meta
        $seoTitle = "Tag: {$tag->name} | " . config('app.name');
        $seoDescription = $tag->description ?? "Xem tất cả nội dung liên quan đến tag {$tag->name}";
        $seoKeywords = $tag->name;

        return view('clients.tags.show', [
            'tag' => $tag,
            'items' => $items,
            'title' => $title,
            'description' => $description,
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'seoKeywords' => $seoKeywords,
        ]);
    }

    /**
     * Hiển thị tags theo entity type
     */
    public function index(string $entityType, Request $request): View
    {
        $tags = Tag::where('entity_type', $entityType)
            ->active()
            ->orderBy('usage_count', 'desc')
            ->orderBy('name')
            ->paginate(24);

        $entityTypeLabel = match($entityType) {
            'product', Product::class => 'Sản phẩm',
            'post', Post::class => 'Bài viết',
            default => ucfirst($entityType),
        };

        return view('clients.tags.index', [
            'tags' => $tags,
            'entityType' => $entityType,
            'entityTypeLabel' => $entityTypeLabel,
        ]);
    }
}

