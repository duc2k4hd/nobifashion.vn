<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentModerationRule extends Model
{
    use HasFactory;

    protected $table = 'comment_moderation_rules';

    protected $fillable = [
        'language',
        'rule_type',
        'pattern',
        'severity',
        'action',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

