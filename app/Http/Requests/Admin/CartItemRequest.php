<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],
            'product_variant_id' => [
                'nullable',
                'integer',
                'exists:product_variants,id',
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('product_variant_id') && $this->product_variant_id) {
                $variant = \App\Models\ProductVariant::find($this->product_variant_id);
                if ($variant && $variant->product_id != $this->product_id) {
                    $validator->errors()->add('product_variant_id', 'Biến thể không thuộc sản phẩm này.');
                }
            }

            if ($this->has('quantity')) {
                $stock = $this->getStockQuantity();
                if ($stock < $this->quantity) {
                    $validator->errors()->add('quantity', "Chỉ còn {$stock} sản phẩm trong kho.");
                }
            }
        });
    }

    private function getStockQuantity(): int
    {
        if ($this->product_variant_id) {
            $variant = \App\Models\ProductVariant::find($this->product_variant_id);
            return $variant ? (int) $variant->stock_quantity : 0;
        }

        $product = \App\Models\Product::find($this->product_id);
        return $product ? (int) $product->stock_quantity : 0;
    }
}

