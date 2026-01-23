<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashSalePriceLog extends Model
{
    use HasFactory;

    protected $table = 'flash_sale_price_logs';

    protected $fillable = [
        'flash_sale_item_id',
        'old_price',
        'new_price',
        'changed_by',
        'changed_at',
        'reason',
    ];

    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'changed_at' => 'datetime',
    ];

    // Quan hệ N-1: Thuộc về Flash Sale Item
    public function flashSaleItem()
    {
        return $this->belongsTo(FlashSaleItem::class, 'flash_sale_item_id');
    }

    // Quan hệ N-1: Account thay đổi
    public function changer()
    {
        return $this->belongsTo(Account::class, 'changed_by');
    }
}
