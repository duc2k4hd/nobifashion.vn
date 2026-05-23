<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ProductHowTo extends Model
{
    use HasFactory;

    protected static ?bool $hasIsActiveColumn = null;

    protected $table = 'product_how_tos';

    protected $fillable = [
        'product_id',
        'title',
        'description',
        'supplies',
        'steps',
        'is_active',
    ];

    protected $casts = [
        'supplies' => 'array', // JSON danh sách vật liệu
        'steps'     => 'array', // JSON danh sách các bước
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

    /**
     * Trả về danh sách vật liệu
     */
    public function getMaterialsList(): array
    {
        return $this->supplies ?? [];
    }

    /**
     * Trả về danh sách các bước
     */
    public function getStepsList(): array
    {
        return $this->steps ?? [];
    }

    /**
     * Bật hướng dẫn
     */
    public function activate()
    {
        if (! static::hasIsActiveColumn()) {
            return;
        }

        $this->update(['is_active' => true]);
    }

    /**
     * Tắt hướng dẫn
     */
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
