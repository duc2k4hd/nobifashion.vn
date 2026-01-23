<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SeoService
{
    public function generateSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        if (empty($baseSlug)) {
            $baseSlug = Str::slug(Str::random(8));
        }

        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = "{$baseSlug}-" . ++$counter;
        }

        return $slug;
    }

    public function hydratePost(Post $post): void
    {
        if (empty($post->slug) && !empty($post->title)) {
            $post->slug = $this->generateSlug($post->title, $post->id);
        } elseif (!empty($post->slug)) {
            $post->slug = $this->generateSlug($post->slug, $post->id);
        }

        if (empty($post->excerpt) && !empty($post->content)) {
            $post->excerpt = $this->generateExcerpt($post->content);
        }

        if (empty($post->meta_title) && !empty($post->title)) {
            $post->meta_title = Str::limit($post->title, 60);
        }

        if (empty($post->meta_description)) {
            $post->meta_description = Str::limit(strip_tags((string) $post->excerpt ?: (string) $post->content), 160);
        }

        if (empty($post->meta_keywords) && !empty($post->tag_ids)) {
            $tagNames = Tag::whereIn('id', Arr::wrap($post->tag_ids))->pluck('name')->all();
            $post->meta_keywords = implode(', ', $tagNames);
        }

        if (empty($post->meta_canonical) && $post->slug) {
            $post->meta_canonical = url("/blog/{$post->slug}");
        }
    }

    public function generateExcerpt(string $content, int $limit = 160): string
    {
        return Str::limit(Str::of(strip_tags($content))->squish(), $limit);
    }

    public function evaluateSeoScore(Post $post): array
    {
        $score = 100;
        $warnings = [];

        if (empty($post->meta_title)) {
            $score -= 10;
            $warnings[] = 'Thiếu meta title.';
        }

        $descriptionLength = Str::length($post->meta_description ?? '');
        if ($descriptionLength < 80 || $descriptionLength > 170) {
            $score -= 10;
            $warnings[] = 'Meta description nên nằm trong khoảng 80-170 ký tự.';
        }

        if (empty($post->thumbnail)) {
            $score -= 10;
            $warnings[] = 'Thiếu thumbnail cho social sharing.';
        }

        if (empty($post->tag_ids)) {
            $score -= 5;
            $warnings[] = 'Nên gán tag cho bài viết để tăng nội dung liên quan.';
        }

        if (empty($post->excerpt)) {
            $score -= 5;
            $warnings[] = 'Thiếu excerpt ngắn gọn.';
        }

        return [
            'score' => max(0, $score),
            'warnings' => $warnings,
        ];
    }

    public function analyzeContent(array $payload): array
    {
        $content = (string) Arr::get($payload, 'content', '');
        $wordCount = str_word_count(strip_tags($content));

        return [
            'word_count' => $wordCount,
            'read_time_minutes' => max(1, (int) ceil($wordCount / 250)),
            'keyword_density' => $this->calculateKeywordDensity($content, Arr::get($payload, 'focus_keyword')),
        ];
    }

    protected function calculateKeywordDensity(string $content, ?string $keyword): float
    {
        if (!$keyword) {
            return 0.0;
        }

        $plain = Str::lower(strip_tags($content));
        $keyword = Str::lower($keyword);
        $keywordCount = substr_count($plain, $keyword);
        $totalWords = str_word_count($plain);

        if ($totalWords === 0) {
            return 0.0;
        }

        return round(($keywordCount / $totalWords) * 100, 2);
    }

    protected function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Cache::remember("posts:slug-check:{$slug}:{$ignoreId}", 60, function () use ($slug, $ignoreId) {
            $query = Post::withTrashed()->where('slug', $slug);

            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }

            return $query->exists();
        });
    }
}


