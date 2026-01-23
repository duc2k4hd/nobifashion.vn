<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        $routeProduct = optional(request()->route('product'));
        $productId = $routeProduct ? $routeProduct->id : null;

        return [
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable'],
            'meta_canonical' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'primary_category_id' => ['nullable', 'exists:categories,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'tag_names' => ['nullable', 'string', 'max:500'],
            'is_featured' => ['nullable', 'boolean'],
            'has_variants' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],

            'images' => ['nullable', 'array'],
            'images.*.id' => ['nullable', 'integer', 'exists:images,id'],
            'images.*.title' => ['nullable', 'string', 'max:255'],
            'images.*.notes' => ['nullable', 'string'],
            'images.*.alt' => ['nullable', 'string', 'max:255'],
            'images.*.is_primary' => ['nullable', 'boolean'],
            'images.*.order' => ['nullable', 'integer', 'min:0'],
            'images.*.file' => ['nullable', 'file', 'image', 'max:5120'],
            'images.*.path' => ['nullable', 'string'],

            'variants' => ['nullable', 'array'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.attributes' => ['nullable', 'array'],
            'variants.*.attributes.*.key' => ['nullable', 'string', 'max:100'],
            'variants.*.attributes.*.value' => ['nullable', 'string', 'max:255'],
            'variants.*.image_id' => ['nullable', 'integer', 'exists:images,id'],
            'variants.*.status' => ['nullable', 'in:active,inactive'],

            'faqs' => ['nullable', 'array'],
            'faqs.*.id' => ['nullable', 'integer', 'exists:product_faqs,id'],
            'faqs.*.question' => ['required_with:faqs', 'string'],
            'faqs.*.answer' => ['nullable', 'string'],
            'faqs.*.order' => ['nullable', 'integer'],

            'how_tos' => ['nullable', 'array'],
            'how_tos.*.id' => ['nullable', 'integer', 'exists:product_how_tos,id'],
            'how_tos.*.title' => ['required_with:how_tos', 'string'],
            'how_tos.*.description' => ['nullable', 'string'],
            'how_tos.*.steps' => ['nullable'],
            'how_tos.*.supplies' => ['nullable'],
            'how_tos.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Xử lý tag_ids: loại bỏ giá trị rỗng và convert sang integer
        $tagIds = collect($this->input('tag_ids', []))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();

        $this->merge([
            'tag_ids' => $tagIds,
        ]);
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        $data['is_featured'] = (bool)($data['is_featured'] ?? false);
        $data['has_variants'] = (bool)($data['has_variants'] ?? false);
        $data['is_active'] = (bool)($data['is_active'] ?? true);

        // Validate total stock matches sum of variants stock
        if (isset($data['stock_quantity']) && !empty($data['variants']) && is_array($data['variants'])) {
            $variantStock = 0;
            foreach ($data['variants'] as $variant) {
                if (array_key_exists('stock_quantity', $variant) && $variant['stock_quantity'] !== null && $variant['stock_quantity'] !== '') {
                    $variantStock += (int) $variant['stock_quantity'];
                }
            }

            if ($variantStock > 0 && (int) $data['stock_quantity'] !== $variantStock) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'stock_quantity' => 'Tồn kho tổng phải bằng tổng tồn kho của các biến thể (' . $variantStock . ').',
                ]);
            }
        }

        return $data;
    }
}

