<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        return [
            // Code không thể sửa, chỉ có thể sửa status
            'status' => [
                'required',
                'in:active,ordered,abandoned',
            ],
        ];
    }
}

