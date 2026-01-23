<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt_text,
            'thumbnail' => $this->thumbnail ? asset($this->thumbnail) : null,
            'category' => $this->category?->only(['id', 'name', 'slug']),
            'author' => $this->author?->only(['id', 'name']),
            'published_at' => optional($this->published_at)->toIso8601String(),
            'views' => $this->views,
            'is_featured' => (bool) $this->is_featured,
        ];
    }
}

