<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'campaign',
        'followers_count',
        'rating_score',
        'joined_years',
        'banner_slides',
        'faqs',
        'logo',
        'website',
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
        'followers_count' => 'integer',
        'rating_score' => 'float',
        'joined_years' => 'integer',
        'banner_slides' => 'array',
        'faqs' => 'array',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
