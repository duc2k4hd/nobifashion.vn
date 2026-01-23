<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class OrderShippingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        $statuses = implode(',', array_keys(config('ghn.shipping_statuses', [])));

        return [
            'status' => ["required", "in:{$statuses}"],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

