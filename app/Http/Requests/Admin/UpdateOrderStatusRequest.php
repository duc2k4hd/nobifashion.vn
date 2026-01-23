<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,processing,completed,cancelled'],
            'payment_status' => ['nullable', 'in:pending,paid,failed'],
            'delivery_status' => ['nullable', 'in:pending,shipped,delivered,returned'],
        ];
    }
}

