<?php

namespace App\Http\Requests\Admin;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        $routeAccount = optional(request()->route('account'));
        $accountId = $routeAccount ? $routeAccount->id : null;
        $passwordRules = $accountId
            ? ['nullable', 'confirmed', 'min:8']
            : ['required', 'confirmed', 'min:8'];

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => $passwordRules,
            'password_confirmation' => ['same:password'],
            'role' => ['required', 'string', Rule::in(Account::roles())],
            'is_active' => ['nullable', 'boolean'],
            'logs' => ['nullable', 'string'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        $data['is_active'] = (bool)($data['is_active'] ?? true);
        $data = array_filter($data, function ($value, $key) {
            return $key !== 'password_confirmation';
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}


