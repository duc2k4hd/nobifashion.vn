<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CommentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
            'is_approved' => ['sometimes', 'boolean'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }
}


