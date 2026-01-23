<?php

namespace App\Http\Requests\Admin\FlashSale;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class StoreFlashSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Sẽ check permission trong controller hoặc middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'tag' => 'nullable|string|max:50',
            'start_time' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    // Nếu status = draft, cho phép start_time trong quá khứ
                    // Nếu status = active, start_time phải >= now() hoặc đã bắt đầu
                    if ($this->input('status') === 'active') {
                        if (Carbon::parse($value) < now()->subMinute()) {
                            $fail('Thời gian bắt đầu phải là thời gian tương lai khi xuất bản.');
                        }
                    }
                },
            ],
            'end_time' => [
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
            'status' => 'required|in:draft,active,expired',
            'is_active' => 'boolean',
            'max_per_user' => 'nullable|integer|min:1|max:100',
            'display_limit' => 'nullable|integer|min:1|max:100',
            'product_add_mode' => 'required|in:auto_by_category,manual',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Tên chương trình Flash Sale là bắt buộc.',
            'start_time.required' => 'Thời gian bắt đầu là bắt buộc.',
            'end_time.required' => 'Thời gian kết thúc là bắt buộc.',
            'end_time.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
        ];
    }
}
