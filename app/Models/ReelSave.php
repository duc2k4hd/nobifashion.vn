<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReelSave extends Model
{
    public $timestamps = false;
    protected $table = 'reel_saves';

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

