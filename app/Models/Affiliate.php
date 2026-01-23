<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
    use HasFactory;

    protected $table = 'affiliates';

    protected $fillable = [
        'account_id',
        'product_id',
        'ref_code',
        'clicks',
        'conversions',
        'commission_total',
        'address',
    ];

    protected $casts = [
        'commission_total' => 'decimal:2',
        'clicks'           => 'integer',
        'conversions'      => 'integer',
    ];

    // ------------------------------
    // Quan hệ
    // ------------------------------

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // ------------------------------
    // Scope
    // ------------------------------

    public function scopeByRefCode($query, string $code)
    {
        return $query->where('ref_code', $code);
    }

    // ------------------------------
    // Hàm tiện ích
    // ------------------------------

    public function conversionRate(): float
    {
        if ($this->clicks > 0) {
            return round(($this->conversions / $this->clicks) * 100, 2);
        }
        return 0.0;
    }

    public function addClick()
    {
        $this->increment('clicks');
    }

    public function addConversion(float $commission = 0)
    {
        $this->increment('conversions');
        $this->commission_total += $commission;
        $this->save();
    }
}
