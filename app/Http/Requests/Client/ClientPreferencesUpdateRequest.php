<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class ClientPreferencesUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 通知偏好
            'notify_order_created' => ['nullable', 'boolean'],
            'notify_order_updated' => ['nullable', 'boolean'],
            'notify_order_shipped' => ['nullable', 'boolean'],
            'notify_order_completed' => ['nullable', 'boolean'],
            'notify_promotions' => ['nullable', 'boolean'],
            'notify_flash_sale' => ['nullable', 'boolean'],
            'notify_new_products' => ['nullable', 'boolean'],
            'notify_stock_alert' => ['nullable', 'boolean'],
            'notify_security' => ['nullable', 'boolean'],
            'notify_via_email' => ['nullable', 'boolean'],
            'notify_via_sms' => ['nullable', 'boolean'],
            'notify_via_in_app' => ['nullable', 'boolean'],
            // 隐私设置
            'show_order_history' => ['nullable', 'boolean'],
            'show_favorites' => ['nullable', 'boolean'],
            // 偏好设置
            'preferred_language' => ['nullable', 'string', 'in:vi,en,zh,ja'],
            'preferred_timezone' => ['nullable', 'string', 'max:50'],
            'preferred_currency' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'preferred_language.in' => 'Ngôn ngữ không hợp lệ.',
            'preferred_timezone.max' => 'Múi giờ không hợp lệ.',
            'preferred_currency.max' => 'Tiền tệ không hợp lệ.',
        ];
    }
}

