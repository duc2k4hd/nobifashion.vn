<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReelProduct extends Model
{
    public $timestamps = false;
    protected $table = 'reel_products';

    protected $fillable = [
        'reel_id',
        'product_id',
        'order',
    ];
}

