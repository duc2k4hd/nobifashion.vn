<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ContactReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx', 'max:10240'], // 10MB
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Vui lòng nhập nội dung trả lời.',
            'message.min' => 'Nội dung trả lời phải có ít nhất 10 ký tự.',
            'message.max' => 'Nội dung trả lời không được vượt quá 5000 ký tự.',
            'attachment.file' => 'File đính kèm phải là một tệp tin.',
            'attachment.mimes' => 'File đính kèm phải có định dạng: jpg, jpeg, png, gif, pdf, doc, docx, xls, xlsx.',
            'attachment.max' => 'File đính kèm không được vượt quá 10MB.',
        ];
    }
}

