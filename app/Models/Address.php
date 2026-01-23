<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'full_name',
        'phone_number',
        'detail_address',
        'ward',
        'district',
        'province',
        'province_code',
        'district_code',
        'ward_code',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'address_type',
        'notes',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public const TYPE_HOME = 'home';
    public const TYPE_WORK = 'work';

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeFilter($query, array $filters = [])
    {
        if (!empty($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }

        if (!empty($filters['province'])) {
            $query->where('province', $filters['province']);
        }

        if (!empty($filters['district'])) {
            $query->where('district', $filters['district']);
        }

        if (!empty($filters['address_type'])) {
            $query->where('address_type', $filters['address_type']);
        }

        if (!empty($filters['is_default'])) {
            $query->where('is_default', (bool) $filters['is_default']);
        }

        return $query;
    }

    public function shortLabel(): string
    {
        return sprintf(
            '%s, %s, %s',
            $this->detail_address,
            $this->district,
            $this->province
        );
    }
}
