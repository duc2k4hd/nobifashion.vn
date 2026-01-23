<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public API
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx', 'max:10240'], // 10MB
            'source' => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Tự động set source nếu không có
        if (!$this->has('source')) {
            $this->merge([
                'source' => 'web',
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên của bạn.',
            'name.max' => 'Tên không được vượt quá 255 ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'subject.required' => 'Vui lòng nhập chủ đề.',
            'subject.max' => 'Chủ đề không được vượt quá 255 ký tự.',
            'message.required' => 'Vui lòng nhập nội dung liên hệ.',
            'message.min' => 'Nội dung phải có ít nhất 10 ký tự.',
            'message.max' => 'Nội dung không được vượt quá 5000 ký tự.',
            'attachment.file' => 'File đính kèm phải là một tệp tin.',
            'attachment.mimes' => 'File đính kèm phải có định dạng: jpg, jpeg, png, gif, pdf, doc, docx, xls, xlsx.',
            'attachment.max' => 'File đính kèm không được vượt quá 10MB.',
        ];
    }
}

