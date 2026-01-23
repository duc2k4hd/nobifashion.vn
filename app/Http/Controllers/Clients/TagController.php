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
            ->firstOrFail();

        $items = collect();
        $title = '';
        $description = '';

        if ($tag->entity_type === Product::class || $tag->entity_type === 'product') {
            // Lấy tất cả tags có cùng slug và entity_type = product
            $productIds = Tag::where('slug', $slug)
                ->where(function($q) {
                    $q->where('entity_type', Product::class)
                      ->orWhere('entity_type', 'product');
                })
                ->pluck('entity_id')
                ->unique();

            $items = Product::whereIn('id', $productIds)
                ->where('status', 'active')
                ->orderByDesc('created_at')
                ->paginate(12);

            $title = "Sản phẩm với tag: {$tag->name}";
            $description = $tag->description ?? "Danh sách sản phẩm được gắn tag {$tag->name}";
        } elseif ($tag->entity_type === Post::class || $tag->entity_type === 'post') {
            // Lấy tất cả tags có cùng slug và entity_type = post
            $postIds = Tag::where('slug', $slug)
                ->where(function($q) {
                    $q->where('entity_type', Post::class)
                      ->orWhere('entity_type', 'post');
                })
                ->pluck('entity_id')
                ->unique();

            $items = Post::whereIn('id', $postIds)
                ->where('status', 'published')
                ->orderByDesc('created_at')
                ->paginate(12);

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

