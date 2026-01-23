<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TagUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Sẽ được kiểm tra bởi Policy
    }

    public function rules(): array
    {
        $tagId = $this->route('tag')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('tags', 'slug')->ignore($tagId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'entity_type' => ['sometimes', 'required', 'string', 'in:product,post,' . \App\Models\Product::class . ',' . \App\Models\Post::class],
            'entity_id' => ['sometimes', 'required', 'integer'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Validate entity_id phải tồn tại trong entity_type (nếu có thay đổi)
        if ($this->has('entity_type') && $this->has('entity_id')) {
            $entityType = $this->entity_type;
            $entityId = $this->entity_id;

            // Normalize entity_type
            if ($entityType === 'product') {
                $entityType = \App\Models\Product::class;
            } elseif ($entityType === 'post') {
                $entityType = \App\Models\Post::class;
            }

            // Kiểm tra entity có tồn tại không
            if ($entityType === \App\Models\Product::class) {
                $exists = \App\Models\Product::where('id', $entityId)->exists();
            } elseif ($entityType === \App\Models\Post::class) {
                $exists = \App\Models\Post::where('id', $entityId)->exists();
            } else {
                $exists = false;
            }

            if (!$exists) {
                $this->merge(['entity_id' => null]);
            }
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên tag là bắt buộc',
            'slug.unique' => 'Slug đã tồn tại',
            'entity_type.required' => 'Loại entity là bắt buộc',
            'entity_type.in' => 'Loại entity không hợp lệ',
            'entity_id.required' => 'ID entity là bắt buộc',
            'entity_id.integer' => 'ID entity phải là số',
        ];
    }
}

