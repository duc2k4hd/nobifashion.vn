<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ProductFaq extends Model
{
    use HasFactory;

    protected static ?bool $hasIsActiveColumn = null;

    protected $table = 'product_faqs';

    protected $fillable = [
        'product_id',
        'question',
        'answer',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ------------------------------
    // Quan hệ
    // ------------------------------

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // ------------------------------
    // Scope
    // ------------------------------

    public function scopeActive($query)
    {
        if (! static::hasIsActiveColumn()) {
            return $query;
        }

        return $query->where('is_active', true);
    }

    // ------------------------------
    // Hàm tiện ích
    // ------------------------------

    public function isActive(): bool
    {
        if (! static::hasIsActiveColumn()) {
            return true;
        }

        return $this->is_active === true;
    }

    public function activate()
    {
        if (! static::hasIsActiveColumn()) {
            return;
        }

        $this->update(['is_active' => true]);
    }

    public function deactivate()
    {
        if (! static::hasIsActiveColumn()) {
            return;
        }

        $this->update(['is_active' => false]);
    }

    protected static function hasIsActiveColumn(): bool
    {
        if (static::$hasIsActiveColumn !== null) {
            return static::$hasIsActiveColumn;
        }

        return static::$hasIsActiveColumn = Schema::hasColumn((new static())->getTable(), 'is_active');
    }
}
