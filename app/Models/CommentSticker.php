<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentSticker extends Model
{
    use HasFactory;

    protected $table = 'comment_stickers';

    protected $fillable = [
        'code',
        'name',
        'asset_url',
        'category',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}

