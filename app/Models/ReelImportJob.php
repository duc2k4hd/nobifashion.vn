<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReelImportJob extends Model
{
    use HasFactory;

    protected $table = 'reel_import_jobs';

    protected $fillable = [
        'file_name',
        'source_type',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'skipped_rows',
        'status',
        'errors_log',
        'created_by',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'successful_rows' => 'integer',
        'failed_rows' => 'integer',
        'skipped_rows' => 'integer',
        'errors_log' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'created_by');
    }
}

