<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentModerationWhitelist extends Model
{
    use HasFactory;

    protected $table = 'comment_moderation_whitelists';

    protected $fillable = [
        'word',
        'note',
    ];
}

