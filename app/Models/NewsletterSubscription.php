<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsletterSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'newsletter_subscriptions';

    protected $fillable = [
        'email',
        'status',
        'verify_token',
        'ip_address',
        'user_agent',
        'source',
        'verified_at',
        'note',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ------------------------------
    // Scopes
    // ------------------------------

    public function scopeSubscribed($query)
    {
        return $query->where('status', 'subscribed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnsubscribed($query)
    {
        return $query->where('status', 'unsubscribed');
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // ------------------------------
    // Accessors
    // ------------------------------

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Chờ xác nhận',
            'subscribed' => 'Đã đăng ký',
            'unsubscribed' => 'Đã hủy',
            default => 'Không xác định',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'subscribed' => 'green',
            'unsubscribed' => 'red',
            default => 'gray',
        };
    }

    public function getIsVerifiedAttribute(): bool
    {
        return $this->verified_at !== null;
    }

    // ------------------------------
    // Methods
    // ------------------------------

    public function markAsVerified(): void
    {
        $this->update([
            'status' => 'subscribed',
            'verified_at' => now(),
            'verify_token' => null,
        ]);
    }

    public function markAsUnsubscribed(): void
    {
        $this->update([
            'status' => 'unsubscribed',
            'verify_token' => null,
        ]);
    }

    public function generateVerifyToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update(['verify_token' => $token]);
        return $token;
    }
}
