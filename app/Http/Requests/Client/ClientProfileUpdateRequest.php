<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class ClientProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['nullable', 'string', 'max:120'],
            'nickname' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:500'],
            'gender' => ['nullable', 'in:male,female,other'],
            'birthday' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:20'],
            'location' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,avif', 'max:4096'],
            'sub_avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,avif', 'max:4096'],
            'remove_avatar' => ['nullable', 'boolean'],
            'remove_sub_avatar' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.image' => 'Ảnh đại diện phải là file ảnh.',
            'avatar.max' => 'Ảnh đại diện không được vượt quá 4MB.',
            'sub_avatar.image' => 'Ảnh nền phải là file ảnh.',
            'sub_avatar.max' => 'Ảnh nền không được vượt quá 4MB.',
        ];
    }
}

