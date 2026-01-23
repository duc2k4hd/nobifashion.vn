<?php

namespace App\Http\Requests\Admin\FlashSale;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class UpdateFlashSaleRequest extends FormRequest
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
        
        // Nếu Flash Sale đang chạy, chỉ cho phép toggle is_active
        if ($flashSale && $flashSale->isActive()) {
            return [
                'is_active' => 'boolean',
            ];
        }

        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'tag' => 'nullable|string|max:50',
            'start_time' => [
                'sometimes',
                'required',
                'date',
            ],
            'end_time' => [
                'sometimes',
                'required',
                'date',
                'after:start_time',
                function ($attribute, $value, $fail) {
                    $startTime = $this->input('start_time');
                    if ($startTime) {
                        $start = Carbon::parse($startTime);
                        $end = Carbon::parse($value);
                        
                        // Đảm bảo end > start
                        if ($end->lte($start)) {
                            $fail('Thời gian kết thúc phải sau thời gian bắt đầu.');
                            return;
                        }
                        
                        // Thời lượng tối thiểu: 1 giờ (tính bằng giây rồi chia 3600 để chính xác)
                        // Dùng abs() để đảm bảo luôn dương
                        $totalSeconds = abs($end->diffInSeconds($start));
                        $totalHours = $totalSeconds / 3600;
                        if ($totalHours < 1) {
                            $fail('Thời lượng Flash Sale phải ít nhất 1 giờ.');
                        }
                        
                        // Thời lượng tối đa: 30 ngày
                        if ($end->diffInDays($start) > 30) {
                            $fail('Thời lượng Flash Sale không được vượt quá 30 ngày.');
                        }
                    }
                },
            ],
            'status' => 'sometimes|required|in:draft,active,expired',
            'is_active' => 'boolean',
            'max_per_user' => 'nullable|integer|min:1|max:100',
            'display_limit' => 'nullable|integer|min:1|max:100',
            'product_add_mode' => [
                'sometimes',
                'required',
                'in:auto_by_category,manual',
                function ($attribute, $value, $fail) use ($flashSale) {
                    // Không cho phép thay đổi mode nếu đã có sản phẩm
                    if ($flashSale && $flashSale->items()->count() > 0 && $flashSale->product_add_mode !== $value) {
                        $fail('Không thể thay đổi chế độ thêm sản phẩm sau khi đã thêm sản phẩm.');
                    }
                },
            ],
        ];
    }
}
