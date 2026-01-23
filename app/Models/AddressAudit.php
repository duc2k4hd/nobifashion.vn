<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddressAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'address_id',
        'performed_by',
        'action',
        'description',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'performed_by');
    }
}

