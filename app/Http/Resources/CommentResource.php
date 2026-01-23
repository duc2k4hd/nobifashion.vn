<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'guest_name' => $this->guest_name,
            'guest_email' => $this->guest_email,
            'content' => $this->content,
            'rating' => $this->rating,
            'is_approved' => $this->is_approved,
            'is_reported' => $this->is_reported,
            'reports_count' => $this->reports_count,
            'parent_id' => $this->parent_id,
            'commentable_type' => $this->commentable_type,
            'commentable_id' => $this->commentable_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'account' => $this->whenLoaded('account', function () {
                return [
                    'id' => $this->account->id,
                    'name' => $this->account->name,
                    'email' => $this->account->email,
                ];
            }),
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
        ];
    }
}


