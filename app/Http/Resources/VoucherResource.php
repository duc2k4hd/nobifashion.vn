<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'value' => (float) $this->value,
            'value_label' => $this->value_label,
            'status' => $this->status,
            'status_badge' => $this->status_badge,
            'usage_limit' => $this->usage_limit,
            'usage_count' => $this->usage_count,
            'per_user_limit' => $this->per_user_limit,
            'min_order_amount' => $this->min_order_amount,
            'max_discount_amount' => $this->max_discount_amount,
            'applicable_to' => $this->applicable_to,
            'applicable_ids' => $this->applicable_ids,
            'start_at' => optional($this->start_at)->toIso8601String(),
            'end_at' => optional($this->end_at)->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}


