<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'full_name' => $this->full_name,
            'nickname' => $this->nickname,
            'avatar' => $this->avatar ? asset('admins/img/accounts/' . ltrim($this->avatar, '/')) : null,
            'sub_avatar' => $this->sub_avatar ? asset('admins/img/accounts/' . ltrim($this->sub_avatar, '/')) : null,
            'bio' => $this->bio,
            'gender' => $this->gender,
            'birthday' => optional($this->birthday)->toDateString(),
            'location' => $this->location,
            'phone' => $this->phone,
            'is_public' => (bool) $this->is_public,
            'avatar_history' => $this->avatar_history ?? [],
            'sub_avatar_history' => $this->sub_avatar_history ?? [],
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}