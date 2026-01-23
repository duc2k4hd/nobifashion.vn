<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductHowTo extends Model
{
    use HasFactory;

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
        $this->update(['is_active' => true]);
    }

    /**
     * Tắt hướng dẫn
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }
}
