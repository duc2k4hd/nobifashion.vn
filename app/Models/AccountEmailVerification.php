<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountEmailVerification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'token',
        'expires_at',
        'created_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
