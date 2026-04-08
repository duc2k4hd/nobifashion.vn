<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YodyTempProduct extends Model
{
    protected $table = 'yody_temp_products';

    protected $fillable = [
        'sku',
        'name',
        'slug',
        'source_url',
        'primary_category_slug',
        'crawl_batch',
        'variant_count',
        'image_count',
        'image_files',
        'preview_relative_path',
        'first_crawled_at',
        'last_crawled_at',
    ];

    protected $casts = [
        'variant_count' => 'integer',
        'image_count' => 'integer',
        'image_files' => 'array',
        'first_crawled_at' => 'datetime',
        'last_crawled_at' => 'datetime',
    ];
}
