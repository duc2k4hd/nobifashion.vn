<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostService
{
    public function __construct(
        protected SeoService $seoService,
        protected PostStatusService $statusService,
    ) {
    }

    public function create(array $payload, Account $author): Post
    {
        return DB::transaction(function () use ($payload, $author) {
            $data = $this->preparePayload($payload);
            $data['created_by'] = $author->id;
            $data['account_id'] = $payload['account_id'] ?? $author->id;

            $tagIds = $data['tag_ids'] ?? [];
            $tagNames = $data['tag_names'] ?? null;
            unset($data['tag_ids'], $data['tag_names']); // Không lưu vào cột tag_ids nữa

            $post = new Post($data);
            $post->save();

            $this->syncTags($post, $tagIds, $tagNames);
            $this->handleStatusTransition($post, $payload);

            return $post->refresh();
        });
    }

    public function update(Post $post, array $payload, Account $editor): Post
    {
        return DB::transaction(function () use ($post, $payload, $editor) {
            $data = $this->preparePayload($payload, $post);
            
            $tagIds = $data['tag_ids'] ?? [];
            $tagNames = $data['tag_names'] ?? null;
            unset($data['tag_ids'], $data['tag_names']); // Không lưu vào cột tag_ids nữa
            
            $post->fill($data);
            $post->account_id = $payload['account_id'] ?? $post->account_id ?? $editor->id;
            $post->save();

            $this->syncTags($post, $tagIds, $tagNames);
            $this->handleStatusTransition($post, $payload);
            $this->recordRevision($post, $editor);

            return $post->refresh();
        });
    }

    public function autosave(Post $post, array $payload, Account $editor): PostRevision
    {
        return $this->recordRevision($post, $editor, true, $payload);
    }

    public function restoreRevision(Post $post, PostRevision $revision, Account $editor): Post
    {
        return DB::transaction(function () use ($post, $revision, $editor) {
            $post->fill([
                'title' => $revision->title ?? $post->title,
                'excerpt' => $revision->excerpt ?? $post->excerpt,
                'content' => $revision->content ?? $post->content,
                'meta_title' => $revision->meta['meta_title'] ?? $post->meta_title,
                'meta_description' => $revision->meta['meta_description'] ?? $post->meta_description,
                'meta_keywords' => $revision->meta['meta_keywords'] ?? $post->meta_keywords,
            ]);
            $post->save();

            PostRevision::create([
                'post_id' => $post->id,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'meta' => [
                    'meta_title' => $post->meta_title,
                    'meta_description' => $post->meta_description,
                    'meta_keywords' => $post->meta_keywords,
                ],
                'edited_by' => $editor->id,
                'is_autosave' => false,
            ]);

            return $post->refresh();
        });
    }

    public function duplicate(Post $post, Account $actor): Post
    {
        return DB::transaction(function () use ($post, $actor) {
            $clone = $post->replicate([
                'views',
                'slug',
                'published_at',
                'status',
                'is_featured',
            ]);

            $clone->title = "{$post->title} (Copy)";
            $clone->slug = $this->seoService->generateSlug("{$post->slug}-copy");
            $clone->status = 'draft';
            $clone->is_featured = false;
            $clone->views = 0;
            $clone->created_by = $actor->id;
            $clone->published_at = null;
            $clone->save();

            return $clone;
        });
    }

    public function incrementViews(Post $post, Request $request, int $cooldownMinutes = 15): void
    {
        $cacheKey = sprintf(
            'post:view:%s:%s',
            $post->id,
            sha1($request->ip() . '|' . $request->userAgent())
        );

        if (!Cache::has($cacheKey)) {
            $post->increment('views');
            Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
        }
    }

    protected function preparePayload(array $payload, ?Post $post = null): array
    {
        $data = Arr::only($payload, [
            'title',
            'slug',
            'meta_title',
            'meta_description',
            'meta_keywords',
            'meta_canonical',
            'excerpt',
            'content',
            'thumbnail',
            'thumbnail_alt_text',
            'status',
            'is_featured',
            'category_id',
            'tag_ids',
            'tag_names', // Thêm tag_names vào đây
            'published_at',
        ]);

        if (array_key_exists('is_featured', $payload)) {
            $data['is_featured'] = (bool) $payload['is_featured'];
        } else {
            $data['is_featured'] = $post?->is_featured ?? false;
        }

        // Xử lý tags: có thể từ tag_ids (dropdown) hoặc tag_names (input mới)
        $tagIds = [];
        
        // Lấy tag IDs từ dropdown
        if (array_key_exists('tag_ids', $payload)) {
            $tagIds = array_filter(array_map('intval', Arr::wrap($payload['tag_ids'])));
        } else {
            // Lấy tag IDs từ relationship nếu có post
            $tagIds = $post ? $post->tags()->pluck('id')->toArray() : [];
        }
        
        // Lưu tag_names để xử lý sau trong syncTags
        $data['tag_names'] = $payload['tag_names'] ?? null;
        
        $data['tag_ids'] = array_values(array_unique($tagIds));

        if (!empty($data['slug'])) {
            $data['slug'] = $this->seoService->generateSlug($data['slug'], $post?->id);
        } elseif (!empty($data['title'])) {
            $data['slug'] = $this->seoService->generateSlug($data['title'], $post?->id);
        }

        if (empty($data['excerpt']) && !empty($data['content'])) {
            $data['excerpt'] = $this->seoService->generateExcerpt($data['content']);
        }

        return $data;
    }

    protected function handleStatusTransition(Post $post, array $payload): void
    {
        $status = Arr::get($payload, 'status', $post->status);
        $publishAt = Arr::get($payload, 'published_at');
        $schedule = $publishAt ? Carbon::parse($publishAt) : null;

        if ($status === 'published') {
            $this->statusService->publish($post, $schedule);
        } elseif ($status === 'archived') {
            $this->statusService->archive($post);
        } elseif ($status === 'draft') {
            $this->statusService->markDraft($post);
        } elseif ($schedule) {
            $this->statusService->publish($post, $schedule);
        }
    }

    protected function recordRevision(Post $post, ?Account $editor = null, bool $isAutosave = false, ?array $payload = null): PostRevision
    {
        $payload = $payload ?? [];

        return PostRevision::create([
            'post_id' => $post->id,
            'title' => Arr::get($payload, 'title', $post->title),
            'excerpt' => Arr::get($payload, 'excerpt', $post->excerpt),
            'content' => Arr::get($payload, 'content', $post->content),
            'meta' => [
                'meta_title' => Arr::get($payload, 'meta_title', $post->meta_title),
                'meta_description' => Arr::get($payload, 'meta_description', $post->meta_description),
                'meta_keywords' => Arr::get($payload, 'meta_keywords', $post->meta_keywords),
            ],
            'edited_by' => $editor?->id,
            'is_autosave' => $isAutosave,
        ]);
    }

    /**
     * Sync tags cho post vào tags table với entity_type = 'App\Models\Post'
     * Mỗi tag sẽ được tạo với entity_id = post->id và entity_type = Post::class
     * 
     * @param Post $post
     * @param array $tagIds Tag IDs từ dropdown
     * @param string|null $tagNames Tag names từ input (phân cách bằng dấu phẩy)
     */
    protected function syncTags(Post $post, array $tagIds, ?string $tagNames = null): void
    {
        // Xóa tất cả tags cũ của post này
        Tag::where('entity_type', Post::class)
            ->where('entity_id', $post->id)
            ->delete();

        // Xử lý tag names từ input (tags mới)
        $allTagNames = [];
        if (!empty($tagNames)) {
            $newTagNames = $this->parseTagNames($tagNames);
            $allTagNames = array_merge($allTagNames, $newTagNames);
        }

        // Nếu không có tagIds và không có tagNames, xóa hết tags
        if (empty($tagIds) && empty($allTagNames)) {
            $post->tag_ids = [];
            $post->saveQuietly();
            return;
        }

        // Lấy thông tin tags từ posts (entity_type = Post::class)
        // Chỉ lấy tags của posts, không lấy tags của products
        $existingTags = [];
        if (!empty($tagIds)) {
            $existingTags = Tag::whereIn('id', $tagIds)
                ->where('entity_type', Post::class) // Chỉ lấy tags của posts
                ->select('id', 'name', 'slug', 'description', 'is_active')
                ->get()
                ->unique('name') // Lấy unique theo name để tránh duplicate
                ->keyBy('id');
            
            // Lấy thêm tag names từ existing tags
            foreach ($existingTags as $tag) {
                $allTagNames[] = $tag->name;
            }
        }

        // Loại bỏ duplicate và tạo tags
        $allTagNames = array_unique(array_map('trim', $allTagNames));
        $createdTagIds = [];
        
        foreach ($allTagNames as $tagName) {
            if (empty($tagName)) {
                continue;
            }
            
            // Kiểm tra xem tag đã có với entity_id = post->id chưa
            $existingPostTag = Tag::where('entity_type', Post::class)
                ->where('entity_id', $post->id)
                ->where('name', $tagName)
                ->first();
            
            if ($existingPostTag) {
                // Nếu đã tồn tại, dùng tag đó
                $createdTagIds[] = $existingPostTag->id;
                continue;
            }
            
            // Tìm tag template (có thể từ posts khác hoặc mới tạo)
            $templateTag = Tag::where('entity_type', Post::class)
                ->where('name', $tagName)
                ->first();
            
            // Tạo tag mới với entity_type và entity_id cho post này
            $baseSlug = Str::slug($tagName);
            $uniqueSlug = $baseSlug . '-post-' . $post->id;
            
            // Đảm bảo slug unique
            $counter = 1;
            while (Tag::where('slug', $uniqueSlug)->exists()) {
                $uniqueSlug = $baseSlug . '-post-' . $post->id . '-' . $counter;
                $counter++;
            }

            $newTag = Tag::create([
                'name' => $tagName,
                'slug' => $uniqueSlug,
                'description' => $templateTag->description ?? null,
                'is_active' => $templateTag->is_active ?? true,
                'usage_count' => 0, // Reset usage count cho tag mới
                'entity_id' => $post->id,
                'entity_type' => Post::class,
            ]);
            $createdTagIds[] = $newTag->id;
        }

        // Cập nhật lại tag_ids trong posts table để backward compatibility
        // Lưu IDs của tags vừa tạo
        $post->tag_ids = $createdTagIds;
        $post->saveQuietly(); // Save without triggering events
    }

    /**
     * Parse tag names từ string (phân cách bằng dấu phẩy)
     */
    protected function parseTagNames(string $tagNames): array
    {
        return array_filter(
            array_map('trim', explode(',', $tagNames)),
            fn($name) => !empty($name)
        );
    }
}


