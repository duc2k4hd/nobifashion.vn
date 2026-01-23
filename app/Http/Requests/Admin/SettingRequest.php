<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingRequest extends FormRequest
{
    protected array $allowedTypes = [
        'string',
        'text',
        'textarea',
        'integer',
        'float',
        'number',
        'boolean',
        'json',
        'email',
        'url',
        'image',
    ];

    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        $setting = $this->route('setting');
        $settingId = $setting?->id;

        return [
            'label' => ['nullable', 'string', 'max:255'],
            'key' => [
                'required',
                'string',
                'max:191',
                'regex:/^[a-z0-9_\-\.]+$/',
                Rule::unique('settings', 'key')->ignore($settingId),
            ],
            'group' => ['nullable', 'string', 'max:191'],
            'type' => ['required', Rule::in($this->allowedTypes)],
            'value' => ['nullable'],
            'description' => ['nullable', 'string'],
            'is_public' => ['sometimes', 'boolean'],
            'is_required' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'key' => $this->key ? strtolower(trim($this->key)) : null,
            'group' => $this->group ? strtolower(trim($this->group)) : null,
            'is_public' => $this->boolean('is_public'),
            'is_required' => $this->boolean('is_required'),
        ]);
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $type = $this->input('type');
                $value = $this->input('value');

                if ($type === 'json' && !empty($value)) {
                    json_decode($value, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $validator->errors()->add('value', 'Giá trị JSON không hợp lệ.');
                    }
                }

                if ($type === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $validator->errors()->add('value', 'Giá trị phải là email hợp lệ.');
                }

                if ($type === 'url' && !empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $validator->errors()->add('value', 'Giá trị phải là URL hợp lệ.');
                }
            }
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        $data['is_public'] = (bool) ($data['is_public'] ?? false);
        $data['is_required'] = (bool) ($data['is_required'] ?? false);

        return $data;
    }
}


