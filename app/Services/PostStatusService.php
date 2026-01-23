<?php

namespace App\Services;

use App\Models\Post;
use Carbon\Carbon;

class PostStatusService
{
    public function publish(Post $post, ?Carbon $scheduleAt = null): Post
    {
        if ($scheduleAt && $scheduleAt->isFuture()) {
            $post->status = 'pending';
            $post->published_at = $scheduleAt;
        } else {
            $post->status = 'published';
            $post->published_at = $scheduleAt?->isPast() ? $scheduleAt : now();
        }

        $post->save();

        return $post;
    }

    public function archive(Post $post): Post
    {
        $post->status = 'archived';
        $post->save();

        return $post;
    }

    public function markDraft(Post $post): Post
    {
        $post->status = 'draft';
        $post->published_at = null;
        $post->save();

        return $post;
    }

    public function toggleFeatured(Post $post, bool $featured): Post
    {
        $post->is_featured = $featured;
        $post->save();

        return $post;
    }
}


