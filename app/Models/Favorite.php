<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    protected $table = 'favorites';

    protected $fillable = [
        'product_id',
        'account_id',
        'session_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeOfOwner($query, $accountId = null, $sessionId = null)
    {
        if ($accountId) {
            return $query->where('account_id', $accountId);
        }
        return $query->whereNull('account_id')->where('session_id', $sessionId);
    }
}


