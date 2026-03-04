<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $table = 'profiles';

    protected $fillable = [
        'account_id',
        'full_name',
        'nickname',
        'avatar',
        'sub_avatar',
        'bio',
        'gender',
        'birthday',
        'location',
        'phone',
        'is_public',
        'avatar_history',
        'sub_avatar_history',
        // 通知偏好
        'notify_order_created',
        'notify_order_updated',
        'notify_order_shipped',
        'notify_order_completed',
        'notify_promotions',
        'notify_flash_sale',
        'notify_new_products',
        'notify_stock_alert',
        'notify_security',
        'notify_via_email',
        'notify_via_sms',
        'notify_via_in_app',
        // 隐私设置
        'show_order_history',
        'show_favorites',
        // 偏好设置
        'preferred_language',
        'preferred_timezone',
        'preferred_currency',
    ];

    protected $casts = [
        'birthday'  => 'date',
        'is_public' => 'boolean',
        'avatar_history' => 'array',
        'sub_avatar_history' => 'array',
        // 通知偏好
        'notify_order_created' => 'boolean',
        'notify_order_updated' => 'boolean',
        'notify_order_shipped' => 'boolean',
        'notify_order_completed' => 'boolean',
        'notify_promotions' => 'boolean',
        'notify_flash_sale' => 'boolean',
        'notify_new_products' => 'boolean',
        'notify_stock_alert' => 'boolean',
        'notify_security' => 'boolean',
        'notify_via_email' => 'boolean',
        'notify_via_sms' => 'boolean',
        'notify_via_in_app' => 'boolean',
        // 隐私设置
        'show_order_history' => 'boolean',
        'show_favorites' => 'boolean',
    ];

    // ------------------------------
    // Quan hệ
    // ------------------------------

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    // ------------------------------
    // Scope
    // ------------------------------

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    // ------------------------------
    // Hàm tiện ích
    // ------------------------------


    public function displayName(): string
    {
        return $this->nickname ?: $this->full_name;
    }

    public function age(): ?int
    {
        return $this->birthday ? $this->birthday->age : null;
    }

    public function hasAvatar(): bool
    {
        return !empty($this->avatar);
    }
}
