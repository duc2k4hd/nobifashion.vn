<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReplyGhnTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        return [
            'ticket_id' => ['required', 'integer'],
            'description' => ['required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,pdf,xlsx,xls,csv', 'max:10240'], // Max 10MB
        ];
    }

    public function messages(): array
    {
        return [
            'ticket_id.required' => 'Mã ticket là bắt buộc.',
            'ticket_id.integer' => 'Mã ticket không hợp lệ.',
            'description.required' => 'Vui lòng nhập nội dung phản hồi.',
            'description.max' => 'Nội dung phản hồi không được vượt quá 2000 ký tự.',
            'attachment.file' => 'File đính kèm không hợp lệ.',
            'attachment.mimes' => 'File đính kèm phải là: jpg, jpeg, png, gif, pdf, xlsx, xls, csv.',
            'attachment.max' => 'File đính kèm không được vượt quá 10MB.',
        ];
    }
}

