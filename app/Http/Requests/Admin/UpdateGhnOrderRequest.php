<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGhnOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        return [
            'to_name' => ['required', 'string', 'max:255'],
            'to_phone' => ['required', 'string', 'max:20'],
            'to_address' => ['required', 'string', 'max:500'],
            'to_province_id' => ['nullable', 'integer'],
            'to_ward_code' => ['required', 'string'],
            'to_district_id' => ['required', 'integer'],
            'payment_type_id' => ['required', 'in:1,2'],
            'required_note' => ['required', 'in:CHOTHUHANG,CHOXEMHANGKHONGTHU,KHONGCHOXEMHANG'],
            'note' => ['nullable', 'string', 'max:1000'],
            'cod_amount' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'integer', 'min:0'],
            'length' => ['nullable', 'integer', 'min:0'],
            'width' => ['nullable', 'integer', 'min:0'],
            'height' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

