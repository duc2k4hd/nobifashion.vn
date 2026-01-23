<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SitemapConfig extends Model
{
    use HasFactory;

    protected $table = 'sitemap_configs';

    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    /**
     * Get config value with type casting
     */
    public function getValueAttribute($value)
    {
        return match($this->type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            'datetime' => $value ? \Carbon\Carbon::parse($value) : null,
            default => $value,
        };
    }

    /**
     * Set config value
     */
    public function setValueAttribute($value)
    {
        $this->attributes['value'] = match($this->type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) $value,
            'json' => json_encode($value),
            'datetime' => $value instanceof \Carbon\Carbon ? $value->toDateTimeString() : $value,
            default => $value,
        };
    }

    /**
     * Get config by key
     */
    public static function getValue(string $key, $default = null)
    {
        $config = static::where('key', $key)->first();
        return $config ? $config->value : $default;
    }

    /**
     * Set config by key
     */
    public static function setValue(string $key, $value, string $type = 'string'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );
    }
}
