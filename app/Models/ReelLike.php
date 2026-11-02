<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReelLike extends Model
{
    public $timestamps = false;
    protected $table = 'reel_likes';

    protected $fillable = [
        'reel_id',
        'user_id',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}

