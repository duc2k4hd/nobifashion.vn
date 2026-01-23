<?php

namespace App\Http\Requests\Admin\FlashSale;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Product;

class StoreFlashSaleItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $flashSale = $this->route('flash_sale');
        
        return [
            'product_id' => [
                'required',
                'exists:products,id',
                function ($attribute, $value, $fail) use ($flashSale) {
                    // Kiểm tra không trùng lặp
                    if ($flashSale && $flashSale->items()->where('product_id', $value)->exists()) {
                        $fail('Sản phẩm này đã có trong Flash Sale.');
                    }
                },
            ],
            'original_price' => 'required|numeric|min:0',
            'sale_price' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $originalPrice = $this->input('original_price');
                    if ($value >= $originalPrice) {
                        $fail('Giá Flash Sale phải nhỏ hơn giá gốc.');
                    }
                    
                    // Giá Flash Sale phải >= 10% giá gốc (giảm tối đa 90%)
                    if ($value < $originalPrice * 0.1) {
                        $fail('Giá Flash Sale không được giảm quá 90%.');
                    }
                },
            ],
            'stock' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    $productId = $this->input('product_id');
                    if ($productId) {
                        $product = Product::find($productId);
                        if ($product && $value > $product->stock_quantity) {
                            $fail("Số lượng Flash Sale ({$value}) không được vượt quá tồn kho thực ({$product->stock_quantity}).");
                        }
                    }
                },
            ],
            'max_per_user' => 'nullable|integer|min:1|max:10',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'product_variant_id' => 'nullable|exists:product_variants,id',
        ];
    }
}
