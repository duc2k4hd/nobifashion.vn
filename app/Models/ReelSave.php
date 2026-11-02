<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReelSave extends Model
{
    protected $table = 'reel_saves';

    protected $fillable = [
        'reel_id',
        'user_id',
        'anonymous_user_id',
        'ip_address',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function reel(): BelongsTo
    {
        return $this->belongsTo(Reel::class, 'reel_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'user_id');
    }

    public function anonymousUser(): BelongsTo
    {
        return $this->belongsTo(AnonymousUser::class, 'anonymous_user_id');
    }
}
