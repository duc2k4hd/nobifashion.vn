<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'title',
        'message',
        'account_id',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // ------------------------------
    // Quan hệ
    // ------------------------------

    /**
     * Người nhận thông báo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    // ------------------------------
    // Scope
    // ------------------------------

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    // ------------------------------
    // Hàm tiện ích
    // ------------------------------

    /**
     * Đánh dấu thông báo là đã đọc
     */
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Đánh dấu thông báo là chưa đọc
     */
    public function markAsUnread()
    {
        $this->update(['is_read' => false]);
    }

    /**
     * Kiểm tra thông báo đã đọc chưa
     */
    public function isRead(): bool
    {
        return $this->is_read === true;
    }
}
