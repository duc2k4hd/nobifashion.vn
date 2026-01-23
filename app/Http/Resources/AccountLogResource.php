<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountLogResource extends JsonResource
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
            'type' => $this->type,
            'account_id' => $this->account_id,
            'admin_id' => $this->admin_id,
            'admin_name' => $this->admin?->displayName(),
            'payload' => $this->payload,
            'ip' => $this->ip,
            'user_agent' => $this->user_agent,
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
