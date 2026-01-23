<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateGhnTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'in:Tư vấn,Hối Giao/Lấy/Trả hàng,Thay đổi thông tin,Khiếu nại'],
            'description' => ['required', 'string', 'max:2000'],
            'c_email' => ['nullable', 'email', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,pdf,xlsx,xls,csv', 'max:10240'], // Max 10MB
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'Vui lòng chọn loại ticket.',
            'category.in' => 'Loại ticket không hợp lệ.',
            'description.required' => 'Vui lòng nhập mô tả yêu cầu.',
            'description.max' => 'Mô tả không được vượt quá 2000 ký tự.',
            'c_email.email' => 'Email không hợp lệ.',
            'attachment.file' => 'File đính kèm không hợp lệ.',
            'attachment.mimes' => 'File đính kèm phải là: jpg, jpeg, png, gif, pdf, xlsx, xls, csv.',
            'attachment.max' => 'File đính kèm không được vượt quá 10MB.',
        ];
    }
}

