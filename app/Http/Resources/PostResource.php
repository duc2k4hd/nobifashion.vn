<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt_text,
            'thumbnail' => $this->thumbnail ? asset($this->thumbnail) : null,
            'thumbnail_alt_text' => $this->thumbnail_alt_text,
            'meta' => [
                'title' => $this->meta_title,
                'description' => $this->meta_description,
                'keywords' => $this->meta_keywords,
                'canonical' => $this->meta_canonical,
            ],
            'tags' => $this->tag_ids ?? [],
            'category' => $this->category?->only(['id', 'name', 'slug']),
            'author' => $this->author?->only(['id', 'name']),
            'published_at' => optional($this->published_at)->toIso8601String(),
            'views' => $this->views,
            'is_featured' => (bool) $this->is_featured,
        ];
    }
}
