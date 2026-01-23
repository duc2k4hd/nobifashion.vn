<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class NewsletterSendBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email_account_id' => ['nullable', 'integer', 'exists:email_accounts,id'],
            'template' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'filter_status' => ['nullable', 'string', 'in:all,subscribed'],
            'filter_source' => ['nullable', 'string', 'max:255'],
            'filter_date_from' => ['nullable', 'date'],
            'filter_date_to' => ['nullable', 'date', 'after_or_equal:filter_date_from'],
            'subscription_ids' => ['nullable', 'array'],
            'subscription_ids.*' => ['integer', 'exists:newsletter_subscriptions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'template.required' => 'Vui lòng chọn template email.',
            'subject.required' => 'Vui lòng nhập tiêu đề email.',
            'subject.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'filter_status.in' => 'Trạng thái lọc không hợp lệ.',
            'filter_date_to.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'subscription_ids.array' => 'Danh sách ID không hợp lệ.',
            'subscription_ids.*.exists' => 'Một hoặc nhiều ID không tồn tại.',
        ];
    }
}

