<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'attachment',
        'status',
        'source',
        'ip_address',
        'user_agent',
        'user_id',
        'admin_note',
        'timeline',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'timeline' => 'array',
    ];

    /**
     * Relationship với Account (nếu người dùng đã đăng nhập)
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'user_id');
    }

    /**
     * Alias cho account (backward compatibility)
     */
    public function user(): BelongsTo
    {
        return $this->account();
    }

    /**
     * Scopes
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeDone($query)
    {
        return $query->where('status', 'done');
    }

    public function scopeSpam($query)
    {
        return $query->where('status', 'spam');
    }

    public function scopeByStatus($query, ?string $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopeBySource($query, ?string $source)
    {
        if ($source) {
            return $query->where('source', $source);
        }
        return $query;
    }

    public function scopeByUserId($query, ?int $userId)
    {
        if ($userId) {
            return $query->where('user_id', $userId);
        }
        return $query;
    }

    public function scopeSearch($query, ?string $keyword)
    {
        if (!$keyword) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%")
                ->orWhere('phone', 'like', "%{$keyword}%")
                ->orWhere('subject', 'like', "%{$keyword}%")
                ->orWhere('message', 'like', "%{$keyword}%");
        });
    }

    public function scopeDateRange($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        return $query;
    }

    /**
     * Accessors
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'new' => 'Mới',
            'processing' => 'Đang xử lý',
            'done' => 'Đã xử lý',
            'spam' => 'Spam',
            default => 'Không xác định',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'new' => 'badge-new',
            'processing' => 'badge-processing',
            'done' => 'badge-done',
            'spam' => 'badge-spam',
            default => 'badge-secondary',
        };
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment) {
            return null;
        }
        return asset('storage/contacts/' . $this->attachment);
    }

    public function getAttachmentExtensionAttribute(): ?string
    {
        if (!$this->attachment) {
            return null;
        }
        return strtolower(pathinfo($this->attachment, PATHINFO_EXTENSION));
    }

    public function hasAttachment(): bool
    {
        return !empty($this->attachment);
    }

    /**
     * Kiểm tra có phải spam không (dựa trên logic phát hiện)
     */
    public function getIsSpamDetectedAttribute(): bool
    {
        // Logic này sẽ được implement trong SpamDetector service
        // Tạm thời return false, sẽ được cập nhật sau
        return false;
    }

    /**
     * Kiểm tra có thể trả lời không
     */
    public function canReply(): bool
    {
        return !empty($this->email) && $this->status !== 'spam';
    }

    /**
     * Lấy timeline
     */
    public function getTimelineAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }

    /**
     * Thêm entry vào timeline
     */
    public function addTimelineEntry(string $action, string $description, ?int $userId = null, ?string $userName = null): void
    {
        $timeline = $this->timeline ?? [];
        $timeline[] = [
            'action' => $action,
            'description' => $description,
            'user_id' => $userId ?? auth('web')->id(),
            'user_name' => $userName ?? auth('web')->user()->name ?? 'System',
            'created_at' => now()->toIso8601String(),
        ];
        $this->updateQuietly(['timeline' => $timeline]);
    }
}
