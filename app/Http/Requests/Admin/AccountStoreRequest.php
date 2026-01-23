<?php

namespace App\Http\Requests\Admin;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Account::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255', Rule::unique('accounts', 'email')],
            'password' => ['required', Password::defaults(), 'confirmed'],
            'password_confirmation' => ['required', 'same:password'],
            'role' => ['required', Rule::in(Account::roles())],
            'is_active' => ['sometimes', 'boolean'],
            'account_status' => ['sometimes', Rule::in(Account::statuses())],
            'security_flags' => ['nullable', 'array'],
            'security_flags.*' => ['string', 'max:50'],
            'profile.full_name' => ['nullable', 'string', 'max:255'],
            'profile.nickname' => ['nullable', 'string', 'max:255'],
            'profile.phone' => ['nullable', 'string', 'max:20', 'regex:/^(\+?\d{1,3})?0?\d{8,12}$/'],
            'profile.gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'profile.birthday' => ['nullable', 'date'],
            'profile.location' => ['nullable', 'string', 'max:255'],
            'profile.bio' => ['nullable', 'string'],
            'profile.is_public' => ['nullable', 'boolean'],
        ];
    }
}
