<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_public',
        'is_required',
    ];

    protected $casts = [
        'is_public'   => 'boolean',
        'is_required' => 'boolean',
    ];

    // ------------------------------
    // Scope
    // ------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    // ------------------------------
    // Hàm tiện ích
    // ------------------------------

    /**
     * Trả về value đã parse theo type
     */
    public function getParsedValue()
    {
        return match ($this->type) {
            'boolean' => (bool) $this->value,
            'integer' => (int) $this->value,
            'float'   => (float) $this->value,
            'json'    => json_decode($this->value, true),
            default   => $this->value,
        };
    }

    /**
     * Lấy nhanh setting theo key
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->getParsedValue() : $default;
    }

    protected static function booted()
    {
        static::saved(function () {
            Cache::forget('settings');
        });

        static::deleted(function () {
            Cache::forget('settings');
        });
    }

    /**
     * Cập nhật hoặc tạo mới setting
     */
    public static function setValue(
        string $key,
        $value,
        string $type = 'string',
        string $group = 'general',
        string $label = null,
        string $description = null,
        bool $isPublic = false,
        bool $isRequired = false
    ): Setting {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value'       => is_array($value) ? json_encode($value) : $value,
                'type'        => $type,
                'group'       => $group,
                'label'       => $label,
                'description' => $description,
                'is_public'   => $isPublic,
                'is_required' => $isRequired,
            ]
        );
    }
}
