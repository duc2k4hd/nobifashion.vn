<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderFromCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        return [
            'receiver_name' => ['required', 'string', 'max:255'],
            'receiver_phone' => ['required', 'string', 'max:20'],
            'receiver_email' => ['nullable', 'email', 'max:255'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_province_id' => ['required', 'integer'],
            'shipping_district_id' => ['required', 'integer'],
            'shipping_ward_id' => ['required', 'integer'],
            'payment_method' => ['required', 'in:cod,bank_transfer,qr,momo,zalopay'],
            'payment_status' => ['nullable', 'in:pending,paid,failed'],
            'shipping_partner' => ['nullable', 'in:viettelpost,ghtk,ghn'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'voucher_id' => ['nullable', 'integer', 'exists:vouchers,id'],
            'voucher_code' => ['nullable', 'string', 'max:255'],
            'voucher_discount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:pending,processing,completed,cancelled'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

