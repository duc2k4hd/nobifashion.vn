<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        $routeCategory = optional(request()->route('category'));
        $categoryId = $routeCategory ? $routeCategory->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'image' => ['nullable', 'file', 'image', 'max:4096'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string'],
            'meta_canonical' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'delete_image' => ['nullable', 'boolean'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        $data['is_active'] = (bool)($data['is_active'] ?? true);

        if (!isset($data['sort_order']) && $this->filled('order')) {
            $data['sort_order'] = (int)$this->input('order');
        }

        if ($this->has('metadata') && is_array($this->input('metadata'))) {
            $meta = $this->input('metadata');
            if (empty($data['meta_title']) && !empty($meta['meta_title'])) {
                $data['meta_title'] = $meta['meta_title'];
            }
            if (empty($data['meta_description']) && !empty($meta['meta_description'])) {
                $data['meta_description'] = $meta['meta_description'];
            }
            if (empty($data['meta_keywords']) && !empty($meta['meta_keywords'])) {
                $data['meta_keywords'] = $meta['meta_keywords'];
            }
            if (empty($data['meta_canonical']) && !empty($meta['meta_canonical'])) {
                $data['meta_canonical'] = $meta['meta_canonical'];
            }
        }

        return $data;
    }
}


