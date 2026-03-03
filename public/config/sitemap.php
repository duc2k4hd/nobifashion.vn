<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sitemap Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for sitemap generation and management
    |
    */

    'enabled' => env('SITEMAP_ENABLED', true),

    'cache' => [
        'enabled' => env('SITEMAP_CACHE_ENABLED', true),
        'ttl' => env('SITEMAP_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'sitemap:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Rebuild Sitemap
    |--------------------------------------------------------------------------
    |
    | When enabled, sitemap will be automatically rebuilt when content is
    | created, updated, or deleted. This ensures sitemap is always up-to-date
    | but may impact performance on high-traffic sites.
    |
    */
    'auto_rebuild' => env('SITEMAP_AUTO_REBUILD', true),

    'urls_per_file' => env('SITEMAP_URLS_PER_FILE', 10000),

    'defaults' => [
        'changefreq' => [
            'home' => 'always',
            'category' => 'daily',
            'product' => 'daily',
            'post' => 'weekly',
            'page' => 'monthly',
            'tag' => 'weekly',
            'image' => 'monthly',
        ],
        'priority' => [
            'home' => 1.0,
            'category' => 0.9,
            'product' => 0.9,
            'post' => 0.7,
            'page' => 0.6,
            'tag' => 0.5,
            'image' => 0.5,
        ],
    ],

    'types' => [
        'posts' => [
            'enabled' => env('SITEMAP_POSTS_ENABLED', true),
            'route' => 'sitemap-posts',
            'model' => \App\Models\Post::class,
        ],
        'products' => [
            'enabled' => env('SITEMAP_PRODUCTS_ENABLED', true),
            'route' => 'sitemap-products',
            'model' => \App\Models\Product::class,
        ],
        'categories' => [
            'enabled' => env('SITEMAP_CATEGORIES_ENABLED', true),
            'route' => 'sitemap-categories',
            'model' => \App\Models\Category::class,
        ],
        'tags' => [
            'enabled' => env('SITEMAP_TAGS_ENABLED', true),
            'route' => 'sitemap-tags',
            'model' => \App\Models\Tag::class,
        ],
        'pages' => [
            'enabled' => env('SITEMAP_PAGES_ENABLED', true),
            'route' => 'sitemap-pages',
            'model' => null, // Custom pages
        ],
        'images' => [
            'enabled' => env('SITEMAP_IMAGES_ENABLED', true),
            'route' => 'sitemap-images',
            'model' => null, // Extracted from posts/products
        ],
    ],

    'ping' => [
        'enabled' => env('SITEMAP_PING_ENABLED', true),
        'google' => 'https://www.google.com/ping?sitemap=',
        'bing' => 'https://www.bing.com/ping?sitemap=',
        'timeout' => 5,
    ],

    'exclude' => [
        'urls' => [
            '/admin',
            '/admin/*',
            '/api',
            '/api/*',
        ],
        'patterns' => [
            '/\?.*$/', // Query strings
        ],
    ],

    'base_url' => env('APP_URL', 'http://localhost'),
];


