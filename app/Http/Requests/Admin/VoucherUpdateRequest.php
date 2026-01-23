<?php

namespace App\Http\Requests\Admin;

use App\Models\Voucher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoucherUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $voucher = $this->route('voucher');
        $voucherId = $voucher instanceof Voucher ? $voucher->id : null;

        return [
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9\-_]+$/', Rule::unique('vouchers', 'code')->ignore($voucherId)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'type' => ['required', Rule::in([
                Voucher::TYPE_PERCENTAGE,
                Voucher::TYPE_FIXED_AMOUNT,
                Voucher::TYPE_FREE_SHIPPING,
                Voucher::TYPE_SHIPPING_PERCENTAGE,
                Voucher::TYPE_SHIPPING_FIXED,
            ])],
            'value' => ['required_unless:type,' . Voucher::TYPE_FREE_SHIPPING, 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'applicable_to' => ['required', Rule::in([
                Voucher::APPLICABLE_ALL,
                Voucher::APPLICABLE_PRODUCTS,
                Voucher::APPLICABLE_CATEGORIES,
            ])],
            'applicable_ids' => ['nullable', 'array'],
            'applicable_ids.*' => ['integer', 'min:1'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'status' => ['required', Rule::in([
                Voucher::STATUS_ACTIVE,
                Voucher::STATUS_DISABLED,
                Voucher::STATUS_SCHEDULED,
            ])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper($this->input('code', '')),
            'applicable_ids' => array_filter(
                (array) $this->input('applicable_ids', []),
                fn ($id) => !empty($id)
            ),
        ]);
    }
}

