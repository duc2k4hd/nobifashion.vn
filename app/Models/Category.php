<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'parent_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_canonical',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ------------------------------
    // Quan hệ
    // ------------------------------

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Quan hệ sản phẩm qua cột primary_category_id (tối ưu cho query chính).
     */
    public function primaryProducts()
    {
        return $this->hasMany(Product::class, 'primary_category_id');
    }

    /**
     * Quan hệ sản phẩm qua JSON category_ids (dùng khi cần lấy thêm).
     */
    public function extraProducts()
    {
        return Product::whereJsonContains('category_ids', $this->id);
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'category_id');
    }

    // ------------------------------
    // Scope
    // ------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    // ------------------------------
    // Hàm tiện ích
    // ------------------------------

    /**
     * Lấy toàn bộ sản phẩm thuộc category này
     * (bao gồm primary và extra).
     */
    public function allProducts()
    {
        $primary = $this->primaryProducts()->get();
        $extra   = $this->extraProducts()->get();

        return $primary->merge($extra)->unique('id');
    }


    /**
     * Trả về đường dẫn đầy đủ (cha > con).
     */
    public function fullPath(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }
}
