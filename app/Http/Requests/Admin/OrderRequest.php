<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        return [
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'session_id' => ['nullable', 'string', 'max:255'],
            'receiver_name' => ['required', 'string', 'max:255'],
            'receiver_phone' => ['required', 'string', 'max:20'],
            'receiver_email' => ['nullable', 'email', 'max:255'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_province_id' => ['required', 'integer'],
            'shipping_district_id' => ['required', 'integer'],
            'shipping_ward_id' => ['required', 'integer'],
            'payment_method' => ['required', 'in:cod,bank_transfer,qr,momo,zalopay,payos'],
            'payment_status' => ['nullable', 'in:pending,paid,failed'],
            'transaction_code' => ['nullable', 'string', 'max:255'],
            'shipping_partner' => ['nullable', 'in:viettelpost,ghtk,ghn'],
            'shipping_tracking_code' => ['nullable', 'string', 'max:255'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'voucher_id' => ['nullable', 'integer', 'exists:vouchers,id'],
            'voucher_code' => ['nullable', 'string', 'max:255'],
            'voucher_discount' => ['nullable', 'numeric', 'min:0'],
            'delivery_status' => ['nullable', 'in:pending,shipped,delivered,returned'],
            'status' => ['nullable', 'in:pending,processing,completed,cancelled'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Đơn hàng phải có ít nhất 1 sản phẩm.',
            'items.min' => 'Đơn hàng phải có ít nhất 1 sản phẩm.',
            'items.*.product_id.required' => 'Vui lòng chọn sản phẩm.',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
            'items.*.price.min' => 'Giá phải lớn hơn hoặc bằng 0.',
        ];
    }
}

