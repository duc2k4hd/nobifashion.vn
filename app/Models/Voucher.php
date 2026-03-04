<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED_AMOUNT = 'fixed_amount';
    public const TYPE_FREE_SHIPPING = 'free_shipping';
    public const TYPE_SHIPPING_PERCENTAGE = 'shipping_percentage';
    public const TYPE_SHIPPING_FIXED = 'shipping_fixed';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SCHEDULED = 'scheduled';

    public const APPLICABLE_ALL = 'all_products';
    public const APPLICABLE_PRODUCTS = 'specific_products';
    public const APPLICABLE_CATEGORIES = 'specific_categories';

    protected $table = 'vouchers';

    protected $fillable = [
        'code',
        'name',
        'description',
        'image',
        'account_id',
        'type',
        'value',
        'usage_limit',
        'usage_count',
        'per_user_limit',
        'min_order_amount',
        'max_discount_amount',
        'applicable_to',
        'applicable_ids',
        'start_at',
        'end_at',
        'status',
    ];

    protected $casts = [
        'value'               => 'decimal:2',
        'min_order_amount'    => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'applicable_ids'      => 'array',
        'start_at'            => 'datetime',
        'end_at'              => 'datetime',
    ];

    protected $appends = [
        'status_badge',
        'type_label',
        'value_label',
    ];


    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function histories()
    {
        return $this->hasMany(VoucherHistory::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            });
    }

    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, function (Builder $q, $status) {
                $q->where('status', $status);
            })
            ->when($filters['type'] ?? null, function (Builder $q, $type) {
                $q->where('type', $type);
            })
            ->when(($filters['applicable_to'] ?? null), function (Builder $q, $app) {
                $q->where('applicable_to', $app);
            })
            ->when($filters['created_by'] ?? null, function (Builder $q, $accountId) {
                $q->where('account_id', $accountId);
            })
            ->when($filters['date_from'] ?? null, function (Builder $q, $from) {
                $q->whereDate('created_at', '>=', $from);
            })
            ->when($filters['date_to'] ?? null, function (Builder $q, $to) {
                $q->whereDate('created_at', '<=', $to);
            })
            ->when($filters['search'] ?? null, function (Builder $q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('code', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%");
                });
            });
    }

    public function refreshComputedStatus(): void
    {
        if ($this->status === self::STATUS_DISABLED) {
            return;
        }

        if ($this->end_at && $this->end_at->isPast()) {
            $this->status = self::STATUS_EXPIRED;
            return;
        }

        if ($this->start_at && $this->start_at->isFuture()) {
            $this->status = self::STATUS_SCHEDULED;
            return;
        }

        $this->status = self::STATUS_ACTIVE;
    }

    public function isStackableShippingVoucher(): bool
    {
        return in_array($this->type, [
            self::TYPE_FREE_SHIPPING,
            self::TYPE_SHIPPING_PERCENTAGE,
            self::TYPE_SHIPPING_FIXED,
        ], true);
    }

    public function isValid(float $orderAmount, int $userUsageCount = 0): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->start_at && $this->start_at->isFuture()) {
            return false;
        }

        if ($this->end_at && $this->end_at->isPast()) {
            return false;
        }

        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        if ($this->per_user_limit !== null && $userUsageCount >= $this->per_user_limit) {
            return false;
        }

        if ($this->min_order_amount !== null && $orderAmount < $this->min_order_amount) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $orderAmount, float $shippingFee = 0): float
    {
        $discount = 0;

        switch ($this->type) {
            case self::TYPE_PERCENTAGE:
                $discount = $orderAmount * ($this->value / 100);
                break;
            case self::TYPE_FIXED_AMOUNT:
                $discount = $this->value;
                break;
            case self::TYPE_FREE_SHIPPING:
                $discount = $shippingFee;
                break;
            case self::TYPE_SHIPPING_PERCENTAGE:
                $discount = $shippingFee * ($this->value / 100);
                break;
            case self::TYPE_SHIPPING_FIXED:
                $discount = min($this->value, $shippingFee);
                break;
        }

        if ($this->max_discount_amount !== null && $discount > $this->max_discount_amount) {
            $discount = $this->max_discount_amount;
        }

        return max(0, min($discount, $orderAmount + $shippingFee));
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function decrementUsage(): void
    {
        if ($this->usage_count > 0) {
            $this->decrement('usage_count');
        }
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_SCHEDULED => 'info',
            self::STATUS_EXPIRED => 'secondary',
            self::STATUS_DISABLED => 'warning',
            default => 'secondary',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PERCENTAGE => 'Giảm % đơn hàng',
            self::TYPE_FIXED_AMOUNT => 'Giảm tiền đơn hàng',
            self::TYPE_FREE_SHIPPING => 'Miễn phí vận chuyển',
            self::TYPE_SHIPPING_PERCENTAGE => 'Giảm % phí vận chuyển',
            self::TYPE_SHIPPING_FIXED => 'Giảm tiền phí vận chuyển',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function getValueLabelAttribute(): string
    {
        if (in_array($this->type, [self::TYPE_PERCENTAGE, self::TYPE_SHIPPING_PERCENTAGE], true)) {
            return rtrim(rtrim(number_format($this->value, 2, '.', ''), '0'), '.') . '%';
        }

        if ($this->type === self::TYPE_FREE_SHIPPING) {
            return 'Miễn phí';
        }

        return number_format($this->value, 0, ',', '.') . 'đ';
    }
}
