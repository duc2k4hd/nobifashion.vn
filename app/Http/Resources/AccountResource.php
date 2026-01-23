<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'is_active' => (bool) $this->is_active,
            'account_status' => $this->account_status,
            'email_verified_at' => optional($this->email_verified_at)->toDateTimeString(),
            'last_password_changed_at' => optional($this->last_password_changed_at)->toDateTimeString(),
            'login_attempts' => $this->login_attempts,
            'security_flags' => $this->security_flags,
            'login_history' => $this->login_history,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
            'profile' => new ProfileResource($this->whenLoaded('profile')),
        ];
    }
}
