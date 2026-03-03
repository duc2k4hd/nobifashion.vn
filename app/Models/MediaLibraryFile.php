<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaLibraryFile extends Model
{
    protected $table = 'media_library_files';

    protected $fillable = [
        'name',
        'path',
        'url',
        'extension',
        'mime_type',
        'context',
        'size',
        'width',
        'height',
        'file_modified_at',
    ];

    protected $casts = [
        'size'             => 'integer',
        'width'            => 'integer',
        'height'           => 'integer',
        'file_modified_at' => 'datetime',
    ];

    /** Scope lọc theo context (post / product) */
    public function scopeForContext($query, string $context)
    {
        return $query->where('context', $context);
    }

    /** Trả về mảng dimensions hoặc null */
    public function getDimensionsAttribute(): ?array
    {
        if ($this->width && $this->height) {
            return ['width' => $this->width, 'height' => $this->height];
        }
        return null;
    }
}
