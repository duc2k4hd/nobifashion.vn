<?php

namespace App\Http\Requests\Admin\FlashSale;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Product;

class UpdateFlashSaleItemRequest extends FormRequest
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
        $item = $this->route('item');
        
        // Nếu Flash Sale đang chạy, không cho sửa
        if ($item && $item->flashSale && $item->flashSale->isActive()) {
            return [
                'is_active' => 'boolean', // Chỉ cho phép toggle
            ];
        }

        return [
            'original_price' => 'sometimes|required|numeric|min:0',
            'sale_price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $item = $this->route('item');
                    $originalPriceInput = $this->input('original_price');
                    $originalPrice = $originalPriceInput !== null && $originalPriceInput !== ''
                        ? (float) $originalPriceInput
                        : (float) ($item->original_price ?? 0);

                    $sale = (float) $value;

                    if ($originalPrice <= 0) {
                        return;
                    }

                    if ($sale >= $originalPrice) {
                        $fail('Giá Flash Sale phải nhỏ hơn giá gốc.');
                        return;
                    }
                    
                    if ($sale < $originalPrice * 0.1) {
                        $fail('Giá Flash Sale không được giảm quá 90%.');
                    }
                },
            ],
            'stock' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($item) {
                    if ($item && $item->product) {
                        if ($value > $item->product->stock_quantity) {
                            $fail("Số lượng Flash Sale ({$value}) không được vượt quá tồn kho thực ({$item->product->stock_quantity}).");
                        }
                    }
                },
            ],
            'max_per_user' => 'nullable|integer|min:1|max:10',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'reason' => 'nullable|string|max:500', // Lý do thay đổi giá
        ];
    }
}
