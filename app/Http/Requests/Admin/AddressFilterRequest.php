<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AddressFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth('web')->user();
        return $user && method_exists($user, 'isAdmin') && $user->isAdmin();
    }

    public function rules(): array
    {
        return [
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'province' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'address_type' => ['nullable', 'in:home,work'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}

