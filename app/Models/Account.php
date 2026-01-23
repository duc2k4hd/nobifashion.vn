<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Account extends Authenticatable
{
    const ROLE_USER   = 'user';
    const ROLE_ADMIN  = 'admin';
    const ROLE_STAFF  = 'staff';
    const ROLE_WRITER = 'writer';

    const STATUS_ACTIVE    = 'active';
    const STATUS_INACTIVE  = 'inactive';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_LOCKED    = 'locked';
    const STATUS_REVIEW    = 'review';
    const STATUS_BANNED    = 'banned';
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'role',
        'login_history',
        'is_active',
        'last_password_changed_at',
        'login_attempts',
        'account_status',
        'security_flags',
    ];

    public function scopeApplyFilters($query, array $filters = [])
    {
        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhereHas('profile', function ($profileQuery) use ($keyword) {
                        $profileQuery->where('full_name', 'like', "%{$keyword}%")
                            ->orWhere('nickname', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%");
                    });
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['account_status'])) {
            $query->where('account_status', $filters['account_status']);
        }

        if (!empty($filters['email_verified'])) {
            $filters['email_verified'] === 'yes'
                ? $query->whereNotNull('email_verified_at')
                : $query->whereNull('email_verified_at');
        }

        if (!empty($filters['gender'])) {
            $query->whereHas('profile', function ($profileQuery) use ($filters) {
                $profileQuery->where('gender', $filters['gender']);
            });
        }

        if (!empty($filters['location'])) {
            $query->whereHas('profile', function ($profileQuery) use ($filters) {
                $profileQuery->where('location', 'like', "%{$filters['location']}%");
            });
        }

        if (!empty($filters['last_login_from']) || !empty($filters['last_login_to'])) {
            $query->whereBetween('login_history', [
                $filters['last_login_from'] ?? now()->subYears(10),
                $filters['last_login_to'] ?? now(),
            ]);
        }

        return $query;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function isWriter(): bool
    {
        return $this->role === self::ROLE_WRITER;
    }

    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    public function isVerified(): bool
    {
        return $this->email_verified_at !== null && $this->email_verified_at <= now();
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isSuspended(): bool
    {
        return $this->account_status === self::STATUS_SUSPENDED;
    }

    public function isLocked(): bool
    {
        return $this->account_status === self::STATUS_LOCKED;
    }

    public function displayName(): string
    {
        return $this->profile?->full_name
            ?? $this->profile?->nickname
            ?? $this->name
            ?? $this->email;
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function logs()
    {
        return $this->hasMany(AccountLog::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'created_by');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'created_by');
    }

    public function carts() {
        return $this->hasMany(Cart::class);
    }

    public function orders() {
        return $this->hasMany(Order::class);
    }

    public function addresses() {
        return $this->hasMany(Address::class);
    }

    public function affiliates() {
        return $this->hasMany(Affiliate::class);
    }

    public function comments() {
        return $this->hasMany(Comment::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function emailVerifications()
    {
        return $this->hasMany(AccountEmailVerification::class);
    }







    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public static function roles(): array
    {
        return [
            self::ROLE_USER,
            self::ROLE_ADMIN,
            self::ROLE_STAFF,
            self::ROLE_WRITER,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_SUSPENDED,
            self::STATUS_LOCKED,
            self::STATUS_REVIEW,
            self::STATUS_BANNED,
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_password_changed_at' => 'datetime',
            'login_history' => 'datetime',
            'security_flags' => 'array',
        ];
    }
}
